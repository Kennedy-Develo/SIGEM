import { ref } from 'vue'
import { defineStore } from 'pinia'

import http from '@/services/http'

import type { AuditFilters, AuditLog, AuditPagination } from '@/types/audit'

export const useAuditStore = defineStore('audit', () => {
  const auditLogs = ref<AuditLog[]>([])
  const loading = ref(false)

  const currentPage = ref(1)
  const lastPage = ref(1)
  const perPage = ref(15)
  const total = ref(0)

  async function fetchAuditLogs(filters: AuditFilters = {}): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<AuditPagination>('/api/admin/audit-logs', {
        params: filters,
      })

      auditLogs.value = response.data.data
      currentPage.value = response.data.current_page
      lastPage.value = response.data.last_page
      perPage.value = response.data.per_page
      total.value = response.data.total
    } finally {
      loading.value = false
    }
  }

  function clear(): void {
    auditLogs.value = []
    currentPage.value = 1
    lastPage.value = 1
    perPage.value = 15
    total.value = 0
  }

  return {
    auditLogs,
    loading,
    currentPage,
    lastPage,
    perPage,
    total,
    fetchAuditLogs,
    clear,
  }
})
