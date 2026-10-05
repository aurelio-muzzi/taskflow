<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '../../layouts/AppLayout.vue'
import Modal from '../../components/common/Modal.vue'
import Pagination from '../../components/common/Pagination.vue'
import { userService } from '../../services/userService'
import { roleService } from '../../services/roleService'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import type { User, Role } from '../../types/auth'
import type { PaginationMeta } from '../../types/api'
import {
  UserPlus,
  Search,
  Edit2,
  Trash2,
  Power,
  Shield,
  Loader2,
  AlertTriangle
} from '@lucide/vue'

const authStore = useAuthStore()
const toast = useToast()

const users = ref<User[]>([])
const roles = ref<Role[]>([])
const loading = ref(false)
const saving = ref(false)

const pagination = ref<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

// Filtros
const search = ref('')
const selectedRole = ref('')
const selectedStatus = ref('')

// Modal state
const showModal = ref(false)
const isEditing = ref(false)
const editingUserId = ref<number | null>(null)
const formErrors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  email: '',
  role_id: '',
  status: 'ACTIVE',
  password: '',
  password_confirmation: '',
})

async function fetchRoles() {
  try {
    const res = await roleService.getRoles()
    if (res.success) {
      roles.value = res.data
    }
  } catch (err) {
    console.error('Falha ao carregar perfis:', err)
  }
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const res = await userService.getUsers({
      page,
      per_page: pagination.value.per_page,
      q: search.value || undefined,
      role: selectedRole.value || undefined,
      status: selectedStatus.value || undefined,
    })

    if (res.success && res.data) {
      users.value = res.data.items
      pagination.value = res.data.pagination
    }
  } catch {
    toast.error('Erro ao carregar lista de usuários.')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  isEditing.value = false
  editingUserId.value = null
  formErrors.value = {}
  form.value = {
    name: '',
    email: '',
    role_id: roles.value[0]?.id ? String(roles.value[0].id) : '3',
    status: 'ACTIVE',
    password: '',
    password_confirmation: '',
  }
  showModal.value = true
}

function openEditModal(user: User) {
  isEditing.value = true
  editingUserId.value = user.id
  formErrors.value = {}
  form.value = {
    name: user.name,
    email: user.email,
    role_id: user.role?.id ? String(user.role.id) : '',
    status: user.status,
    password: '',
    password_confirmation: '',
  }
  showModal.value = true
}

async function handleSaveUser() {
  formErrors.value = {}
  saving.value = true

  try {
    const payload: Record<string, any> = {
      name: form.value.name,
      email: form.value.email,
      role_id: Number(form.value.role_id),
      status: form.value.status,
    }

    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }

    if (isEditing.value && editingUserId.value) {
      await userService.updateUser(editingUserId.value, payload)
      toast.success('Usuário atualizado com sucesso!')
    } else {
      await userService.createUser(payload)
      toast.success('Usuário cadastrado com sucesso!')
    }

    showModal.value = false
    fetchUsers(pagination.value.current_page)
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message || 'Falha ao salvar usuário.')
    }
  } finally {
    saving.value = false
  }
}

async function handleToggleStatus(user: User) {
  if (user.id === authStore.user?.id) {
    toast.warning('Você não pode desativar sua própria conta.')
    return
  }

  try {
    const res = await userService.toggleStatus(user.id)
    if (res.success && res.data) {
      user.status = res.data.status
      toast.success(`Status de ${user.name} alterado para ${user.status === 'ACTIVE' ? 'Ativo' : 'Inativo'}.`)
    }
  } catch {
    toast.error('Erro ao alterar status do usuário.')
  }
}

async function handleDeleteUser(user: User) {
  if (user.id === authStore.user?.id) {
    toast.warning('Você não pode remover sua própria conta.')
    return
  }

  const confirmed = await toast.confirm(
    `Tem certeza que deseja remover o usuário ${user.name}? (Exclusão lógica/Soft delete)`,
    {
      title: 'Confirmar Exclusão',
      confirmText: 'Sim, remover',
      cancelText: 'Cancelar',
    }
  )

  if (!confirmed) {
    return
  }

  try {
    await userService.deleteUser(user.id)
    toast.success('Usuário removido com sucesso.')
    fetchUsers(pagination.value.current_page)
  } catch {
    toast.error('Erro ao remover usuário.')
  }
}

function getRoleBadgeClass(slug?: string) {
  switch (slug) {
    case 'admin':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    case 'manager':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20'
    default:
      return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
  }
}

