import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth.store'

// Mock the auth API module
vi.mock('@/api/auth.api', () => ({
  login: vi.fn(),
  profile: vi.fn(),
  logout: vi.fn(),
}))

import * as authApi from '@/api/auth.api'

const mockUser = {
  id: 1,
  name: 'Dr. Test',
  email: 'dr@test.com',
  role: 'doctor' as const,
}

const mockAdminUser = {
  id: 2,
  name: 'Admin Test',
  email: 'admin@test.com',
  role: 'admin' as const,
}

const mockPatientUser = {
  id: 3,
  name: 'Patient Test',
  email: 'patient@test.com',
  role: 'patient' as const,
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('has null token and user when no token in localStorage', () => {
      const store = useAuthStore()
      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
    })

    it('is not authenticated when no token', () => {
      const store = useAuthStore()
      expect(store.isAuthenticated).toBe(false)
    })

    it('has null role when no user', () => {
      const store = useAuthStore()
      expect(store.userRole).toBeNull()
    })

    it('reads token from localStorage on init', () => {
      localStorage.setItem('access_token', 'existing-token')
      const store = useAuthStore()
      expect(store.token).toBe('existing-token')
    })
  })

  describe('login', () => {
    it('stores token and user on successful login', async () => {
      const mockedLogin = vi.mocked(authApi.login)
      mockedLogin.mockResolvedValue({
        access_token: 'test-token-123',
        token_type: 'Bearer',
        user: mockUser,
      })

      const store = useAuthStore()
      const result = await store.login({ email: 'dr@test.com', password: 'password' })

      expect(mockedLogin).toHaveBeenCalledWith({ email: 'dr@test.com', password: 'password' })
      expect(store.token).toBe('test-token-123')
      expect(store.user).toEqual(mockUser)
      expect(store.isAuthenticated).toBe(true)
      expect(localStorage.getItem('access_token')).toBe('test-token-123')
      expect(result).toEqual(mockUser)
    })

    it('sets error and throws on failed login', async () => {
      const mockedLogin = vi.mocked(authApi.login)
      const apiError = new Error('Invalid credentials')
      ;(apiError as any).response = { data: { message: 'Invalid credentials' } }
      mockedLogin.mockRejectedValue(apiError)

      const store = useAuthStore()
      await expect(store.login({ email: 'wrong@test.com', password: 'wrong' })).rejects.toThrow()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(store.error).toBe('Invalid credentials')
    })

    it('sets error from exception message when server returns none', async () => {
      const mockedLogin = vi.mocked(authApi.login)
      mockedLogin.mockRejectedValue(new Error('Network Error'))

      const store = useAuthStore()
      await expect(store.login({ email: 'dr@test.com', password: 'password' })).rejects.toThrow()

      // The store uses err?.message as fallback when no response.data.message
      expect(store.error).toBe('Network Error')
    })
  })

  describe('logout', () => {
    it('clears token and user on logout', async () => {
      localStorage.setItem('access_token', 'test-token')
      const store = useAuthStore()
      // Simulate logged-in state
      store.token = 'test-token'
      store.user = mockUser

      const mockedLogout = vi.mocked(authApi.logout)
      mockedLogout.mockResolvedValue()

      await store.logout()

      expect(mockedLogout).toHaveBeenCalled()
      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(store.error).toBeNull()
      expect(localStorage.getItem('access_token')).toBeNull()
    })

    it('clears state even when logout API fails', async () => {
      localStorage.setItem('access_token', 'test-token')
      const store = useAuthStore()
      store.token = 'test-token'
      store.user = mockUser

      const mockedLogout = vi.mocked(authApi.logout)
      mockedLogout.mockRejectedValue(new Error('Network error'))

      await store.logout()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(localStorage.getItem('access_token')).toBeNull()
    })
  })

  describe('fetchProfile', () => {
    it('fetches and sets user profile', async () => {
      localStorage.setItem('access_token', 'test-token')
      const store = useAuthStore()
      store.token = 'test-token'

      const mockedProfile = vi.mocked(authApi.profile)
      mockedProfile.mockResolvedValue(mockUser)

      const result = await store.fetchProfile()

      expect(mockedProfile).toHaveBeenCalled()
      expect(store.user).toEqual(mockUser)
      expect(result).toEqual(mockUser)
    })

    it('returns null when no token exists', async () => {
      const store = useAuthStore()
      const result = await store.fetchProfile()
      expect(result).toBeNull()
      expect(store.user).toBeNull()
    })

    it('clears token on profile fetch failure', async () => {
      localStorage.setItem('access_token', 'invalid-token')
      const store = useAuthStore()
      store.token = 'invalid-token'

      const mockedProfile = vi.mocked(authApi.profile)
      mockedProfile.mockRejectedValue(new Error('Unauthorized'))

      const result = await store.fetchProfile()

      expect(result).toBeNull()
      expect(store.token).toBeNull()
      expect(localStorage.getItem('access_token')).toBeNull()
    })
  })

  describe('role getters', () => {
    it('isAdmin returns true when role is admin', () => {
      const store = useAuthStore()
      store.user = mockAdminUser
      expect(store.isAdmin).toBe(true)
      expect(store.isDoctor).toBe(false)
      expect(store.isPatient).toBe(false)
    })

    it('isDoctor returns true when role is doctor', () => {
      const store = useAuthStore()
      store.user = mockUser
      expect(store.isAdmin).toBe(false)
      expect(store.isDoctor).toBe(true)
      expect(store.isPatient).toBe(false)
    })

    it('isPatient returns true when role is patient', () => {
      const store = useAuthStore()
      store.user = mockPatientUser
      expect(store.isAdmin).toBe(false)
      expect(store.isDoctor).toBe(false)
      expect(store.isPatient).toBe(true)
    })

    it('all role getters return false when no user', () => {
      const store = useAuthStore()
      store.user = null
      expect(store.isAdmin).toBe(false)
      expect(store.isDoctor).toBe(false)
      expect(store.isPatient).toBe(false)
    })
  })

  describe('loading state', () => {
    it('sets isLoading during login', async () => {
      const mockedLogin = vi.mocked(authApi.login)
      mockedLogin.mockImplementation(
        () =>
          new Promise((resolve) =>
            setTimeout(() => resolve({ access_token: 't', token_type: 'Bearer', user: mockUser }), 50),
          ),
      )

      const store = useAuthStore()
      const promise = store.login({ email: 'dr@test.com', password: 'password' })
      expect(store.isLoading).toBe(true)
      await promise
      expect(store.isLoading).toBe(false)
    })
  })
})
