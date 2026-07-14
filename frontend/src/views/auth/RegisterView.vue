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
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const nameRules = [
  (value: string) => Boolean(value) || 'Informe seu nome completo.',
  (value: string) => value.trim().length >= 3 || 'O nome deve possuir pelo menos 3 caracteres.',
]

const emailRules = [
  (value: string) => Boolean(value) || 'Informe seu e-mail.',
  (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Informe um e-mail válido.',
]

const passwordRules = [
  (value: string) => Boolean(value) || 'Informe uma senha.',
  (value: string) => value.length >= 12 || 'A senha deve possuir pelo menos 12 caracteres.',
  (value: string) => /[a-z]/.test(value) || 'Inclua pelo menos uma letra minúscula.',
  (value: string) => /[A-Z]/.test(value) || 'Inclua pelo menos uma letra maiúscula.',
  (value: string) => /\d/.test(value) || 'Inclua pelo menos um número.',
  (value: string) => /[^A-Za-z0-9]/.test(value) || 'Inclua pelo menos um símbolo.',
]

const passwordConfirmationRules = [
  (value: string) => Boolean(value) || 'Confirme sua senha.',
  (value: string) => value === password.value || 'A confirmação da senha não corresponde.',
]

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível enviar a solicitação. Tente novamente.'
  }

  const response = error.response?.data
  const firstValidationMessage = response?.errors
    ? Object.values(response.errors)[0]?.[0]
    : undefined

  return (
    firstValidationMessage ??
    response?.message ??
    'Não foi possível enviar a solicitação. Verifique os dados informados.'
  )
}

async function handleRegister(): Promise<void> {
  errorMessage.value = ''
  successMessage.value = ''

  if (!formValid.value) {
    errorMessage.value = 'Preencha corretamente todos os campos.'

    return
  }

  try {
    const response = await auth.register({
      name: name.value.trim(),
      email: email.value.trim().toLowerCase(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    successMessage.value = response.message

    name.value = ''
    email.value = ''
    password.value = ''
    passwordConfirmation.value = ''
  } catch (error) {
    errorMessage.value = resolveErrorMessage(error)
  }
}
</script>

<template>
  <AuthLayout>
    <v-card class="register-card pa-6 pa-sm-8" color="surface">
      <div class="register-card__header">
        <div class="register-card__icon" aria-hidden="true">
          <v-icon icon="mdi-account-plus-outline" color="primary" size="28" />
        </div>

        <p class="register-card__eyebrow">Solicitação de acesso</p>

        <h2 class="register-card__title">Crie sua conta</h2>

        <p class="register-card__subtitle">
          Preencha seus dados. O acesso será liberado após a aprovação de um administrador.
        </p>
      </div>

      <v-alert
        type="info"
        variant="tonal"
        density="comfortable"
        icon="mdi-information-outline"
        class="mb-6"
      >
        Toda nova conta começa com o status pendente.
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
        v-model="formValid"
        :disabled="auth.loading || Boolean(successMessage)"
        @submit.prevent="handleRegister"
      >
        <div class="field-group">
          <label class="field-label" for="name"> Nome completo </label>

          <v-text-field
            id="name"
            v-model="name"
            :rules="nameRules"
            placeholder="Digite seu nome completo"
            prepend-inner-icon="mdi-account-outline"
            autocomplete="name"
            hide-details="auto"
            required
          />
        </div>

        <div class="field-group">
          <label class="field-label" for="register-email"> E-mail institucional </label>

          <v-text-field
            id="register-email"
            v-model="email"
            :rules="emailRules"
            type="email"
            placeholder="nome@instituicao.gov.br"
            prepend-inner-icon="mdi-email-outline"
            autocomplete="email"
            hide-details="auto"
            required
          />
        </div>

        <div class="field-group">
          <label class="field-label" for="register-password"> Senha </label>

          <v-text-field
            id="register-password"
            v-model="password"
            :rules="passwordRules"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Crie uma senha segura"
            prepend-inner-icon="mdi-lock-outline"
            :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
            autocomplete="new-password"
            hide-details="auto"
            required
            @click:append-inner="showPassword = !showPassword"
          />

          <p class="password-help">
            Use 12 ou mais caracteres, com maiúscula, minúscula, número e símbolo.
          </p>
        </div>

        <div class="field-group">
          <label class="field-label" for="password-confirmation"> Confirme a senha </label>

          <v-text-field
            id="password-confirmation"
            v-model="passwordConfirmation"
            :rules="passwordConfirmationRules"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            placeholder="Digite novamente sua senha"
            prepend-inner-icon="mdi-lock-check-outline"
            :append-inner-icon="
              showPasswordConfirmation ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
            "
            autocomplete="new-password"
            hide-details="auto"
            required
            @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
          />
        </div>

        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          prepend-icon="mdi-send-outline"
          :loading="auth.loading"
          class="mt-2"
        >
          Enviar solicitação
        </v-btn>
      </v-form>

      <v-divider class="my-6" />

      <p class="register-card__login-text">Já possui uma conta?</p>

      <v-btn
        :to="{ name: 'login' }"
        variant="tonal"
        color="secondary"
        block
        prepend-icon="mdi-login-variant"
      >
        Voltar para o login
      </v-btn>

      <p class="register-card__footer">
        Seus dados serão utilizados somente para identificação e controle de acesso ao SIGEM.
      </p>
    </v-card>
  </AuthLayout>
</template>

<style scoped>
.register-card {
  border: 1px solid rgb(22 58 95 / 8%);
  box-shadow: 0 24px 60px rgb(22 58 95 / 10%) !important;
}

.register-card__header {
  margin-bottom: 28px;
}

.register-card__icon {
  display: grid;
  width: 54px;
  height: 54px;
  margin-bottom: 20px;
  place-items: center;
  border-radius: 17px;
  background: rgb(22 58 95 / 9%);
}

.register-card__eyebrow {
  margin: 0 0 6px;
  color: #0e7490;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.register-card__title {
  margin: 0;
  color: #172033;
  font-size: clamp(1.8rem, 4vw, 2.35rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.15;
}

.register-card__subtitle {
  margin: 12px 0 0;
  color: #64748b;
  line-height: 1.6;
}

.field-group {
  margin-bottom: 20px;
}

.field-label {
  display: inline-block;
  margin-bottom: 8px;
  color: #334155;
  font-size: 0.88rem;
  font-weight: 700;
}

.password-help {
  margin: 8px 0 0;
  color: #64748b;
  font-size: 0.76rem;
  line-height: 1.5;
}

.register-card__login-text {
  margin: 0 0 12px;
  color: #64748b;
  font-size: 0.9rem;
  text-align: center;
}

.register-card__footer {
  margin: 24px 0 0;
  color: #94a3b8;
  font-size: 0.72rem;
  line-height: 1.6;
  text-align: center;
}

@media (max-width: 599px) {
  .register-card {
    box-shadow: none !important;
  }
}
</style>
