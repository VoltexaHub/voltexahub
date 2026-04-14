<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({ avatar: null });
const fileInput = ref(null);

const onChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    form.avatar = file;
    form.post(route('profile.avatar.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset(); if (fileInput.value) fileInput.value.value = ''; },
    });
};

const remove = () => {
    if (confirm('Remove avatar?')) {
        router.delete(route('profile.avatar.destroy'), { preserveScroll: true });
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Avatar</h2>
            <p class="mt-1 text-sm text-gray-600">Upload an image (JPG, PNG, GIF, or WebP — max 2 MB).</p>
        </header>

        <div class="mt-6 flex items-center gap-6">
            <img :src="user.avatar_url" :key="user.avatar_url" alt="" class="w-20 h-20 rounded-full border border-gray-200" />
            <div class="space-y-2">
                <label class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 cursor-pointer">
                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onChange" :disabled="form.processing" />
                    {{ form.processing ? 'Uploading...' : 'Upload new' }}
                </label>
                <button v-if="user.avatar_path" type="button" @click="remove"
                        class="ml-2 text-sm text-red-600 hover:underline">Remove</button>
                <p v-if="form.errors.avatar" class="text-sm text-red-600">{{ form.errors.avatar }}</p>
            </div>
        </div>
    </section>
</template>
