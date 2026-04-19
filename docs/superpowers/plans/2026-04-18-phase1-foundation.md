# VoltexaHub Phase 1 — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a fully functional forum with auth, categories/forums/threads/posts, admin panel, mod tools, and a polished dark/light responsive theme.

**Architecture:** Laravel 13 monolith with Inertia.js serving Vue 3 pages. Domain-based directory structure under `app/` (Auth, Forum, Admin, Moderation, Settings). Single built-in Tailwind CSS theme with dark/light toggle via `dark` class on `<html>`.

**Tech Stack:** PHP 8.4, Laravel 13, Inertia.js v2, Vue 3 (Composition API), Tailwind CSS v4, Postgres 16, Redis, Vite 6, marked.js, highlight.js, @vueuse/core, Cloudflare Turnstile

---

## File Map

```
app/
  Auth/Controllers/         RegisterController, LoginController, LogoutController,
                            ForgotPasswordController, ResetPasswordController,
                            EmailVerificationController
  Auth/Services/            TurnstileService.php
  Forum/Controllers/        ForumIndexController, ForumController,
                            ThreadController, PostController
  Forum/Models/             Category, Forum, Thread, Post, PostReaction
  Admin/Controllers/        DashboardController, CategoryController,
                            ForumController, ThreadController,
                            PostController, UserController,
                            GroupController, SettingController
  Moderation/Controllers/   ModController, ReportController
  Moderation/Models/        Report, ModLog
  Settings/Models/          Setting.php
  Http/Middleware/          HandleInertiaRequests, IsAdmin, IsModerator
  Models/                   User.php (extended), Group.php

database/migrations/        (one per table — see Task 3)

resources/
  css/app.css               Tailwind v4 entry + CSS custom properties
  js/
    app.js                  Inertia bootstrap
    Pages/Auth/             Login, Register, ForgotPassword,
                            ResetPassword, VerifyEmail
    Pages/Forum/            Index (forum list), Show (thread list)
    Pages/Thread/           Show (post list), Create
    Pages/Admin/            Dashboard, Categories/Index,
                            Forums/Index, Threads/Index,
                            Posts/Index, Users/Index,
                            Groups/Index, Settings/Index
    Components/Layout/      AppLayout, AdminLayout, Navbar, Footer
    Components/Forum/       CategorySection, ForumRow, ThreadRow
    Components/Post/        PostCard, MarkdownEditor, PostReplyForm
    Components/Admin/       AdminSidebar, SortableList, ConfirmModal
    Components/UI/          ThemeToggle, Pagination, Alert,
                            Badge, Avatar, Dropdown

routes/
  web.php                   public routes
  auth.php                  auth routes
  admin.php                 admin routes

deploy/
  setup.sh
```

---

## Task 1: Scaffold Laravel project

**Files:**
- Create: all Laravel base files in repo root

- [ ] **Step 1: Scaffold into temp dir then merge**

```bash
cd /s/Claude
composer create-project laravel/laravel:^13 vh_temp --prefer-dist
cp -rn vh_temp/. VoltexaHub/
rm -rf vh_temp
cd VoltexaHub
```

- [ ] **Step 2: Install backend dependencies**

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
```

- [ ] **Step 3: Install frontend dependencies**

```bash
npm install @inertiajs/vue3 vue@^3 @vitejs/plugin-vue \
  @vueuse/core marked highlight.js \
  tailwindcss@^4 @tailwindcss/vite
```

- [ ] **Step 4: Update `vite.config.js`**

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        vue(),
        tailwindcss(),
    ],
    resolve: { alias: { '@': '/resources/js' } },
})
```

- [ ] **Step 5: Create `resources/js/app.js`**

```js
import './bootstrap'
import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp, Link, Head } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

createInertiaApp({
    title: (title) => `${title} — VoltexaHub`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el)
    },
    progress: { color: '#7c3aed' },
})
```

- [ ] **Step 6: Create Inertia middleware**

```bash
php artisan inertia:middleware
```

Register it in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);
})
```

- [ ] **Step 7: Update `.env` for Postgres**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=voltexahub
DB_USERNAME=voltexahub
DB_PASSWORD=secret

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "chore: scaffold Laravel 13 + Inertia + Vue 3 + Tailwind v4"
```

---

## Task 2: Base theme + layout

**Files:**
- Create: `resources/css/app.css`
- Create: `resources/js/Components/Layout/AppLayout.vue`
- Create: `resources/js/Components/Layout/Navbar.vue`
- Create: `resources/js/Components/Layout/Footer.vue`
- Create: `resources/js/Components/UI/ThemeToggle.vue`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Write `resources/css/app.css`**

```css
@import "tailwindcss";

:root {
    --bg: #1a1a2e;
    --surface: #16213e;
    --surface-raised: #1e2547;
    --border: #2d3a5e;
    --text: #f1f5f9;
    --text-muted: #94a3b8;
    --text-faint: #475569;
    --accent: #7c3aed;
    --accent-2: #2563eb;
    --accent-gradient: linear-gradient(135deg, #7c3aed, #2563eb);
    --danger: #ef4444;
    --success: #22c55e;
    --warning: #f59e0b;
}

.light {
    --bg: #f8fafc;
    --surface: #ffffff;
    --surface-raised: #f1f5f9;
    --border: #e2e8f0;
    --text: #0f172a;
    --text-muted: #475569;
    --text-faint: #94a3b8;
}

body { background: var(--bg); color: var(--text); }
```

- [ ] **Step 2: Create `resources/js/Components/UI/ThemeToggle.vue`**

```vue
<script setup>
import { useStorage } from '@vueuse/core'
const dark = useStorage('theme', true)
function toggle() {
    dark.value = !dark.value
    document.documentElement.classList.toggle('light', !dark.value)
}
// Apply on mount
if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('light', !dark.value)
}
</script>
<template>
  <button @click="toggle" class="p-2 rounded-lg hover:bg-white/10 transition-colors" :title="dark ? 'Switch to light' : 'Switch to dark'">
    <span v-if="dark">☀️</span>
    <span v-else>🌙</span>
  </button>
</template>
```

- [ ] **Step 3: Create `resources/js/Components/Layout/Navbar.vue`**

```vue
<script setup>
import ThemeToggle from '@/Components/UI/ThemeToggle.vue'
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const auth = computed(() => page.props.auth)
</script>
<template>
  <nav style="background:var(--surface);border-bottom:1px solid var(--border)"
       class="sticky top-0 z-50 px-4 h-14 flex items-center gap-4">
    <Link :href="route('forum.index')" class="flex items-center gap-2 shrink-0">
      <span class="w-7 h-7 rounded-md flex items-center justify-center text-white text-xs font-bold"
            style="background:var(--accent-gradient)">V</span>
      <span style="color:var(--text)" class="font-bold tracking-wide text-sm hidden sm:block">VOLTEXAHUB</span>
    </Link>

    <div class="flex items-center gap-5 text-sm ml-2" style="color:var(--text-muted)">
      <Link :href="route('forum.index')" class="hover:text-white transition-colors">Forums</Link>
      <Link :href="route('members.index')" class="hover:text-white transition-colors">Members</Link>
      <Link :href="route('staff')" class="hover:text-white transition-colors">Staff</Link>
      <Link :href="route('groups.index')" class="hover:text-white transition-colors">Groups</Link>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <ThemeToggle />
      <template v-if="auth.user">
        <span style="color:var(--text-muted)" class="text-sm">{{ auth.user.username }}</span>
        <Link :href="route('logout')" method="post" as="button"
              class="text-sm px-3 py-1 rounded" style="color:var(--text-muted)">Logout</Link>
      </template>
      <template v-else>
        <Link :href="route('login')" class="text-sm px-3 py-1 rounded hover:bg-white/10 transition-colors"
              style="color:var(--text-muted)">Login</Link>
        <Link :href="route('register')" class="text-sm px-4 py-1.5 rounded-md text-white font-medium"
              style="background:var(--accent-gradient)">Register</Link>
      </template>
    </div>
  </nav>
</template>
```

- [ ] **Step 4: Create `resources/js/Components/Layout/AppLayout.vue`**

```vue
<script setup>
import Navbar from '@/Components/Layout/Navbar.vue'
import Footer from '@/Components/Layout/Footer.vue'
</script>
<template>
  <div class="min-h-screen flex flex-col" style="background:var(--bg)">
    <Navbar />
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
    <Footer />
  </div>
</template>
```

- [ ] **Step 5: Create `resources/js/Components/Layout/Footer.vue`**

```vue
<template>
  <footer class="mt-auto py-6 text-center text-xs" style="color:var(--text-faint);border-top:1px solid var(--border)">
    VoltexaHub &copy; {{ new Date().getFullYear() }}
  </footer>
</template>
```

- [ ] **Step 6: Share auth in `HandleInertiaRequests.php`**

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'username' => $request->user()->username,
                'email' => $request->user()->email,
                'avatar' => $request->user()->avatar,
                'is_admin' => $request->user()->group?->permissions['is_admin'] ?? false,
                'is_moderator' => $request->user()->group?->permissions['is_moderator'] ?? false,
                'credits' => $request->user()->credits,
            ] : null,
        ],
        'flash' => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
    ];
}
```

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(theme): base layout, navbar, dark/light toggle"
```

---

## Task 3: Database migrations

**Files:** All under `database/migrations/`

- [ ] **Step 1: Write failing test for table existence**

```php
// tests/Feature/DatabaseSchemaTest.php
public function test_core_tables_exist(): void
{
    $this->assertDatabaseTableExists('groups');
    $this->assertDatabaseTableExists('categories');
    $this->assertDatabaseTableExists('forums');
    $this->assertDatabaseTableExists('threads');
    $this->assertDatabaseTableExists('posts');
    $this->assertDatabaseTableExists('reports');
    $this->assertDatabaseTableExists('mod_logs');
    $this->assertDatabaseTableExists('settings');
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/DatabaseSchemaTest.php
```
Expected: FAIL — tables don't exist yet.

- [ ] **Step 3: Create groups migration**

```bash
php artisan make:migration create_groups_table
```

