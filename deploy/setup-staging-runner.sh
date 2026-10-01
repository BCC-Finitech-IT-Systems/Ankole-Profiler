#!/usr/bin/env bash
#
# One-time setup that lets .github/workflows/deploy-ankole-staging.yml deploy
# to the staging box (10.0.1.117) on every push to the `staging` branch.
#
# Run it as root on the staging server, after setup-server.sh:
#
#   sudo RUNNER_TOKEN=<registration token> ./deploy/setup-staging-runner.sh
#
# The token comes from Settings -> Actions -> Runners -> New self-hosted runner
# (or `gh api -X POST repos/<owner>/<repo>/actions/runners/registration-token`)
# and expires after an hour. Instead of RUNNER_TOKEN you can drop it in
# ~/.ankole-runner-token of the user running sudo; it is deleted once read.
#
# Re-running is safe. The script:
#   1. adds public DNS resolvers if github.com doesn't resolve (the runner
#      has to reach GitHub to pick up jobs);
#   2. converts the flat /var/www/ankole-profiler copy that setup-server.sh
#      leaves behind into the releases/ + shared/ + current layout the
#      workflow deploys into, and repoints nginx and the queue worker at
#      current/;
#   3. creates the `ankole-runner` user, grants it passwordless sudo for
#      exactly the commands the workflow runs, and registers it as a
#      self-hosted runner (label ankole-profiler-117) under systemd.

set -euo pipefail

BASE="${BASE:-/var/www/ankole-profiler}"
PHP_BIN="${PHP_BIN:-php8.3}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
NGINX_SITE="${NGINX_SITE:-/etc/nginx/sites-available/ankole-profiler.conf}"
WORKER_CONF="${WORKER_CONF:-/etc/supervisor/conf.d/ankole-profiler-worker.conf}"

RUNNER_USER="${RUNNER_USER:-ankole-runner}"
RUNNER_DIR="/home/${RUNNER_USER}/actions-runner"
RUNNER_LABELS="${RUNNER_LABELS:-ankole-profiler-117}"
RUNNER_NAME="${RUNNER_NAME:-$(hostname)-ankole-profiler}"
REPO_URL="${REPO_URL:-https://github.com/BCC-Finitech-IT-Systems/Ankole-Profiler}"
TOKEN_FILE="${TOKEN_FILE:-$(getent passwd "${SUDO_USER:-root}" | cut -d: -f6)/.ankole-runner-token}"

