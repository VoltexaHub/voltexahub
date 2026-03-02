# VoltexaHub - Project Progress

## Overview
Custom gaming community platform for VoltexaMC. Forum + Store + Admin panel.
Two repos: `voltexaforum` (Vue frontend) + `voltexahub` (Laravel backend).

## Stack
- **Frontend:** Vue 3 + Vite + Tailwind CSS + Pinia + Vue Router + Axios + marked + vuedraggable
- **Backend:** Laravel 12 + PHP 8.5 + SQLite (local) / MySQL (prod) + Sanctum + Spatie Permission + Stripe

## Local Dev
```bash
# Backend (port 8000)
cd ~/Projects/voltexahub && php artisan serve --host=0.0.0.0 --port=8000

# Frontend (port 5173)
cd ~/Projects/voltexaforum && npm run dev -- --host

# Queue worker (emails + delivery jobs)
cd ~/Projects/voltexahub && php artisan queue:work --sleep=3 --tries=3

# Soketi (real-time — optional for dev)
bash ~/Projects/voltexaforum/scripts/soketi-start.sh
```

## Test Account
- Email: victor99@test.com
- Password: Password123!
- Role: admin (full access), User ID: 7

---

## What's Built ✅

### Auth & Users
- Sanctum token auth (stored in localStorage as `voltexahub_token`)
- Register, Login, Logout
- Password reset (frontend + backend: /forgot-password, /reset-password)
- Avatar upload — POST /api/user/avatar, stored in public/storage/avatars/
- Avatar Vue reactivity fixed via `setAvatarUrl()` store action (spread pattern)
- Online tracking — `last_seen` column, `UpdateLastSeen` middleware (throttled 2min)
- GET /api/users/online — list of recently active users

### Forum — Public
- GET /api/forums — full game tree (games → categories → forums), threads_count, last_post info
- Thread list (GET /api/forums/{slug}/threads)
- Thread view (GET /api/threads/{id}) with posts
- Create thread (POST /api/threads) — accepts forum_slug OR forum_id
- Create post/reply (POST /api/posts)
- Edit post (PUT /api/posts/{id}) — author or mod, tracks edited_at + edit_count
- Edit thread (PUT /api/threads/{id}) — author or admin (title + first post body)
- Reactions (POST /api/posts/{id}/react) — awards credits to post author (not self)
- @mention parsing — regex finds @username, sends MentionNotification + broadcasts

### Search
- GET /api/search?q=&type=all|threads|posts|users&page= — grouped paginated results
- SearchController, SearchView.vue (tabbed, URL sync, term highlighting, skeletons)
- Search bar in AppHeader (desktop center, Enter to search)

### Notifications
- 6 notification types: ThreadReply, Mention, AwardReceived, AchievementUnlocked, PurchaseConfirmed, DMReceived
- API: GET /api/notifications, PUT /api/notifications/{id}/read, PUT /api/notifications/read-all, DELETE /api/notifications/{id}
- Bell icon in header with unread badge
- Dropdown (recent 10, mark read on click)
- Full notifications page (/notifications) with FA mark-read button
- Wired into PostController, AdminUserController, StoreController, Stripe webhook

### Private Messages
- Conversations + messages + conversation_user tables
- ConversationController: list/create/fetch/send/unread-count
- MessagesView.vue — split-panel inbox + conversation
- ComposeModal.vue — new conversation
- Envelope icon with unread badge in header
- Routes: /messages, /messages/:id

### Real-time (Soketi)
- `NewNotification` + `NewMessage` broadcast events
- config/broadcasting.php with Pusher/Soketi env vars
- routes/channels.php — private user channels + online presence channel
- Laravel Echo wired in main.js, subscribes on login
- Presence store (stores/presence.js) — online users widget on HomeView
- Soketi config: app ID = voltexahub, key = voltexahub-key, secret = voltexahub-secret, port 6001

### Markdown
- MarkdownEditor.vue — toolbar (Bold/Italic/Code/CodeBlock/Link/Quote/UL/OL/HR), preview toggle, uses `marked`
- MarkdownRenderer.vue — XSS-safe styled prose output
- Reply box + NewThreadView use MarkdownEditor