```php
Schema::create('groups', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('color', 7)->default('#94a3b8');
    $table->string('icon')->nullable();
    $table->boolean('is_staff')->default(false);
    $table->json('permissions')->default('{}');
    $table->unsignedSmallInteger('display_order')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 4: Modify users table migration**

Add to existing `create_users_table` migration (or create `add_forum_columns_to_users_table`):

```php
$table->string('username')->unique()->after('id');
$table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
$table->string('avatar')->nullable();
$table->text('bio')->nullable();
$table->text('signature')->nullable();
$table->boolean('is_trusted')->default(false);
$table->unsignedBigInteger('credits')->default(0);
$table->unsignedInteger('post_count')->default(0);
$table->unsignedInteger('thread_count')->default(0);
$table->timestamp('last_seen_at')->nullable();
$table->string('banned_reason')->nullable();
$table->timestamp('banned_at')->nullable();
$table->string('referral_code', 12)->unique()->nullable();
$table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete();
```

- [ ] **Step 5: Create remaining migrations**

```bash
php artisan make:migration create_categories_table
php artisan make:migration create_forums_table
php artisan make:migration create_threads_table
php artisan make:migration create_posts_table
php artisan make:migration create_post_reactions_table
php artisan make:migration create_reports_table
php artisan make:migration create_mod_logs_table
php artisan make:migration create_settings_table
```

`create_categories_table`:
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->unsignedSmallInteger('display_order')->default(0);
    $table->timestamps();
});
```

`create_forums_table`:
```php
Schema::create('forums', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('icon')->nullable();
    $table->unsignedSmallInteger('display_order')->default(0);
    $table->unsignedInteger('thread_count')->default(0);
    $table->unsignedInteger('post_count')->default(0);
    $table->foreignId('last_post_id')->nullable()->constrained('posts')->nullOnDelete();
    $table->timestamps();
});
```

`create_threads_table`:
```php
Schema::create('threads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('forum_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('slug')->unique();
    $table->boolean('is_pinned')->default(false);
    $table->boolean('is_locked')->default(false);
    $table->boolean('is_deleted')->default(false);
    $table->unsignedInteger('views')->default(0);
    $table->unsignedInteger('reply_count')->default(0);
    $table->foreignId('last_post_id')->nullable()->constrained('posts')->nullOnDelete();
    $table->timestamps();
    $table->index(['forum_id', 'is_deleted', 'is_pinned', 'created_at']);
});
```

`create_posts_table`:
```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('body');
    $table->boolean('is_deleted')->default(false);
    $table->timestamp('edited_at')->nullable();
    $table->foreignId('edited_by_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index(['thread_id', 'is_deleted', 'created_at']);
});
```

`create_post_reactions_table`:
```php
Schema::create('post_reactions', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->string('type', 20)->default('like');
    $table->primary(['user_id', 'post_id']);
    $table->timestamps();
});
```

`create_reports_table`:
```php
Schema::create('reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
    $table->morphs('reportable');
    $table->text('reason');
    $table->string('status', 20)->default('open');
    $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index('status');
});
```

`create_mod_logs_table`:
```php
Schema::create('mod_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
    $table->string('action', 50);
    $table->string('target_type', 50);
    $table->unsignedBigInteger('target_id');
    $table->text('note')->nullable();
    $table->timestamps();
});
```

`create_settings_table`:
```php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 6: Run migrations**

```bash
php artisan migrate
```
Expected: all tables created with no errors.

- [ ] **Step 7: Run test to verify it passes**

```bash
php artisan test tests/Feature/DatabaseSchemaTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(db): Phase 1 migrations — groups, categories, forums, threads, posts, reports, mod_logs, settings"
```

---

## Task 4: Models and relationships

**Files:**
- Create: `app/Models/Group.php`
- Modify: `app/Models/User.php`
- Create: `app/Forum/Models/Category.php`
- Create: `app/Forum/Models/Forum.php`
- Create: `app/Forum/Models/Thread.php`
- Create: `app/Forum/Models/Post.php`
- Create: `app/Forum/Models/PostReaction.php`
- Create: `app/Moderation/Models/Report.php`
- Create: `app/Moderation/Models/ModLog.php`
- Create: `app/Settings/Models/Setting.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Unit/Models/ThreadModelTest.php
public function test_thread_belongs_to_forum_and_user(): void
{
    $group = Group::factory()->create();
    $user = User::factory()->create(['group_id' => $group->id]);
    $category = Category::factory()->create();
    $forum = Forum::factory()->create(['category_id' => $category->id]);
    $thread = Thread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

    $this->assertInstanceOf(Forum::class, $thread->forum);
    $this->assertInstanceOf(User::class, $thread->user);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Models/ThreadModelTest.php
```
Expected: FAIL — Thread class not found.

- [ ] **Step 3: Create `app/Models/Group.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['name', 'color', 'icon', 'is_staff', 'permissions', 'display_order'];
    protected $casts = ['permissions' => 'array', 'is_staff' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function can(string $permission): bool
    {
        return $this->permissions[$permission] ?? false;
    }
}
```

- [ ] **Step 4: Update `app/Models/User.php`**

```php
protected $fillable = [
    'username', 'email', 'password', 'group_id', 'avatar', 'bio',
    'signature', 'is_trusted', 'credits', 'post_count', 'thread_count',
    'last_seen_at', 'banned_at', 'banned_reason', 'referral_code', 'referred_by_id',
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'last_seen_at' => 'datetime',
    'banned_at' => 'datetime',
    'is_trusted' => 'boolean',
];

public function group(): BelongsTo { return $this->belongsTo(Group::class); }
public function threads(): HasMany { return $this->hasMany(\App\Forum\Models\Thread::class); }
public function posts(): HasMany { return $this->hasMany(\App\Forum\Models\Post::class); }
public function isBanned(): bool { return $this->banned_at !== null; }
public function isAdmin(): bool { return $this->group?->can('is_admin') ?? false; }
public function isModerator(): bool { return $this->group?->can('is_moderator') ?? false; }
```

- [ ] **Step 5: Create forum models**

`app/Forum/Models/Category.php`:
```php
<?php
namespace App\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'display_order'];

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class)->orderBy('display_order');
    }
}
```

`app/Forum/Models/Forum.php`:
```php
<?php
namespace App\Forum\Models;

use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'icon', 'display_order'];

    public function category() { return $this->belongsTo(Category::class); }
    public function threads() { return $this->hasMany(Thread::class); }
    public function lastPost() { return $this->belongsTo(Post::class, 'last_post_id'); }
}
```

`app/Forum/Models/Thread.php`:
```php
<?php
namespace App\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Thread extends Model
{
    protected $fillable = ['forum_id', 'user_id', 'title', 'slug', 'is_pinned', 'is_locked', 'is_deleted'];
    protected $casts = ['is_pinned' => 'boolean', 'is_locked' => 'boolean', 'is_deleted' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Thread $thread) {
            $thread->slug = Str::slug($thread->title) . '-' . Str::random(6);
        });
    }

    public function forum() { return $this->belongsTo(Forum::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function posts() { return $this->hasMany(Post::class)->where('is_deleted', false)->oldest(); }
    public function lastPost() { return $this->belongsTo(Post::class, 'last_post_id'); }
}
```

`app/Forum/Models/Post.php`:
```php
<?php
namespace App\Forum\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['thread_id', 'user_id', 'body', 'is_deleted', 'edited_at', 'edited_by_id'];
    protected $casts = ['is_deleted' => 'boolean', 'edited_at' => 'datetime'];

    public function thread() { return $this->belongsTo(Thread::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function reactions() { return $this->hasMany(PostReaction::class); }
}
```

`app/Forum/Models/PostReaction.php`:
```php
<?php
namespace App\Forum\Models;
use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    protected $fillable = ['user_id', 'post_id', 'type'];
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function post() { return $this->belongsTo(Post::class); }
}
```

`app/Moderation/Models/Report.php`:
```php
<?php
namespace App\Moderation\Models;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['reporter_id', 'reportable_type', 'reportable_id', 'reason', 'status', 'resolved_by'];

    public function reporter() { return $this->belongsTo(\App\Models\User::class, 'reporter_id'); }
    public function reportable() { return $this->morphTo(); }
    public function resolver() { return $this->belongsTo(\App\Models\User::class, 'resolved_by'); }
}
```

`app/Moderation/Models/ModLog.php`:
```php
<?php
namespace App\Moderation\Models;
use Illuminate\Database\Eloquent\Model;

class ModLog extends Model
{
    protected $fillable = ['moderator_id', 'action', 'target_type', 'target_id', 'note'];
    public function moderator() { return $this->belongsTo(\App\Models\User::class, 'moderator_id'); }
}
```

`app/Settings/Models/Setting.php`:
```php
<?php
namespace App\Settings\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", fn() =>
            static::where('key', $key)->value('value') ?? $default
        );
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
```

- [ ] **Step 6: Create model factories**

```bash
php artisan make:factory GroupFactory --model=Group
php artisan make:factory CategoryFactory --model="App\Forum\Models\Category"
php artisan make:factory ForumFactory --model="App\Forum\Models\Forum"
php artisan make:factory ThreadFactory --model="App\Forum\Models\Thread"
php artisan make:factory PostFactory --model="App\Forum\Models\Post"
```

`GroupFactory`:
```php
public function definition(): array
{
    return [
        'name' => fake()->word(),
        'color' => '#7c3aed',
        'is_staff' => false,
        'permissions' => ['can_post' => true, 'can_create_thread' => true, 'is_admin' => false, 'is_moderator' => false],
        'display_order' => 0,
    ];
}
```

`CategoryFactory`:
```php
public function definition(): array
{
    return ['name' => fake()->words(2, true), 'display_order' => 0];
}
```

`ForumFactory`:
```php
public function definition(): array
{
    return [
        'category_id' => Category::factory(),
        'name' => fake()->words(3, true),
        'description' => fake()->sentence(),
        'display_order' => 0,
    ];
}
```

`ThreadFactory`:
```php
public function definition(): array
{
    return [
        'forum_id' => Forum::factory(),
        'user_id' => User::factory(),
        'title' => fake()->sentence(),
        'is_pinned' => false,
        'is_locked' => false,
        'is_deleted' => false,
    ];
}
```

`PostFactory`:
```php
public function definition(): array
{
    return [
        'thread_id' => Thread::factory(),
        'user_id' => User::factory(),
        'body' => fake()->paragraphs(2, true),
        'is_deleted' => false,
    ];
}
```

- [ ] **Step 7: Run test to verify it passes**

```bash
php artisan test tests/Unit/Models/ThreadModelTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(models): Group, Category, Forum, Thread, Post, Report, ModLog, Setting + factories"
```

---

## Task 5: Authentication

**Files:**
- Create: `app/Auth/Services/TurnstileService.php`
- Create: `app/Auth/Controllers/RegisterController.php`
- Create: `app/Auth/Controllers/LoginController.php`
- Create: `app/Auth/Controllers/LogoutController.php`
- Create: `app/Auth/Controllers/ForgotPasswordController.php`
- Create: `app/Auth/Controllers/ResetPasswordController.php`
- Create: `app/Auth/Controllers/EmailVerificationController.php`
- Create: `resources/js/Pages/Auth/Login.vue`
- Create: `resources/js/Pages/Auth/Register.vue`
- Create: `resources/js/Pages/Auth/ForgotPassword.vue`
- Create: `resources/js/Pages/Auth/ResetPassword.vue`
- Create: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `routes/auth.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Auth/RegistrationTest.php
public function test_new_user_can_register(): void
{
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        '_turnstile' => 'test-token',
    ]);
    $response->assertRedirect('/');
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}

