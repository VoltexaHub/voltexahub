<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({ categories: Array })

const form = useForm({ name: '', description: '' })
const editingId = ref(null)
const editForm = useForm({ name: '', description: '' })

function startEdit(cat) {
    editingId.value = cat.id
    editForm.name = cat.name
    editForm.description = cat.description || ''
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="font-semibold text-sm" style="color:var(--text)">Categories</h1>
    </template>

    <!-- Create form -->
    <div class="rounded-xl p-5 mb-6" style="background:var(--surface);border:1px solid var(--border)">
      <h2 class="text-sm font-semibold mb-4" style="color:var(--text)">New Category</h2>
      <form @submit.prevent="form.post(route('admin.categories.store'), { onSuccess: () => form.reset() })"
            class="flex gap-3">
        <input v-model="form.name" placeholder="Name" required
               class="flex-1 px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
        <input v-model="form.description" placeholder="Description (optional)"
               class="flex-1 px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
        <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white"
                style="background:var(--accent-gradient)">Add</button>
      </form>
      <p v-if="form.errors.name" class="mt-2 text-xs" style="color:var(--danger)">{{ form.errors.name }}</p>
    </div>

    <!-- List -->
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="categories.length === 0" class="px-5 py-4 text-sm" style="color:var(--text-muted)">
        No categories yet.
      </div>
      <div v-for="(cat, i) in categories" :key="cat.id"
           class="flex items-center gap-4 px-5 py-3"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
        <div class="flex-1">
          <template v-if="editingId === cat.id">
            <div class="flex gap-2 flex-wrap">
              <input v-model="editForm.name" class="px-2 py-1 rounded text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <input v-model="editForm.description" placeholder="Description"
                     class="px-2 py-1 rounded text-sm outline-none flex-1"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <button @click="editForm.put(route('admin.categories.update', cat.id), { onSuccess: () => editingId = null })"
                      class="px-3 py-1 rounded text-xs text-white" style="background:var(--accent)">Save</button>
              <button @click="editingId = null" class="px-3 py-1 rounded text-xs"
                      style="color:var(--text-muted)">Cancel</button>
            </div>
          </template>
          <template v-else>
            <span class="text-sm font-medium" style="color:var(--text)">{{ cat.name }}</span>
            <span v-if="cat.description" class="ml-2 text-xs" style="color:var(--text-muted)">{{ cat.description }}</span>
            <span class="ml-2 text-xs" style="color:var(--text-faint)">{{ cat.forums_count }} forum{{ cat.forums_count !== 1 ? 's' : '' }}</span>
          </template>
        </div>
        <div class="flex gap-3 shrink-0" v-if="editingId !== cat.id">
          <button @click="startEdit(cat)" class="text-xs" style="color:var(--text-muted)">Edit</button>
          <Link :href="route('admin.categories.destroy', cat.id)" method="delete" as="button"
                class="text-xs" style="color:var(--danger)">Delete</Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
