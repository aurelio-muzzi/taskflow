<script setup lang="ts">
import { computed } from 'vue'
import { useToast } from '../../composables/useToast'
import { CheckCircle2, AlertCircle, Info, AlertTriangle, X } from '@lucide/vue'

const { toasts, remove, handleConfirmAction } = useToast()

const regularToasts = computed(() => toasts.value.filter(t => t.type !== 'confirm'))
const confirmToasts = computed(() => toasts.value.filter(t => t.type === 'confirm'))

const iconMap = {
  success: CheckCircle2,
  error: AlertCircle,
  info: Info,
  warning: AlertTriangle,
  confirm: AlertTriangle,
}

const colorMap = {
  success: 'bg-emerald-950/95 border-emerald-500/50 text-emerald-200',
  error: 'bg-rose-950/95 border-rose-500/50 text-rose-200',
  info: 'bg-sky-950/95 border-sky-500/50 text-sky-200',
  warning: 'bg-amber-950/95 border-amber-500/50 text-amber-200',
  confirm: 'bg-slate-900/98 border-rose-500/50 text-slate-100 shadow-2xl ring-1 ring-rose-500/30',
}
</script>

<template>
  <!-- Notificações Normais (Canto Inferior Direito) -->
  <div class="fixed bottom-5 right-5 z-50 flex flex-col space-y-3 pointer-events-none max-w-md w-[calc(100vw-2.5rem)] sm:w-full">
    <transition-group
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-3 opacity-0 sm:translate-y-0 sm:translate-x-3"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in regularToasts"
        :key="toast.id"
        :class="[
          'pointer-events-auto rounded-2xl border shadow-2xl backdrop-blur-xl text-sm p-4 transition-all duration-200',
          colorMap[toast.type]
        ]"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3 min-w-0">
            <component :is="iconMap[toast.type]" class="w-5 h-5 flex-shrink-0" />
            <span class="font-medium leading-snug break-words">{{ toast.text }}</span>
          </div>
          <button
            @click="remove(toast.id)"
            class="ml-3 p-1 rounded-lg hover:bg-white/10 transition-colors opacity-70 hover:opacity-100 shrink-0 cursor-pointer"
            title="Fechar"
            aria-label="Fechar"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </transition-group>
  </div>

  <!-- Mensagens de Confirmação (CENTRALIZADAS NO MEIO DA TELA) -->
  <div
    v-if="confirmToasts.length > 0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm pointer-events-auto transition-opacity duration-200"
    @click.self="handleConfirmAction(confirmToasts[confirmToasts.length - 1].id, false)"
  >
    <transition-group
      enter-active-class="transform ease-out duration-250 transition"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transform ease-in duration-150 transition"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-for="toast in confirmToasts"
        :key="toast.id"
        :class="[
          'w-full max-w-md rounded-3xl border shadow-2xl backdrop-blur-2xl p-5 sm:p-6 transition-all duration-200 space-y-4',
          colorMap[toast.type]
        ]"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start space-x-3.5 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-500/30 text-rose-400 flex items-center justify-center shrink-0">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div class="min-w-0 pt-0.5">
              <h4 v-if="toast.title" class="font-bold text-white text-sm tracking-tight mb-1">
                {{ toast.title }}
              </h4>
              <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                {{ toast.text }}
              </p>
            </div>
          </div>
          <button
            @click="handleConfirmAction(toast.id, false)"
            class="p-1.5 rounded-lg hover:bg-white/10 text-slate-400 hover:text-white transition-colors shrink-0 cursor-pointer"
            title="Cancelar"
            aria-label="Cancelar"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Botões de Ação Táteis Centralizados/Espaçados -->
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800/80">
          <button
            type="button"
            @click="handleConfirmAction(toast.id, false)"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 border border-slate-700/60 transition-colors cursor-pointer"
          >
            {{ toast.cancelText || 'Cancelar' }}
          </button>
          <button
            type="button"
            @click="handleConfirmAction(toast.id, true)"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-rose-600 hover:bg-rose-500 shadow-lg shadow-rose-600/30 transition-all cursor-pointer"
          >
            {{ toast.confirmText || 'Confirmar' }}
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

