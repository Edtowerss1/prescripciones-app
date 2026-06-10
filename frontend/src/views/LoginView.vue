<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { login, isLoading, error: authError } = useAuth()

const email = ref('')
const password = ref('')
const localError = ref<string | null>(null)

const roleHomeMap: Record<string, string> = {
  admin: '/admin',
  doctor: '/doctor/prescriptions',
  patient: '/patient/prescriptions',
}

async function handleSubmit() {
  if (!email.value || !password.value) {
    localError.value = 'Email and password are required.'
    return
  }

  localError.value = null

  try {
    const user = await login({ email: email.value, password: password.value })
    const home = roleHomeMap[user.role]
    if (home) {
      router.push(home)
    }
  } catch {
    // Error is already set in the store
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="handleSubmit">
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
      <input
        id="email"
        v-model="email"
        type="email"
        autocomplete="email"
        required
        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        placeholder="doctor@example.com"
        :disabled="isLoading"
      />
    </div>

    <div>
      <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
      <input
        id="password"
        v-model="password"
        type="password"
        autocomplete="current-password"
        required
        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        placeholder="Enter your password"
        :disabled="isLoading"
      />
    </div>

    <div
      v-if="localError || authError"
      class="rounded-md bg-red-50 p-3 text-sm text-red-700"
    >
      {{ localError || authError }}
    </div>

    <button
      type="submit"
      :disabled="isLoading"
      class="flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
    >
      <svg
        v-if="isLoading"
        class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
        />
      </svg>
      {{ isLoading ? 'Signing in...' : 'Sign in' }}
    </button>
  </form>
</template>
