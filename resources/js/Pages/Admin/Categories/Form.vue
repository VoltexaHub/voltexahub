<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ category: Object });

const isEdit = !!props.category;

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    description: props.category?.description ?? '',
    position: props.category?.position ?? 0,
});

const submit = () => {
    if (isEdit) form.put(route('admin.categories.update', props.category.id));
    else form.post(route('admin.categories.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Admin · Edit Category' : 'Admin · New Category'" />
    <AdminLayout>
        <header class="mb-8">
            <p class="vx-meta mb-2">{{ isEdit ? 'Editing category' : 'New category' }}</p>
            <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">
                {{ isEdit ? category.name : 'Untitled' }}
            </h1>
        </header>

        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <div>
                <label class="vx-meta mb-2 block">Name</label>
                <input v-model="form.name" type="text" class="vx-input" required />
                <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="vx-meta mb-2 block">Slug <span class="lowercase tracking-normal opacity-60 normal-case">(auto from name if blank)</span></label>
                <input v-model="form.slug" type="text" class="vx-input font-mono" />
                <p v-if="form.errors.slug" class="text-sm text-red-600 mt-1">{{ form.errors.slug }}</p>
            </div>
            <div>
                <label class="vx-meta mb-2 block">Description</label>
                <textarea v-model="form.description" rows="3" class="vx-input" />
                <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
            </div>
            <div>
                <label class="vx-meta mb-2 block">Position</label>
                <input v-model.number="form.position" type="number" min="0" class="vx-input w-32" />
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t" style="border-color:var(--border)">
                <Link :href="route('admin.categories.index')" class="vx-btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="vx-btn-primary">{{ isEdit ? 'Update' : 'Create' }}</button>
            </div>
        </form>
    </AdminLayout>
</template>
