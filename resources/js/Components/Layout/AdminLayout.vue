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
      <div class="p-3" style="border-top:1px solid var(--border)">
        <Link :href="route('forum.index')" class="text-xs" style="color:var(--text-faint)">← Back to forum</Link>
      </div>
    </aside>
    <!-- Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 flex items-center px-6"
              style="border-bottom:1px solid var(--border);background:var(--surface)">
        <slot name="header" />
      </header>
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
