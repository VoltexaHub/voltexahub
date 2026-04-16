<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ entries: Object, actions: Array, filters: Object });

const action = ref(props.filters.action || '');
watch(action, (v) => router.get(route('admin.activity.index'), { action: v || null }, { preserveState: true, replace: true }));

const fmt = (d) => new Date(d).toLocaleString();
</script>

<template>
    <Head title="Admin · Activity" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b gap-4 flex-wrap" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Audit</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Activity Log</h1>
            </div>
            <select v-model="action" class="vx-input text-sm max-w-xs">
                <option value="">All actions</option>
                <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
            </select>
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta w-40">When</th>
                    <th class="py-2 vx-meta w-32">Admin</th>
                    <th class="py-2 vx-meta w-40">Action</th>
                    <th class="py-2 vx-meta w-32">Subject</th>
                    <th class="py-2 vx-meta">Summary</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="e in entries.data" :key="e.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-3 font-mono text-xs" style="color:var(--text-subtle)">{{ fmt(e.created_at) }}</td>
                    <td class="py-3" style="color:var(--text-muted)">{{ e.user?.name || '—' }}</td>
                    <td class="py-3 font-mono text-xs"><span class="vx-chip">{{ e.action }}</span></td>
                    <td class="py-3 font-mono text-xs" style="color:var(--text-muted)">
                        <span v-if="e.subject_type">{{ e.subject_type }}#{{ e.subject_id }}</span>
                        <span v-else>—</span>
                    </td>
                    <td class="py-3" style="color:var(--text)">{{ e.summary || '' }}</td>
                </tr>
                <tr v-if="entries.data.length === 0">
                    <td colspan="5" class="py-16 text-center italic" style="color:var(--text-muted)">Nothing logged yet.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="entries.links" class="mt-6 flex flex-wrap gap-1">
            <Link v-for="(link, i) in entries.links" :key="i" :href="link.url || '#'" v-html="link.label"
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
