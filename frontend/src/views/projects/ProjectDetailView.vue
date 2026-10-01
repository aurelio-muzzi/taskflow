<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import AppLayout from '../../layouts/AppLayout.vue'
import Modal from '../../components/common/Modal.vue'
import { projectService } from '../../services/projectService'
import { taskService } from '../../services/taskService'
import { userService } from '../../services/userService'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import type { Project, ProjectMember, ProjectRole } from '../../types/project'
import type { Task, TaskStatus } from '../../types/task'
import type { User } from '../../types/auth'
import {
  ArrowLeft,
  Calendar,
  Clock,
  UserCheck,
  UserPlus,
  Shield,
  Trash2,
  Loader2,
  CheckSquare,
  Plus
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const projectId = Number(route.params.id)
const project = ref<Project | null>(null)
const members = ref<ProjectMember[]>([])
const allUsers = ref<User[]>([])
const tasks = ref<Task[]>([])
const loading = ref(true)

// Add Member Modal State
const showAddMemberModal = ref(false)
const addingMember = ref(false)
const memberForm = ref({
  user_id: '',
  role: 'MEMBER' as ProjectRole,
})
const memberErrors = ref<Record<string, string[]>>({})

const canManage = computed(() => {
  if (authStore.isAdmin) return true
  return project.value?.can_manage || false
})

// Filtra usuários que já NÃO estão na equipe do projeto
const availableUsersToAdd = computed(() => {
  const memberUserIds = new Set(members.value.map(m => m.user_id))
  return allUsers.value.filter(u => !memberUserIds.has(u.id))
})

async function fetchProjectData() {
  loading.value = true
  try {
    const [projRes, membersRes, tasksRes] = await Promise.all([
      projectService.getProject(projectId),
      projectService.getMembers(projectId),
      taskService.getProjectTasks(projectId, { all: true }),
    ])

    if (projRes.success && projRes.data) {
      project.value = projRes.data
    }
    if (membersRes.success && membersRes.data) {
      members.value = membersRes.data
    }
    if (tasksRes.success && tasksRes.data) {
      tasks.value = Array.isArray(tasksRes.data) ? tasksRes.data : tasksRes.data.items
    }
  } catch {
    toast.error('Erro ao carregar detalhes do projeto.')
    router.push('/projects')
  } finally {
    loading.value = false
  }
}

async function quickChangeTaskStatus(task: Task, newStatus: TaskStatus) {
  try {
    await taskService.updateTaskStatus(task.id, newStatus)
    task.status = newStatus
    toast.success('Status da tarefa atualizado com sucesso!')
  } catch {
    toast.error('Erro ao atualizar status da tarefa.')
  }
}

function getTaskStatusBadge(status: TaskStatus) {
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

function getTaskPriorityBadge(priority: string) {
  switch (priority) {
    case 'low': return 'text-slate-400 bg-slate-800'
    case 'medium': return 'text-amber-400 bg-amber-950/60'
    case 'high': return 'text-orange-400 bg-orange-950/60'
    case 'urgent': return 'text-rose-400 bg-rose-950/60 font-semibold'
    default: return 'text-slate-400 bg-slate-800'
  }
}

async function fetchAllUsers() {
  try {
    const res = await userService.getUsers({ per_page: 100 })
    if (res.success && res.data) {
      allUsers.value = res.data.items
    }
  } catch {
    // Silently continue
  }
}

function openAddMemberModal() {
  memberErrors.value = {}
  memberForm.value = {
    user_id: availableUsersToAdd.value[0]?.id ? String(availableUsersToAdd.value[0].id) : '',
    role: 'MEMBER',
  }
  showAddMemberModal.value = true
}

async function handleAddMember() {
  if (!memberForm.value.user_id) {
    toast.warning('Selecione um usuário para adicionar.')
    return
  }

  addingMember.value = true
  memberErrors.value = {}

  try {
    await projectService.addMember(projectId, {
      user_id: Number(memberForm.value.user_id),
      role: memberForm.value.role,
    })

    toast.success('Membro adicionado à equipe com sucesso!')
    showAddMemberModal.value = false

    // Recarrega lista de membros
    const res = await projectService.getMembers(projectId)
    if (res.success && res.data) {
      members.value = res.data
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      memberErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message || 'Falha ao adicionar membro.')
    }
  } finally {
    addingMember.value = false
  }
}

async function handleUpdateMemberRole(member: ProjectMember, newRole: ProjectRole) {
  try {
    await projectService.updateMemberRole(projectId, member.user_id, newRole)
    member.role = newRole
    toast.success('Função do membro atualizada com sucesso.')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Falha ao atualizar papel do membro.')
  }
}

async function handleRemoveMember(member: ProjectMember) {
  if (!confirm(`Deseja remover ${member.user?.name} da equipe deste projeto?`)) {
    return
  }

  try {
    await projectService.removeMember(projectId, member.user_id)
    toast.success('Membro removido da equipe.')
    members.value = members.value.filter(m => m.user_id !== member.user_id)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Erro ao remover membro.')
  }
}

function getProjectRoleBadge(role: ProjectRole) {
  switch (role) {
    case 'OWNER':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'MANAGER':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'MEMBER':
      return 'bg-teal-500/10 text-teal-400 border-teal-500/20'
    case 'VIEWER':
      return 'bg-slate-800 text-slate-400 border-slate-700'
  }
}

onMounted(() => {
  fetchProjectData()
  fetchAllUsers()
})
</script>

<template>
  <AppLayout>
    <div v-if="loading" class="p-16 text-center text-slate-400">
      <Loader2 class="w-8 h-8 animate-spin mx-auto text-emerald-400 mb-2" />
      <p class="text-xs">Carregando dados do projeto...</p>
    </div>

    <div v-else-if="project" class="space-y-8">
      <!-- Breadcrumb / Back button -->
      <div>
        <RouterLink
          to="/projects"
          class="inline-flex items-center space-x-1.5 text-xs text-slate-400 hover:text-emerald-400 transition-colors"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Voltar para Projetos</span>
        </RouterLink>
      </div>

      <!-- Project Hero Card -->
      <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 relative z-10">
          <div class="space-y-3 max-w-2xl">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-lg bg-slate-950 font-mono text-xs font-bold text-emerald-400 border border-slate-800">
                {{ project.code }}
              </span>
              <span class="px-3 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {{ project.status_label }}
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              {{ project.name }}
            </h1>

            <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
              {{ project.description || 'Nenhuma descrição fornecida para este projeto.' }}
            </p>
          </div>

          <!-- Metadata Pill Badges -->
          <div class="flex flex-col sm:items-end gap-2 text-xs text-slate-400 shrink-0">
            <div class="flex items-center gap-1.5 bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800">
              <UserCheck class="w-4 h-4 text-emerald-400" />
              <span>Responsável: <strong class="text-slate-200">{{ project.owner?.name }}</strong></span>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800">
                <Calendar class="w-3.5 h-3.5 text-slate-500" />
                <span>Início: <strong class="text-slate-200">{{ project.start_date || 'N/D' }}</strong></span>
              </div>
              <div class="flex items-center gap-1.5 bg-slate-950/80 px-3 py-1.5 rounded-xl border border-slate-800">
                <Clock class="w-3.5 h-3.5 text-slate-500" />
                <span>Prazo: <strong class="text-slate-200">{{ project.due_date || 'N/D' }}</strong></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Members Section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
              <Shield class="w-5 h-5 text-emerald-400" />
              <span>Equipe do Projeto</span>
              <span class="text-xs font-mono font-medium text-slate-400">({{ members.length }})</span>
            </h2>
            <p class="text-xs text-slate-400">Membros atribuídos e seus níveis de permissão dentro deste projeto.</p>
          </div>

          <button
            v-if="canManage"
            @click="openAddMemberModal"
            class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition-colors shadow-lg shadow-emerald-500/10 cursor-pointer"
          >
            <UserPlus class="w-4 h-4" />
            <span>Adicionar Membro</span>
          </button>
        </div>

        <!-- Members Table -->
        <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-800 bg-slate-950/40 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <th class="py-3 px-4">Membro</th>
                <th class="py-3 px-4">Perfil Global</th>
                <th class="py-3 px-4">Função no Projeto</th>
                <th class="py-3 px-4">Entrou em</th>
                <th v-if="canManage" class="py-3 px-4 text-right">Gerenciar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr v-for="member in members" :key="member.id" class="hover:bg-slate-800/30 transition-colors">
                <!-- User name & email -->
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
                      {{ member.user?.name ? member.user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div>
                      <p class="font-medium text-white">{{ member.user?.name }}</p>
                      <p class="text-slate-400 text-[11px]">{{ member.user?.email }}</p>
                    </div>
                  </div>
                </td>

                <!-- Global Role -->
                <td class="py-3 px-4">
                  <span class="text-slate-400 font-medium">
                    {{ member.user?.role?.name || 'Usuário' }}
                  </span>
                </td>

                <!-- Project Role with Selector for Managers -->
                <td class="py-3 px-4">
                  <div v-if="canManage && member.role !== 'OWNER'" class="inline-block">
                    <select
                      :value="member.role"
                      @change="(e) => handleUpdateMemberRole(member, (e.target as HTMLSelectElement).value as ProjectRole)"
                      class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[11px] font-semibold text-emerald-400 focus:outline-none focus:border-emerald-500 cursor-pointer"
                    >
                      <option value="MANAGER">Gerente</option>
                      <option value="MEMBER">Membro</option>
                      <option value="VIEWER">Visualizador</option>
                    </select>
                  </div>
                  <span
                    v-else
                    :class="[
                      'px-2.5 py-1 rounded-full text-[11px] font-semibold border inline-block',
                      getProjectRoleBadge(member.role)
                    ]"
                  >
                    {{ member.role_label }}
                  </span>
                </td>

                <!-- Joined At -->
                <td class="py-3 px-4 text-slate-400 text-[11px]">
                  {{ member.joined_at ? new Date(member.joined_at).toLocaleDateString('pt-BR') : '-' }}
                </td>

                <!-- Actions -->
                <td v-if="canManage" class="py-3 px-4 text-right">
                  <button
                    v-if="member.role !== 'OWNER'"
                    @click="handleRemoveMember(member)"
                    title="Remover da equipe"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                  <span v-else class="text-[10px] text-slate-500 italic">Responsável</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tasks of this Project (Stage 4) -->
      <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden space-y-0">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800">
          <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
              <CheckSquare class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-white">Tarefas do Projeto</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-300">
                  {{ tasks.length }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">
                Demandas ativas, estimativas e acompanhamento de status da equipe.
              </p>
            </div>
          </div>

          <RouterLink
            :to="`/tasks?project_id=${projectId}`"
            class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition-colors shadow-sm"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Gerenciar Tarefas</span>
          </RouterLink>
        </div>

        <div v-if="tasks.length === 0" class="p-8 text-center text-slate-500 text-xs">
          Nenhuma tarefa cadastrada para este projeto até o momento.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider bg-slate-950/40">
                <th class="py-3 px-5">Tarefa</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Prioridade</th>
                <th class="py-3 px-4">Responsável</th>
                <th class="py-3 px-4">Prazo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr
                v-for="task in tasks"
                :key="task.id"
                class="hover:bg-slate-800/30 transition-colors"
              >
                <td class="py-3.5 px-5">
                  <span class="font-medium text-white block">{{ task.title }}</span>
                  <span v-if="task.description" class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ task.description }}</span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <select
                    :value="task.status"
                    @change="quickChangeTaskStatus(task, ($event.target as HTMLSelectElement).value as TaskStatus)"
                    :class="[
                      'text-[11px] font-medium px-2.5 py-1 rounded-full border cursor-pointer focus:outline-none transition-colors',
                      getTaskStatusBadge(task.status)
                    ]"
                  >
                    <option value="todo">A Fazer</option>
                    <option value="in_progress">Em Progresso</option>
                    <option value="review">Em Revisão</option>
                    <option value="done">Concluída</option>
                  </select>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span :class="['px-2 py-0.5 rounded text-[11px] font-medium', getTaskPriorityBadge(task.priority)]">
                    {{ task.priority_label }}
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap text-slate-300">
                  <div v-if="task.assignee" class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-slate-800 flex items-center justify-center text-[10px] text-slate-300 font-bold">
                      {{ task.assignee.name.charAt(0) }}
                    </div>
                    <span>{{ task.assignee.name }}</span>
                  </div>
                  <span v-else class="text-slate-500 italic text-[11px]">Não atribuído</span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap text-slate-400 text-[11px]">
                  {{ task.due_date ? new Date(task.due_date).toLocaleDateString('pt-BR') : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add Member Modal -->
      <Modal
        :show="showAddMemberModal"
        title="Adicionar Membro à Equipe"
        @close="showAddMemberModal = false"
      >
        <form @submit.prevent="handleAddMember" class="space-y-4 text-xs">
          <div v-if="availableUsersToAdd.length === 0" class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300">
            Todos os usuários disponíveis já fazem parte da equipe deste projeto.
          </div>

          <template v-else>
            <!-- Select User -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Selecionar Usuário</label>
              <select
                v-model="memberForm.user_id"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              >
                <option v-for="u in availableUsersToAdd" :key="u.id" :value="String(u.id)">
                  {{ u.name }} ({{ u.email }}) — {{ u.role?.name }}
                </option>
              </select>
              <p v-if="memberErrors.user_id" class="text-rose-400 mt-1">{{ memberErrors.user_id[0] }}</p>
            </div>

            <!-- Select Role -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Função no Projeto</label>
              <select
                v-model="memberForm.role"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="MANAGER">Gerente do Projeto (cria e gerencia tarefas e membros)</option>
                <option value="MEMBER">Membro (executa tarefas e comenta)</option>
                <option value="VIEWER">Visualizador (apenas leitura)</option>
              </select>
              <p v-if="memberErrors.role" class="text-rose-400 mt-1">{{ memberErrors.role[0] }}</p>
            </div>
          </template>
        </form>

        <template #footer>
          <button
            type="button"
            @click="showAddMemberModal = false"
            class="px-4 py-2 rounded-xl border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-medium transition-colors"
          >
            Cancelar
          </button>
          <button
            v-if="availableUsersToAdd.length > 0"
            type="button"
            :disabled="addingMember"
            @click="handleAddMember"
            class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold text-xs transition-colors flex items-center space-x-1.5"
          >
            <Loader2 v-if="addingMember" class="w-3.5 h-3.5 animate-spin" />
            <span>Adicionar à Equipe</span>
          </button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>
