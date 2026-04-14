<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    notify_reply_email: !!user.value.notify_reply_email,
    notify_reply_app:   !!user.value.notify_reply_app,
    notify_pm_email:    !!user.value.notify_pm_email,
    notify_pm_app:      !!user.value.notify_pm_app,
});

const save = () => form.patch(route('profile.notifications.update'), { preserveScroll: true });

const rows = [
    { key: 'reply', label: 'Thread replies', help: "When someone replies in a thread you started or participated in." },
    { key: 'pm',    label: 'Private messages', help: "When another user sends you a message." },
];
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Notifications</h2>
            <p class="mt-1 text-sm text-gray-600">Choose which events should notify you, and how.</p>
        </header>

        <form @submit.prevent="save" class="mt-6 space-y-6">
            <div>
                <div class="grid grid-cols-[1fr_auto_auto] gap-x-6 gap-y-2 text-sm items-center">
                    <div></div>
                    <div class="text-xs font-mono uppercase tracking-wider text-gray-500 text-center">Email</div>
                    <div class="text-xs font-mono uppercase tracking-wider text-gray-500 text-center">In-app</div>

                    <template v-for="row in rows" :key="row.key">
                        <div class="border-t border-gray-200 py-3">
                            <div class="font-medium text-gray-900">{{ row.label }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ row.help }}</div>
                        </div>
                        <div class="border-t border-gray-200 py-3 text-center">
                            <input :id="`${row.key}_email`" v-model="form[`notify_${row.key}_email`]" type="checkbox"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        </div>
                        <div class="border-t border-gray-200 py-3 text-center">
                            <input :id="`${row.key}_app`" v-model="form[`notify_${row.key}_app`]" type="checkbox"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-gray-700 disabled:opacity-50">
                    Save
                </button>
                <Transition leave-active-class="transition ease-in-out" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
