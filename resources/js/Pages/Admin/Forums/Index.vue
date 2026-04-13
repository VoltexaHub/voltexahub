<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    forums: Array,
});

const destroy = (forum) => {
    if (confirm(`Delete forum "${forum.name}"? This will also delete its threads and posts.`)) {
        router.delete(route('admin.forums.destroy', forum.id));
    }
};
</script>

<template>
    <Head title="Forums" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Forums</h1>
            <Link :href="route('admin.forums.create')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">
                New Forum
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2 w-16">Pos</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2 w-24">Threads</th>
                        <th class="px-4 py-2 w-24">Posts</th>
                        <th class="px-4 py-2 w-40 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="forum in forums" :key="forum.id">
                        <td class="px-4 py-3 text-gray-500">{{ forum.position }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ forum.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ forum.category?.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ forum.slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ forum.threads_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ forum.posts_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.forums.edit', forum.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                            <button @click="destroy(forum)" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="forums.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No forums yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
