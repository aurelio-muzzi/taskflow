<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '../layouts/AppLayout.vue'
import { useAuthStore } from '../stores/auth'
import { dashboardService } from '../services/dashboardService'
import type { DashboardMetrics } from '../types/dashboard'
import {
  FolderKanban,
  CheckSquare,
  AlertCircle,
  TrendingUp,
  Calendar,
  Layers,
  ShieldAlert,
  Loader2,
  Activity,
  Plus
} from '@lucide/vue'

const authStore = useAuthStore()
const loading = ref(true)
const scope = ref<'all' | 'mine'>('all')

const metrics = ref<DashboardMetrics>({
  projects: {
    total: 0,
    active: 0,
    planning: 0,
    completed: 0,
    on_hold: 0,
    archived: 0,
    by_status: {}
  },
  tasks: {
    total: 0,
    pending: 0,
    completed: 0,
    overdue: 0,
    completion_rate: 0,
    by_status: { todo: 0, in_progress: 0, review: 0, done: 0 },
    by_priority: { low: 0, medium: 0, high: 0, urgent: 0 }
  },
  my_tasks: {
    total: 0,
    pending: 0,
    completed: 0,
    overdue: 0,
    by_status: { todo: 0, in_progress: 0, review: 0, done: 0 }
  },
  upcoming_deadlines: [],
  recent_activities: []
})

async function fetchMetrics() {
  loading.value = true
  try {
    const res = await dashboardService.getMetrics()
    if (res.data) {
      metrics.value = res.data
    }
  } catch (err) {
    console.error('Erro ao carregar métricas:', err)
  } finally {
    loading.value = false
  }
}

// Valores computados dependendo do toggle 'all' vs 'mine'
const activeTasksCount = computed(() => {
  return scope.value === 'all' ? metrics.value.tasks.pending : metrics.value.my_tasks.pending
})

const completedTasksCount = computed(() => {
  return scope.value === 'all' ? metrics.value.tasks.completed : metrics.value.my_tasks.completed
})

const overdueTasksCount = computed(() => {
  return scope.value === 'all' ? metrics.value.tasks.overdue : metrics.value.my_tasks.overdue
})

const statusDistribution = computed(() => {
  return scope.value === 'all' ? metrics.value.tasks.by_status : metrics.value.my_tasks.by_status
})

function getEventBadge(event: string) {
  switch (event) {
    case 'created':
      return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
    case 'updated':
      return 'bg-blue-500/15 text-blue-400 border-blue-500/30'
    case 'status_changed':
      return 'bg-purple-500/15 text-purple-400 border-purple-500/30'
    case 'comment_added':
      return 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30'
    default:
      return 'bg-slate-800 text-slate-400 border-slate-700'
  }
}

function getEventLabel(event: string) {
  switch (event) {
    case 'created': return 'Criado'
    case 'updated': return 'Atualizado'
    case 'status_changed': return 'Status'
    case 'comment_added': return 'Comentário'
    default: return event
  }
}

function getPriorityBadge(priority: string) {
  switch (priority) {
    case 'low': return 'text-slate-400 bg-slate-800'
    case 'medium': return 'text-amber-400 bg-amber-950/60'
    case 'high': return 'text-orange-400 bg-orange-950/60'
    case 'urgent': return 'text-rose-400 bg-rose-950/60 font-semibold'
    default: return 'text-slate-400 bg-slate-800'
  }
}

function getDaysRemaining(dueDateStr: string): string {
  const due = new Date(dueDateStr)
  const now = new Date()
  const diffTime = due.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return `${Math.abs(diffDays)}d atrasada`
  if (diffDays === 0) return 'Hoje'
  return `${diffDays}d restantes`
}

