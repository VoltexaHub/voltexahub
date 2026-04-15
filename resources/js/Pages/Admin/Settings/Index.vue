<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({ settings: Object, callbackUrls: Object });

const form = useForm({
    github_client_id: props.settings.oauth.github.client_id || '',
    github_client_secret: '',
    google_client_id: props.settings.oauth.google.client_id || '',
    google_client_secret: '',
    announcement_message: props.settings.announcement?.message || '',
    announcement_tone: props.settings.announcement?.tone || 'info',
    privacy_title: props.settings.pages?.privacy?.title || '',
    privacy_body: props.settings.pages?.privacy?.body || '',
    terms_title: props.settings.pages?.terms?.title || '',
    terms_body: props.settings.pages?.terms?.body || '',
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

            <section>
                <header class="mb-5">
                    <h2 class="font-serif text-2xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Announcement</h2>
                    <p class="text-sm mt-1" style="color:var(--text-muted)">
                        A sitewide banner shown above every page. Leave blank to hide. Changing the text bumps the version and re-shows it to users who dismissed an earlier one.
                    </p>
                </header>

                <div class="pt-5 border-t space-y-4" :style="{ borderColor: 'var(--border)' }">
                    <div>
                        <label class="vx-meta mb-2 block">Message</label>
                        <textarea v-model="form.announcement_message" rows="3" maxlength="1000"
                                  placeholder="e.g. We'll be upgrading the forum Friday at 8pm UTC."
                                  class="vx-input text-sm"></textarea>
                    </div>
                    <div>
                        <label class="vx-meta mb-2 block">Tone</label>
                        <select v-model="form.announcement_tone" class="vx-input text-sm w-48">
                            <option value="info">Info (accent)</option>
                            <option value="notice">Notice (neutral)</option>
                            <option value="warning">Warning (amber)</option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <header class="mb-5">
                    <h2 class="font-serif text-2xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Pages</h2>
                    <p class="text-sm mt-1" style="color:var(--text-muted)">
                        Markdown body shown at <code style="color:var(--accent)">/privacy</code> and <code style="color:var(--accent)">/terms</code>. Blank reverts to the built-in default copy.
                    </p>
                </header>

                <div class="space-y-8">
                    <div class="pt-5 border-t space-y-3" :style="{ borderColor: 'var(--border)' }">
                        <h3 class="font-serif text-lg font-medium" style="font-family:'Fraunces',serif;color:var(--text)">Privacy Policy</h3>
                        <div>
                            <label class="vx-meta mb-2 block">Title</label>
                            <input v-model="form.privacy_title" type="text" maxlength="120" class="vx-input text-sm" />
                        </div>
                        <div>
                            <label class="vx-meta mb-2 block">Body (markdown)</label>
                            <textarea v-model="form.privacy_body" rows="10" maxlength="20000"
                                      placeholder="Leave blank to use the built-in default"
                                      class="vx-input font-mono text-sm"></textarea>
                        </div>
                    </div>
                    <div class="pt-5 border-t space-y-3" :style="{ borderColor: 'var(--border)' }">
                        <h3 class="font-serif text-lg font-medium" style="font-family:'Fraunces',serif;color:var(--text)">Terms of Service</h3>
                        <div>
                            <label class="vx-meta mb-2 block">Title</label>
                            <input v-model="form.terms_title" type="text" maxlength="120" class="vx-input text-sm" />
                        </div>
                        <div>
                            <label class="vx-meta mb-2 block">Body (markdown)</label>
                            <textarea v-model="form.terms_body" rows="10" maxlength="20000"
                                      placeholder="Leave blank to use the built-in default"
                                      class="vx-input font-mono text-sm"></textarea>
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
