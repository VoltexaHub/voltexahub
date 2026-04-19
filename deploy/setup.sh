#!/usr/bin/env bash
# VoltexaHub — Ubuntu 24.04 server setup
# Run as root on a fresh Hetzner VPS:
#   bash <(curl -fsSL https://raw.githubusercontent.com/joogiebear/voltexahub/main/deploy/setup.sh)
# Or after cloning:
#   bash deploy/setup.sh

set -euo pipefail

DOMAIN="voltexahub.com"
DB_NAME="voltexahub"
DB_USER="voltexahub"
APP_DIR="/var/www/voltexahub"
PHP_VER="8.4"

# ── colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}▶ $*${NC}"; }
success() { echo -e "${GREEN}✔ $*${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $*${NC}"; }
die()     { echo -e "${RED}✘ $*${NC}"; exit 1; }

[[ $EUID -ne 0 ]] && die "Run as root"

# ── gather secrets ────────────────────────────────────────────────────────────
echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}       VoltexaHub — Server Setup for ${DOMAIN}${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════${NC}"
echo ""

read -rsp "Postgres password for '${DB_USER}': " DB_PASS; echo
read -rsp "Laravel APP_KEY (leave blank to generate): " APP_KEY_INPUT; echo
read -rsp "Cloudflare Turnstile site key: " TURNSTILE_SITE; echo
read -rsp "Cloudflare Turnstile secret key: " TURNSTILE_SECRET; echo
read -rsp "Mail host (SMTP, e.g. smtp.mailgun.org) [skip=localhost]: " MAIL_HOST; echo
MAIL_HOST="${MAIL_HOST:-localhost}"
if [[ "$MAIL_HOST" != "localhost" ]]; then
    read -rsp "Mail username: " MAIL_USER; echo
    read -rsp "Mail password: " MAIL_PASS; echo
    read -rp  "Mail port [587]: " MAIL_PORT; MAIL_PORT="${MAIL_PORT:-587}"
    read -rp  "Mail from address [noreply@${DOMAIN}]: " MAIL_FROM; MAIL_FROM="${MAIL_FROM:-noreply@${DOMAIN}}"
fi

echo ""
info "Starting setup — this takes about 5 minutes"

# ── system ───────────────────────────────────────────────────────────────────
info "Updating system packages"
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget gnupg2 ca-certificates lsb-release \
    software-properties-common apt-transport-https \
    git unzip zip acl ufw fail2ban

# ── PHP 8.4 ──────────────────────────────────────────────────────────────────
info "Installing PHP ${PHP_VER}"
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VER}-fpm \
    php${PHP_VER}-cli \
    php${PHP_VER}-pgsql \
    php${PHP_VER}-redis \
    php${PHP_VER}-mbstring \
    php${PHP_VER}-xml \
    php${PHP_VER}-curl \
    php${PHP_VER}-zip \
    php${PHP_VER}-bcmath \
    php${PHP_VER}-intl \
    php${PHP_VER}-gd \
    php${PHP_VER}-opcache

# tune opcache for production
cat > /etc/php/${PHP_VER}/fpm/conf.d/99-voltexahub.ini <<'PHP'
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
realpath_cache_size=4096k
realpath_cache_ttl=600
PHP

systemctl enable php${PHP_VER}-fpm
success "PHP ${PHP_VER} installed"

# ── Composer ─────────────────────────────────────────────────────────────────
info "Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null
success "Composer $(composer --version --no-ansi | cut -d' ' -f3)"

# ── Node.js 22 ───────────────────────────────────────────────────────────────
info "Installing Node.js 22"
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - > /dev/null 2>&1
apt-get install -y -qq nodejs
success "Node $(node --version)"

# ── PostgreSQL 16 ────────────────────────────────────────────────────────────
info "Installing PostgreSQL 16"
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg
echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
    > /etc/apt/sources.list.d/pgdg.list
