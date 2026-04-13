<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});

const nav = [
    { label: 'Dashboard', route: 'admin.dashboard' },
    { label: 'Categories', route: 'admin.categories.index' },
    { label: 'Forums', route: 'admin.forums.index' },
    { label: 'Threads', route: 'admin.threads.index' },
    { label: 'Users', route: 'admin.users.index' },
    { label: 'Plugins', route: 'admin.plugins.index' },
];

const isActive = (name) => page.url.startsWith('/admin') && route().current(name.replace('.index', '.*'));
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <header class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <Link :href="route('admin.dashboard')" class="font-semibold">VoltexaHub Admin</Link>
                    <nav class="flex gap-4 text-sm">
                        <Link
                            v-for="item in nav"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="['hover:text-white', isActive(item.route) ? 'text-white' : 'text-gray-300']"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <Link :href="route('home')" class="text-gray-300 hover:text-white">View Site</Link>
                    <span class="text-gray-400">{{ page.props.auth.user?.name }}</span>
                    <Link :href="route('logout')" method="post" as="button" class="text-gray-300 hover:text-white">Log out</Link>
                </div>
            </div>
        </header>

        <div v-if="flash.success" class="bg-green-100 border-b border-green-200 text-green-800 px-4 py-2 text-sm">
            <div class="max-w-7xl mx-auto">{{ flash.success }}</div>
        </div>
        <div v-if="flash.error" class="bg-red-100 border-b border-red-200 text-red-800 px-4 py-2 text-sm">
            <div class="max-w-7xl mx-auto">{{ flash.error }}</div>
        </div>

        <main class="max-w-7xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
