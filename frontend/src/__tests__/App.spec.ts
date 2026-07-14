import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import App from '@/App.vue'

describe('App', () => {
  it('renderiza a estrutura principal e a área de rotas', () => {
    const wrapper = mount(App, {
      global: {
        stubs: {
          VApp: {
            template: '<div data-testid="app-shell"><slot /></div>',
          },

          RouterView: {
            template: '<div data-testid="router-view" />',
          },
        },
      },
    })

    expect(wrapper.find('[data-testid="app-shell"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="router-view"]').exists()).toBe(true)
  })
})
