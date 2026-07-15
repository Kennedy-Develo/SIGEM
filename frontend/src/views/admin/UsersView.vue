<script setup lang="ts">
import { useDisplay } from 'vuetify'
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

import { useAuthStore } from '@/stores/auth'
import { useUsersStore } from '@/stores/users'

import type { ManagedUser, UpdateUserAccess, UserFilters } from '@/types/admin'
import type { UserRole, UserStatus } from '@/types/auth'

interface ErrorResponse {
  message?: string
}

const auth = useAuthStore()
const usersStore = useUsersStore()
const { mobile } = useDisplay()

const search = ref('')
const roleFilter = ref<UserRole | 'all'>('all')
const statusFilter = ref<UserStatus | 'all'>('all')

const dialogOpen = ref(false)
const selectedUser = ref<ManagedUser | null>(null)
const selectedRole = ref<UserRole>('reader')
const selectedStatus = ref<'active' | 'blocked'>('active')

const errorMessage = ref('')
const successMessage = ref('')

const roleOptions = [
  {
    title: 'Administrador',
    value: 'administrator',
  },
  {
    title: 'Gestor',
    value: 'manager',
  },
  {
    title: 'Operador',
    value: 'operator',
  },
  {
    title: 'Leitor',
    value: 'reader',
  },
]

const statusOptions = [
  {
    title: 'Todos os status',
    value: 'all',
  },
  {
    title: 'Pendente',
    value: 'pending',
  },
  {
    title: 'Ativo',
    value: 'active',
  },
  {
    title: 'Bloqueado',
    value: 'blocked',
  },
]

const roleFilterOptions = [
  {
    title: 'Todos os perfis',
    value: 'all',
  },
  ...roleOptions,
]

const updateStatusOptions = [
  {
    title: 'Ativo',
    value: 'active',
  },
  {
    title: 'Bloqueado',
    value: 'blocked',
  },
]

const roleLabels: Record<UserRole, string> = {
  administrator: 'Administrador',
  manager: 'Gestor',
  operator: 'Operador',
  reader: 'Leitor',
}

const statusLabels: Record<UserStatus, string> = {
  pending: 'Pendente',
  active: 'Ativo',
  blocked: 'Bloqueado',
}

const pendingOnPage = computed(
  () => usersStore.users.filter((user) => user.status === 'pending').length,
)

function statusColor(status: UserStatus): string {
  const colors: Record<UserStatus, string> = {
    pending: 'warning',
    active: 'success',
    blocked: 'error',
  }

  return colors[status]
}

