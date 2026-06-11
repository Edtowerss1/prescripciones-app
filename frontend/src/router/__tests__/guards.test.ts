import { describe, it, expect } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { setupAuthGuard } from '../guards'

describe('setupAuthGuard', () => {
  it('is a named export function that accepts one Router argument', () => {
    expect(typeof setupAuthGuard).toBe('function')
    expect(setupAuthGuard.length).toBe(1)
  })

  it('registers a beforeEach guard without throwing', () => {
    setActivePinia(createPinia())

    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        {
          path: '/doctor/prescriptions',
          name: 'doctor-prescriptions',
          component: { template: '<div>Prescriptions</div>' },
          meta: { requiresAuth: true, allowedRoles: ['doctor'] },
        },
      ],
    })

    expect(() => setupAuthGuard(router)).not.toThrow()
  })
})