### Moderation
- Thread actions (visible to mods): pin/unpin, lock/unlock, delete, move (forum picker)
- Per-post mod actions: delete post, delete whole thread if first post
- Backend: PUT /admin/threads/{id}/move, DELETE /admin/threads/{id}
- Forum last_post_at + last_post_user_id recalculated after deletion

### Credits System
- Credits log (credits_log table), GET /api/user/credits → `{ balance, log: [] }`
- Credits awarded on: thread create, post reply, reaction received (not self), achievement unlock, purchase
- Role-based multipliers stored as JSON in forum_config (key: role_credit_multipliers)
- Config-driven amounts: credits_per_thread, credits_per_reply, credits_for_solved, credits_per_like, credits_per_like_given, credits_daily_post_limit
- GET /api/credits/earning-info — public endpoint, returns ways_to_earn + role_multipliers
- CreditsView.vue — balance, log with pagination, "How to Earn" guide cards, role bonus table

### Store
- Real money: PaymentIntent + Stripe webhook (payment_intent.succeeded/failed)
- Credits purchases: direct balance deduct
- RCON delivery: DeliveryService + DeliverPurchase queued job
- Purchase confirmation email (queued)

### Email System
- WelcomeEmail, PurchaseConfirmation — use ForumConfig::get('forum_name') (white-label)
- Welcome on register, purchase confirmation on payment
- Log driver locally, real SMTP via .env MAIL_* vars

### Admin Panel (42+ endpoints)
- Dashboard — real stats + activity
- User management — search, ban, credits adjust, award give
- Forum tree — drag-to-reorder (vuedraggable), full edit (name/slug/icon/description/active), create/delete games/categories/forums
- FA icon picker (FaIconPicker.vue) — 120+ curated icons, searchable
- Award management — custom image upload, stored in public/storage/awards/
- Store items + purchases
- Achievements CRUD
- Forum config — load/save all settings
- Reorder API: POST /admin/games/reorder, /categories/reorder, /forums/reorder

### Config System
- All values stored as strings in forum_config table (key/value)
- ForumConfig::get(key, default) + ForumConfig::set(key, value)
- Frontend: forum.js store, `isMultiGame` + `isMaintenanceMode` getters coerce "true"/"false" strings
- AdminConfig.vue coerces booleans on load (`=== 'true'`)
- Accent color applied dynamically via CSS variable (--color-purple-accent) in App.vue
- Forum name used in AppHeader, page titles, emails, register/login
- Maintenance mode: router guard redirects non-admins to /maintenance (MaintenanceView.vue)
- Multi-game mode: shows/hides game selector on HomeView

### White-Label
- Zero hardcoded brand names in code
- Forum name, accent color, site URLs all from forum_config
- Emails use ForumConfig::get('forum_name')
- Default seeds use 'My Forum' not 'VoltexaMC'

### Installer
- `php artisan voltexahub:install` — interactive command (forum name, URL, admin account, DB type, migrations, seeding, role assignment)
- Supports flags: --forum-name=, --admin-email=, etc. for non-interactive
- `install.sh` — 700-line full VPS installer: OS check, PHP/Composer/Node/MySQL/Nginx/Certbot/Soketi, DB creation, .env, migrations, seeders, admin via tinker, Nginx config, Let's Encrypt SSL, systemd services (voltexahub-app, voltexahub-queue, voltexahub-soketi)
- README.md + INSTALL.md created

### Bug Fixes (session 2026-03-02)
- Config booleans stuck ON: DB stores `"true"`/`"false"` strings; fixed coercion everywhere
- Credits page infinite loading: API returns `{balance, log:[]}`, code was assigning whole object to `creditsLog` (array), `.filter()` on object silently fails
- API 500 "Route [login] not defined": Sanctum tried to redirect unauthenticated API requests to named `login` route; fixed in bootstrap/app.php to return 401 JSON
- ConversationController::show() type error: `int $id` rejected string route params → `int|string $id`
- APP_URL missing :8000 → avatar URLs wrong
- Forum last_post stale after thread deletion
- Thread create: forum_slug not accepted by backend
- Config save payload key wrong
- Forum icon: emoji → FA classes in DB

