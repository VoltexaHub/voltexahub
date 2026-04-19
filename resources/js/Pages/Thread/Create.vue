<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import MarkdownEditor from '@/Components/Post/MarkdownEditor.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ forum: Object })
const form = useForm({ title: '', body: '' })

function submit() {
    form.post(route('thread.store', props.forum.id))
}
</script>
<template>
  <AppLayout>
    <Head title="New Thread" />
    <div class="max-w-3xl">
      <div class="text-xs mb-4" style="color:var(--text-faint)">
        <Link :href="route('forum.index')" class="hover:underline" style="color:var(--accent)">Forums</Link>
        →
        <Link :href="route('forum.show', forum.id)" class="hover:underline" style="color:var(--accent)">{{ forum.name }}</Link>
        → New Thread
      </div>
      <h1 class="text-xl font-bold mb-6" style="color:var(--text)">New Thread in {{ forum.name }}</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Title</label>
          <input v-model="form.title" type="text" required maxlength="200"
                 class="w-full px-3 py-2 rounded-lg text-sm outline-none focus:ring-2 ring-purple-500"
                 style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
          <p v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</p>
        </div>
        <div>
          <label class="block text-sm mb-1" style="color:var(--text-muted)">Body</label>
          <MarkdownEditor v-model="form.body" />
          <p v-if="form.errors.body" class="text-red-400 text-xs mt-1">{{ form.errors.body }}</p>
        </div>
        <button type="submit" :disabled="form.processing"
                class="px-6 py-2 rounded-lg text-sm text-white font-medium"
                style="background:var(--accent-gradient)">
          Post Thread
        </button>
      </form>
    </div>
  </AppLayout>
</template>
