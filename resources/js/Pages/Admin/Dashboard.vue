<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'

defineProps({ stats: Object })

const cards = [
    { label: 'Users', key: 'users', icon: '👥' },
    { label: 'Threads', key: 'threads', icon: '📋' },
    { label: 'Posts', key: 'posts', icon: '✍️' },
    { label: 'Open Reports', key: 'reports', icon: '🚩' },
]
</script>
<template>
  <AdminLayout>
    <template #header>
      <h1 class="font-semibold text-sm" style="color:var(--text)">Dashboard</h1>
    </template>
    <Head title="Admin — Dashboard" />
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in cards" :key="card.key"
           class="rounded-xl p-5" style="background:var(--surface);border:1px solid var(--border)">
        <div class="text-2xl mb-2">{{ card.icon }}</div>
        <div class="text-2xl font-bold" style="color:var(--text)">{{ stats[card.key] }}</div>
        <div class="text-xs mt-1" style="color:var(--text-muted)">{{ card.label }}</div>
      </div>
    </div>
  </AdminLayout>
</template>
