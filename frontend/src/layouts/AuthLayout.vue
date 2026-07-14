<template>
  <v-main class="auth-layout">
    <div class="auth-layout__glow auth-layout__glow--top" />
    <div class="auth-layout__glow auth-layout__glow--bottom" />

    <v-container fluid class="auth-layout__container">
      <v-row no-gutters class="auth-layout__grid">
        <v-col cols="12" lg="7" class="auth-layout__presentation">
          <section class="brand-panel">
            <div class="brand-panel__identity">
              <div class="brand-panel__logo" aria-hidden="true">
                <v-icon icon="mdi-message-text-clock-outline" size="32" />
              </div>

              <div>
                <p class="brand-panel__name">SIGEM</p>
                <p class="brand-panel__description">Sistema de Gestão de Manifestações</p>
              </div>
            </div>

            <div class="brand-panel__content">
              <v-chip
                color="accent"
                variant="tonal"
                prepend-icon="mdi-shield-check-outline"
                class="mb-6"
              >
                Gestão segura e organizada
              </v-chip>

              <h1 class="brand-panel__title">Informação clara para decisões melhores.</h1>

              <p class="brand-panel__text">
                Centralize atendimentos, acompanhe prazos e transforme manifestações em conhecimento
                institucional.
              </p>

              <div class="brand-panel__features">
                <div class="feature-item">
                  <v-icon icon="mdi-clock-check-outline" color="accent" />
                  <span>Acompanhamento de prazos</span>
                </div>

                <div class="feature-item">
                  <v-icon icon="mdi-account-lock-outline" color="accent" />
                  <span>Acesso controlado por perfil</span>
                </div>

                <div class="feature-item">
                  <v-icon icon="mdi-chart-box-outline" color="accent" />
                  <span>Indicadores para tomada de decisão</span>
                </div>
              </div>
            </div>

            <p class="brand-panel__footer">
              Plataforma desenvolvida com segurança, transparência e foco no cidadão.
            </p>
          </section>
        </v-col>

        <v-col cols="12" lg="5" class="auth-layout__form-column">
          <div class="auth-layout__form">
            <slot />
          </div>
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<style scoped>
.auth-layout {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(circle at 10% 10%, rgb(20 184 166 / 14%), transparent 32rem),
    linear-gradient(135deg, #0d2944 0%, #163a5f 58%, #0e7490 100%);
}

.auth-layout__container {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  padding: 24px;
}

.auth-layout__grid {
  min-height: calc(100vh - 48px);
}

.auth-layout__presentation,
.auth-layout__form-column {
  display: flex;
}

.auth-layout__form-column {
  align-items: center;
  justify-content: center;
  padding: 32px;
  border-radius: 32px;
  background: rgb(244 247 251 / 96%);
  box-shadow: 0 24px 80px rgb(5 20 35 / 28%);
}

.auth-layout__form {
  width: 100%;
  max-width: 470px;
}

.auth-layout__glow {
  position: absolute;
  border-radius: 999px;
  filter: blur(10px);
  pointer-events: none;
}

.auth-layout__glow--top {
  top: -160px;
  right: 24%;
  width: 360px;
  height: 360px;
  background: rgb(37 99 235 / 18%);
}

.auth-layout__glow--bottom {
  bottom: -180px;
  left: 15%;
  width: 420px;
  height: 420px;
  background: rgb(20 184 166 / 16%);
}

.brand-panel {
  display: flex;
  width: 100%;
  max-width: 780px;
  min-height: 100%;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 64px 40px 40px;
  color: #ffffff;
}

.brand-panel__identity {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-panel__logo {
  display: grid;
  width: 58px;
  height: 58px;
  place-items: center;
  border: 1px solid rgb(255 255 255 / 20%);
  border-radius: 18px;
  background: rgb(255 255 255 / 12%);
  backdrop-filter: blur(12px);
}

.brand-panel__name {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.brand-panel__description {
  margin: 2px 0 0;
  color: rgb(255 255 255 / 72%);
  font-size: 0.88rem;
}

.brand-panel__content {
  max-width: 650px;
  margin: 72px 0;
}

.brand-panel__title {
  max-width: 620px;
  margin: 0;
  font-size: clamp(2.5rem, 5vw, 4.7rem);
  font-weight: 800;
  letter-spacing: -0.055em;
  line-height: 1.02;
}

.brand-panel__text {
  max-width: 590px;
  margin: 28px 0 0;
  color: rgb(255 255 255 / 76%);
  font-size: 1.15rem;
  line-height: 1.75;
}

.brand-panel__features {
  display: grid;
  gap: 16px;
  margin-top: 38px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  color: rgb(255 255 255 / 88%);
}

.brand-panel__footer {
  margin: 0;
  color: rgb(255 255 255 / 56%);
  font-size: 0.8rem;
}

@media (max-width: 1279px) {
  .auth-layout__container {
    padding: 16px;
  }

  .auth-layout__grid {
    min-height: calc(100vh - 32px);
  }

  .brand-panel {
    padding: 32px 24px 40px;
  }

  .brand-panel__content {
    margin: 56px 0 24px;
  }

  .brand-panel__features,
  .brand-panel__footer {
    display: none;
  }

  .auth-layout__form-column {
    padding: 36px 24px;
  }
}

@media (max-width: 599px) {
  .auth-layout__container {
    padding: 0;
  }

  .auth-layout__grid {
    min-height: 100vh;
  }

  .brand-panel {
    padding: 26px 22px 38px;
  }

  .brand-panel__content {
    margin: 38px 0 0;
  }

  .brand-panel__title {
    font-size: 2.25rem;
  }

  .brand-panel__text {
    margin-top: 18px;
    font-size: 1rem;
  }

  .auth-layout__form-column {
    border-radius: 28px 28px 0 0;
    padding: 36px 20px 48px;
  }
}
</style>
