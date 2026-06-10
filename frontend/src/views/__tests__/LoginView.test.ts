import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

// Mock auth API so the real store uses mocked API calls
vi.mock('@/api/auth.api', () => ({
  login: vi.fn(),
  profile: vi.fn(),
  logout: vi.fn(),
}))

// Mock vue-router
const push = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
  useRoute: () => ({ name: 'login' }),
  RouterView: { render: () => null },
}))

import * as authApi from '@/api/auth.api'
import LoginView from '@/views/LoginView.vue'

const mockDoctorUser = {
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

describe('LoginView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    push.mockClear()
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('renders email and password fields', () => {
    const wrapper = mount(LoginView)
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
  })

  it('renders submit button', () => {
    const wrapper = mount(LoginView)
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.exists()).toBe(true)
    expect(submitBtn.text()).toContain('Sign in')
  })

  it('disables submit button during loading', async () => {
    const mockedLogin = vi.mocked(authApi.login)
    // Keep the promise pending to test loading state
    mockedLogin.mockImplementation(
      () => new Promise(() => {}), // never resolves
    )

    const wrapper = mount(LoginView)
    await wrapper.find('#email').setValue('dr@test.com')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()
    expect(submitBtn.text()).toContain('Signing in')
  })

  it('shows validation error when fields are empty', async () => {
    const wrapper = mount(LoginView)
    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Email and password are required.')
  })

  it('shows error message on failed login', async () => {
    const mockedLogin = vi.mocked(authApi.login)
    const apiError = new Error('Invalid credentials')
    ;(apiError as any).response = { data: { message: 'Invalid credentials' } }
    mockedLogin.mockRejectedValue(apiError)

    const wrapper = mount(LoginView)
    await wrapper.find('#email').setValue('wrong@test.com')
    await wrapper.find('#password').setValue('wrong')
    await wrapper.find('form').trigger('submit.prevent')

    // Wait for async login to fail
    await new Promise((r) => setTimeout(r, 50))

    expect(wrapper.text()).toContain('Invalid credentials')
  })

  it('redirects doctor to /doctor/prescriptions on successful login', async () => {
    const mockedLogin = vi.mocked(authApi.login)
    mockedLogin.mockResolvedValue({
      access_token: 'test-token',
      token_type: 'Bearer',
      user: mockDoctorUser,
    })

    const wrapper = mount(LoginView)
    await wrapper.find('#email').setValue('dr@test.com')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')

    // Wait for async login to resolve
    await new Promise((r) => setTimeout(r, 50))

    expect(push).toHaveBeenCalledWith('/doctor/prescriptions')
  })

  it('redirects admin to /admin on successful login', async () => {
    const mockedLogin = vi.mocked(authApi.login)
    mockedLogin.mockResolvedValue({
      access_token: 'test-token',
      token_type: 'Bearer',
      user: mockAdminUser,
    })

    const wrapper = mount(LoginView)
    await wrapper.find('#email').setValue('admin@test.com')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')

    await new Promise((r) => setTimeout(r, 50))

    expect(push).toHaveBeenCalledWith('/admin')
  })

  it('redirects patient to /patient/prescriptions on successful login', async () => {
    const mockedLogin = vi.mocked(authApi.login)
    mockedLogin.mockResolvedValue({
      access_token: 'test-token',
      token_type: 'Bearer',
      user: mockPatientUser,
    })

    const wrapper = mount(LoginView)
    await wrapper.find('#email').setValue('patient@test.com')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')

    await new Promise((r) => setTimeout(r, 50))

    expect(push).toHaveBeenCalledWith('/patient/prescriptions')
  })
})
