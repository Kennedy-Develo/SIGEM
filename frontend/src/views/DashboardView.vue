<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

import type { UserRole } from '@/types/auth'

const auth = useAuthStore()

const roleLabels: Record<UserRole, string> = {
  administrator: 'Administrador',
  manager: 'Gestor',
  operator: 'Operador',
  reader: 'Leitor',
}
</script>

<template>
  <v-container class="dashboard-page py-6 py-md-8" fluid>
    <div class="welcome">
      <div>
        <p class="welcome__eyebrow">Visão geral</p>

        <h1 class="welcome__title">Olá, {{ auth.user?.name }}</h1>

        <p class="welcome__description">
          Bem-vindo ao SIGEM. Acompanhe as principais informações do sistema.
        </p>
      </div>

      <v-chip color="success" prepend-icon="mdi-shield-check-outline" variant="tonal" size="large">
        Sessão autenticada
      </v-chip>
    </div>

    <v-row>
      <v-col cols="12" sm="6" lg="3">
        <v-card class="metric-card pa-5" rounded="xl">
          <div class="metric-card__icon metric-card__icon--primary">
            <v-icon icon="mdi-file-document-outline" />
          </div>

          <p class="metric-card__label">Manifestações</p>

          <strong class="metric-card__value">0</strong>

          <span class="metric-card__detail"> Módulo ainda não iniciado </span>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="metric-card pa-5" rounded="xl">
          <div class="metric-card__icon metric-card__icon--warning">
            <v-icon icon="mdi-clock-outline" />
          </div>

          <p class="metric-card__label">Prazos próximos</p>

          <strong class="metric-card__value">0</strong>

          <span class="metric-card__detail"> Nenhum prazo cadastrado </span>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="metric-card pa-5" rounded="xl">
          <div class="metric-card__icon metric-card__icon--success">
            <v-icon icon="mdi-check-circle-outline" />
          </div>

          <p class="metric-card__label">Concluídas</p>

          <strong class="metric-card__value">0</strong>

          <span class="metric-card__detail"> Nenhum registro concluído </span>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="metric-card pa-5" rounded="xl">
          <div class="metric-card__icon metric-card__icon--info">
            <v-icon icon="mdi-account-group-outline" />
          </div>

          <p class="metric-card__label">Seu perfil</p>

          <strong class="metric-card__profile">
            {{ auth.user ? roleLabels[auth.user.role] : '—' }}
          </strong>

          <span class="metric-card__detail"> Acesso controlado por perfil </span>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-2">
      <v-col cols="12" lg="8">
        <v-card class="content-card pa-6" rounded="xl">
          <div class="content-card__header">
            <div>
              <p class="content-card__eyebrow">Estrutura do sistema</p>

              <h2>Fundação concluída</h2>
            </div>

            <v-icon icon="mdi-office-building-check-outline" color="primary" size="34" />
          </div>

          <p class="content-card__description">
            Autenticação, perfis, aprovação de usuários e proteção de rotas estão sendo construídos
            antes do módulo de manifestações.
          </p>

          <v-list class="foundation-list">
            <v-list-item prepend-icon="mdi-check-circle" title="Autenticação segura por sessão" />

            <v-list-item
              prepend-icon="mdi-check-circle"
              title="Cadastro com aprovação administrativa"
            />

            <v-list-item prepend-icon="mdi-check-circle" title="Perfis e status de acesso" />

            <v-list-item prepend-icon="mdi-check-circle" title="Gestão visual de usuários" />

            <v-list-item prepend-icon="mdi-check-circle" title="Recuperação segura de senha" />
          </v-list>
        </v-card>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card class="content-card pa-6" rounded="xl">
          <p class="content-card__eyebrow">Próxima ação</p>

          <h2>
            {{ auth.isAdministrator ? 'Gerencie os acessos' : 'Aguarde os próximos módulos' }}
          </h2>

          <p class="content-card__description">
            {{
              auth.isAdministrator
                ? 'Analise solicitações pendentes e defina o perfil de cada usuário.'
                : 'Novos recursos serão disponibilizados conforme o projeto evoluir.'
            }}
          </p>

          <v-btn
            v-if="auth.isAdministrator"
            :to="{ name: 'admin-users' }"
            color="primary"
            prepend-icon="mdi-account-cog-outline"
            block
            class="mt-5"
          >
            Gerenciar usuários
          </v-btn>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.dashboard-page {
  max-width: 1500px;
}

.welcome {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 28px;
}

.welcome__eyebrow,
.content-card__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.welcome__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.9rem, 4vw, 2.7rem);
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.welcome__description {
  margin: 10px 0 0;
  color: #64748b;
}

.metric-card,
.content-card {
  height: 100%;
  border: 1px solid rgb(15 23 42 / 6%);
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%) !important;
}

.metric-card__icon {
  display: grid;
  width: 48px;
  height: 48px;
  margin-bottom: 20px;
  place-items: center;
  border-radius: 15px;
}

.metric-card__icon--primary {
  color: #1d4ed8;
  background: #dbeafe;
}

.metric-card__icon--warning {
  color: #b45309;
  background: #fef3c7;
}

.metric-card__icon--success {
  color: #15803d;
  background: #dcfce7;
}

.metric-card__icon--info {
  color: #0e7490;
  background: #cffafe;
}

.metric-card__label {
  margin: 0 0 6px;
  color: #64748b;
  font-size: 0.8rem;
}

.metric-card__value {
  display: block;
  color: #172033;
  font-size: 2rem;
}

.metric-card__profile {
  display: block;
  color: #172033;
  font-size: 1.15rem;
}

.metric-card__detail {
  display: block;
  margin-top: 8px;
  color: #94a3b8;
  font-size: 0.74rem;
}

.content-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}

.content-card h2 {
  margin: 0;
  color: #172033;
  font-size: 1.25rem;
}

.content-card__description {
  margin: 16px 0 0;
  color: #64748b;
  line-height: 1.7;
}

.foundation-list {
  margin-top: 16px;
}

.foundation-list :deep(.v-icon) {
  color: #16a34a;
}

@media (max-width: 700px) {
  .welcome {
    display: grid;
  }
}
</style>
