import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import http from '@/services/http'

import type {
  AuthUser,
  LoginCredentials,
  LoginResponse,
  RegisterCredentials,
  RegisterResponse,
} from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isAdministrator = computed(() => user.value?.role === 'administrator')

  async function login(credentials: LoginCredentials): Promise<AuthUser> {
    loading.value = true

    try {
      await http.get('/sanctum/csrf-cookie')

      const response = await http.post<LoginResponse>('/login', credentials)

      user.value = response.data.user
      initialized.value = true

      return response.data.user
    } finally {
      loading.value = false
    }
  }

  async function register(credentials: RegisterCredentials): Promise<RegisterResponse> {
    loading.value = true

    try {
      await http.get('/sanctum/csrf-cookie')

      const response = await http.post<RegisterResponse>('/register', credentials)

      return response.data
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(): Promise<AuthUser | null> {
    loading.value = true

    try {
      const response = await http.get<AuthUser>('/api/user')

      user.value = response.data

      return response.data
    } catch {
      user.value = null

      return null
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function logout(): Promise<void> {
    loading.value = true

    try {
      await http.post('/logout')
    } finally {
      user.value = null
      loading.value = false
      initialized.value = true
    }
  }

  return {
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdministrator,
    login,
    register,
    fetchUser,
    logout,
  }
})