public function test_login_with_valid_credentials(): void
{
    $user = User::factory()->create(['password' => bcrypt('Password1!')]);
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Password1!',
        '_turnstile' => 'test-token',
    ]);
    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Auth/RegistrationTest.php
```
Expected: FAIL — route not found.

- [ ] **Step 3: Create `TurnstileService.php`**

```php
<?php
namespace App\Auth\Services;

use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function verify(string $token, string $ip): bool
    {
        if (app()->environment('testing')) return true;

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return $response->json('success') === true;
    }
}
```

Add to `config/services.php`:
```php
'turnstile' => [
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret' => env('TURNSTILE_SECRET_KEY'),
],
```

- [ ] **Step 4: Create `RegisterController.php`**

```php
<?php
namespace App\Auth\Controllers;

use App\Auth\Services\TurnstileService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController
{
    public function show()
    {
        return Inertia::render('Auth/Register', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile)
    {
        if (!$turnstile->verify($request->input('_turnstile', ''), $request->ip())) {
            return back()->withErrors(['_turnstile' => 'Captcha verification failed.']);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:users', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'referral_code' => Str::upper(Str::random(8)),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('forum.index');
    }
}
```

- [ ] **Step 5: Create `LoginController.php`**

```php
<?php
namespace App\Auth\Controllers;

use App\Auth\Services\TurnstileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController
{
    public function show()
    {
        return Inertia::render('Auth/Login', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile)
    {
        if (!$turnstile->verify($request->input('_turnstile', ''), $request->ip())) {
            return back()->withErrors(['_turnstile' => 'Captcha verification failed.']);
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('forum.index'));
    }
}
```

- [ ] **Step 6: Create remaining auth controllers**

`LogoutController.php`:
```php
<?php
namespace App\Auth\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    public function __invoke(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('forum.index');
    }
}
```

`ForgotPasswordController.php`:
```php
<?php
namespace App\Auth\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class ForgotPasswordController
{
    public function show() { return Inertia::render('Auth/ForgotPassword'); }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'If that email exists, a reset link has been sent.');
    }
}
```

`ResetPasswordController.php`:
```php
<?php
namespace App\Auth\Controllers;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ResetPasswordController
{
    public function show(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token, 'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successfully.')
            : back()->withErrors(['email' => __($status)]);
    }
}
```

`EmailVerificationController.php`:
```php
<?php
namespace App\Auth\Controllers;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationController
{
    public function show() { return Inertia::render('Auth/VerifyEmail'); }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('forum.index')->with('success', 'Email verified!');
    }

    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent.');
    }
}
```

- [ ] **Step 7: Register routes in `routes/auth.php`**

```php
<?php
use App\Auth\Controllers\{
    RegisterController, LoginController, LogoutController,
    ForgotPasswordController, ResetPasswordController, EmailVerificationController
};
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/verify-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});
```

Include in `routes/web.php`:
```php
require __DIR__.'/auth.php';
```

- [ ] **Step 8: Create `resources/js/Pages/Auth/Login.vue`**

```vue
<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const props = defineProps({ turnstileSiteKey: String })
const form = useForm({ email: '', password: '', remember: false, _turnstile: '' })
const turnstileWidget = ref(null)

onMounted(() => {
    if (window.turnstile && props.turnstileSiteKey) {
        window.turnstile.render(turnstileWidget.value, {
            sitekey: props.turnstileSiteKey,
            callback: (token) => { form._turnstile = token },
        })
    }
})

function submit() { form.post(route('login')) }
</script>
<template>
  <AppLayout>
    <Head title="Login" />
    <div class="max-w-md mx-auto mt-16">
      <h1 class="text-2xl font-bold mb-6" style="color:var(--text)">Sign in</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Email</label>
          <input v-model="form.email" type="email" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Password</label>
          <input v-model="form.password" type="password" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
        </div>
        <div ref="turnstileWidget"></div>
        <p v-if="form.errors._turnstile" class="text-red-400 text-xs">{{ form.errors._turnstile }}</p>
        <button type="submit" :disabled="form.processing"
                class="w-full py-2 rounded-lg text-white font-medium text-sm transition-opacity"
                :class="form.processing ? 'opacity-50' : ''"
                style="background:var(--accent-gradient)">
          Sign in
        </button>
        <p class="text-sm text-center" style="color:var(--text-muted)">
          No account? <Link :href="route('register')" style="color:var(--accent)">Register</Link>
        </p>
      </form>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 9: Create `resources/js/Pages/Auth/Register.vue`**

Same structure as Login.vue but with username, email, password, password_confirmation fields. Add Turnstile widget. Submit to `route('register')`.

- [ ] **Step 10: Create remaining auth pages**

`ForgotPassword.vue` — single email field, submit to `route('password.email')`, show flash success.  
`ResetPassword.vue` — email (pre-filled), password, password_confirmation, submit to `route('password.update')`.  
`VerifyEmail.vue` — instructions + resend button posting to `route('verification.send')`.

- [ ] **Step 11: Add Turnstile script to `resources/views/app.blade.php`**

```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
```

- [ ] **Step 12: Run tests**

```bash
php artisan test tests/Feature/Auth/
```
Expected: PASS

- [ ] **Step 13: Commit**

```bash
git add -A
git commit -m "feat(auth): register, login, logout, password reset, email verification + Turnstile"
```

---

## Task 6: Forum index and forum view

**Files:**
- Create: `app/Forum/Controllers/ForumIndexController.php`
- Create: `app/Forum/Controllers/ForumController.php`
- Create: `resources/js/Pages/Forum/Index.vue`
- Create: `resources/js/Pages/Forum/Show.vue`
- Create: `resources/js/Components/Forum/CategorySection.vue`
- Create: `resources/js/Components/Forum/ForumRow.vue`
- Create: `resources/js/Components/Forum/ThreadRow.vue`
- Create: `resources/js/Components/UI/Pagination.vue`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Forum/ForumIndexTest.php
public function test_forum_index_renders(): void
{
    $category = Category::factory()->create(['name' => 'Development']);
    Forum::factory()->create(['category_id' => $category->id, 'name' => 'Web Dev']);

    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Forum/Index')
        ->has('categories', 1)
    );
}

public function test_forum_show_lists_threads(): void
{
    $forum = Forum::factory()->create();
    Thread::factory()->count(3)->create(['forum_id' => $forum->id]);

    $response = $this->get("/forum/{$forum->id}");
    $response->assertInertia(fn ($page) => $page
        ->component('Forum/Show')
        ->has('threads.data', 3)
    );
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Forum/ForumIndexTest.php
```
Expected: FAIL — route not found.

- [ ] **Step 3: Create `ForumIndexController.php`**

```php
<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Category;
use App\Settings\Models\Setting;
use Inertia\Inertia;

class ForumIndexController
{
    public function __invoke()
    {
        $categories = Category::with([
            'forums' => fn($q) => $q->orderBy('display_order'),
            'forums.lastPost.user',
        ])->orderBy('display_order')->get();

        return Inertia::render('Forum/Index', [
            'categories' => $categories,
            'siteName' => Setting::get('site_name', 'VoltexaHub'),
        ]);
    }
}
```

- [ ] **Step 4: Create `ForumController.php`**

```php
<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use Inertia\Inertia;

class ForumController
{
    public function show(Forum $forum)
    {
        $threads = Thread::where('forum_id', $forum->id)
            ->where('is_deleted', false)
            ->with(['user.group', 'lastPost.user'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_id')
            ->paginate(25);

        return Inertia::render('Forum/Show', [
            'forum' => $forum->load('category'),
            'threads' => $threads,
        ]);
    }
}
```

- [ ] **Step 5: Add routes to `routes/web.php`**

```php
use App\Forum\Controllers\{ForumIndexController, ForumController, ThreadController, PostController};

Route::get('/', ForumIndexController::class)->name('forum.index');
Route::get('/forum/{forum}', [ForumController::class, 'show'])->name('forum.show');
Route::get('/thread/{thread:slug}', [ThreadController::class, 'show'])->name('thread.show');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/forum/{forum}/new-thread', [ThreadController::class, 'create'])->name('thread.create');
    Route::post('/forum/{forum}/new-thread', [ThreadController::class, 'store'])->name('thread.store');
    Route::post('/thread/{thread:slug}/reply', [PostController::class, 'store'])->name('post.store');
    Route::put('/post/{post}', [PostController::class, 'update'])->name('post.update');
    Route::delete('/post/{post}', [PostController::class, 'destroy'])->name('post.destroy');
});
```

- [ ] **Step 6: Create `resources/js/Components/UI/Pagination.vue`**

```vue
<script setup>
defineProps({ links: Array })
</script>
<template>
  <div v-if="links?.length > 3" class="flex gap-1 mt-6 justify-center">
    <template v-for="link in links" :key="link.label">
      <Link v-if="link.url" :href="link.url"
            class="px-3 py-1.5 rounded text-sm transition-colors"
            :style="link.active
              ? 'background:var(--accent);color:white'
              : 'background:var(--surface);color:var(--text-muted)'"
            v-html="link.label" />
      <span v-else class="px-3 py-1.5 rounded text-sm opacity-40"
            style="color:var(--text-muted)" v-html="link.label" />
    </template>
  </div>
</template>
```

- [ ] **Step 7: Create `resources/js/Pages/Forum/Index.vue`**

```vue
<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
defineProps({ categories: Array, siteName: String })
</script>
<template>
  <AppLayout>
    <Head :title="siteName" />
    <div class="space-y-8">
      <div v-for="category in categories" :key="category.id">
        <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--accent)">
          {{ category.name }}
        </h2>
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
          <div v-for="(forum, i) in category.forums" :key="forum.id"
               class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-white/5"
               :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
            <div class="text-2xl w-8 text-center">{{ forum.icon || '💬' }}</div>
            <div class="flex-1 min-w-0">
              <Link :href="route('forum.show', forum.id)"
                    class="font-semibold text-sm hover:underline"
                    style="color:var(--text)">{{ forum.name }}</Link>
              <p v-if="forum.description" class="text-xs mt-0.5 truncate" style="color:var(--text-muted)">
                {{ forum.description }}
              </p>
            </div>
            <div class="text-right shrink-0 hidden sm:block">
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.thread_count }} threads</div>
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.post_count }} posts</div>
            </div>
            <div v-if="forum.last_post" class="text-right shrink-0 hidden md:block max-w-40">
              <div class="text-xs truncate" style="color:var(--text-muted)">
                <Link :href="route('thread.show', forum.last_post.thread?.slug)"
                      class="hover:underline" style="color:var(--accent)">
                  {{ forum.last_post.user?.username }}
                </Link>
              </div>
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.last_post.created_at }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 8: Create `resources/js/Pages/Forum/Show.vue`**

