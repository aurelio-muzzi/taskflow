<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '../../layouts/AppLayout.vue'
import Modal from '../../components/common/Modal.vue'
import Pagination from '../../components/common/Pagination.vue'
import { projectService } from '../../services/projectService'
import { userService } from '../../services/userService'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import type { Project, ProjectStatus } from '../../types/project'
import type { User } from '../../types/auth'
import type { PaginationMeta } from '../../types/api'
import {
  FolderKanban,
  Plus,
  Search,
  Users,
  Calendar,
  Clock,
  ArrowRight,
  Edit2,
  Trash2,
  Loader2,
  AlertTriangle
} from '@lucide/vue'

const authStore = useAuthStore()
const toast = useToast()

const projects = ref<Project[]>([])
const availableUsers = ref<User[]>([])
const loading = ref(false)
const saving = ref(false)

const pagination = ref<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 9,
  total: 0,
})

// Filtros
const search = ref('')
const selectedStatus = ref('')
const sortBy = ref('created_at')

// Modal de Criação / Edição
const showModal = ref(false)
const isEditing = ref(false)
const editingProjectId = ref<number | null>(null)
const formErrors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  code: '',
  description: '',
  status: 'PLANNING' as ProjectStatus,
  start_date: '',
  due_date: '',
  owner_id: '',
})

const canCreate = computed(() => authStore.isAdmin || authStore.isManager)

async function fetchProjects(page = 1) {
  loading.value = true
  try {
    const res = await projectService.getProjects({
      page,
      per_page: pagination.value.per_page,
      q: search.value || undefined,
      status: selectedStatus.value || undefined,
      sort_by: sortBy.value,
      direction: sortBy.value === 'name' ? 'asc' : 'desc',
    })

    if (res.success && res.data) {
      projects.value = res.data.items
      pagination.value = res.data.pagination
    }
  } catch {
    toast.error('Erro ao carregar lista de projetos.')
  } finally {
    loading.value = false
  }
}

async function fetchAvailableUsers() {
  if (!authStore.isAdmin) return
  try {
    const res = await userService.getUsers({ per_page: 100 })
    if (res.success && res.data) {
      availableUsers.value = res.data.items
    }
  } catch {
    // Silently continue
  }
}

function openCreateModal() {
  isEditing.value = false
  editingProjectId.value = null
  formErrors.value = {}
  form.value = {
    name: '',
    code: '',
    description: '',
    status: 'PLANNING',
    start_date: new Date().toISOString().split('T')[0],
    due_date: '',
    owner_id: authStore.user?.id ? String(authStore.user.id) : '',
  }
  showModal.value = true
}

function openEditModal(project: Project) {
  isEditing.value = true
  editingProjectId.value = project.id
  formErrors.value = {}
  form.value = {
    name: project.name,
    code: project.code,
    description: project.description || '',
    status: project.status,
    start_date: project.start_date || '',
    due_date: project.due_date || '',
    owner_id: String(project.owner_id),
  }
  showModal.value = true
}

// Sugere código a partir do nome se o código estiver vazio
function handleNameInput() {
  if (!isEditing.value && !form.value.code) {
    const words = form.value.name.trim().split(/\s+/)
    if (words.length > 0 && words[0]) {
      form.value.code = words.map(w => w.substring(0, 3).toUpperCase()).join('-').substring(0, 15)
    }
  }
}

async function handleSaveProject() {
  formErrors.value = {}
  saving.value = true

  try {
    const payload: Record<string, any> = {
      name: form.value.name,
      code: form.value.code,
      description: form.value.description || null,
      status: form.value.status,
      start_date: form.value.start_date || null,
      due_date: form.value.due_date || null,
    }

    if (authStore.isAdmin && form.value.owner_id) {
      payload.owner_id = Number(form.value.owner_id)
    }

    if (isEditing.value && editingProjectId.value) {
      await projectService.updateProject(editingProjectId.value, payload)
      toast.success('Projeto atualizado com sucesso!')
    } else {
      await projectService.createProject(payload)
      toast.success('Projeto criado com sucesso!')
    }

    showModal.value = false
    fetchProjects(pagination.value.current_page)
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message || 'Falha ao salvar projeto.')
    }
  } finally {
    saving.value = false
  }
}

