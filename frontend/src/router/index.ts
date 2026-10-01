import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: () => {
      const token = localStorage.getItem('taskflow_token')
      return token ? '/dashboard' : '/login'
    }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/LoginView.vue'),
    meta: { title: 'Login — TaskFlow', guestOnly: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { title: 'Dashboard — TaskFlow', requiresAuth: true }
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('../views/users/UserListView.vue'),
    meta: { title: 'Gestão de Usuários — TaskFlow', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/profile/ProfileView.vue'),
    meta: { title: 'Meu Perfil — TaskFlow', requiresAuth: true }
  },
  {
    path: '/projects',
    name: 'projects',
    component: () => import('../views/PlaceholderView.vue'),
    props: {
      title: 'Módulo de Projetos',
      stage: 'Etapa 3',
      description: 'Gestão de projetos, cronogramas, membros de equipe e controle de status.'
    },
    meta: { title: 'Projetos — TaskFlow', requiresAuth: true }
  },
  {
    path: '/tasks',
    name: 'tasks',
    component: () => import('../views/PlaceholderView.vue'),
    props: {
      title: 'Módulo de Tarefas & Kanban',
      stage: 'Etapa 4 e 7',
      description: 'Gerenciamento de tarefas, prioridades, estimativas e fluxo Kanban interativo.'
    },
    meta: { title: 'Tarefas — TaskFlow', requiresAuth: true }
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: () => import('../views/PlaceholderView.vue'),
    props: {
      title: 'Trilha de Auditoria',
      stage: 'Etapa 5',
      description: 'Registro cronológico de alterações, snapshots de dados e rastreabilidade total.'
    },
    meta: { title: 'Auditoria — TaskFlow', requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  if (to.meta.title) {
    document.title = String(to.meta.title)
  }

  const token = localStorage.getItem('taskflow_token')
  const rawUser = localStorage.getItem('taskflow_user')
  const user = rawUser ? JSON.parse(rawUser) : null

  // Rota exige autenticação
  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login' })
  }

  // Rota de visitante (ex: login) redireciona quem já está autenticado
  if (to.meta.guestOnly && token) {
    return next({ name: 'dashboard' })
  }

  // Rota exige privilégio de Administrador
  if (to.meta.requiresAdmin && user?.role?.slug !== 'admin') {
    return next({ name: 'dashboard' })
  }

  next()
})

export default router