```vue
<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { usePage } from '@inertiajs/vue3'
const props = defineProps({ forum: Object, threads: Object })
const auth = computed(() => usePage().props.auth)
</script>
<template>
  <AppLayout>
    <Head :title="forum.name" />
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-xs mb-1" style="color:var(--text-faint)">
          <Link :href="route('forum.index')" class="hover:underline" style="color:var(--accent)">Forums</Link>
          → {{ forum.category?.name }}
        </div>
        <h1 class="text-xl font-bold" style="color:var(--text)">{{ forum.name }}</h1>
      </div>
      <Link v-if="auth.user" :href="route('thread.create', forum.id)"
            class="px-4 py-2 rounded-lg text-sm text-white font-medium"
            style="background:var(--accent-gradient)">
        + New Thread
      </Link>
    </div>

    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="!threads.data.length" class="py-12 text-center text-sm" style="color:var(--text-muted)">
        No threads yet. Be the first to post!
      </div>
      <div v-for="(thread, i) in threads.data" :key="thread.id"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/5 transition-colors"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span v-if="thread.is_pinned" class="text-xs px-1.5 py-0.5 rounded" style="background:var(--accent);color:white">📌</span>
            <span v-if="thread.is_locked" class="text-xs" style="color:var(--text-faint)">🔒</span>
            <Link :href="route('thread.show', thread.slug)"
                  class="font-medium text-sm hover:underline" style="color:var(--text)">
              {{ thread.title }}
            </Link>
          </div>
          <div class="text-xs mt-0.5" style="color:var(--text-faint)">
            by <span style="color:var(--text-muted)">{{ thread.user?.username }}</span>
          </div>
        </div>
        <div class="text-xs text-right shrink-0 hidden sm:block" style="color:var(--text-faint)">
          <div>{{ thread.reply_count }} replies</div>
          <div>{{ thread.views }} views</div>
        </div>
        <div v-if="thread.last_post" class="text-xs text-right shrink-0 hidden md:block w-28" style="color:var(--text-muted)">
          <div>{{ thread.last_post.user?.username }}</div>
          <div style="color:var(--text-faint)">{{ thread.last_post.created_at }}</div>
        </div>
      </div>
    </div>

    <Pagination :links="threads.links" />
  </AppLayout>
</template>
```

- [ ] **Step 9: Run tests**

```bash
php artisan test tests/Feature/Forum/ForumIndexTest.php
```
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(forum): index page, forum view, thread list with pagination"
```

---

## Task 7: Thread view + markdown editor + post reply

**Files:**
- Create: `app/Forum/Controllers/ThreadController.php`
- Create: `app/Forum/Controllers/PostController.php`
- Create: `resources/js/Pages/Thread/Show.vue`
- Create: `resources/js/Pages/Thread/Create.vue`
- Create: `resources/js/Components/Post/PostCard.vue`
- Create: `resources/js/Components/Post/MarkdownEditor.vue`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Forum/ThreadTest.php
public function test_thread_show_renders_posts(): void
{
    $thread = Thread::factory()->create();
    Post::factory()->count(3)->create(['thread_id' => $thread->id]);

    $response = $this->get("/thread/{$thread->slug}");
    $response->assertInertia(fn ($page) => $page
        ->component('Thread/Show')
        ->has('posts.data', 3)
    );
}

public function test_authenticated_user_can_post_reply(): void
{
    $user = User::factory()->create();
    $thread = Thread::factory()->create();

    $response = $this->actingAs($user)->post("/thread/{$thread->slug}/reply", [
        'body' => '## Hello world\n\nThis is a test reply.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('posts', ['thread_id' => $thread->id, 'user_id' => $user->id]);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Forum/ThreadTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `ThreadController.php`**

```php
<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use App\Forum\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThreadController
{
    public function show(Thread $thread)
    {
        abort_if($thread->is_deleted, 404);
        $thread->increment('views');

        $posts = Post::where('thread_id', $thread->id)
            ->where('is_deleted', false)
            ->with(['user.group', 'reactions'])
            ->oldest()
            ->paginate(20);

        return Inertia::render('Thread/Show', [
            'thread' => $thread->load(['forum.category', 'user.group']),
            'posts' => $posts,
        ]);
    }

    public function create(Forum $forum)
    {
        return Inertia::render('Thread/Create', ['forum' => $forum]);
    }

    public function store(Request $request, Forum $forum)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'body'  => ['required', 'string', 'min:10'],
        ]);

        $thread = $forum->threads()->create([
            'user_id' => $request->user()->id,
            'title'   => $data['title'],
        ]);

        $post = $thread->posts()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        $thread->update(['last_post_id' => $post->id]);
        $forum->increment('thread_count');
        $forum->increment('post_count');
        $forum->update(['last_post_id' => $post->id]);

        $request->user()->increment('thread_count');
        $request->user()->increment('post_count');

        return redirect()->route('thread.show', $thread->slug);
    }
}
```

- [ ] **Step 4: Create `PostController.php`**

```php
<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use Illuminate\Http\Request;

class PostController
{
    public function store(Request $request, Thread $thread)
    {
        abort_if($thread->is_locked, 403, 'Thread is locked.');

        $data = $request->validate(['body' => ['required', 'string', 'min:1']]);

        $post = $thread->posts()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        $thread->increment('reply_count');
        $thread->update(['last_post_id' => $post->id]);
        $thread->forum->increment('post_count');
        $thread->forum->update(['last_post_id' => $post->id]);
        $request->user()->increment('post_count');

        return redirect()->route('thread.show', $thread->slug)
            ->with('success', 'Reply posted.');
    }

    public function update(Request $request, Post $post)
    {
        abort_unless($request->user()->id === $post->user_id || $request->user()->isModerator(), 403);
        $data = $request->validate(['body' => ['required', 'string', 'min:1']]);
        $post->update(['body' => $data['body'], 'edited_at' => now(), 'edited_by_id' => $request->user()->id]);
        return back()->with('success', 'Post updated.');
    }

    public function destroy(Request $request, Post $post)
    {
        abort_unless($request->user()->id === $post->user_id || $request->user()->isModerator(), 403);
        $post->update(['is_deleted' => true]);
        return back()->with('success', 'Post deleted.');
    }
}
```

- [ ] **Step 5: Create `resources/js/Components/Post/MarkdownEditor.vue`**

```vue
<script setup>
import { ref, computed } from 'vue'
import { marked } from 'marked'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

marked.setOptions({
    highlight: (code, lang) => {
        const language = hljs.getLanguage(lang) ? lang : 'plaintext'
        return hljs.highlight(code, { language }).value
    },
    langPrefix: 'hljs language-',
})

const props = defineProps({ modelValue: String, placeholder: { type: String, default: 'Write your post...' } })
const emit = defineEmits(['update:modelValue'])

const tab = ref('write')
const preview = computed(() => marked.parse(props.modelValue || ''))

function insert(before, after = '') {
    const ta = document.getElementById('md-editor')
    const start = ta.selectionStart, end = ta.selectionEnd
    const selected = props.modelValue.substring(start, end)
    const newVal = props.modelValue.substring(0, start) + before + selected + after + props.modelValue.substring(end)
    emit('update:modelValue', newVal)
}
</script>
<template>
  <div class="rounded-lg overflow-hidden" style="border:1px solid var(--border)">
    <!-- Toolbar -->
    <div class="flex items-center gap-1 px-3 py-2" style="background:var(--surface-raised);border-bottom:1px solid var(--border)">
      <button type="button" @click="insert('**','**')" class="px-2 py-1 rounded text-xs font-bold hover:bg-white/10" style="color:var(--text-muted)">B</button>
      <button type="button" @click="insert('*','*')" class="px-2 py-1 rounded text-xs italic hover:bg-white/10" style="color:var(--text-muted)">I</button>
      <button type="button" @click="insert('`','`')" class="px-2 py-1 rounded text-xs font-mono hover:bg-white/10" style="color:var(--text-muted)">`</button>
      <button type="button" @click="insert('\n```\n','\n```')" class="px-2 py-1 rounded text-xs font-mono hover:bg-white/10" style="color:var(--text-muted)">{ }</button>
      <button type="button" @click="insert('[','](url)')" class="px-2 py-1 rounded text-xs hover:bg-white/10" style="color:var(--text-muted)">🔗</button>
      <div class="ml-auto flex gap-1">
        <button type="button" @click="tab = 'write'"
                class="px-3 py-1 rounded text-xs transition-colors"
                :style="tab === 'write' ? 'background:var(--accent);color:white' : 'color:var(--text-muted)'">Write</button>
        <button type="button" @click="tab = 'preview'"
                class="px-3 py-1 rounded text-xs transition-colors"
                :style="tab === 'preview' ? 'background:var(--accent);color:white' : 'color:var(--text-muted)'">Preview</button>
      </div>
    </div>
    <!-- Editor / Preview -->
    <textarea v-if="tab === 'write'" id="md-editor"
              :value="modelValue" @input="emit('update:modelValue', $event.target.value)"
              :placeholder="placeholder" rows="10"
              class="w-full px-4 py-3 text-sm resize-y outline-none font-mono"
              style="background:var(--surface);color:var(--text);min-height:160px" />
    <div v-else class="px-4 py-3 prose prose-invert max-w-none text-sm min-h-40"
         style="background:var(--surface);color:var(--text)"
         v-html="preview || '<span style=\'opacity:0.4\'>Nothing to preview</span>'" />
  </div>
</template>
```