---

## What's Next 🔧

### High Priority
1. **Thread subscriptions** — follow a thread, get notified on new replies
   - `thread_subscriptions` table (user_id, thread_id)
   - Subscribe/unsubscribe button in ThreadView
   - Trigger ThreadReply notification for subscribers

2. **Report system** — report button on posts → admin moderation queue
   - `reports` table (reporter_id, post_id, reason, status)
   - Report button in post actions
   - Admin moderation queue page

3. **Public profile pages** — polish
   - Post history tab
   - Awards/achievements display
   - Join date, post count, credits balance visible

4. **Frontend password reset form** — /reset-password page needs to accept token + submit new password (backend already done)

### Medium Priority
5. **Image embeds in markdown** — storage symlink exists, needs upload endpoint + paste/drag handler in MarkdownEditor
6. **Thread solved/best answer** — mark a reply as solution (credits_for_solved), closed indicator
7. **Test installer end-to-end** — run `php artisan voltexahub:install` on a clean SQLite DB
8. **Email verification flow** — frontend redirect from email link → verify → redirect to forum

### Lower Priority
9. **Soketi as background service** — currently manually started; add to installer systemd
10. **Plugin system** — admin panel hook registration API (placeholder tab exists)
11. **Leaderboard** — top credits earners, top posters
12. **Tag system** — thread tags/flair, filter by tag

### Known Issues / Tech Debt
- Email verification flow not wired on frontend (backend done)
- RCON delivery untested end-to-end (needs a running game server)
- Soketi must be started separately for real-time features
- Stripe keys are placeholders in dev — need real keys to test checkout
- `php artisan serve` used in systemd services; INSTALL.md notes this is a dev simplification

---

## Architecture Notes
- Frontend auth: Sanctum token in localStorage as `voltexahub_token`
- API response format: `{ data: ..., message: ..., meta: ... }`
- Admin routes: /api/admin/* protected by auth:sanctum + role:admin
- Forum config: key/value strings in forum_config table — always coerce booleans client-side
- Credits: all changes logged in credits_log with balance_after
- Forum model: has `threads()` hasMany + `subforums()` hasMany — NO direct posts() relationship
- Public forum tree: games → categories → forums (same structure as admin)
- Emails: queued via database queue, log driver locally
- Avatar storage: symlink public/storage → storage/app/public; APP_URL must include port (:8000)

## Key Files
```
voltexaforum/src/
  services/api.js              — all API calls
  stores/auth.js               — auth (token, user, setAvatarUrl)
  stores/forum.js              — config cache (isMultiGame, isMaintenanceMode)
  stores/notifications.js      — notification state
  stores/messages.js           — DM state
  stores/presence.js           — online users (Soketi)
  echo.js                      — Laravel Echo setup
  router/index.js              — routes + guards (auth, admin, maintenance)
  views/admin/AdminConfig.vue  — all forum settings (booleans coerced on load)
  views/CreditsView.vue        — credits log + earning guide
  views/MaintenanceView.vue    — maintenance mode page
  components/MarkdownEditor.vue
  components/MarkdownRenderer.vue
  components/FaIconPicker.vue
  components/NotificationDropdown.vue
  components/ComposeModal.vue

voltexahub/
  app/Http/Controllers/Api/    — public + auth controllers
  app/Http/Controllers/Api/Admin/ — admin controllers
  app/Models/ForumConfig.php   — get/set static helpers
  app/Services/RconService.php
  app/Services/DeliveryService.php
  app/Jobs/DeliverPurchase.php
  database/seeders/ForumConfigSeeder.php — all config defaults
  database/seeders/DefaultContentSeeder.php — idempotent firstOrCreate
  routes/api.php
  bootstrap/app.php            — exception handler (API → 401 JSON)
```
