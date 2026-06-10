import { useAuthStore } from '@/stores/auth.store'
import { storeToRefs } from 'pinia'

export function useAuth() {
  const store = useAuthStore()
  const { user, isAuthenticated, userRole, isLoading } = storeToRefs(store)

  return {
    login: store.login,
    logout: store.logout,
    user,
    isAuthenticated,
    userRole,
    isLoading,
  }
}
