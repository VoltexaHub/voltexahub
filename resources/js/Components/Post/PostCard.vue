<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import MarkdownEditor from './MarkdownEditor.vue'
import { marked } from 'marked'
import hljs from 'highlight.js'

marked.use({
    renderer: {
        code(token) {
            const lang = token.lang || 'plaintext'
            const language = hljs.getLanguage(lang) ? lang : 'plaintext'
            const highlighted = hljs.highlight(token.text, { language }).value
            return `<pre><code class="hljs language-${language}">${highlighted}</code></pre>`
        }
    }
})

const props = defineProps({ post: Object, isFirst: Boolean })
const auth = computed(() => usePage().props.auth)
const editing = ref(false)
const editForm = useForm({ body: props.post.body })
const renderedBody = computed(() => marked.parse(props.post.body || ''))

function saveEdit() {
    editForm.put(route('post.update', props.post.id), {
        onSuccess: () => { editing.value = false }
    })
}
</script>
<template>
  <div class="flex gap-4"
       :style="!isFirst ? 'border-top:1px solid var(--border);padding-top:1.5rem;margin-top:1.5rem' : ''">
    <!-- User column -->
    <div class="w-28 shrink-0 text-center hidden sm:block">
      <img v-if="post.user?.avatar" :src="post.user.avatar"
           class="w-14 h-14 rounded-full mx-auto mb-2 object-cover" />
      <div v-else class="w-14 h-14 rounded-full mx-auto mb-2 flex items-center justify-center text-lg font-bold text-white"
           :style="`background:${post.user?.group?.color || 'var(--accent)'}`">
        {{ post.user?.username?.[0]?.toUpperCase() }}
      </div>
      <div class="text-xs font-semibold" :style="`color:${post.user?.group?.color || 'var(--text)'}`">
        {{ post.user?.username }}
      </div>
      <div class="text-xs mt-0.5" style="color:var(--text-faint)">{{ post.user?.group?.name }}</div>
      <div class="text-xs mt-1" style="color:var(--text-faint)">Posts: {{ post.user?.post_count }}</div>
      <div v-if="post.user?.credits" class="mt-1 text-xs px-2 py-0.5 rounded-full inline-block text-white"
           style="background:var(--accent-gradient)">
        ⭐ {{ post.user.credits }}cr
      </div>
    </div>
    <!-- Content column -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs" style="color:var(--text-faint)">{{ post.created_at }}</span>
        <span v-if="post.edited_at" class="text-xs italic" style="color:var(--text-faint)">(edited)</span>
        <div v-if="auth.user?.id === post.user_id || auth.user?.is_moderator" class="ml-auto flex gap-2">
          <button @click="editing = !editing" class="text-xs hover:underline" style="color:var(--text-faint)">Edit</button>
          <Link :href="route('post.destroy', post.id)" method="delete" as="button"
                class="text-xs hover:underline" style="color:var(--danger)">Delete</Link>
        </div>
      </div>
      <div v-if="!editing"
           class="prose prose-invert max-w-none text-sm leading-relaxed"
           style="color:var(--text)" v-html="renderedBody" />
      <div v-else class="space-y-3">
        <MarkdownEditor v-model="editForm.body" />
        <p v-if="editForm.errors.body" class="text-red-400 text-xs">{{ editForm.errors.body }}</p>
        <div class="flex gap-2">
          <button @click="saveEdit" :disabled="editForm.processing"
                  class="px-4 py-1.5 rounded text-sm text-white" style="background:var(--accent)">Save</button>
          <button @click="editing = false"
                  class="px-4 py-1.5 rounded text-sm" style="color:var(--text-muted)">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
