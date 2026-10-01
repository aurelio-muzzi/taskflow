<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  LayoutDashboard,
  FolderKanban,
  CheckSquare,
  Users,
  ShieldAlert,
  UserCircle,
  Layers
} from '@lucide/vue'

const route = useRoute()
const authStore = useAuthStore()

const menuItems = computed(() => [
  { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard, show: true },
  { name: 'Projetos', path: '/projects', icon: FolderKanban, show: true },
  { name: 'Tarefas', path: '/tasks', icon: CheckSquare, show: true },
  { name: 'Usuários', path: '/users', icon: Users, show: authStore.isAdmin },
  { name: 'Auditoria', path: '/audit-logs', icon: ShieldAlert, show: authStore.isAdmin },
  { name: 'Meu Perfil', path: '/profile', icon: UserCircle, show: true },
])
</script>

<template>
  <aside class="w-64 bg-slate-900/90 border-r border-slate-800 flex flex-col justify-between shrink-0 h-screen sticky top-0">
    <div>
      <!-- Brand Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-800/80">
        <div class="flex items-center space-x-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <Layers class="w-5 h-5 text-slate-950" />
          </div>
          <span class="text-xl font-bold tracking-tight text-white">TaskFlow</span>
        </div>
      </div>

      <!-- Navigation Links -->
      <nav class="p-4 space-y-1.5">
        <template v-for="item in menuItems" :key="item.path">
          <RouterLink
            v-if="item.show"
            :to="item.path"
            :class="[
              'flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all',
              route.path.startsWith(item.path)
                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm'
                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'
            ]"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            <span>{{ item.name }}</span>
          </RouterLink>
        </template>
      </nav>
    </div>

    <!-- Bottom Role Badge -->
    <div class="p-4 border-t border-slate-800/80 m-4 rounded-xl bg-slate-950/40 border">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
          {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
        </div>
        <div class="overflow-hidden">
          <p class="text-xs font-semibold text-white truncate">{{ authStore.user?.name }}</p>
          <p class="text-[10px] text-emerald-400 uppercase tracking-wider font-mono">
            {{ authStore.user?.role?.name || 'Usuário' }}
          </p>
        </div>
      </div>
    </div>
  </aside>
</template>
