<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import { notificationService } from '../../services/notificationService'
import type { InternalNotification } from '../../types/notification'
import GlobalSearchModal from './GlobalSearchModal.vue'
import { LogOut, UserCircle, Bell, CheckCheck, Clock, Search, Menu } from '@lucide/vue'
import { useSidebar } from '../../composables/useSidebar'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const { toggle } = useSidebar()

const notifications = ref<InternalNotification[]>([])
const unreadCount = ref(0)
const isNotificationsOpen = ref(false)
const isSearchOpen = ref(false)

async function fetchNotifications() {
  try {
    const res = await notificationService.getNotifications()
    if (res.data) {
      notifications.value = res.data.items
      unreadCount.value = res.data.unread_count
    }
  } catch {
    // Silently continue
  }
}

async function markAsRead(notification: InternalNotification) {
  if (notification.is_read) return
  try {
    await notificationService.markAsRead(notification.id)
    notification.is_read = true
    if (unreadCount.value > 0) unreadCount.value--
  } catch {
    toast.error('Erro ao marcar notificação.')
  }
}

async function markAllAsRead() {
  try {
    await notificationService.markAllAsRead()
    notifications.value.forEach(n => { n.is_read = true })
    unreadCount.value = 0
    toast.success('Todas as notificações foram marcadas como lidas.')
  } catch {
    toast.error('Erro ao atualizar notificações.')
  }
}

function toggleNotifications() {
  isNotificationsOpen.value = !isNotificationsOpen.value
  if (isNotificationsOpen.value) {
    fetchNotifications()
  }
}

function handleClickOutside(e: MouseEvent) {
  const target = e.target as HTMLElement
  if (!target.closest('#notifications-wrapper')) {
    isNotificationsOpen.value = false
  }
}

function handleGlobalKeydown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    isSearchOpen.value = !isSearchOpen.value
  }
}

async function handleLogout() {
  await authStore.logout()
  toast.info('Você saiu do sistema.')
  router.push('/login')
}

onMounted(() => {
  fetchNotifications()
  window.addEventListener('click', handleClickOutside)
  window.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
  window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<template>
  <header class="h-16 shrink-0 border-b border-slate-800/80 bg-slate-900/80 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between z-30">
    <div class="flex items-center space-x-2.5">
      <!-- Botão Hamburger para Tablet e Mobile (< lg) -->
      <button
        @click="toggle"
        class="lg:hidden p-2 -ml-1 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/70 transition-colors cursor-pointer"
        title="Menu de navegação"
        aria-label="Menu de navegação"
      >
        <Menu class="w-5 h-5 text-emerald-400" />
      </button>

      <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-md border border-emerald-500/20 font-semibold">
        Workspace
      </span>
      <span class="text-slate-400 text-xs font-medium hidden md:inline">/ TaskFlow Management</span>
    </div>

    <div class="flex items-center space-x-3 sm:space-x-4">
      <!-- Atalho para Busca Global -->
      <button
        @click="isSearchOpen = true"
        class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 hover:text-white hover:border-slate-700 transition-colors shadow-sm"
        title="Buscar (Ctrl+K)"
      >
        <Search class="w-3.5 h-3.5 text-slate-500" />
        <span class="hidden sm:inline">Buscar...</span>
        <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono text-slate-500 bg-slate-900 border border-slate-800 rounded">
          Ctrl+K
        </kbd>
      </button>

      <!-- Sino de Notificações com Dropdown -->
      <div id="notifications-wrapper" class="relative flex items-center">
        <button
          @click.stop="toggleNotifications"
          class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/70 transition-colors"
          title="Notificações"
        >
          <Bell class="w-4 h-4" />
          <span
            v-if="unreadCount > 0"
            class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400 animate-pulse"
          />
        </button>

        <!-- Menu Flutuante de Notificações -->
        <div
          v-if="isNotificationsOpen"
          class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 max-w-sm rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden z-50 animate-in fade-in zoom-in-95 duration-150"
        >
          <div class="p-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-950/60">
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-white">Notificações</span>
              <span
                v-if="unreadCount > 0"
                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"
              >
                {{ unreadCount }} nova(s)
              </span>
            </div>

            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-[11px] text-emerald-400 hover:text-emerald-300 flex items-center gap-1 font-medium transition-colors"
            >
              <CheckCheck class="w-3.5 h-3.5" />
              <span>Marcar lidas</span>
            </button>
          </div>

          <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60">
            <div v-if="notifications.length === 0" class="p-8 text-center text-xs text-slate-500">
              Nenhuma notificação no momento.
            </div>

            <div
              v-for="item in notifications"
              :key="item.id"
              @click="markAsRead(item)"
              :class="[
                'p-3.5 transition-colors cursor-pointer text-left',
                item.is_read ? 'hover:bg-slate-800/30 opacity-70' : 'bg-slate-800/40 hover:bg-slate-800/70 border-l-2 border-emerald-400'
              ]"
            >
              <div class="flex items-start justify-between gap-2">
                <span class="text-xs font-semibold text-white line-clamp-1">{{ item.title }}</span>
                <span class="text-[10px] text-slate-500 whitespace-nowrap flex items-center gap-1">
                  <Clock class="w-3 h-3" />
                  {{ new Date(item.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}
                </span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">{{ item.message }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center space-x-2 text-xs text-slate-400 px-2.5 py-1 rounded-lg bg-slate-950/40 border border-slate-800/60">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="hidden sm:inline font-medium">Online</span>
      </div>

      <div class="h-4 w-px bg-slate-800"></div>

      <div class="flex items-center space-x-2">
        <RouterLink
          to="/profile"
          class="flex items-center space-x-2 text-xs font-medium text-slate-300 hover:text-white px-2 py-1.5 rounded-lg hover:bg-slate-800/60 transition-colors"
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

  <!-- Modal de Busca Global Spotlight -->
  <GlobalSearchModal :show="isSearchOpen" @close="isSearchOpen = false" />
</template>
