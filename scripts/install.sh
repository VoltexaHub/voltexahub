#!/usr/bin/env bash
# One-paste installer for VoltexaHub on a fresh Ubuntu 22.04+ / Debian 12+ host.
#
# Remote:
#   curl -fsSL https://raw.githubusercontent.com/VoltexaHub/VoltexaHub/main/scripts/install.sh | sudo bash
#
# Or interactively:
#   curl -fsSL https://raw.githubusercontent.com/VoltexaHub/VoltexaHub/main/scripts/install.sh -o install.sh
#   chmod +x install.sh
#   sudo ./install.sh
#
# The script is idempotent: re-running it updates the repo and rebuilds.

set -euo pipefail

# ---------- Config (override via env before running) ----------
REPO_URL="${REPO_URL:-https://github.com/VoltexaHub/VoltexaHub.git}"
INSTALL_DIR="${INSTALL_DIR:-/opt/voltexahub}"
BRANCH="${BRANCH:-main}"

# ---------- Helpers ----------
c_reset="\033[0m"; c_dim="\033[2m"; c_green="\033[32m"; c_amber="\033[33m"; c_red="\033[31m"; c_bold="\033[1m"
say()  { printf "${c_green}▶${c_reset} %s\n" "$*"; }
note() { printf "${c_dim}  %s${c_reset}\n" "$*"; }
warn() { printf "${c_amber}!${c_reset} %s\n" "$*"; }
die()  { printf "${c_red}✗${c_reset} %s\n" "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "This script needs root. Re-run with: sudo bash $0"

# 'curl | sudo bash' makes bash's stdin the pipe, so plain `read` either
# returns EOF or consumes the script. We try to reopen stdin from /dev/tty —
# but on some Ubuntu/sudo combos that isn't available either. Callers can
# also pre-set every input via env vars (see variable names below) to skip
# prompting entirely — that's the bulletproof path for curl|bash invocations.
if [ ! -t 0 ]; then
    exec 0</dev/tty 2>/dev/null || true
fi
INTERACTIVE=0
[ -t 0 ] && INTERACTIVE=1

# ask PROMPT [DEFAULT] [ENV_VAR]
# - If ENV_VAR is set in the environment, use that value (non-interactive override).
# - Else, if we have a tty, prompt with DEFAULT.
# - Else, fall back to DEFAULT.
ask() {
    local prompt="$1" default="${2:-}" env_name="${3:-}" var=""
    if [ -n "$env_name" ]; then
        local env_value="${!env_name:-}"
        if [ -n "$env_value" ]; then
            printf "%s" "$env_value"
            return
        fi
    fi
    if [ $INTERACTIVE -eq 1 ]; then
        read -r -p "  ${prompt}${default:+ [$default]}: " var || var=""
    fi
    printf "%s" "${var:-$default}"
}

# ask_required PROMPT ENV_VAR
ask_required() {
    local prompt="$1" env_name="${2:-}" value=""
    if [ -n "$env_name" ]; then
        local env_value="${!env_name:-}"
        if [ -n "$env_value" ]; then
            printf "%s" "$env_value"
            return
        fi
    fi
    if [ $INTERACTIVE -eq 0 ]; then
        die "'${prompt}' is required (set env var ${env_name} or re-run: curl ... -o install.sh && sudo bash install.sh)"
    fi
    for _ in 1 2 3; do
        value="$(ask "$prompt")"
        [ -n "$value" ] && { printf "%s" "$value"; return; }
        printf "${c_red}  (required — try again)${c_reset}\n" >&2
    done
    die "'${prompt}' is required"
}

random_hex() { openssl rand -hex "${1:-16}"; }

# ---------- Prereqs ----------
say "Checking prerequisites"

if ! command -v docker >/dev/null 2>&1; then
    say "Installing Docker"
    curl -fsSL https://get.docker.com | sh
fi
docker compose version >/dev/null 2>&1 || die "docker compose plugin not found after install"

command -v openssl >/dev/null 2>&1 || { apt-get update -qq && apt-get install -y -qq openssl; }
command -v git     >/dev/null 2>&1 || { apt-get update -qq && apt-get install -y -qq git; }

# ---------- Repo ----------
say "Preparing ${INSTALL_DIR}"
if [ -d "$INSTALL_DIR/.git" ]; then
    note "Existing checkout found — pulling latest on ${BRANCH}"
    git -C "$INSTALL_DIR" fetch --all --prune
    git -C "$INSTALL_DIR" checkout "$BRANCH"
    git -C "$INSTALL_DIR" pull --ff-only
else
    git clone --branch "$BRANCH" "$REPO_URL" "$INSTALL_DIR"
fi
cd "$INSTALL_DIR"

# ---------- Gather config ----------
echo ""
say "Configuring"
if [ $INTERACTIVE -eq 0 ]; then
    note "No controlling terminal detected — reading config from env vars."
    note "If any required var is missing, install will bail out with the name."
    note "Set these before re-running, or use the download-then-run form:"
    note "  curl -fsSL https://raw.githubusercontent.com/VoltexaHub/VoltexaHub/main/scripts/install.sh -o install.sh && sudo bash install.sh"
fi

DOMAIN="$(ask_required 'Domain (e.g. forum.example.com)' VX_DOMAIN)"
ADMIN_NAME="$(ask 'Admin display name' 'Admin' VX_ADMIN_NAME)"
ADMIN_HANDLE="$(ask 'Admin @handle' 'admin' VX_ADMIN_HANDLE)"
ADMIN_EMAIL="$(ask_required 'Admin email' VX_ADMIN_EMAIL)"
ADMIN_PASSWORD="$(ask 'Admin password (leave blank to auto-generate)' '' VX_ADMIN_PASSWORD)"
if [ -z "$ADMIN_PASSWORD" ]; then
    ADMIN_PASSWORD="$(random_hex 12)"
    GENERATED_PASSWORD=1
fi

SMTP_HOST="$(ask 'SMTP host (blank = log mailer, mail stays local)' '' VX_SMTP_HOST)"
SMTP_USER=""; SMTP_PASS=""; SMTP_PORT=""; SMTP_FROM=""
if [ -n "$SMTP_HOST" ]; then
    SMTP_PORT="$(ask 'SMTP port' '587' VX_SMTP_PORT)"
    SMTP_USER="$(ask 'SMTP username' '' VX_SMTP_USER)"
    SMTP_PASS="$(ask 'SMTP password' '' VX_SMTP_PASS)"
    SMTP_FROM="$(ask 'Mail From address' "forum@${DOMAIN}" VX_SMTP_FROM)"
fi

# ---------- .env ----------
say "Writing .env"
DB_PASSWORD="$(random_hex 16)"
REVERB_APP_ID="$(random_hex 4)"
REVERB_APP_KEY="$(random_hex 16)"
REVERB_APP_SECRET="$(random_hex 32)"

APP_KEY="base64:$(openssl rand -base64 32)"

if [ -f .env ]; then
    note ".env already exists — leaving it alone"
else
    cp .env.example .env
    # Write production-friendly values in-place.
    {
        echo ""
        echo "# Injected by install.sh"
        echo "APP_KEY=${APP_KEY}"
        echo "APP_ENV=production"
        echo "APP_DEBUG=false"
        echo "APP_URL=https://${DOMAIN}"

        echo "DB_CONNECTION=pgsql"
        echo "DB_HOST=postgres"
        echo "DB_PORT=5432"
        echo "DB_DATABASE=voltexahub"
        echo "DB_USERNAME=voltexa"
        echo "DB_PASSWORD=${DB_PASSWORD}"

        echo "REDIS_HOST=redis"
        echo "REDIS_PORT=6379"
        echo "CACHE_STORE=redis"
        echo "SESSION_DRIVER=redis"
        echo "QUEUE_CONNECTION=redis"

        echo "BROADCAST_CONNECTION=reverb"
        echo "REVERB_APP_ID=${REVERB_APP_ID}"
        echo "REVERB_APP_KEY=${REVERB_APP_KEY}"
        echo "REVERB_APP_SECRET=${REVERB_APP_SECRET}"
        echo "REVERB_HOST=${DOMAIN}"
        echo "REVERB_PORT=443"
        echo "REVERB_SCHEME=https"
        echo "REVERB_SERVER_HOST=0.0.0.0"
        echo "REVERB_SERVER_PORT=8081"
        echo "VITE_REVERB_APP_KEY=${REVERB_APP_KEY}"
        echo "VITE_REVERB_HOST=${DOMAIN}"
        echo "VITE_REVERB_PORT=443"
        echo "VITE_REVERB_SCHEME=https"

        if [ -n "$SMTP_HOST" ]; then
            echo "MAIL_MAILER=smtp"
            echo "MAIL_HOST=${SMTP_HOST}"
            echo "MAIL_PORT=${SMTP_PORT}"
            echo "MAIL_USERNAME=${SMTP_USER}"
            echo "MAIL_PASSWORD=${SMTP_PASS}"
            echo "MAIL_ENCRYPTION=tls"
            echo "MAIL_FROM_ADDRESS=\"${SMTP_FROM}\""
        else
            warn "No SMTP host supplied — mail will go to the log driver. Edit .env later to enable real mail."
        fi
    } >> .env
fi

# Patch the committed Postgres password so the container matches the env.
sed -i "s/POSTGRES_PASSWORD:.*/POSTGRES_PASSWORD: ${DB_PASSWORD}/" docker-compose.yml

# ---------- Caddyfile ----------
say "Configuring Caddy for ${DOMAIN}"
sed -i "s/forum\.example\.com/${DOMAIN}/g" docker/Caddyfile

# ---------- Build + boot ----------
say "Building images (this takes a few minutes on first run)"
docker compose -f docker-compose.yml -f docker-compose.prod.yml build --pull

say "Booting the stack"
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# ---------- App key, migrations, storage, admin ----------
say "Ensuring APP_KEY is set on disk"
if ! grep -qE '^APP_KEY=base64:' .env; then
    echo "APP_KEY=${APP_KEY}" >> .env
fi

# Belt-and-suspenders: copy .env directly into every PHP container so Artisan
# commands don't depend on the bind-mount being correctly resolved by compose.
say "Copying .env into containers"
for svc in app queue scheduler reverb; do
    docker compose -f docker-compose.yml -f docker-compose.prod.yml cp .env "${svc}:/var/www/html/.env" 2>/dev/null || true
done

say "Waiting for Postgres"
for i in {1..30}; do
    if docker compose exec -T postgres pg_isready -U voltexa -d voltexahub >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

say "Running migrations"
docker compose exec -T app php artisan migrate --force

say "Linking public storage"
docker compose exec -T app php artisan storage:link || true

say "Bootstrapping admin account (${ADMIN_EMAIL})"
docker compose exec -T app php artisan tinker --execute="
if (! \App\Models\User::where('email', '${ADMIN_EMAIL}')->exists()) {
    \App\Models\User::create([
        'name'              => '${ADMIN_NAME}',
        'handle'            => '${ADMIN_HANDLE}',
        'email'             => '${ADMIN_EMAIL}',
        'password'          => bcrypt('${ADMIN_PASSWORD}'),
        'email_verified_at' => now(),
        'is_admin'          => true,
    ]);
    echo 'Admin created.';
} else {
    echo 'Admin already exists — skipping.';
}
"

say "Caching config / routes / views"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# ---------- Preflight ----------
echo ""
say "Running preflight"
docker compose exec -T app php artisan app:preflight || warn "Preflight reported issues — see above."

# ---------- Summary ----------
echo ""
printf "${c_green}${c_bold}VoltexaHub is up.${c_reset}\n"
echo ""
printf "  Forum:    https://%s\n" "$DOMAIN"
printf "  Admin:    %s\n" "$ADMIN_EMAIL"
if [ -n "${GENERATED_PASSWORD:-}" ]; then
    printf "  Password: %s  ${c_amber}(auto-generated — save it now)${c_reset}\n" "$ADMIN_PASSWORD"
fi
echo ""
note "DNS for ${DOMAIN} must point at this host before the HTTPS cert can issue."
note "Next: add 'scripts/backup.sh' to cron (see docs/DEPLOY.md)."
