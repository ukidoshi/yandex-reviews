import { computed, readonly, ref } from 'vue'
import { apiRequest } from '../api/client'

const user = ref(null)
const loading = ref(false)
const error = ref(null)
const initialized = ref(false)

export function useAuth() {
  const isAuthenticated = computed(() => user.value !== null)

  async function fetchUser() {
    loading.value = true
    error.value = null

    try {
      const data = await apiRequest('/api/user')
      user.value = data.user
    } catch (err) {
      user.value = null
      if (err.status !== 401) {
        error.value = err.data?.message ?? err.message
      }
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function login(email, password) {
    loading.value = true
    error.value = null

    try {
      const data = await apiRequest('/api/login', {
        method: 'POST',
        body: { email, password },
      })
      user.value = data.user
      return true
    } catch (err) {
      const validationMessage = err.data?.errors?.email?.[0]
      error.value = validationMessage ?? err.data?.message ?? err.message
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    error.value = null

    try {
      await apiRequest('/api/logout', { method: 'POST' })
      user.value = null
    } catch (err) {
      error.value = err.data?.message ?? err.message
    } finally {
      loading.value = false
    }
  }

  return {
    user: readonly(user),
    loading: readonly(loading),
    error: readonly(error),
    initialized: readonly(initialized),
    isAuthenticated,
    fetchUser,
    login,
    logout,
  }
}