log()  { printf '\n\033[1;32m==>\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

[[ "$(id -u)" == "0" ]] || die "Run this script as root (e.g. with sudo)."

ensure_dns() {
  getent hosts github.com >/dev/null && return 0
  log "github.com doesn't resolve - adding public resolvers to systemd-resolved"
  mkdir -p /etc/systemd/resolved.conf.d
  cat > /etc/systemd/resolved.conf.d/ankole-public-dns.conf <<'DNS'
[Resolve]
DNS=8.8.8.8 1.1.1.1
DNS
  systemctl restart systemd-resolved
  sleep 2
  getent hosts github.com >/dev/null || die "github.com still doesn't resolve - fix DNS on this box and re-run."
}

ensure_runner_user() {
  id -u "${RUNNER_USER}" >/dev/null 2>&1 || useradd -m -s /bin/bash "${RUNNER_USER}"
  # Lets the runner prune old releases whose bootstrap/cache belongs to www-data.
  usermod -aG www-data "${RUNNER_USER}"
}

convert_layout() {
  if [[ -L "${BASE}/current" ]]; then
    log "${BASE} already uses the release layout"
  else
    [[ -f "${BASE}/artisan" ]] || die "No app found at ${BASE} - run setup-server.sh first."
    log "Converting ${BASE} to releases/ + shared/ + current"
    local initial="${BASE}/releases/initial"
    mkdir -p "${BASE}/shared" "${BASE}/releases"
    cp -a "${BASE}/.env" "${BASE}/shared/.env"
    cp -a "${BASE}/storage" "${BASE}/shared/storage"
    mkdir -p "${BASE}/shared/storage/app/public"
    rsync -a --exclude='/releases' --exclude='/shared' --exclude='/.env' --exclude='/storage' \
      "${BASE}/" "${initial}/"
    ln -sfn "${BASE}/shared/.env" "${initial}/.env"
    ln -sfn "${BASE}/shared/storage" "${initial}/storage"
    ln -sfn "${BASE}/shared/storage/app/public" "${initial}/public/storage"
    mkdir -p "${initial}/bootstrap/cache"
    ln -sfn "${initial}" "${BASE}/current"
  fi

  log "Pointing nginx and the queue worker at ${BASE}/current"
  sed -i "s#root ${BASE}/public;#root ${BASE}/current/public;#" "${NGINX_SITE}"
  nginx -t
  systemctl reload nginx
  sed -i -e "s#${PHP_BIN} ${BASE}/artisan #${PHP_BIN} ${BASE}/current/artisan #" \
         -e "s#^directory=${BASE}\$#directory=${BASE}/current#" \
         -e "s#${BASE}/storage/logs/#${BASE}/shared/storage/logs/#" "${WORKER_CONF}"
  supervisorctl reread
  supervisorctl update
  systemctl reload "${PHP_FPM_SERVICE}"

  # The flat copy is no longer served from; drop it.
  find "${BASE}" -mindepth 1 -maxdepth 1 ! -name releases ! -name shared ! -name current -exec rm -rf {} +

  chown "${RUNNER_USER}:${RUNNER_USER}" "${BASE}"
  chown -R "${RUNNER_USER}:${RUNNER_USER}" "${BASE}/releases"
  chown -h "${RUNNER_USER}:${RUNNER_USER}" "${BASE}/current"
  chown www-data:www-data "${BASE}/shared/.env"
  chmod 640 "${BASE}/shared/.env"
  chown -R www-data:www-data "${BASE}/shared/storage"
  chmod -R 775 "${BASE}/shared/storage"
  for cache in "${BASE}"/releases/*/bootstrap/cache; do
    chown -R www-data:www-data "${cache}"
    chmod -R 775 "${cache}"
  done
}

write_sudoers() {
  log "Granting ${RUNNER_USER} passwordless sudo for the deploy workflow's commands"
  local sudoers_file=/etc/sudoers.d/ankole-runner
  cat > "${sudoers_file}.tmp" <<SUDOERS
# Exactly the privileged commands .github/workflows/deploy-ankole-staging.yml runs.
${RUNNER_USER} ALL=(root) NOPASSWD: /usr/bin/chown -R www-data\:www-data ${BASE}/shared/storage ${BASE}/releases/*/bootstrap/cache
${RUNNER_USER} ALL=(root) NOPASSWD: /usr/bin/chmod -R 775 ${BASE}/shared/storage ${BASE}/releases/*/bootstrap/cache
${RUNNER_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload ${PHP_FPM_SERVICE}
${RUNNER_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart ankole-profiler-worker\:*
${RUNNER_USER} ALL=(www-data) NOPASSWD: /usr/bin/${PHP_BIN} ${BASE}/releases/*/artisan *
SUDOERS
  chmod 440 "${sudoers_file}.tmp"
  visudo -cf "${sudoers_file}.tmp" || die "Generated sudoers file failed validation - see ${sudoers_file}.tmp"
  mv "${sudoers_file}.tmp" "${sudoers_file}"
}

install_runner() {
  if [[ -f "${RUNNER_DIR}/.runner" ]]; then
    log "Runner already registered in ${RUNNER_DIR}"
  else
    local token="${RUNNER_TOKEN:-}"
    if [[ "${token}" == "" && -f "${TOKEN_FILE}" ]]; then
      token="$(tr -d '[:space:]' < "${TOKEN_FILE}")"
      rm -f "${TOKEN_FILE}"
    fi
    [[ "${token}" != "" ]] || die "No registration token - set RUNNER_TOKEN or write it to ${TOKEN_FILE}."

    log "Downloading the GitHub Actions runner"
    local version
    version="$(curl -fsSL https://api.github.com/repos/actions/runner/releases/latest | grep -oP '"tag_name":\s*"v\K[0-9.]+' | head -1)"
    [[ "${version}" != "" ]] || die "Could not determine the latest actions/runner release."
    sudo -u "${RUNNER_USER}" mkdir -p "${RUNNER_DIR}"
    curl -fsSL "https://github.com/actions/runner/releases/download/v${version}/actions-runner-linux-x64-${version}.tar.gz" \
      | sudo -u "${RUNNER_USER}" tar xz -C "${RUNNER_DIR}"
    "${RUNNER_DIR}/bin/installdependencies.sh"

    log "Registering runner ${RUNNER_NAME} (labels: ${RUNNER_LABELS})"
    (cd "${RUNNER_DIR}" && sudo -u "${RUNNER_USER}" ./config.sh --unattended --replace \
      --url "${REPO_URL}" --token "${token}" \
      --name "${RUNNER_NAME}" --labels "${RUNNER_LABELS}" --work _work)
  fi

  cd "${RUNNER_DIR}"
  [[ -f .service ]] || ./svc.sh install "${RUNNER_USER}"
  ./svc.sh start
  ./svc.sh status
}

ensure_dns
ensure_runner_user
convert_layout
write_sudoers
install_runner

log "Done - pushes to the staging branch now deploy here via ${RUNNER_NAME}."
