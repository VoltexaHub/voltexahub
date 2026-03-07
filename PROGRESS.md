# VoltexaHub - Project Progress

## Overview
Custom forum platform. White-label, self-hostable. Live at https://community.voltexahub.com.
Two repos: `voltexaforum` (Vue frontend) + `voltexahub` (Laravel backend).

## Stack
- **Frontend:** Vue 3 + Vite + Tailwind CSS v4 + Pinia + Vue Router + Axios + marked + vuedraggable
- **Backend:** Laravel 12 + PHP 8.4 + SQLite (local) / MySQL (prod) + Sanctum + Spatie Permission + Stripe

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

## Production
- **URL:** https://community.voltexahub.com
- **VPS:** 187.124.80.32 (Ubuntu 24.04, PHP 8.4, MySQL, Nginx, Soketi)
- **Deploy backend:** `cd /var/www/voltexahub && git fetch origin main && git reset --hard origin/main && cp /tmp/voltexahub_env_backup .env && php artisan migrate --force && php artisan config:clear && php artisan cache:clear && systemctl restart voltexahub-app`
- **Deploy frontend:** `cd /var/www/voltexaforum && git fetch origin main && git reset --hard origin/main && npm run build && systemctl reload nginx`

## Test Account (prod)
- Username: voltexa / Email: joogiebear@protonmail.com
- Role: admin (User ID: 1)

---

## What's Built ✅

