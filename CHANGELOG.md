# VoltexaHub Changelog

All confirmed, working changes only. One entry per resolved feature or fix — no iteration noise.

Format: `[feat]` `[fix]` `[security]` `[perf]` `[breaking]`

---

## v0.4.0 — March 10, 2026

### Sites
- [feat] docs.voltexahub.com — VitePress documentation site (30+ pages)
- [feat] api.voltexahub.com — Stripe-style API reference (all endpoints, cURL/Axios examples, dark/light mode)
- [feat] voltexahub.com — marketing site (features, showcase, changelog, mobile nav)

### Security
- [security] Multi-factor authentication — TOTP, email OTP, 8 recovery codes (bcrypt hashed)
- [security] MFA login intercept — temp_token flow, 5-min TTL, Sanctum token only issued after MFA verified
- [security] Trust this device — localStorage 30-day expiry, auto-sends email OTP on next login
- [security] Admin MFA reset — `DELETE /api/admin/users/{id}/mfa`
- [security] Active sessions manager — GeoIP, revoke single or all sessions
- [security] Admin security email alerts — fires on every admin login, password change, email change (synchronous)
- [security] Password policy enforcement + account lockout (10 attempts / 15 min)
- [security] Email change verification via signed URL to old address
- [security] Content Security Policy headers via SecureHeaders middleware
- [security] Rate limiting — threads (10/min), posts (20/min), search (30/min), MFA email (5/min), MFA verify (10/min)
- [security] CF-Connecting-IP fix — getRealIp() checks CF → X-Forwarded-For → request IP
- [security] Username enumeration fix — register returns generic error for duplicate email/username
- [security] Current password required for email/password changes
- [security] Sanctum token expiry — prune-expired runs daily (720h TTL)
- [security] Queue health check — `GET /api/health/queue`

### Forum
- [feat] Leaderboard rework — XP tab, level badges, your rank banner when outside top 50, mobile selects
- [feat] Card grid homepage layout — admin configurable via home_layout ForumConfig key
- [feat] Per-category header color picker — header_color varchar(7) on categories table
- [feat] Admin user edit rebuilt — tabbed layout, XP adjustment endpoint
- [feat] UpdateLastSeen middleware — stamps last_seen and last_active_at
- [fix] ThreadSubscriptionController — accepts slug or numeric ID

### Infrastructure
- [feat] Docker migration — PHP-FPM, Nginx, MySQL, Redis, Reverb all containerized
- [feat] Laravel Reverb — replaced broken Soketi, WebSockets working
- [feat] Wildcard SSL cert — *.voltexahub.com via certbot DNS challenge (Cloudflare)

---

## v0.3.0 — March 4, 2026

- [feat] Thread prefixes — colored labels ([Guide], [Question], [WIP])
- [feat] Thread tags — freeform hashtags with autocomplete, clickable tag pages
- [feat] Thread Solved / Best Answer — mark solutions, award credits to helpers
- [feat] Leaderboard page — credits/posts/threads/reactions, all-time/month/week, top 3 trophies
- [feat] Locked content improvements — unlock modal, author bypass, Working/Not Working voting
- [feat] Payment providers admin — Stripe, PayPal, Plisio — toggleable with config modals
- [feat] Custom gateway upload — PHP gateways implementing PaymentGatewayInterface
- [feat] Prerequisite plan check — blocks checkout/activate if user lacks required role
- [feat] Plisio multi-currency selection
- [feat] Store item edit modal — FA icon classes or emoji
- [feat] Card grid homepage layout foundation
- [perf] VPS optimizations — OPcache 256MB, MySQL buffer pool, PHP-FPM workers, 2GB swap, Nginx gzip
- [fix] [lock] BBCode placeholder system — strips not processed by Markdown
- [fix] Locked content binding on initial mount

---

## v0.2.5 — March 2026

- [feat] Upgrade plans and perks system (profile cover, custom CSS, username color, userbar hue, awards reorder, change username)
- [feat] Advertisement system with group bypass
- [feat] Locked content BBCode [lock=N]...[/lock]
- [feat] Image upload in editor (file picker, paste, drag & drop)
- [feat] Live BBCode preview via backend renderer
- [feat] Unlock requirements (min posts, must-like, bypass perk)

---

## v0.2.0 — March 2026

- [feat] Private messages — split-panel inbox, real-time
- [feat] Thread subscriptions with reply notifications
- [feat] Post likes + reactions with credit rewards
- [feat] Thread likes with username display
- [feat] BBCode editor toolbar (color, size, spoiler, image, video)
- [feat] Report system + admin moderation queue
- [feat] Awards system — admin CRUD, image upload
- [feat] Credits log + role multipliers

---

## v0.1.0 — February 2026

- [feat] Initial release
- [feat] Full forum engine — categories, forums, subforums, threads, posts
- [feat] User auth — register, login, password reset, email verify
- [feat] Admin panel — users, forums, store, achievements, config
- [feat] Credits system
- [feat] Store with Stripe + credits
- [feat] Real-time notifications via Soketi
- [feat] Plugin system foundation

---

## v0.5.0 — in progress

- [ ] DB automated backups
- [ ] Admin re-auth (password confirm before destructive actions)
