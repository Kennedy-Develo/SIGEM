<script setup lang="ts">
import { computed } from 'vue'

import type { ManifestationIndicators } from '@/types/manifestation'

interface IndicatorCard {
  key: keyof ManifestationIndicators
  label: string
  detail: string
  icon: string
  color: string
  background: string
}

const props = withDefaults(
  defineProps<{
    indicators: ManifestationIndicators
    loading?: boolean
  }>(),
  {
    loading: false,
  },
)

const cards = computed<IndicatorCard[]>(() => [
  {
    key: 'overdue',
    label: 'Vencidas',
    detail: 'Prazo ultrapassado',
    icon: 'mdi-alert-circle-outline',
    color: '#b91c1c',
    background: '#fee2e2',
  },
  {
    key: 'due_today',
    label: 'Vencem hoje',
    detail: 'Resolver ainda hoje',
    icon: 'mdi-calendar-alert-outline',
    color: '#c2410c',
    background: '#ffedd5',
  },
  {
    key: 'due_next_7_days',
    label: 'Até 7 dias',
    detail: 'Próximos prazos',
    icon: 'mdi-calendar-clock-outline',
    color: '#a16207',
    background: '#fef3c7',
  },
  {
    key: 'extended',
    label: 'Prorrogadas',
    detail: 'Acompanhar novo prazo',
    icon: 'mdi-calendar-refresh-outline',
    color: '#b91c1c',
    background: '#fee2e2',
  },
  {
    key: 'completed',
    label: 'Concluídas',
    detail: 'Finalizadas no filtro atual',
    icon: 'mdi-check-circle-outline',
    color: '#15803d',
    background: '#dcfce7',
  },
])
</script>

<template>
  <section class="tracking-panel">
    <div class="tracking-panel__header">
      <div>
        <p class="tracking-panel__eyebrow">Acompanhamento</p>

        <h2 class="tracking-panel__title">Painel de prazos</h2>

        <p class="tracking-panel__description">
          Use os indicadores para identificar o que precisa de atenção.
        </p>
      </div>

      <v-icon icon="mdi-chart-timeline-variant-shimmer" color="primary" size="30" />
    </div>

    <div class="indicator-grid">
      <v-card v-for="card in cards" :key="card.key" class="indicator-card" rounded="lg" flat>
        <div
          class="indicator-card__icon"
          :style="{
            color: card.color,
            backgroundColor: card.background,
          }"
        >
          <v-icon :icon="card.icon" size="23" />
        </div>

        <div class="indicator-card__content">
          <span class="indicator-card__label">
            {{ card.label }}
          </span>

          <v-skeleton-loader
            v-if="props.loading"
            type="heading"
            width="42"
            class="indicator-card__skeleton"
          />

          <strong v-else class="indicator-card__value">
            {{ props.indicators[card.key] }}
          </strong>

          <small class="indicator-card__detail">
            {{ card.detail }}
          </small>
        </div>
      </v-card>
    </div>
  </section>
</template>

<style scoped>
.tracking-panel {
  padding: 20px;
  border: 1px solid rgb(15 23 42 / 6%);
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 12px 32px rgb(15 23 42 / 5%);
}

.tracking-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 18px;
}

.tracking-panel__eyebrow {
  margin: 0 0 4px;
  color: #0e7490;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.tracking-panel__title {
  margin: 0;
  color: #172033;
  font-size: 1.15rem;
}

.tracking-panel__description {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 0.78rem;
}

.indicator-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
}

.indicator-card {
  display: flex;
  min-width: 0;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 1px solid #e2e8f0;
}

.indicator-card__icon {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 13px;
}

.indicator-card__content {
  display: grid;
  min-width: 0;
}

.indicator-card__label {
  color: #64748b;
  font-size: 0.72rem;
}

.indicator-card__value {
  margin-top: 2px;
  color: #172033;
  font-size: 1.55rem;
  line-height: 1.2;
}

.indicator-card__detail {
  margin-top: 5px;
  overflow: hidden;
  color: #94a3b8;
  font-size: 0.67rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.indicator-card__skeleton {
  margin-top: 2px;
}

.indicator-card__skeleton :deep(.v-skeleton-loader__heading) {
  margin: 0;
}

@media (max-width: 1200px) {
  .indicator-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .indicator-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 460px) {
  .indicator-grid {
    grid-template-columns: 1fr;
  }

  .indicator-card__detail {
    white-space: normal;
  }
}
</style>
