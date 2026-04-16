<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
const admin = computed(() => page.props.admin || {});

const nav = [
    { label: 'Overview',   route: 'admin.dashboard' },
    { label: 'Categories', route: 'admin.categories.index' },
    { label: 'Forums',     route: 'admin.forums.index' },
    { label: 'Threads',    route: 'admin.threads.index' },
    { label: 'Polls',      route: 'admin.polls.index' },
    { label: 'Posts',      route: 'admin.posts.index' },
    { label: 'Reports',    route: 'admin.reports.index', badgeKey: 'pending_reports' },
    { label: 'Users',      route: 'admin.users.index' },
    { label: 'Plugins',    route: 'admin.plugins.index' },
    { label: 'Activity',   route: 'admin.activity.index' },
    { label: 'Settings',   route: 'admin.settings.index' },
];

const isActive = (name) => page.url.startsWith('/admin') && route().current(name.replace('.index', '.*'));

// Prevent FOUC: mirror the theme logic
onMounted(() => {
    try {
        const m = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (m === 'dark' || (m !== 'light' && prefersDark)) document.documentElement.classList.add('dark');
    } catch (e) {}
});

const toggleTheme = () => {
    const root = document.documentElement;
    const next = root.classList.contains('dark') ? 'light' : 'dark';
    root.classList.toggle('dark', next === 'dark');
    try { localStorage.setItem('theme', next); } catch (e) {}
};
</script>

<template>
    <div class="min-h-screen bg-[color:var(--bg)] text-[color:var(--text)] font-sans">
        <header class="border-b" style="border-color: var(--border);">
            <div class="max-w-7xl mx-auto px-6 h-16 flex items-center gap-6">
                <Link :href="route('admin.dashboard')" class="font-serif font-semibold text-[1.25rem] leading-none tracking-tight shrink-0" style="font-family: 'Fraunces', Georgia, serif;">
                    VoltexaHub
                    <span style="color: var(--accent)">·</span>
                    <span class="text-[0.7rem] font-mono uppercase tracking-widest align-middle ml-1" style="color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">Admin</span>
                </Link>

                <nav class="flex gap-5 text-sm ml-4">
                    <Link
                        v-for="item in nav"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            'relative flex items-center gap-1.5 transition-colors',
                            isActive(item.route) ? 'font-medium' : 'hover:text-[color:var(--text)]',
                        ]"
                        :style="{ color: isActive(item.route) ? 'var(--text)' : 'var(--text-muted)' }"
                    >
                        {{ item.label }}
                        <span
                            v-if="item.badgeKey && admin[item.badgeKey] > 0"
                            class="inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 text-[10px] font-mono font-medium rounded-full text-white"
                            :style="{ background: 'var(--accent)' }"
                        >{{ admin[item.badgeKey] }}</span>
                        <span
                            v-if="isActive(item.route)"
                            class="absolute -bottom-[17px] left-0 right-0 h-[2px]"
                            :style="{ background: 'var(--accent)' }"
                        ></span>
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-4 text-sm" style="color: var(--text-muted)">
                    <Link :href="route('home')" class="hover:text-[color:var(--text)]" :style="{ color: 'inherit' }">View Site</Link>
                    <span>·</span>
                    <span>{{ page.props.auth.user?.name }}</span>
                    <Link :href="route('logout')" method="post" as="button" class="hover:text-[color:var(--text)]">Log out</Link>
                    <button type="button" @click="toggleTheme" aria-label="Toggle dark mode"
                            class="p-1.5 rounded-md hover:bg-[color:var(--surface-mute)] transition">
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.364-6.364l-1.06 1.06M6.697 17.303l-1.06 1.06m0-13.728l1.06 1.06M17.303 17.303l1.06 1.06M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <div v-if="flash.success" class="vx-flash vx-flash-success">
            <div class="max-w-7xl mx-auto px-6">{{ flash.success }}</div>
        </div>
        <div v-if="flash.error" class="vx-flash vx-flash-error">
            <div class="max-w-7xl mx-auto px-6">{{ flash.error }}</div>
        </div>

        <main class="max-w-7xl mx-auto px-6 py-10">
            <slot />
        </main>
    </div>
</template>
