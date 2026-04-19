<script setup>
import { useStorage } from '@vueuse/core'
const dark = useStorage('theme', true)
function toggle() {
    dark.value = !dark.value
    document.documentElement.classList.toggle('light', !dark.value)
}
if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('light', !dark.value)
}
</script>
<template>
  <button @click="toggle" class="p-2 rounded-lg hover:bg-white/10 transition-colors" :title="dark ? 'Switch to light' : 'Switch to dark'">
    <span v-if="dark">☀️</span>
    <span v-else>🌙</span>
  </button>
</template>
