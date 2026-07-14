import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

import { createVuetify } from 'vuetify'
import type { ThemeDefinition } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

const sigemLight: ThemeDefinition = {
  dark: false,
  colors: {
    background: '#F4F7FB',
    surface: '#FFFFFF',
    primary: '#163A5F',
    secondary: '#0E7490',
    accent: '#14B8A6',
    info: '#2563EB',
    success: '#16A34A',
    warning: '#D97706',
    error: '#DC2626',
    'on-background': '#172033',
    'on-surface': '#172033',
    'surface-variant': '#E8EEF5',
  },
}

export default createVuetify({
  theme: {
    defaultTheme: 'sigemLight',
    themes: {
      sigemLight,
    },
  },

  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },

  defaults: {
    VBtn: {
      rounded: 'lg',
      elevation: 0,
    },

    VCard: {
      rounded: 'xl',
      elevation: 0,
    },

    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
    },

    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
    },
  },
})