apt-get update -qq
apt-get install -y -qq postgresql-16
systemctl enable postgresql
systemctl start postgresql

info "Creating database and user"
sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';" 2>/dev/null || \
    sudo -u postgres psql -c "ALTER USER ${DB_USER} WITH PASSWORD '${DB_PASS}';"
sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"
success "PostgreSQL 16 ready — database '${DB_NAME}'"

# ── Redis ─────────────────────────────────────────────────────────────────────
info "Installing Redis"
apt-get install -y -qq redis-server
sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf
systemctl enable redis-server
systemctl start redis-server
success "Redis ready"

# ── Caddy ────────────────────────────────────────────────────────────────────
info "Installing Caddy"
curl -fsSL https://dl.cloudsmith.io/public/caddy/stable/gpg.key | gpg --dearmor -o /usr/share/keyrings/caddy.gpg
curl -fsSL https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt \
    | sed 's|signed-by=|signed-by=/usr/share/keyrings/caddy.gpg |' \
    > /etc/apt/sources.list.d/caddy-stable.list
apt-get update -qq
apt-get install -y -qq caddy
success "Caddy $(caddy version)"

# ── App user ─────────────────────────────────────────────────────────────────
info "Creating app user 'voltexahub'"
id voltexahub &>/dev/null || useradd -m -s /bin/bash voltexahub
usermod -aG www-data voltexahub

# ── Clone / pull repo ─────────────────────────────────────────────────────────
info "Deploying application to ${APP_DIR}"
if [[ -d "${APP_DIR}/.git" ]]; then
    info "Repo exists — pulling latest"
    sudo -u voltexahub git -C "${APP_DIR}" pull origin main
else
    sudo -u voltexahub git clone https://github.com/joogiebear/voltexahub.git "${APP_DIR}"
fi

# ── .env ─────────────────────────────────────────────────────────────────────
info "Writing .env"
if [[ -z "${APP_KEY_INPUT}" ]]; then
    APP_KEY_VAL=""
else
    APP_KEY_VAL="${APP_KEY_INPUT}"
fi

cat > "${APP_DIR}/.env" <<ENV
APP_NAME=VoltexaHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USER:-}
MAIL_PASSWORD=${MAIL_PASS:-}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${MAIL_FROM:-noreply@${DOMAIN}}
MAIL_FROM_NAME=VoltexaHub

TURNSTILE_SITE_KEY=${TURNSTILE_SITE}
TURNSTILE_SECRET_KEY=${TURNSTILE_SECRET}

VITE_TURNSTILE_SITE_KEY=${TURNSTILE_SITE}
ENV

chown voltexahub:voltexahub "${APP_DIR}/.env"
chmod 600 "${APP_DIR}/.env"

# ── PHP-FPM pool ──────────────────────────────────────────────────────────────
info "Configuring PHP-FPM pool"
cat > /etc/php/${PHP_VER}/fpm/pool.d/voltexahub.conf <<FPM
[voltexahub]
user = voltexahub
group = www-data
listen = /run/php/voltexahub.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php/${PHP_VER}-fpm-voltexahub.log
php_admin_flag[log_errors] = on
FPM

mkdir -p /var/log/php
systemctl restart php${PHP_VER}-fpm

# ── Caddyfile ─────────────────────────────────────────────────────────────────
info "Writing Caddyfile"
cat > /etc/caddy/Caddyfile <<CADDY
${DOMAIN}, www.${DOMAIN} {
    root * ${APP_DIR}/public
    encode gzip

    php_fastcgi unix//run/php/voltexahub.sock

    file_server

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
    }

    log {
        output file /var/log/caddy/voltexahub.log {
            roll_size 50mb
            roll_keep 5
        }
    }
}
CADDY

systemctl enable caddy

# ── Composer install ──────────────────────────────────────────────────────────
info "Installing PHP dependencies"
sudo -u voltexahub composer install \
    --working-dir="${APP_DIR}" \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --quiet

