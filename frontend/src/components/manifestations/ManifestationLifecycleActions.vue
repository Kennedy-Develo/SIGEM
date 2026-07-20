<script setup lang="ts">
import axios from 'axios'
import { computed, ref } from 'vue'

import { useManifestationsStore } from '@/stores/manifestations'
import type { UserRole } from '@/types/auth'
import type {
  Manifestation,
  ManifestationLifecycleOption,
  TransitionManifestationPayload,
} from '@/types/manifestation'

interface ErrorResponse {
  message?: string
}

interface FormReference {
  validate: () => Promise<{ valid: boolean }>
}

const props = defineProps<{
  manifestation: Manifestation
  userRole: UserRole
  userId: number
}>()

const emit = defineEmits<{
  transitioned: [manifestation: Manifestation, message: string]
}>()

const manifestationsStore = useManifestationsStore()

const dialogOpen = ref(false)
const selectedAction = ref<ManifestationLifecycleOption | null>(null)
const reason = ref('')
const newDeadlineAt = ref('')
const externalAgency = ref('')
const errorMessage = ref('')
const formReference = ref<FormReference | null>(null)

const lifecycleOptions: ManifestationLifecycleOption[] = [
  {
    value: 'start',
    label: 'Iniciar atendimento',
    description: 'Coloca a manifestação em andamento.',
    icon: 'mdi-play-circle-outline',
    color: 'primary',
    requiresReason: false,
    requiresDeadline: false,
    requiresExternalAgency: false,
  },
  {
    value: 'extend',
    label: 'Prorrogar prazo',
    description: 'Define um novo prazo para a manifestação.',
    icon: 'mdi-calendar-clock-outline',
    color: 'error',
    requiresReason: true,
    requiresDeadline: true,
    requiresExternalAgency: false,
  },
  {
    value: 'forward',
    label: 'Encaminhar para outro órgão',
    description: 'Registra o encaminhamento para um órgão externo.',
    icon: 'mdi-share-outline',
    color: 'warning',
    requiresReason: true,
    requiresDeadline: false,
    requiresExternalAgency: true,
  },
  {
    value: 'answer',
    label: 'Registrar resposta da Ouvidoria',
    description: 'Marca a manifestação como respondida pela Ouvidoria.',
    icon: 'mdi-message-reply-text-outline',
    color: 'info',
    requiresReason: false,
    requiresDeadline: false,
    requiresExternalAgency: false,
  },
  {
    value: 'complete',
    label: 'Concluir manifestação',
    description: 'Finaliza o atendimento da manifestação.',
    icon: 'mdi-check-circle-outline',
    color: 'success',
    requiresReason: false,
    requiresDeadline: false,
    requiresExternalAgency: false,
  },
  {
    value: 'archive',
    label: 'Arquivar manifestação',
    description: 'Retira uma manifestação concluída da lista operacional.',
    icon: 'mdi-archive-outline',
    color: 'grey-darken-1',
    requiresReason: true,
    requiresDeadline: false,
    requiresExternalAgency: false,
  },
  {
    value: 'reopen',
    label: 'Reabrir manifestação',
    description: 'Devolve a manifestação para a situação em andamento.',
    icon: 'mdi-backup-restore',
    color: 'secondary',
    requiresReason: true,
    requiresDeadline: false,
    requiresExternalAgency: false,
  },
]

const isAdministratorOrManager = computed(() =>
  ['administrator', 'manager'].includes(props.userRole),
)

const isResponsibleOperator = computed(
  () =>
    props.userRole === 'operator' &&
    (props.manifestation.current_assignee_id === props.userId ||
      props.manifestation.created_by_id === props.userId),
)

const canOperateManifestation = computed(
  () => isAdministratorOrManager.value || isResponsibleOperator.value,
)

const availableActions = computed(() => {
  if (!canOperateManifestation.value) {
    return []
  }

  return lifecycleOptions.filter((option) => {
    if (!isAdministratorOrManager.value && ['archive', 'reopen'].includes(option.value)) {
      return false
    }

    switch (option.value) {
      case 'start':
        return props.manifestation.status === 'registered'

      case 'extend':
      case 'forward':
      case 'answer':
      case 'complete':
        return ['registered', 'in_progress'].includes(props.manifestation.status)

      case 'archive':
        return props.manifestation.status === 'completed'

      case 'reopen':
        return ['completed', 'archived'].includes(props.manifestation.status)

      default:
        return false
    }
  })
})

const isSubmitting = computed(
  () => manifestationsStore.transitioningManifestationId === props.manifestation.id,
)

const minimumDeadline = computed(() => {
  const baseDeadline = props.manifestation.current_deadline_at ?? props.manifestation.opened_at

  const date = new Date(`${baseDeadline.substring(0, 10)}T12:00:00`)
  date.setDate(date.getDate() + 1)

  return date.toISOString().substring(0, 10)
})

const requiredRule = (value: string): true | string =>
  value.trim().length > 0 || 'Este campo é obrigatório.'

function resolveErrorMessage(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível realizar a ação.'
  }

  return error.response?.data.message ?? 'Não foi possível realizar a ação.'
}

function openAction(option: ManifestationLifecycleOption): void {
  selectedAction.value = option
  reason.value = ''
  newDeadlineAt.value = ''
  externalAgency.value = ''
  errorMessage.value = ''
  dialogOpen.value = true
}

function closeDialog(): void {
  if (isSubmitting.value) {
    return
  }

  dialogOpen.value = false
  selectedAction.value = null
  errorMessage.value = ''
}

