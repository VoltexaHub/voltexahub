<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({ settings: Object, callbackUrls: Object });

const form = useForm({
    github_client_id: props.settings.oauth.github.client_id || '',
    github_client_secret: '',
    google_client_id: props.settings.oauth.google.client_id || '',
    google_client_secret: '',
});

const save = () => {
    form.put(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => { form.github_client_secret = ''; form.google_client_secret = ''; },
    });
};

const clearSecret = (provider) => {
    if (confirm(`Clear ${provider} client secret?`)) {
        router.delete(route('admin.settings.oauth.clear-secret'), { data: { provider }, preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Admin · Settings" />
    <AdminLayout>
        <header class="mb-8 pb-5 border-b" style="border-color:var(--border)">
            <p class="vx-meta mb-2">Configuration</p>
            <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Settings</h1>
        </header>

        <form @submit.prevent="save" class="space-y-12 max-w-3xl">
            <section>
                <header class="mb-5">
                    <h2 class="font-serif text-2xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">OAuth providers</h2>
                    <p class="text-sm mt-1" style="color:var(--text-muted)">
                        Leave a provider blank to hide its sign-in button. Secrets are stored encrypted at rest.
                    </p>
                </header>

                <div class="space-y-8">
                    <div class="pt-5 border-t" :style="{ borderColor: 'var(--border)' }">
                        <h3 class="font-serif text-lg font-medium mb-1" style="font-family:'Fraunces',serif;color:var(--text)">GitHub</h3>
                        <p class="vx-meta normal-case tracking-normal text-[0.72rem] mb-4" style="color:var(--text-subtle)">
                            Callback URL: <code class="font-mono" style="color:var(--accent)">{{ callbackUrls.github }}</code>
                            · Register at
                            <a href="https://github.com/settings/developers" target="_blank" class="hover:underline" :style="{ color: 'var(--accent)' }">github.com/settings/developers</a>
                        </p>
                        <div class="space-y-4">
                            <div>
                                <label class="vx-meta mb-2 block">Client ID</label>
                                <input v-model="form.github_client_id" type="text" class="vx-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="vx-meta mb-2 block">
                                    Client Secret
                                    <span v-if="settings.oauth.github.has_secret" class="ml-2 text-[0.7rem] font-mono" :style="{ color: 'var(--accent)' }">· saved</span>
                                </label>
                                <input v-model="form.github_client_secret" type="password" autocomplete="off"
                                       :placeholder="settings.oauth.github.has_secret ? 'Leave blank to keep current' : 'Paste secret here'"
                                       class="vx-input font-mono text-sm" />
                                <button v-if="settings.oauth.github.has_secret" type="button" @click="clearSecret('github')"
                                        class="mt-2 text-xs text-red-600 hover:underline">Clear saved secret</button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t" :style="{ borderColor: 'var(--border)' }">
                        <h3 class="font-serif text-lg font-medium mb-1" style="font-family:'Fraunces',serif;color:var(--text)">Google</h3>
                        <p class="vx-meta normal-case tracking-normal text-[0.72rem] mb-4" style="color:var(--text-subtle)">
                            Callback URL: <code class="font-mono" style="color:var(--accent)">{{ callbackUrls.google }}</code>
                            · Register at
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="hover:underline" :style="{ color: 'var(--accent)' }">console.cloud.google.com/apis/credentials</a>
                        </p>
                        <div class="space-y-4">
                            <div>
                                <label class="vx-meta mb-2 block">Client ID</label>
                                <input v-model="form.google_client_id" type="text" class="vx-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="vx-meta mb-2 block">
                                    Client Secret
                                    <span v-if="settings.oauth.google.has_secret" class="ml-2 text-[0.7rem] font-mono" :style="{ color: 'var(--accent)' }">· saved</span>
                                </label>
                                <input v-model="form.google_client_secret" type="password" autocomplete="off"
                                       :placeholder="settings.oauth.google.has_secret ? 'Leave blank to keep current' : 'Paste secret here'"
                                       class="vx-input font-mono text-sm" />
                                <button v-if="settings.oauth.google.has_secret" type="button" @click="clearSecret('google')"
                                        class="mt-2 text-xs text-red-600 hover:underline">Clear saved secret</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end pt-5 border-t" :style="{ borderColor: 'var(--border)' }">
                <button type="submit" :disabled="form.processing" class="vx-btn-primary">Save Settings</button>
            </div>
        </form>
    </AdminLayout>
</template>
