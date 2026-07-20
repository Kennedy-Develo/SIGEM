<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useDisplay } from 'vuetify'

import { useAuditStore } from '@/stores/audit'

import type { AuditAction, AuditFilters, AuditLog } from '@/types/audit'

interface ErrorResponse {
  message?: string
}

const auditStore = useAuditStore()
const { mobile } = useDisplay()

const search = ref('')
const actionFilter = ref<AuditAction | 'all'>('all')
const fromDate = ref('')
const toDate = ref('')
const selectedPerPage = ref<15 | 25 | 50>(15)

const errorMessage = ref('')
const detailDialogOpen = ref(false)
const selectedAuditLog = ref<AuditLog | null>(null)

const actionOptions = [
  {
    title: 'Todas as ações',
    value: 'all',
  },
  {
    title: 'Acesso de usuário atualizado',
    value: 'user.access_updated',
  },
  {
    title: 'Manifestação cadastrada',
    value: 'manifestation.created',
  },
  {
    title: 'Manifestação atualizada',
    value: 'manifestation.updated',
  },
  {
    title: 'Ciclo de vida da manifestação atualizado',
    value: 'manifestation.lifecycle_updated',
  },
]

const perPageOptions = [
  {
    title: '15 por página',
    value: 15,
  },
  {
    title: '25 por página',
    value: 25,
  },
  {
    title: '50 por página',
    value: 50,
  },
]

const roleLabels: Record<string, string> = {
  administrator: 'Administrador',
  manager: 'Gestor',
  operator: 'Operador',
  reader: 'Leitor',
}

const statusLabels: Record<string, string> = {
  pending: 'Pendente',
  active: 'Ativo',
  blocked: 'Bloqueado',
  registered: 'Cadastrada',
  in_progress: 'Em andamento',
  completed: 'Concluída',
  archived: 'Arquivada',
}

const fieldLabels: Record<string, string> = {
  role: 'Perfil',
  status: 'Situação',
  approved_by: 'Aprovado por',
  approved_at: 'Aprovado em',
  blocked_at: 'Bloqueado em',
  current_deadline_at: 'Prazo atual',
  original_deadline_at: 'Prazo original',
  started_at: 'Início do atendimento',
  extended_at: 'Data da prorrogação',
  forwarded_to_external_agency_at: 'Data de encaminhamento',
  forwarded_at: 'Data de encaminhamento',
  external_agency: 'Órgão de interesse',
  answered_by_ombudsman_at: 'Resposta da Ouvidoria',
  completed_at: 'Data de conclusão',
  archived_at: 'Data de arquivamento',
}

const dateFields = new Set([
  'approved_at',
  'blocked_at',
  'current_deadline_at',
  'original_deadline_at',
  'started_at',
  'extended_at',
  'forwarded_to_external_agency_at',
  'forwarded_at',
  'answered_by_ombudsman_at',
  'completed_at',
  'archived_at',
])

const recordsOnPage = computed(() => auditStore.auditLogs.length)

const recordsToday = computed(() => {
  const today = new Date().toDateString()

  return auditStore.auditLogs.filter((auditLog) => {
    if (!auditLog.created_at) {
      return false
    }

    return new Date(auditLog.created_at).toDateString() === today
  }).length
})

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

function formatFieldName(field: string): string {
  return fieldLabels[field] ?? field
}

function formatValue(field: string, value: unknown): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  if (field === 'role' && typeof value === 'string') {
    return roleLabels[value] ?? value
  }

  if (field === 'status' && typeof value === 'string') {
    return statusLabels[value] ?? value
  }

  if (dateFields.has(field) && typeof value === 'string') {
    return formatDate(value)
  }

  if (typeof value === 'boolean') {
    return value ? 'Sim' : 'Não'
  }

  if (typeof value === 'object') {
    return JSON.stringify(value)
  }

  return String(value)
}

function isManifestationSubject(auditLog: AuditLog): boolean {
  const subjectType = auditLog.subject.type.toLowerCase()

  return subjectType === 'manifestation' || subjectType.includes('manifestation')
}

