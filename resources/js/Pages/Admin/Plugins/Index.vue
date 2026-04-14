<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ plugins: Array });

const toggle = (plugin) => {
    const url = plugin.enabled ? route('admin.plugins.disable', plugin.slug) : route('admin.plugins.enable', plugin.slug);
    router.post(url, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admin · Plugins" />
    <AdminLayout>
        <header class="flex items-end justify-between mb-8 pb-5 border-b" style="border-color:var(--border)">
            <div>
                <p class="vx-meta mb-2">Extensions</p>
                <h1 class="font-serif text-4xl font-semibold tracking-tight" style="font-family:'Fraunces',serif;color:var(--text)">Plugins</h1>
            </div>
        </header>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:var(--text-subtle)">
                    <th class="py-2 vx-meta">Name</th>
                    <th class="py-2 vx-meta">Slug</th>
                    <th class="py-2 vx-meta">Version</th>
                    <th class="py-2 vx-meta">Description</th>
                    <th class="py-2 vx-meta w-28 text-right">State</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="plugin in plugins" :key="plugin.slug" class="border-t" :style="{ borderColor: 'var(--border)' }">
                    <td class="py-4 font-serif text-base font-medium" style="font-family:'Fraunces',serif;color:var(--text)">{{ plugin.name }}</td>
                    <td class="py-4 font-mono text-xs" style="color:var(--text-muted)">{{ plugin.slug }}</td>
                    <td class="py-4 font-mono text-xs tabular-nums" style="color:var(--text-muted)">{{ plugin.version }}</td>
                    <td class="py-4" style="color:var(--text-muted)">{{ plugin.description }}</td>
                    <td class="py-4 text-right">
                        <button @click="toggle(plugin)"
                                class="text-xs font-mono uppercase tracking-wider px-3 py-1.5 rounded-md border transition-colors"
                                :style="plugin.enabled
                                    ? { color: 'var(--accent)', background: 'var(--accent-weak)', borderColor: 'var(--accent)' }
                                    : { color: 'var(--text-muted)', background: 'var(--surface)', borderColor: 'var(--border)' }">
                            {{ plugin.enabled ? 'Enabled' : 'Disabled' }}
                        </button>
                    </td>
                </tr>
                <tr v-if="plugins.length === 0">
                    <td colspan="5" class="py-16 text-center italic" style="color:var(--text-muted)">
                        No plugins installed. Drop one in <code class="font-mono px-1 rounded" style="background:var(--surface-mute)">plugins/</code>.
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="vx-meta mt-8 normal-case tracking-normal text-[0.72rem]" style="color:var(--text-subtle)">
            Plugins expose hooks via <code style="color:var(--accent)">$hooks->listen('hook_name', fn)</code> in <code style="color:var(--accent)">plugin.php</code>.
            Themes invoke slots with <code style="color:var(--accent)">&#64;hook('hook_name')</code>.
        </p>
    </AdminLayout>
</template>
