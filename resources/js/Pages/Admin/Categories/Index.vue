<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ categories: Array });

const destroy = (cat) => {
    if (confirm(`Delete category "${cat.name}"? This will also delete its forums.`)) {
        router.delete(route('admin.categories.destroy', cat.id));
    }
};
</script>

<template>
    <Head title="Admin · Categories" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Structure</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Categories</h1>
            </div>
            <Link :href="route('admin.categories.create')" class="vx-btn-primary">New Category</Link>
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta w-16">#</th>
                    <th class="py-2 vx-meta">Name</th>
                    <th class="py-2 vx-meta">Slug</th>
                    <th class="py-2 vx-meta w-24 text-right">Forums</th>
                    <th class="py-2 vx-meta w-40 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(cat, i) in categories" :key="cat.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-4 tabular-nums" style="color:var(--text-subtle)">{{ String(cat.position).padStart(2,'0') }}</td>
                    <td class="py-4 font-serif text-lg font-medium" style="font-family:'Fraunces',serif;color:var(--text)">{{ cat.name }}</td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-muted)">{{ cat.slug }}</td>
                    <td class="py-4 text-right tabular-nums" style="color:var(--text-muted)">{{ cat.forums_count }}</td>
                    <td class="py-4 text-right space-x-4 text-xs">
                        <Link :href="route('admin.categories.edit', cat.id)" class="hover:underline" :style="{ color: 'var(--accent)' }">Edit</Link>
                        <button @click="destroy(cat)" class="hover:underline text-red-600">Delete</button>
                    </td>
                </tr>
                <tr v-if="categories.length === 0">
                    <td colspan="5" class="py-16 text-center italic" style="color:var(--text-muted)">No categories yet.</td>
                </tr>
            </tbody>
        </table>
    </AdminLayout>
</template>
