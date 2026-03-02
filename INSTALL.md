# VoltexaHub — Installation Guide

VoltexaHub is a self-hosted gaming community forum platform built on Laravel 12 + Vue 3.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| OS | Ubuntu 20.04 / 22.04 / 24.04, Debian 11/12 |
| PHP | 8.2+ |
| Node.js | 18+ |
| Composer | 2.x |
| Database | MySQL 8+ or SQLite |
| Web Server | Nginx (recommended) |

> **Note:** The installer will automatically install all dependencies if they are missing.

---

## Quick Install (Recommended)

The fastest way to get up and running on a fresh VPS:

```bash
git clone https://github.com/youruser/voltexahub.git /var/www/voltexahub
cd /var/www/voltexahub
sudo bash install.sh
```

The installer will walk you through:
- Domain name + SSL setup (Let's Encrypt)
- Forum name
- Admin account creation
- Database selection (MySQL or SQLite)

That's it. Everything else is handled automatically.

---

## What the Installer Does

1. **Collects config** — prompts for all settings upfront, then runs unattended
2. **Installs system packages** — PHP 8.2, Composer, Node.js 20, MySQL (if chosen), Nginx, Certbot, Soketi
3. **Creates database** — MySQL DB + user with proper permissions (or SQLite file)
4. **Configures `.env`** — APP_KEY, database, URL, broadcasting
5. **Runs migrations + seeders** — fresh schema with default forum structure
6. **Creates admin account** — with the credentials you provide
7. **Configures Nginx** — SPA frontend, API proxy, WebSocket proxy, storage
8. **Issues SSL certificate** — via Let's Encrypt (optional, requires a domain)
9. **Creates systemd services** — auto-start on boot:
   - `voltexahub-app` — Laravel API server
   - `voltexahub-queue` — Email + notification worker
   - `voltexahub-soketi` — Real-time WebSocket server

---

## Manual Installation

If you prefer to set things up yourself:

### 1. Clone the repos

```bash
git clone https://github.com/youruser/voltexahub.git
git clone https://github.com/youruser/voltexaforum.git
```

### 2. Backend setup

```bash
cd voltexahub
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

Edit `.env` with your database credentials, then:

```bash
php artisan migrate --force
php artisan db:seed --class=DefaultContentSeeder
php artisan db:seed --class=RoleSeeder
php artisan storage:link
```

### 3. Run the interactive installer

```bash
php artisan voltexahub:install
```

This will prompt for your forum name, admin account, and save config to the database.

### 4. Frontend setup

```bash
cd ../voltexaforum
npm install
echo "VITE_API_URL=https://yourdomain.com" > .env
npm run build
```

### 5. Configure Nginx

Point your Nginx server block to:
- Frontend: `voltexaforum/dist/` (static SPA)
- API: proxy `/api/*` → `http://127.0.0.1:8000`
- WebSocket: proxy `/app/*` → `http://127.0.0.1:6001`

See the generated config in `/etc/nginx/sites-available/voltexahub` after running the installer for a complete example.

### 6. Start services

```bash
# Start Soketi (WebSocket server)
soketi start --config=soketi.json

# Start queue worker
php artisan queue:work --sleep=3 --tries=3

# Start Laravel (or use php-fpm + Nginx instead)
php artisan serve --host=127.0.0.1 --port=8000
```

---

## Hosting the Installer

To let users install with a single command (`curl | bash`), host the install script on your server or CDN.

### Option A: GitHub Releases (Recommended)

1. Tag a release in your repo: `git tag v1.0.0 && git push --tags`
2. Users can then run:

```bash
curl -fsSL https://raw.githubusercontent.com/youruser/voltexahub/main/install.sh | sudo bash
```

Or for a specific version:
```bash
curl -fsSL https://github.com/youruser/voltexahub/releases/download/v1.0.0/install.sh | sudo bash
```

### Option B: Your own domain

Serve the script from `https://get.yourdomain.com`:

**Nginx config for the installer host:**
```nginx
server {
    listen 80;
    server_name get.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name get.yourdomain.com;

    # SSL certs (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/get.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/get.yourdomain.com/privkey.pem;

    root /var/www/installer;
    index install.sh;

    location / {
        default_type text/plain;
        try_files $uri $uri/ =404;
    }
}
```

Place `install.sh` at `/var/www/installer/install.sh`, then users run:
```bash
curl -fsSL https://get.yourdomain.com/install.sh | sudo bash
```

### Option C: Cloudflare Pages / Vercel (Static hosting)

Commit `install.sh` to a separate repo and deploy to Cloudflare Pages or any static host. The file will be served at a stable URL.

---

## Post-Install Configuration

After installation, log in to the admin panel at `/admin` and configure:

| Setting | Location |
|---------|----------|
| Forum name, accent color | Admin → Config → Forum Settings |
| Credits per post/thread | Admin → Config → Credits |
| Stripe keys (store) | Admin → Config → Store |
| Forum structure | Admin → Forums |
| Achievement definitions | Admin → Achievements |
| Award icons | Admin → Awards |

---

## Updating

```bash
cd /var/www/voltexahub
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear

# Rebuild frontend
cd /var/www/voltexaforum
git pull
npm install
npm run build

# Restart services
sudo systemctl restart voltexahub-app voltexahub-queue
```

---

## Managing Services

```bash
# Status
sudo systemctl status voltexahub-app
sudo systemctl status voltexahub-queue
sudo systemctl status voltexahub-soketi

# Restart
sudo systemctl restart voltexahub-app

# View logs
sudo journalctl -u voltexahub-app -f
sudo journalctl -u voltexahub-queue -f
sudo journalctl -u voltexahub-soketi -f
```

---

## Troubleshooting

**Forum shows "Failed to load forums"**
- Check the Laravel API is running: `sudo systemctl status voltexahub-app`
- Check CORS: ensure `APP_URL` in `.env` matches your actual domain

**Emails not sending**
- Make sure the queue worker is running: `sudo systemctl status voltexahub-queue`
- Check mail settings in `.env` (MAIL_HOST, MAIL_USERNAME, etc.)

**Real-time notifications not working**
- Check Soketi is running: `sudo systemctl status voltexahub-soketi`
- Verify `PUSHER_HOST` in `.env` matches your server IP

**Permission errors**
```bash
sudo chown -R www-data:www-data /var/www/voltexahub
sudo chmod -R 775 /var/www/voltexahub/storage /var/www/voltexahub/bootstrap/cache
```

**500 errors after update**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
sudo systemctl restart voltexahub-app
```

---

## Architecture

```
                    User Browser
                         │
                    [ Nginx ]
                   /         \
        Vue SPA (dist/)    /api/* proxy
                               │
                        [ Laravel API ]
                        port 8000
                          /    \
                    MySQL/     Storage
                    SQLite     (avatars, awards)
                         \
                    [ Queue Worker ]
                    (emails, notifications)

        [ Soketi ] ←──── Laravel Broadcasting
        port 6001
             │
        WebSocket (/app/*)
```

---

## Default Credentials (Dev Only)

> ⚠️ Change these immediately after install.

| Field | Value |
|-------|-------|
| Email | Set during install |
| Password | Set during install |

The installer always prompts for your own credentials — no default passwords are ever set.
