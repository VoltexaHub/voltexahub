<script setup>
import Navbar from '@/Components/Layout/Navbar.vue'
import Footer from '@/Components/Layout/Footer.vue'
</script>
<template>
  <div class="min-h-screen flex flex-col" style="background:var(--bg)">
    <Navbar />
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
    <Footer />
  </div>
</template>
