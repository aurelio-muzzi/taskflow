<script setup lang="ts">
import { useToast } from '../../composables/useToast'
import { CheckCircle2, AlertCircle, Info, AlertTriangle, X } from '@lucide/vue'

const { toasts, remove } = useToast()

const iconMap = {
  success: CheckCircle2,
  error: AlertCircle,
  info: Info,
  warning: AlertTriangle,
}

const colorMap = {
  success: 'bg-emerald-950/90 border-emerald-500/50 text-emerald-200',
  error: 'bg-rose-950/90 border-rose-500/50 text-rose-200',
  info: 'bg-sky-950/90 border-sky-500/50 text-sky-200',
  warning: 'bg-amber-950/90 border-amber-500/50 text-amber-200',
}
</script>

<template>
  <div class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full">
    <transition-group
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'pointer-events-auto flex items-center justify-between p-4 rounded-xl border shadow-xl backdrop-blur-md text-sm',
          colorMap[toast.type]
        ]"
      >
        <div class="flex items-center space-x-3">
          <component :is="iconMap[toast.type]" class="w-5 h-5 flex-shrink-0" />
          <span class="font-medium leading-snug">{{ toast.text }}</span>
        </div>
        <button
          @click="remove(toast.id)"
          class="ml-3 p-1 rounded-lg hover:bg-white/10 transition-colors opacity-70 hover:opacity-100"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </transition-group>
  </div>
</template>