function subjectLabel(auditLog: AuditLog): string {
  if (auditLog.subject.name) {
    return auditLog.subject.name
  }

  return isManifestationSubject(auditLog) ? 'Manifestação removida' : 'Usuário removido'
}

function subjectSecondary(auditLog: AuditLog): string {
  if (auditLog.subject.email) {
    return auditLog.subject.email
  }

  if (auditLog.subject.id !== null) {
    return `Código ${auditLog.subject.id}`
  }

  return 'Registro indisponível'
}

function subjectSectionLabel(auditLog: AuditLog): string {
  return isManifestationSubject(auditLog) ? 'Manifestação afetada' : 'Usuário afetado'
}

function subjectIcon(auditLog: AuditLog): string {
  return isManifestationSubject(auditLog) ? 'mdi-file-document-outline' : 'mdi-account-outline'
}

function actionIcon(auditLog: AuditLog): string {
  if (auditLog.action === 'manifestation.created') {
    return 'mdi-file-document-plus-outline'
  }

  if (auditLog.action === 'manifestation.updated') {
    return 'mdi-file-document-edit-outline'
  }

  if (auditLog.action === 'manifestation.lifecycle_updated') {
    return 'mdi-source-branch'
  }

  return 'mdi-account-cog-outline'
}

function changedFields(auditLog: AuditLog): string[] {
  const metadataFields = auditLog.metadata?.changed_fields

  if (Array.isArray(metadataFields)) {
    return metadataFields.filter((field): field is string => typeof field === 'string')
  }

  const oldValues = auditLog.old_values ?? {}
  const newValues = auditLog.new_values ?? {}

  return Object.keys(newValues).filter((field) => oldValues[field] !== newValues[field])
}

function changeSummary(auditLog: AuditLog): string {
  const fields = changedFields(auditLog)

  if (fields.length === 0) {
    return 'Operação registrada'
  }

  const preferredFields = ['status', 'current_deadline_at', 'external_agency', 'role']

  const preferredField = preferredFields.find((field) => fields.includes(field)) ?? fields[0]

  if (!preferredField) {
    return 'Operação registrada'
  }

  const oldValue = formatValue(preferredField, auditLog.old_values?.[preferredField])

  const newValue = formatValue(preferredField, auditLog.new_values?.[preferredField])

  const remainingChanges = fields.length - 1
  const suffix = remainingChanges > 0 ? ` +${remainingChanges}` : ''

  return `${formatFieldName(preferredField)}: ${oldValue} → ${newValue}${suffix}`
}

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível carregar o histórico de auditoria.'
  }

  return error.response?.data.message ?? 'Não foi possível carregar o histórico de auditoria.'
}

function buildFilters(page = 1): AuditFilters {
  const filters: AuditFilters = {
    page,
    per_page: selectedPerPage.value,
  }

  const normalizedSearch = search.value.trim()

  if (normalizedSearch) {
    filters.search = normalizedSearch
  }

  if (actionFilter.value !== 'all') {
    filters.action = actionFilter.value
  }

  if (fromDate.value) {
    filters.from = fromDate.value
  }

  if (toDate.value) {
    filters.to = toDate.value
  }

  return filters
}

