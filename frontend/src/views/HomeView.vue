<script setup lang="ts">
import { CheckCircle2, ShieldCheck, Layers, GitBranch, Terminal } from '@lucide/vue'
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-between selection:bg-emerald-500/20 selection:text-emerald-400">
    <!-- Navbar -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <Layers class="w-5 h-5 text-slate-950" />
          </div>
          <div>
            <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
              TaskFlow
            </span>
            <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              v1.0-dev
            </span>
          </div>
        </div>

        <nav class="flex items-center gap-3">
          <a
            href="http://localhost:8080/api/v1/health"
            target="_blank"
            class="text-xs font-mono px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/50 hover:bg-slate-800 text-slate-300 transition-colors flex items-center gap-1.5"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            API Status
          </a>
        </nav>
      </div>
    </header>

    <!-- Hero Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 flex-1 flex flex-col justify-center">
      <div class="text-center max-w-3xl mx-auto space-y-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium tracking-wide">
          <ShieldCheck class="w-4 h-4" />
          Fullstack Portfolio Grade Architecture
        </div>

        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-tight">
          Gestão Profissional de Tarefas e Projetos com
          <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
            TaskFlow
          </span>
        </h1>

        <p class="text-base sm:text-lg text-slate-400 leading-relaxed">
          Plataforma desenvolvida com Laravel 12+, PostgreSQL, Vue.js 3, TypeScript e Tailwind CSS. 
          Arquitetura desacoplada, RBAC granular, auditoria contínua e Docker compose unificado.
        </p>

        <!-- Tech badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 text-left">
          <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/50 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-emerald-400 font-semibold text-sm mb-1">
              <CheckCircle2 class="w-4 h-4" />
              Laravel 12 API
            </div>
            <p class="text-xs text-slate-400">Sanctum, Actions, DTOs, Policies e Form Requests.</p>
          </div>

          <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/50 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-teal-400 font-semibold text-sm mb-1">
              <CheckCircle2 class="w-4 h-4" />
              Vue 3 & TypeScript
            </div>
            <p class="text-xs text-slate-400">Pinia, Vue Router, Tailwind CSS e componentes tipados.</p>
          </div>

          <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/50 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-cyan-400 font-semibold text-sm mb-1">
              <CheckCircle2 class="w-4 h-4" />
              PostgreSQL & Audit
            </div>
            <p class="text-xs text-slate-400">Migrations relacionais, índices otimizados e Soft Deletes.</p>
          </div>

          <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/50 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-blue-400 font-semibold text-sm mb-1">
              <CheckCircle2 class="w-4 h-4" />
              Docker & Nginx
            </div>
            <p class="text-xs text-slate-400">Ambiente pronto com orquestração de containers.</p>
          </div>
        </div>

        <!-- Terminal Status Card -->
        <div class="mt-8 p-4 rounded-xl border border-slate-800 bg-slate-900/80 text-left font-mono text-xs text-slate-300">
          <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 text-slate-400">
            <div class="flex items-center gap-2">
              <Terminal class="w-4 h-4 text-emerald-400" />
              <span>Etapa 1: Ambiente e Estrutura Inicial</span>
            </div>
            <span class="text-emerald-400">STATUS: CONCLUÍDO</span>
          </div>
          <div class="space-y-1 text-slate-400">
            <p><span class="text-emerald-400">✓</span> Backend: Laravel 12 + Sanctum + API scaffolding</p>
            <p><span class="text-emerald-400">✓</span> Frontend: Vue 3 + TypeScript + Pinia + Vue Router + Tailwind</p>
            <p><span class="text-emerald-400">✓</span> Database: PostgreSQL schema planejado e extensões configuradas</p>
            <p><span class="text-emerald-400">✓</span> Infraestrutura: Dockerfile PHP 8.3 FPM, Nginx config e docker-compose.yml</p>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-900/40 py-6 text-center text-xs text-slate-500">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
        <p>TaskFlow — Projeto de Portfólio Profissional Fullstack</p>
        <p class="flex items-center gap-1">
          <GitBranch class="w-3.5 h-3.5 text-slate-400" />
          Branch: <span class="text-slate-300">main</span>
        </p>
      </div>
    </footer>
  </div>
</template>