async function handleDeleteProject(project: Project) {
  if (!confirm(`Tem certeza que deseja remover o projeto "${project.name}" (${project.code})? (Exclusão lógica/Soft delete)`)) {
    return
  }

  try {
    await projectService.deleteProject(project.id)
    toast.success('Projeto removido com sucesso.')
    fetchProjects(pagination.value.current_page)
  } catch {
    toast.error('Erro ao excluir projeto.')
  }
}

function getStatusBadgeClass(status: ProjectStatus) {
  switch (status) {
    case 'PLANNING':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'ACTIVE':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'ON_HOLD':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    case 'COMPLETED':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/20'
    case 'ARCHIVED':
      return 'bg-slate-800 text-slate-400 border-slate-700'
    default:
      return 'bg-slate-800 text-slate-400 border-slate-700'
  }
}

onMounted(() => {
  fetchProjects()
  fetchAvailableUsers()
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <FolderKanban class="w-6 h-6 text-emerald-400" />
            Projetos
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Acompanhe o status, cronogramas e membros de todos os projetos ativos.
          </p>
        </div>

        <button
          v-if="canCreate"
          @click="openCreateModal"
          class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition-colors shadow-lg shadow-emerald-500/10 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Novo Projeto</span>
        </button>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Search -->
        <div class="relative">
          <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            @input="fetchProjects(1)"
            type="text"
            placeholder="Buscar por nome ou código..."
            class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="selectedStatus"
            @change="fetchProjects(1)"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors"
          >
            <option value="">Todos os Status</option>
            <option value="PLANNING">Planejamento</option>
            <option value="ACTIVE">Ativo</option>
            <option value="ON_HOLD">Em Espera</option>
            <option value="COMPLETED">Concluído</option>
            <option value="ARCHIVED">Arquivado</option>
          </select>
        </div>

        <!-- Sort Filter -->
        <div>
          <select
            v-model="sortBy"
            @change="fetchProjects(1)"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors"
          >
            <option value="created_at">Mais Recentes</option>
            <option value="name">Nome (A - Z)</option>
            <option value="due_date">Prazo de Entrega</option>
          </select>
        </div>
      </div>

      <!-- Projects Grid -->
      <div v-if="loading" class="p-16 text-center text-slate-400 bg-slate-900/50 rounded-2xl border border-slate-800">
        <Loader2 class="w-8 h-8 animate-spin mx-auto text-emerald-400 mb-2" />
        <p class="text-xs">Carregando projetos...</p>
      </div>

      <div v-else-if="projects.length === 0" class="p-16 text-center text-slate-500 bg-slate-900/50 rounded-2xl border border-slate-800">
        <AlertTriangle class="w-8 h-8 mx-auto text-slate-600 mb-2" />
        <p class="text-sm font-medium">Nenhum projeto encontrado</p>
        <p class="text-xs text-slate-600">Ajuste os filtros ou crie um novo projeto.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="project in projects"
          :key="project.id"
          class="rounded-2xl bg-slate-900 border border-slate-800/80 p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg hover:shadow-emerald-500/5 group"
        >
          <div class="space-y-3">
            <!-- Top row: Code & Status -->
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 rounded-lg bg-slate-950 font-mono text-[11px] font-bold text-emerald-400 border border-slate-800">
                {{ project.code }}
              </span>
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[10px] font-semibold border',
                  getStatusBadgeClass(project.status)
                ]"
              >
                {{ project.status_label }}
              </span>
            </div>

            <!-- Project Title & Description -->
            <div>
              <RouterLink
                :to="`/projects/${project.id}`"
                class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors line-clamp-1"
              >
                {{ project.name }}
              </RouterLink>
              <p class="text-xs text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                {{ project.description || 'Sem descrição cadastrada.' }}
              </p>
            </div>

            <!-- Dates Information -->
            <div class="pt-2 border-t border-slate-800/60 grid grid-cols-2 gap-2 text-[11px] text-slate-400">
              <div class="flex items-center gap-1.5 truncate">
                <Calendar class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                <span>Início: {{ project.start_date || 'N/D' }}</span>
              </div>
              <div class="flex items-center gap-1.5 truncate">
                <Clock class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                <span>Prazo: {{ project.due_date || 'N/D' }}</span>
              </div>
            </div>
          </div>

          <!-- Bottom: Owner & Team & Actions -->
          <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
            <!-- Owner info -->
            <div class="flex items-center space-x-2 truncate">
              <div class="w-6 h-6 rounded-full bg-slate-800 text-emerald-400 border border-slate-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                {{ project.owner?.name ? project.owner.name.charAt(0).toUpperCase() : 'P' }}
              </div>
              <span class="text-slate-300 font-medium truncate text-[11px]">
                {{ project.owner?.name }}
              </span>
            </div>

            <!-- Team count & Action Links -->
            <div class="flex items-center space-x-2 shrink-0">
              <span class="flex items-center gap-1 text-[11px] text-slate-400 bg-slate-950 px-2 py-0.5 rounded-md border border-slate-800">
                <Users class="w-3 h-3 text-emerald-400" />
                {{ project.members_count || 1 }}
              </span>

              <!-- Edit button if allowed -->
              <button
                v-if="project.can_manage"
                @click="openEditModal(project)"
                class="p-1 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-slate-800 transition-colors"
                title="Editar Projeto"
              >
                <Edit2 class="w-3.5 h-3.5" />
              </button>

              <!-- Delete button if allowed -->
              <button
                v-if="project.can_manage"
                @click="handleDeleteProject(project)"
                class="p-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors"
                title="Excluir Projeto"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>

              <RouterLink
                :to="`/projects/${project.id}`"
                class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                title="Ver Detalhes"
              >
                <ArrowRight class="w-3.5 h-3.5" />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination :meta="pagination" @change-page="fetchProjects" />

      <!-- Create / Edit Project Modal -->
      <Modal
        :show="showModal"
        :title="isEditing ? 'Editar Projeto' : 'Novo Projeto'"
        @close="showModal = false"
      >
        <form @submit.prevent="handleSaveProject" class="space-y-4 text-xs">
          <!-- Nome -->
          <div>
            <label class="block font-medium text-slate-300 mb-1">Nome do Projeto</label>
            <input
              v-model="form.name"
              @input="handleNameInput"
              type="text"
              required
              placeholder="Ex: Refatoração da API de Pagamentos"
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
            />
            <p v-if="formErrors.name" class="text-rose-400 mt-1">{{ formErrors.name[0] }}</p>
          </div>

          <!-- Código Único & Status -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1">Código Único (Identificador)</label>
              <input
                v-model="form.code"
                type="text"
                required
                placeholder="Ex: PAY-API"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase focus:outline-none focus:border-emerald-500"
              />
              <p v-if="formErrors.code" class="text-rose-400 mt-1">{{ formErrors.code[0] }}</p>
            </div>

            <div>
              <label class="block font-medium text-slate-300 mb-1">Status</label>
              <select
                v-model="form.status"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="PLANNING">Planejamento</option>
                <option value="ACTIVE">Ativo</option>
                <option value="ON_HOLD">Em Espera</option>
                <option value="COMPLETED">Concluído</option>
                <option value="ARCHIVED">Arquivado</option>
              </select>
            </div>
          </div>

          <!-- Descrição -->
          <div>
            <label class="block font-medium text-slate-300 mb-1">Descrição do Projeto</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Objetivos, escopo e entregáveis principais..."
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500 resize-none"
            ></textarea>
          </div>

          <!-- Datas -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1">Data Inicial</label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block font-medium text-slate-300 mb-1">Previsão de Conclusão</label>
              <input
                v-model="form.due_date"
                type="date"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
              <p v-if="formErrors.due_date" class="text-rose-400 mt-1">{{ formErrors.due_date[0] }}</p>
            </div>
          </div>

          <!-- Responsável (Apenas para Admin) -->
          <div v-if="authStore.isAdmin && availableUsers.length > 0">
            <label class="block font-medium text-slate-300 mb-1">Responsável / Proprietário</label>
            <select
              v-model="form.owner_id"
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
            >
              <option v-for="u in availableUsers" :key="u.id" :value="String(u.id)">
                {{ u.name }} ({{ u.email }})
              </option>
            </select>
          </div>
        </form>

        <template #footer>
          <button
            type="button"
            @click="showModal = false"
            class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-medium transition-colors"
          >
            Cancelar
          </button>
          <button
            type="button"
            :disabled="saving"
            @click="handleSaveProject"
            class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold text-xs transition-colors flex items-center space-x-1.5"
          >
            <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
            <span>{{ isEditing ? 'Salvar Alterações' : 'Criar Projeto' }}</span>
          </button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>
