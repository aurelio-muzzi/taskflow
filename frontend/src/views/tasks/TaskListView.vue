<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AppLayout from '../../layouts/AppLayout.vue'
import Modal from '../../components/common/Modal.vue'
import Pagination from '../../components/common/Pagination.vue'
import KanbanBoard from '../../components/kanban/KanbanBoard.vue'
import { taskService } from '../../services/taskService'
import { projectService } from '../../services/projectService'
import { userService } from '../../services/userService'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import { commentService } from '../../services/commentService'
import type { Task, TaskPriority, TaskStatus, CreateTaskPayload, UpdateTaskPayload } from '../../types/task'
import type { TaskComment } from '../../types/comment'
import type { Project } from '../../types/project'
import type { User } from '../../types/auth'
import type { PaginationMeta } from '../../types/api'
import {
  Search,
  Plus,
  CheckSquare,
  Calendar,
  Folder,
  Edit2,
  Trash2,
  Loader2,
  AlertTriangle,
  MessageSquare,
  Send,
  Kanban,
  List
} from '@lucide/vue'

const route = useRoute()
const authStore = useAuthStore()
const toast = useToast()

// Modal de Comentários
const isCommentsModalOpen = ref(false)
const activeTaskForComments = ref<Task | null>(null)
const comments = ref<TaskComment[]>([])
const loadingComments = ref(false)
const newCommentText = ref('')
const submittingComment = ref(false)

async function openCommentsModal(task: Task) {
  activeTaskForComments.value = task
  isCommentsModalOpen.value = true
  loadingComments.value = true
  try {
    const res = await commentService.getComments(task.id)
    if (res.data) {
      comments.value = res.data
    }
  } catch {
    toast.error('Erro ao carregar comentários.')
  } finally {
    loadingComments.value = false
  }
}

async function submitComment() {
  if (!newCommentText.value.trim() || !activeTaskForComments.value) return
  submittingComment.value = true
  try {
    const res = await commentService.createComment(activeTaskForComments.value.id, newCommentText.value.trim())
    if (res.data) {
      comments.value.push(res.data)
      newCommentText.value = ''
      toast.success('Comentário publicado!')
    }
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Erro ao publicar comentário.'
    toast.error(msg)
  } finally {
    submittingComment.value = false
  }
}

async function removeComment(commentId: number) {
  try {
    await commentService.deleteComment(commentId)
    comments.value = comments.value.filter(c => c.id !== commentId)
    toast.success('Comentário removido.')
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Erro ao remover comentário.'
    toast.error(msg)
  }
}

const tasks = ref<Task[]>([])
const projects = ref<Project[]>([])
const users = ref<User[]>([])
const loading = ref(true)

const pagination = reactive<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 100,
  total: 0
})

const viewMode = ref<'kanban' | 'list'>('kanban')

function setViewMode(mode: 'kanban' | 'list') {
  viewMode.value = mode
  if (mode === 'kanban') {
    filters.status = ''
    pagination.per_page = 100
  } else {
    pagination.per_page = 15
  }
  fetchTasks(1)
}

const filters = reactive({
  q: '',
  project_id: route.query.project_id ? Number(route.query.project_id) : undefined as number | undefined,
  status: '',
  priority: '',
  assigned_to: undefined as number | undefined
})

const statusTabs = [
  { label: 'Todas', value: '' },
  { label: 'A Fazer', value: 'todo' },
  { label: 'Em Progresso', value: 'in_progress' },
  { label: 'Em Revisão', value: 'review' },
  { label: 'Concluídas', value: 'done' }
]

// Modal Criar/Editar
const isModalOpen = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const editingTaskId = ref<number | null>(null)

const taskForm = reactive({
  project_id: null as number | null,
  title: '',
  description: '',
  status: 'todo' as TaskStatus,
  priority: 'medium' as TaskPriority,
  assigned_to: null as number | null,
  due_date: '',
  estimated_hours: null as number | null
})

