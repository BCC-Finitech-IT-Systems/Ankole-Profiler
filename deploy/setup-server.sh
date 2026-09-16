#!/usr/bin/env bash
#
# One-shot bootstrap for a fresh Ubuntu/Debian server to host Ankole Profiler.
#
# Run this ONCE, as root, from inside a checked-out copy of this repository:
#
#   git clone https://github.com/BCC-Finitech-IT-Systems/Ankole-Profiler.git
#   cd Ankole-Profiler
#   sudo ./deploy/setup-server.sh
#
# It installs every system package the app needs (PHP 8.3, Composer, Node.js,
# a database server, a web server, a queue process manager), deploys the
# checked-out code to APP_DIR, configures the database, builds the app, and
# wires up the web server + queue worker so the site is live at the end of
# the run. It also (optionally) prepares this box to run the GitHub Actions
# deploy pipeline already defined in .github/workflows/, so subsequent
# `git push` deploys work without further manual setup.
#
# See deploy/README.md for the full list of environment variables this
# script honors and what each stack option configures.

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration (override any of these by exporting them before running)
# ---------------------------------------------------------------------------
APP_DIR="${APP_DIR:-/var/www/ankole-profiler}"
PHP_VERSION="${PHP_VERSION:-8.3}"
NODE_MAJOR="${NODE_MAJOR:-20}"

# nginx+php-fpm+supervisor matches .github/workflows/deploy.yml (production).
# apache+systemd matches .github/workflows/deploy-ankole-staging-166.yml.
WEB_SERVER="${WEB_SERVER:-nginx}"          # nginx | apache
QUEUE_MANAGER="${QUEUE_MANAGER:-}"         # supervisor | systemd (defaults per WEB_SERVER below)

DOMAIN="${DOMAIN:-_}"                      # server_name / ServerName; "_" = catch-all, any Host header
APP_ENV_NAME="${APP_ENV_NAME:-production}"
APP_URL="${APP_URL:-}"                     # defaults to http(s)://$DOMAIN below

DB_ENGINE="${DB_ENGINE:-mysql}"            # mysql | mariadb
DB_DATABASE="${DB_DATABASE:-ankole_profiler}"
DB_USERNAME="${DB_USERNAME:-ankole_profiler}"
DB_PASSWORD="${DB_PASSWORD:-}"             # random one is generated below if left blank

DEPLOY_USER="${DEPLOY_USER:-}"             # OS user the CI/CD pipeline (self-hosted runner) runs as;
                                            # set this to wire up passwordless sudo for deploy.yml's commands

ENABLE_TLS="${ENABLE_TLS:-false}"          # true to request a Let's Encrypt cert for $DOMAIN via certbot
CERTBOT_EMAIL="${CERTBOT_EMAIL:-}"

GH_RUNNER_URL="${GH_RUNNER_URL:-}"         # e.g. https://github.com/BCC-Finitech-IT-Systems/Ankole-Profiler
GH_RUNNER_TOKEN="${GH_RUNNER_TOKEN:-}"     # registration token from GitHub -> Settings -> Actions -> Runners
GH_RUNNER_LABELS="${GH_RUNNER_LABELS:-}"   # defaults per WEB_SERVER below
GH_RUNNER_NAME="${GH_RUNNER_NAME:-$(hostname)-ankole-profiler}"

ASSUME_YES="${ASSUME_YES:-false}"

if [[ "${QUEUE_MANAGER}" == "" ]]; then
  if [[ "${WEB_SERVER}" == "apache" ]]; then QUEUE_MANAGER="systemd"; else QUEUE_MANAGER="supervisor"; fi
fi
if [[ "${GH_RUNNER_LABELS}" == "" ]]; then
  if [[ "${WEB_SERVER}" == "apache" ]]; then GH_RUNNER_LABELS="ankole-profiler-166"; else GH_RUNNER_LABELS="ankole-profiler"; fi
fi
if [[ "${APP_URL}" == "" ]]; then
  if [[ "${DOMAIN}" == "_" ]]; then APP_URL="http://localhost"; else APP_URL="https://${DOMAIN}"; fi
fi

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"
PHP_BIN="php${PHP_VERSION}"

for arg in "$@"; do
  case "$arg" in
    -y|--yes) ASSUME_YES="true" ;;
    -h|--help)
      grep '^#' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

