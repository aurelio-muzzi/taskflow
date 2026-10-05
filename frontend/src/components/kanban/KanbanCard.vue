<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import type { Task, TaskPriority, TaskStatus } from '../../types/task'
import {
  Calendar,
  AlertTriangle,
  MessageSquare,
  Clock,
  MoreHorizontal,
  ChevronRight
} from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    task: Task
    canEdit?: boolean
    isDragging?: boolean
    showProjectBadge?: boolean
  }>(),
  {
    canEdit: true,
    isDragging: false,
    showProjectBadge: false
  }
)

const emit = defineEmits<{
  (e: 'click', task: Task): void
  (e: 'comments-click', task: Task): void
  (e: 'move-status', task: Task, status: TaskStatus): void
}>()

const showMenu = ref(false)
const menuRef = ref<HTMLElement | null>(null)

function handleClickOutside(event: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const priorityConfig = computed(() => {
  switch (props.task.priority as TaskPriority) {
    case 'urgent':
      return { label: 'Urgente', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' }
    case 'high':
      return { label: 'Alta', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' }
    case 'medium':
      return { label: 'Média', class: 'bg-sky-500/10 text-sky-400 border-sky-500/20' }
    case 'low':
    default:
      return { label: 'Baixa', class: 'bg-slate-500/10 text-slate-400 border-slate-500/20' }
  }
})

const isOverdue = computed(() => {
  if (!props.task.due_date || props.task.status === 'done') return false
  const today = new Date().toISOString().split('T')[0]
  return props.task.due_date < today
})

const isDueToday = computed(() => {
  if (!props.task.due_date || props.task.status === 'done') return false
  const today = new Date().toISOString().split('T')[0]
  return props.task.due_date === today
})

const formattedDueDate = computed(() => {
  if (!props.task.due_date) return null
  const [year, month, day] = props.task.due_date.split('-')
  return `${day}/${month}/${year}`
})

const assigneeInitials = computed(() => {
  if (!props.task.assignee?.name) return '?'
  const parts = props.task.assignee.name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase()
  }
  return parts[0].slice(0, 2).toUpperCase()
})

const availableStatusMoves = computed(() => {
  const all: { status: TaskStatus; label: string }[] = [
    { status: 'todo', label: 'A Fazer' },
    { status: 'in_progress', label: 'Em Andamento' },
    { status: 'review', label: 'Em Revisão' },
    { status: 'done', label: 'Concluído' }
  ]
  return all.filter(s => s.status !== props.task.status)
})
</script>

<template>
  <div
    :class="[
      'group relative bg-slate-900 border rounded-xl p-4 shadow-sm transition-all duration-150 select-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500/50',
      isDragging
        ? 'opacity-40 border-primary-500 ring-2 ring-primary-500/30 rotate-1'
        : 'border-slate-800/80 hover:border-slate-700 hover:shadow-md hover:bg-slate-850'
    ]"
    role="button"
    tabindex="0"
    :aria-label="`Tarefa: ${task.title}. Prioridade: ${priorityConfig.label}. Status: ${task.status_label || task.status}. Pressione Enter para abrir detalhes.`"
    @click="emit('click', task)"
    @keydown.enter.self="emit('click', task)"
    @keydown.space.prevent.self="emit('click', task)"
  >
    <!-- Card Header: Badges & Action Menu -->
    <div class="flex items-center justify-between gap-2 mb-2.5">
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Priority Badge -->
        <span
          :class="[
            'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-semibold border',
            priorityConfig.class
          ]"
        >
          <span
            v-if="task.priority === 'urgent'"
            class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"
          />
          {{ priorityConfig.label }}
        </span>

        <!-- Project Badge (optional) -->
        <span
          v-if="showProjectBadge && task.project"
          class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700/60 max-w-[120px] truncate"
          :title="task.project.name"
        >
          {{ task.project.name }}
        </span>
      </div>

      <!-- Quick Move / Options Menu -->
      <div
        v-if="canEdit"
        ref="menuRef"
        class="relative"
        @click.stop
      >
        <button
          type="button"
          class="p-1.5 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 opacity-80 sm:opacity-60 sm:group-hover:opacity-100 transition-opacity cursor-pointer"
          title="Mover ou opções da tarefa"
          @click="showMenu = !showMenu"
        >
          <MoreHorizontal class="w-4 h-4" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showMenu"
          class="absolute right-0 top-full mt-1 w-44 rounded-xl bg-slate-850 border border-slate-700/80 shadow-xl py-1 z-30 text-xs"
        >
          <div class="px-3 py-1.5 font-medium text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-750">
            Mover para
          </div>
          <button
            v-for="target in availableStatusMoves"
            :key="target.status"
            type="button"
            class="w-full text-left px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-750 flex items-center justify-between transition-colors"
            @click="emit('move-status', task, target.status); showMenu = false"
          >
            <span>{{ target.label }}</span>
            <ChevronRight class="w-3.5 h-3.5 text-slate-500" />
          </button>
        </div>
      </div>
    </div>

    <!-- Task Title -->
    <h4 class="text-sm font-medium text-slate-200 group-hover:text-white leading-snug line-clamp-2 mb-3">
      {{ task.title }}
    </h4>

    <!-- Card Footer: Due Date, Comments, Assignee -->
    <div class="flex items-center justify-between pt-2 border-t border-slate-800/60 text-xs">
      <div class="flex items-center gap-3 text-slate-400">
        <!-- Due Date -->
        <div
          v-if="task.due_date"
          :class="[
            'inline-flex items-center gap-1 font-medium',
            isOverdue
              ? 'text-rose-400 font-semibold'
              : isDueToday
                ? 'text-amber-400 font-semibold'
                : 'text-slate-400'
          ]"
          :title="isOverdue ? 'Tarefa atrasada!' : isDueToday ? 'Vence hoje!' : 'Data de entrega'"
        >
          <AlertTriangle v-if="isOverdue" class="w-3.5 h-3.5 text-rose-400" />
          <Calendar v-else class="w-3.5 h-3.5" />
          <span>{{ formattedDueDate }}</span>
        </div>

        <!-- Estimated Hours -->
        <div
          v-if="task.estimated_hours"
          class="inline-flex items-center gap-1 text-slate-500"
          :title="`${task.estimated_hours}h estimadas`"
        >
          <Clock class="w-3.5 h-3.5" />
          <span>{{ task.estimated_hours }}h</span>
        </div>

        <!-- Comments Counter -->
        <button
          type="button"
          class="inline-flex items-center gap-1 text-slate-400 hover:text-primary-400 transition-colors"
          :title="`${task.comments_count || 0} comentários`"
          @click.stop="emit('comments-click', task)"
        >
          <MessageSquare class="w-3.5 h-3.5" />
          <span>{{ task.comments_count || 0 }}</span>
        </button>
      </div>

      <!-- Assignee Avatar -->
      <div class="flex items-center">
        <div
          v-if="task.assignee"
          class="w-6 h-6 rounded-full bg-gradient-to-tr from-primary-600 to-indigo-500 text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-slate-900 shadow-sm"
          :title="`Responsável: ${task.assignee.name}`"
        >
          {{ assigneeInitials }}
        </div>
        <div
          v-else
          class="w-6 h-6 rounded-full border border-dashed border-slate-700 flex items-center justify-center text-slate-600 text-[10px]"
          title="Sem responsável"
        >
          -
        </div>
      </div>
    </div>
  </div>
</template>
