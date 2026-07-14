<script setup lang="ts">
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function handleLogout(): Promise<void> {
  await auth.logout()

  await router.replace({
    name: 'login',
  })
}
</script>

<template>
  <v-app>
    <v-app-bar color="primary" elevation="0">
      <v-app-bar-title>
        <strong>SIGEM</strong>
      </v-app-bar-title>

      <v-btn :loading="auth.loading" prepend-icon="mdi-logout" variant="text" @click="handleLogout">
        Sair
      </v-btn>
    </v-app-bar>

    <v-main class="dashboard">
      <v-container class="py-10">
        <v-card class="pa-6 pa-sm-8" rounded="xl">
          <v-icon icon="mdi-view-dashboard-outline" color="primary" size="40" class="mb-4" />

          <p class="dashboard__eyebrow">Área protegida</p>

          <h1 class="dashboard__title">Olá, {{ auth.user?.name }}</h1>

          <p class="dashboard__description">
            Sua autenticação foi realizada com sucesso. O painel completo será construído nas
            próximas etapas.
          </p>

          <v-chip
            color="success"
            prepend-icon="mdi-shield-check-outline"
            variant="tonal"
            class="mt-4"
          >
            Sessão autenticada
          </v-chip>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f1f5f9;
}

.dashboard__eyebrow {
  margin: 0 0 8px;
  color: #0e7490;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.dashboard__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  line-height: 1.15;
}

.dashboard__description {
  max-width: 680px;
  margin: 16px 0 0;
  color: #64748b;
  line-height: 1.7;
}
</style>
