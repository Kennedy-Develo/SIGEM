<script setup lang="ts">
import { computed, ref } from 'vue'

import type {
  Manifestation,
  ManifestationCatalogs,
  ManifestationFilters,
  ManifestationSource,
  ManifestationStatus,
  ManifestationType,
} from '@/types/manifestation'

const props = defineProps<{
  manifestations: Manifestation[]
  catalogs: ManifestationCatalogs
  loading: boolean
  currentPage: number
  lastPage: number
  total: number
}>()

const emit = defineEmits<{
  applyFilters: [filters: ManifestationFilters]
  changePage: [page: number]
  create: []
  select: [manifestation: Manifestation]
}>()

const search = ref('')
const sourceFilter = ref<ManifestationSource | 'all'>('all')
const statusFilter = ref<ManifestationStatus | 'all'>('all')
const typeFilter = ref<ManifestationType | 'all'>('all')
const sectorFilter = ref<number | null>(null)

const statusOptions = computed(() => [
  {
    title: 'Todas as situações',
    value: 'all',
  },
  ...props.catalogs.statuses.map((status) => ({
    title: status.label,
    value: status.value,
  })),
])

const typeOptions = computed(() => [
  {
    title: 'Todos os tipos',
    value: 'all',
  },
  ...props.catalogs.types.map((type) => ({
    title: type.label,
    value: type.value,
  })),
])

function buildFilters(page = 1): ManifestationFilters {
  const filters: ManifestationFilters = {
    page,
    per_page: 15,
    sort_by: 'current_deadline_at',
    sort_direction: 'asc',
  }

  const normalizedSearch = search.value.trim()

  if (normalizedSearch) {
    filters.search = normalizedSearch
  }

  if (sourceFilter.value !== 'all') {
    filters.source = sourceFilter.value
  }

  if (statusFilter.value !== 'all') {
    filters.status = statusFilter.value
  }

  if (typeFilter.value !== 'all') {
    filters.type = typeFilter.value
  }

  if (sectorFilter.value !== null) {
    filters.sector_id = sectorFilter.value
  }

  return filters
}

function applyFilters(): void {
  emit('applyFilters', buildFilters())
}

function changePage(page: number): void {
  emit('changePage', page)
}

function clearFilters(): void {
  search.value = ''
  sourceFilter.value = 'all'
  statusFilter.value = 'all'
  typeFilter.value = 'all'
  sectorFilter.value = null

  emit('applyFilters', buildFilters())
}

function selectSource(source: ManifestationSource | 'all'): void {
  sourceFilter.value = source
  applyFilters()
}

function formatDate(value: string | null): string {
  if (!value) {
    return 'Sem data'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
  }).format(new Date(value))
}

function sourceLabel(source: ManifestationSource): string {
  return props.catalogs.sources.find((option) => option.value === source)?.label ?? source
}

function typeLabel(type: ManifestationType): string {
  return props.catalogs.types.find((option) => option.value === type)?.label ?? type
}

function statusLabel(status: ManifestationStatus): string {
  return props.catalogs.statuses.find((option) => option.value === status)?.label ?? status
}

function statusColor(manifestation: Manifestation): string {
  if (manifestation.status === 'completed') {
    return 'success'
  }

  if (manifestation.status === 'archived') {
    return 'grey'
  }

  if (manifestation.extended_at) {
    return 'error'
  }

  if (manifestation.forwarded_to_external_agency_at) {
    return 'warning'
  }

  if (manifestation.answered_by_ombudsman_at) {
    return 'info'
  }

  return manifestation.status === 'registered' ? 'primary' : 'secondary'
}

function isOverdue(manifestation: Manifestation): boolean {
  if (
    !manifestation.current_deadline_at ||
    manifestation.status === 'completed' ||
    manifestation.status === 'archived'
  ) {
    return false
  }

  const deadline = new Date(manifestation.current_deadline_at)
  const today = new Date()

  deadline.setHours(0, 0, 0, 0)
  today.setHours(0, 0, 0, 0)

  return deadline < today
}

function deadlineColor(manifestation: Manifestation): string {
  if (manifestation.status === 'completed') {
    return 'success'
  }

  if (manifestation.status === 'archived') {
    return 'grey'
  }

  if (isOverdue(manifestation)) {
    return 'error'
  }

  return 'warning'
}
</script>

