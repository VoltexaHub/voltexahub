<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ forums: Array });

const destroy = (forum) => {
    if (confirm(`Delete forum "${forum.name}"? This also deletes its threads and posts.`)) {
        router.delete(route('admin.forums.destroy', forum.id));
    }
};
</script>

<template>
    <Head title="Admin · Forums" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Structure</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Forums</h1>
            </div>
            <Link :href="route('admin.forums.create')" class="vx-btn-primary">New Forum</Link>
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta w-16">#</th>
                    <th class="py-2 vx-meta">Name</th>
                    <th class="py-2 vx-meta">Category</th>
                    <th class="py-2 vx-meta">Slug</th>
                    <th class="py-2 vx-meta w-20 text-right">Threads</th>
                    <th class="py-2 vx-meta w-20 text-right">Posts</th>
                    <th class="py-2 vx-meta w-40 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="forum in forums" :key="forum.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-4 tabular-nums" style="color:var(--text-subtle)">{{ String(forum.position).padStart(2,'0') }}</td>
                    <td class="py-4 font-serif text-base font-medium" style="font-family:'Fraunces',serif;color:var(--text)">{{ forum.name }}</td>
                    <td class="py-4" style="color:var(--text-muted)">{{ forum.category?.name }}</td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-muted)">{{ forum.slug }}</td>
                    <td class="py-4 text-right tabular-nums" style="color:var(--text-muted)">{{ forum.threads_count }}</td>
                    <td class="py-4 text-right tabular-nums" style="color:var(--text-muted)">{{ forum.posts_count }}</td>
                    <td class="py-4 text-right space-x-4 text-xs">
                        <Link :href="route('admin.forums.edit', forum.id)" class="hover:underline" :style="{ color: 'var(--accent)' }">Edit</Link>
                        <button @click="destroy(forum)" class="hover:underline text-red-600">Delete</button>
                    </td>
                </tr>
                <tr v-if="forums.length === 0">
                    <td colspan="7" class="py-16 text-center italic" style="color:var(--text-muted)">No forums yet.</td>
                </tr>
            </tbody>
        </table>
    </AdminLayout>
</template>
