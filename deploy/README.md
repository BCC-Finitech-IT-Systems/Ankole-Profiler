# Server setup

`setup-server.sh` is a one-shot bootstrap for a fresh Ubuntu/Debian box. Run it
once to install every system dependency this app needs and bring the site up:
PHP 8.3 (+ extensions), Composer, Node.js, a database server, a web server,
and a queue process manager. It also builds the app and runs migrations.

To have pushes to the `staging` branch deploy automatically, follow it with
`setup-staging-runner.sh` (see [Staging deploys](#staging-deploys) below).

## Usage

On the target server, as root:

```bash
git clone https://github.com/BCC-Finitech-IT-Systems/Ankole-Profiler.git
cd Ankole-Profiler
sudo ./deploy/setup-server.sh
```

The script prints a summary of what it's about to do and asks for
confirmation; pass `-y` to skip the prompt for unattended runs.

## Options (environment variables)

| Variable | Default | Purpose |
|---|---|---|
| `APP_DIR` | `/var/www/ankole-profiler` | Where the app is deployed. Matches the path both deploy workflows use. |
| `WEB_SERVER` | `nginx` | `nginx` (php-fpm; what staging runs) or `apache` (mod_php). |
| `QUEUE_MANAGER` | `supervisor` with nginx, `systemd` with apache | How `queue:work` is kept running. |
| `PHP_VERSION` | `8.3` | Must match what the deploy workflows invoke (`php8.3`). |
| `DOMAIN` | `_` (catch-all) | `server_name` / `ServerName` for the vhost. |
| `APP_ENV_NAME` | `production` | Written to `.env` as `APP_ENV`; also sets `APP_DEBUG`. |
| `DB_ENGINE` | `mysql` | `mysql` or `mariadb`. |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `ankole_profiler` / `ankole_profiler` / random | Created on the local DB server. |
| `ENABLE_TLS`, `CERTBOT_EMAIL` | `false` | Set `ENABLE_TLS=true` with a real `DOMAIN` and `CERTBOT_EMAIL` to provision Let's Encrypt via certbot. |
| `DEPLOY_USER` | unset | OS user your CI runner executes as. When set, the script writes `/etc/sudoers.d/ankole-profiler-deploy` granting passwordless sudo for the commands a flat (non-release) deploy needs. Not needed for staging — `setup-staging-runner.sh` writes its own rules. |
| `GH_RUNNER_URL`, `GH_RUNNER_TOKEN`, `GH_RUNNER_LABELS` | unset | When both URL and token are set, registers this box as a GitHub Actions self-hosted runner (as a dedicated `githubrunner` user, installed as a service) with labels matching the workflow's `runs-on`. Get a registration token from the repo's Settings → Actions → Runners → New self-hosted runner (it expires quickly, so export it right before running). |

Re-running the script is safe: package installs are idempotent, and an
existing `.env` is never overwritten.

## Staging deploys

`.github/workflows/deploy-ankole-staging.yml` deploys every push to the
`staging` branch to the staging box (10.0.1.117) through a self-hosted runner
labelled `ankole-profiler-117`. It builds each commit into
`/var/www/ankole-profiler/releases/<sha>`, shares `.env` and `storage/` from
`shared/`, and atomically repoints the `current` symlink that nginx and the
queue worker serve from (rolling back if the health check returns a 5xx).

After `setup-server.sh`, run this once on the box to switch it to that layout
and register the runner:

```bash
sudo RUNNER_TOKEN=<token> ./deploy/setup-staging-runner.sh
```

Get the token from Settings → Actions → Runners → New self-hosted runner (it
expires after an hour). The script runs the runner as a dedicated
`ankole-runner` user with passwordless sudo for only the commands the workflow
uses, and adds public DNS resolvers if the box can't resolve `github.com`.

## What it deliberately does not do

- It does not run `populate-database.php` or `seed_subcategories.php` (repo
  root) — those are manual data-seeding scripts, reviewed and run by hand.
- It does not fill in real SMS/WhatsApp provider credentials — those are
  secrets only you have; the script leaves the relevant `.env` keys blank
  with a reminder printed at the end.
- If `.env` already exists at `$APP_DIR/.env`, it's left untouched so a
  re-run never clobbers production secrets.
