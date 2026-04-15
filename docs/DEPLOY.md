# Deploying VoltexaHub

This guide covers a realistic single-host production deploy using Docker Compose. It's battle-tested for small-to-mid forums; scale out when you need to.

**At a glance.** Five services, one `docker-compose.yml`, TLS from Caddy, Postgres + Redis + Reverb on the same host, persistent volumes for DB and uploads.

---

## 1. Prerequisites

- Linux host (Ubuntu 22.04+ or Debian 12 recommended)
- Docker Engine 26+ and the Compose plugin
- A domain pointing to the host (`forum.example.com`)
- SMTP credentials for outgoing mail (Postmark, Mailgun, Amazon SES, or your mail server)

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER  # re-login after
```

---

## 2. Clone and configure

```bash
git clone https://github.com/VoltexaHub/VoltexaHub.git /opt/voltexahub
cd /opt/voltexahub
cp .env.example .env
```

Edit `.env`:

```env
APP_NAME=VoltexaHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://forum.example.com
APP_KEY=                      # fill in via `php artisan key:generate` (step 4)

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=voltexahub
DB_USERNAME=voltexa
DB_PASSWORD=change-me-strong

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=change-me
REVERB_APP_SECRET=change-me
REVERB_HOST=forum.example.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8081
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your-token
MAIL_PASSWORD=your-token
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="forum@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VOLTEXAHUB_THEME=default
```

Regenerate secrets:

```bash
openssl rand -hex 16   # paste into REVERB_APP_SECRET
```

---

## 3. Production Docker Compose overlay (committed — reference only)

The repository ships `docker-compose.prod.yml` and `docker/Caddyfile` ready to use. Edit the Caddyfile and replace `forum.example.com` with your real domain. Caddy fetches a Let's Encrypt certificate for it automatically on first request.

<details>
<summary>Reference: contents of <code>docker-compose.prod.yml</code></summary>

```yaml
# docker-compose.prod.yml
services:
  app:
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false

  nginx:
    restart: unless-stopped
    ports: !reset []             # remove the public 8080 binding
    expose:
      - "80"

  postgres:
    restart: unless-stopped
    ports: !reset []

  redis:
    restart: unless-stopped
    ports: !reset []

  reverb:
    restart: unless-stopped

  node:
    profiles: ["disabled"]       # no Vite dev server in prod

  queue:
    build:
      context: .
      args: { UID: 1000, GID: 1000 }
    container_name: voltexa-queue
    command: php artisan queue:work --tries=3 --timeout=90
    volumes: [ "./:/var/www/html" ]
    depends_on: [ postgres, redis ]
    restart: unless-stopped
    networks: [ voltexa ]

  caddy:
    image: caddy:2-alpine
    container_name: voltexa-caddy
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
      - "443:443/udp"
    volumes:
      - ./docker/Caddyfile:/etc/caddy/Caddyfile
      - caddy_data:/data
      - caddy_config:/config
    depends_on: [ nginx, reverb ]
    networks: [ voltexa ]

volumes:
  caddy_data:
  caddy_config:
```

</details>

---

## 4. First boot

```bash
# Build once
docker compose -f docker-compose.yml -f docker-compose.prod.yml build

# Start the stack
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Generate the app key
docker compose exec app php artisan key:generate --force

# Pre-flight the deployment
docker compose exec app php artisan app:preflight

# Install deps, build assets, migrate
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec -T node:20-alpine sh -c "cd /var/www/html && npm ci && npm run build"  # or run on host
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Bootstrap an admin account:

```bash
docker compose exec app php artisan tinker --execute=\
  "\App\Models\User::create([
      'name' => 'Admin',
      'email' => 'admin@example.com',
      'password' => bcrypt('change-me'),
      'email_verified_at' => now(),
      'is_admin' => true,
  ]);"
```

---

## 5. Ongoing operations

### Deploy an update

```bash
cd /opt/voltexahub
git pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache route:cache view:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Asset build

The multi-stage `Dockerfile` builds assets inside the `asset-build` stage and copies the result into the `prod` image, so `docker compose ... build` is all you need. No manual `npm run build` on the host.

If you want to force an assets-only rebuild without restarting services, `docker compose build app` is enough — only the `asset-build` stage reruns when `package.json` / `package-lock.json` / `resources/**` have changed.

### Logs

```bash
docker compose logs -f --tail=100 app
docker compose logs -f --tail=100 queue
docker compose logs -f --tail=100 reverb
```

### Backups

`scripts/backup.sh` dumps Postgres and tars user uploads, pruning older files so the backup directory doesn't grow unbounded:

```bash
# One-off
./scripts/backup.sh

# Nightly at 03:00, keeping 30 days
crontab -e
0 3 * * *  cd /opt/voltexahub && BACKUP_KEEP=30 ./scripts/backup.sh >> /var/log/vx-backup.log 2>&1
```

Tweak via env:
- `BACKUP_DIR` (default `/var/backups/voltexahub`)
- `BACKUP_KEEP` (default 14)
- `DB_USERNAME`, `DB_DATABASE` (default to `voltexa` / `voltexahub`)

The archive is plain `tar.gz` + `.sql.gz`, so restoring is just `gunzip | psql` and `tar xzf` into `storage/app/public/`.

---

## 6. Security checklist before going public

- `APP_DEBUG=false` and `APP_ENV=production`
- Rotate `APP_KEY` before first real users sign up (you can't rotate it later without breaking encrypted settings and OAuth secrets)
- Firewall (`ufw allow 80,443/tcp`); everything else on the Docker network
- Strong `DB_PASSWORD`, `REVERB_APP_SECRET`
- Enable automatic OS updates on the host
- Set up Caddy's built-in Let's Encrypt (handled automatically by the image on first request)
- Consider a WAF / bot-filter in front (Cloudflare Tunnel is a zero-cost option)
- Configure SMTP DKIM + SPF so notification mail doesn't land in spam
- Wire up error tracking — set `SENTRY_LARAVEL_DSN` (Sentry.io or self-hosted GlitchTip). `php artisan app:preflight` warns if it's missing.

---

## 7. Scaling when you outgrow a single host

- Move Postgres to a managed provider (AWS RDS, Neon, Supabase)
- Run multiple `app` containers behind Caddy and pin sessions via Redis
- Split Reverb onto its own box — it's pure WebSocket and CPU-bound
- Put storage on S3 (or compatible) and set `FILESYSTEM_DISK=s3`
- Hand asset serving off to a CDN pointed at `/build/*`
