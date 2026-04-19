<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
defineProps({ categories: Array, siteName: String })
</script>
<template>
  <AppLayout>
    <Head :title="siteName" />
    <div class="space-y-8">
      <div v-for="category in categories" :key="category.id">
        <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--accent)">
          {{ category.name }}
        </h2>
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
          <div v-for="(forum, i) in category.forums" :key="forum.id"
               class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-white/5"
               :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
            <div class="text-2xl w-8 text-center">{{ forum.icon || '💬' }}</div>
            <div class="flex-1 min-w-0">
              <Link :href="route('forum.show', forum.id)"
                    class="font-semibold text-sm hover:underline"
                    style="color:var(--text)">{{ forum.name }}</Link>
              <p v-if="forum.description" class="text-xs mt-0.5 truncate" style="color:var(--text-muted)">
                {{ forum.description }}
              </p>
            </div>
            <div class="text-right shrink-0 hidden sm:block">
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.thread_count }} threads</div>
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.post_count }} posts</div>
            </div>
            <div v-if="forum.last_post" class="text-right shrink-0 hidden md:block max-w-40">
              <div class="text-xs truncate" style="color:var(--text-muted)">
                <Link v-if="forum.last_post.thread?.slug"
                      :href="route('thread.show', forum.last_post.thread.slug)"
                      class="hover:underline" style="color:var(--accent)">
                  {{ forum.last_post.user?.username }}
                </Link>
              </div>
              <div class="text-xs" style="color:var(--text-faint)">{{ forum.last_post.created_at }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