onMounted(() => {
  fetchMetrics()
})
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Welcome Hero Banner -->
      <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="relative z-10 max-w-xl space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
            <Activity class="w-3.5 h-3.5" />
            Painel Executivo & Operacional
          </div>

          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
            Olá, {{ authStore.user?.name }}!
          </h1>

          <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
            Acompanhe o desempenho dos projetos, fluxo das demandas e indicadores da equipe em tempo real.
          </p>
        </div>

        <!-- Scope Toggle & Action buttons -->
        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center gap-3">
          <div class="p-1 rounded-xl bg-slate-950 border border-slate-800 flex items-center text-xs">
            <button
              @click="scope = 'all'"
              :class="[
                'px-3 py-1.5 rounded-lg font-medium transition-colors',
                scope === 'all'
                  ? 'bg-emerald-500 text-slate-950 font-semibold shadow-sm'
                  : 'text-slate-400 hover:text-white'
              ]"
            >
              Visão Geral
            </button>
            <button
              @click="scope = 'mine'"
              :class="[
                'px-3 py-1.5 rounded-lg font-medium transition-colors',
                scope === 'mine'
                  ? 'bg-emerald-500 text-slate-950 font-semibold shadow-sm'
                  : 'text-slate-400 hover:text-white'
              ]"
            >
              Minhas Demandas
            </button>
          </div>

          <RouterLink
            to="/tasks"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-semibold transition-colors"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Nova Tarefa</span>
          </RouterLink>
        </div>

        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center p-16 text-slate-400">
        <Loader2 class="w-8 h-8 animate-spin text-emerald-400 mb-3" />
        <span class="text-xs">Calculando indicadores e métricas...</span>
      </div>

      <template v-else>
        <!-- KPI Cards Grid (4 Colunas) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Card 1: Projetos Ativos -->
          <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 relative overflow-hidden group hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-3">
              <span class="text-xs font-medium">Projetos Ativos</span>
              <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <FolderKanban class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <p class="text-2xl font-bold text-white">{{ metrics.projects.active }}</p>
              <span class="text-xs text-slate-400 font-normal">de {{ metrics.projects.total }} totais</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2.5">
              <span>{{ metrics.projects.planning }} em planejamento</span>
              <RouterLink to="/projects" class="text-emerald-400 hover:text-emerald-300 font-medium">Ver todos</RouterLink>
            </div>
          </div>

          <!-- Card 2: Tarefas Pendentes -->
          <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 relative overflow-hidden group hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-3">
              <span class="text-xs font-medium">{{ scope === 'all' ? 'Demandas em Aberto' : 'Minhas Demandas em Aberto' }}</span>
              <div class="w-8 h-8 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                <CheckSquare class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <p class="text-2xl font-bold text-white">{{ activeTasksCount }}</p>
              <span class="text-xs text-slate-400 font-normal">pendentes</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2.5">
              <span>{{ completedTasksCount }} já finalizadas</span>
              <RouterLink to="/tasks" class="text-blue-400 hover:text-blue-300 font-medium">Gerenciar</RouterLink>
            </div>
          </div>

          <!-- Card 3: Taxa de Conclusão -->
          <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 relative overflow-hidden group hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-3">
              <span class="text-xs font-medium">Taxa de Conclusão</span>
              <div class="w-8 h-8 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                <TrendingUp class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <p class="text-2xl font-bold text-white">{{ metrics.tasks.completion_rate }}%</p>
              <span class="text-xs text-slate-400 font-normal">eficiência</span>
            </div>
            <div class="w-full bg-slate-950 h-1.5 rounded-full mt-3 overflow-hidden">
              <div
                class="bg-purple-500 h-full rounded-full transition-all duration-500"
                :style="{ width: `${metrics.tasks.completion_rate}%` }"
              ></div>
            </div>
          </div>

          <!-- Card 4: Demandas Atrasadas -->
          <div :class="[
            'p-5 rounded-2xl border relative overflow-hidden transition-colors',
            overdueTasksCount > 0
              ? 'bg-rose-950/20 border-rose-500/30'
              : 'bg-slate-900/60 border-slate-800'
          ]">
            <div class="flex items-center justify-between text-slate-400 mb-3">
              <span class="text-xs font-medium">Demandas Atrasadas</span>
              <div :class="[
                'w-8 h-8 rounded-xl flex items-center justify-center',
                overdueTasksCount > 0 ? 'bg-rose-500/20 text-rose-400' : 'bg-slate-800 text-slate-500'
              ]">
                <AlertCircle class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <p :class="['text-2xl font-bold', overdueTasksCount > 0 ? 'text-rose-400' : 'text-white']">
                {{ overdueTasksCount }}
              </p>
              <span class="text-xs text-slate-400 font-normal">prazo expirado</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-800/80 pt-2.5">
              <span>{{ overdueTasksCount > 0 ? 'Requer atenção imediata' : 'Tudo em dia' }}</span>
              <RouterLink to="/tasks" class="text-rose-400 hover:text-rose-300 font-medium">Verificar</RouterLink>
            </div>
          </div>
        </div>

        <!-- Seções Médias: Distribuição de Tarefas & Detalhamento por Prioridade -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Distribuição por Status -->
          <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Layers class="w-4 h-4 text-emerald-400" />
                <h3 class="text-sm font-semibold text-white">Status do Fluxo de Trabalho</h3>
              </div>
              <span class="text-xs text-slate-400 font-mono">
                Total: {{ statusDistribution.todo + statusDistribution.in_progress + statusDistribution.review + statusDistribution.done }}
              </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="text-[11px] text-slate-400 block mb-1">A Fazer</span>
                <span class="text-lg font-bold text-white">{{ statusDistribution.todo }}</span>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="text-[11px] text-blue-400 block mb-1">Em Progresso</span>
                <span class="text-lg font-bold text-blue-300">{{ statusDistribution.in_progress }}</span>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="text-[11px] text-purple-400 block mb-1">Em Revisão</span>
                <span class="text-lg font-bold text-purple-300">{{ statusDistribution.review }}</span>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="text-[11px] text-emerald-400 block mb-1">Concluídas</span>
                <span class="text-lg font-bold text-emerald-300">{{ statusDistribution.done }}</span>
              </div>
            </div>

            <!-- Barra combinada colorida -->
            <div class="space-y-1.5 pt-2">
              <div class="w-full bg-slate-950 h-3 rounded-full overflow-hidden flex">
                <div
                  class="bg-slate-700 h-full transition-all"
                  :style="{ width: `${(statusDistribution.todo / (metrics.tasks.total || 1)) * 100}%` }"
                  title="A Fazer"
                ></div>
                <div
                  class="bg-blue-500 h-full transition-all"
                  :style="{ width: `${(statusDistribution.in_progress / (metrics.tasks.total || 1)) * 100}%` }"
                  title="Em Progresso"
                ></div>
                <div
                  class="bg-purple-500 h-full transition-all"
                  :style="{ width: `${(statusDistribution.review / (metrics.tasks.total || 1)) * 100}%` }"
                  title="Em Revisão"
                ></div>
                <div
                  class="bg-emerald-500 h-full transition-all"
                  :style="{ width: `${(statusDistribution.done / (metrics.tasks.total || 1)) * 100}%` }"
                  title="Concluída"
                ></div>
              </div>
            </div>
          </div>

          <!-- Distribuição por Prioridade -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
              <AlertCircle class="w-4 h-4 text-amber-400" />
              <span>Volume por Prioridade</span>
            </h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="flex items-center gap-2 text-rose-400 font-semibold">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  Urgente
                </span>
                <span class="font-bold text-white">{{ metrics.tasks.by_priority.urgent }}</span>
              </div>

              <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="flex items-center gap-2 text-orange-400 font-semibold">
                  <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                  Alta
                </span>
                <span class="font-bold text-white">{{ metrics.tasks.by_priority.high }}</span>
              </div>

              <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="flex items-center gap-2 text-amber-400">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  Média
                </span>
                <span class="font-bold text-white">{{ metrics.tasks.by_priority.medium }}</span>
              </div>

              <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <span class="flex items-center gap-2 text-slate-400">
                  <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                  Baixa
                </span>
                <span class="font-bold text-white">{{ metrics.tasks.by_priority.low }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Seções Inferiores: Próximos Prazos & Feed de Auditoria Recente -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Próximos Prazos -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Calendar class="w-4 h-4 text-emerald-400" />
                <h3 class="text-sm font-semibold text-white">Próximos Prazos de Entrega</h3>
              </div>
              <RouterLink to="/tasks" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">
                Ver todas
              </RouterLink>
            </div>

            <div v-if="metrics.upcoming_deadlines.length === 0" class="p-8 text-center text-xs text-slate-500">
              Nenhuma demanda com prazo pendente próximo.
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="task in metrics.upcoming_deadlines"
                :key="task.id"
                class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between text-xs"
              >
                <div class="space-y-1 max-w-[65%]">
                  <div class="flex items-center gap-2">
                    <span v-if="task.project" class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] font-mono text-emerald-400">
                      {{ task.project.code }}
                    </span>
                    <span class="font-medium text-white line-clamp-1">{{ task.title }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-[11px] text-slate-400">
                    <span v-if="task.assignee">Resp: {{ task.assignee.name }}</span>
                    <span v-else class="italic">Sem responsável</span>
                  </div>
                </div>

                <div class="text-right">
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold block mb-1', getPriorityBadge(task.priority)]">
                    {{ task.priority_label }}
                  </span>
                  <span v-if="task.due_date" class="text-[11px] font-mono text-slate-400">
                    {{ getDaysRemaining(task.due_date) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Feed de Atividades Recentes -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ShieldAlert class="w-4 h-4 text-emerald-400" />
                <h3 class="text-sm font-semibold text-white">Atividades Recentes no Sistema</h3>
              </div>
              <RouterLink
                v-if="authStore.isAdmin || authStore.isManager"
                to="/audit-logs"
                class="text-xs text-emerald-400 hover:text-emerald-300 font-medium"
              >
                Trilha completa
              </RouterLink>
            </div>

            <div v-if="metrics.recent_activities.length === 0" class="p-8 text-center text-xs text-slate-500">
              Nenhuma atividade registrada recentemente.
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="act in metrics.recent_activities"
                :key="act.id"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800/80 flex items-start gap-3 text-xs"
              >
                <div class="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center font-bold text-[10px] text-slate-300 flex-shrink-0 mt-0.5">
                  {{ act.user?.name ? act.user.name.charAt(0) : 'S' }}
                </div>

                <div class="flex-1 min-w-0 space-y-1">
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-white truncate">{{ act.user?.name || 'Sistema' }}</span>
                    <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold border', getEventBadge(act.event)]">
                      {{ getEventLabel(act.event) }}
                    </span>
                  </div>
                  <p class="text-slate-400 text-[11px] line-clamp-1">{{ act.description || 'Operação registrada' }}</p>
                  <span class="text-[10px] text-slate-500 block font-mono">
                    {{ new Date(act.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
