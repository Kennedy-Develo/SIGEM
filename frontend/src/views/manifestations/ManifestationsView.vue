<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

import ManifestationForm from '@/components/manifestations/ManifestationForm.vue'
import ManifestationIndicators from '@/components/manifestations/ManifestationIndicators.vue'
import ManifestationLifecycleActions from '@/components/manifestations/ManifestationLifecycleActions.vue'
import ManifestationList from '@/components/manifestations/ManifestationList.vue'
import { useAuthStore } from '@/stores/auth'
import { useManifestationsStore } from '@/stores/manifestations'

import type { Manifestation, ManifestationFilters } from '@/types/manifestation'

interface ErrorResponse {
  message?: string
}

const authStore = useAuthStore()
const manifestationsStore = useManifestationsStore()

const successMessage = ref('')
const errorMessage = ref('')
const selectedManifestation = ref<Manifestation | null>(null)
const detailsDialogOpen = ref(false)
const formSection = ref<HTMLElement | null>(null)

const activeFilters = ref<ManifestationFilters>({
  page: 1,
  per_page: 15,
  sort_by: 'current_deadline_at',
  sort_direction: 'asc',
})

const canCreateManifestation = computed(() => {
  const role = authStore.user?.role

  return role === 'administrator' || role === 'manager' || role === 'operator'
})

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível carregar as manifestações.'
  }

  return error.response?.data.message ?? 'Não foi possível carregar as manifestações.'
}

