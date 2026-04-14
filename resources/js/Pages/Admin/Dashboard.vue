<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin · Overview" />
    <AdminLayout>
        <header class="mb-10">
            <p class="vx-meta mb-2">The Hub · Admin</p>
            <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',Georgia,serif;color:var(--text)">
                Overview
            </h1>
        </header>

        <dl class="grid grid-cols-2 md:grid-cols-5 border-t border-b" style="border-color:var(--border)">
            <div
                v-for="(value, key, i) in stats"
                :key="key"
                class="py-6 px-5"
                :class="i > 0 ? 'border-l' : ''"
                :style="{ borderColor: 'var(--border)' }"
            >
                <dt class="vx-meta mb-2">{{ key }}</dt>
                <dd class="text-4xl font-semibold tabular-nums tracking-tight" style="font-family:'Fraunces',Georgia,serif;color:var(--text)">
                    {{ value }}
                </dd>
            </div>
        </dl>
    </AdminLayout>
</template>
