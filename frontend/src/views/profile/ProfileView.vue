<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '../../layouts/AppLayout.vue'
import { useAuthStore } from '../../stores/auth'
import { authService } from '../../services/authService'
import { useToast } from '../../composables/useToast'
import { UserCircle, Lock, Save, Loader2, KeyRound } from '@lucide/vue'

const authStore = useAuthStore()
const toast = useToast()

// Profile Form
const profileForm = ref({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
})
const profileLoading = ref(false)
const profileErrors = ref<Record<string, string[]>>({})

// Password Form
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordLoading = ref(false)
const passwordErrors = ref<Record<string, string[]>>({})

async function handleUpdateProfile() {
  profileLoading.value = true
  profileErrors.value = {}

  try {
    const res = await authService.updateProfile(profileForm.value)
    if (res.success && res.data) {
      authStore.setUser(res.data)
      toast.success('Perfil atualizado com sucesso!')
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      profileErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message || 'Falha ao atualizar perfil.')
    }
  } finally {
    profileLoading.value = false
  }
}

async function handleChangePassword() {
  passwordLoading.value = true
  passwordErrors.value = {}

  try {
    await authService.changePassword(passwordForm.value)
    toast.success('Senha alterada com sucesso!')
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      passwordErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message || 'Falha ao alterar senha.')
    }
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl space-y-8">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
          <UserCircle class="w-6 h-6 text-emerald-400" />
          Meu Perfil
        </h1>
        <p class="text-xs text-slate-400 mt-1">Gerencie suas informações cadastrais e segurança da conta.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Informações Pessoais -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
          <div class="flex items-center space-x-3 pb-3 border-b border-slate-800">
            <UserCircle class="w-5 h-5 text-emerald-400" />
            <h2 class="text-sm font-semibold text-white">Dados Pessoais</h2>
          </div>

          <form @submit.prevent="handleUpdateProfile" class="space-y-4 text-xs">
            <!-- Nome -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Nome Completo</label>
              <input
                v-model="profileForm.name"
                type="text"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
              <p v-if="profileErrors.name" class="text-rose-400 mt-1">{{ profileErrors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">E-mail Profissional</label>
              <input
                v-model="profileForm.email"
                type="email"
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
              />
              <p v-if="profileErrors.email" class="text-rose-400 mt-1">{{ profileErrors.email[0] }}</p>
            </div>

            <!-- Perfil (Read-only) -->
            <div>
              <label class="block font-medium text-slate-400 mb-1">Perfil Atribuído (RBAC)</label>
              <div class="px-3 py-2 rounded-xl bg-slate-950/60 border border-slate-800/80 text-emerald-400 font-mono font-medium">
                {{ authStore.user?.role?.name || 'Usuário' }}
              </div>
            </div>

            <button
              type="submit"
              :disabled="profileLoading"
              class="w-full py-2 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold transition-colors flex items-center justify-center space-x-1.5"
            >
              <Loader2 v-if="profileLoading" class="w-3.5 h-3.5 animate-spin" />
              <Save v-else class="w-3.5 h-3.5" />
              <span>Salvar Alterações</span>
            </button>
          </form>
        </div>

        <!-- Card 2: Alteração de Senha -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
          <div class="flex items-center space-x-3 pb-3 border-b border-slate-800">
            <Lock class="w-5 h-5 text-teal-400" />
            <h2 class="text-sm font-semibold text-white">Segurança & Senha</h2>
          </div>

          <form @submit.prevent="handleChangePassword" class="space-y-4 text-xs">
            <!-- Senha Atual -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Senha Atual</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                required
                placeholder="••••••••"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-teal-500"
              />
              <p v-if="passwordErrors.current_password" class="text-rose-400 mt-1">{{ passwordErrors.current_password[0] }}</p>
            </div>

            <!-- Nova Senha -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Nova Senha</label>
              <input
                v-model="passwordForm.password"
                type="password"
                required
                placeholder="Mínimo 8 caracteres com letras e números"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-teal-500"
              />
              <p v-if="passwordErrors.password" class="text-rose-400 mt-1">{{ passwordErrors.password[0] }}</p>
            </div>

            <!-- Confirmação Nova Senha -->
            <div>
              <label class="block font-medium text-slate-300 mb-1">Confirmar Nova Senha</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                placeholder="Confirme a nova senha"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-teal-500"
              />
            </div>

            <button
              type="submit"
              :disabled="passwordLoading"
              class="w-full py-2 px-4 rounded-xl bg-teal-500 hover:bg-teal-400 disabled:opacity-50 text-slate-950 font-semibold transition-colors flex items-center justify-center space-x-1.5"
            >
              <Loader2 v-if="passwordLoading" class="w-3.5 h-3.5 animate-spin" />
              <KeyRound v-else class="w-3.5 h-3.5" />
              <span>Atualizar Senha</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
