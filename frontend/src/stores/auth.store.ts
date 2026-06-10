import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as authApi from '@/api/auth.api'
import type { User } from '@/types/user'
import type { LoginRequest } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('access_token'))
  const user = ref<User | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.role ?? null)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isDoctor = computed(() => user.value?.role === 'doctor')
  const isPatient = computed(() => user.value?.role === 'patient')

  async function login(credentials: LoginRequest): Promise<User> {
    isLoading.value = true
    error.value = null
    try {
      const res = await authApi.login(credentials)
      token.value = res.access_token
      localStorage.setItem('access_token', res.access_token)
      user.value = res.user
      return res.user
    } catch (err: any) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        'Login failed. Please check your credentials.'
      error.value = message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchProfile(): Promise<User | null> {
    if (!token.value) return null
    isLoading.value = true
    try {
      const u = await authApi.profile()
      user.value = u
      return u
    } catch {
      token.value = null
      user.value = null
      localStorage.removeItem('access_token')
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } catch {
      // Even if the API call fails, clear local state
    } finally {
      token.value = null
      user.value = null
      error.value = null
      localStorage.removeItem('access_token')
    }
  }

  return {
    token,
    user,
    isLoading,
    error,
    isAuthenticated,
    userRole,
    isAdmin,
    isDoctor,
    isPatient,
    login,
    fetchProfile,
    logout,
  }
})
