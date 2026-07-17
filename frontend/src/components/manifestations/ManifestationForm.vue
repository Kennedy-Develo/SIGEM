<script setup lang="ts">
import axios from 'axios'
import { computed, nextTick, reactive, ref, watch } from 'vue'

import { useManifestationsStore } from '@/stores/manifestations'
import type {
  ManifestationCatalogs,
  ManifestationSource,
  ManifestationType,
  StoreManifestationPayload,
} from '@/types/manifestation'

interface Props {
  catalogs: ManifestationCatalogs
  loadingCatalogs?: boolean
}

interface Emits {
  created: [message: string]
  failed: [message: string]
}

interface FormInstance {
  validate: () => Promise<{ valid: boolean }>
  resetValidation: () => void
}

interface ManifestationFormData {
  nup: string
  source: ManifestationSource
  type: ManifestationType
  subject_id: number | null
  subsubject_id: number | null
  sector_id: number | null
  current_assignee_id: number | null
  conclusion_responsible_area: string
  summary: string
  description: string
  opened_at: string
  deadline_at: string
}

interface ErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

const props = withDefaults(defineProps<Props>(), {
  loadingCatalogs: false,
})

const emit = defineEmits<Emits>()

const manifestationsStore = useManifestationsStore()

const formRef = ref<FormInstance | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

const requiredRule = (value: unknown) =>
  (value !== null && value !== undefined && value !== '') || 'Este campo é obrigatório.'

const nupRule = (value: string) =>
  /^\d{17}$/.test(value) || 'O NUP deve conter exatamente 17 números.'

const maximumLengthRule = (maximum: number) => (value: string) =>
  !value || value.length <= maximum || `Informe no máximo ${maximum} caracteres.`

function currentDate(): string {
  const today = new Date()
  const offset = today.getTimezoneOffset()
  const localDate = new Date(today.getTime() - offset * 60 * 1000)

  return localDate.toISOString().slice(0, 10)
}

function initialForm(): ManifestationFormData {
  return {
    nup: '',
    source: 'fala_br',
    type: 'request',
    subject_id: null,
    subsubject_id: null,
    sector_id: null,
    current_assignee_id: null,
    conclusion_responsible_area: '',
    summary: '',
    description: '',
    opened_at: currentDate(),
    deadline_at: '',
  }
}

const form = reactive<ManifestationFormData>(initialForm())

const subsubjects = computed(() => {
  const subject = props.catalogs.subjects.find((item) => item.id === form.subject_id)

  return subject?.subsubjects ?? []
})

const deadlineRules = computed(() => [
  (value: string) =>
    !value ||
    !form.opened_at ||
    value >= form.opened_at ||
    'O prazo não pode ser anterior à data de abertura.',
])

watch(
  () => form.subject_id,
  () => {
    const selectedSubsubjectExists = subsubjects.value.some(
      (item) => item.id === form.subsubject_id,
    )

    if (!selectedSubsubjectExists) {
      form.subsubject_id = null
    }

    clearFieldError('subject_id')
    clearFieldError('subsubject_id')
  },
)

function updateNup(value: string | null): void {
  form.nup = (value ?? '').replace(/\D/g, '').slice(0, 17)

  clearFieldError('nup')
}

function clearFieldError(field: string): void {
  if (!fieldErrors.value[field]) {
    return
  }

  fieldErrors.value = {
    ...fieldErrors.value,
    [field]: [],
  }
}

function errorsFor(field: string): string[] {
  return fieldErrors.value[field] ?? []
}

function resolveError(error: unknown): string {
  if (!axios.isAxiosError<ErrorResponse>(error)) {
    return 'Não foi possível cadastrar a manifestação.'
  }

  fieldErrors.value = error.response?.data.errors ?? {}

  return error.response?.data.message ?? 'Verifique os campos informados e tente novamente.'
}

async function resetForm(): Promise<void> {
  Object.assign(form, initialForm())
  fieldErrors.value = {}

  await nextTick()
  formRef.value?.resetValidation()
}

async function submit(): Promise<void> {
  fieldErrors.value = {}

  const validation = await formRef.value?.validate()

  if (!validation?.valid) {
    emit('failed', 'Verifique os campos obrigatórios do formulário.')
    return
  }

  if (form.subject_id === null || form.subsubject_id === null || form.sector_id === null) {
    emit('failed', 'Selecione o assunto, o subassunto e o setor.')
    return
  }

  const payload: StoreManifestationPayload = {
    nup: form.nup,
    source: form.source,
    type: form.type,
    subject_id: form.subject_id,
    subsubject_id: form.subsubject_id,
    sector_id: form.sector_id,
    current_assignee_id: form.current_assignee_id,
    conclusion_responsible_area: form.conclusion_responsible_area.trim() || null,
    summary: form.summary.trim() || null,
    description: form.description.trim() || null,
    opened_at: form.opened_at,
    original_deadline_at: form.deadline_at || null,
    current_deadline_at: form.deadline_at || null,
  }

  try {
    const response = await manifestationsStore.createManifestation(payload)

    emit('created', response.message || 'Manifestação cadastrada com sucesso.')

    await resetForm()
  } catch (error: unknown) {
    emit('failed', resolveError(error))
  }
}
</script>

