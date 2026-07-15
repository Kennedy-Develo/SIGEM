<script setup lang="ts">
import axios from 'axios'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

interface ErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

const route = useRoute()
const auth = useAuthStore()

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))

const initialEmail = typeof route.query.email === 'string' ? route.query.email : ''

const invalidLink = computed(() => token.value.length === 0)

const formValid = ref(false)
const email = ref(initialEmail)
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const emailRules = [
  (value: string) => Boolean(value) || 'Informe seu e-mail.',
  (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Informe um e-mail válido.',
]

const passwordRules = [
  (value: string) => Boolean(value) || 'Informe a nova senha.',
  (value: string) => value.length >= 12 || 'A senha deve possuir pelo menos 12 caracteres.',
  (value: string) => /[a-z]/.test(value) || 'Inclua pelo menos uma letra minúscula.',
  (value: string) => /[A-Z]/.test(value) || 'Inclua pelo menos uma letra maiúscula.',
  (value: string) => /\d/.test(value) || 'Inclua pelo menos um número.',
  (value: string) => /[^A-Za-z0-9]/.test(value) || 'Inclua pelo menos um símbolo.',
]

const passwordConfirmationRules = [
  (value: string) => Boolean(value) || 'Confirme a nova senha.',
  (value: string) => value === password.value || 'A confirmação da senha não corresponde.',
]

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível redefinir a senha. Tente novamente.'
  }

  const response = error.response?.data
  const firstValidationMessage = response?.errors
    ? Object.values(response.errors)[0]?.[0]
    : undefined

  return (
    firstValidationMessage ??
    response?.message ??
    'O link pode ter expirado ou já ter sido utilizado.'
  )
}

async function handleResetPassword(): Promise<void> {
  errorMessage.value = ''
  successMessage.value = ''

  if (invalidLink.value) {
    errorMessage.value = 'Este link de recuperação é inválido.'

    return
  }

  if (!formValid.value) {
    errorMessage.value = 'Preencha corretamente todos os campos.'

    return
  }

  try {
    const response = await auth.resetPassword({
      token: token.value,
      email: email.value.trim().toLowerCase(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    successMessage.value = response.message
    password.value = ''
    passwordConfirmation.value = ''
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}
</script>

<template>
  <AuthLayout>
    <v-card class="reset-card pa-6 pa-sm-8" color="surface">
      <div class="reset-card__header">
        <div class="reset-card__icon" aria-hidden="true">
          <v-icon icon="mdi-form-textbox-password" color="primary" size="28" />
        </div>

        <p class="reset-card__eyebrow">Segurança da conta</p>

        <h2 class="reset-card__title">Crie uma nova senha</h2>

        <p class="reset-card__subtitle">
          Escolha uma senha forte e diferente das utilizadas anteriormente.
        </p>
      </div>

      <v-alert
        v-if="invalidLink"
        type="error"
        variant="tonal"
        density="comfortable"
        icon="mdi-link-variant-off"
        class="mb-6"
      >
        Este link não possui um token de recuperação válido. Solicite um novo link para continuar.
      </v-alert>

      <v-alert
        v-if="successMessage"
        type="success"
        variant="tonal"
        density="comfortable"
        icon="mdi-check-circle-outline"
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

      <v-form
        v-if="!invalidLink && !successMessage"
        v-model="formValid"
        @submit.prevent="handleResetPassword"
      >
        <label class="field-label" for="reset-email"> E-mail </label>

        <v-text-field
          id="reset-email"
          v-model="email"
          type="email"
          placeholder="nome@instituicao.gov.br"
          prepend-inner-icon="mdi-email-outline"
          :rules="emailRules"
          autocomplete="email"
          variant="outlined"
          class="mb-2"
        />

        <label class="field-label" for="reset-password"> Nova senha </label>

        <v-text-field
          id="reset-password"
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="Digite sua nova senha"
          prepend-inner-icon="mdi-lock-outline"
          :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
          :rules="passwordRules"
          autocomplete="new-password"
          variant="outlined"
          class="mb-2"
          @click:append-inner="showPassword = !showPassword"
        />

        <label class="field-label" for="reset-password-confirmation"> Confirme a nova senha </label>

        <v-text-field
          id="reset-password-confirmation"
          v-model="passwordConfirmation"
          :type="showPasswordConfirmation ? 'text' : 'password'"
          placeholder="Digite novamente a nova senha"
          prepend-inner-icon="mdi-lock-check-outline"
          :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
          :rules="passwordConfirmationRules"
          autocomplete="new-password"
          variant="outlined"
          class="mb-3"
          @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
        />

        <v-alert
          type="info"
          variant="tonal"
          density="compact"
          icon="mdi-information-outline"
          class="mb-6"
        >
          Use pelo menos 12 caracteres, incluindo letras maiúsculas, minúsculas, número e símbolo.
        </v-alert>

        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          prepend-icon="mdi-lock-reset"
          :loading="auth.loading"
        >
          Redefinir senha
        </v-btn>
      </v-form>

      <v-btn
        v-if="successMessage"
        :to="{ name: 'login' }"
        color="primary"
        size="large"
        block
        prepend-icon="mdi-login-variant"
      >
        Entrar com a nova senha
      </v-btn>

      <v-btn
        v-if="invalidLink"
        :to="{ name: 'forgot-password' }"
        color="primary"
        size="large"
        block
        prepend-icon="mdi-email-arrow-right-outline"
      >
        Solicitar novo link
      </v-btn>

      <div class="reset-card__back">
        <v-btn
          :to="{ name: 'login' }"
          variant="text"
          color="secondary"
          prepend-icon="mdi-arrow-left"
        >
          Voltar para o login
        </v-btn>
      </div>
    </v-card>
  </AuthLayout>
</template>

<style scoped>
.reset-card {
  border: 1px solid rgb(15 23 42 / 6%);
  border-radius: 26px;
  box-shadow: 0 24px 60px rgb(15 23 42 / 10%) !important;
}

.reset-card__header {
  margin-bottom: 24px;
}

.reset-card__icon {
  display: grid;
  width: 52px;
  height: 52px;
  margin-bottom: 20px;
  place-items: center;
  border-radius: 16px;
  background: rgb(22 58 95 / 9%);
}

.reset-card__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.reset-card__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.9rem, 5vw, 2.5rem);
  letter-spacing: -0.045em;
  line-height: 1.1;
}

.reset-card__subtitle {
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

.reset-card__back {
  display: flex;
  justify-content: center;
  margin-top: 18px;
}
</style>