- [ ] **Step 6: Create `resources/js/Components/Post/PostCard.vue`**

```vue
<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import MarkdownEditor from './MarkdownEditor.vue'
import { marked } from 'marked'
import hljs from 'highlight.js'

marked.setOptions({ highlight: (code, lang) => { const l = hljs.getLanguage(lang) ? lang : 'plaintext'; return hljs.highlight(code, { language: l }).value }, langPrefix: 'hljs language-' })

const props = defineProps({ post: Object, isFirst: Boolean })
const auth = computed(() => usePage().props.auth)
const editing = ref(false)
const editForm = useForm({ body: props.post.body })
const renderedBody = computed(() => marked.parse(props.post.body || ''))
function saveEdit() { editForm.put(route('post.update', props.post.id), { onSuccess: () => editing.value = false }) }
</script>
<template>
  <div class="flex gap-4" :style="!isFirst ? 'border-top:1px solid var(--border);padding-top:1.5rem;margin-top:1.5rem' : ''">
    <!-- User column -->
    <div class="w-28 shrink-0 text-center hidden sm:block">
      <img v-if="post.user?.avatar" :src="post.user.avatar" class="w-14 h-14 rounded-full mx-auto mb-2 object-cover" />
      <div v-else class="w-14 h-14 rounded-full mx-auto mb-2 flex items-center justify-center text-lg font-bold text-white"
           :style="`background:${post.user?.group?.color || 'var(--accent)'}`">
        {{ post.user?.username?.[0]?.toUpperCase() }}
      </div>
      <div class="text-xs font-semibold" :style="`color:${post.user?.group?.color || 'var(--text)'}`">
        {{ post.user?.username }}
      </div>
      <div class="text-xs mt-0.5" style="color:var(--text-faint)">{{ post.user?.group?.name }}</div>
      <div class="text-xs mt-1" style="color:var(--text-faint)">Posts: {{ post.user?.post_count }}</div>
      <div v-if="post.user?.credits" class="mt-1 text-xs px-2 py-0.5 rounded-full inline-block text-white"
           style="background:var(--accent-gradient)">
        ⭐ {{ post.user.credits }}cr
      </div>
    </div>
    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs" style="color:var(--text-faint)">{{ post.created_at }}</span>
        <span v-if="post.edited_at" class="text-xs italic" style="color:var(--text-faint)">(edited)</span>
        <div class="ml-auto flex gap-2" v-if="auth.user?.id === post.user_id || auth.user?.is_moderator">
          <button @click="editing = !editing" class="text-xs hover:underline" style="color:var(--text-faint)">Edit</button>
          <Link :href="route('post.destroy', post.id)" method="delete" as="button"
                class="text-xs hover:underline" style="color:var(--danger)">Delete</Link>
        </div>
      </div>
      <div v-if="!editing" class="prose prose-invert max-w-none text-sm leading-relaxed"
           style="color:var(--text)" v-html="renderedBody" />
      <div v-else class="space-y-3">
        <MarkdownEditor v-model="editForm.body" />
        <div class="flex gap-2">
          <button @click="saveEdit" class="px-4 py-1.5 rounded text-sm text-white" style="background:var(--accent)">Save</button>
          <button @click="editing = false" class="px-4 py-1.5 rounded text-sm" style="color:var(--text-muted)">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 7: Create `resources/js/Pages/Thread/Show.vue`**

```vue
<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import PostCard from '@/Components/Post/PostCard.vue'
import MarkdownEditor from '@/Components/Post/MarkdownEditor.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({ thread: Object, posts: Object })
const auth = computed(() => usePage().props.auth)
const replyForm = useForm({ body: '' })
function submitReply() {
    replyForm.post(route('post.store', props.thread.slug), { onSuccess: () => replyForm.reset() })
}
</script>
<template>
  <AppLayout>
    <Head :title="thread.title" />
    <!-- Breadcrumb -->
    <div class="text-xs mb-4" style="color:var(--text-faint)">
      <Link :href="route('forum.index')" style="color:var(--accent)">Forums</Link>
      → <Link :href="route('forum.show', thread.forum_id)" style="color:var(--accent)">{{ thread.forum?.name }}</Link>
      → {{ thread.title }}
    </div>

    <!-- Thread title -->
    <div class="mb-6">
      <div class="flex items-center gap-2 mb-1">
        <span v-if="thread.is_pinned" class="text-xs px-2 py-0.5 rounded text-white" style="background:var(--accent)">📌 Pinned</span>
        <span v-if="thread.is_locked" class="text-xs px-2 py-0.5 rounded" style="background:var(--surface-raised);color:var(--text-muted)">🔒 Locked</span>
      </div>
      <h1 class="text-xl font-bold" style="color:var(--text)">{{ thread.title }}</h1>
    </div>

    <!-- Posts -->
    <div class="rounded-xl px-6 py-5" style="background:var(--surface);border:1px solid var(--border)">
      <PostCard v-for="(post, i) in posts.data" :key="post.id" :post="post" :isFirst="i === 0" />
    </div>

    <Pagination :links="posts.links" />

    <!-- Reply form -->
    <div v-if="auth.user && !thread.is_locked" class="mt-8">
      <h3 class="text-sm font-semibold mb-3" style="color:var(--text)">Post a reply</h3>
      <form @submit.prevent="submitReply" class="space-y-3">
        <MarkdownEditor v-model="replyForm.body" />
        <p v-if="replyForm.errors.body" class="text-red-400 text-xs">{{ replyForm.errors.body }}</p>
        <button type="submit" :disabled="replyForm.processing"
                class="px-5 py-2 rounded-lg text-sm text-white font-medium"
                style="background:var(--accent-gradient)">
          Post Reply
        </button>
      </form>
    </div>
    <div v-else-if="!auth.user" class="mt-8 text-sm" style="color:var(--text-muted)">
      <Link :href="route('login')" style="color:var(--accent)">Log in</Link> to reply.
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 8: Create `resources/js/Pages/Thread/Create.vue`**

```vue
<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import MarkdownEditor from '@/Components/Post/MarkdownEditor.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ forum: Object })
const form = useForm({ title: '', body: '' })
function submit() { form.post(route('thread.store', props.forum.id)) }
</script>
<template>
  <AppLayout>
    <Head title="New Thread" />
    <div class="max-w-3xl">
      <h1 class="text-xl font-bold mb-6" style="color:var(--text)">New Thread in {{ forum.name }}</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Title</label>
          <input v-model="form.title" type="text" required maxlength="200"
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Body</label>
          <MarkdownEditor v-model="form.body" />
          <p v-if="form.errors.body" class="text-red-400 text-xs mt-1">{{ form.errors.body }}</p>
        </div>
        <button type="submit" :disabled="form.processing"
                class="px-6 py-2 rounded-lg text-sm text-white font-medium"
                style="background:var(--accent-gradient)">
          Post Thread
        </button>
      </form>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 9: Run tests**

```bash
php artisan test tests/Feature/Forum/ThreadTest.php
```
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(threads): thread view, create thread, post reply, markdown editor with syntax highlighting"
```

---

## Task 8: Middleware — IsAdmin, IsModerator

**Files:**
- Create: `app/Http/Middleware/IsAdmin.php`
- Create: `app/Http/Middleware/IsModerator.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Admin/AdminAccessTest.php
public function test_guest_cannot_access_admin(): void
{
    $this->get('/admin')->assertRedirect('/login');
}

public function test_regular_user_cannot_access_admin(): void
{
    $group = Group::factory()->create(['permissions' => ['is_admin' => false, 'is_moderator' => false]]);
    $user = User::factory()->create(['group_id' => $group->id]);
    $this->actingAs($user)->get('/admin')->assertForbidden();
}

public function test_admin_user_can_access_admin(): void
{
    $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
    $user = User::factory()->create(['group_id' => $group->id]);
    $this->actingAs($user)->get('/admin')->assertOk();
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Admin/AdminAccessTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `IsAdmin.php`**

```php
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403);
        }
        return $next($request);
    }
}
```

- [ ] **Step 4: Create `IsModerator.php`**

```php
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class IsModerator
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || (!$request->user()->isModerator() && !$request->user()->isAdmin())) {
            abort(403);
        }
        return $next($request);
    }
}
```

- [ ] **Step 5: Register aliases in `bootstrap/app.php`**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\IsAdmin::class,
        'moderator' => \App\Http\Middleware\IsModerator::class,
    ]);
    // ...existing web middleware
})
```

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Admin/AdminAccessTest.php
```
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(middleware): IsAdmin and IsModerator guards"
```

---

## Task 9: Admin panel — scaffolding + dashboard

**Files:**
- Create: `app/Admin/Controllers/DashboardController.php`
- Create: `resources/js/Components/Layout/AdminLayout.vue`
- Create: `resources/js/Pages/Admin/Dashboard.vue`
- Create: `routes/admin.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Admin/DashboardTest.php
public function test_admin_dashboard_shows_stats(): void
{
    $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
    $admin = User::factory()->create(['group_id' => $group->id]);
    Thread::factory()->count(5)->create();

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Dashboard')
        ->has('stats')
    );
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Admin/DashboardTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `DashboardController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Forum\Models\{Category, Forum, Thread, Post};
use App\Models\User;
use App\Moderation\Models\Report;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users'    => User::count(),
                'threads'  => Thread::where('is_deleted', false)->count(),
                'posts'    => Post::where('is_deleted', false)->count(),
                'forums'   => Forum::count(),
                'reports'  => Report::where('status', 'open')->count(),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Create `routes/admin.php`**

