<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import AppLayout from '../../layouts/AppLayout.vue'
import Pagination from '../../components/common/Pagination.vue'
import Modal from '../../components/common/Modal.vue'
import { auditService } from '../../services/auditService'
import { useToast } from '../../composables/useToast'
import type { AuditLog } from '../../types/audit'
import type { PaginationMeta } from '../../types/api'
import {
  ShieldAlert,
  Clock,
  Globe,
  Loader2,
  FileCode,
  Layers
} from '@lucide/vue'

const toast = useToast()

const logs = ref<AuditLog[]>([])
const loading = ref(true)

const pagination = reactive<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

const filters = reactive({
  event: '',
  auditable_type: ''
})

// Modal de Detalhes dos Valores
const selectedLog = ref<AuditLog | null>(null)
const isDetailModalOpen = ref(false)

async function fetchAuditLogs(page = 1) {
  loading.value = true
  try {
    const res = await auditService.getAuditLogs({
      page,
      per_page: pagination.per_page,
      event: filters.event || undefined,
      auditable_type: filters.auditable_type || undefined
    })

    if (res.data) {
      logs.value = res.data.items
      pagination.current_page = res.data.pagination.current_page
      pagination.last_page = res.data.pagination.last_page
      pagination.total = res.data.pagination.total
    }
  } catch {
    toast.error('Erro ao carregar trilha de auditoria.')
  } finally {
    loading.value = false
  }
}

function handlePageChange(newPage: number) {
  fetchAuditLogs(newPage)
}

function openDetailModal(log: AuditLog) {
  selectedLog.value = log
  isDetailModalOpen.value = true
}

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
    case 'comment_deleted':
    case 'deleted':
      return 'bg-rose-500/15 text-rose-400 border-rose-500/30'
    default:
      return 'bg-slate-800 text-slate-400 border-slate-700'
  }
}

function getEventLabel(event: string) {
  switch (event) {
    case 'created': return 'Criação'
    case 'updated': return 'Atualização'
    case 'status_changed': return 'Status'
    case 'comment_added': return 'Comentário'
    case 'comment_deleted': return 'Comentário Excluído'
    case 'deleted': return 'Exclusão'
    default: return event
  }
}

