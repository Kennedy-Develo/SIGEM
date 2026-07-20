import { ref } from 'vue'
import { defineStore } from 'pinia'

import http from '@/services/http'

import type {
  Manifestation,
  ManifestationCatalogs,
  ManifestationFilters,
  ManifestationIndicators,
  ManifestationListResponse,
  StoreManifestationPayload,
  StoreManifestationResponse,
  TransitionManifestationPayload,
  TransitionManifestationResponse,
} from '@/types/manifestation'

export const useManifestationsStore = defineStore('manifestations', () => {
  const manifestations = ref<Manifestation[]>([])

  const catalogs = ref<ManifestationCatalogs>({
    sources: [],
    types: [],
    statuses: [],
    subjects: [],
    sectors: [],
    assignees: [],
  })

  const indicators = ref<ManifestationIndicators>({
    overdue: 0,
    due_today: 0,
    due_next_7_days: 0,
    extended: 0,
    completed: 0,
  })

  const loading = ref(false)
  const loadingCatalogs = ref(false)
  const creating = ref(false)
  const transitioningManifestationId = ref<number | null>(null)

  const currentPage = ref(1)
  const lastPage = ref(1)
  const perPage = ref(15)
  const total = ref(0)

  async function fetchCatalogs(): Promise<void> {
    loadingCatalogs.value = true

    try {
      const response = await http.get<ManifestationCatalogs>('/api/manifestations/catalogs')

      catalogs.value = response.data
    } finally {
      loadingCatalogs.value = false
    }
  }

  async function fetchManifestations(filters: ManifestationFilters = {}): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ManifestationListResponse>('/api/manifestations', {
        params: filters,
      })

      manifestations.value = response.data.manifestations.data
      indicators.value = response.data.indicators

      currentPage.value = response.data.manifestations.current_page
      lastPage.value = response.data.manifestations.last_page
      perPage.value = response.data.manifestations.per_page
      total.value = response.data.manifestations.total
    } finally {
      loading.value = false
    }
  }

  async function createManifestation(
    payload: StoreManifestationPayload,
  ): Promise<StoreManifestationResponse> {
    creating.value = true

    try {
      const response = await http.post<StoreManifestationResponse>('/api/manifestations', payload)

      return response.data
    } finally {
      creating.value = false
    }
  }

  async function transitionManifestation(
    manifestationId: number,
    payload: TransitionManifestationPayload,
  ): Promise<TransitionManifestationResponse> {
    transitioningManifestationId.value = manifestationId

    try {
      const response = await http.post<TransitionManifestationResponse>(
        `/api/manifestations/${manifestationId}/transition`,
        payload,
      )

      const manifestationIndex = manifestations.value.findIndex(
        (manifestation) => manifestation.id === manifestationId,
      )

      if (manifestationIndex >= 0) {
        manifestations.value[manifestationIndex] = response.data.manifestation
      }

      return response.data
    } finally {
      transitioningManifestationId.value = null
    }
  }

  function clear(): void {
    manifestations.value = []

    indicators.value = {
      overdue: 0,
      due_today: 0,
      due_next_7_days: 0,
      extended: 0,
      completed: 0,
    }

    transitioningManifestationId.value = null
    currentPage.value = 1
    lastPage.value = 1
    perPage.value = 15
    total.value = 0
  }

  return {
    manifestations,
    catalogs,
    indicators,
    loading,
    loadingCatalogs,
    creating,
    transitioningManifestationId,
    currentPage,
    lastPage,
    perPage,
    total,
    fetchCatalogs,
    fetchManifestations,
    createManifestation,
    transitionManifestation,
    clear,
  }
})
