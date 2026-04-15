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

# When invoked via `curl ... | sudo bash`, stdin is the pipe, so `read` would
# consume the script (or return nothing). Re-open stdin from /dev/tty if we can.
if [ ! -t 0 ]; then
    exec 0</dev/tty 2>/dev/null || true
fi

INTERACTIVE=0
[ -t 0 ] && INTERACTIVE=1

ask() {
    local prompt="$1" default="${2:-}" var=""
    if [ $INTERACTIVE -eq 1 ]; then
        read -r -p "  ${prompt}${default:+ [$default]}: " var || var=""
    fi
    printf "%s" "${var:-$default}"
}

ask_required() {
    local prompt="$1" value=""
    for _ in 1 2 3; do
        value="$(ask "$prompt" '')"
        [ -n "$value" ] && { printf "%s" "$value"; return; }
        [ $INTERACTIVE -eq 1 ] && printf "${c_red}  (required — try again)${c_reset}\n" >&2
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
    warn "No controlling terminal available — install needs to be interactive."
    warn "Re-run:"
    warn "  curl -fsSL https://raw.githubusercontent.com/VoltexaHub/VoltexaHub/main/scripts/install.sh -o install.sh && sudo bash install.sh"
    die  "Aborting."
fi

DOMAIN="$(ask_required 'Domain (e.g. forum.example.com)')"
ADMIN_NAME="$(ask 'Admin display name' 'Admin')"
ADMIN_HANDLE="$(ask 'Admin @handle' 'admin')"
ADMIN_EMAIL="$(ask_required 'Admin email')"
ADMIN_PASSWORD="$(ask 'Admin password (leave blank to auto-generate)' '')"
if [ -z "$ADMIN_PASSWORD" ]; then
    ADMIN_PASSWORD="$(random_hex 12)"
    GENERATED_PASSWORD=1
fi

SMTP_HOST="$(ask 'SMTP host (blank = log mailer, mail stays local)' '')"
SMTP_USER=""; SMTP_PASS=""; SMTP_PORT=""; SMTP_FROM=""
if [ -n "$SMTP_HOST" ]; then
    SMTP_PORT="$(ask 'SMTP port' '587')"
    SMTP_USER="$(ask 'SMTP username' '')"
    SMTP_PASS="$(ask 'SMTP password' '')"
    SMTP_FROM="$(ask 'Mail From address' "forum@${DOMAIN}")"
fi

# ---------- .env ----------
say "Writing .env"
DB_PASSWORD="$(random_hex 16)"
REVERB_APP_ID="$(random_hex 4)"
REVERB_APP_KEY="$(random_hex 16)"
REVERB_APP_SECRET="$(random_hex 32)"

if [ -f .env ]; then
    note ".env already exists — leaving it alone"
else
    cp .env.example .env
    # Write production-friendly values in-place.
    {
        echo ""
        echo "# Injected by install.sh"
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
say "Generating APP_KEY (if blank)"
if ! grep -qE '^APP_KEY=base64:' .env; then
    docker compose exec -T app php artisan key:generate --force
fi

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
