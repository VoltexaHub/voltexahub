# VoltexaHub - Project Progress

## Overview
Custom gaming community platform for VoltexaMC. Forum + Store + Admin panel.
Two repos: `voltexaforum` (Vue frontend) + `voltexahub` (Laravel backend).

## Stack
- **Frontend:** Vue 3 + Vite + Tailwind CSS + Pinia + Vue Router + Axios
- **Backend:** Laravel 12 + PHP 8.5 + SQLite (local) + Sanctum + Spatie Permission + Cashier

## Local Dev
```bash
# Backend (port 8000)
cd ~/Projects/voltexahub && php artisan serve --host=0.0.0.0 --port=8000

# Frontend (port 5173)
cd ~/Projects/voltexaforum && npm run dev -- --host

# Queue worker (emails + delivery jobs)
cd ~/Projects/voltexahub && php artisan queue:work --sleep=3 --tries=3
```

## Test Account
- Email: victor99@test.com
- Password: Password123!
- Role: admin (full access)

---

## What's Built ✅

### Frontend (voltexaforum)
- Forum index — single/multi-game mode, category > forum tree
- Thread list, thread view, postbit with usergroup badges + awards
- Unified store — real money + credits, featured banner, owned states
- User profile, achievements, credits log
- UserCP — profile, account, notifications, privacy, cosmetics, game accounts, sessions
- Login + Register pages
- Toast notification system
- Dark/light mode toggle, mobile responsive
- Full self-contained admin panel (code-split, plugin-ready):
  - Dashboard (real stats + activity feed)
  - User management (search, ban, credits, awards)
  - Forum tree manager (real CRUD)
  - Moderation (pin/lock/solve/delete)
  - Store items + purchases (real CRUD + manual delivery)
  - Achievements + awards CRUD
  - Forum config (real load/save)
  - Plugins placeholder (future)

### Backend (voltexahub)
- 15 migrations (users, games, categories, forums, subforums, threads, posts,
  reactions, achievements, awards, credits_log, store_items, store_purchases,
  cosmetics, user_sessions)
- All models with relationships + addCredits/spendCredits/checkAchievements
- Seeders: forum config, games, categories, forums, achievements, awards,
  store items, test users
- 32 public/auth API endpoints
- 42 admin API endpoints (protected by auth:sanctum + role:admin)
- Sanctum token auth (stored in localStorage as voltexahub_token)
- Spatie roles: member, vip, elite, moderator, admin
- CORS configured for localhost:5173

### Email System
- Password reset (forgot-password + reset-password endpoints)
- Email verification on registration
- Welcome email sent on register (queued)
- Purchase confirmation email (queued)
- Using log driver locally (check storage/logs/laravel.log)
- Ready for real SMTP (update .env MAIL_* vars)

### Stripe Integration
- PaymentIntent creation for real money purchases (/api/store/checkout)
- Webhook handler at /stripe/webhook (payment_intent.succeeded/failed)
- Pending → completed purchase flow
- Keys: set STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET in .env

### RCON Delivery System
- Custom RCON client (app/Services/RconService.php) — TCP socket, proper protocol
- DeliveryService (app/Services/DeliveryService.php) — replaces {player} with IGN
- DeliverPurchase job (app/Jobs/DeliverPurchase.php) — queued, async
- Wired into: credit purchases, Stripe webhook, admin manual delivery
- RCON config per game in Admin → Config:
  rcon_host_minecraft, rcon_port_minecraft, rcon_password_minecraft
  rcon_host_rust, rcon_port_rust, rcon_password_rust

### Queue System
- QUEUE_CONNECTION=database (jobs stored in DB)
- Run: php artisan queue:work --sleep=3 --tries=3
- Handles: welcome emails, verification emails, purchase confirmation, RCON delivery

---

## What's Next 🔧

### High Priority
1. **Notifications System**
   - In-app bell icon with unread count badge in header
   - Notification dropdown (recent 10, mark all read)
   - Triggered by: reply to thread, @mention, award received, achievement unlocked,
     credits earned, purchase confirmed/failed, DM received (future)
   - Real-time via Soketi + Laravel Echo (WebSocket)
   - Email fallback per notification type (UserCP settings already built)
   - Tables needed: notifications (Laravel built-in) already exists

2. **Private Messages**
   - Conversations + messages tables
   - Inbox, compose, thread view
   - Unread count in header
   - Notification on new DM

3. **Real-time (Soketi)**
   - Install Soketi: npm install -g @soketi/soketi
   - Laravel Echo on frontend
   - Broadcast: new posts, notifications, online status

