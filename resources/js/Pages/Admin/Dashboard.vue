<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Dashboard</h1>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div v-for="(value, key) in stats" :key="key" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-sm text-gray-500 capitalize">{{ key }}</div>
                <div class="text-3xl font-semibold text-gray-900 mt-1">{{ value }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
