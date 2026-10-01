<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardService } from '../../services/dashboardService'
import type { GlobalSearchResults } from '../../types/dashboard'
import {
  Search,
  Folder,
  CheckSquare,
  Users,
  Loader2,
  X,
  ArrowRight
} from '@lucide/vue'

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const router = useRouter()
const searchInput = ref<HTMLInputElement | null>(null)
const query = ref('')
const loading = ref(false)
const results = ref<GlobalSearchResults>({
  projects: [],
  tasks: [],
  users: []
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function handleInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (query.value.trim().length < 2) {
    results.value = { projects: [], tasks: [], users: [] }
    loading.value = false
    return
  }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const res = await dashboardService.globalSearch(query.value.trim(), 4)
      if (res.data) {
        results.value = res.data
      }
    } catch {
      // Silently continue
    } finally {
      loading.value = false
    }
  }, 250)
}

function navigateTo(path: string) {
  emit('close')
  router.push(path)
}

function handleKeyDown(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    query.value = ''
    results.value = { projects: [], tasks: [], users: [] }
    setTimeout(() => {
      searchInput.value?.focus()
    }, 50)
  }
})

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="ease-out duration-200 transition"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-150 transition"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-start justify-center pt-20 px-4"
        @click.self="emit('close')"
      >
        <div class="w-full max-w-2xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
          <!-- Search Header Input -->
          <div class="p-4 border-b border-slate-800 flex items-center gap-3 bg-slate-950/60">
            <Search class="w-5 h-5 text-emerald-400 flex-shrink-0" />
            <input
              ref="searchInput"
              v-model="query"
              @input="handleInput"
              type="text"
              placeholder="Pesquisar projetos, demandas ou membros da equipe..."
              class="w-full bg-transparent text-sm text-white placeholder-slate-500 focus:outline-none"
            />
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin text-slate-400 flex-shrink-0" />
            <button
              v-else-if="query"
              @click="query = ''; results = { projects: [], tasks: [], users: [] }"
              class="text-slate-400 hover:text-white"
            >
              <X class="w-4 h-4" />
            </button>
            <span class="px-2 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-800 rounded border border-slate-700">
              ESC
            </span>
          </div>

          <!-- Search Results Container -->
          <div class="max-h-96 overflow-y-auto p-4 space-y-4">
            <div v-if="!query || query.length < 2" class="p-8 text-center text-xs text-slate-500">
              Digite pelo menos 2 caracteres para buscar em todo o ecossistema.
            </div>

            <div
              v-else-if="!loading && results.projects.length === 0 && results.tasks.length === 0 && results.users.length === 0"
              class="p-8 text-center text-xs text-slate-400"
            >
              Nenhum resultado encontrado para <strong class="text-white">"{{ query }}"</strong>.
            </div>

            <!-- Projetos -->
            <div v-if="results.projects.length > 0" class="space-y-2">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 px-1">
                <Folder class="w-3.5 h-3.5 text-emerald-400" />
                <span>Projetos ({{ results.projects.length }})</span>
              </span>
              <div class="space-y-1">
                <div
                  v-for="p in results.projects"
                  :key="p.id"
                  @click="navigateTo(`/projects/${p.id}`)"
                  class="p-2.5 rounded-xl hover:bg-slate-800/60 cursor-pointer flex items-center justify-between text-xs transition-colors border border-transparent hover:border-slate-800"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-emerald-400 font-semibold px-1.5 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-[10px]">
                      {{ p.code }}
                    </span>
                    <span class="font-medium text-white">{{ p.name }}</span>
                  </div>
                  <ArrowRight class="w-3.5 h-3.5 text-slate-500" />
                </div>
              </div>
            </div>

            <!-- Tarefas -->
            <div v-if="results.tasks.length > 0" class="space-y-2">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 px-1">
                <CheckSquare class="w-3.5 h-3.5 text-blue-400" />
                <span>Demandas / Tarefas ({{ results.tasks.length }})</span>
              </span>
              <div class="space-y-1">
                <div
                  v-for="t in results.tasks"
                  :key="t.id"
                  @click="navigateTo('/tasks')"
                  class="p-2.5 rounded-xl hover:bg-slate-800/60 cursor-pointer flex items-center justify-between text-xs transition-colors border border-transparent hover:border-slate-800"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-white">{{ t.title }}</span>
                    <span v-if="t.project" class="text-[10px] text-slate-500 font-mono">
                      ({{ t.project.code }})
                    </span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-medium px-2 py-0.5 rounded bg-slate-800">
                    {{ t.status_label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Usuários -->
            <div v-if="results.users.length > 0" class="space-y-2">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 px-1">
                <Users class="w-3.5 h-3.5 text-purple-400" />
                <span>Membros da Equipe ({{ results.users.length }})</span>
              </span>
              <div class="space-y-1">
                <div
                  v-for="u in results.users"
                  :key="u.id"
                  @click="navigateTo('/users')"
                  class="p-2.5 rounded-xl hover:bg-slate-800/60 cursor-pointer flex items-center justify-between text-xs transition-colors border border-transparent hover:border-slate-800"
                >
                  <div class="flex items-center gap-2.5">
                    <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-300">
                      {{ u.name.charAt(0) }}
                    </div>
                    <div>
                      <span class="font-medium text-white block">{{ u.name }}</span>
                      <span class="text-[10px] text-slate-500">{{ u.email }}</span>
                    </div>
                  </div>
                  <span class="text-[10px] text-purple-400 font-medium px-2 py-0.5 rounded bg-purple-500/10 border border-purple-500/20">
                    {{ u.role?.name || 'Membro' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Shortcut info -->
          <div class="p-3 bg-slate-950/80 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between px-4">
            <span>Dica: Use <strong>Ctrl + K</strong> para abrir esta busca a qualquer momento.</span>
            <span>TaskFlow Search</span>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