async function loadAuditLogs(page = 1): Promise<void> {
  errorMessage.value = ''

  try {
    await auditStore.fetchAuditLogs(buildFilters(page))
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

function clearFilters(): void {
  search.value = ''
  actionFilter.value = 'all'
  fromDate.value = ''
  toDate.value = ''
  selectedPerPage.value = 15

  void loadAuditLogs()
}

function openDetails(auditLog: AuditLog): void {
  selectedAuditLog.value = auditLog
  detailDialogOpen.value = true
}

function closeDetails(): void {
  detailDialogOpen.value = false
  selectedAuditLog.value = null
}

onMounted(() => {
  void loadAuditLogs()
})
</script>
<template>
  <v-container class="audit-page py-6 py-md-8" fluid>
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">Administração</p>

        <h1 class="page-header__title">Histórico de auditoria</h1>

        <p class="page-header__description">
          Acompanhe alterações administrativas e consulte quem realizou cada ação.
        </p>
      </div>

      <v-btn
        color="primary"
        prepend-icon="mdi-refresh"
        :loading="auditStore.loading"
        @click="loadAuditLogs(auditStore.currentPage)"
      >
        Atualizar
      </v-btn>
    </div>

    <v-row class="mb-2">
      <v-col cols="12" sm="4">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon summary-card__icon--primary">
            <v-icon icon="mdi-clipboard-text-clock-outline" />
          </div>

          <div>
            <p class="summary-card__label">Registros encontrados</p>

            <strong class="summary-card__value">
              {{ auditStore.total }}
            </strong>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon summary-card__icon--info">
            <v-icon icon="mdi-file-eye-outline" />
          </div>

          <div>
            <p class="summary-card__label">Nesta página</p>

            <strong class="summary-card__value">
              {{ recordsOnPage }}
            </strong>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="4">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon summary-card__icon--success">
            <v-icon icon="mdi-calendar-check-outline" />
          </div>

          <div>
            <p class="summary-card__label">Hoje nesta página</p>

            <strong class="summary-card__value">
              {{ recordsToday }}
            </strong>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-alert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      closable
      class="mb-5"
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </v-alert>

    <v-card rounded="xl" class="filters-card pa-4 pa-md-5 mb-5">
      <v-row align="center">
        <v-col cols="12" md="4">
          <v-text-field
            v-model="search"
            label="Pesquisar"
            placeholder="Responsável ou usuário afetado"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            @keyup.enter="loadAuditLogs()"
          />
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-select
            v-model="actionFilter"
            :items="actionOptions"
            label="Tipo de ação"
            hide-details
          />
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-select
            v-model="selectedPerPage"
            :items="perPageOptions"
            label="Exibição"
            hide-details
          />
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-btn
            color="primary"
            prepend-icon="mdi-filter-outline"
            block
            height="48"
            @click="loadAuditLogs()"
          >
            Filtrar
          </v-btn>
        </v-col>

        <v-col cols="12" sm="6" md="1">
          <v-btn
            variant="text"
            icon="mdi-filter-off-outline"
            aria-label="Limpar filtros"
            block
            @click="clearFilters"
          />
        </v-col>
      </v-row>

      <v-row class="mt-1">
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="fromDate"
            type="date"
            label="Data inicial"
            prepend-inner-icon="mdi-calendar-start-outline"
            hide-details
          />
        </v-col>

        <v-col cols="12" sm="6">
          <v-text-field
            v-model="toDate"
            type="date"
            label="Data final"
            prepend-inner-icon="mdi-calendar-end-outline"
            hide-details
          />
        </v-col>
      </v-row>
    </v-card>

    <v-card rounded="xl" class="audit-card">
      <v-progress-linear v-if="auditStore.loading" color="primary" indeterminate />

      <div class="audit-table-wrapper">
        <v-table class="audit-table">
          <thead>
            <tr>
              <th>Data e hora</th>
              <th>Ação</th>
              <th>Responsável</th>
              <th>Registro afetado</th>
              <th>Alteração</th>
              <th class="text-right">Detalhes</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="auditLog in auditStore.auditLogs" :key="auditLog.id">
              <td>
                <div class="date-cell">
                  <v-icon icon="mdi-clock-outline" size="17" color="grey" />

                  <span>{{ formatDate(auditLog.created_at) }}</span>
                </div>
              </td>

              <td>
                <v-chip color="primary" variant="tonal" size="small">
                  <v-icon :icon="actionIcon(auditLog)" start size="16" />

                  {{ auditLog.action_label }}
                </v-chip>
              </td>
              <td>
                <div class="person-cell">
                  <v-avatar color="primary" variant="tonal" size="38">
                    <v-icon icon="mdi-shield-account-outline" size="20" />
                  </v-avatar>

                  <div>
                    <strong>
                      {{ auditLog.actor?.name ?? 'Usuário removido' }}
                    </strong>

                    <span>
                      {{ auditLog.actor?.email ?? 'Responsável indisponível' }}
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <div class="person-cell">
                  <v-avatar color="secondary" variant="tonal" size="38">
                    <v-icon :icon="subjectIcon(auditLog)" size="20" />
                  </v-avatar>

                  <div>
                    <strong>
                      {{ subjectLabel(auditLog) }}
                    </strong>

                    <span>
                      {{ subjectSecondary(auditLog) }}
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <span class="change-summary">
                  {{ changeSummary(auditLog) }}
                </span>
              </td>

              <td class="text-right">
                <v-btn
                  color="primary"
                  variant="tonal"
                  size="small"
                  prepend-icon="mdi-eye-outline"
                  @click="openDetails(auditLog)"
                >
                  Visualizar
                </v-btn>
              </td>
            </tr>

            <tr v-if="!auditStore.loading && auditStore.auditLogs.length === 0">
              <td colspan="6">
                <div class="empty-state">
                  <v-icon icon="mdi-clipboard-search-outline" size="48" color="grey" />

                  <strong>Nenhum registro encontrado</strong>

                  <span> Ainda não existem ações auditadas com os filtros selecionados. </span>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <v-divider />

      <div class="pagination">
        <span>{{ auditStore.total }} registro(s)</span>

        <v-pagination
          v-if="auditStore.lastPage > 1"
          :model-value="auditStore.currentPage"
          :length="auditStore.lastPage"
          :total-visible="mobile ? 3 : 7"
          density="comfortable"
          @update:model-value="loadAuditLogs"
        />
      </div>
    </v-card>

    <v-dialog v-model="detailDialogOpen" max-width="760" scrollable @after-leave="closeDetails">
      <v-card v-if="selectedAuditLog" rounded="xl">
        <v-card-title class="dialog-title">
          <div class="dialog-title__icon">
            <v-icon icon="mdi-clipboard-text-clock-outline" color="primary" />
          </div>

          <div>
            <strong>Detalhes da auditoria</strong>

            <span> Registro #{{ selectedAuditLog.id }} </span>
          </div>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-5 pa-md-6">
          <v-row>
            <v-col cols="12" md="6">
              <div class="detail-section">
                <p class="detail-section__label">Ação realizada</p>

                <strong>{{ selectedAuditLog.action_label }}</strong>

                <span>{{ formatDate(selectedAuditLog.created_at) }}</span>
              </div>
            </v-col>

            <v-col cols="12" md="6">
              <div class="detail-section">
                <p class="detail-section__label">Responsável</p>

                <strong>
                  {{ selectedAuditLog.actor?.name ?? 'Usuário removido' }}
                </strong>

                <span>
                  {{ selectedAuditLog.actor?.email ?? 'E-mail indisponível' }}
                </span>
              </div>
            </v-col>

            <v-col cols="12">
              <div class="detail-section">
                <p class="detail-section__label">
                  {{ subjectSectionLabel(selectedAuditLog) }}
                </p>

                <strong>
                  {{ subjectLabel(selectedAuditLog) }}
                </strong>

                <span>
                  {{ subjectSecondary(selectedAuditLog) }}
                </span>
              </div>
            </v-col>
          </v-row>

          <v-divider class="my-5" />

          <p class="changes-title">Alterações registradas</p>

          <div v-if="changedFields(selectedAuditLog).length > 0" class="changes-list">
            <div v-for="field in changedFields(selectedAuditLog)" :key="field" class="change-row">
              <strong>{{ formatFieldName(field) }}</strong>

              <div class="change-row__values">
                <div>
                  <span>Antes</span>

                  <p>
                    {{ formatValue(field, selectedAuditLog.old_values?.[field]) }}
                  </p>
                </div>

                <v-icon icon="mdi-arrow-right" color="primary" class="change-row__arrow" />

                <div>
                  <span>Depois</span>

                  <p>
                    {{ formatValue(field, selectedAuditLog.new_values?.[field]) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <v-alert v-else type="info" variant="tonal">
            A operação foi registrada, mas não houve mudança nos valores acompanhados.
          </v-alert>

          <v-divider class="my-5" />

          <div class="technical-data">
            <div>
              <span>Endereço IP</span>

              <strong>
                {{ selectedAuditLog.ip_address ?? 'Não informado' }}
              </strong>
            </div>

            <div>
              <span>Navegador ou dispositivo</span>

              <strong>
                {{ selectedAuditLog.user_agent ?? 'Não informado' }}
              </strong>
            </div>
          </div>
        </v-card-text>

        <v-card-actions class="pa-5 pt-0">
          <v-spacer />

          <v-btn color="primary" prepend-icon="mdi-check" @click="detailDialogOpen = false">
            Fechar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.audit-page {
  max-width: 1500px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.page-header__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.page-header__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.page-header__description {
  margin: 10px 0 0;
  color: #64748b;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.summary-card__icon {
  display: grid;
  width: 52px;
  height: 52px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 16px;
}

.summary-card__icon--primary {
  color: #1d4ed8;
  background: #dbeafe;
}

.summary-card__icon--info {
  color: #0e7490;
  background: #cffafe;
}

.summary-card__icon--success {
  color: #15803d;
  background: #dcfce7;
}

.summary-card__label {
  margin: 0;
  color: #64748b;
  font-size: 0.8rem;
}

.summary-card__value {
  color: #172033;
  font-size: 1.7rem;
}

.filters-card,
.audit-card,
.summary-card {
  border: 1px solid rgb(15 23 42 / 6%);
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%) !important;
}

.audit-table-wrapper {
  overflow-x: auto;
}

.audit-table {
  min-width: 1180px;
}

.date-cell {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #475569;
  white-space: nowrap;
}

.person-cell {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 7px 0;
}

.person-cell div {
  display: grid;
}

.person-cell strong {
  color: #172033;
  font-size: 0.86rem;
}

.person-cell span {
  color: #64748b;
  font-size: 0.74rem;
}

.change-summary {
  color: #334155;
  font-size: 0.8rem;
  font-weight: 600;
}

.empty-state {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 54px 20px;
  color: #64748b;
  text-align: center;
}

.empty-state strong {
  color: #334155;
}

.pagination {
  display: flex;
  min-height: 72px;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 20px;
  color: #64748b;
  font-size: 0.82rem;
}

.dialog-title {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px 24px;
}

.dialog-title__icon {
  display: grid;
  width: 46px;
  height: 46px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 14px;
  background: rgb(22 58 95 / 9%);
}

.dialog-title div:last-child {
  display: grid;
}

.dialog-title strong {
  color: #172033;
}

.dialog-title span {
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 400;
}

.detail-section {
  display: grid;
  gap: 3px;
  min-height: 92px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}

.detail-section__label {
  margin: 0 0 4px;
  color: #0e7490;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.detail-section strong {
  color: #172033;
}

.detail-section span {
  overflow-wrap: anywhere;
  color: #64748b;
  font-size: 0.8rem;
}

.changes-title {
  margin: 0 0 14px;
  color: #172033;
  font-weight: 800;
}

.changes-list {
  display: grid;
  gap: 12px;
}

.change-row {
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.change-row > strong {
  display: block;
  margin-bottom: 12px;
  color: #172033;
  font-size: 0.82rem;
}

.change-row__values {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  align-items: center;
  gap: 12px;
}

.change-row__values div {
  min-width: 0;
  padding: 12px;
  border-radius: 10px;
  background: #f8fafc;
}

.change-row__values span {
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
}

.change-row__values p {
  margin: 4px 0 0;
  overflow-wrap: anywhere;
  color: #172033;
  font-size: 0.86rem;
  font-weight: 700;
}

.technical-data {
  display: grid;
  gap: 14px;
}

.technical-data div {
  display: grid;
  gap: 3px;
}

.technical-data span {
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
}

.technical-data strong {
  overflow-wrap: anywhere;
  color: #334155;
  font-size: 0.82rem;
  font-weight: 500;
}

@media (max-width: 700px) {
  .page-header {
    display: grid;
  }

  .page-header .v-btn {
    width: 100%;
  }

  .pagination {
    display: grid;
    justify-items: center;
  }

  .change-row__values {
    grid-template-columns: 1fr;
  }

  .change-row__arrow {
    transform: rotate(90deg);
    justify-self: center;
  }
}
</style>
