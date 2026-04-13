<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    plugins: Array,
});

const toggle = (plugin) => {
    const url = plugin.enabled
        ? route('admin.plugins.disable', plugin.slug)
        : route('admin.plugins.enable', plugin.slug);
    router.post(url, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Plugins" />
    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Plugins</h1>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2">Version</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2 w-32 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="plugin in plugins" :key="plugin.slug">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ plugin.name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ plugin.slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ plugin.version }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ plugin.description }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                @click="toggle(plugin)"
                                :class="[
                                    'text-xs px-3 py-1 rounded border',
                                    plugin.enabled
                                        ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100'
                                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                {{ plugin.enabled ? 'Enabled' : 'Disabled' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="plugins.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No plugins installed. Drop one in <code>plugins/</code>.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-xs text-gray-500">
            Plugins expose hooks via <code>$hooks-&gt;listen('hook_name', fn)</code> in <code>plugin.php</code>.
            Themes invoke slots with <code>&#64;hook('hook_name')</code>.
        </div>
    </AdminLayout>
</template>
