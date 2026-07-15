<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'

import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

interface ErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

const auth = useAuthStore()

const formValid = ref(false)
const email = ref('')
const errorMessage = ref('')
const successMessage = ref('')

const emailRules = [
  (value: string) => Boolean(value) || 'Informe seu e-mail.',
  (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Informe um e-mail válido.',
]

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível solicitar a recuperação. Tente novamente.'
  }

  const response = error.response?.data
  const firstValidationMessage = response?.errors
    ? Object.values(response.errors)[0]?.[0]
    : undefined

  return (
    firstValidationMessage ??
    response?.message ??
    'Não foi possível solicitar a recuperação. Tente novamente.'
  )
}

async function handleForgotPassword(): Promise<void> {
  errorMessage.value = ''
  successMessage.value = ''

  if (!formValid.value) {
    errorMessage.value = 'Informe um endereço de e-mail válido.'

    return
  }

  try {
    const response = await auth.requestPasswordReset({
      email: email.value.trim().toLowerCase(),
    })

    successMessage.value = response.message
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}
</script>

<template>
  <AuthLayout>
    <v-card class="recovery-card pa-6 pa-sm-8" color="surface">
      <div class="recovery-card__header">
        <div class="recovery-card__icon" aria-hidden="true">
          <v-icon icon="mdi-lock-reset" color="primary" size="28" />
        </div>

        <p class="recovery-card__eyebrow">Recuperação de acesso</p>

        <h2 class="recovery-card__title">Esqueceu sua senha?</h2>

        <p class="recovery-card__subtitle">
          Informe o e-mail cadastrado para receber as instruções de redefinição.
        </p>
      </div>

      <v-alert
        type="info"
        variant="tonal"
        density="comfortable"
        icon="mdi-shield-check-outline"
        class="mb-6"
      >
        Por segurança, a resposta será a mesma mesmo que o e-mail não esteja cadastrado.
      </v-alert>

      <v-alert
        v-if="successMessage"
        type="success"
        variant="tonal"
        density="comfortable"
        icon="mdi-email-check-outline"
        class="mb-6"
      >
        {{ successMessage }}
      </v-alert>

      <v-alert
        v-if="errorMessage"
        type="error"
        variant="tonal"
        density="comfortable"
        closable
        class="mb-6"
        @click:close="errorMessage = ''"
      >
        {{ errorMessage }}
      </v-alert>

      <v-form v-if="!successMessage" v-model="formValid" @submit.prevent="handleForgotPassword">
        <label class="field-label" for="recovery-email"> E-mail </label>

        <v-text-field
          id="recovery-email"
          v-model="email"
          type="email"
          placeholder="nome@instituicao.gov.br"
          prepend-inner-icon="mdi-email-outline"
          :rules="emailRules"
          autocomplete="email"
          variant="outlined"
          class="mb-3"
        />

        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          prepend-icon="mdi-email-arrow-right-outline"
          :loading="auth.loading"
        >
          Enviar instruções
        </v-btn>
      </v-form>

      <v-btn
        v-else
        :to="{ name: 'login' }"
        color="primary"
        size="large"
        block
        prepend-icon="mdi-arrow-left"
      >
        Voltar para o login
      </v-btn>

      <div class="recovery-card__divider">
        <v-divider />
        <span>ou</span>
        <v-divider />
      </div>

      <v-btn
        :to="{ name: 'login' }"
        variant="tonal"
        color="secondary"
        block
        prepend-icon="mdi-login-variant"
      >
        Lembrei minha senha
      </v-btn>

      <p class="recovery-card__footer">
        O link de recuperação possui validade limitada e deve ser utilizado apenas pelo titular da
        conta.
      </p>
    </v-card>
  </AuthLayout>
</template>

<style scoped>
.recovery-card {
  border: 1px solid rgb(15 23 42 / 6%);
  border-radius: 26px;
  box-shadow: 0 24px 60px rgb(15 23 42 / 10%) !important;
}

.recovery-card__header {
  margin-bottom: 24px;
}

.recovery-card__icon {
  display: grid;
  width: 52px;
  height: 52px;
  margin-bottom: 20px;
  place-items: center;
  border-radius: 16px;
  background: rgb(22 58 95 / 9%);
}

.recovery-card__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.recovery-card__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.9rem, 5vw, 2.5rem);
  letter-spacing: -0.045em;
  line-height: 1.1;
}

.recovery-card__subtitle {
  margin: 12px 0 0;
  color: #64748b;
  line-height: 1.65;
}

.field-label {
  display: inline-block;
  margin-bottom: 8px;
  color: #334155;
  font-size: 0.84rem;
  font-weight: 700;
}

.recovery-card__divider {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 14px;
  margin: 24px 0 18px;
  color: #94a3b8;
  font-size: 0.72rem;
}

.recovery-card__footer {
  margin: 24px 0 0;
  color: #94a3b8;
  font-size: 0.74rem;
  line-height: 1.6;
  text-align: center;
}
</style>
