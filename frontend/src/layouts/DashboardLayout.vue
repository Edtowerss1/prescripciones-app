<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const { user, logout, userRole } = useAuth()
const router = useRouter()
const sidebarOpen = ref(false)

const roleLinks: Record<string, Array<{ label: string; to: string }>> = {
  doctor: [
    { label: 'Prescriptions', to: '/doctor/prescriptions' },
    { label: 'New Prescription', to: '/doctor/prescriptions/new' },
  ],
  patient: [{ label: 'My Prescriptions', to: '/patient/prescriptions' }],
  admin: [
    { label: 'Dashboard', to: '/admin' },
    { label: 'All Prescriptions', to: '/admin/prescriptions' },
  ],
}

const links = computed(() => roleLinks[userRole.value ?? ''] ?? [])

async function handleLogout() {
  await logout()
  router.push('/login')
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-white shadow-sm transition-transform duration-200 lg:static lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-16 items-center border-b px-6">
        <h2 class="text-lg font-semibold text-gray-900">Prescripciones</h2>
      </div>
      <nav class="flex-1 space-y-1 px-3 py-4">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900"
          active-class="bg-indigo-50 text-indigo-700"
          @click="sidebarOpen = false"
        >
          {{ link.label }}
        </router-link>
      </nav>
    </aside>

    <!-- Main content -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <!-- Top bar -->
      <header class="flex h-16 items-center justify-between border-b bg-white px-4 lg:px-6">
        <button
          class="rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
          @click="toggleSidebar"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>

        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-700">{{ user?.name }}</span>
          <button
            class="rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </header>

      <!-- Content slot -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
