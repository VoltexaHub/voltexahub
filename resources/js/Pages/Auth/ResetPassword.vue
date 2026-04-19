<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ token: String, email: String })
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
function submit() { form.post(route('password.update')) }
</script>
<template>
  <AppLayout>
    <Head title="Reset Password" />
    <div class="max-w-md mx-auto mt-16 px-4">
      <h1 class="text-2xl font-bold mb-6" style="color:var(--text)">Set new password</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Email</label>
          <input v-model="form.email" type="email" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">New password</label>
          <input v-model="form.password" type="password" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Confirm password</label>
          <input v-model="form.password_confirmation" type="password" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.password_confirmation" class="text-red-400 text-xs mt-1">{{ form.errors.password_confirmation }}</p>
        </div>
        <button type="submit" :disabled="form.processing"
                class="w-full py-2 rounded-lg text-white font-medium text-sm"
                style="background:var(--accent-gradient)">
          Reset password
        </button>
      </form>
    </div>
  </AppLayout>
</template>