<template>
  <v-card class="manifestation-list" rounded="xl">
    <div class="manifestation-list__header">
      <div>
        <p class="manifestation-list__eyebrow">Acompanhamento</p>

        <h2>Manifestações</h2>

        <span>{{ props.total }} registro(s) encontrado(s)</span>
      </div>

      <v-btn color="primary" prepend-icon="mdi-plus" size="small" @click="emit('create')">
        Nova
      </v-btn>
    </div>

    <div class="source-tabs">
      <button
        type="button"
        :class="{ 'source-tabs__button--active': sourceFilter === 'all' }"
        class="source-tabs__button"
        @click="selectSource('all')"
      >
        Todas
      </button>

      <button
        type="button"
        :class="{ 'source-tabs__button--active': sourceFilter === 'fala_br' }"
        class="source-tabs__button"
        @click="selectSource('fala_br')"
      >
        FALA.BR
      </button>

      <button
        type="button"
        :class="{ 'source-tabs__button--active': sourceFilter === 'sei' }"
        class="source-tabs__button"
        @click="selectSource('sei')"
      >
        SEI
      </button>
    </div>

    <div class="manifestation-list__filters">
      <v-text-field
        v-model="search"
        label="Pesquisar"
        placeholder="NUP, assunto, setor ou resumo"
        prepend-inner-icon="mdi-magnify"
        clearable
        hide-details
        @keyup.enter="applyFilters"
      />

      <div class="manifestation-list__filter-grid">
        <v-select v-model="statusFilter" :items="statusOptions" label="Situação" hide-details />

        <v-select v-model="typeFilter" :items="typeOptions" label="Tipo" hide-details />
      </div>

      <v-autocomplete
        v-model="sectorFilter"
        :items="props.catalogs.sectors"
        item-title="label"
        item-value="id"
        label="Tag ou setor"
        placeholder="Digite ou selecione"
        prepend-inner-icon="mdi-tag-outline"
        clearable
        hide-details
        no-data-text="Nenhum setor encontrado"
      />

      <div class="manifestation-list__filter-actions">
        <v-btn color="primary" prepend-icon="mdi-filter-outline" @click="applyFilters">
          Filtrar
        </v-btn>

        <v-btn variant="text" prepend-icon="mdi-filter-off-outline" @click="clearFilters">
          Limpar
        </v-btn>
      </div>
    </div>

    <v-progress-linear v-if="props.loading" color="primary" indeterminate />

    <div class="manifestation-list__results">
      <button
        v-for="manifestation in props.manifestations"
        :key="manifestation.id"
        type="button"
        class="manifestation-card"
        @click="emit('select', manifestation)"
      >
        <div class="manifestation-card__top">
          <div class="manifestation-card__nup">
            <v-icon icon="mdi-circle-medium" size="18" color="primary" />

            <strong>{{ manifestation.nup }}</strong>
          </div>

          <span>{{ formatDate(manifestation.opened_at) }}</span>
        </div>

        <div class="manifestation-card__subject">
          <strong>{{ manifestation.subject.name }}</strong>

          <span>{{ manifestation.subsubject.name }}</span>
        </div>

        <p v-if="manifestation.summary" class="manifestation-card__summary">
          {{ manifestation.summary }}
        </p>

        <div class="manifestation-card__chips">
          <v-chip color="primary" variant="tonal" size="x-small">
            {{ sourceLabel(manifestation.source) }}
          </v-chip>

          <v-chip :color="statusColor(manifestation)" variant="tonal" size="x-small">
            {{ statusLabel(manifestation.status) }}
          </v-chip>

          <v-chip color="secondary" variant="tonal" size="x-small">
            {{ manifestation.sector.acronym }}
          </v-chip>

          <v-chip
            v-if="manifestation.current_deadline_at"
            :color="deadlineColor(manifestation)"
            variant="tonal"
            size="x-small"
            prepend-icon="mdi-calendar-clock-outline"
          >
            {{ formatDate(manifestation.current_deadline_at) }}
          </v-chip>
        </div>

        <div class="manifestation-card__conditions">
          <span v-if="manifestation.extended_at" class="condition condition--red">
            Prorrogada
          </span>

          <span
            v-if="manifestation.forwarded_to_external_agency_at"
            class="condition condition--yellow"
          >
            Encaminhada
          </span>

          <span v-if="manifestation.answered_by_ombudsman_at" class="condition condition--blue">
            Respondida pela Ouvidoria
          </span>
        </div>

        <div class="manifestation-card__footer">
          <span>
            <v-icon icon="mdi-account-outline" size="16" />

            {{ manifestation.current_assignee?.name ?? 'Sem respondente' }}
          </span>

          <span>{{ typeLabel(manifestation.type) }}</span>
        </div>
      </button>

      <div v-if="!props.loading && props.manifestations.length === 0" class="empty-state">
        <v-icon icon="mdi-file-search-outline" size="46" color="grey-lighten-1" />

        <strong>Nenhuma manifestação encontrada</strong>

        <span>Cadastre uma manifestação ou ajuste os filtros.</span>
      </div>
    </div>

    <div class="manifestation-list__pagination">
      <span>{{ props.total }} registro(s)</span>

      <v-pagination
        v-if="props.lastPage > 1"
        :model-value="props.currentPage"
        :length="props.lastPage"
        :total-visible="4"
        density="compact"
        @update:model-value="changePage"
      />
    </div>
  </v-card>