onMounted(() => {
  fetchAuditLogs()
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <ShieldAlert class="w-6 h-6 text-emerald-400" />
          <span>Trilha de Auditoria</span>
        </h1>
        <p class="text-xs text-slate-400">
          Registro imutável de eventos operacionais, alterações de estado e rastreabilidade de dados do TaskFlow.
        </p>
      </div>

      <!-- Barra de Filtros -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Filtro por Evento -->
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tipo de Evento</label>
          <select
            v-model="filters.event"
            @change="fetchAuditLogs(1)"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
          >
            <option value="">Todos os Eventos</option>
            <option value="created">Criação (created)</option>
            <option value="updated">Atualização (updated)</option>
            <option value="status_changed">Mudança de Status (status_changed)</option>
            <option value="comment_added">Comentário Adicionado (comment_added)</option>
            <option value="deleted">Exclusão (deleted)</option>
          </select>
        </div>

        <!-- Filtro por Entidade -->
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 mb-1">Entidade Auditada</label>
          <select
            v-model="filters.auditable_type"
            @change="fetchAuditLogs(1)"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
          >
            <option value="">Todas as Entidades</option>
            <option value="Task">Tarefas (Task)</option>
            <option value="Project">Projetos (Project)</option>
            <option value="User">Usuários (User)</option>
          </select>
        </div>
      </div>

      <!-- Tabela de Logs -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div v-if="loading" class="flex flex-col items-center justify-center p-12 text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin mb-3 text-emerald-400" />
          <span class="text-xs">Carregando trilha de auditoria...</span>
        </div>

        <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center p-12 text-center">
          <Layers class="w-12 h-12 text-slate-700 mb-3" />
          <h3 class="text-sm font-semibold text-white">Nenhum registro de auditoria</h3>
          <p class="text-xs text-slate-400 mt-1 max-w-sm">
            Eventos e transições de estado registrados pelo sistema aparecerão nesta seção.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <th class="px-5 py-3.5">Evento</th>
                <th class="px-4 py-3.5">Entidade</th>
                <th class="px-5 py-3.5">Descrição</th>
                <th class="px-4 py-3.5">Usuário</th>
                <th class="px-4 py-3.5">IP</th>
                <th class="px-4 py-3.5">Data & Hora</th>
                <th class="px-5 py-3.5 text-right">Valores</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr
                v-for="log in logs"
                :key="log.id"
                class="hover:bg-slate-800/30 transition-colors"
              >
                <!-- Evento -->
                <td class="px-5 py-3.5 whitespace-nowrap">
                  <span :class="['px-2.5 py-1 rounded-full text-[10px] font-semibold border', getEventBadge(log.event)]">
                    {{ getEventLabel(log.event) }}
                  </span>
                </td>

                <!-- Entidade -->
                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-[11px] text-slate-300">
                  <span class="text-emerald-400">{{ log.auditable_name }}</span> #{{ log.auditable_id }}
                </td>

                <!-- Descrição -->
                <td class="px-5 py-3.5 max-w-sm text-slate-300">
                  {{ log.description || '—' }}
                </td>

                <!-- Usuário -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <div v-if="log.user" class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded-full bg-slate-800 flex items-center justify-center text-[10px] text-slate-300 font-bold">
                      {{ log.user.name.charAt(0) }}
                    </div>
                    <span class="text-slate-300">{{ log.user.name }}</span>
                  </div>
                  <span v-else class="text-slate-500 italic text-[11px]">Sistema</span>
                </td>

                <!-- IP -->
                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-[11px] text-slate-400">
                  <div class="flex items-center gap-1.5">
                    <Globe class="w-3 h-3 text-slate-500" />
                    <span>{{ log.ip_address || '—' }}</span>
                  </div>
                </td>

                <!-- Data & Hora -->
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-400 text-[11px]">
                  <div class="flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-slate-500" />
                    <span>{{ new Date(log.created_at).toLocaleString('pt-BR') }}</span>
                  </div>
                </td>

                <!-- Ações / Snapshot -->
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <button
                    v-if="log.old_values || log.new_values"
                    @click="openDetailModal(log)"
                    class="inline-flex items-center gap-1 text-[11px] text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
                  >
                    <FileCode class="w-3.5 h-3.5" />
                    <span>Ver Snapshot</span>
                  </button>
                  <span v-else class="text-slate-600 text-[11px]">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination :meta="pagination" @change-page="handlePageChange" />
      </div>

      <!-- Modal de Snapshot de Dados -->
      <Modal
        :show="isDetailModalOpen"
        title="Snapshot de Auditoria de Dados"
        max-width="lg"
        @close="isDetailModalOpen = false"
      >
        <div class="space-y-4 text-xs">
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-1 font-mono text-[11px]">
            <div><span class="text-slate-500">Entidade:</span> <span class="text-emerald-400">{{ selectedLog?.auditable_name }} #{{ selectedLog?.auditable_id }}</span></div>
            <div><span class="text-slate-500">Evento:</span> <span class="text-white">{{ selectedLog?.event }}</span></div>
            <div><span class="text-slate-500">Data:</span> <span class="text-slate-300">{{ selectedLog ? new Date(selectedLog.created_at).toLocaleString('pt-BR') : '' }}</span></div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <span class="block font-semibold text-slate-400 mb-1">Valores Anteriores (Old):</span>
              <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[10px] text-rose-300 font-mono overflow-x-auto max-h-56">{{ JSON.stringify(selectedLog?.old_values, null, 2) || 'N/A' }}</pre>
            </div>
            <div>
              <span class="block font-semibold text-slate-400 mb-1">Novos Valores (New):</span>
              <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[10px] text-emerald-300 font-mono overflow-x-auto max-h-56">{{ JSON.stringify(selectedLog?.new_values, null, 2) || 'N/A' }}</pre>
            </div>
          </div>

          <div class="flex justify-end pt-3 border-t border-slate-800">
            <button
              @click="isDetailModalOpen = false"
              class="px-4 py-2 border border-slate-800 text-xs font-medium text-slate-400 rounded-xl hover:text-white hover:bg-slate-800 transition-colors"
            >
              Fechar
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>
