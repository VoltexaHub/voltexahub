<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ reports: Object, counts: Object, filter: String });

const tabs = [
    { key: 'pending', label: 'Pending' },
    { key: 'resolved', label: 'Resolved' },
    { key: 'dismissed', label: 'Dismissed' },
];

const dismiss = (r) => { if (confirm('Dismiss this report?')) router.post(route('admin.reports.dismiss', r.id), {}, { preserveScroll: true }); };
const resolveDelete = (r) => {
    if (confirm('Delete the reported post and resolve all reports on it?')) {
        router.post(route('admin.reports.resolve-delete', r.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Admin · Reports" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Moderation</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Reports</h1>
            </div>
            <nav class="flex gap-1 text-sm">
                <Link v-for="t in tabs" :key="t.key" :href="route('admin.reports.index', { status: t.key })"
                      class="px-3 py-1.5 rounded-md border font-mono text-xs uppercase tracking-wider transition-colors"
                      :style="filter === t.key
                        ? { color: '#fff', background: 'var(--accent)', borderColor: 'var(--accent)' }
                        : { color: 'var(--text-muted)', background: 'var(--surface)', borderColor: 'var(--border)' }">
                    {{ t.label }} <span class="opacity-80">· {{ counts[t.key] }}</span>
                </Link>
            </nav>
        </header>

        <ul>
            <li v-for="r in reports.data" :key="r.id" class="border-t py-5 flex items-start gap-4" :style="{ borderColor: 'var(--border)' }">
                <div class="flex-1 min-w-0">
                    <p class="text-sm" style="color:var(--text)">
                        <span class="font-serif font-medium" style="font-family:'Fraunces',serif">{{ r.reporter?.name }}</span>
                        <span style="color:var(--text-muted)"> reported </span>
                        <Link v-if="r.post?.author" :href="route('users.show', r.post.author)"
                              class="font-serif font-medium hover:underline" style="font-family:'Fraunces',serif">
                            {{ r.post.author.name }}
                        </Link>
                        <span v-else style="color:var(--text-muted)">[deleted user]</span>
                        <span class="ml-2 vx-chip">{{ r.reason }}</span>
                        <span class="vx-meta ml-3 normal-case tracking-normal">{{ new Date(r.created_at).toLocaleString() }}</span>
                    </p>
                    <p v-if="r.note" class="text-sm italic mt-1.5" style="color:var(--text-muted)">"{{ r.note }}"</p>

                    <div v-if="r.post" class="mt-3 border-l-2 pl-4" :style="{ borderColor: 'var(--accent)' }">
                        <p class="text-xs mb-1" style="color:var(--text-subtle)">
                            <Link :href="route('threads.show', [r.post.thread.forum.slug, r.post.thread.slug]) + '#post-' + r.post.id"
                                  class="hover:underline" :style="{ color: 'var(--accent)' }">
                                {{ r.post.thread.title }}
                            </Link>
                            <span class="ml-1">in {{ r.post.thread.forum.name }}</span>
                        </p>
                        <p class="text-sm line-clamp-3 whitespace-pre-wrap" style="color:var(--text)">{{ r.post.body }}</p>
                    </div>
                    <p v-else class="text-sm italic mt-2" style="color:var(--text-subtle)">[post deleted]</p>

                    <p v-if="r.resolver" class="vx-meta mt-3 normal-case tracking-normal">
                        {{ r.status }} by {{ r.resolver.name }} · {{ new Date(r.resolved_at).toLocaleString() }}
                    </p>
                </div>
                <div v-if="filter === 'pending' && r.post" class="flex flex-col gap-2 shrink-0">
                    <button @click="resolveDelete(r)"
                            class="text-xs px-3 py-1.5 rounded-md border hover:bg-red-50 transition-colors"
                            style="color:#dc2626;border-color:#fecaca;">
                        Delete post
                    </button>
                    <button @click="dismiss(r)"
                            class="text-xs px-3 py-1.5 rounded-md border transition-colors"
                            :style="{ color: 'var(--text-muted)', borderColor: 'var(--border)' }">
                        Dismiss
                    </button>
                </div>
            </li>
            <li v-if="reports.data.length === 0" class="py-16 text-center italic" style="color:var(--text-muted)">
                No {{ filter }} reports.
            </li>
        </ul>

        <div v-if="reports.links" class="mt-6 flex flex-wrap gap-1">
            <Link v-for="(link, i) in reports.links" :key="i" :href="link.url || '#'" v-html="link.label"
                class="px-3 py-1 text-sm border rounded-md font-mono"
                :class="[!link.url && 'opacity-40 pointer-events-none']"
                :style="{
                    background: link.active ? 'var(--accent)' : 'var(--surface)',
                    borderColor: link.active ? 'var(--accent)' : 'var(--border)',
                    color: link.active ? '#fff' : 'var(--text-muted)',
                }" />
        </div>
    </AdminLayout>
</template>