</template>

<style scoped>
.manifestation-list {
  height: 100%;
  overflow: hidden;
  border: 1px solid rgb(15 23 42 / 6%);
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%) !important;
}

.manifestation-list__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 20px 14px;
}

.manifestation-list__eyebrow {
  margin: 0 0 3px;
  color: #0e7490;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.manifestation-list__header h2 {
  margin: 0;
  color: #172033;
  font-size: 1.2rem;
}

.manifestation-list__header span {
  color: #64748b;
  font-size: 0.72rem;
}

.source-tabs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  margin: 0 20px 14px;
  padding: 4px;
  border-radius: 12px;
  background: #e8eef8;
}

.source-tabs__button {
  min-height: 36px;
  border: 0;
  border-radius: 9px;
  color: #475569;
  background: transparent;
  font: inherit;
  font-size: 0.76rem;
  font-weight: 700;
  cursor: pointer;
}

.source-tabs__button--active {
  color: #1d4ed8;
  background: #ffffff;
  box-shadow: 0 3px 8px rgb(15 23 42 / 8%);
}

.manifestation-list__filters {
  display: grid;
  gap: 12px;
  padding: 0 20px 18px;
}

.manifestation-list__filter-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.manifestation-list__filter-actions {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
}

.manifestation-list__results {
  display: grid;
  max-height: 680px;
  gap: 10px;
  overflow-y: auto;
  padding: 14px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.manifestation-card {
  display: grid;
  width: 100%;
  gap: 10px;
  padding: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 15px;
  color: inherit;
  background: #ffffff;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    transform 150ms ease;
}

.manifestation-card:hover {
  border-color: #93c5fd;
  box-shadow: 0 8px 20px rgb(15 23 42 / 8%);
  transform: translateY(-1px);
}

.manifestation-card__top,
.manifestation-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.manifestation-card__top > span,
.manifestation-card__footer {
  color: #64748b;
  font-size: 0.68rem;
}

.manifestation-card__nup {
  display: flex;
  align-items: center;
}

.manifestation-card__nup strong {
  color: #172033;
  font-size: 0.8rem;
}

.manifestation-card__subject {
  display: grid;
}

.manifestation-card__subject strong {
  color: #172033;
  font-size: 0.86rem;
}

.manifestation-card__subject span {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.73rem;
}

.manifestation-card__summary {
  display: -webkit-box;
  margin: 0;
  overflow: hidden;
  color: #475569;
  font-size: 0.73rem;
  line-height: 1.5;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

.manifestation-card__chips,
.manifestation-card__conditions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.condition {
  padding: 3px 7px;
  border-radius: 999px;
  font-size: 0.62rem;
  font-weight: 700;
}

.condition--red {
  color: #b91c1c;
  background: #fee2e2;
}

.condition--yellow {
  color: #a16207;
  background: #fef3c7;
}

.condition--blue {
  color: #1d4ed8;
  background: #dbeafe;
}

.manifestation-card__footer {
  padding-top: 9px;
  border-top: 1px solid #f1f5f9;
}

.manifestation-card__footer span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.empty-state {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 48px 20px;
  color: #64748b;
  text-align: center;
}

.empty-state strong {
  color: #334155;
}

.empty-state span {
  font-size: 0.76rem;
}

.manifestation-list__pagination {
  display: grid;
  justify-items: center;
  gap: 10px;
  padding: 16px;
  color: #64748b;
  font-size: 0.72rem;
}

@media (max-width: 500px) {
  .manifestation-list__filter-grid,
  .manifestation-list__filter-actions {
    grid-template-columns: 1fr;
  }

  .manifestation-card__top,
  .manifestation-card__footer {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
