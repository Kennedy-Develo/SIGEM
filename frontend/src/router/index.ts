import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: {
        title: 'Entrar | SIGEM',
        guestOnly: true,
      },
    },
    {
      path: '/solicitar-acesso',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: {
        title: 'Solicitar acesso | SIGEM',
        guestOnly: true,
      },
    },
    {
      path: '/esqueci-minha-senha',
      name: 'forgot-password',
      component: () => import('@/views/auth/ForgotPasswordView.vue'),
      meta: {
        title: 'Recuperar senha | SIGEM',
        guestOnly: true,
      },
    },
    {
      path: '/redefinir-senha',
      name: 'reset-password',
      component: () => import('@/views/auth/ResetPasswordView.vue'),
      meta: {
        title: 'Redefinir senha | SIGEM',
        guestOnly: true,
      },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: {
        requiresAuth: true,
      },
      children: [
        {
          path: '',
          redirect: {
            name: 'dashboard',
          },
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/DashboardView.vue'),
          meta: {
            title: 'Painel | SIGEM',
          },
        },
        {
          path: 'manifestacoes',
          name: 'manifestations',
          component: () => import('@/views/manifestations/ManifestationsView.vue'),
          meta: {
            title: 'Manifestações | SIGEM',
          },
        },
        {
          path: 'administracao/usuarios',
          name: 'admin-users',
          component: () => import('@/views/admin/UsersView.vue'),
          meta: {
            title: 'Gestão de usuários | SIGEM',
            requiresAdmin: true,
          },
        },
        {
          path: 'administracao/auditoria',
          name: 'admin-audit',
          component: () => import('@/views/admin/AuditLogsView.vue'),
          meta: {
            title: 'Histórico de auditoria | SIGEM',
            requiresAdmin: true,
          },
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchUser()
  }

  const requiresAuth = to.matched.some((route) => route.meta.requiresAuth)

  const requiresAdmin = to.matched.some((route) => route.meta.requiresAdmin)

  if (requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (requiresAdmin && !auth.isAdministrator) {
    return {
      name: 'dashboard',
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return {
      name: 'dashboard',
    }
  }

  return true
})

router.afterEach((to) => {
  document.title = typeof to.meta.title === 'string' ? to.meta.title : 'SIGEM'
})

export default router