```php
<?php
use App\Admin\Controllers\{
    DashboardController, CategoryController, ForumController,
    ThreadController, PostController, UserController,
    GroupController, SettingController
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('forums', ForumController::class)->except(['show']);
    Route::post('forums/reorder', [ForumController::class, 'reorder'])->name('forums.reorder');
    Route::resource('threads', ThreadController::class)->only(['index', 'destroy']);
    Route::resource('posts', PostController::class)->only(['index', 'update', 'destroy']);
    Route::resource('users', UserController::class)->except(['create', 'store']);
    Route::post('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
    Route::post('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
    Route::resource('groups', GroupController::class)->except(['show']);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
});
```

Include in `routes/web.php`:
```php
require __DIR__.'/admin.php';
```

- [ ] **Step 5: Create `resources/js/Components/Layout/AdminLayout.vue`**

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const navItems = [
    { label: 'Dashboard', route: 'admin.dashboard', icon: '📊' },
    { label: 'Categories', route: 'admin.categories.index', icon: '📂' },
    { label: 'Forums', route: 'admin.forums.index', icon: '💬' },
    { label: 'Threads', route: 'admin.threads.index', icon: '📋' },
    { label: 'Posts', route: 'admin.posts.index', icon: '✍️' },
    { label: 'Users', route: 'admin.users.index', icon: '👥' },
    { label: 'Groups', route: 'admin.groups.index', icon: '🏷️' },
    { label: 'Settings', route: 'admin.settings.index', icon: '⚙️' },
]
</script>
<template>
  <div class="min-h-screen flex" style="background:var(--bg)">
    <!-- Sidebar -->
    <aside class="w-52 shrink-0 flex flex-col" style="background:var(--surface);border-right:1px solid var(--border)">
      <div class="h-14 flex items-center px-4" style="border-bottom:1px solid var(--border)">
        <Link :href="route('admin.dashboard')" class="font-bold text-sm" style="color:var(--text)">⚙️ Admin Panel</Link>
      </div>
      <nav class="flex-1 p-2 space-y-0.5">
        <Link v-for="item in navItems" :key="item.route"
              :href="route(item.route)"
              class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors"
              :style="route().current(item.route)
                ? 'background:var(--accent);color:white'
                : 'color:var(--text-muted)'">
          <span>{{ item.icon }}</span>{{ item.label }}
        </Link>
      </nav>
      <div class="p-3 border-t" style="border-color:var(--border)">
        <Link :href="route('forum.index')" class="text-xs" style="color:var(--text-faint)">← Back to forum</Link>
      </div>
    </aside>
    <!-- Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 flex items-center px-6" style="border-bottom:1px solid var(--border);background:var(--surface)">
        <slot name="header" />
      </header>
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
```

- [ ] **Step 6: Create `resources/js/Pages/Admin/Dashboard.vue`**

```vue
<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
defineProps({ stats: Object })
const cards = [
    { label: 'Users', key: 'users', icon: '👥' },
    { label: 'Threads', key: 'threads', icon: '📋' },
    { label: 'Posts', key: 'posts', icon: '✍️' },
    { label: 'Open Reports', key: 'reports', icon: '🚩' },
]
</script>
<template>
  <AdminLayout>
    <template #header><h1 class="font-semibold text-sm" style="color:var(--text)">Dashboard</h1></template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in cards" :key="card.key"
           class="rounded-xl p-5" style="background:var(--surface);border:1px solid var(--border)">
        <div class="text-2xl mb-2">{{ card.icon }}</div>
        <div class="text-2xl font-bold" style="color:var(--text)">{{ stats[card.key] }}</div>
        <div class="text-xs mt-1" style="color:var(--text-muted)">{{ card.label }}</div>
      </div>
    </div>
  </AdminLayout>
</template>
```

- [ ] **Step 7: Run test**

```bash
php artisan test tests/Feature/Admin/DashboardTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(admin): panel scaffolding, sidebar layout, dashboard stats"
```

---

## Task 10: Admin — categories + forums CRUD + reorder

**Files:**
- Create: `app/Admin/Controllers/CategoryController.php`
- Create: `app/Admin/Controllers/ForumController.php`
- Create: `resources/js/Pages/Admin/Categories/Index.vue`
- Create: `resources/js/Pages/Admin/Forums/Index.vue`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Admin/CategoryAdminTest.php
public function test_admin_can_create_category(): void
{
    $admin = $this->makeAdmin();
    $response = $this->actingAs($admin)->post('/admin/categories', [
        'name' => 'Development', 'description' => 'Dev stuff',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('categories', ['name' => 'Development']);
}

public function test_admin_can_reorder_categories(): void
{
    $admin = $this->makeAdmin();
    $cats = Category::factory()->count(3)->create();
    $order = $cats->pluck('id')->reverse()->values()->toArray();

    $this->actingAs($admin)->post('/admin/categories/reorder', ['order' => $order])
         ->assertOk();
    $this->assertEquals(0, Category::find($order[0])->display_order);
}

// Helper in TestCase:
protected function makeAdmin(): User
{
    $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
    return User::factory()->create(['group_id' => $group->id]);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Admin/CategoryAdminTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `app/Admin/Controllers/CategoryController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Forum\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::orderBy('display_order')->withCount('forums')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);
        Category::create($data + ['display_order' => Category::max('display_order') + 1]);
        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);
        $category->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        foreach ($data['order'] as $i => $id) {
            Category::where('id', $id)->update(['display_order' => $i]);
        }
        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 4: Create `app/Admin/Controllers/ForumController.php`**

Same pattern as CategoryController but for forums. Includes `category_id` in validate. Reorder scoped to category.

```php
<?php
namespace App\Admin\Controllers;

use App\Forum\Models\{Category, Forum};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ForumController
{
    public function index()
    {
        return Inertia::render('Admin/Forums/Index', [
            'forums' => Forum::with('category')->orderBy('display_order')->withCount('threads')->get(),
            'categories' => Category::orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);
        Forum::create($data + ['display_order' => Forum::max('display_order') + 1]);
        return back()->with('success', 'Forum created.');
    }

    public function update(Request $request, Forum $forum)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);
        $forum->update($data);
        return back()->with('success', 'Forum updated.');
    }

    public function destroy(Forum $forum)
    {
        $forum->delete();
        return back()->with('success', 'Forum deleted.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        foreach ($data['order'] as $i => $id) {
            Forum::where('id', $id)->update(['display_order' => $i]);
        }
        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 5: Create `resources/js/Pages/Admin/Categories/Index.vue`**

```vue
<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({ categories: Array })
const form = useForm({ name: '', description: '' })
const editingId = ref(null)
const editForm = useForm({ name: '', description: '' })

function startEdit(cat) {
    editingId.value = cat.id
    editForm.name = cat.name
    editForm.description = cat.description || ''
}
</script>
<template>
  <AdminLayout>
    <template #header><h1 class="font-semibold text-sm" style="color:var(--text)">Categories</h1></template>

    <!-- Create form -->
    <div class="rounded-xl p-5 mb-6" style="background:var(--surface);border:1px solid var(--border)">
      <h2 class="text-sm font-semibold mb-4" style="color:var(--text)">New Category</h2>
      <form @submit.prevent="form.post(route('admin.categories.store'), { onSuccess: () => form.reset() })"
            class="flex gap-3">
        <input v-model="form.name" placeholder="Name" required
               class="flex-1 px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
        <input v-model="form.description" placeholder="Description (optional)"
               class="flex-1 px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
        <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white"
                style="background:var(--accent-gradient)">Add</button>
      </form>
    </div>

    <!-- List -->
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-for="(cat, i) in categories" :key="cat.id"
           class="flex items-center gap-4 px-5 py-3"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
        <span class="text-xs cursor-move" style="color:var(--text-faint)">⠿</span>
        <div class="flex-1">
          <template v-if="editingId === cat.id">
            <div class="flex gap-2">
              <input v-model="editForm.name" class="px-2 py-1 rounded text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <button @click="editForm.put(route('admin.categories.update', cat.id), { onSuccess: () => editingId = null })"
                      class="px-3 py-1 rounded text-xs text-white" style="background:var(--accent)">Save</button>
              <button @click="editingId = null" class="px-3 py-1 rounded text-xs" style="color:var(--text-muted)">Cancel</button>
            </div>
          </template>
          <template v-else>
            <span class="text-sm font-medium" style="color:var(--text)">{{ cat.name }}</span>
            <span class="ml-2 text-xs" style="color:var(--text-faint)">{{ cat.forums_count }} forums</span>
          </template>
        </div>
        <div class="flex gap-2" v-if="editingId !== cat.id">
          <button @click="startEdit(cat)" class="text-xs" style="color:var(--text-muted)">Edit</button>
          <Link :href="route('admin.categories.destroy', cat.id)" method="delete" as="button"
                class="text-xs" style="color:var(--danger)">Delete</Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
```

- [ ] **Step 6: Create `resources/js/Pages/Admin/Forums/Index.vue`**

Same pattern as Categories/Index.vue, with additional `category_id` select and `icon` input fields.

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Feature/Admin/CategoryAdminTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(admin): categories and forums CRUD with display order reordering"
```

---

## Task 11: Admin — users + groups

**Files:**
- Create: `app/Admin/Controllers/UserController.php`
- Create: `app/Admin/Controllers/GroupController.php`
- Create: `resources/js/Pages/Admin/Users/Index.vue`
- Create: `resources/js/Pages/Admin/Groups/Index.vue`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Admin/UserAdminTest.php
public function test_admin_can_ban_user(): void
{
    $admin = $this->makeAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin)->post("/admin/users/{$user->id}/ban", ['reason' => 'Spam'])
         ->assertRedirect();
    $this->assertNotNull($user->fresh()->banned_at);
}

