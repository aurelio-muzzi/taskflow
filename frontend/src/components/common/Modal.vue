<script setup lang="ts">
import { X } from '@lucide/vue'

defineProps<{
  show: boolean
  title: string
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const maxWidthClass = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
}
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="ease-out duration-300 transition"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-200 transition"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="emit('close')"
      >
        <div
          :class="[
            'w-full rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5 sm:p-6 relative transform transition-all max-h-[90vh] flex flex-col',
            maxWidthClass[maxWidth || 'lg']
          ]"
        >
          <!-- Header -->
          <div class="flex items-center justify-between pb-3 sm:pb-4 mb-4 border-b border-slate-800 shrink-0">
            <h3 class="text-base sm:text-lg font-bold text-white tracking-tight">{{ title }}</h3>
            <button
              @click="emit('close')"
              class="p-2 sm:p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
              title="Fechar"
              aria-label="Fechar"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body Rolável Internamente para Teclado Virtual / Telas Menores -->
          <div class="space-y-4 overflow-y-auto flex-1 pr-1">
            <slot />
          </div>

          <!-- Footer Fixo -->
          <div v-if="$slots.footer" class="mt-5 pt-3 sm:pt-4 border-t border-slate-800 flex justify-end gap-3 shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