function roleColor(role: UserRole): string {
  const colors: Record<UserRole, string> = {
    administrator: 'primary',
    manager: 'secondary',
    operator: 'info',
    reader: 'default',
  }

  return colors[role]
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
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

function buildFilters(page = 1): UserFilters {
  const filters: UserFilters = {
    page,
  }

  const normalizedSearch = search.value.trim()

  if (normalizedSearch) {
    filters.search = normalizedSearch
  }

  if (roleFilter.value !== 'all') {
    filters.role = roleFilter.value
  }

  if (statusFilter.value !== 'all') {
    filters.status = statusFilter.value
  }

  return filters
}

async function loadUsers(page = 1): Promise<void> {
  errorMessage.value = ''

  try {
    await usersStore.fetchUsers(buildFilters(page))
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

function clearFilters(): void {
  search.value = ''
  roleFilter.value = 'all'
  statusFilter.value = 'all'

  void loadUsers()
}

function openAccessDialog(user: ManagedUser): void {
  selectedUser.value = user
  selectedRole.value = user.role
  selectedStatus.value = user.status === 'blocked' ? 'blocked' : 'active'
  successMessage.value = ''
  errorMessage.value = ''
  dialogOpen.value = true
}

function closeAccessDialog(): void {
  dialogOpen.value = false
  selectedUser.value = null
}

async function saveUserAccess(): Promise<void> {
  if (!selectedUser.value) {
    return
  }

  errorMessage.value = ''
  successMessage.value = ''

  const access: UpdateUserAccess = {
    role: selectedRole.value,
    status: selectedStatus.value,
  }

  try {
    const response = await usersStore.updateUserAccess(selectedUser.value.id, access)

    successMessage.value = response.message
    closeAccessDialog()

    await loadUsers(usersStore.currentPage)
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <v-container class="users-page py-6 py-md-8" fluid>
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">Administração</p>

        <h1 class="page-header__title">Gestão de usuários</h1>

        <p class="page-header__description">
          Aprove solicitações, defina perfis e controle o acesso ao SIGEM.
        </p>
      </div>

      <v-btn
        color="primary"
        prepend-icon="mdi-refresh"
        :loading="usersStore.loading"
        @click="loadUsers(usersStore.currentPage)"
      >
        Atualizar
      </v-btn>
    </div>

    <v-row class="mb-2">
      <v-col cols="12" sm="6">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon summary-card__icon--primary">
            <v-icon icon="mdi-account-group-outline" />
          </div>

          <div>
            <p class="summary-card__label">Usuários encontrados</p>

            <strong class="summary-card__value">
              {{ usersStore.total }}
            </strong>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6">
        <v-card class="summary-card pa-5" rounded="xl">
          <div class="summary-card__icon summary-card__icon--warning">
            <v-icon icon="mdi-account-clock-outline" />
          </div>

          <div>
            <p class="summary-card__label">Pendentes nesta página</p>

            <strong class="summary-card__value">
              {{ pendingOnPage }}
            </strong>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-alert
      v-if="successMessage"
      type="success"
      variant="tonal"
      closable
      class="mb-5"
      @click:close="successMessage = ''"
    >
      {{ successMessage }}
    </v-alert>

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
        <v-col cols="12" md="5">
          <v-text-field
            v-model="search"
            label="Pesquisar usuário"
            placeholder="Nome ou e-mail"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            @keyup.enter="loadUsers()"
          />
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-select v-model="statusFilter" :items="statusOptions" label="Status" hide-details />
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-select v-model="roleFilter" :items="roleFilterOptions" label="Perfil" hide-details />
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-btn
            color="primary"
            prepend-icon="mdi-filter-outline"
            block
            height="48"
            @click="loadUsers()"
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
    </v-card>

    <v-card rounded="xl" class="users-card">
      <v-progress-linear v-if="usersStore.loading" color="primary" indeterminate />

      <div class="users-table-wrapper">
        <v-table class="users-table">
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Perfil</th>
              <th>Status</th>
              <th>Solicitado em</th>
              <th>Último acesso</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="user in usersStore.users" :key="user.id">
              <td>
                <div class="user-cell">
                  <v-avatar color="primary" variant="tonal" size="42">
                    <v-icon icon="mdi-account-outline" />
                  </v-avatar>

                  <div>
                    <strong>{{ user.name }}</strong>
                    <span>{{ user.email }}</span>
                  </div>
                </div>
              </td>

              <td>
                <v-chip :color="roleColor(user.role)" variant="tonal" size="small">
                  {{ roleLabels[user.role] }}
                </v-chip>
              </td>

              <td>
                <v-chip :color="statusColor(user.status)" variant="tonal" size="small">
                  {{ statusLabels[user.status] }}
                </v-chip>
              </td>

              <td>{{ formatDate(user.created_at) }}</td>

              <td>{{ formatDate(user.last_login_at) }}</td>

              <td class="text-right">
                <v-tooltip
                  :text="
                    user.id === auth.user?.id
                      ? 'Você não pode alterar seu próprio acesso'
                      : 'Gerenciar acesso'
                  "
                >
                  <template #activator="{ props }">
                    <v-btn
                      v-bind="props"
                      color="primary"
                      variant="tonal"
                      size="small"
                      prepend-icon="mdi-account-cog-outline"
                      :disabled="user.id === auth.user?.id"
                      @click="openAccessDialog(user)"
                    >
                      Gerenciar
                    </v-btn>
                  </template>
                </v-tooltip>
              </td>
            </tr>

            <tr v-if="!usersStore.loading && usersStore.users.length === 0">
              <td colspan="6">
                <div class="empty-state">
                  <v-icon icon="mdi-account-search-outline" size="46" color="grey" />

                  <strong>Nenhum usuário encontrado</strong>

                  <span> Ajuste os filtros e tente novamente. </span>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <v-divider />

      <div class="pagination">
        <span> {{ usersStore.total }} usuário(s) </span>

        <v-pagination
          v-if="usersStore.lastPage > 1"
          :model-value="usersStore.currentPage"
          :length="usersStore.lastPage"
          :total-visible="mobile ? 3 : 7"
          density="comfortable"
          @update:model-value="loadUsers"
        />
      </div>
    </v-card>

    <v-dialog v-model="dialogOpen" max-width="560" persistent>
      <v-card rounded="xl">
        <v-card-title class="dialog-title">
          <div class="dialog-title__icon">
            <v-icon icon="mdi-account-cog-outline" color="primary" />
          </div>

          <div>
            <strong>Gerenciar acesso</strong>
            <span>{{ selectedUser?.name }}</span>
          </div>
        </v-card-title>

        <v-card-text class="pt-2">
          <v-alert
            v-if="selectedUser?.status === 'pending'"
            type="warning"
            variant="tonal"
            density="comfortable"
            class="mb-5"
          >
            Ao salvar como ativo, esta solicitação será aprovada.
          </v-alert>

          <v-select
            v-model="selectedRole"
            :items="roleOptions"
            label="Perfil de acesso"
            prepend-inner-icon="mdi-shield-account-outline"
            class="mb-3"
          />

          <v-select
            v-model="selectedStatus"
            :items="updateStatusOptions"
            label="Status da conta"
            prepend-inner-icon="mdi-account-check-outline"
          />

          <v-alert
            v-if="selectedStatus === 'blocked'"
            type="error"
            variant="tonal"
            density="comfortable"
            class="mt-2"
          >
            O usuário perderá o acesso e suas sessões serão encerradas.
          </v-alert>
        </v-card-text>

        <v-card-actions class="pa-5 pt-0">
          <v-spacer />

          <v-btn
            variant="text"
            :disabled="usersStore.updatingUserId !== null"
            @click="closeAccessDialog"
          >
            Cancelar
          </v-btn>

          <v-btn
            color="primary"
            prepend-icon="mdi-content-save-outline"
            :loading="usersStore.updatingUserId === selectedUser?.id"
            @click="saveUserAccess"
          >
            Salvar alterações
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.users-page {
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
  place-items: center;
  border-radius: 16px;
}

.summary-card__icon--primary {
  color: #1d4ed8;
  background: #dbeafe;
}

.summary-card__icon--warning {
  color: #b45309;
  background: #fef3c7;
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
.users-card,
.summary-card {
  border: 1px solid rgb(15 23 42 / 6%);
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%) !important;
}

.users-table-wrapper {
  overflow-x: auto;
}

.users-table {
  min-width: 980px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.user-cell div {
  display: grid;
}

.user-cell strong {
  color: #172033;
  font-size: 0.88rem;
}

.user-cell span {
  color: #64748b;
  font-size: 0.76rem;
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
