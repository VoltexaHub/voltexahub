# VoltexaHub

A modern, open-source forum engine built for a great theming and plugin developer experience — an alternative to MyBB/phpBB with responsive, mobile-first defaults and a small, coherent Blade-based theme surface.

**Status:** early development. v0.1 foundation shipped.
**License:** AGPL-3.0-or-later (see [LICENSE](LICENSE)).

## Stack

- **Backend:** PHP 8.4, Laravel 13
- **Admin UI:** Inertia + Vue 3
- **Public UI:** Blade themes (Tailwind + Typography)
- **Data:** Postgres 16, Redis 7
- **Frontend build:** Vite
- **Markdown:** league/commonmark (GFM) + EasyMDE client editor
- **Deploy:** Docker Compose

## Quickstart

Requires Docker Desktop.

```bash
git clone https://github.com/VoltexaHub/VoltexaHub.git
cd VoltexaHub

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate:fresh --seed --force
```

Open http://localhost:8080.

**Seeded admin account:**
- Email: `admin@voltexahub.test`
- Password: `password`

The admin panel is at `/admin`. Vite HMR runs at http://localhost:5173.

## Project layout

```
app/                    # Laravel application
  Http/Controllers/     # Public + Admin controllers
  Models/               # Category, Forum, Thread, Post, User
  Services/             # ThemeManager, PluginManager, HookManager, Markdown
resources/js/Pages/     # Inertia/Vue pages (admin + auth)
themes/                 # Blade themes
  default/
    theme.json
    views/              # layout, forum-index, forum-show, thread-show, thread-create, post-edit
plugins/                # Drop-in plugins
  welcome-banner/
    plugin.json
    plugin.php          # hook registrations
    views/              # Blade views under `plugin.<slug>::` namespace
    migrations/         # (optional) auto-loaded
    routes.php          # (optional) auto-loaded under web middleware
docker-compose.yml
```

## Authoring a theme

Themes are just a folder of Blade files. Copy `themes/default` to `themes/<slug>`, edit `theme.json`, and point `VOLTEXAHUB_THEME=<slug>` in your `.env`.

Required views:
- `layout.blade.php` — the shell (header, nav, `@yield('content')`, hook slots)
- `forum-index.blade.php`
- `forum-show.blade.php`
- `thread-show.blade.php`
- `thread-create.blade.php`
- `post-edit.blade.php`

Exposed hook slots in the default layout: `head`, `before_content`, `after_content`. Invoke with `@hook('name')`.

## Authoring a plugin

Create `plugins/<slug>/plugin.json`:

```json
{
    "slug": "my-plugin",
    "name": "My Plugin",
    "version": "1.0.0",
    "description": "What it does",
    "author": "You"
}
```

Add a `plugin.php` bootstrap file. Three variables are in scope: `$app`, `$hooks`, `$plugin` (manifest + metadata).

```php
<?php
$hooks->listen('before_content', function () use ($plugin) {
    return view('plugin.'.$plugin['slug'].'::banner')->render();
});
```

Optional files that auto-load when the plugin is enabled:
- `views/` → registered at `plugin.<slug>::` view namespace
- `migrations/` → picked up by `php artisan migrate`
- `routes.php` → registered under the `web` middleware group

Enable/disable from **Admin → Plugins**. State is persisted to `storage/app/plugins.json`.

See `plugins/welcome-banner/` for a minimal working example.

## Development

```bash
docker compose exec app php artisan test        # PHPUnit
docker compose exec node npm run build          # Production asset build
docker compose exec app php artisan migrate     # Apply migrations
docker compose logs -f app                      # Tail Laravel logs
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Solo for now; contributions welcome as the project matures.

## Deploying to production

See [docs/DEPLOY.md](docs/DEPLOY.md) for a single-host recipe. The repo ships `docker-compose.prod.yml` and `docker/Caddyfile` — point the Caddyfile at your domain, fill in your `.env`, and the stack boots with HTTPS, a queue worker, a scheduler (for weekly digests), and Reverb WebSockets.

Pre-flight a deployment any time with:

```bash
docker compose exec app php artisan app:preflight
```

— it checks APP_DEBUG, DB connectivity, pending migrations, storage link, mail/queue drivers, Reverb, OAuth providers, and exits non-zero on any blocker so you can wire it into a CI/CD gate.

## Roadmap

**v0.1 (foundation — shipped):** auth, forums/categories/threads/posts, markdown editor, default responsive theme, theme + plugin loaders, admin panel, Docker deploy.

**v0.2 (planned):** WebSocket live replies (Reverb), WYSIWYG, SSO/OAuth, moderation queue, private messages, advanced search, user profile pages.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