// Modal Exclusão
const isDeleteModalOpen = ref(false)
const taskToDelete = ref<Task | null>(null)
const deleting = ref(false)

async function fetchTasks(page = 1) {
  loading.value = true
  try {
    const res = await taskService.getTasks({
      page,
      per_page: pagination.per_page,
      q: filters.q || undefined,
      project_id: filters.project_id || undefined,
      status: filters.status || undefined,
      priority: filters.priority || undefined,
      assigned_to: filters.assigned_to || undefined
    })

    if (res.data) {
      tasks.value = res.data.items
      pagination.current_page = res.data.pagination.current_page
      pagination.last_page = res.data.pagination.last_page
      pagination.total = res.data.pagination.total
    }
  } catch {
    toast.error('Erro ao carregar lista de tarefas.')
  } finally {
    loading.value = false
  }
}

async function fetchInitialData() {
  try {
    const [projRes, userRes] = await Promise.all([
      projectService.getProjects({ per_page: 100 }),
      userService.getUsers({ per_page: 100 })
    ])
    if (projRes.data) projects.value = projRes.data.items
    if (userRes.data) users.value = userRes.data.items
  } catch {
    console.error('Erro ao carregar projetos/usuários de apoio.')
  }
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function handleSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchTasks(1)
  }, 350)
}

function handleStatusFilter(status: string) {
  filters.status = status
  fetchTasks(1)
}

function handlePageChange(newPage: number) {
  fetchTasks(newPage)
}

function openCreateModal() {
  isEditing.value = false
  editingTaskId.value = null
  taskForm.project_id = filters.project_id || (projects.value.length > 0 ? projects.value[0].id : null)
  taskForm.title = ''
  taskForm.description = ''
  taskForm.status = 'todo'
  taskForm.priority = 'medium'
  taskForm.assigned_to = null
  taskForm.due_date = ''
  taskForm.estimated_hours = null
  isModalOpen.value = true
}

async function handleKanbanQuickCreate(title: string, status: TaskStatus) {
  const targetProjectId = filters.project_id || (projects.value.length > 0 ? projects.value[0].id : null)
  if (!targetProjectId) {
    toast.warning('Selecione um projeto nos filtros acima para adicionar uma tarefa diretamente no quadro.')
    return
  }

  try {
    const res = await taskService.createTask(targetProjectId, {
      title,
      status,
      priority: 'medium'
    })
    if (res.data) {
      toast.success('Tarefa criada no quadro!')
      fetchTasks(1)
    }
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Erro ao criar tarefa.'
    toast.error(msg)
  }
}

function openEditModal(task: Task) {
  isEditing.value = true
  editingTaskId.value = task.id
  taskForm.project_id = task.project_id
  taskForm.title = task.title
  taskForm.description = task.description || ''
  taskForm.status = task.status
  taskForm.priority = task.priority
  taskForm.assigned_to = task.assigned_to
  taskForm.due_date = task.due_date || ''
  taskForm.estimated_hours = task.estimated_hours
  isModalOpen.value = true
}

async function saveTask() {
  if (!taskForm.title.trim()) {
    toast.warning('O título da tarefa é obrigatório.')
    return
  }

  if (!isEditing.value && !taskForm.project_id) {
    toast.warning('Selecione um projeto para a tarefa.')
    return
  }

  saving.value = true
  try {
    if (isEditing.value && editingTaskId.value) {
      const payload: UpdateTaskPayload = {
        title: taskForm.title.trim(),
        description: taskForm.description ? taskForm.description.trim() : null,
        status: taskForm.status,
        priority: taskForm.priority,
        assigned_to: taskForm.assigned_to,
        due_date: taskForm.due_date || null,
        estimated_hours: taskForm.estimated_hours ? Number(taskForm.estimated_hours) : null
      }
      await taskService.updateTask(editingTaskId.value, payload)
      toast.success('Tarefa atualizada com sucesso!')
    } else if (taskForm.project_id) {
      const payload: CreateTaskPayload = {
        title: taskForm.title.trim(),
        description: taskForm.description ? taskForm.description.trim() : null,
        status: taskForm.status,
        priority: taskForm.priority,
        assigned_to: taskForm.assigned_to,
        due_date: taskForm.due_date || null,
        estimated_hours: taskForm.estimated_hours ? Number(taskForm.estimated_hours) : null
      }
      await taskService.createTask(taskForm.project_id, payload)
      toast.success('Tarefa criada com sucesso!')
    }

    isModalOpen.value = false
    fetchTasks(pagination.current_page)
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Falha ao salvar tarefa.'
    toast.error(msg)
  } finally {
    saving.value = false
  }
}

