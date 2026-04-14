<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ users: Object, filters: Object });

const page = usePage();
const q = ref(props.filters.q || '');
let t;
watch(q, (val) => {
    clearTimeout(t);
    t = setTimeout(() => router.get(route('admin.users.index'), { q: val }, { preserveState: true, replace: true }), 300);
});

const toggleAdmin = (user) => router.put(route('admin.users.update', user.id), { is_admin: !user.is_admin }, { preserveScroll: true });
const destroy = (user) => {
    if (confirm(`Delete user "${user.name}"? This deletes their threads and posts.`)) {
        router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Admin · Users" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Community</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Users</h1>
            </div>
            <input v-model="q" type="search" placeholder="Search name or email…" class="vx-input text-sm max-w-xs" />
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta w-12">#</th>
                    <th class="py-2 vx-meta">Name</th>
                    <th class="py-2 vx-meta">Email</th>
                    <th class="py-2 vx-meta w-24">Role</th>
                    <th class="py-2 vx-meta w-32">Joined</th>
                    <th class="py-2 vx-meta w-48 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users.data" :key="user.id" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-4 font-mono text-xs tabular-nums" style="color:var(--text-subtle)">{{ user.id }}</td>
                    <td class="py-4 font-serif text-base font-medium" style="font-family:'Fraunces',serif;color:var(--text)">{{ user.name }}</td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-muted)">{{ user.email }}</td>
                    <td class="py-4">
                        <span v-if="user.is_admin" class="vx-chip">Admin</span>
                        <span v-else class="vx-meta">Member</span>
                    </td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-subtle)">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                    <td class="py-4 text-right space-x-4 text-xs">
                        <button @click="toggleAdmin(user)" :disabled="user.id === page.props.auth.user.id"
                                class="hover:underline disabled:opacity-30 disabled:cursor-not-allowed"
                                :style="{ color: user.is_admin ? 'var(--text-muted)' : 'var(--accent)' }">
                            {{ user.is_admin ? 'Demote' : 'Promote' }}
                        </button>
                        <button @click="destroy(user)" :disabled="user.id === page.props.auth.user.id"
                                class="hover:underline disabled:opacity-30 disabled:cursor-not-allowed text-red-600">
                            Delete
                        </button>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td colspan="6" class="py-16 text-center italic" style="color:var(--text-muted)">No users found.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="users.links" class="mt-6 flex flex-wrap gap-1">
            <Link v-for="(link, i) in users.links" :key="i" :href="link.url || '#'" v-html="link.label"
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
