import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/authService'
import type { LoginCredentials, User } from '../types/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('taskflow_token'))
  const user = ref<User | null>(
    localStorage.getItem('taskflow_user')
      ? JSON.parse(localStorage.getItem('taskflow_user')!)
      : null
  )
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => Boolean(token.value))
  const isAdmin = computed(() => user.value?.role?.slug === 'admin' || user.value?.is_admin === true)
  const isManager = computed(() => user.value?.role?.slug === 'manager' || user.value?.is_manager === true)
  const isUser = computed(() => user.value?.role?.slug === 'user' || user.value?.is_user === true)

  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    error.value = null
    try {
      const response = await authService.login(credentials)
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user

        localStorage.setItem('taskflow_token', response.data.token)
        localStorage.setItem('taskflow_user', JSON.stringify(response.data.user))
        return true
      }
      return false
    } catch (err: any) {
      const msg = err.response?.data?.message || 'Falha ao autenticar. Verifique suas credenciais.'
      error.value = msg
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      if (token.value) {
        await authService.logout()
      }
    } catch {
      // Ignora erro se token já estiver revogado
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('taskflow_token')
      localStorage.removeItem('taskflow_user')
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) return
    try {
      const response = await authService.getMe()
      if (response.success && response.data) {
        user.value = response.data
        localStorage.setItem('taskflow_user', JSON.stringify(response.data))
      }
    } catch {
      await logout()
    }
  }

  function setUser(updatedUser: User): void {
    user.value = updatedUser
    localStorage.setItem('taskflow_user', JSON.stringify(updatedUser))
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    isManager,
    isUser,
    login,
    logout,
    fetchUser,
    setUser,
  }
})