async function quickChangeStatus(task: Task, newStatus: TaskStatus) {
  try {
    await taskService.updateTaskStatus(task.id, newStatus)
    task.status = newStatus
    toast.success(`Status alterado para "${getStatusLabel(newStatus)}"`)
  } catch {
    toast.error('Erro ao atualizar status da tarefa.')
  }
}

function openDeleteModal(task: Task) {
  taskToDelete.value = task
  isDeleteModalOpen.value = true
}

async function confirmDelete() {
  if (!taskToDelete.value) return
  deleting.value = true
  try {
    await taskService.deleteTask(taskToDelete.value.id)
    toast.success('Tarefa excluída com sucesso!')
    isDeleteModalOpen.value = false
    fetchTasks(pagination.current_page)
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Erro ao excluir tarefa.'
    toast.error(msg)
  } finally {
    deleting.value = false
  }
}

function getStatusBadge(status: TaskStatus) {
  switch (status) {
    case 'todo':
      return 'bg-slate-800 text-slate-300 border-slate-700'
    case 'in_progress':
      return 'bg-blue-950 text-blue-400 border-blue-800'
    case 'review':
      return 'bg-purple-950 text-purple-400 border-purple-800'
    case 'done':
      return 'bg-emerald-950 text-emerald-400 border-emerald-800'
  }
}

function getStatusLabel(status: TaskStatus) {
  switch (status) {
    case 'todo': return 'A Fazer'
    case 'in_progress': return 'Em Progresso'
    case 'review': return 'Em Revisão'
    case 'done': return 'Concluída'
  }
}

function getPriorityBadge(priority: TaskPriority) {
  switch (priority) {
    case 'low':
      return 'text-slate-400 bg-slate-800'
    case 'medium':
      return 'text-amber-400 bg-amber-950/60'
    case 'high':
      return 'text-orange-400 bg-orange-950/60'
    case 'urgent':
      return 'text-rose-400 bg-rose-950/60 font-semibold'
  }
}

function getPriorityLabel(priority: TaskPriority) {
  switch (priority) {
    case 'low': return 'Baixa'
    case 'medium': return 'Média'
    case 'high': return 'Alta'
    case 'urgent': return 'Urgente'
  }
}

