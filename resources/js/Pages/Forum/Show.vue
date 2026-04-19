<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({ forum: Object, threads: Object })
const auth = computed(() => usePage().props.auth)
</script>
<template>
  <AppLayout>
    <Head :title="forum.name" />
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-xs mb-1" style="color:var(--text-faint)">
          <Link :href="route('forum.index')" class="hover:underline" style="color:var(--accent)">Forums</Link>
          → {{ forum.category?.name }}
        </div>
        <h1 class="text-xl font-bold" style="color:var(--text)">{{ forum.name }}</h1>
      </div>
      <Link v-if="auth.user" :href="route('thread.create', forum.id)"
            class="px-4 py-2 rounded-lg text-sm text-white font-medium"
            style="background:var(--accent-gradient)">
        + New Thread
      </Link>
    </div>

    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="!threads.data.length" class="py-12 text-center text-sm" style="color:var(--text-muted)">
        No threads yet. Be the first to post!
      </div>
      <div v-for="(thread, i) in threads.data" :key="thread.id"
           class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/5 transition-colors"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span v-if="thread.is_pinned" class="text-xs px-1.5 py-0.5 rounded" style="background:var(--accent);color:white">📌</span>
            <span v-if="thread.is_locked" class="text-xs" style="color:var(--text-faint)">🔒</span>
            <Link :href="route('thread.show', thread.slug)"
                  class="font-medium text-sm hover:underline" style="color:var(--text)">
              {{ thread.title }}
            </Link>
          </div>
          <div class="text-xs mt-0.5" style="color:var(--text-faint)">
            by <span style="color:var(--text-muted)">{{ thread.user?.username }}</span>
          </div>
        </div>
        <div class="text-xs text-right shrink-0 hidden sm:block" style="color:var(--text-faint)">
          <div>{{ thread.reply_count }} replies</div>
          <div>{{ thread.views }} views</div>
        </div>
        <div v-if="thread.last_post" class="text-xs text-right shrink-0 hidden md:block w-28" style="color:var(--text-muted)">
          <div>{{ thread.last_post.user?.username }}</div>
          <div style="color:var(--text-faint)">{{ thread.last_post.created_at }}</div>
        </div>
      </div>
    </div>

    <Pagination :links="threads.links" />
  </AppLayout>
</template>
