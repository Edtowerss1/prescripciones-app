import type { Router } from 'vue-router'

const roleHomeMap: Record<string, string> = {
  admin: '/admin',
  doctor: '/doctor/prescriptions',
  patient: '/patient/prescriptions',
}

export function setupAuthGuard(router: Router): void {
  router.beforeEach(async (to, _from, next) => {
    const token = localStorage.getItem('access_token')
    const requiresAuth = to.matched.some((r) => r.meta.requiresAuth)

    if (!requiresAuth) {
      // Public route — if authenticated and on login, redirect to role home
      if (token && to.name === 'login') {
        const { useAuthStore } = await import('@/stores/auth.store')
        const store = useAuthStore()
        if (!store.user) {
          await store.fetchProfile()
        }
        return next(roleHomeMap[store.user?.role ?? ''] || '/login')
      }
      return next()
    }

    // Protected route — must have token
    if (!token) {
      return next({ name: 'login' })
    }

    // Has token — ensure profile is loaded
    const { useAuthStore } = await import('@/stores/auth.store')
    const store = useAuthStore()

    if (!store.user) {
      const profile = await store.fetchProfile()
      if (!profile) {
        return next({ name: 'login' })
      }
    }

    // Check role-based access
    const allowedRoles = to.meta.allowedRoles as string[] | undefined
    if (allowedRoles && allowedRoles.length > 0) {
      const role = store.user?.role
      if (!role || !allowedRoles.includes(role)) {
        return next(roleHomeMap[role ?? ''] || '/login')
      }
    }

    next()
  })
}
