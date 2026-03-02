# VoltexaHub

A self-hosted gaming community forum platform. Built with Laravel 12 + Vue 3.

## Features

- **Forums** — Games → Categories → Forums → Threads → Posts
- **Store** — Credits system + Stripe payments + RCON delivery
- **Achievements** — Configurable achievement system with progress tracking
- **Awards** — Custom badge uploads, manually assigned by admins
- **Notifications** — In-app bell, real-time via WebSocket
- **Private Messages** — DMs with real-time delivery
- **Admin Panel** — Full management: forums, users, store, achievements, awards, config
- **Real-time** — Powered by Soketi (self-hosted Pusher-compatible)
- **White-label** — Forum name, accent color, all config via admin panel
- **Installer** — One-command VPS setup with Nginx, SSL, MySQL, systemd

## Quick Start (Production)

```bash
git clone https://github.com/youruser/voltexahub.git
cd voltexahub
sudo bash install.sh
```

See [INSTALL.md](INSTALL.md) for full documentation.

## Development Setup

### Backend

```bash
cd voltexahub
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd voltexaforum
npm install
npm run dev
```

### Queue worker (for emails/notifications)

```bash
cd voltexahub
php artisan queue:work
```

### Real-time (optional for dev)

```bash
# Install Soketi once
npm install -g @soketi/soketi

# Start it
bash scripts/soketi-start.sh
```

Default dev URLs:
- Frontend: http://localhost:5173
- API: http://localhost:8000

## Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 12, PHP 8.2+, Sanctum auth, Spatie Permission |
| Frontend | Vue 3, Vite, Tailwind CSS, Pinia |
| Database | MySQL (production) / SQLite (development) |
| Real-time | Soketi (self-hosted) + Laravel Echo |
| Payments | Stripe PaymentIntents |
| Email | Laravel Mail (SMTP/Mailgun/SES) |
| Icons | Font Awesome 6 Free |

## License

MIT
