<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({ groups: Array })

const PERMISSION_KEYS = [
    { key: 'can_post',             label: 'Can post' },
    { key: 'can_create_thread',    label: 'Can create thread' },
    { key: 'can_upload_avatar',    label: 'Can upload avatar' },
    { key: 'can_use_signature',    label: 'Can use signature' },
    { key: 'can_react',            label: 'Can react' },
    { key: 'is_moderator',         label: 'Moderator' },
    { key: 'is_admin',             label: 'Administrator' },
]

function defaultPerms() {
    return Object.fromEntries(PERMISSION_KEYS.map(p => [p.key, false]))
}

const createForm = useForm({
    name: '',
    color: '#7c3aed',
    icon: '',
    is_staff: false,
    permissions: defaultPerms(),
})

function submitCreate() {
    createForm.post(route('admin.groups.store'), { onSuccess: () => createForm.reset() })
}

const editingId = ref(null)
const editForm = useForm({
    name: '',
    color: '#7c3aed',
    icon: '',
    is_staff: false,
    permissions: defaultPerms(),
})

function startEdit(group) {
    editingId.value = group.id
    editForm.name = group.name
    editForm.color = group.color
    editForm.icon = group.icon || ''
    editForm.is_staff = group.is_staff
    editForm.permissions = { ...defaultPerms(), ...(group.permissions || {}) }
}

function submitEdit(group) {
    editForm.put(route('admin.groups.update', group.id), {
        onSuccess: () => { editingId.value = null },
    })
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="font-semibold text-sm" style="color:var(--text)">Groups</h1>
    </template>

    <!-- Create form -->
    <div class="rounded-xl p-5 mb-6" style="background:var(--surface);border:1px solid var(--border)">
      <h2 class="text-sm font-semibold mb-4" style="color:var(--text)">New Group</h2>
      <form @submit.prevent="submitCreate" class="space-y-4">
        <div class="flex gap-3 flex-wrap">
          <input v-model="createForm.name" placeholder="Name" required
                 class="flex-1 min-w-32 px-3 py-2 rounded-lg text-sm outline-none"
                 style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
          <div class="flex items-center gap-2">
            <label class="text-xs" style="color:var(--text-muted)">Color</label>
            <input type="color" v-model="createForm.color"
                   class="w-9 h-9 rounded cursor-pointer border-0 p-0.5"
                   style="background:var(--bg);border:1px solid var(--border)" />
          </div>
          <input v-model="createForm.icon" placeholder="Icon (emoji)" maxlength="10"
                 class="w-28 px-3 py-2 rounded-lg text-sm outline-none"
                 style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="createForm.is_staff" class="rounded" />
            <span class="text-xs" style="color:var(--text-muted)">Staff</span>
          </label>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-2">
          <label v-for="p in PERMISSION_KEYS" :key="p.key"
                 class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" v-model="createForm.permissions[p.key]" class="rounded" />
            <span class="text-xs" style="color:var(--text-muted)">{{ p.label }}</span>
          </label>
        </div>
        <p v-if="createForm.errors.name" class="text-xs" style="color:var(--danger)">{{ createForm.errors.name }}</p>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white"
                style="background:var(--accent-gradient)">Create Group</button>
      </form>
    </div>

    <!-- Group list -->
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="groups.length === 0" class="px-5 py-4 text-sm" style="color:var(--text-muted)">
        No groups yet.
      </div>
      <div v-for="(group, i) in groups" :key="group.id"
           class="px-5 py-4"
           :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">

        <!-- Edit mode -->
        <template v-if="editingId === group.id">
          <form @submit.prevent="submitEdit(group)" class="space-y-3">
            <div class="flex gap-3 flex-wrap">
              <input v-model="editForm.name" placeholder="Name" required
                     class="flex-1 min-w-32 px-3 py-2 rounded-lg text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <div class="flex items-center gap-2">
                <label class="text-xs" style="color:var(--text-muted)">Color</label>
                <input type="color" v-model="editForm.color"
                       class="w-9 h-9 rounded cursor-pointer border-0 p-0.5"
                       style="background:var(--bg);border:1px solid var(--border)" />
              </div>
              <input v-model="editForm.icon" placeholder="Icon (emoji)" maxlength="10"
                     class="w-28 px-3 py-2 rounded-lg text-sm outline-none"
                     style="background:var(--bg);border:1px solid var(--border);color:var(--text)" />
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="editForm.is_staff" class="rounded" />
                <span class="text-xs" style="color:var(--text-muted)">Staff</span>
              </label>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
              <label v-for="p in PERMISSION_KEYS" :key="p.key"
                     class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" v-model="editForm.permissions[p.key]" class="rounded" />
                <span class="text-xs" style="color:var(--text-muted)">{{ p.label }}</span>
              </label>
            </div>
            <div class="flex gap-2">
              <button type="submit" class="px-3 py-1 rounded text-xs text-white"
                      style="background:var(--accent)">Save</button>
              <button type="button" @click="editingId = null"
                      class="px-3 py-1 rounded text-xs"
                      style="color:var(--text-muted)">Cancel</button>
            </div>
          </form>
        </template>

        <!-- View mode -->
        <template v-else>
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full shrink-0" :style="{ background: group.color }"></div>
            <span v-if="group.icon" class="text-base leading-none">{{ group.icon }}</span>
            <span class="text-sm font-medium" style="color:var(--text)">{{ group.name }}</span>
            <span v-if="group.is_staff" class="text-xs px-1.5 py-0.5 rounded"
                  style="background:var(--surface-raised);color:var(--text-muted)">Staff</span>
            <span class="text-xs" style="color:var(--text-faint)">{{ group.users_count }} member{{ group.users_count !== 1 ? 's' : '' }}</span>
            <div class="ml-auto flex gap-3 shrink-0">
              <button @click="startEdit(group)" class="text-xs" style="color:var(--text-muted)">Edit</button>
              <Link :href="route('admin.groups.destroy', group.id)" method="delete" as="button"
                    class="text-xs" style="color:var(--danger)">Delete</Link>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AdminLayout>
</template>
