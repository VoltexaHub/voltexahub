<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ threads: Object, filters: Object });

const q = ref(props.filters.q || '');
let t;
watch(q, (val) => {
    clearTimeout(t);
    t = setTimeout(() => router.get(route('admin.threads.index'), { q: val }, { preserveState: true, replace: true }), 300);
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
    <Head title="Admin · Threads" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Moderation</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Threads</h1>
            </div>
            <input v-model="q" type="search" placeholder="Search titles…" class="vx-input text-sm max-w-xs" />
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta">Title</th>
                    <th class="py-2 vx-meta">Forum</th>
                    <th class="py-2 vx-meta">Author</th>
                    <th class="py-2 vx-meta w-16 text-right">Posts</th>
                    <th class="py-2 vx-meta w-28">Last Post</th>
                    <th class="py-2 vx-meta w-56 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="thread in threads.data" :key="thread.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span v-if="thread.is_pinned" class="vx-chip">Pinned</span>
                            <span v-if="thread.is_locked" class="vx-chip" style="color:#991b1b;background:#fee2e2;border-color:#fecaca;">Locked</span>
                            <Link :href="route('threads.show', [thread.forum.slug, thread.slug])" class="font-serif font-medium hover:underline" style="font-family:'Fraunces',serif;color:var(--text)">
                                {{ thread.title }}
                            </Link>
                        </div>
                    </td>
                    <td class="py-4" style="color:var(--text-muted)">{{ thread.forum?.name }}</td>
                    <td class="py-4" style="color:var(--text-muted)">{{ thread.author?.name }}</td>
                    <td class="py-4 text-right tabular-nums" style="color:var(--text-muted)">{{ thread.posts_count }}</td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-subtle)">{{ thread.last_post_at ? new Date(thread.last_post_at).toLocaleDateString() : '—' }}</td>
                    <td class="py-4 text-right space-x-3 text-xs">
                        <button @click="togglePin(thread)" class="hover:underline" :style="{ color: thread.is_pinned ? 'var(--accent)' : 'var(--text-muted)' }">
                            {{ thread.is_pinned ? 'Unpin' : 'Pin' }}
                        </button>
                        <button @click="toggleLock(thread)" class="hover:underline" :style="{ color: thread.is_locked ? '#dc2626' : 'var(--text-muted)' }">
                            {{ thread.is_locked ? 'Unlock' : 'Lock' }}
                        </button>
                        <button @click="destroy(thread)" class="hover:underline text-red-600">Delete</button>
                    </td>
                </tr>
                <tr v-if="threads.data.length === 0">
                    <td colspan="6" class="py-16 text-center italic" style="color:var(--text-muted)">No threads found.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="threads.links" class="mt-6 flex flex-wrap gap-1">
            <Link v-for="(link, i) in threads.links" :key="i" :href="link.url || '#'" v-html="link.label"
                class="px-3 py-1 text-sm border rounded-md font-mono"
                :class="[link.active ? 'text-white' : '', !link.url && 'opacity-40 pointer-events-none']"
                :style="{
                    background: link.active ? 'var(--accent)' : 'var(--surface)',
                    borderColor: link.active ? 'var(--accent)' : 'var(--border)',
                    color: link.active ? '#fff' : 'var(--text-muted)',
                }" />
        </div>
    </AdminLayout>
</template>
