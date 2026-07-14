<script setup lang="ts">
import { ref } from 'vue'

import AuthLayout from '@/layouts/AuthLayout.vue'

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
</script>

<template>
  <AuthLayout>
    <v-card class="login-card pa-6 pa-sm-8" color="surface">
      <div class="login-card__header">
        <div class="login-card__icon" aria-hidden="true">
          <v-icon icon="mdi-login-variant" color="primary" size="28" />
        </div>

        <p class="login-card__eyebrow">Acesso ao sistema</p>
        <h2 class="login-card__title">Bem-vindo de volta</h2>
        <p class="login-card__subtitle">Entre com suas credenciais para acessar o SIGEM.</p>
      </div>

      <v-alert
        type="info"
        variant="tonal"
        density="comfortable"
        icon="mdi-shield-lock-outline"
        class="mb-6"
      >
        Ambiente seguro e de acesso restrito.
      </v-alert>

      <v-form @submit.prevent>
        <div class="field-group">
          <label class="field-label" for="email">E-mail institucional</label>

          <v-text-field
            id="email"
            v-model="email"
            type="email"
            placeholder="nome@instituicao.gov.br"
            prepend-inner-icon="mdi-email-outline"
            autocomplete="username"
            hide-details="auto"
          />
        </div>

        <div class="field-group">
          <div class="field-label-row">
            <label class="field-label" for="password">Senha</label>

            <button class="forgot-password" type="button">Esqueci minha senha</button>
          </div>

          <v-text-field
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Digite sua senha"
            prepend-inner-icon="mdi-lock-outline"
            :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
            autocomplete="current-password"
            hide-details="auto"
            @click:append-inner="showPassword = !showPassword"
          />
        </div>

        <v-checkbox
          v-model="remember"
          label="Manter-me conectado neste dispositivo"
          color="primary"
          density="comfortable"
          hide-details
          class="mb-5"
        />

        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          prepend-icon="mdi-arrow-right-circle-outline"
        >
          Entrar
        </v-btn>
      </v-form>

      <div class="login-card__divider">
        <v-divider />
        <span>ou</span>
        <v-divider />
      </div>

      <div class="login-card__request">
        <p>Ainda não possui acesso?</p>

        <v-btn variant="tonal" color="secondary" block prepend-icon="mdi-account-plus-outline">
          Solicitar cadastro
        </v-btn>
      </div>

      <p class="login-card__footer">
        Ao acessar, você concorda com as políticas de segurança e uso responsável das informações.
      </p>
    </v-card>
  </AuthLayout>
</template>

<style scoped>
.login-card {
  border: 1px solid rgb(22 58 95 / 8%);
  box-shadow: 0 24px 60px rgb(22 58 95 / 10%) !important;
}

.login-card__header {
  margin-bottom: 28px;
}

.login-card__icon {
  display: grid;
  width: 54px;
  height: 54px;
  margin-bottom: 20px;
  place-items: center;
  border-radius: 17px;
  background: rgb(22 58 95 / 9%);
}

.login-card__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.login-card__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.8rem, 4vw, 2.35rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.login-card__subtitle {
  margin: 12px 0 0;
  color: #64748b;
  line-height: 1.6;
}

.field-group {
  margin-bottom: 20px;
}

.field-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.field-label {
  display: inline-block;
  margin-bottom: 8px;
  color: #334155;
  font-size: 0.88rem;
  font-weight: 700;
}

.forgot-password {
  margin-bottom: 8px;
  border: 0;
  background: transparent;
  color: #0e7490;
  cursor: pointer;
  font: inherit;
  font-size: 0.82rem;
  font-weight: 700;
}

.forgot-password:hover {
  text-decoration: underline;
}

.forgot-password:focus-visible {
  outline: 3px solid rgb(14 116 144 / 24%);
  outline-offset: 3px;
  border-radius: 4px;
}

.login-card__divider {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 14px;
  margin: 28px 0 22px;
  color: #94a3b8;
  font-size: 0.78rem;
}

.login-card__request p {
  margin: 0 0 12px;
  color: #64748b;
  font-size: 0.9rem;
  text-align: center;
}

.login-card__footer {
  margin: 24px 0 0;
  color: #94a3b8;
  font-size: 0.72rem;
  line-height: 1.6;
  text-align: center;
}

@media (max-width: 599px) {
  .login-card {
    box-shadow: none !important;
  }
}
</style>