# ── Generate APP_KEY if needed ────────────────────────────────────────────────
if [[ -z "${APP_KEY_VAL}" ]]; then
    info "Generating APP_KEY"
    sudo -u voltexahub php "${APP_DIR}/artisan" key:generate --force
else
    sudo -u voltexahub sed -i "s|APP_KEY=|APP_KEY=${APP_KEY_VAL}|" "${APP_DIR}/.env"
fi

# ── Frontend build ────────────────────────────────────────────────────────────
info "Building frontend assets"
sudo -u voltexahub bash -c "cd '${APP_DIR}' && npm ci --silent && npm run build"

# ── Storage / permissions ─────────────────────────────────────────────────────
info "Setting permissions"
chown -R voltexahub:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
sudo -u voltexahub php "${APP_DIR}/artisan" storage:link

# ── Database ──────────────────────────────────────────────────────────────────
info "Running migrations"
sudo -u voltexahub php "${APP_DIR}/artisan" migrate --force

info "Seeding initial data"
sudo -u voltexahub php "${APP_DIR}/artisan" db:seed --force

# ── Laravel optimise ─────────────────────────────────────────────────────────
info "Caching config/routes/views"
sudo -u voltexahub php "${APP_DIR}/artisan" config:cache
sudo -u voltexahub php "${APP_DIR}/artisan" route:cache
sudo -u voltexahub php "${APP_DIR}/artisan" view:cache
sudo -u voltexahub php "${APP_DIR}/artisan" event:cache

# ── Queue worker (systemd) ───────────────────────────────────────────────────
info "Installing queue worker service"
cat > /etc/systemd/system/voltexahub-worker.service <<UNIT
[Unit]
Description=VoltexaHub Queue Worker
After=network.target

[Service]
User=voltexahub
Group=www-data
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php ${APP_DIR}/artisan queue:work redis \
    --sleep=3 --tries=3 --max-time=3600 --queue=default,emails
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable voltexahub-worker
systemctl start voltexahub-worker

# ── Firewall ──────────────────────────────────────────────────────────────────
info "Configuring firewall"
ufw --force reset > /dev/null
ufw default deny incoming > /dev/null
ufw default allow outgoing > /dev/null
ufw allow ssh > /dev/null
ufw allow 80/tcp > /dev/null
ufw allow 443/tcp > /dev/null
ufw allow 443/udp > /dev/null   # HTTP/3
ufw --force enable > /dev/null
success "Firewall enabled (SSH + 80 + 443)"

# ── Fail2ban ──────────────────────────────────────────────────────────────────
systemctl enable fail2ban
systemctl start fail2ban

# ── Start Caddy ───────────────────────────────────────────────────────────────
info "Starting Caddy"
systemctl restart caddy

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  VoltexaHub is live at https://${DOMAIN}${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${CYAN}Services:${NC}"
echo -e "    caddy          $(systemctl is-active caddy)"
echo -e "    php${PHP_VER}-fpm    $(systemctl is-active php${PHP_VER}-fpm)"
echo -e "    postgresql     $(systemctl is-active postgresql)"
echo -e "    redis          $(systemctl is-active redis-server)"
echo -e "    queue-worker   $(systemctl is-active voltexahub-worker)"
echo ""
echo -e "  ${CYAN}Logs:${NC}"
echo -e "    Caddy:   journalctl -u caddy -f"
echo -e "    Laravel: tail -f ${APP_DIR}/storage/logs/laravel.log"
echo -e "    Queue:   journalctl -u voltexahub-worker -f"
echo ""
echo -e "  ${YELLOW}Next steps:${NC}"
echo -e "    1. Log in at https://${DOMAIN}"
echo -e "    2. Promote your account to admin via psql:"
echo -e "       sudo -u postgres psql ${DB_NAME}"
echo -e "       UPDATE users SET group_id = (SELECT id FROM groups WHERE name='Administrators') WHERE email='you@example.com';"
echo ""