async function loadInitialData(): Promise<void> {
  errorMessage.value = ''

  try {
    await Promise.all([
      manifestationsStore.fetchCatalogs(),
      manifestationsStore.fetchManifestations(activeFilters.value),
    ])
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

async function applyFilters(filters: ManifestationFilters): Promise<void> {
  errorMessage.value = ''
  activeFilters.value = {
    ...filters,
    page: 1,
  }

  try {
    await manifestationsStore.fetchManifestations(activeFilters.value)
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

async function changePage(page: number): Promise<void> {
  errorMessage.value = ''

  activeFilters.value = {
    ...activeFilters.value,
    page,
  }

  try {
    await manifestationsStore.fetchManifestations(activeFilters.value)
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

async function refreshManifestations(): Promise<void> {
  errorMessage.value = ''

  try {
    await manifestationsStore.fetchManifestations(activeFilters.value)
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

function openCreateForm(): void {
  successMessage.value = ''
  errorMessage.value = ''

  if (!canCreateManifestation.value) {
    errorMessage.value = 'Seu perfil possui acesso somente para consulta das manifestações.'

    return
  }

  formSection.value?.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
  })
}

async function handleCreated(message: string): Promise<void> {
  successMessage.value = message
  errorMessage.value = ''

  activeFilters.value = {
    page: 1,
    per_page: 15,
    sort_by: 'created_at',
    sort_direction: 'desc',
  }

  try {
    await manifestationsStore.fetchManifestations(activeFilters.value)
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }

  window.scrollTo({
    top: 0,
    behavior: 'smooth',
  })
}

function handleFailure(message: string): void {
  successMessage.value = ''
  errorMessage.value = message
}

async function handleLifecycleTransition(
  manifestation: Manifestation,
  message: string,
): Promise<void> {
  selectedManifestation.value = manifestation
  successMessage.value = message
  errorMessage.value = ''

  try {
    await manifestationsStore.fetchManifestations(activeFilters.value)
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

function openManifestationDetails(manifestation: Manifestation): void {
  selectedManifestation.value = manifestation
  detailsDialogOpen.value = true
}

function closeManifestationDetails(): void {
  detailsDialogOpen.value = false
  selectedManifestation.value = null
}

function formatDate(value: string | null): string {
  if (!value) {
    return 'Não informado'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
  }).format(new Date(value))
}

function sourceLabel(manifestation: Manifestation): string {
  return (
    manifestationsStore.catalogs.sources.find((option) => option.value === manifestation.source)
      ?.label ?? manifestation.source
  )
}

function typeLabel(manifestation: Manifestation): string {
  return (
    manifestationsStore.catalogs.types.find((option) => option.value === manifestation.type)
      ?.label ?? manifestation.type
  )
}

function statusLabel(manifestation: Manifestation): string {
  return (
    manifestationsStore.catalogs.statuses.find((option) => option.value === manifestation.status)
      ?.label ?? manifestation.status
  )
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

onMounted(() => {
  void loadInitialData()
})
</script>

<template>
  <v-container class="manifestations-page py-6 py-md-8" fluid>
    <header class="page-header">
      <div>
        <p class="page-header__eyebrow">Gestão operacional</p>

        <h1 class="page-header__title">Manifestações</h1>

        <p class="page-header__description">
          Cadastre, distribua e acompanhe manifestações do Fala.BR e do SEI.
        </p>
      </div>

      <v-btn
        :loading="manifestationsStore.loading"
        color="primary"
        prepend-icon="mdi-refresh"
        @click="refreshManifestations"
      >
        Atualizar
      </v-btn>
    </header>

    <v-alert
      v-if="successMessage"
      class="mb-5"
      closable
      type="success"
      variant="tonal"
      @click:close="successMessage = ''"
    >
      {{ successMessage }}
    </v-alert>

    <v-alert
      v-if="errorMessage"
      class="mb-5"
      closable
      type="error"
      variant="tonal"
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </v-alert>

    <ManifestationIndicators
      :indicators="manifestationsStore.indicators"
      :loading="manifestationsStore.loading"
      class="mb-5"
    />

    <div class="manifestations-workspace">
      <aside class="manifestations-workspace__list">
        <ManifestationList
          :catalogs="manifestationsStore.catalogs"
          :current-page="manifestationsStore.currentPage"
          :last-page="manifestationsStore.lastPage"
          :loading="manifestationsStore.loading"
          :manifestations="manifestationsStore.manifestations"
          :total="manifestationsStore.total"
          @apply-filters="applyFilters"
          @change-page="changePage"
          @create="openCreateForm"
          @select="openManifestationDetails"
        />
      </aside>

      <main ref="formSection" class="manifestations-workspace__form">
        <ManifestationForm
          v-if="canCreateManifestation"
          :catalogs="manifestationsStore.catalogs"
          :loading-catalogs="manifestationsStore.loadingCatalogs"
          @created="handleCreated"
          @failed="handleFailure"
        />

        <v-card v-else class="reader-access-card" elevation="0" rounded="xl">
          <div class="reader-access-card__icon">
            <v-icon color="primary" icon="mdi-eye-outline" size="34" />
          </div>

          <span class="reader-access-card__eyebrow"> Acesso para consulta </span>

          <h2>Acompanhe as manifestações</h2>

          <p>
            Seu perfil de Leitor permite consultar os registros, prazos, responsáveis e situações
            das manifestações.
          </p>

          <v-alert color="info" icon="mdi-information-outline" variant="tonal">
            O cadastro e a alteração de manifestações são permitidos somente para Administradores,
            Gestores e Operadores.
          </v-alert>
        </v-card>
      </main>
    </div>

    <v-dialog v-model="detailsDialogOpen" max-width="900">
      <v-card v-if="selectedManifestation" class="details-dialog" rounded="xl">
        <v-card-title class="details-dialog__header">
          <div class="details-dialog__icon">
            <v-icon color="primary" icon="mdi-file-document-outline" />
          </div>

          <div>
            <strong>Detalhes da manifestação</strong>
            <span>NUP {{ selectedManifestation.nup }}</span>
          </div>

          <v-spacer />

          <v-btn
            aria-label="Fechar detalhes"
            icon="mdi-close"
            variant="text"
            @click="closeManifestationDetails"
          />
        </v-card-title>

        <v-divider />

        <v-card-text class="details-dialog__body">
          <v-alert
            v-if="successMessage"
            class="mb-5"
            closable
            type="success"
            variant="tonal"
            @click:close="successMessage = ''"
          >
            {{ successMessage }}
          </v-alert>
          <div class="details-dialog__chips">
            <v-chip color="primary" variant="tonal">
              {{ sourceLabel(selectedManifestation) }}
            </v-chip>

            <v-chip :color="statusColor(selectedManifestation)" variant="tonal">
              {{ statusLabel(selectedManifestation) }}
            </v-chip>

            <v-chip color="secondary" variant="tonal">
              {{ typeLabel(selectedManifestation) }}
            </v-chip>
          </div>

          <div class="details-grid">
            <div class="details-item">
              <span>Assunto</span>
              <strong>
                {{ selectedManifestation.subject.name }}
              </strong>
            </div>

            <div class="details-item">
              <span>Subassunto</span>
              <strong>
                {{ selectedManifestation.subsubject.name }}
              </strong>
            </div>

            <div class="details-item">
              <span>Tag/Setor</span>
              <strong>
                {{ selectedManifestation.sector.acronym }} —
                {{ selectedManifestation.sector.name }}
              </strong>
            </div>

            <div class="details-item">
              <span>Respondente</span>
              <strong>
                {{ selectedManifestation.current_assignee?.name ?? 'Não definido' }}
              </strong>
            </div>

            <div class="details-item">
              <span>Data de abertura</span>
              <strong>
                {{ formatDate(selectedManifestation.opened_at) }}
              </strong>
            </div>

            <div class="details-item">
              <span>Prazo atual</span>
              <strong>
                {{ formatDate(selectedManifestation.current_deadline_at) }}
              </strong>
            </div>

            <div class="details-item details-item--full">
              <span>Área responsável pela resposta conclusiva</span>
              <strong>
                {{ selectedManifestation.conclusion_responsible_area ?? 'Não informada' }}
              </strong>
            </div>
          </div>

          <div v-if="selectedManifestation.summary" class="details-text">
            <span>Resumo da manifestação</span>

            <p>{{ selectedManifestation.summary }}</p>
          </div>

          <div v-if="selectedManifestation.description" class="details-text">
            <span>Descrição ou observações</span>

            <p>{{ selectedManifestation.description }}</p>
          </div>

          <div
            v-if="
              selectedManifestation.extended_at ||
              selectedManifestation.forwarded_to_external_agency_at ||
              selectedManifestation.answered_by_ombudsman_at
            "
            class="details-conditions"
          >
            <span
              v-if="selectedManifestation.extended_at"
              class="details-condition details-condition--red"
            >
              Prorrogada
            </span>

            <span
              v-if="selectedManifestation.forwarded_to_external_agency_at"
              class="details-condition details-condition--yellow"
            >
              Encaminhada para outro órgão
            </span>

            <span
              v-if="selectedManifestation.answered_by_ombudsman_at"
              class="details-condition details-condition--blue"
            >
              Respondida pela Ouvidoria
            </span>
          </div>
          <ManifestationLifecycleActions
            v-if="authStore.user"
            class="mt-6"
            :manifestation="selectedManifestation"
            :user-id="authStore.user.id"
            :user-role="authStore.user.role"
            @transitioned="handleLifecycleTransition"
          />
        </v-card-text>

        <v-card-actions class="details-dialog__actions">
          <v-spacer />

          <v-btn
            color="primary"
            prepend-icon="mdi-check"
            variant="text"
            @click="closeManifestationDetails"
          >
            Fechar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.manifestations-page {
  max-width: 1600px;
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
  font-size: clamp(1.9rem, 4vw, 2.6rem);
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.page-header__description {
  margin: 10px 0 0;
  color: #64748b;
}

.manifestations-workspace {
  display: grid;
  grid-template-columns: minmax(320px, 0.82fr) minmax(600px, 2fr);
  align-items: start;
  gap: 20px;
}

.manifestations-workspace__list,
.manifestations-workspace__form {
  min-width: 0;
  scroll-margin-top: 24px;
}

.reader-access-card {
  display: grid;
  justify-items: start;
  gap: 13px;
  padding: 32px;
  border: 1px solid rgb(15 23 42 / 6%);
  background: #ffffff;
}

.reader-access-card__icon {
  display: grid;
  width: 58px;
  height: 58px;
  place-items: center;
  border-radius: 18px;
  background: #dbeafe;
}

.reader-access-card__eyebrow {
  color: #0e7490;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.reader-access-card h2 {
  margin: 0;
  color: #172033;
}

.reader-access-card p {
  max-width: 620px;
  margin: 0 0 8px;
  color: #64748b;
  line-height: 1.7;
}

.details-dialog {
  overflow: hidden;
}

.details-dialog__header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px 24px;
}

.details-dialog__icon {
  display: grid;
  width: 46px;
  height: 46px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 14px;
  background: #dbeafe;
}

.details-dialog__header > div:nth-child(2) {
  display: grid;
}

.details-dialog__header strong {
  color: #172033;
  font-size: 1.05rem;
}

.details-dialog__header span {
  margin-top: 2px;
  color: #64748b;
  font-size: 0.76rem;
}

.details-dialog__body {
  display: grid;
  gap: 22px;
  padding: 24px;
}

.details-dialog__chips,
.details-conditions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 13px;
}

.details-item {
  display: grid;
  gap: 5px;
  padding: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  background: #f8fafc;
}

.details-item--full {
  grid-column: 1 / -1;
}

.details-item span,
.details-text span {
  color: #0e7490;
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.details-item strong {
  color: #27354b;
  font-size: 0.84rem;
}

.details-text {
  padding: 17px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
}

.details-text p {
  margin: 9px 0 0;
  color: #475569;
  line-height: 1.65;
  white-space: pre-wrap;
}

.details-condition {
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
}

.details-condition--red {
  color: #b91c1c;
  background: #fee2e2;
}

.details-condition--yellow {
  color: #a16207;
  background: #fef3c7;
}

.details-condition--blue {
  color: #1d4ed8;
  background: #dbeafe;
}

.details-dialog__actions {
  padding: 0 24px 20px;
}

@media (max-width: 1180px) {
  .manifestations-workspace {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .page-header {
    display: grid;
  }

  .page-header .v-btn {
    width: 100%;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .details-item--full {
    grid-column: auto;
  }

  .details-dialog__header {
    align-items: flex-start;
  }
}
</style>
