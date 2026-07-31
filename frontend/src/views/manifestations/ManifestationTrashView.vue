<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'

import { useManifestationsStore } from '@/stores/manifestations'

import type { Manifestation } from '@/types/manifestation'

interface ErrorResponse {
  message?: string
}

const manifestationsStore = useManifestationsStore()

const selectedPerPage = ref<15 | 25 | 50>(15)
const selectedManifestation = ref<Manifestation | null>(null)

const restoreDialogOpen = ref(false)
const restoreReason = ref('')
const restoring = ref(false)

const successMessage = ref('')
const errorMessage = ref('')

const perPageOptions: Array<{
  title: string
  value: 15 | 25 | 50
}> = [
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

function formatDate(value: string | null): string {
  if (!value) {
    return 'Não informado'
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value))
}

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível concluir a operação.'
  }

  return error.response?.data.message ?? 'Não foi possível concluir a operação.'
}

async function loadTrash(page = 1): Promise<void> {
  errorMessage.value = ''

  try {
    await manifestationsStore.fetchTrashedManifestations(
      page,
      selectedPerPage.value,
    )
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

function openRestoreDialog(manifestation: Manifestation): void {
  selectedManifestation.value = manifestation
  restoreReason.value = ''
  errorMessage.value = ''
  restoreDialogOpen.value = true
}

function closeRestoreDialog(): void {
  restoreDialogOpen.value = false
  restoreReason.value = ''
  selectedManifestation.value = null
}

async function confirmRestore(): Promise<void> {
  const manifestation = selectedManifestation.value
  const reason = restoreReason.value.trim()

  if (!manifestation) {
    return
  }

  if (!reason) {
    errorMessage.value = 'Informe o motivo da restauração.'
    return
  }

  restoring.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await manifestationsStore.restoreManifestation(
      manifestation.id,
      reason,
    )

    const currentPage = manifestationsStore.trashCurrentPage

    await loadTrash(currentPage)

    if (
      currentPage > 1 &&
      manifestationsStore.trashedManifestations.length === 0
    ) {
      await loadTrash(currentPage - 1)
    }

    closeRestoreDialog()

    successMessage.value = 'Manifestação restaurada com sucesso.'
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  } finally {
    restoring.value = false
  }
}

function changePage(page: number): void {
  void loadTrash(page)
}

function changePerPage(): void {
  void loadTrash(1)
}

onMounted(() => {
  void loadTrash()
})
</script>

<template>
  <v-container class="trash-page py-6 py-md-8" fluid>
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">Administração</p>

        <h1 class="page-header__title">
          Lixeira de manifestações
        </h1>

        <p class="page-header__description">
          Consulte manifestações removidas e restaure registros quando
          necessário.
        </p>
      </div>

      <v-btn
        color="primary"
        prepend-icon="mdi-refresh"
        :loading="manifestationsStore.trashLoading"
        @click="loadTrash(manifestationsStore.trashCurrentPage)"
      >
        Atualizar
      </v-btn>
    </div>

    <v-alert
      v-if="successMessage"
      class="mb-5"
      type="success"
      variant="tonal"
      closable
      @click:close="successMessage = ''"
    >
      {{ successMessage }}
    </v-alert>

    <v-alert
      v-if="errorMessage && !restoreDialogOpen"
      class="mb-5"
      type="error"
      variant="tonal"
      closable
      @click:close="errorMessage = ''"
    >
      {{ errorMessage }}
    </v-alert>

    <v-row class="mb-2">
      <v-col cols="12" md="8">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon">
            <v-icon icon="mdi-delete-clock-outline" size="28" />
          </div>

          <div>
            <p class="summary-card__label">
              Manifestações na lixeira
            </p>

            <strong class="summary-card__value">
              {{ manifestationsStore.trashTotal }}
            </strong>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card class="summary-card pa-5" rounded="xl">
          <v-select
            v-model="selectedPerPage"
            :items="perPageOptions"
            label="Exibição"
            prepend-inner-icon="mdi-format-list-numbered"
            hide-details
            @update:model-value="changePerPage"
          />
        </v-card>
      </v-col>
    </v-row>

    <v-card class="trash-card" rounded="xl">
      <v-progress-linear
        v-if="manifestationsStore.trashLoading"
        color="primary"
        indeterminate
      />

      <div class="trash-table-wrapper">
        <v-table class="trash-table">
          <thead>
            <tr>
              <th>NUP</th>
              <th>Resumo</th>
              <th>Enviada para a lixeira em</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="manifestation in manifestationsStore.trashedManifestations"
              :key="manifestation.id"
            >
              <td>
                <div class="nup-cell">
                  <v-icon
                    icon="mdi-file-document-outline"
                    color="primary"
                    size="20"
                  />

                  <strong>{{ manifestation.nup }}</strong>
                </div>
              </td>

              <td>
                <span class="summary-text">
                  {{ manifestation.summary || 'Resumo não informado' }}
                </span>
              </td>

              <td>
                <div class="date-cell">
                  <v-icon
                    icon="mdi-clock-outline"
                    color="grey"
                    size="18"
                  />

                  <span>
                    {{ formatDate(manifestation.deleted_at) }}
                  </span>
                </div>
              </td>

              <td class="text-right">
                <v-btn
                  color="success"
                  variant="tonal"
                  size="small"
                  prepend-icon="mdi-backup-restore"
                  @click="openRestoreDialog(manifestation)"
                >
                  Restaurar
                </v-btn>
              </td>
            </tr>

            <tr
              v-if="
                !manifestationsStore.trashLoading &&
                manifestationsStore.trashedManifestations.length === 0
              "
            >
              <td colspan="4">
                <div class="empty-state">
                  <v-icon
                    icon="mdi-delete-empty-outline"
                    size="54"
                    color="grey"
                  />

                  <strong>A lixeira está vazia</strong>

                  <span>
                    Nenhuma manifestação foi enviada para a lixeira.
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <v-divider />

      <div class="pagination">
        <span>
          {{ manifestationsStore.trashTotal }} registro(s)
        </span>

        <v-pagination
          v-if="manifestationsStore.trashLastPage > 1"
          :model-value="manifestationsStore.trashCurrentPage"
          :length="manifestationsStore.trashLastPage"
          :total-visible="7"
          density="comfortable"
          @update:model-value="changePage"
        />
      </div>
    </v-card>

    <v-dialog
      v-model="restoreDialogOpen"
      max-width="560"
      persistent
    >
      <v-card rounded="xl">
        <v-card-title class="dialog-title">
          <div class="dialog-title__icon">
            <v-icon
              icon="mdi-backup-restore"
              color="success"
              size="26"
            />
          </div>

          <div>
            <strong>Restaurar manifestação</strong>

            <span>
              NUP {{ selectedManifestation?.nup }}
            </span>
          </div>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-5">
          <v-alert
            type="info"
            variant="tonal"
            class="mb-5"
          >
            A manifestação voltará a aparecer na lista principal.
          </v-alert>

          <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-5"
            closable
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </v-alert>

          <v-textarea
            v-model="restoreReason"
            label="Motivo da restauração"
            placeholder="Explique por que esta manifestação será restaurada."
            prepend-inner-icon="mdi-text-box-outline"
            maxlength="2000"
            counter
            rows="4"
            auto-grow
            :disabled="restoring"
          />
        </v-card-text>

        <v-card-actions class="pa-5 pt-0">
          <v-spacer />

          <v-btn
            variant="text"
            :disabled="restoring"
            @click="closeRestoreDialog"
          >
            Cancelar
          </v-btn>

          <v-btn
            color="success"
            variant="flat"
            prepend-icon="mdi-backup-restore"
            :loading="restoring"
            @click="confirmRestore"
          >
            Restaurar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.trash-page {
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

.summary-card,
.trash-card {
  border: 1px solid rgb(15 23 42 / 6%);
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%) !important;
}

.summary-card {
  display: flex;
  min-height: 92px;
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
  color: #b91c1c;
  background: #fee2e2;
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

.trash-table-wrapper {
  overflow-x: auto;
}

.trash-table {
  min-width: 900px;
}

.nup-cell,
.date-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.nup-cell strong {
  color: #172033;
  white-space: nowrap;
}

.date-cell {
  color: #475569;
  white-space: nowrap;
}

.summary-text {
  display: inline-block;
  max-width: 480px;
  overflow-wrap: anywhere;
  color: #334155;
}

.empty-state {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 64px 20px;
  color: #64748b;
  text-align: center;
}

.empty-state strong {
  color: #334155;
  font-size: 1rem;
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
  background: #dcfce7;
}

.dialog-title > div:last-child {
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
}
</style>
