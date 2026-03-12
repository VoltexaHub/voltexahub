# VoltexaHub

A self-hosted, open-source community forum platform. Built with Laravel 12 + Vue 3.

## Features

- **Forums** — Categories → Forums → Threads → Posts with full moderation tools
- **Plugin System** — Extend with backend hooks, frontend slots, custom routes, and DB migrations
- **Store** — Credits economy + Stripe, PayPal, and Plisio payment gateways
- **Upgrade Plans** — Paid membership tiers with prerequisite checks
- **Achievements & Awards** — Configurable achievement system and custom badge uploads
- **XP & Levels** — 20-tier level system with XP boosts
- **Notifications** — Real-time in-app notifications via WebSocket
- **Private Messages** — DMs with real-time delivery
- **MFA** — TOTP and email OTP with recovery codes
- **SEO** — Sitemap, robots.txt, per-page meta tags, per-forum noindex
- **Admin Panel** — Full management: forums, users, store, plugins, SEO, security
- **Security** — Rate limiting, audit log, active sessions, account lockout, CSP headers, admin re-auth
- **Real-time** — Powered by Laravel Reverb (WebSockets)
- **Themes** — CSS + layout config flags with custom CSS/JS injection
- **Installer** — One-command VPS setup with Nginx, SSL, MySQL, Docker

## Quick Start (Production)

```bash
git clone https://github.com/VoltexaHub/voltexahub.git
cd voltexahub
sudo bash install.sh
```

See [INSTALL.md](INSTALL.md) for full documentation.

## Development Setup

### Backend

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd ../voltexaforum
npm install
npm run dev
```

### Queue worker (for emails/notifications)

```bash
php artisan queue:work
```

Default dev URLs:
- Frontend: http://localhost:5173
- API: http://localhost:8000

## Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 12, PHP 8.2+, Sanctum, Spatie Permission |
| Frontend | Vue 3, Vite, Tailwind CSS v4, Pinia |
| Database | MySQL (production) / SQLite (development) |
| Real-time | Laravel Reverb + Laravel Echo |
| Payments | Stripe, PayPal, Plisio |
| Email | Laravel Mail (SMTP) |
| Icons | Font Awesome 6 Free |

## Links

- **Docs:** https://docs.voltexahub.com
- **API Reference:** https://api.voltexahub.com
- **Community:** https://community.voltexahub.com
- **Website:** https://voltexahub.com

## License

MIT
