<script setup>
defineProps({ links: Array })
</script>
<template>
  <div v-if="links?.length > 3" class="flex gap-1 mt-6 justify-center">
    <template v-for="link in links" :key="link.label">
      <Link v-if="link.url" :href="link.url"
            class="px-3 py-1.5 rounded text-sm transition-colors"
            :style="link.active
              ? 'background:var(--accent);color:white'
              : 'background:var(--surface);color:var(--text-muted)'"
            v-html="link.label" />
      <span v-else class="px-3 py-1.5 rounded text-sm opacity-40"
            style="color:var(--text-muted)" v-html="link.label" />
    </template>
  </div>
</template>