log()  { printf '\n\033[1;32m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m!!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

[[ "$(id -u)" == "0" ]] || die "Run this script as root (e.g. with sudo)."
[[ "${WEB_SERVER}" == "nginx" || "${WEB_SERVER}" == "apache" ]] || die "WEB_SERVER must be nginx or apache."
[[ "${DB_ENGINE}" == "mysql" || "${DB_ENGINE}" == "mariadb" ]] || die "DB_ENGINE must be mysql or mariadb."
[[ -f "${SOURCE_DIR}/artisan" ]] || die "Run this from a checked-out copy of the repo (artisan not found at ${SOURCE_DIR})."

if [[ "${DB_PASSWORD}" == "" ]]; then
  DB_PASSWORD="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)"
fi

log "Ankole Profiler server setup"
cat <<SUMMARY
  App directory:   ${APP_DIR}
  Source checkout: ${SOURCE_DIR}
  PHP version:     ${PHP_VERSION}
  Web server:      ${WEB_SERVER}
  Queue manager:   ${QUEUE_MANAGER}
  Database:        ${DB_ENGINE} (db=${DB_DATABASE}, user=${DB_USERNAME})
  Domain:          ${DOMAIN}
  App URL:         ${APP_URL}
  App env:         ${APP_ENV_NAME}
  Deploy user:     ${DEPLOY_USER:-<none - CI sudoers rules will be skipped>}
  GH runner:       ${GH_RUNNER_URL:+configuring}${GH_RUNNER_URL:-<none - GH_RUNNER_URL/GH_RUNNER_TOKEN not set, skipping>}
SUMMARY

if [[ "${ASSUME_YES}" != "true" ]]; then
  read -r -p "Proceed? [y/N] " reply
  [[ "$reply" =~ ^[Yy]$ ]] || die "Aborted."
fi

# ---------------------------------------------------------------------------
detect_os() {
  [[ -f /etc/os-release ]] || die "Unsupported OS: /etc/os-release not found."
  . /etc/os-release
  OS_ID="$ID"
  OS_CODENAME="${VERSION_CODENAME:-}"
  [[ "$OS_ID" == "ubuntu" || "$OS_ID" == "debian" ]] || die "This script supports Ubuntu/Debian only (found: $OS_ID)."
}

install_system_packages() {
  log "Installing system packages (this can take a few minutes)"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y ca-certificates curl gnupg unzip git software-properties-common lsb-release apt-transport-https

  if ! apt-cache policy "php${PHP_VERSION}" 2>/dev/null | grep -q Candidate:.*"${PHP_VERSION}"; then
    if [[ "$OS_ID" == "ubuntu" ]]; then
      add-apt-repository -y ppa:ondrej/php
    else
      install -d -m 0755 /etc/apt/keyrings
      curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
      echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${OS_CODENAME} main" \
        > /etc/apt/sources.list.d/sury-php.list
    fi
    apt-get update -y
  fi

  local php_packages=(
    "php${PHP_VERSION}-cli" "php${PHP_VERSION}-common" "php${PHP_VERSION}-mysql"
    "php${PHP_VERSION}-sqlite3" "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml"
    "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-bcmath"
    "php${PHP_VERSION}-gd" "php${PHP_VERSION}-intl" "php${PHP_VERSION}-opcache"
    "php${PHP_VERSION}-readline"
  )
  if [[ "${WEB_SERVER}" == "nginx" ]]; then
    php_packages+=("php${PHP_VERSION}-fpm")
  else
    php_packages+=("libapache2-mod-php${PHP_VERSION}")
  fi
  apt-get install -y "${php_packages[@]}"

  if ! command -v composer >/dev/null 2>&1; then
    log "Installing Composer"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
  fi

  if ! command -v node >/dev/null 2>&1 || [[ "$(node -v | sed -E 's/^v([0-9]+).*/\1/')" -lt "${NODE_MAJOR}" ]]; then
    log "Installing Node.js ${NODE_MAJOR}.x"
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
  fi

  if [[ "${DB_ENGINE}" == "mysql" ]]; then
    apt-get install -y mysql-server
    DB_SERVICE="mysql"
  else
    apt-get install -y mariadb-server
    DB_SERVICE="mariadb"
  fi
  systemctl enable --now "${DB_SERVICE}"

  if [[ "${WEB_SERVER}" == "nginx" ]]; then
    apt-get install -y nginx
  else
    apt-get install -y apache2
  fi

  if [[ "${QUEUE_MANAGER}" == "supervisor" ]]; then
    apt-get install -y supervisor
    systemctl enable --now supervisor
  fi

  if [[ "${ENABLE_TLS}" == "true" ]]; then
    if [[ "${WEB_SERVER}" == "nginx" ]]; then
      apt-get install -y certbot python3-certbot-nginx
    else
      apt-get install -y certbot python3-certbot-apache
    fi
  fi
}

setup_database() {
  log "Creating database and user"
  mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL
}

sync_app_code() {
  log "Deploying source to ${APP_DIR}"
  mkdir -p "${APP_DIR}"
  rsync -a --delete \
    --exclude='.env' \
    --exclude='.git' \
    --exclude='storage/' \
    --exclude='bootstrap/cache/' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    "${SOURCE_DIR}/" "${APP_DIR}/"
  mkdir -p "${APP_DIR}/bootstrap/cache"
  mkdir -p "${APP_DIR}/storage/logs"
  mkdir -p "${APP_DIR}/storage/framework/cache" "${APP_DIR}/storage/framework/sessions" "${APP_DIR}/storage/framework/views"
  mkdir -p "${APP_DIR}/storage/app/public" "${APP_DIR}/storage/app/private"
}

write_env_file() {
  local env_file="${APP_DIR}/.env"
  if [[ -f "${env_file}" ]]; then
    warn ".env already exists at ${env_file} - leaving it untouched."
    return
  fi
  log "Writing ${env_file}"
  local app_debug="false"
  [[ "${APP_ENV_NAME}" != "production" ]] && app_debug="true"
  cat > "${env_file}" <<ENV
APP_NAME="Ankole Profiler"
APP_ENV=${APP_ENV_NAME}
APP_KEY=
APP_DEBUG=${app_debug}
APP_TIMEZONE=UTC
APP_URL=${APP_URL}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=${DB_ENGINE}
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@ankoleprofiler.com"
MAIL_FROM_NAME="\${APP_NAME}"

# SMS (Africa's Talking) - fill in real credentials before relying on SMS features
SMS_PROVIDER=africas_talking
AFRICAS_TALKING_API_KEY=
AFRICAS_TALKING_USERNAME=
AFRICAS_TALKING_SENDER_ID=
AFRICAS_TALKING_SANDBOX=true
AT_USERNAME=
AT_API_KEY=
AT_ENVIRONMENT=sandbox
AT_SENDER_ID=SHORTCODE
AT_WEBHOOK_TOKEN=

WHATSAPP_PROVIDER=twilio
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM_NUMBER=
TWILIO_WHATSAPP_FROM=
META_WHATSAPP_ACCESS_TOKEN=
META_WHATSAPP_PHONE_NUMBER_ID=
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_VERIFY_TOKEN=
META_WHATSAPP_WEBHOOK_SECRET=

VITE_APP_NAME="\${APP_NAME}"
ENV
}

install_php_dependencies() {
  log "Installing PHP dependencies (composer install)"
  (cd "${APP_DIR}" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress)
}

install_node_and_build() {
  log "Installing Node dependencies and building frontend assets"
  (cd "${APP_DIR}" && npm ci --no-audit --no-fund && npm run build)
}

fix_runtime_permissions() {
  log "Handing storage, bootstrap/cache and .env to www-data"
  chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" "${APP_DIR}/.env"
  chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
  chmod 640 "${APP_DIR}/.env"
}

run_artisan_setup() {
  log "Running Laravel setup (key:generate, migrate, cache, storage:link)"
  if ! grep -q '^APP_KEY=base64:' "${APP_DIR}/.env"; then
    sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" key:generate --force
  fi
  sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" migrate --force
  # route:cache is intentionally skipped: the root ("/") route is a Closure,
  # which route caching mishandles and turns into an HTTP 405.
  sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" route:clear
  sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" config:cache
  sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" view:cache
  sudo -u www-data "${PHP_BIN}" "${APP_DIR}/artisan" storage:link || true
}

configure_nginx() {
  log "Configuring nginx"
  cat > /etc/nginx/sites-available/ankole-profiler.conf <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    client_max_body_size 64M;

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/${PHP_FPM_SERVICE}.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
  ln -sf /etc/nginx/sites-available/ankole-profiler.conf /etc/nginx/sites-enabled/ankole-profiler.conf
  [[ -e /etc/nginx/sites-enabled/default ]] && rm -f /etc/nginx/sites-enabled/default
  nginx -t
  systemctl enable --now nginx
  systemctl reload nginx
  systemctl enable --now "${PHP_FPM_SERVICE}"
}

configure_apache() {
  log "Configuring Apache"
  a2enmod rewrite >/dev/null
  cat > /etc/apache2/sites-available/ankole-profiler.conf <<APACHE
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${APP_DIR}/public
    LimitRequestBody 67108864

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/ankole-profiler-error.log
    CustomLog \${APACHE_LOG_DIR}/ankole-profiler-access.log combined
</VirtualHost>
APACHE
  a2ensite ankole-profiler.conf >/dev/null
  a2dissite 000-default.conf >/dev/null 2>&1 || true
  apache2ctl configtest
  systemctl enable --now apache2
  systemctl reload apache2
}

configure_supervisor_worker() {
  log "Configuring the queue worker under supervisor"
  cat > /etc/supervisor/conf.d/ankole-profiler-worker.conf <<SUPERVISOR
[program:ankole-profiler-worker]
process_name=%(program_name)s_%(process_num)02d
command=${PHP_BIN} ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR
  supervisorctl reread
  supervisorctl update
  supervisorctl restart ankole-profiler-worker:* || supervisorctl start ankole-profiler-worker:*
}

configure_systemd_worker() {
  log "Configuring the queue worker under systemd"
  cat > /etc/systemd/system/ankole-profiler-worker.service <<SYSTEMD
[Unit]
Description=Ankole Profiler queue worker
After=network.target ${DB_SERVICE:-mysql}.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=${APP_DIR}
ExecStart=${PHP_BIN} ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
SYSTEMD
  systemctl daemon-reload
  systemctl enable --now ankole-profiler-worker
  systemctl restart ankole-profiler-worker
}

configure_tls() {
  [[ "${ENABLE_TLS}" == "true" ]] || return 0
  [[ "${DOMAIN}" != "_" ]] || { warn "ENABLE_TLS=true but DOMAIN is unset; skipping certbot."; return 0; }
  [[ "${CERTBOT_EMAIL}" != "" ]] || { warn "ENABLE_TLS=true but CERTBOT_EMAIL is unset; skipping certbot."; return 0; }
  log "Requesting a Let's Encrypt certificate for ${DOMAIN}"
  if [[ "${WEB_SERVER}" == "nginx" ]]; then
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
  else
    certbot --apache -d "${DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
  fi
}

configure_pipeline_sudoers() {
  [[ "${DEPLOY_USER}" != "" ]] || { warn "DEPLOY_USER not set; skipping CI/CD sudoers setup (see deploy/README.md)."; return 0; }
  log "Granting ${DEPLOY_USER} passwordless sudo for the deploy pipeline's commands"
  local sudoers_file="/etc/sudoers.d/ankole-profiler-deploy"
  {
    if [[ "${WEB_SERVER}" == "nginx" ]]; then
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/mkdir -p ${APP_DIR}/*"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/chown -R www-data:www-data storage bootstrap/cache"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/chmod -R 775 storage bootstrap/cache"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload ${PHP_FPM_SERVICE}"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl is-active nginx"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl is-active ${PHP_FPM_SERVICE}"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart ankole-profiler-worker:*"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl status ankole-profiler-worker:*"
      echo "${DEPLOY_USER} ALL=(www-data) NOPASSWD: ${PHP_BIN} artisan *"
    else
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/chown -R www-data:www-data ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/chmod -R 775 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload apache2"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl restart ankole-profiler-worker"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl is-active apache2"
      echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl is-active ankole-profiler-worker"
      echo "${DEPLOY_USER} ALL=(www-data) NOPASSWD: ${PHP_BIN} ${APP_DIR}/artisan *"
    fi
  } > "${sudoers_file}"
  chmod 440 "${sudoers_file}"
  visudo -cf "${sudoers_file}" || die "Generated sudoers file failed validation - check ${sudoers_file}"
}

setup_github_runner() {
  [[ "${GH_RUNNER_URL}" != "" && "${GH_RUNNER_TOKEN}" != "" ]] || {
    warn "GH_RUNNER_URL/GH_RUNNER_TOKEN not set - skipping self-hosted runner install."
    warn "To wire up the GitHub Actions pipeline later: create a registration token at"
    warn "  <repo> -> Settings -> Actions -> Runners -> New self-hosted runner"
    warn "then re-run with GH_RUNNER_URL and GH_RUNNER_TOKEN set (labels default to '${GH_RUNNER_LABELS}')."
    return 0
  }
  log "Registering a GitHub Actions self-hosted runner (labels: ${GH_RUNNER_LABELS})"
  id -u githubrunner >/dev/null 2>&1 || useradd -m -s /bin/bash githubrunner
  usermod -aG sudo githubrunner 2>/dev/null || true

  local runner_dir="/home/githubrunner/actions-runner"
  mkdir -p "${runner_dir}"
  local latest_tag
  latest_tag="$(curl -fsSL https://api.github.com/repos/actions/runner/releases/latest | grep -oP '"tag_name":\s*"v\K[0-9.]+' | head -1)"
  [[ "${latest_tag}" != "" ]] || die "Could not determine latest actions/runner release."
  local tarball="actions-runner-linux-x64-${latest_tag}.tar.gz"
  curl -fsSL -o "${runner_dir}/${tarball}" \
    "https://github.com/actions/runner/releases/download/v${latest_tag}/${tarball}"
  tar xzf "${runner_dir}/${tarball}" -C "${runner_dir}"
  rm -f "${runner_dir}/${tarball}"
  chown -R githubrunner:githubrunner "${runner_dir}"

  sudo -u githubrunner "${runner_dir}/config.sh" --unattended \
    --url "${GH_RUNNER_URL}" \
    --token "${GH_RUNNER_TOKEN}" \
    --name "${GH_RUNNER_NAME}" \
    --labels "${GH_RUNNER_LABELS}" \
    --work "_work"

  (cd "${runner_dir}" && ./svc.sh install githubrunner && ./svc.sh start)
}

# ---------------------------------------------------------------------------
detect_os
install_system_packages
setup_database
sync_app_code
write_env_file
install_php_dependencies
install_node_and_build
fix_runtime_permissions
run_artisan_setup

if [[ "${WEB_SERVER}" == "nginx" ]]; then configure_nginx; else configure_apache; fi
if [[ "${QUEUE_MANAGER}" == "supervisor" ]]; then configure_supervisor_worker; else configure_systemd_worker; fi
configure_tls
configure_pipeline_sudoers
setup_github_runner

log "Done"
cat <<DONE
Ankole Profiler is deployed to ${APP_DIR} and should be reachable at ${APP_URL}.

Database credentials (also written to ${APP_DIR}/.env):
  database: ${DB_DATABASE}
  username: ${DB_USERNAME}
  password: ${DB_PASSWORD}

Still to do:
  - Fill in real SMS/WhatsApp credentials (AT_*, AFRICAS_TALKING_*, TWILIO_*,
    META_WHATSAPP_*) in ${APP_DIR}/.env, then:
      sudo -u www-data ${PHP_BIN} ${APP_DIR}/artisan config:cache
  - Review populate-database.php / seed_subcategories.php at the repo root -
    they are optional manual data-seeding scripts, not run automatically.
DONE
[[ "${ENABLE_TLS}" != "true" ]] && [[ "${DOMAIN}" != "_" ]] && \
  echo "  - Set ENABLE_TLS=true and CERTBOT_EMAIL=you@example.com and re-run to provision HTTPS."
[[ "${DEPLOY_USER}" == "" ]] && \
  echo "  - Set DEPLOY_USER=<ci-runner-user> and re-run to let the GitHub Actions pipeline deploy without prompts."
[[ "${GH_RUNNER_URL}" == "" ]] && \
  echo "  - Set GH_RUNNER_URL and GH_RUNNER_TOKEN and re-run to register this box as the pipeline's self-hosted runner."
