<script setup>
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ users: Object, groups: Array, filters: Object })

const search = ref(props.filters?.search || '')
watch(search, (val) => {
    router.get(route('admin.users.index'), { search: val }, { preserveState: true, replace: true })
})

function updateGroup(user, groupId) {
    useForm({ group_id: groupId || null }).put(route('admin.users.update', user.id))
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="font-semibold text-sm" style="color:var(--text)">Users</h1>
    </template>

    <div class="mb-4">
      <input v-model="search" placeholder="Search username or email..."
             class="w-full max-w-xs px-3 py-2 rounded-lg text-sm outline-none"
             style="background:var(--surface);border:1px solid var(--border);color:var(--text)" />
    </div>

    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
      <div v-if="users.data.length === 0" class="px-5 py-4 text-sm" style="color:var(--text-muted)">
        No users found.
      </div>
      <table v-else class="w-full text-sm">
        <thead style="background:var(--surface-raised)">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">User</th>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">Group</th>
            <th class="text-left px-4 py-3 text-xs font-semibold" style="color:var(--text-muted)">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(user, i) in users.data" :key="user.id"
              :style="i > 0 ? 'border-top:1px solid var(--border)' : ''">
            <td class="px-4 py-3">
              <div class="font-medium" style="color:var(--text)">{{ user.username }}</div>
              <div class="text-xs" style="color:var(--text-faint)">{{ user.email }}</div>
            </td>
            <td class="px-4 py-3">
              <select @change="updateGroup(user, $event.target.value)"
                      :value="user.group_id"
                      class="px-2 py-1 rounded text-xs outline-none"
                      style="background:var(--bg);border:1px solid var(--border);color:var(--text)">
                <option value="">None</option>
                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
            </td>
            <td class="px-4 py-3">
              <span v-if="user.banned_at"
                    class="text-xs px-2 py-0.5 rounded"
                    style="background:#ef4444;color:white">Banned</span>
              <span v-else class="text-xs" style="color:var(--text-faint)">Active</span>
            </td>
            <td class="px-4 py-3 text-right">
              <Link v-if="!user.banned_at"
                    :href="route('admin.users.ban', user.id)"
                    method="post"
                    as="button"
                    class="text-xs"
                    style="color:var(--danger)">Ban</Link>
              <Link v-else
                    :href="route('admin.users.unban', user.id)"
                    method="post"
                    as="button"
                    class="text-xs"
                    style="color:var(--success)">Unban</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :links="users.links" />
  </AdminLayout>
</template>