<template>
  <v-card class="manifestation-form" elevation="0" rounded="xl">
    <div class="manifestation-form__header">
      <div>
        <span class="manifestation-form__eyebrow"> Cadastro </span>

        <h2>Nova manifestação</h2>

        <p>Cadastre os dados recebidos pelo Fala.BR ou pelo SEI.</p>
      </div>

      <v-chip color="primary" prepend-icon="mdi-file-plus-outline" variant="tonal">
        Novo registro
      </v-chip>
    </div>

    <v-divider />

    <div v-if="loadingCatalogs" class="manifestation-form__loading">
      <v-progress-circular color="primary" indeterminate />

      <span>Carregando opções do cadastro...</span>
    </div>

    <v-form v-else ref="formRef" class="manifestation-form__body" @submit.prevent="submit">
      <v-alert class="mb-6" color="info" icon="mdi-information-outline" variant="tonal">
        A manifestação será criada inicialmente com a situação
        <strong>Cadastrada</strong>.
      </v-alert>

      <div class="manifestation-form__section">
        <div class="manifestation-form__section-title">
          <v-icon color="primary" icon="mdi-identifier" />

          <div>
            <h3>Identificação</h3>
            <p>Informe o número, a origem e o tipo da manifestação.</p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              :error-messages="errorsFor('nup')"
              :model-value="form.nup"
              :rules="[requiredRule, nupRule]"
              counter="17"
              inputmode="numeric"
              label="Número/NUP"
              maxlength="17"
              placeholder="Digite os 17 números do NUP"
              prepend-inner-icon="mdi-numeric"
              variant="outlined"
              @update:model-value="updateNup"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.source"
              :error-messages="errorsFor('source')"
              :items="catalogs.sources"
              :rules="[requiredRule]"
              item-title="label"
              item-value="value"
              label="Origem"
              prepend-inner-icon="mdi-source-branch"
              variant="outlined"
              @update:model-value="clearFieldError('source')"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.type"
              :error-messages="errorsFor('type')"
              :items="catalogs.types"
              :rules="[requiredRule]"
              item-title="label"
              item-value="value"
              label="Tipo"
              prepend-inner-icon="mdi-shape-outline"
              variant="outlined"
              @update:model-value="clearFieldError('type')"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.opened_at"
              :error-messages="errorsFor('opened_at')"
              :rules="[requiredRule]"
              label="Data de abertura"
              prepend-inner-icon="mdi-calendar-start"
              type="date"
              variant="outlined"
              @update:model-value="clearFieldError('opened_at')"
            />
          </v-col>
        </v-row>
      </div>

      <v-divider class="my-6" />

      <div class="manifestation-form__section">
        <div class="manifestation-form__section-title">
          <v-icon color="primary" icon="mdi-text-box-search-outline" />

          <div>
            <h3>Classificação</h3>
            <p>Selecione o assunto, o subassunto e o setor responsável.</p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="form.subject_id"
              :error-messages="errorsFor('subject_id')"
              :items="catalogs.subjects"
              :rules="[requiredRule]"
              clearable
              item-title="name"
              item-value="id"
              label="Assunto"
              no-data-text="Nenhum assunto encontrado"
              placeholder="Digite ou selecione o assunto"
              prepend-inner-icon="mdi-format-list-bulleted"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="form.subsubject_id"
              :disabled="form.subject_id === null"
              :error-messages="errorsFor('subsubject_id')"
              :items="subsubjects"
              :rules="[requiredRule]"
              clearable
              item-title="name"
              item-value="id"
              label="Subassunto"
              no-data-text="Nenhum subassunto encontrado"
              placeholder="Digite ou selecione o subassunto"
              prepend-inner-icon="mdi-format-list-checks"
              variant="outlined"
              @update:model-value="clearFieldError('subsubject_id')"
            />
          </v-col>

          <v-col cols="12">
            <v-autocomplete
              v-model="form.sector_id"
              :error-messages="errorsFor('sector_id')"
              :items="catalogs.sectors"
              :rules="[requiredRule]"
              clearable
              item-title="label"
              item-value="id"
              label="Tag/Setor"
              no-data-text="Nenhum setor encontrado"
              placeholder="Digite a sigla ou abra a lista para selecionar"
              prepend-inner-icon="mdi-office-building-outline"
              variant="outlined"
              @update:model-value="clearFieldError('sector_id')"
            >
              <template #item="{ props: itemProps, item }">
                <v-list-item
                  v-bind="itemProps"
                  :subtitle="item.raw.name"
                  :title="item.raw.acronym"
                />
              </template>

              <template #selection="{ item }">
                <span>
                  <strong>{{ item.raw.acronym }}</strong>
                  — {{ item.raw.name }}
                </span>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>
      </div>

      <v-divider class="my-6" />

      <div class="manifestation-form__section">
        <div class="manifestation-form__section-title">
          <v-icon color="primary" icon="mdi-account-clock-outline" />

          <div>
            <h3>Responsabilidade e prazo</h3>
            <p>Defina o responsável atual e o prazo de resposta.</p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="form.current_assignee_id"
              :error-messages="errorsFor('current_assignee_id')"
              :items="catalogs.assignees"
              clearable
              item-title="name"
              item-value="id"
              label="Respondente"
              no-data-text="Nenhum usuário encontrado"
              placeholder="Digite ou selecione o responsável"
              prepend-inner-icon="mdi-account-check-outline"
              variant="outlined"
              @update:model-value="clearFieldError('current_assignee_id')"
            >
              <template #item="{ props: itemProps, item }">
                <v-list-item
                  v-bind="itemProps"
                  :subtitle="`${item.raw.email} • ${item.raw.role_label}`"
                  :title="item.raw.name"
                />
              </template>
            </v-autocomplete>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.deadline_at"
              :error-messages="[
                ...errorsFor('original_deadline_at'),
                ...errorsFor('current_deadline_at'),
              ]"
              :min="form.opened_at"
              :rules="deadlineRules"
              label="Prazo de resposta"
              prepend-inner-icon="mdi-calendar-clock-outline"
              type="date"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-text-field
              v-model="form.conclusion_responsible_area"
              :error-messages="errorsFor('conclusion_responsible_area')"
              :rules="[maximumLengthRule(255)]"
              counter="255"
              label="Área responsável pela resposta conclusiva"
              maxlength="255"
              placeholder="Digite livremente o nome da área responsável"
              prepend-inner-icon="mdi-domain"
              variant="outlined"
              @update:model-value="clearFieldError('conclusion_responsible_area')"
            />
          </v-col>
        </v-row>
      </div>

      <v-divider class="my-6" />

      <div class="manifestation-form__section">
        <div class="manifestation-form__section-title">
          <v-icon color="primary" icon="mdi-text-box-outline" />

          <div>
            <h3>Conteúdo</h3>
            <p>Registre o resumo e as informações complementares.</p>
          </div>
        </div>

        <v-row>
          <v-col cols="12">
            <v-text-field
              v-model="form.summary"
              :error-messages="errorsFor('summary')"
              :rules="[maximumLengthRule(255)]"
              counter="255"
              label="Resumo da manifestação"
              maxlength="255"
              placeholder="Apresente brevemente o conteúdo da manifestação"
              prepend-inner-icon="mdi-text-short"
              variant="outlined"
              @update:model-value="clearFieldError('summary')"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              :error-messages="errorsFor('description')"
              :rules="[maximumLengthRule(10000)]"
              auto-grow
              counter="10000"
              label="Descrição ou observações"
              maxlength="10000"
              placeholder="Inclua informações adicionais quando necessário"
              prepend-inner-icon="mdi-note-text-outline"
              rows="4"
              variant="outlined"
              @update:model-value="clearFieldError('description')"
            />
          </v-col>
        </v-row>
      </div>

      <div class="manifestation-form__actions">
        <v-btn
          :disabled="manifestationsStore.creating"
          color="secondary"
          prepend-icon="mdi-broom"
          type="button"
          variant="tonal"
          @click="resetForm"
        >
          Limpar
        </v-btn>

        <v-btn
          :loading="manifestationsStore.creating"
          color="primary"
          prepend-icon="mdi-content-save-outline"
          size="large"
          type="submit"
          variant="flat"
        >
          Cadastrar manifestação
        </v-btn>
      </div>
    </v-form>
  </v-card>
