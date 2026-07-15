<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'

import { useAuthStore } from '@/stores/auth'

import type { UserRole } from '@/types/auth'

const router = useRouter()
const auth = useAuthStore()
const { mobile } = useDisplay()

const drawer = ref(true)

const roleLabels: Record<UserRole, string> = {
  administrator: 'Administrador',
  manager: 'Gestor',
  operator: 'Operador',
  reader: 'Leitor',
}

const userInitials = computed(() => {
  const name = auth.user?.name.trim() ?? ''

  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

const userRoleLabel = computed(() => {
  const role = auth.user?.role

  return role ? roleLabels[role] : ''
})

watch(
  mobile,
  (isMobile) => {
    drawer.value = !isMobile
  },
  {
    immediate: true,
  },
)

async function handleLogout(): Promise<void> {
  await auth.logout()

  await router.replace({
    name: 'login',
  })
}
</script>

<template>
  <v-navigation-drawer v-model="drawer" :temporary="mobile" color="primary" width="280">
    <div class="brand">
      <div class="brand__icon">
        <v-icon icon="mdi-file-document-check-outline" size="30" />
      </div>

      <div>
        <p class="brand__name">SIGEM</p>
        <p class="brand__description">Gestão de Manifestações</p>
      </div>
    </div>

    <v-divider class="mb-3" />

    <v-list nav density="comfortable" class="px-3">
      <v-list-subheader class="menu-subheader"> Visão geral </v-list-subheader>

      <v-list-item
        :to="{ name: 'dashboard' }"
        prepend-icon="mdi-view-dashboard-outline"
        title="Painel"
        rounded="lg"
        exact
      />

      <template v-if="auth.isAdministrator">
        <v-list-subheader class="menu-subheader mt-4"> Administração </v-list-subheader>

        <v-list-item
          :to="{ name: 'admin-users' }"
          prepend-icon="mdi-account-group-outline"
          title="Usuários"
          rounded="lg"
        />
      </template>
    </v-list>

    <template #append>
      <div class="drawer-footer pa-4">
        <v-btn
          :loading="auth.loading"
          variant="tonal"
          color="white"
          prepend-icon="mdi-logout"
          block
          @click="handleLogout"
        >
          Sair do sistema
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>

  <v-app-bar color="surface" elevation="0" border>
    <v-app-bar-nav-icon aria-label="Abrir ou fechar menu" @click="drawer = !drawer" />

    <v-app-bar-title class="app-title"> Sistema de Gestão de Manifestações </v-app-bar-title>

    <div class="user-summary">
      <div class="user-summary__text">
        <strong>{{ auth.user?.name }}</strong>
        <span>{{ userRoleLabel }}</span>
      </div>

      <v-avatar color="primary" size="42">
        <span class="user-summary__initials">
          {{ userInitials }}
        </span>
      </v-avatar>
    </div>
  </v-app-bar>

  <v-main class="main-content">
    <RouterView />
  </v-main>
</template>

<style scoped>
.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px 20px;
}

.brand__icon {
  display: grid;
  width: 48px;
  height: 48px;
  flex: 0 0 auto;
  place-items: center;
  border: 1px solid rgb(255 255 255 / 22%);
  border-radius: 15px;
  background: rgb(255 255 255 / 10%);
}

.brand__name {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: 0.04em;
}

.brand__description {
  margin: 2px 0 0;
  color: rgb(255 255 255 / 68%);
  font-size: 0.75rem;
}

.menu-subheader {
  color: rgb(255 255 255 / 58%) !important;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.drawer-footer {
  border-top: 1px solid rgb(255 255 255 / 12%);
}

.app-title {
  color: #172033;
  font-size: 1rem;
  font-weight: 800;
}

.user-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-right: 20px;
}

.user-summary__text {
  display: grid;
  text-align: right;
}

.user-summary__text strong {
  color: #172033;
  font-size: 0.86rem;
}

.user-summary__text span {
  color: #64748b;
  font-size: 0.72rem;
}

.user-summary__initials {
  font-size: 0.78rem;
  font-weight: 800;
}

.main-content {
  min-height: 100vh;
  background: #f1f5f9;
}

@media (max-width: 700px) {
  .app-title,
  .user-summary__text {
    display: none;
  }

  .user-summary {
    padding-right: 12px;
  }
}
</style>
