<script setup>
import { ref, computed } from 'vue'
import 'highlight.js/styles/github-dark.css'
import { renderMarkdown } from '@/lib/markdown.js'

const props = defineProps({
    modelValue: String,
    placeholder: { type: String, default: 'Write your post...' }
})
const emit = defineEmits(['update:modelValue'])

const tab = ref('write')
const preview = computed(() => renderMarkdown(props.modelValue || ''))

function insert(before, after = '') {
    const ta = document.getElementById('md-editor')
    if (!ta) return
    const start = ta.selectionStart
    const end = ta.selectionEnd
    const value = props.modelValue || ''
    const newVal = value.substring(0, start) + before + value.substring(start, end) + after + value.substring(end)
    emit('update:modelValue', newVal)
    ta.focus()
}
</script>
<template>
  <div class="rounded-lg overflow-hidden" style="border:1px solid var(--border)">
    <!-- Toolbar -->
    <div class="flex items-center gap-1 px-3 py-2" style="background:var(--surface-raised);border-bottom:1px solid var(--border)">
      <button type="button" @click="insert('**','**')" class="px-2 py-1 rounded text-xs font-bold hover:bg-white/10" style="color:var(--text-muted)">B</button>
      <button type="button" @click="insert('*','*')" class="px-2 py-1 rounded text-xs italic hover:bg-white/10" style="color:var(--text-muted)">I</button>
      <button type="button" @click="insert('\`','\`')" class="px-2 py-1 rounded text-xs font-mono hover:bg-white/10" style="color:var(--text-muted)">`</button>
      <button type="button" @click="insert('\n\`\`\`\n','\n\`\`\`')" class="px-2 py-1 rounded text-xs font-mono hover:bg-white/10" style="color:var(--text-muted)">{ }</button>
      <button type="button" @click="insert('[','](url)')" class="px-2 py-1 rounded text-xs hover:bg-white/10" style="color:var(--text-muted)">🔗</button>
      <div class="ml-auto flex gap-1">
        <button type="button" @click="tab = 'write'"
                class="px-3 py-1 rounded text-xs transition-colors"
                :style="tab === 'write' ? 'background:var(--accent);color:white' : 'color:var(--text-muted)'">Write</button>
        <button type="button" @click="tab = 'preview'"
                class="px-3 py-1 rounded text-xs transition-colors"
                :style="tab === 'preview' ? 'background:var(--accent);color:white' : 'color:var(--text-muted)'">Preview</button>
      </div>
    </div>
    <!-- Editor / Preview -->
    <textarea v-if="tab === 'write'" id="md-editor"
              :value="modelValue" @input="emit('update:modelValue', $event.target.value)"
              :placeholder="placeholder" rows="10"
              class="w-full px-4 py-3 text-sm resize-y outline-none font-mono"
              style="background:var(--surface);color:var(--text);min-height:160px" />
    <div v-else class="px-4 py-3 prose prose-invert max-w-none text-sm min-h-40"
         style="background:var(--surface);color:var(--text)"
         v-html="preview || '&lt;span style=\'opacity:0.4\'&gt;Nothing to preview&lt;/span&gt;'" />
  </div>
</template>
