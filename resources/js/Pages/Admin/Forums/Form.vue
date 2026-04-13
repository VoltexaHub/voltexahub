<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    forum: Object,
    categories: Array,
});

const isEdit = !!props.forum;

const form = useForm({
    category_id: props.forum?.category_id ?? (props.categories[0]?.id ?? null),
    name: props.forum?.name ?? '',
    slug: props.forum?.slug ?? '',
    description: props.forum?.description ?? '',
    position: props.forum?.position ?? 0,
});

const submit = () => {
    if (isEdit) {
        form.put(route('admin.forums.update', props.forum.id));
    } else {
        form.post(route('admin.forums.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Forum' : 'New Forum'" />
    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">{{ isEdit ? 'Edit' : 'New' }} Forum</h1>

        <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4 max-w-2xl">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select v-model="form.category_id" class="w-full rounded border-gray-300" required>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <p v-if="form.errors.category_id" class="text-sm text-red-600 mt-1">{{ form.errors.category_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input v-model="form.name" type="text" class="w-full rounded border-gray-300" required />
                <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-gray-400">(auto from name if blank)</span></label>
                <input v-model="form.slug" type="text" class="w-full rounded border-gray-300" />
                <p v-if="form.errors.slug" class="text-sm text-red-600 mt-1">{{ form.errors.slug }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea v-model="form.description" rows="3" class="w-full rounded border-gray-300" />
                <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                <input v-model.number="form.position" type="number" min="0" class="w-32 rounded border-gray-300" />
                <p v-if="form.errors.position" class="text-sm text-red-600 mt-1">{{ form.errors.position }}</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <Link :href="route('admin.forums.index')" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 disabled:opacity-50">
                    {{ isEdit ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
