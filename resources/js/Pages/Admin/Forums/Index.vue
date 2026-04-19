<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    forums: Array,
    categories: Array,
})

const form = useForm({ category_id: '', name: '', description: '', icon: '' })
const editingId = ref(null)
const editForm = useForm({ category_id: '', name: '', description: '', icon: '' })

function startEdit(forum) {
    editingId.value = forum.id
    editForm.category_id = forum.category_id
    editForm.name = forum.name
    editForm.description = forum.description || ''
    editForm.icon = forum.icon || ''
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="font-semibold text-sm" style="color:var(--text)">Forums</h1>
    </template>

    <!-- Create form -->
    <div class="rounded-xl p-5 mb-6" style="background:var(--surface);border:1px solid var(--border)">
      <h2 class="text-sm font-semibold mb-4" style="color:var(--text)">New Forum</h2>
      <form @submit.prevent="form.post(route('admin.forums.store'), { onSuccess: () => form.reset() })"
            class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <!-- Category select -->
        <select v-model="form.category_id" required
                class="px-3 py-2 rounded-lg text-sm outline-none"
                style="background:var(--bg);border:1px solid var(--border);color:var(--text)">
          <option value="" disabled>Select category…</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>

        <!-- Name -->
        <input v-model="form.name" placeholder="Forum name" required
               class="px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />

        <!-- Description -->
        <input v-model="form.description" placeholder="Description (optional)"
               class="sm:col-span-2 px-3 py-2 rounded-lg text-sm outline-none"
               style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />

        <!-- Icon + submit row -->
        <div class="flex gap-3 sm:col-span-2">
          <input v-model="form.icon" placeholder="Icon emoji (optional)" maxlength="10"
                 class="w-40 px-3 py-2 rounded-lg text-sm outline-none"
                 style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
          <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white"
                  style="background:var(--accent-gradient)">Add Forum</button>
        </div>
      </form>
      <p v-if="form.errors.category_id" class="mt-2 text-xs" style="color:var(--danger)">{{ form.errors.category_id }}</p>
      <p v-if="form.errors.name" class="mt-2 text-xs" style="color:var(--danger)">{{ form.errors.name }}</p>
    </div>

    <!-- List -->
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="forums.length === 0" class="px-5 py-4 text-sm" style="color:var(--text-muted)">
        No forums yet.
      </div>
      <div v-for="(forum, i) in forums" :key="forum.id"
           class="flex items-center gap-4 px-5 py-3"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
        <!-- Icon badge -->
        <span v-if="forum.icon" class="text-base shrink-0">{{ forum.icon }}</span>

        <div class="flex-1 min-w-0">
          <template v-if="editingId === forum.id">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
              <select v-model="editForm.category_id" required
                      class="px-2 py-1 rounded text-sm outline-none"
                      style="background:var(--bg);border:1px solid var(--border);color:var(--text)">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <input v-model="editForm.name" placeholder="Name" required
                     class="px-2 py-1 rounded text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <input v-model="editForm.description" placeholder="Description"
                     class="sm:col-span-2 px-2 py-1 rounded text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <div class="flex gap-2 sm:col-span-2">
                <input v-model="editForm.icon" placeholder="Icon emoji" maxlength="10"
                       class="w-32 px-2 py-1 rounded text-sm outline-none"
                       style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
                <button @click="editForm.put(route('admin.forums.update', forum.id), { onSuccess: () => editingId = null })"
                        class="px-3 py-1 rounded text-xs text-white" style="background:var(--accent)">Save</button>
                <button @click="editingId = null" class="px-3 py-1 rounded text-xs"
                        style="color:var(--text-muted)">Cancel</button>
              </div>
            </div>
          </template>
          <template v-else>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-medium" style="color:var(--text)">{{ forum.name }}</span>
              <span class="text-xs px-1.5 py-0.5 rounded"
                    style="background:var(--surface-raised);color:var(--text-muted)">{{ forum.category?.name }}</span>
              <span class="text-xs" style="color:var(--text-faint)">{{ forum.threads_count }} thread{{ forum.threads_count !== 1 ? 's' : '' }}</span>
            </div>
            <p v-if="forum.description" class="text-xs mt-0.5 truncate" style="color:var(--text-muted)">{{ forum.description }}</p>
          </template>
        </div>

        <div class="flex gap-3 shrink-0" v-if="editingId !== forum.id">
          <button @click="startEdit(forum)" class="text-xs" style="color:var(--text-muted)">Edit</button>
          <Link :href="route('admin.forums.destroy', forum.id)" method="delete" as="button"
                class="text-xs" style="color:var(--danger)">Delete</Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