onMounted(() => {
  fetchRoles()
  fetchUsers()
})
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Shield class="w-6 h-6 text-emerald-400" />
            Gerenciamento de Usuários
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Controle de contas, perfis globais de acesso (RBAC) e status de ativação.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition-colors shadow-lg shadow-emerald-500/10"
        >
          <UserPlus class="w-4 h-4" />
          <span>Cadastrar Usuário</span>
        </button>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Search -->
        <div class="relative">
          <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            @input="fetchUsers(1)"
            type="text"
            placeholder="Buscar por nome ou e-mail..."
            class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
          />
        </div>

        <!-- Role Filter -->
        <div>
          <select
            v-model="selectedRole"
            @change="fetchUsers(1)"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors"
          >
            <option value="">Todos os Perfis</option>
            <option v-for="role in roles" :key="role.id" :value="role.slug">
              {{ role.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="selectedStatus"
            @change="fetchUsers(1)"
            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 transition-colors"
          >
            <option value="">Todos os Status</option>
            <option value="ACTIVE">Ativo</option>
            <option value="INACTIVE">Inativo</option>
          </select>
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin mx-auto text-emerald-400 mb-2" />
          <p class="text-xs">Carregando usuários...</p>
        </div>

        <div v-else-if="users.length === 0" class="p-12 text-center text-slate-500">
          <AlertTriangle class="w-8 h-8 mx-auto text-slate-600 mb-2" />
          <p class="text-sm font-medium">Nenhum usuário encontrado</p>
          <p class="text-xs text-slate-600">Ajuste os filtros de busca para visualizar resultados.</p>
        </div>

        <div v-else class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse min-w-[640px]">
            <thead>
            <tr class="border-b border-slate-800 bg-slate-950/40 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4">Usuário</th>
              <th class="py-3.5 px-4">Perfil (RBAC)</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4">Cadastrado em</th>
              <th class="py-3.5 px-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-xs">
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-800/30 transition-colors">
              <!-- Name & Email -->
              <td class="py-3.5 px-4">
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <p class="font-medium text-white">{{ user.name }}</p>
                    <p class="text-slate-400 text-[11px]">{{ user.email }}</p>
                  </div>
                </div>
              </td>

              <!-- Role Badge -->
              <td class="py-3.5 px-4">
                <span
                  :class="[
                    'px-2.5 py-1 rounded-full text-[11px] font-medium border inline-block',
                    getRoleBadgeClass(user.role?.slug)
                  ]"
                >
                  {{ user.role?.name || 'Sem perfil' }}
                </span>
              </td>

              <!-- Status Badge -->
              <td class="py-3.5 px-4">
                <span
                  :class="[
                    'px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold uppercase tracking-wider',
                    user.status === 'ACTIVE'
                      ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                      : 'bg-slate-800 text-slate-400 border border-slate-700'
                  ]"
                >
                  {{ user.status === 'ACTIVE' ? 'Ativo' : 'Inativo' }}
                </span>
              </td>

              <!-- Created At -->
              <td class="py-3.5 px-4 text-slate-400">
                {{ user.created_at ? new Date(user.created_at).toLocaleDateString('pt-BR') : '-' }}
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-right">
                <div class="inline-flex items-center space-x-1">
                  <!-- Toggle Status Button -->
                  <button
                    @click="handleToggleStatus(user)"
                    :disabled="user.id === authStore.user?.id"
                    :title="user.status === 'ACTIVE' ? 'Desativar usuário' : 'Ativar usuário'"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-slate-800 disabled:opacity-30 transition-colors"
                  >
                    <Power class="w-4 h-4" />
                  </button>

                  <!-- Edit Button -->
                  <button
                    @click="openEditModal(user)"
                    title="Editar usuário"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-slate-800 transition-colors"
                  >
                    <Edit2 class="w-4 h-4" />
                  </button>

                  <!-- Delete Button -->
                  <button
                    @click="handleDeleteUser(user)"
                    :disabled="user.id === authStore.user?.id"
                    title="Remover usuário"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 disabled:opacity-30 transition-colors"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <Pagination :meta="pagination" @change-page="fetchUsers" />
      </div>

      <!-- Create / Edit User Modal -->
      <Modal
        :show="showModal"
        :title="isEditing ? 'Editar Usuário' : 'Novo Usuário'"
        @close="showModal = false"
      >
        <form @submit.prevent="handleSaveUser" class="space-y-4 text-xs">
          <!-- Nome -->
          <div>
            <label class="block font-medium text-slate-300 mb-1">Nome Completo</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
            />
            <p v-if="formErrors.name" class="text-rose-400 mt-1">{{ formErrors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="block font-medium text-slate-300 mb-1">E-mail Profissional</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
            />
            <p v-if="formErrors.email" class="text-rose-400 mt-1">{{ formErrors.email[0] }}</p>
          </div>

          <!-- Perfil & Status -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1">Perfil (RBAC)</label>
              <select
                v-model="form.role_id"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              >
                <option v-for="r in roles" :key="r.id" :value="String(r.id)">
                  {{ r.name }}
                </option>
              </select>
              <p v-if="formErrors.role_id" class="text-rose-400 mt-1">{{ formErrors.role_id[0] }}</p>
            </div>

            <div>
              <label class="block font-medium text-slate-300 mb-1">Status da Conta</label>
              <select
                v-model="form.status"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              >
                <option value="ACTIVE">Ativo</option>
                <option value="INACTIVE">Inativo</option>
              </select>
            </div>
          </div>

          <!-- Senha e Confirmação -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1">
                {{ isEditing ? 'Nova Senha (Opcional)' : 'Senha' }}
              </label>
              <input
                v-model="form.password"
                type="password"
                :required="!isEditing"
                placeholder="Mínimo 8 caracteres"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
              <p v-if="formErrors.password" class="text-rose-400 mt-1">{{ formErrors.password[0] }}</p>
            </div>

            <div>
              <label class="block font-medium text-slate-300 mb-1">Confirmar Senha</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                :required="!isEditing && Boolean(form.password)"
                placeholder="Confirme a senha"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
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
            @click="handleSaveUser"
            class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold text-xs transition-colors flex items-center space-x-1.5"
          >
            <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
            <span>{{ isEditing ? 'Salvar Alterações' : 'Criar Usuário' }}</span>
          </button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>
