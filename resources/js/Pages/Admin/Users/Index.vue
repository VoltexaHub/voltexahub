<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const page = usePage();
const q = ref(props.filters.q || '');

let t;
watch(q, (val) => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get(route('admin.users.index'), { q: val }, { preserveState: true, replace: true });
    }, 300);
});

const toggleAdmin = (user) => router.put(route('admin.users.update', user.id), { is_admin: !user.is_admin }, { preserveScroll: true });
const destroy = (user) => {
    if (confirm(`Delete user "${user.name}"? This will delete their threads and posts.`)) {
        router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Users" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Users</h1>
            <input v-model="q" type="search" placeholder="Search name or email..." class="rounded border-gray-300 text-sm" />
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2 w-12">ID</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2 w-32">Admin</th>
                        <th class="px-4 py-2 w-32">Joined</th>
                        <th class="px-4 py-2 w-48 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3 text-gray-500">{{ user.id }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span v-if="user.is_admin" class="inline-block px-2 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">Admin</span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button
                                @click="toggleAdmin(user)"
                                :disabled="user.id === page.props.auth.user.id"
                                class="text-xs px-2 py-1 rounded border border-gray-200 text-gray-700 disabled:opacity-40"
                            >
                                {{ user.is_admin ? 'Demote' : 'Promote' }}
                            </button>
                            <button
                                @click="destroy(user)"
                                :disabled="user.id === page.props.auth.user.id"
                                class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-40"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No users found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.links" class="mt-4 flex flex-wrap gap-1">
            <Link v-for="(link, i) in users.links" :key="i" :href="link.url || '#'" v-html="link.label"
                :class="['px-3 py-1 text-sm border rounded', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50', !link.url && 'opacity-50 pointer-events-none']" />
        </div>
    </AdminLayout>
</template>
