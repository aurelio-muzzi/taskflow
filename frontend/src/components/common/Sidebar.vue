<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import {
  LayoutDashboard,
  FolderKanban,
  CheckSquare,
  Users,
  ShieldAlert,
  UserCircle,
  Layers,
  LogOut
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const navSections = computed(() => [
  {
    title: 'Visão Geral',
    show: true,
    items: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard, show: true },
      { name: 'Projetos', path: '/projects', icon: FolderKanban, show: true },
      { name: 'Tarefas', path: '/tasks', icon: CheckSquare, show: true },
    ],
  },
  {
    title: 'Administração',
    show: authStore.isAdmin,
    items: [
      { name: 'Usuários', path: '/users', icon: Users, show: authStore.isAdmin },
      { name: 'Auditoria', path: '/audit-logs', icon: ShieldAlert, show: authStore.isAdmin },
    ],
  },
  {
    title: 'Preferências',
    show: true,
    items: [
      { name: 'Meu Perfil', path: '/profile', icon: UserCircle, show: true },
    ],
  },
])

async function handleLogout() {
  await authStore.logout()
  toast.info('Você saiu do sistema.')
  router.push('/login')
}
</script>

<template>
  <aside class="w-64 lg:w-72 bg-slate-900/95 backdrop-blur-xl border-r border-slate-800/80 flex flex-col shrink-0 h-full select-none z-30">
    <!-- Brand Header -->
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800/80 shrink-0 bg-slate-950/20">
      <RouterLink to="/dashboard" class="flex items-center space-x-3 group">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform duration-200">
          <Layers class="w-5 h-5 text-slate-950" />
        </div>
        <div class="flex flex-col">
          <span class="text-base font-bold tracking-tight text-white leading-none">TaskFlow</span>
          <span class="text-[10px] text-slate-400 font-mono tracking-wider mt-0.5">ENTERPRISE</span>
        </div>
      </RouterLink>

      <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
        v1.0
      </span>
    </div>

    <!-- Navigation Scroll Area -->
    <nav class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
      <template v-for="section in navSections" :key="section.title">
        <div v-if="section.show !== false" class="space-y-1">
          <p class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider font-mono mb-2">
            {{ section.title }}
          </p>

          <template v-for="item in section.items" :key="item.path">
            <RouterLink
              v-if="item.show"
              :to="item.path"
              :class="[
                'group relative flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                route.path.startsWith(item.path)
                  ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 shadow-sm shadow-emerald-500/5'
                  : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60 border border-transparent'
              ]"
            >
              <div class="flex items-center space-x-3 min-w-0">
                <component
                  :is="item.icon"
                  :class="[
                    'w-4 h-4 flex-shrink-0 transition-colors',
                    route.path.startsWith(item.path) ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-200'
                  ]"
                />
                <span class="truncate">{{ item.name }}</span>
              </div>

              <div
                v-if="route.path.startsWith(item.path)"
                class="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400"
              />
            </RouterLink>
          </template>
        </div>
      </template>
    </nav>

    <!-- Bottom User Footer Card -->
    <div class="p-3 border-t border-slate-800/80 bg-slate-950/40 shrink-0">
      <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700/80 transition-colors">
        <RouterLink to="/profile" class="flex items-center space-x-3 min-w-0 flex-1 hover:opacity-90 transition-opacity">
          <div class="relative w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500/20 to-teal-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
            {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
            <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-400 border-2 border-slate-900"></span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-slate-200 truncate leading-tight">
              {{ authStore.user?.name }}
            </p>
            <p class="text-[10px] text-emerald-400 uppercase tracking-wider font-mono mt-0.5 truncate">
              {{ authStore.user?.role?.name || 'Usuário' }}
            </p>
          </div>
        </RouterLink>

        <button
          @click="handleLogout"
          class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors shrink-0 ml-1"
          title="Sair do sistema"
        >
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </div>
  </aside>
</template>
