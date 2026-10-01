<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue'
import { useAuthStore } from '../stores/auth'
import {
  ShieldCheck,
  Users,
  FolderKanban,
  CheckCircle2,
  Clock,
  ArrowUpRight
} from '@lucide/vue'
import { RouterLink } from 'vue-router'

const authStore = useAuthStore()
</script>

<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Welcome Banner -->
      <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
            <ShieldCheck class="w-3.5 h-3.5" />
            Sessão Ativa: {{ authStore.user?.role?.name || 'Usuário' }}
          </div>

          <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            Olá, {{ authStore.user?.name }}!
          </h1>

          <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
            Bem-vindo ao TaskFlow. O controle de autenticação e RBAC (Etapa 2) está plenamente operacional.
          </p>
        </div>

        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Quick Metrics Preview -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium">Seu Perfil</span>
            <ShieldCheck class="w-4 h-4 text-emerald-400" />
          </div>
          <p class="text-xl font-bold text-white">{{ authStore.user?.role?.name }}</p>
          <p class="text-[11px] text-slate-500 mt-1">Nível de acesso global</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium">Status da Conta</span>
            <CheckCircle2 class="w-4 h-4 text-teal-400" />
          </div>
          <p class="text-xl font-bold text-emerald-400">Ativo</p>
          <p class="text-[11px] text-slate-500 mt-1">Autenticação Sanctum verificada</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium">Próxima Etapa</span>
            <FolderKanban class="w-4 h-4 text-cyan-400" />
          </div>
          <p class="text-xl font-bold text-white">Etapa 3</p>
          <p class="text-[11px] text-slate-500 mt-1">Projetos & Membros de Equipe</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium">Ambiente</span>
            <Clock class="w-4 h-4 text-purple-400" />
          </div>
          <p class="text-xl font-bold text-white">Docker / Local</p>
          <p class="text-[11px] text-slate-500 mt-1">Laravel 12 + PostgreSQL</p>
        </div>
      </div>

      <!-- Quick Actions for Admin -->
      <div v-if="authStore.isAdmin" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Users class="w-5 h-5 text-emerald-400" />
            <h2 class="text-sm font-semibold text-white">Painel Administrativo de Usuários</h2>
          </div>
          <RouterLink
            to="/users"
            class="text-xs text-emerald-400 hover:text-emerald-300 font-medium inline-flex items-center gap-1"
          >
            <span>Gerenciar todos</span>
            <ArrowUpRight class="w-3.5 h-3.5" />
          </RouterLink>
        </div>
        <p class="text-xs text-slate-400">
          Como administrador, você possui permissão para cadastrar, editar, desativar e excluir contas de usuários do sistema.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
