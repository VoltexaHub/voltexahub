<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const form = useForm({ email: '' })
const flash = computed(() => usePage().props.flash)
function submit() { form.post(route('password.email')) }
</script>
<template>
  <AppLayout>
    <Head title="Forgot Password" />
    <div class="max-w-md mx-auto mt-16 px-4">
      <h1 class="text-2xl font-bold mb-2" style="color:var(--text)">Reset password</h1>
      <p class="text-sm mb-6" style="color:var(--text-muted)">Enter your email to receive a reset link.</p>
      <div v-if="flash?.success" class="mb-4 p-3 rounded-lg text-sm text-green-400 bg-green-400/10">{{ flash.success }}</div>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Email</label>
          <input v-model="form.email" type="email" required
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
        </div>
        <button type="submit" :disabled="form.processing"
                class="w-full py-2 rounded-lg text-white font-medium text-sm"
                style="background:var(--accent-gradient)">
          Send reset link
        </button>
      </form>
    </div>
  </AppLayout>
</template>
