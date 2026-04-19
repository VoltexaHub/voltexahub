<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const form = useForm({})
const flash = computed(() => usePage().props.flash)
function resend() { form.post(route('verification.send')) }
</script>
<template>
  <AppLayout>
    <Head title="Verify Email" />
    <div class="max-w-md mx-auto mt-16 px-4 text-center">
      <h1 class="text-2xl font-bold mb-4" style="color:var(--text)">Verify your email</h1>
      <p class="text-sm mb-6" style="color:var(--text-muted)">
        We sent a verification link to your email address. Click the link to activate your account.
      </p>
      <div v-if="flash?.success" class="mb-4 p-3 rounded-lg text-sm text-green-400 bg-green-400/10">{{ flash.success }}</div>
      <button @click="resend" :disabled="form.processing"
              class="px-6 py-2 rounded-lg text-white font-medium text-sm"
              style="background:var(--accent-gradient)">
        Resend verification email
      </button>
    </div>
  </AppLayout>
</template>
