<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue'
import { FolderKanban } from '@lucide/vue'

defineProps<{
  title: string
  stage: string
  description: string
}>()
</script>

<template>
  <AppLayout>
    <div class="p-12 rounded-3xl bg-slate-900 border border-slate-800 text-center max-w-xl mx-auto space-y-4 my-12">
      <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/20">
        <FolderKanban class="w-6 h-6" />
      </div>
      <h2 class="text-xl font-bold text-white">{{ title }}</h2>
      <p class="text-xs text-slate-400 leading-relaxed">{{ description }}</p>
      <span class="inline-block px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">
        Programado para {{ stage }}
      </span>
    </div>
  </AppLayout>
</template>
