<script setup>
import { computed } from 'vue'
import ThemeToggle from '@/Components/UI/ThemeToggle.vue'
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const auth = computed(() => page.props.auth)
</script>
<template>
  <nav style="background:var(--surface);border-bottom:1px solid var(--border)"
       class="sticky top-0 z-50 px-4 h-14 flex items-center gap-4">
    <Link :href="route('forum.index')" class="flex items-center gap-2 shrink-0">
      <span class="w-7 h-7 rounded-md flex items-center justify-center text-white text-xs font-bold"
            style="background:var(--accent-gradient)">V</span>
      <span style="color:var(--text)" class="font-bold tracking-wide text-sm hidden sm:block">VOLTEXAHUB</span>
    </Link>

    <div class="flex items-center gap-5 text-sm ml-2" style="color:var(--text-muted)">
      <Link :href="route('forum.index')" class="transition-colors hover:opacity-100" style="color:var(--text-muted)" @mouseenter="$event.target.style.color='var(--text)'" @mouseleave="$event.target.style.color='var(--text-muted)'">Forums</Link>
      <Link :href="route('members.index')" class="transition-colors" style="color:var(--text-muted)" @mouseenter="$event.target.style.color='var(--text)'" @mouseleave="$event.target.style.color='var(--text-muted)'">Members</Link>
      <Link :href="route('staff')" class="transition-colors" style="color:var(--text-muted)" @mouseenter="$event.target.style.color='var(--text)'" @mouseleave="$event.target.style.color='var(--text-muted)'">Staff</Link>
      <Link :href="route('groups.index')" class="transition-colors" style="color:var(--text-muted)" @mouseenter="$event.target.style.color='var(--text)'" @mouseleave="$event.target.style.color='var(--text-muted)'">Groups</Link>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <ThemeToggle />
      <template v-if="auth?.user">
        <span style="color:var(--text-muted)" class="text-sm">{{ auth.user.username }}</span>
        <Link :href="route('logout')" method="post" as="button"
              class="text-sm px-3 py-1 rounded" style="color:var(--text-muted)">Logout</Link>
      </template>
      <template v-else>
        <Link :href="route('login')" class="text-sm px-3 py-1 rounded hover:bg-white/10 transition-colors"
              style="color:var(--text-muted)">Login</Link>
        <Link :href="route('register')" class="text-sm px-4 py-1.5 rounded-md text-white font-medium"
              style="background:var(--accent-gradient)">Register</Link>
      </template>
    </div>
  </nav>
</template>