async function submitTransition(): Promise<void> {
  if (selectedAction.value === null) {
    return
  }

  const validation = await formReference.value?.validate()

  if (validation?.valid === false) {
    return
  }

  errorMessage.value = ''

  const payload: TransitionManifestationPayload = {
    action: selectedAction.value.value,
  }

  if (selectedAction.value.requiresReason) {
    payload.reason = reason.value.trim()
  }

  if (selectedAction.value.requiresDeadline) {
    payload.new_deadline_at = newDeadlineAt.value
  }

  if (selectedAction.value.requiresExternalAgency) {
    payload.external_agency = externalAgency.value.trim()
  }

  try {
    const response = await manifestationsStore.transitionManifestation(
      props.manifestation.id,
      payload,
    )

    emit('transitioned', response.manifestation, response.message)

    dialogOpen.value = false
    selectedAction.value = null
  } catch (error: unknown) {
    errorMessage.value = resolveErrorMessage(error)
  }
}
</script>

<template>
  <section class="lifecycle">
    <div class="lifecycle__header">
      <div>
        <span class="lifecycle__eyebrow"> CONTROLE OPERACIONAL </span>

        <h3>Ciclo de vida</h3>

        <p>Atualize a situação da manifestação conforme o andamento do atendimento.</p>
      </div>

      <v-icon color="primary" icon="mdi-state-machine" size="30" />
    </div>

    <v-alert
      v-if="availableActions.length === 0"
      color="grey"
      icon="mdi-information-outline"
      type="info"
      variant="tonal"
    >
      Não existem ações disponíveis para esta manifestação ou para o seu perfil.
    </v-alert>

    <div v-else class="lifecycle__actions">
      <v-btn
        v-for="option in availableActions"
        :key="option.value"
        :color="option.color"
        :disabled="isSubmitting"
        :prepend-icon="option.icon"
        block
        class="lifecycle__action"
        variant="tonal"
        @click="openAction(option)"
      >
        {{ option.label }}
      </v-btn>
    </div>

    <v-dialog v-model="dialogOpen" max-width="620" persistent>
      <v-card v-if="selectedAction" class="lifecycle-dialog" rounded="xl">
        <v-card-title class="lifecycle-dialog__header">
          <span class="lifecycle-dialog__icon" :class="`text-${selectedAction.color}`">
            <v-icon :icon="selectedAction.icon" size="28" />
          </span>

          <span>
            <strong>{{ selectedAction.label }}</strong>

            <small> Manifestação {{ manifestation.nup }} </small>
          </span>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <p class="lifecycle-dialog__description">
            {{ selectedAction.description }}
          </p>

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

          <v-form ref="formReference" @submit.prevent="submitTransition">
            <v-textarea
              v-if="selectedAction.requiresReason"
              v-model="reason"
              :disabled="isSubmitting"
              :rules="[requiredRule]"
              auto-grow
              counter="2000"
              label="Motivo da ação"
              maxlength="2000"
              prepend-inner-icon="mdi-text-box-outline"
              rows="3"
              variant="outlined"
            />

            <v-text-field
              v-if="selectedAction.requiresDeadline"
              v-model="newDeadlineAt"
              :disabled="isSubmitting"
              :min="minimumDeadline"
              :rules="[requiredRule]"
              label="Novo prazo"
              prepend-inner-icon="mdi-calendar-clock"
              type="date"
              variant="outlined"
            />

            <v-text-field
              v-if="selectedAction.requiresExternalAgency"
              v-model="externalAgency"
              :disabled="isSubmitting"
              :rules="[requiredRule]"
              counter="255"
              label="Órgão de destino"
              maxlength="255"
              prepend-inner-icon="mdi-domain"
              variant="outlined"
            />
          </v-form>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-5">
          <v-spacer />

          <v-btn :disabled="isSubmitting" variant="text" @click="closeDialog"> Cancelar </v-btn>

          <v-btn
            :color="selectedAction.color"
            :loading="isSubmitting"
            prepend-icon="mdi-check"
            variant="flat"
            @click="submitTransition"
          >
            Confirmar ação
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </section>
</template>

<style scoped>
.lifecycle {
  border: 1px solid #dce4ee;
  border-radius: 18px;
  padding: 20px;
}

.lifecycle__header {
  align-items: flex-start;
  display: flex;
  gap: 20px;
  justify-content: space-between;
  margin-bottom: 20px;
}

.lifecycle__eyebrow {
  color: #007c98;
  display: block;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  margin-bottom: 5px;
}

.lifecycle__header h3 {
  color: #0b1f3a;
  font-size: 1.15rem;
  margin: 0;
}

.lifecycle__header p {
  color: #5d6d82;
  font-size: 0.86rem;
  margin: 5px 0 0;
}

.lifecycle__actions {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.lifecycle__action {
  font-size: 0.75rem;
  justify-content: flex-start;
  min-height: 44px;
}

.lifecycle-dialog__header {
  align-items: center;
  display: flex;
  gap: 14px;
  padding: 22px 24px;
}

.lifecycle-dialog__header strong {
  color: #0b1f3a;
  display: block;
  font-size: 1.05rem;
}

.lifecycle-dialog__header small {
  color: #65758b;
  display: block;
  font-size: 0.75rem;
  font-weight: 400;
  margin-top: 3px;
}

.lifecycle-dialog__icon {
  align-items: center;
  background: #eef3f8;
  border-radius: 12px;
  display: flex;
  height: 48px;
  justify-content: center;
  width: 48px;
}

.lifecycle-dialog__description {
  color: #526278;
  font-size: 0.9rem;
  margin: 0 0 20px;
}

@media (max-width: 600px) {
  .lifecycle__actions {
    grid-template-columns: 1fr;
  }

  .lifecycle {
    padding: 16px;
  }
}
</style>
