<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import type { PaginationMeta } from '../../types/api'

defineProps<{
  meta: PaginationMeta
}>()

const emit = defineEmits<{
  (e: 'change-page', page: number): void
}>()
</script>

<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-800 text-xs text-slate-400">
    <div>
      Mostrando página <span class="font-semibold text-slate-200">{{ meta.current_page }}</span> de <span class="font-semibold text-slate-200">{{ meta.last_page }}</span> (Total: {{ meta.total }} registros)
    </div>

    <div class="flex items-center gap-1">
      <button
        :disabled="meta.current_page <= 1"
        @click="emit('change-page', meta.current_page - 1)"
        class="p-1.5 rounded-lg border border-slate-800 bg-slate-900 hover:bg-slate-800 disabled:opacity-40 disabled:hover:bg-slate-900 text-slate-300 transition-colors"
      >
        <ChevronLeft class="w-4 h-4" />
      </button>

      <span class="px-3 py-1 font-mono text-emerald-400 font-semibold">
        {{ meta.current_page }}
      </span>

      <button
        :disabled="meta.current_page >= meta.last_page"
        @click="emit('change-page', meta.current_page + 1)"
        class="p-1.5 rounded-lg border border-slate-800 bg-slate-900 hover:bg-slate-800 disabled:opacity-40 disabled:hover:bg-slate-900 text-slate-300 transition-colors"
      >
        <ChevronRight class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
