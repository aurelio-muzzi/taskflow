<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useToast } from '../../composables/useToast'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { Layers, Lock, Mail, Loader2, ArrowRight } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  if (!email.value || !password.value) {
    errorMessage.value = 'Por favor, preencha todos os campos.'
    return
  }

  try {
    const success = await authStore.login({
      email: email.value,
      password: password.value,
    })

    if (success) {
      toast.success(`Bem-vindo, ${authStore.user?.name}!`)
      router.push('/dashboard')
    }
  } catch (err: any) {
    const apiErrors = err.response?.data?.errors
    if (apiErrors?.email) {
      errorMessage.value = apiErrors.email[0]
    } else {
      errorMessage.value = err.response?.data?.message || 'Credenciais inválidas.'
    }
    toast.error(errorMessage.value)
  }
}

function fillDemo(role: 'admin' | 'manager' | 'user') {
  email.value = `${role}@taskflow.dev`
  password.value = 'Password123!'
  errorMessage.value = ''
}
</script>

<template>
  <AuthLayout>
    <template #header>
      <div class="flex justify-center mb-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
          <Layers class="w-6 h-6 text-slate-950" />
        </div>
      </div>
      <h2 class="text-2xl font-bold text-white tracking-tight">TaskFlow</h2>
      <p class="mt-1 text-xs text-slate-400">Entre com sua conta profissional para continuar</p>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-300">
        {{ errorMessage }}
      </div>

      <!-- Email -->
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1.5">E-mail</label>
        <div class="relative">
          <Mail class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="seu.email@exemplo.com"
            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 transition-colors"
          />
        </div>
      </div>

      <!-- Senha -->
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1.5">Senha</label>
        <div class="relative">
          <Lock class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 transition-colors"
          />
        </div>
      </div>

      <!-- Botão Submit -->
      <button
        type="submit"
        :disabled="authStore.loading"
        class="w-full py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold text-sm transition-all flex items-center justify-center space-x-2 shadow-lg shadow-emerald-500/10 cursor-pointer"
      >
        <Loader2 v-if="authStore.loading" class="w-4 h-4 animate-spin" />
        <span v-else>Entrar no TaskFlow</span>
        <ArrowRight v-if="!authStore.loading" class="w-4 h-4" />
      </button>

      <!-- Contas de Demonstração Rápidas -->
      <div class="pt-4 border-t border-slate-800 text-center">
        <p class="text-[11px] text-slate-400 font-medium mb-2 uppercase tracking-wider">Acesso Rápido de Demonstração:</p>
        <div class="grid grid-cols-3 gap-2">
          <button
            type="button"
            @click="fillDemo('admin')"
            class="py-1.5 px-2 rounded-lg border border-slate-800 bg-slate-950/60 hover:bg-slate-800 text-[11px] text-emerald-400 font-medium transition-colors"
          >
            Admin
          </button>
          <button
            type="button"
            @click="fillDemo('manager')"
            class="py-1.5 px-2 rounded-lg border border-slate-800 bg-slate-950/60 hover:bg-slate-800 text-[11px] text-teal-400 font-medium transition-colors"
          >
            Gerente
          </button>
          <button
            type="button"
            @click="fillDemo('user')"
            class="py-1.5 px-2 rounded-lg border border-slate-800 bg-slate-950/60 hover:bg-slate-800 text-[11px] text-cyan-400 font-medium transition-colors"
          >
            Usuário
          </button>
        </div>
      </div>
    </form>
  </AuthLayout>
</template>
