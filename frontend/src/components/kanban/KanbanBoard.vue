<script setup lang="ts">
import { ref } from 'vue'
import type { Task, TaskStatus } from '../../types/task'
import KanbanColumn from './KanbanColumn.vue'
import { taskService } from '../../services/taskService'
import { useToast } from '../../composables/useToast'
import { Loader2 } from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    tasks: Task[]
    projectId?: number
    canEdit?: boolean
    loading?: boolean
    showProjectBadge?: boolean
  }>(),
  {
    canEdit: true,
    loading: false,
    showProjectBadge: false
  }
)

const emit = defineEmits<{
  (e: 'task-click', task: Task): void
  (e: 'comments-click', task: Task): void
  (e: 'quick-create', title: string, status: TaskStatus): void
  (e: 'task-updated', task: Task): void
  (e: 'refresh'): void
}>()

const toast = useToast()

// Drag & Drop reactive state
const draggedTask = ref<Task | null>(null)
const dragOverStatus = ref<TaskStatus | null>(null)

// Local copy for optimistic UI updates
const localTasks = ref<Task[]>([...props.tasks])

// Synchronize local tasks when props change and not actively dragging
import { watch } from 'vue'
watch(
  () => props.tasks,
  newTasks => {
    if (!draggedTask.value) {
      localTasks.value = [...newTasks]
    }
  },
  { deep: true }
)

// Computed columns
const columns: { status: TaskStatus; title: string }[] = [
  { status: 'todo', title: 'A Fazer' },
  { status: 'in_progress', title: 'Em Andamento' },
  { status: 'review', title: 'Em Revisão' },
  { status: 'done', title: 'Concluído' }
]

function getTasksForStatus(status: TaskStatus) {
  return localTasks.value
    .filter(t => t.status === status)
    .sort((a, b) => a.order - b.order || b.id - a.id)
}

// Drag & Drop Event Handlers
function handleDragStart(event: DragEvent, task: Task) {
  if (!props.canEdit) return
  draggedTask.value = task
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(task.id))
  }
}

function handleDragOver(_event: DragEvent, status: TaskStatus) {
  if (!props.canEdit || !draggedTask.value) return
  dragOverStatus.value = status
}

function handleDragLeave(_event: DragEvent, status: TaskStatus) {
  if (dragOverStatus.value === status) {
    dragOverStatus.value = null
  }
}

async function handleDrop(_event: DragEvent, targetStatus: TaskStatus, targetTaskId?: number) {
  if (!props.canEdit || !draggedTask.value) {
    dragOverStatus.value = null
    draggedTask.value = null
    return
  }

  const movingTask = draggedTask.value
  const previousStatus = movingTask.status
  const previousTasksState = JSON.parse(JSON.stringify(localTasks.value)) as Task[]

  // Reset drag visual indicators
  dragOverStatus.value = null
  draggedTask.value = null

  // Target column tasks without the moving task
  const targetColTasks = localTasks.value
    .filter(t => t.status === targetStatus && t.id !== movingTask.id)
    .sort((a, b) => a.order - b.order || b.id - a.id)

  // Determine insert position
  let insertIndex = targetColTasks.length
  if (targetTaskId !== undefined) {
    const idx = targetColTasks.findIndex(t => t.id === targetTaskId)
    if (idx !== -1) {
      insertIndex = idx
    }
  }

  // Update moving task status in memory
  movingTask.status = targetStatus

  // Insert into new order
  targetColTasks.splice(insertIndex, 0, movingTask)

  // Re-assign continuous order indices
  targetColTasks.forEach((t, i) => {
    t.order = i
  })

  // Optimistic update of local tasks
  localTasks.value = localTasks.value.map(t => {
    const updated = targetColTasks.find(u => u.id === t.id)
    return updated || t
  })

  // Prepare batch reorder payload for target column
  const reorderPayload = targetColTasks.map(t => ({
    id: t.id,
    order: t.order,
    status: t.status
  }))

  try {
    const targetProjectId = props.projectId || movingTask.project_id
    if (targetProjectId) {
      await taskService.reorderTasks(targetProjectId, reorderPayload)
    } else {
      await taskService.updateTaskStatus(movingTask.id, targetStatus, movingTask.order)
    }

    if (previousStatus !== targetStatus) {
      toast.success(`Tarefa movida para "${getStatusLabel(targetStatus)}"`)
    }
    emit('task-updated', movingTask)
    emit('refresh')
  } catch {
    // Rollback on failure
    localTasks.value = previousTasksState
    toast.error('Erro ao atualizar tarefa no servidor. As alterações foram revertidas.')
  }
}

// Quick status change from dropdown / keyboard action
async function handleQuickMoveStatus(task: Task, newStatus: TaskStatus) {
  if (!props.canEdit || task.status === newStatus) return

  const previousStatus = task.status
  const previousTasksState = JSON.parse(JSON.stringify(localTasks.value)) as Task[]

  // Optimistic update
  task.status = newStatus

  try {
    await taskService.updateTaskStatus(task.id, newStatus)
    toast.success(`Tarefa movida para "${getStatusLabel(newStatus)}"`)
    emit('task-updated', task)
    emit('refresh')
  } catch {
    localTasks.value = previousTasksState
    task.status = previousStatus
    toast.error('Erro ao atualizar status da tarefa.')
  }
}

function getStatusLabel(status: TaskStatus): string {
  switch (status) {
    case 'todo': return 'A Fazer'
    case 'in_progress': return 'Em Andamento'
    case 'review': return 'Em Revisão'
    case 'done': return 'Concluído'
  }
}
</script>

<template>
  <div class="relative w-full min-w-0">
    <!-- Loading Overlay -->
    <div
      v-if="loading"
      class="absolute inset-0 bg-slate-950/60 backdrop-blur-[1px] z-20 flex items-center justify-center rounded-2xl"
    >
      <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 px-4 py-2.5 rounded-xl shadow-xl text-slate-300 text-sm">
        <Loader2 class="w-4 h-4 animate-spin text-primary-400" />
        <span>Atualizando quadro...</span>
      </div>
    </div>

    <!-- Kanban Board Grid com Rolagem Fluida e Suporte a Toque -->
    <div class="flex gap-4 overflow-x-auto pb-4 pt-1 min-h-[550px] w-full min-w-0 snap-x snap-proximity scroll-smooth">
      <KanbanColumn
        v-for="col in columns"
        :key="col.status"
        :status="col.status"
        :title="col.title"
        :tasks="getTasksForStatus(col.status)"
        :can-edit="canEdit"
        :is-drag-target="dragOverStatus === col.status"
        :dragged-task-id="draggedTask?.id"
        :show-project-badge="showProjectBadge"
        @task-click="t => emit('task-click', t)"
        @comments-click="t => emit('comments-click', t)"
        @move-status="(t, s) => handleQuickMoveStatus(t, s)"
        @quick-create="(title, status) => emit('quick-create', title, status)"
        @drag-start="(e, t) => handleDragStart(e, t)"
        @drag-over="(e, s) => handleDragOver(e, s)"
        @drag-leave="(e, s) => handleDragLeave(e, s)"
        @drop="(e, s, tid) => handleDrop(e, s, tid)"
      />
    </div>
  </div>
</template>
