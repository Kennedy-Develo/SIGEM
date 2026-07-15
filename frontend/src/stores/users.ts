import { ref } from 'vue'
import { defineStore } from 'pinia'

import http from '@/services/http'

import type {
  ManagedUser,
  UpdateUserAccess,
  UpdateUserAccessResponse,
  UserFilters,
  UserPagination,
} from '@/types/admin'

export const useUsersStore = defineStore('users', () => {
  const users = ref<ManagedUser[]>([])
  const loading = ref(false)
  const updatingUserId = ref<number | null>(null)

  const currentPage = ref(1)
  const lastPage = ref(1)
  const perPage = ref(15)
  const total = ref(0)

  async function fetchUsers(filters: UserFilters = {}): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<UserPagination>('/api/admin/users', {
        params: filters,
      })

      users.value = response.data.data
      currentPage.value = response.data.current_page
      lastPage.value = response.data.last_page
      perPage.value = response.data.per_page
      total.value = response.data.total
    } finally {
      loading.value = false
    }
  }

  async function updateUserAccess(
    userId: number,
    access: UpdateUserAccess,
  ): Promise<UpdateUserAccessResponse> {
    updatingUserId.value = userId

    try {
      const response = await http.patch<UpdateUserAccessResponse>(
        `/api/admin/users/${userId}`,
        access,
      )

      const userIndex = users.value.findIndex((user) => user.id === userId)

      if (userIndex !== -1) {
        users.value[userIndex] = response.data.user
      }

      return response.data
    } finally {
      updatingUserId.value = null
    }
  }

  return {
    users,
    loading,
    updatingUserId,
    currentPage,
    lastPage,
    perPage,
    total,
    fetchUsers,
    updateUserAccess,
  }
})
