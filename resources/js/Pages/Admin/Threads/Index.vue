<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    threads: Object,
    filters: Object,
});

const q = ref(props.filters.q || '');

let t;
watch(q, (val) => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get(route('admin.threads.index'), { q: val }, { preserveState: true, replace: true });
    }, 300);
});

const togglePin = (thread) => router.put(route('admin.threads.update', thread.id), { is_pinned: !thread.is_pinned }, { preserveScroll: true });
const toggleLock = (thread) => router.put(route('admin.threads.update', thread.id), { is_locked: !thread.is_locked }, { preserveScroll: true });
const destroy = (thread) => {
    if (confirm(`Delete thread "${thread.title}"?`)) {
        router.delete(route('admin.threads.destroy', thread.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Threads" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Threads</h1>
            <input v-model="q" type="search" placeholder="Search titles..." class="rounded border-gray-300 text-sm" />
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Forum</th>
                        <th class="px-4 py-2">Author</th>
                        <th class="px-4 py-2 w-20">Posts</th>
                        <th class="px-4 py-2 w-32">Last Post</th>
                        <th class="px-4 py-2 w-64 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="thread in threads.data" :key="thread.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span v-if="thread.is_pinned" class="text-xs text-amber-600">📌</span>
                                <span v-if="thread.is_locked" class="text-xs text-red-600">🔒</span>
                                <Link :href="route('threads.show', [thread.forum.slug, thread.slug])" class="font-medium text-indigo-600 hover:underline">
                                    {{ thread.title }}
                                </Link>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ thread.forum?.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ thread.author?.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ thread.posts_count }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ thread.last_post_at ? new Date(thread.last_post_at).toLocaleDateString() : '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button @click="togglePin(thread)" class="text-xs px-2 py-1 rounded border" :class="thread.is_pinned ? 'bg-amber-50 border-amber-200 text-amber-700' : 'border-gray-200 text-gray-700'">
                                {{ thread.is_pinned ? 'Unpin' : 'Pin' }}
                            </button>
                            <button @click="toggleLock(thread)" class="text-xs px-2 py-1 rounded border" :class="thread.is_locked ? 'bg-red-50 border-red-200 text-red-700' : 'border-gray-200 text-gray-700'">
                                {{ thread.is_locked ? 'Unlock' : 'Lock' }}
                            </button>
                            <button @click="destroy(thread)" class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="threads.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No threads found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="threads.links" class="mt-4 flex flex-wrap gap-1">
            <Link v-for="(link, i) in threads.links" :key="i" :href="link.url || '#'" v-html="link.label"
                :class="['px-3 py-1 text-sm border rounded', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50', !link.url && 'opacity-50 pointer-events-none']" />
        </div>
    </AdminLayout>
</template>
