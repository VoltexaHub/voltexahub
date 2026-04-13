<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});

const destroy = (cat) => {
    if (confirm(`Delete category "${cat.name}"? This will also delete its forums.`)) {
        router.delete(route('admin.categories.destroy', cat.id));
    }
};
</script>

<template>
    <Head title="Categories" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Categories</h1>
            <Link :href="route('admin.categories.create')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">
                New Category
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2 w-16">Pos</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2 w-24">Forums</th>
                        <th class="px-4 py-2 w-40 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="cat in categories" :key="cat.id">
                        <td class="px-4 py-3 text-gray-500">{{ cat.position }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ cat.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ cat.slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ cat.forums_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.categories.edit', cat.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                            <button @click="destroy(cat)" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
