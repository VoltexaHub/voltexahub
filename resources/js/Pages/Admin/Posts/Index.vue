<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({ posts: Object, forums: Array, filters: Object });

const q = ref(props.filters.q || '');
const forumId = ref(props.filters.forum_id || '');
const selected = ref(new Set());

let t;
const push = () => router.get(route('admin.posts.index'),
    { q: q.value, forum_id: forumId.value || null },
    { preserveState: true, replace: true });

watch(q, () => { clearTimeout(t); t = setTimeout(push, 300); });
watch(forumId, push);

const allSelected = computed(() => props.posts.data.length > 0 && selected.value.size === props.posts.data.length);
const toggleAll = () => {
    if (allSelected.value) selected.value = new Set();
    else selected.value = new Set(props.posts.data.map((p) => p.id));
};
const toggle = (id) => {
    const s = new Set(selected.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selected.value = s;
};

const destroy = (post) => {
    if (confirm('Delete this post?')) {
        router.delete(route('admin.posts.destroy', post.id), { preserveScroll: true });
    }
};

const bulkDelete = () => {
    if (selected.value.size === 0) return;
    if (!confirm(`Delete ${selected.value.size} post${selected.value.size === 1 ? '' : 's'}?`)) return;
    router.post(route('admin.posts.bulk-destroy'),
        { ids: [...selected.value], _method: 'delete' },
        { preserveScroll: true, onSuccess: () => selected.value = new Set() });
};

const excerpt = (body) => body.length > 140 ? body.slice(0, 140) + '…' : body;
const fmtDate = (d) => new Date(d).toLocaleDateString();
</script>

<template>
    <Head title="Admin · Posts" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b gap-4 flex-wrap" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Moderation</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Posts</h1>
            </div>
            <div class="flex items-center gap-2">
                <select v-model="forumId" class="vx-input text-sm">
                    <option value="">All forums</option>
                    <option v-for="f in forums" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>
                <input v-model="q" type="search" placeholder="Search body…" class="vx-input text-sm max-w-xs" />
            </div>
        </header>

        <div v-if="selected.size > 0"
             class="mb-4 flex items-center justify-between px-4 py-2 rounded-md"
             :style="{ background: 'var(--surface-mute)', borderColor: 'var(--border)' }">
            <span class="text-sm" style="color:var(--text-muted)">{{ selected.size }} selected</span>
            <button @click="bulkDelete" class="text-sm text-red-600 hover:underline">Delete selected</button>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 w-8"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th>
                    <th class="py-2 vx-meta">Excerpt</th>
                    <th class="py-2 vx-meta w-40">Thread</th>
                    <th class="py-2 vx-meta w-28">Author</th>
                    <th class="py-2 vx-meta w-24">Posted</th>
                    <th class="py-2 vx-meta w-24 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="post in posts.data" :key="post.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-3"><input type="checkbox" :checked="selected.has(post.id)" @change="toggle(post.id)" /></td>
                    <td class="py-3" style="color:var(--text)">
                        <div class="whitespace-pre-wrap line-clamp-2">{{ excerpt(post.body) }}</div>
                    </td>
                    <td class="py-3" style="color:var(--text-muted)">
                        <Link v-if="post.thread" :href="route('threads.show', [post.thread.forum.slug, post.thread.slug])"
                              class="hover:underline" :style="{ color: 'var(--accent)' }">
                            {{ post.thread.title }}
                        </Link>
                    </td>
                    <td class="py-3" style="color:var(--text-muted)">{{ post.author?.name }}</td>
                    <td class="py-3 font-mono text-xs" style="color:var(--text-subtle)">{{ fmtDate(post.created_at) }}</td>
                    <td class="py-3 text-right text-xs">
                        <button @click="destroy(post)" class="hover:underline text-red-600">Delete</button>
                    </td>
                </tr>
                <tr v-if="posts.data.length === 0">
                    <td colspan="6" class="py-16 text-center italic" style="color:var(--text-muted)">No posts match.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="posts.links" class="mt-6 flex flex-wrap gap-1">
            <Link v-for="(link, i) in posts.links" :key="i" :href="link.url || '#'" v-html="link.label"
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