public function test_admin_can_change_user_group(): void
{
    $admin = $this->makeAdmin();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $this->actingAs($admin)->put("/admin/users/{$user->id}", ['group_id' => $group->id])
         ->assertRedirect();
    $this->assertEquals($group->id, $user->fresh()->group_id);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Admin/UserAdminTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `app/Admin/Controllers/UserController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Models\{User, Group};
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController
{
    public function index(Request $request)
    {
        $users = User::with('group')
            ->when($request->search, fn($q, $s) => $q->where('username', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'groups' => Group::orderBy('display_order')->get(),
            'filters' => $request->only('search'),
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load('group'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'group_id'   => ['nullable', 'exists:groups,id'],
            'is_trusted' => ['boolean'],
        ]);
        $user->update($data);
        return back()->with('success', 'User updated.');
    }

    public function ban(Request $request, User $user)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $user->update(['banned_at' => now(), 'banned_reason' => $data['reason']]);
        return back()->with('success', 'User banned.');
    }

    public function unban(User $user)
    {
        $user->update(['banned_at' => null, 'banned_reason' => null]);
        return back()->with('success', 'User unbanned.');
    }
}
```

- [ ] **Step 4: Create `app/Admin/Controllers/GroupController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController
{
    public function index()
    {
        return Inertia::render('Admin/Groups/Index', [
            'groups' => Group::withCount('users')->orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'color'       => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'is_staff'    => ['boolean'],
            'permissions' => ['required', 'array'],
        ]);
        Group::create($data + ['display_order' => Group::max('display_order') + 1]);
        return back()->with('success', 'Group created.');
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'color'       => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'is_staff'    => ['boolean'],
            'permissions' => ['required', 'array'],
        ]);
        $group->update($data);
        return back()->with('success', 'Group updated.');
    }

    public function destroy(Group $group)
    {
        $group->update(['group_id' => null]); // unset users from this group
        $group->delete();
        return back()->with('success', 'Group deleted.');
    }
}
```

- [ ] **Step 5: Create `resources/js/Pages/Admin/Users/Index.vue`**

Paginated user table with columns: username, email, group (editable dropdown), is_trusted toggle, banned status, ban/unban button. Include search input that updates URL via Inertia router.

```vue
<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ users: Object, groups: Array, filters: Object })
const search = ref(props.filters?.search || '')
watch(search, (val) => router.get(route('admin.users.index'), { search: val }, { preserveState: true, replace: true }))

function updateGroup(user, groupId) {
    useForm({ group_id: groupId }).put(route('admin.users.update', user.id))
}
</script>
<template>
  <AdminLayout>
    <template #header><h1 class="font-semibold text-sm" style="color:var(--text)">Users</h1></template>
    <div class="mb-4">
      <input v-model="search" placeholder="Search username or email..."
             class="w-full max-w-xs px-3 py-2 rounded-lg text-sm outline-none"
             style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
    </div>
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <table class="w-full text-sm">
        <thead style="background:var(--surface-raised)">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">User</th>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">Group</th>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(user, i) in users.data" :key="user.id"
              :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
            <td class="px-4 py-3">
              <div style="color:var(--text)" class="font-medium">{{ user.username }}</div>
              <div style="color:var(--text-faint)" class="text-xs">{{ user.email }}</div>
            </td>
            <td class="px-4 py-3">
              <select @change="updateGroup(user, $event.target.value)"
                      :value="user.group_id"
                      class="px-2 py-1 rounded text-xs outline-none"
                      style="background:var(--bg);border:1px solid var(--border);color:var(--text)">
                <option value="">None</option>
                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
            </td>
            <td class="px-4 py-3">
              <span v-if="user.banned_at" class="text-xs px-2 py-0.5 rounded" style="background:#ef4444;color:white">Banned</span>
              <span v-else class="text-xs" style="color:var(--text-faint)">Active</span>
            </td>
            <td class="px-4 py-3 text-right">
              <Link v-if="!user.banned_at" :href="route('admin.users.ban', user.id)" method="post" as="button"
                    class="text-xs" style="color:var(--danger)">Ban</Link>
              <Link v-else :href="route('admin.users.unban', user.id)" method="post" as="button"
                    class="text-xs" style="color:var(--success)">Unban</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :links="users.links" />
  </AdminLayout>
</template>
```

- [ ] **Step 6: Create `resources/js/Pages/Admin/Groups/Index.vue`**

Form to create/edit groups with fields: name, color (color picker), icon (emoji), is_staff toggle, permissions checkboxes (can_post, can_create_thread, can_upload_avatar, can_use_signature, can_react, is_moderator, is_admin).

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Feature/Admin/UserAdminTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(admin): users management (ban/unban/group assignment) + groups CRUD"
```

---

## Task 12: Admin — threads, posts, settings

**Files:**
- Create: `app/Admin/Controllers/ThreadController.php`
- Create: `app/Admin/Controllers/PostController.php`
- Create: `app/Admin/Controllers/SettingController.php`
- Create: `resources/js/Pages/Admin/Threads/Index.vue`
- Create: `resources/js/Pages/Admin/Posts/Index.vue`
- Create: `resources/js/Pages/Admin/Settings/Index.vue`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Admin/SettingsTest.php
public function test_admin_can_update_site_settings(): void
{
    $admin = $this->makeAdmin();
    $this->actingAs($admin)->post('/admin/settings', [
        'site_name' => 'My Forum',
        'site_tagline' => 'Tech discussions',
    ])->assertRedirect();

    $this->assertEquals('My Forum', \App\Settings\Models\Setting::get('site_name'));
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Admin/SettingsTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `app/Admin/Controllers/ThreadController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Forum\Models\Thread;
use Inertia\Inertia;

class ThreadController
{
    public function index()
    {
        return Inertia::render('Admin/Threads/Index', [
            'threads' => Thread::with(['user', 'forum'])
                ->where('is_deleted', false)
                ->latest()->paginate(25),
        ]);
    }

    public function destroy(Thread $thread)
    {
        $thread->update(['is_deleted' => true]);
        return back()->with('success', 'Thread deleted.');
    }
}
```

- [ ] **Step 4: Create `app/Admin/Controllers/PostController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Forum\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController
{
    public function index()
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::with(['user', 'thread'])
                ->where('is_deleted', false)
                ->latest()->paginate(25),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate(['body' => ['required', 'string']]);
        $post->update(['body' => $data['body'], 'edited_at' => now(), 'edited_by_id' => $request->user()->id]);
        return back()->with('success', 'Post updated.');
    }

    public function destroy(Post $post)
    {
        $post->update(['is_deleted' => true]);
        return back()->with('success', 'Post deleted.');
    }
}
```

- [ ] **Step 5: Create `app/Admin/Controllers/SettingController.php`**

```php
<?php
namespace App\Admin\Controllers;

use App\Settings\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'site_name'    => Setting::get('site_name', 'VoltexaHub'),
                'site_tagline' => Setting::get('site_tagline', ''),
                'site_logo'    => Setting::get('site_logo', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name'    => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'site_logo'    => ['nullable', 'string', 'max:500'],
        ]);
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }
        return back()->with('success', 'Settings saved.');
    }
}
```

- [ ] **Step 6: Create Vue pages for threads, posts, settings**

`Admin/Threads/Index.vue` — table with thread title, forum, author, reply count. Delete button per row. Pagination.

`Admin/Posts/Index.vue` — table with post excerpt, thread title, author, date. Edit (inline form) and delete buttons.

`Admin/Settings/Index.vue` — form with site name, tagline, logo URL inputs. Save button.

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Feature/Admin/SettingsTest.php
```
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(admin): thread/post management + site settings"
```

---

## Task 13: Mod tools + reports

**Files:**
- Create: `app/Moderation/Controllers/ModController.php`
- Create: `app/Moderation/Controllers/ReportController.php`
- Create: `resources/js/Pages/Admin/Reports/Index.vue`
- Modify: `routes/admin.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Moderation/ModToolsTest.php
public function test_moderator_can_lock_thread(): void
{
    $group = Group::factory()->create(['permissions' => ['is_moderator' => true, 'is_admin' => false]]);
    $mod = User::factory()->create(['group_id' => $group->id]);
    $thread = Thread::factory()->create();

    $this->actingAs($mod)->post("/mod/thread/{$thread->id}/lock")
         ->assertRedirect();
    $this->assertTrue($thread->fresh()->is_locked);
}

public function test_user_can_report_post(): void
{
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->post('/report', [
        'reportable_type' => 'post',
        'reportable_id' => $post->id,
        'reason' => 'Spam content',
    ])->assertRedirect();
    $this->assertDatabaseHas('reports', ['reportable_id' => $post->id, 'status' => 'open']);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Moderation/ModToolsTest.php
```
Expected: FAIL

- [ ] **Step 3: Create `app/Moderation/Controllers/ModController.php`**

```php
<?php
namespace App\Moderation\Controllers;

use App\Forum\Models\{Thread, Post, Forum};
use App\Moderation\Models\ModLog;
use Illuminate\Http\Request;

class ModController
{
    public function lockThread(Request $request, Thread $thread)
    {
        $thread->update(['is_locked' => !$thread->is_locked]);
        ModLog::create([
            'moderator_id' => $request->user()->id,
            'action' => $thread->is_locked ? 'lock_thread' : 'unlock_thread',
            'target_type' => 'thread', 'target_id' => $thread->id,
        ]);
        return back()->with('success', $thread->is_locked ? 'Thread locked.' : 'Thread unlocked.');
    }

    public function pinThread(Request $request, Thread $thread)
    {
        $thread->update(['is_pinned' => !$thread->is_pinned]);
        ModLog::create([
            'moderator_id' => $request->user()->id,
            'action' => $thread->is_pinned ? 'pin_thread' : 'unpin_thread',
            'target_type' => 'thread', 'target_id' => $thread->id,
        ]);
        return back()->with('success', $thread->is_pinned ? 'Thread pinned.' : 'Thread unpinned.');
    }

    public function moveThread(Request $request, Thread $thread)
    {
        $data = $request->validate(['forum_id' => ['required', 'exists:forums,id']]);
        $oldForum = $thread->forum;
        $thread->update(['forum_id' => $data['forum_id']]);
        ModLog::create([
            'moderator_id' => $request->user()->id,
            'action' => 'move_thread',
            'target_type' => 'thread', 'target_id' => $thread->id,
            'note' => "From forum {$oldForum->id} to {$data['forum_id']}",
        ]);
        return back()->with('success', 'Thread moved.');
    }

    public function deleteThread(Request $request, Thread $thread)
    {
        $thread->update(['is_deleted' => true]);
        ModLog::create([
            'moderator_id' => $request->user()->id,
            'action' => 'delete_thread',
            'target_type' => 'thread', 'target_id' => $thread->id,
        ]);
        return redirect()->route('forum.show', $thread->forum_id)->with('success', 'Thread deleted.');
    }
}
```

- [ ] **Step 4: Create `app/Moderation/Controllers/ReportController.php`**

```php
<?php
namespace App\Moderation\Controllers;

use App\Moderation\Models\{Report, ModLog};
use App\Forum\Models\{Post, Thread};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => ['required', 'in:post,thread'],
            'reportable_id'   => ['required', 'integer'],
            'reason'          => ['required', 'string', 'max:500'],
        ]);

        $modelClass = $data['reportable_type'] === 'post' ? Post::class : Thread::class;
        abort_unless($modelClass::find($data['reportable_id']), 404);

        Report::firstOrCreate(
            ['reporter_id' => $request->user()->id, 'reportable_type' => $modelClass, 'reportable_id' => $data['reportable_id']],
            ['reason' => $data['reason'], 'status' => 'open']
        );

        return back()->with('success', 'Report submitted.');
    }

    public function index()
    {
        return Inertia::render('Admin/Reports/Index', [
            'reports' => Report::with(['reporter', 'reportable', 'resolver'])
                ->where('status', 'open')
                ->latest()->paginate(25),
        ]);
    }

    public function resolve(Request $request, Report $report)
    {
        $report->update(['status' => 'resolved', 'resolved_by' => $request->user()->id]);
        ModLog::create([
            'moderator_id' => $request->user()->id,
            'action' => 'resolve_report',
            'target_type' => 'report', 'target_id' => $report->id,
        ]);
        return back()->with('success', 'Report resolved.');
    }

    public function dismiss(Request $request, Report $report)
    {
        $report->update(['status' => 'dismissed', 'resolved_by' => $request->user()->id]);
        return back()->with('success', 'Report dismissed.');
    }
}
```

- [ ] **Step 5: Add mod routes to `routes/web.php`**

```php
use App\Moderation\Controllers\{ModController, ReportController};

