<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import type { Task, TaskStatus } from '../../types/task'
import KanbanCard from './KanbanCard.vue'
import {
  Circle,
  Clock,
  AlertCircle,
  CheckCircle2,
  Plus,
  Loader2
} from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    status: TaskStatus
    title: string
    tasks: Task[]
    canEdit?: boolean
    isDragTarget?: boolean
    draggedTaskId?: number | null
    showProjectBadge?: boolean
  }>(),
  {
    canEdit: true,
    isDragTarget: false,
    draggedTaskId: null,
    showProjectBadge: false
  }
)

const emit = defineEmits<{
  (e: 'task-click', task: Task): void
  (e: 'comments-click', task: Task): void
  (e: 'move-status', task: Task, status: TaskStatus): void
  (e: 'quick-create', title: string, status: TaskStatus): void
  (e: 'drag-start', event: DragEvent, task: Task): void
  (e: 'drag-over', event: DragEvent, status: TaskStatus): void
  (e: 'drag-leave', event: DragEvent, status: TaskStatus): void
  (e: 'drop', event: DragEvent, status: TaskStatus, targetTaskId?: number): void
}>()

// Inline Quick Create State
const isQuickAdding = ref(false)
const quickTitle = ref('')
const quickInputRef = ref<HTMLInputElement | null>(null)
const isSubmittingQuick = ref(false)

async function startQuickAdd() {
  isQuickAdding.value = true
  quickTitle.value = ''
  await nextTick()
  quickInputRef.value?.focus()
}

function cancelQuickAdd() {
  isQuickAdding.value = false
  quickTitle.value = ''
}

function submitQuickAdd() {
  const trimmed = quickTitle.value.trim()
  if (!trimmed) return
  isSubmittingQuick.value = true
  emit('quick-create', trimmed, props.status)
  quickTitle.value = ''
  isQuickAdding.value = false
  isSubmittingQuick.value = false
}

// Column configuration (colors & icons)
const columnConfig = computed(() => {
  switch (props.status) {
    case 'todo':
      return {
        icon: Circle,
        iconClass: 'text-slate-400',
        badgeClass: 'bg-slate-800 text-slate-300 border-slate-700',
        accentBar: 'bg-slate-500'
      }
    case 'in_progress':
      return {
        icon: Clock,
        iconClass: 'text-blue-400',
        badgeClass: 'bg-blue-950/80 text-blue-300 border-blue-800/80',
        accentBar: 'bg-blue-500'
      }
    case 'review':
      return {
        icon: AlertCircle,
        iconClass: 'text-amber-400',
        badgeClass: 'bg-amber-950/80 text-amber-300 border-amber-800/80',
        accentBar: 'bg-amber-500'
      }
    case 'done':
      return {
        icon: CheckCircle2,
        iconClass: 'text-emerald-400',
        badgeClass: 'bg-emerald-950/80 text-emerald-300 border-emerald-800/80',
        accentBar: 'bg-emerald-500'
      }
  }
})
</script>

<template>
  <div
    :class="[
      'flex flex-col flex-1 min-w-[300px] max-w-[380px] bg-slate-900/60 border rounded-2xl p-3 transition-colors duration-200',
      isDragTarget
        ? 'border-primary-500/80 bg-primary-500/5 ring-2 ring-primary-500/20'
        : 'border-slate-800/80 hover:border-slate-800'
    ]"
    @dragover.prevent="emit('drag-over', $event, status)"
    @dragleave="emit('drag-leave', $event, status)"
    @drop.prevent="emit('drop', $event, status)"
  >
    <!-- Column Header -->
    <div class="flex items-center justify-between pb-3 px-1 border-b border-slate-800/60 mb-3">
      <div class="flex items-center gap-2">
        <component
          :is="columnConfig.icon"
          :class="['w-4 h-4', columnConfig.iconClass]"
        />
        <h3 class="font-semibold text-sm text-slate-200">
          {{ title }}
        </h3>
        <span
          :class="[
            'text-xs px-2 py-0.5 rounded-full font-medium border',
            columnConfig.badgeClass
          ]"
        >
          {{ tasks.length }}
        </span>
      </div>

      <button
        v-if="canEdit"
        type="button"
        class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
        title="Adicionar tarefa nesta coluna"
        @click="startQuickAdd"
      >
        <Plus class="w-4 h-4" />
      </button>
    </div>

    <!-- Cards Container / Drop Zone -->
    <div class="flex-1 overflow-y-auto space-y-2.5 min-h-[150px] pr-1 scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent">
      <!-- Empty Column Message -->
      <div
        v-if="tasks.length === 0 && !isQuickAdding"
        class="h-32 flex flex-col items-center justify-center text-center p-4 border border-dashed border-slate-800/80 rounded-xl text-slate-500 text-xs"
      >
        <span>Nenhuma tarefa aqui</span>
        <button
          v-if="canEdit"
          type="button"
          class="mt-2 text-primary-400 hover:text-primary-300 font-medium"
          @click="startQuickAdd"
        >
          + Adicionar tarefa
        </button>
      </div>

      <!-- Task Cards -->
      <div
        v-for="task in tasks"
        :key="task.id"
        :draggable="canEdit"
        @dragstart="emit('drag-start', $event, task)"
        @drop.stop.prevent="emit('drop', $event, status, task.id)"
      >
        <KanbanCard
          :task="task"
          :can-edit="canEdit"
          :is-dragging="draggedTaskId === task.id"
          :show-project-badge="showProjectBadge"
          @click="emit('task-click', task)"
          @comments-click="emit('comments-click', task)"
          @move-status="(t, s) => emit('move-status', t, s)"
        />
      </div>

      <!-- Inline Quick Add Input -->
      <div
        v-if="isQuickAdding"
        class="bg-slate-900 border border-primary-500/50 rounded-xl p-3 shadow-lg"
      >
        <input
          ref="quickInputRef"
          v-model="quickTitle"
          type="text"
          placeholder="O que precisa ser feito? (Enter para salvar)"
          class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-primary-500"
          :disabled="isSubmittingQuick"
          @keydown.enter.prevent="submitQuickAdd"
          @keydown.esc="cancelQuickAdd"
        />

        <div class="flex items-center justify-end gap-2 mt-2">
          <button
            type="button"
            class="px-2.5 py-1 text-xs text-slate-400 hover:text-slate-200 rounded-lg"
            :disabled="isSubmittingQuick"
            @click="cancelQuickAdd"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-3 py-1 bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white rounded-lg text-xs font-medium flex items-center gap-1 shadow-sm"
            :disabled="!quickTitle.trim() || isSubmittingQuick"
            @click="submitQuickAdd"
          >
            <Loader2 v-if="isSubmittingQuick" class="w-3 h-3 animate-spin" />
            <span>Adicionar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Add Footer Button (when not open) -->
    <div
      v-if="canEdit && !isQuickAdding && tasks.length > 0"
      class="pt-3 mt-1 border-t border-slate-800/40"
    >
      <button
        type="button"
        class="w-full py-1.5 px-3 rounded-xl border border-dashed border-slate-800 text-slate-400 hover:text-slate-200 hover:border-slate-700 hover:bg-slate-850/60 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors"
        @click="startQuickAdd"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Adicionar tarefa</span>
      </button>
    </div>
  </div>
</template>
