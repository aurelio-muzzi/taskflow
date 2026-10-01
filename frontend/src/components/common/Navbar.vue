<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import { LogOut, UserCircle } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

async function handleLogout() {
  await authStore.logout()
  toast.info('Você saiu do sistema.')
  router.push('/login')
}
</script>

<template>
  <header class="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center space-x-2">
      <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
        Workspace
      </span>
      <span class="text-slate-400 text-xs">/ TaskFlow Management</span>
    </div>

    <div class="flex items-center space-x-4">
      <div class="flex items-center space-x-2 text-xs text-slate-400">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="hidden sm:inline">Online</span>
      </div>

      <div class="h-4 w-px bg-slate-800"></div>

      <div class="flex items-center space-x-3">
        <RouterLink
          to="/profile"
          class="flex items-center space-x-2 text-xs font-medium text-slate-300 hover:text-white transition-colors"
        >
          <UserCircle class="w-4 h-4 text-emerald-400" />
          <span class="hidden md:inline">{{ authStore.user?.email }}</span>
        </RouterLink>

        <button
          @click="handleLogout"
          class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
          title="Encerrar Sessão"
        >
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </div>
  </header>
</template>