Route::middleware(['auth', 'moderator'])->prefix('mod')->name('mod.')->group(function () {
    Route::post('/thread/{thread}/lock', [ModController::class, 'lockThread'])->name('thread.lock');
    Route::post('/thread/{thread}/pin', [ModController::class, 'pinThread'])->name('thread.pin');
    Route::post('/thread/{thread}/move', [ModController::class, 'moveThread'])->name('thread.move');
    Route::delete('/thread/{thread}', [ModController::class, 'deleteThread'])->name('thread.delete');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');
});

// Add to admin routes in admin.php:
// Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
// Route::post('reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
// Route::post('reports/{report}/dismiss', [ReportController::class, 'dismiss'])->name('reports.dismiss');
```

- [ ] **Step 6: Create `resources/js/Pages/Admin/Reports/Index.vue`**

Table showing: reporter username, reportable type + link, reason, date. Resolve and Dismiss buttons per row.

- [ ] **Step 7: Add mod action buttons to `Thread/Show.vue`**

In the thread header, show a mod-tools dropdown for moderators/admins:
```vue
<div v-if="auth.user?.is_moderator || auth.user?.is_admin" class="flex gap-2 mt-2">
    <Link :href="route('mod.thread.lock', thread.id)" method="post" as="button"
          class="text-xs px-3 py-1 rounded" style="background:var(--surface-raised);color:var(--text-muted)">
      {{ thread.is_locked ? '🔓 Unlock' : '🔒 Lock' }}
    </Link>
    <Link :href="route('mod.thread.pin', thread.id)" method="post" as="button"
          class="text-xs px-3 py-1 rounded" style="background:var(--surface-raised);color:var(--text-muted)">
      {{ thread.is_pinned ? '📌 Unpin' : '📌 Pin' }}
    </Link>
</div>
```

- [ ] **Step 8: Run tests**

```bash
php artisan test tests/Feature/Moderation/
```
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(mod): lock/pin/move/delete threads, report system, mod log, admin report queue"
```

---

## Task 14: Deploy script

**Files:**
- Create: `deploy/setup.sh`

- [ ] **Step 1: Create `deploy/setup.sh`**

```bash
#!/usr/bin/env bash
set -euo pipefail

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info() { echo -e "${GREEN}[VoltexaHub]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

echo ""
echo "  VoltexaHub Setup"
echo "  ================================"
echo ""

# Collect inputs
read -rp "Domain (e.g. forum.example.com): " DOMAIN
read -rp "Admin username: " ADMIN_USERNAME
read -rp "Admin email: " ADMIN_EMAIL
read -rsp "Admin password: " ADMIN_PASSWORD; echo
read -rp "SMTP host: " SMTP_HOST
read -rp "SMTP port [587]: " SMTP_PORT; SMTP_PORT="${SMTP_PORT:-587}"
read -rp "SMTP username: " SMTP_USERNAME
read -rsp "SMTP password: " SMTP_PASSWORD; echo
read -rp "SMTP from address: " SMTP_FROM
DB_PASSWORD=$(openssl rand -base64 24)
APP_KEY=$(openssl rand -base64 32)

info "Installing system packages..."
apt-get update -qq
apt-get install -y -qq curl git unzip redis-server postgresql postgresql-client

info "Installing PHP 8.4..."
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
apt-get update -qq
apt-get install -y -qq php8.4 php8.4-fpm php8.4-pgsql php8.4-redis php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd

info "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

info "Installing Node.js 22..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1
apt-get install -y -qq nodejs

info "Installing Caddy..."
apt-get install -y -qq debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
apt-get update -qq && apt-get install -y -qq caddy

info "Setting up PostgreSQL..."
sudo -u postgres psql -c "CREATE USER voltexahub WITH PASSWORD '${DB_PASSWORD}';" 2>/dev/null || true
sudo -u postgres psql -c "CREATE DATABASE voltexahub OWNER voltexahub;" 2>/dev/null || true

info "Deploying application..."
mkdir -p /var/www/voltexahub
cd /var/www/voltexahub

# Copy app files (assumes script runs from repo root or files exist)
rsync -a --exclude='.git' --exclude='node_modules' --exclude='vendor' \
    "$(dirname "$0")/../" /var/www/voltexahub/

cat > /var/www/voltexahub/.env <<EOF
APP_NAME=VoltexaHub
APP_ENV=production
APP_KEY=base64:${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=voltexahub
DB_USERNAME=voltexahub
DB_PASSWORD=${DB_PASSWORD}

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=${SMTP_HOST}
MAIL_PORT=${SMTP_PORT}
MAIL_USERNAME=${SMTP_USERNAME}
MAIL_PASSWORD=${SMTP_PASSWORD}
MAIL_FROM_ADDRESS=${SMTP_FROM}
MAIL_FROM_NAME=VoltexaHub

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
EOF

composer install --no-dev --optimize-autoloader -q
npm ci --silent
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create admin user
php artisan tinker --execute="
\$group = \App\Models\Group::firstOrCreate(
    ['name' => 'Administrator'],
    ['color' => '#7c3aed', 'is_staff' => true, 'permissions' => json_encode(['is_admin' => true, 'can_post' => true, 'can_create_thread' => true, 'is_moderator' => true])]
);
\$user = \App\Models\User::create([
    'username' => '${ADMIN_USERNAME}',
    'email' => '${ADMIN_EMAIL}',
    'password' => bcrypt('${ADMIN_PASSWORD}'),
    'group_id' => \$group->id,
    'referral_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
    'email_verified_at' => now(),
]);
echo 'Admin created: ' . \$user->email;
"

chown -R www-data:www-data /var/www/voltexahub
chmod -R 755 /var/www/voltexahub/storage
chmod -R 755 /var/www/voltexahub/bootstrap/cache

info "Configuring Caddy..."
cat > /etc/caddy/Caddyfile <<EOF
${DOMAIN} {
    root * /var/www/voltexahub/public
    php_fastcgi unix//run/php/php8.4-fpm.sock
    file_server
    encode gzip
    log {
        output file /var/log/caddy/voltexahub.log
    }
}
EOF
systemctl reload caddy

info "Setting up queue worker..."
cat > /etc/systemd/system/voltexahub-worker.service <<EOF
[Unit]
Description=VoltexaHub Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/voltexahub
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable voltexahub-worker
systemctl start voltexahub-worker

echo ""
info "Setup complete!"
echo ""
echo "  Your forum is live at: https://${DOMAIN}"
echo "  Admin panel:           https://${DOMAIN}/admin"
echo "  Admin login:           ${ADMIN_EMAIL}"
echo ""
warn "Add your Cloudflare Turnstile keys to /var/www/voltexahub/.env"
echo ""
```

- [ ] **Step 2: Make executable and commit**

```bash
chmod +x deploy/setup.sh
git add deploy/setup.sh
git commit -m "feat(deploy): interactive VPS setup script for Ubuntu 24.04 + Caddy"
```

---

## Task 15: Final wiring + seeder

**Files:**
- Create: `database/seeders/DefaultGroupsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create default groups seeder**

```php
<?php
namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class DefaultGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Administrator', 'color' => '#7c3aed', 'is_staff' => true, 'display_order' => 0,
             'permissions' => ['is_admin' => true, 'is_moderator' => true, 'can_post' => true, 'can_create_thread' => true, 'can_upload_avatar' => true, 'can_use_signature' => true, 'can_react' => true]],
            ['name' => 'Moderator', 'color' => '#2563eb', 'is_staff' => true, 'display_order' => 1,
             'permissions' => ['is_admin' => false, 'is_moderator' => true, 'can_post' => true, 'can_create_thread' => true, 'can_upload_avatar' => true, 'can_use_signature' => true, 'can_react' => true]],
            ['name' => 'Member', 'color' => '#94a3b8', 'is_staff' => false, 'display_order' => 2,
             'permissions' => ['is_admin' => false, 'is_moderator' => false, 'can_post' => true, 'can_create_thread' => true, 'can_upload_avatar' => true, 'can_use_signature' => false, 'can_react' => true]],
        ];

        foreach ($groups as $group) {
            Group::firstOrCreate(['name' => $group['name']], $group);
        }
    }
}
```

- [ ] **Step 2: Update `DatabaseSeeder.php`**

```php
public function run(): void
{
    $this->call(DefaultGroupsSeeder::class);
}
```

- [ ] **Step 3: Run seeder**

```bash
php artisan db:seed
```

- [ ] **Step 4: Run full test suite**

```bash
php artisan test
```
Expected: all tests PASS.

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat(seed): default Administrator, Moderator, Member groups"
```

---

## Task 16: Push to GitHub

- [ ] **Step 1: Set remote and push**

```bash
git remote add origin https://github.com/joogiebear/voltexahub.git
git push -u origin main
```

Expected: all commits pushed. Repo at https://github.com/joogiebear/voltexahub shows the full codebase.