### Infrastructure & Deployment
- VPS fully configured: PHP 8.4, MySQL, Nginx, SSL (Let's Encrypt), Soketi
- `install.sh` — 700-line full VPS installer
- `php artisan voltexahub:install` — interactive install command
- community.voltexahub.com — dedicated SSL cert, separate from root domain
- voltexahub.com — coming soon page (~/Projects/voltexahub-marketing/index.html)
- systemd services: voltexahub-app, voltexahub-queue, voltexahub-soketi
- Forum config cached in localStorage (no flash on page refresh)
- All pages consistent width: max-w-6xl

### Auth & Users
- Sanctum token auth (localStorage: `voltexahub_token`)
- Register, Login, Logout
- Password reset (forgot-password + reset-password, frontend + backend)
- Avatar upload (POST /api/user/avatar, stored in public/storage/avatars/)
- Online tracking — last_seen, UpdateLastSeen middleware (throttled 2min)
- GET /api/users/online — recently active users

### Forum
- Full game → category → forum tree (public + admin)
- Thread list, thread view, create thread, create reply
- Edit post (author or mod), edit thread (author or admin)
- Post reactions (credits awarded to author)
- @mention parsing — sends MentionNotification + broadcasts
- Thread likes — heart button + comma-separated username display
- Thread pinning, locking, deletion, move (admin/mod toolbar)
- Pinned thread visual divider
- Forum icons: FA classes stored in DB
- Per-forum permissions: can_view / can_post / can_reply per role (including guest)

### Forum Permissions System
- forum_permissions table (forum_id, role_name, can_view, can_post, can_reply)
- Roles: guest, member, vip, elite, moderator, admin
- Admin UI: shield button on each forum → matrix editor
- Default: all allowed if no row exists

### Usergroup System
- Group colors + labels stored in ForumConfig (group_color_{role}, group_label_{role})
- group_color + group_label computed on User model ($appends) — flows to all API responses
- Usernames colored by group everywhere: postbit, thread list, home last-post, profile header, members page
- Usergroup legend: horizontal row below forum index (toggleable)
- Admin Groups page: create/edit/delete roles with color + label

### Members & Staff Pages
- /members — grid layout, sortable (newest/posts/credits/A-Z), searchable, paginated 24/page
- /staff — grouped by role (admin, moderator) with group color accents + top border
- Both use group_color for username display

### Search
- GET /api/search?q=&type=all|threads|posts|users&page=
- SearchView.vue — tabbed, URL sync, term highlighting, skeletons

### Notifications
- 6 types: ThreadReply, Mention, AwardReceived, AchievementUnlocked, PurchaseConfirmed, DMReceived
- Bell icon with unread badge, dropdown (10 recent), full page (/notifications)
- Mark as read (individual + all)
- Admin bell wired to /notifications

### Private Messages
- conversations + messages + conversation_user tables
- MessagesView.vue — split panel inbox + conversation
- ComposeModal.vue
- Envelope icon with unread badge in header

### Real-time (Soketi)
- NewNotification + NewMessage broadcast events
- Private user channels + online presence channel
- Laravel Echo in main.js, auto-subscribes on login
- Presence store (stores/presence.js) — online users widget

### Content Editor
- MarkdownEditor.vue — toolbar (Bold/Italic/Code/CodeBlock/Link/Quote/UL/OL/HR), preview toggle
- MarkdownRenderer.vue — XSS-safe styled output
- BBCode support: planned (Option C — Markdown + BBCode side by side)

### Moderation
- Pin/unpin, lock/unlock, delete, move thread (mod toolbar in ThreadView)
- Per-post mod actions: delete post / delete thread
- Forum last_post_at + last_post_user_id recalculated after deletion

### Credits System
- Credits log (credits_log table)
- Earned on: thread, reply, reaction received, achievement, purchase
- Role-based multipliers (JSON in forum_config)
- Config-driven amounts (credits_per_thread, credits_per_reply, etc.)
- GET /api/credits/earning-info (public)
- CreditsView.vue — balance, log, "How to Earn" guide, role bonus table

### Store
- Stripe PaymentIntents (real money)
- Credits purchases (direct balance deduct)
- RCON delivery (DeliveryService + queued job)
- Purchase confirmation email

### Email System
- SMTP config stored in DB (ForumConfig) — applied at runtime via AppServiceProvider
- No server restart needed to change email settings
- Admin config panel: Email/SMTP section with Send Test button
- Test email fires to logged-in admin's email, shows success/error inline
- Emails: WelcomeEmail, PurchaseConfirmation, VerifyEmail, PasswordReset, MentionNotification
- Hostinger SMTP: smtp.hostinger.com, port 465, SSL, from must match authenticated user

### Admin Panel (50+ endpoints)
- Dashboard — real stats (users, posts, threads, online, revenue, recent activity)
- User management — search, ban, award, credits adjust, role change (refreshes immediately)
- Forum tree — drag-to-reorder, full CRUD, FA icon picker
- Forum Permissions editor — per-forum role/permission matrix
- Award management — custom image upload
- Store items + purchases
- Achievements CRUD
- Config — forum settings, credits, store, email/SMTP, usergroup legend toggle
- Admin Groups — create/edit/delete roles with color + label
- Reorder API for games/categories/forums

### Config System
- All values as strings in forum_config (key/value)
- ForumConfig::get(key, default) + set(key, value) static helpers
- Booleans stored as "true"/"false" — coerce with === 'true' on frontend
- Cached in localStorage on frontend to prevent flash

### White-Label
- Zero hardcoded brand names
- Forum name, accent color, URLs all from forum_config
- Emails use ForumConfig::get('forum_name')

### UI Polish
- Dark/light mode toggle (FA moon/sun icons)
- Postbit: group color top border, avatar, username, badge, stats (posts/credits/joined), awards
- Date formatting utility: formatDate, formatDateTime, formatRelative, formatJoinDate
- AppFooter: 4-column (Community, Account, Support) + "Powered by VoltexaHub"
- All pages max-w-6xl for consistent layout

---

## Session: 2026-03-06 — Major Feature Sprint

### XP Boost Store Items
- `user_xp_boosts` table (user_id, multiplier, expires_at) + `UserXpBoost` model
- `XpService::award()` checks active boost and multiplies XP
- `StoreController` handles `xp_boost` item type — purchases stack by extending `expires_at`
- Profile: amber glowing badge with live countdown timer
- Postbit: amber ⚡ icon when author has active boost
- Navbar: pulsing amber bolt when logged-in user has active boost
- Store: amber "XP Boost" tag on boost items
- Admin store form: replaced raw JSON input with multiplier (1.5x/2x/3x/5x) + duration dropdowns

### Admin Panel Improvements
- **Dashboard**: added Posts Today / Threads Today / New Users Today stat cards, Top 5 Forums ranked list, fixed recent registrations + purchases, removed dead `failed_deliveries` field
- **Reports**: lazy-load tabs (only pending fetched on mount), inline post content preview + "View Thread →" link
- **Thread moderation**: checkboxes + bulk action bar (lock/unlock/delete selected)
- **Admin nav**: removed redundant Content → Threads and Content → Posts pages; moved Forums into Content section alongside Thread Prefixes
- **Admin sidebar**: removed bolt icon from header

### Performance Fixes
- Levels table cached for 5 minutes (busted on admin save/delete)
- `Post::getAuthorAttribute()` — eager loads `activeBoost` relation instead of per-post query; uses cached levels
- Subforum stats — replaced per-subforum loop (N+1) with 3 bulk queries total
- Removed redundant manual level computation from PostController

### Component Refactors
- Split `UserCPView.vue` (1,368 lines) → `UserCPProfile`, `UserCPAccount`, `UserCPNotifications`, `UserCPCover` sub-components
- Split `ThreadView.vue` (885 lines) → `PostCard.vue`, `PostEditor.vue` sub-components

### Subforum Display
- Fixed admin forum list not showing subforums (flattened into category.forums after fetch)
- Removed redundant "Sub forum of: X" label from admin list
- `ThreadController` now includes subforums with thread_count, post_count, last_thread, lastPostUser
- Subforum rows in ThreadListView use same grid as thread header (aligned columns)
- Added Forum/Posts/Last Post header row to subforum section
- Removed pinned/regular thread divider from thread list
- Homepage subforum strip: inline links with subtle darker bg

### FA Icon Picker
- Expanded to full FA7 free solid set (~1,385 icons incl. all folder variants)

### Profile Page Enrichment
- **Mood/Status**: nullable `status` field (max 100), set in UserCP, shows below username on profile
- **Pinned Thread**: `pinned_thread_id` on users; "Pin to Profile" button on first post; amber card on profile
- **Reputation**: total likes received across all posts, shown in stats row
- **Stat breakdown**: replies_made, likes_given returned from API
- **Recent Activity feed**: last 8 posts with thread/forum context + excerpt, timeline style on Overview tab

### Credits Page
- Replaced `&#9889;` lightning bolt entities with `fa-solid fa-coins` icon
- "How to Earn Credits" grid: 4 boxes per row (was 3)

### GitHub / Deployment
- All repos migrated to VoltexaHub org as origin
- community.voltexahub.com (187.124.80.32) updated throughout

---

## What's Next 🔧

### High Priority
1. **BBCode editor (Option C)** — Markdown + BBCode side by side
   - s9e/TextFormatter PHP library for parsing
   - BBCode toolbar: color, size, spoiler, code block, image, video embeds

2. **Thread subscriptions**
   - thread_subscriptions table (user_id, thread_id)
   - Subscribe button in ThreadView
   - Trigger ThreadReply notification for subscribers

3. **Report system** ✅ (built — reports table, report button, admin queue with inline preview)

### Medium Priority
4. **Image embeds** — upload endpoint + paste/drag handler in MarkdownEditor
5. **Solved badge in thread list** — green pill next to title for solved threads (deferred, low priority)
6. **Email templates** — deferred
7. **Frontend password reset form** — /reset-password (backend done)
8. **Email verification flow** — frontend redirect from email link → verify

### Profile — Future Ideas (Lower Priority)
- Visitor log (profile_visits table, opt-in)
- Showcase slots (JSON column, 3–5 featured items/links)
- Rarity badge ("Top X% poster this month")
- Longest streak (needs daily activity tracking)
- Custom profile layout / section ordering (premium)
- Follow/follower system (new table)

### Lower Priority
9. **Leaderboard** — top credits earners, top posters
10. **Tag/flair system** — thread tags, filter by tag
11. **Plugin system** — admin panel hook registration
12. **Marketing site (voltexahub.com)** — showcase site

### Known Issues / Tech Debt
- BBCode not yet implemented (just Markdown)
- Email verification flow not wired on frontend
- RCON delivery untested end-to-end
- Stripe keys are placeholders in dev
- `php artisan serve` in systemd (dev simplification — OK for now)

---

## Architecture Notes
- Frontend auth: Sanctum token in localStorage as `voltexahub_token`
- API format: `{ data: ..., message: ..., meta: ... }`
- Admin routes: /api/admin/* — auth:sanctum + role:admin
- Forum config: key/value strings — always coerce booleans client-side
- Credits: all changes logged in credits_log with balance_after
- Forum model: threads() hasMany + subforums() hasMany — NO direct posts() relation
- Public forum tree: games → categories → forums
- Emails: queued via database queue
- group_color / group_label: computed on User model via $appends, reads ForumConfig at runtime
- Forum permissions: no row = all allowed (open by default)

## Key Files
```
voltexaforum/src/
  services/api.js              — all API calls
  stores/auth.js               — auth (token, user, setAvatarUrl)
  stores/forum.js              — config (localStorage cache, isMultiGame, isMaintenanceMode)
  stores/notifications.js      — notification state
  stores/messages.js           — DM state
  stores/presence.js           — online users (Soketi)
  echo.js                      — Laravel Echo setup
  router/index.js              — routes + guards (auth, admin, maintenance)
  utils/date.js                — formatDate, formatDateTime, formatRelative, formatJoinDate
  components/AppFooter.vue     — sitewide footer
  components/UsergroupLegend.vue — horizontal group legend
  components/MarkdownEditor.vue
  components/MarkdownRenderer.vue
  components/FaIconPicker.vue
  components/UserAvatar.vue
  components/NotificationDropdown.vue
  components/ComposeModal.vue
  views/admin/AdminConfig.vue  — all settings (email, credits, store, general)
  views/admin/AdminForumPermissions.vue — per-forum permission matrix
  views/admin/AdminGroups.vue  — usergroup CRUD
  views/MembersView.vue        — public members directory
  views/StaffView.vue          — public staff page

voltexahub/
  app/Http/Controllers/Api/    — public + auth controllers
  app/Http/Controllers/Api/Admin/ — admin controllers
  app/Models/ForumConfig.php   — get/set static helpers
  app/Models/ForumPermission.php — per-forum permissions
  app/Models/User.php          — group_color + group_label in $appends
  app/Providers/AppServiceProvider.php — applies mail config from DB at runtime
  app/Services/RconService.php
  app/Services/DeliveryService.php
  app/Jobs/DeliverPurchase.php
  database/seeders/ForumConfigSeeder.php — all config defaults
  database/seeders/DefaultContentSeeder.php — idempotent firstOrCreate
  routes/api.php
  bootstrap/app.php            — exception handler (API → 401 JSON)
  install.sh                   — full VPS installer
```
