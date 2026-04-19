<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import PostCard from '@/Components/Post/PostCard.vue'
import MarkdownEditor from '@/Components/Post/MarkdownEditor.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({ thread: Object, posts: Object })
const auth = computed(() => usePage().props.auth)
const replyForm = useForm({ body: '' })

function submitReply() {
    replyForm.post(route('post.store', props.thread.slug), {
        onSuccess: () => replyForm.reset()
    })
}
</script>
<template>
  <AppLayout>
    <Head :title="thread.title" />
    <!-- Breadcrumb -->
    <div class="text-xs mb-4" style="color:var(--text-faint)">
      <Link :href="route('forum.index')" class="hover:underline" style="color:var(--accent)">Forums</Link>
      →
      <Link :href="route('forum.show', thread.forum_id)" class="hover:underline" style="color:var(--accent)">
        {{ thread.forum?.name }}
      </Link>
      → {{ thread.title }}
    </div>

    <!-- Thread title + badges -->
    <div class="mb-6">
      <div class="flex items-center gap-2 mb-1">
        <span v-if="thread.is_pinned" class="text-xs px-2 py-0.5 rounded text-white" style="background:var(--accent)">📌 Pinned</span>
        <span v-if="thread.is_locked" class="text-xs px-2 py-0.5 rounded" style="background:var(--surface-raised);color:var(--text-muted)">🔒 Locked</span>
      </div>
      <h1 class="text-xl font-bold" style="color:var(--text)">{{ thread.title }}</h1>
    </div>

    <!-- Posts -->
    <div class="rounded-xl px-6 py-5" style="background:var(--surface);border:1px solid var(--border)">
      <PostCard v-for="(post, i) in posts.data" :key="post.id" :post="post" :isFirst="i === 0" />
    </div>

    <Pagination :links="posts.links" />

    <!-- Reply form -->
    <div v-if="auth.user && !thread.is_locked" class="mt-8">
      <h3 class="text-sm font-semibold mb-3" style="color:var(--text)">Post a reply</h3>
      <form @submit.prevent="submitReply" class="space-y-3">
        <MarkdownEditor v-model="replyForm.body" />
        <p v-if="replyForm.errors.body" class="text-red-400 text-xs">{{ replyForm.errors.body }}</p>
        <button type="submit" :disabled="replyForm.processing"
                class="px-5 py-2 rounded-lg text-sm text-white font-medium"
                style="background:var(--accent-gradient)">
          Post Reply
        </button>
      </form>
    </div>
    <div v-else-if="!auth.user" class="mt-8 text-sm" style="color:var(--text-muted)">
      <Link :href="route('login')" style="color:var(--accent)">Log in</Link> to reply.
    </div>
    <div v-else-if="thread.is_locked" class="mt-8 text-sm" style="color:var(--text-muted)">
      This thread is locked.
    </div>
  </AppLayout>
</template>