### Medium Priority
4. **Thread/post end-to-end test** — verify create thread + reply + credits award all work
5. **@mention system** — parse @username in posts, trigger notification
6. **Thread subscriptions** — follow a thread, get notified of replies
7. **Search** — forum-wide search across threads/posts
8. **Media uploads** — avatars, profile banners (Spatie Medialibrary ready)

### Lower Priority
9. **Production setup** — MySQL, proper .env, Nginx, queue worker as service, cron
10. **Plugin system** — admin panel hook registration API
11. **Multi-game mode** — toggle in config fully wired to frontend
12. **Moderation tools** — report post button on frontend

### Known Issues / Tech Debt
- checkAchievements() progress column: migration may need --force on fresh run
- Admin panel has some remaining mock data fallbacks (purchases, moderation reports)
- Stripe keys are placeholders — need real keys to test checkout
- RCON delivery untested end-to-end (needs a running game server)
- Email verification flow not wired on frontend (redirect from email link)
- Password reset form not built in Vue frontend yet

---

## Architecture Notes
- Frontend auth: Sanctum token in localStorage as `voltexahub_token`
- API response format: `{ data: ..., message: ..., meta: ... }`
- Admin routes: `/api/admin/*` protected by `auth:sanctum` + `role:admin`
- Forum config: key/value pairs in `forum_config` table
- Credits: all changes logged in `credits_log` with balance_after
- Emails: queued via database queue, log driver locally
- RCON: per-game config stored in forum_config table
- Delivery: async job dispatched on any purchase completion

## File Structure
```
voltexaforum/src/
  services/api.js              — all API calls (public + admin)
  stores/auth.js               — Pinia auth store
  stores/forum.js              — Pinia forum config cache
  stores/toast.js              — toast notifications
  layouts/AdminLayout.vue      — admin shell
  views/admin/                 — all admin pages (wired to real API)
  components/
    AppToggle.vue
    AppToast.vue
    UserAvatar.vue
    AppHeader.vue

voltexahub/
  app/Http/Controllers/Api/        — public + auth controllers
  app/Http/Controllers/Api/Admin/  — 8 admin controllers
  app/Models/                      — Eloquent models
  app/Services/
    RconService.php                — TCP RCON client
    DeliveryService.php            — purchase delivery logic
  app/Jobs/
    DeliverPurchase.php            — queued delivery job
  app/Mail/
    WelcomeEmail.php
    PurchaseConfirmation.php
  database/migrations/             — 15+ migrations
  database/seeders/                — all seeders
  routes/api.php                   — all routes
  resources/views/emails/          — email blade templates
```

---

## Session 2026-03-02 — Major Build Day

### Features Added
- **Notifications system** — 6 notification types, DB-backed, API endpoints, wired into posts/awards/achievements/purchases
- **Private Messages** — conversations, messages, unread counts, full API
- **Soketi real-time** — Laravel Echo + Pusher broadcasting, NewNotification + NewMessage events, presence channel
- **Forum FA icon picker** — searchable grid of 120+ icons in admin forum editor
- **Award custom icons** — file upload, stored in public/storage/awards/, icon_url accessor
- **Admin forum editor** — drag-to-reorder (vuedraggable), full edit modal, active/inactive toggles
- **Default content seeder** — always seeds 1 game → 1 category → 1 forum on fresh install (firstOrCreate, safe to re-run)
- **Reorder API** — POST /admin/games/reorder, /categories/reorder, /forums/reorder

### Frontend
- Notifications bell + dropdown + full page (/notifications)
- Messages inbox + compose modal + conversation view (/messages)
- Laravel Echo wired in main.js, subscribes to private user channel on login
- All UI emoji replaced with Font Awesome icons throughout
- Dark/light toggle uses FA fa-moon / fa-sun
- Admin sidebar shows real auth user (not hardcoded)
- Admin sidebar has Back to Site link

### Hardcoding Removed
- Forum name, accent color, site name all driven by forum_config table
- Login/Register/AdminLayout/AppHeader all use forumStore.config.forum_name
- Accent color dynamically applied via CSS variable from config
- Page titles use forum name from config
- Email subjects + templates use ForumConfig::get('forum_name')
- Default seeders use generic 'My Forum' not branded names

### Bugs Fixed
- Public forum endpoint now returns full game tree (games → categories → forums) matching admin structure
- Forum edit: empty slug auto-generates from name, nullable validation added
- Config save: payload key was wrong (forum → config)
- Config booleans (multi_game): now cast to string before storing
- AdminUsers mock data fallback removed
- Forum icons in DB updated from emoji to FA classes

### Scripts
- scripts/soketi-start.sh — start Soketi with correct app config

### Known Issues / Tech Debt (updated)
- Password reset form not built in Vue frontend yet
- Email verification flow not wired on frontend
- Soketi needs to be running separately for real-time to work
- RCON delivery untested end-to-end
