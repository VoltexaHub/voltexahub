<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const props = defineProps({ turnstileSiteKey: String })
const form = useForm({ email: '', password: '', remember: false, _turnstile: '' })
const turnstileWidget = ref(null)

onMounted(() => {
    if (window.turnstile && props.turnstileSiteKey) {
        window.turnstile.render(turnstileWidget.value, {
            sitekey: props.turnstileSiteKey,
            callback: (token) => { form._turnstile = token },
        })
    }
})

function submit() { form.post(route('login')) }
</script>
<template>
  <AppLayout>
    <Head title="Login" />
    <div class="max-w-md mx-auto mt-16 px-4">
      <h1 class="text-2xl font-bold mb-6" style="color:var(--text)">Sign in</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Email</label>
          <input v-model="form.email" type="email" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Password</label>
          <input v-model="form.password" type="password" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
        </div>
        <div ref="turnstileWidget"></div>
        <p v-if="form.errors._turnstile" class="text-red-400 text-xs">{{ form.errors._turnstile }}</p>
        <button type="submit" :disabled="form.processing"
                class="w-full py-2 rounded-lg text-white font-medium text-sm transition-opacity"
                :class="form.processing ? 'opacity-50' : ''"
                style="background:var(--accent-gradient)">
          Sign in
        </button>
        <p class="text-sm text-center" style="color:var(--text-muted)">
          No account? <Link :href="route('register')" style="color:var(--accent)">Register</Link>
        </p>
      </form>
    </div>
  </AppLayout>
</template>