onMounted(() => {
  fetchInitialData()
  fetchTasks()
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6 w-full min-w-0">
      <!-- Header com Título e Ação -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <CheckSquare class="w-6 h-6 text-emerald-400" />
            <span>Gestão de Tarefas</span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Acompanhe demandas, prioridades, responsáveis e prazos de entrega em todos os projetos.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Toggle View Mode -->
          <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800">
            <button
              type="button"
              @click="setViewMode('kanban')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                viewMode === 'kanban'
                  ? 'bg-slate-800 text-white shadow-sm'
                  : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <Kanban class="w-3.5 h-3.5 text-emerald-400" />
              <span>Quadro</span>
            </button>
            <button
              type="button"
              @click="setViewMode('list')"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                viewMode === 'list'
                  ? 'bg-slate-800 text-white shadow-sm'
                  : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <List class="w-3.5 h-3.5 text-emerald-400" />
              <span>Lista</span>
            </button>
          </div>

          <button
            @click="openCreateModal"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs rounded-xl shadow-lg shadow-emerald-500/10 transition-all duration-150 cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Nova Tarefa</span>
          </button>
        </div>
      </div>

      <!-- Barra de Filtros e Busca -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-sm space-y-4">
        <!-- Status Tabs (Apenas na Visão Lista) -->
        <div v-if="viewMode === 'list'" class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-800">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            @click="handleStatusFilter(tab.value)"
            :class="[
              'px-3.5 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors duration-150',
              filters.status === tab.value
                ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30'
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Filtros secundários: Busca, Projeto, Prioridade, Responsável -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Busca Textual -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
            <input
              v-model="filters.q"
              @input="handleSearchInput"
              type="text"
              placeholder="Buscar por título ou descrição..."
              class="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Filtro por Projeto -->
          <div>
            <select
              v-model="filters.project_id"
              @change="fetchTasks(1)"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option :value="undefined">Todos os Projetos</option>
              <option v-for="proj in projects" :key="proj.id" :value="proj.id">
                {{ proj.code }} — {{ proj.name }}
              </option>
            </select>
          </div>

          <!-- Filtro por Prioridade -->
          <div>
            <select
              v-model="filters.priority"
              @change="fetchTasks(1)"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option value="">Todas as Prioridades</option>
              <option value="low">Baixa</option>
              <option value="medium">Média</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>

          <!-- Filtro por Responsável -->
          <div>
            <select
              v-model="filters.assigned_to"
              @change="fetchTasks(1)"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option :value="undefined">Todos os Responsáveis</option>
              <option v-for="u in users" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Visualização em Quadro Kanban -->
      <div v-if="viewMode === 'kanban'" class="pt-1 w-full min-w-0">
        <KanbanBoard
          :tasks="tasks"
          :project-id="filters.project_id"
          :can-edit="true"
          :loading="loading"
          :show-project-badge="!filters.project_id"
          @task-click="openEditModal"
          @comments-click="openCommentsModal"
          @quick-create="handleKanbanQuickCreate"
          @refresh="fetchTasks(1)"
        />
      </div>

      <!-- Tabela / Lista de Tarefas (Visão Lista) -->
      <div v-else class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div v-if="loading" class="flex flex-col items-center justify-center p-12 text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin mb-3 text-emerald-400" />
          <span class="text-xs">Carregando tarefas...</span>
        </div>

        <div v-else-if="tasks.length === 0" class="flex flex-col items-center justify-center p-12 text-center">
          <CheckSquare class="w-12 h-12 text-slate-700 mb-3" />
          <h3 class="text-sm font-semibold text-white">Nenhuma tarefa encontrada</h3>
          <p class="text-xs text-slate-400 mt-1 max-w-sm">
            Ajuste os filtros de pesquisa ou crie uma nova tarefa para começar a organizar as demandas.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-950/60 border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <th class="px-5 py-3.5">Tarefa</th>
                <th class="px-4 py-3.5">Projeto</th>
                <th class="px-4 py-3.5">Status</th>
                <th class="px-4 py-3.5">Prioridade</th>
                <th class="px-4 py-3.5">Responsável</th>
                <th class="px-4 py-3.5">Prazo</th>
                <th class="px-5 py-3.5 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr
                v-for="task in tasks"
                :key="task.id"
                class="hover:bg-slate-800/30 transition-colors"
              >
                <!-- Título & Descrição -->
                <td class="px-5 py-3.5 max-w-xs">
                  <div class="font-medium text-white line-clamp-1">
                    {{ task.title }}
                  </div>
                  <div v-if="task.description" class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">
                    {{ task.description }}
                  </div>
                </td>

                <!-- Projeto -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <RouterLink
                    v-if="task.project"
                    :to="`/projects/${task.project.id}`"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-950 hover:bg-slate-800 text-[11px] font-mono text-emerald-400 border border-slate-800 rounded-lg transition-colors"
                  >
                    <Folder class="w-3 h-3 text-slate-500" />
                    {{ task.project.code }}
                  </RouterLink>
                </td>

                <!-- Status com Dropdown rápido -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <select
                    :value="task.status"
                    @change="quickChangeStatus(task, ($event.target as HTMLSelectElement).value as TaskStatus)"
                    :class="[
                      'text-[11px] font-medium px-2.5 py-1 rounded-full border cursor-pointer focus:outline-none transition-colors',
                      getStatusBadge(task.status)
                    ]"
                  >
                    <option value="todo">A Fazer</option>
                    <option value="in_progress">Em Progresso</option>
                    <option value="review">Em Revisão</option>
                    <option value="done">Concluída</option>
                  </select>
                </td>

                <!-- Prioridade -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span :class="['px-2 py-0.5 rounded text-[11px] font-medium', getPriorityBadge(task.priority)]">
                    {{ getPriorityLabel(task.priority) }}
                  </span>
                </td>

                <!-- Responsável -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <div v-if="task.assignee" class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-300">
                      {{ task.assignee.name.charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-slate-300">{{ task.assignee.name }}</span>
                  </div>
                  <span v-else class="text-slate-500 italic text-[11px]">Não atribuído</span>
                </td>

                <!-- Prazo -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <div v-if="task.due_date" class="flex items-center gap-1.5 text-slate-400">
                    <Calendar class="w-3.5 h-3.5 text-slate-500" />
                    <span>{{ new Date(task.due_date).toLocaleDateString('pt-BR') }}</span>
                  </div>
                  <span v-else class="text-slate-500">—</span>
                </td>

                <!-- Ações -->
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openCommentsModal(task)"
                      title="Comentários da Tarefa"
                      class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-lg transition-colors"
                    >
                      <MessageSquare class="w-4 h-4" />
                    </button>
                    <button
                      @click="openEditModal(task)"
                      title="Editar Tarefa"
                      class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      @click="openDeleteModal(task)"
                      title="Excluir Tarefa"
                      class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <Pagination :meta="pagination" @change-page="handlePageChange" />
      </div>

      <!-- Modal de Criação / Edição de Tarefa -->
      <Modal
        :show="isModalOpen"
        :title="isEditing ? 'Editar Tarefa' : 'Nova Tarefa'"
        max-width="xl"
        @close="isModalOpen = false"
      >
        <form @submit.prevent="saveTask" class="space-y-4">
          <!-- Projeto (caso seja criação) -->
          <div v-if="!isEditing">
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
              Projeto *
            </label>
            <select
              v-model="taskForm.project_id"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option :value="null" disabled>Selecione um projeto</option>
              <option v-for="proj in projects" :key="proj.id" :value="proj.id">
                {{ proj.code }} — {{ proj.name }}
              </option>
            </select>
          </div>

          <!-- Título -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
              Título da Tarefa *
            </label>
            <input
              v-model="taskForm.title"
              type="text"
              required
              placeholder="Ex: Desenvolver endpoint de listagem"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Descrição -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
              Descrição
            </label>
            <textarea
              v-model="taskForm.description"
              rows="3"
              placeholder="Detalhes ou critérios de aceitação da tarefa..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            ></textarea>
          </div>

          <!-- Linha: Status e Prioridade -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Status *
              </label>
              <select
                v-model="taskForm.status"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="todo">A Fazer</option>
                <option value="in_progress">Em Progresso</option>
                <option value="review">Em Revisão</option>
                <option value="done">Concluída</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Prioridade *
              </label>
              <select
                v-model="taskForm.priority"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="low">Baixa</option>
                <option value="medium">Média</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
          </div>

          <!-- Linha: Responsável e Prazo -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Responsável
              </label>
              <select
                v-model="taskForm.assigned_to"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
              >
                <option :value="null">Não atribuído</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Data de Entrega
              </label>
              <input
                v-model="taskForm.due_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <!-- Horas Estimadas -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
              Horas Estimadas
            </label>
            <input
              v-model="taskForm.estimated_hours"
              type="number"
              step="0.5"
              min="0"
              placeholder="Ex: 8.5"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="isModalOpen = false"
              class="px-4 py-2 border border-slate-800 text-xs font-medium text-slate-400 rounded-xl hover:text-white hover:bg-slate-800 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-semibold rounded-xl shadow-sm transition-colors"
            >
              <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
              {{ isEditing ? 'Salvar Alterações' : 'Criar Tarefa' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Modal de Confirmação de Exclusão -->
      <Modal
        :show="isDeleteModalOpen"
        title="Excluir Tarefa"
        max-width="md"
        @close="isDeleteModalOpen = false"
      >
        <div class="space-y-4">
          <div class="flex items-start space-x-3">
            <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 flex-shrink-0">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div>
              <p class="text-xs text-slate-300">
                Tem certeza de que deseja excluir a tarefa
                <strong class="text-white font-semibold">
                  "{{ taskToDelete?.title }}"
                </strong>?
              </p>
              <p class="text-[11px] text-slate-400 mt-1">
                Esta ação remove a tarefa da listagem ativa via Soft Delete.
              </p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="isDeleteModalOpen = false"
              class="px-4 py-2 border border-slate-800 text-xs font-medium text-slate-400 rounded-xl hover:text-white hover:bg-slate-800 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="button"
              @click="confirmDelete"
              :disabled="deleting"
              class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors"
            >
              <Loader2 v-if="deleting" class="w-3.5 h-3.5 animate-spin" />
              Excluir Tarefa
            </button>
          </div>
        </div>
      </Modal>

      <!-- Modal de Comentários da Tarefa -->
      <Modal
        :show="isCommentsModalOpen"
        :title="`Comentários: ${activeTaskForComments?.title || ''}`"
        max-width="lg"
        @close="isCommentsModalOpen = false"
      >
        <div class="space-y-4">
          <!-- Lista de Comentários -->
          <div class="max-h-80 overflow-y-auto space-y-3 pr-1">
            <div v-if="loadingComments" class="flex flex-col items-center justify-center p-8 text-slate-500">
              <Loader2 class="w-6 h-6 animate-spin text-emerald-400 mb-2" />
              <span class="text-xs">Carregando comentários...</span>
            </div>

            <div v-else-if="comments.length === 0" class="p-8 text-center text-xs text-slate-500">
              Nenhum comentário registrado ainda. Seja o primeiro a comentar!
            </div>

            <div
              v-else
              v-for="comm in comments"
              :key="comm.id"
              class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center font-bold text-[10px] text-slate-300">
                    {{ comm.user?.name ? comm.user.name.charAt(0).toUpperCase() : 'U' }}
                  </div>
                  <span class="font-semibold text-white">{{ comm.user?.name || 'Usuário' }}</span>
                  <span class="text-[10px] text-slate-500">
                    {{ new Date(comm.created_at).toLocaleString('pt-BR') }}
                  </span>
                </div>

                <button
                  v-if="comm.user_id === authStore.user?.id || authStore.isAdmin"
                  @click="removeComment(comm.id)"
                  title="Excluir Comentário"
                  class="text-slate-500 hover:text-rose-400 p-1 rounded transition-colors"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>

              <p class="text-slate-300 whitespace-pre-line pl-8">{{ comm.content }}</p>
            </div>
          </div>

          <!-- Formulário de Novo Comentário -->
          <form @submit.prevent="submitComment" class="space-y-3 pt-3 border-t border-slate-800">
            <div>
              <textarea
                v-model="newCommentText"
                rows="2"
                required
                placeholder="Escreva um comentário ou atualização sobre esta tarefa..."
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
              ></textarea>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-[11px] text-slate-500">Pressione Enviar para registrar.</span>
              <button
                type="submit"
                :disabled="submittingComment || !newCommentText.trim()"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold text-xs rounded-xl shadow-sm transition-colors"
              >
                <Loader2 v-if="submittingComment" class="w-3.5 h-3.5 animate-spin" />
                <Send v-else class="w-3.5 h-3.5" />
                <span>Comentar</span>
              </button>
            </div>
          </form>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>
