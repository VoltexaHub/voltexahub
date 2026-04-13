<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
                <Link :href="route('home')" class="text-lg font-semibold text-gray-900">
                    VoltexaHub
                </Link>
                <nav class="flex items-center gap-4 text-sm">
                    <template v-if="page.props.auth.user">
                        <Link v-if="page.props.auth.user.is_admin" :href="route('admin.dashboard')" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            Admin
                        </Link>
                        <Link :href="route('dashboard')" class="text-gray-700 hover:text-gray-900">
                            {{ page.props.auth.user.name }}
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-gray-500 hover:text-gray-900">
                            Log out
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-gray-700 hover:text-gray-900">Log in</Link>
                        <Link :href="route('register')" class="text-gray-700 hover:text-gray-900">Register</Link>
                    </template>
                </nav>
            </div>
        </header>
        <main class="max-w-6xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