</template>

<style scoped>
.manifestation-form {
  border: 1px solid rgba(15, 49, 85, 0.08);
  background: #ffffff;
}

.manifestation-form__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  padding: 24px 28px;
}

.manifestation-form__eyebrow {
  display: block;
  margin-bottom: 5px;
  color: #00758f;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.manifestation-form__header h2 {
  margin: 0;
  color: #071b3a;
  font-size: 1.45rem;
  line-height: 1.2;
}

.manifestation-form__header p {
  margin: 7px 0 0;
  color: #61718b;
  font-size: 0.93rem;
}

.manifestation-form__loading {
  display: flex;
  min-height: 240px;
  align-items: center;
  justify-content: center;
  gap: 14px;
  color: #61718b;
}

.manifestation-form__body {
  padding: 26px 28px 30px;
}

.manifestation-form__section-title {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  margin-bottom: 19px;
}

.manifestation-form__section-title h3 {
  margin: 0;
  color: #10213d;
  font-size: 1rem;
}

.manifestation-form__section-title p {
  margin: 4px 0 0;
  color: #748198;
  font-size: 0.83rem;
}

.manifestation-form__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 12px;
  padding-top: 22px;
  border-top: 1px solid #e8edf3;
}

@media (max-width: 700px) {
  .manifestation-form__header {
    flex-direction: column;
    padding: 21px 20px;
  }

  .manifestation-form__body {
    padding: 22px 20px 25px;
  }

  .manifestation-form__actions {
    flex-direction: column-reverse;
  }

  .manifestation-form__actions :deep(.v-btn) {
    width: 100%;
  }
}
</style>
