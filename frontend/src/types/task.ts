import type { User } from './auth'
import type { Project } from './project'

export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done'
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface Task {
  id: number
  project_id: number
  project?: Project
  title: string
  description: string | null
  status: TaskStatus
  status_label: string
  priority: TaskPriority
  priority_label: string
  assigned_to: number | null
  assignee?: User | null
  created_by: number
  creator?: User | null
  due_date: string | null
  estimated_hours: number | null
  order: number
  completed_at: string | null
  created_at: string
  updated_at: string
}

export interface TaskFilterParams {
  q?: string
  project_id?: number
  status?: string
  priority?: string
  assigned_to?: number
  sort_by?: string
  direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
  all?: boolean
}

export interface CreateTaskPayload {
  title: string
  description?: string | null
  status?: TaskStatus
  priority?: TaskPriority
  assigned_to?: number | null
  due_date?: string | null
  estimated_hours?: number | null
  order?: number
}

export interface UpdateTaskPayload {
  title?: string
  description?: string | null
  status?: TaskStatus
  priority?: TaskPriority
  assigned_to?: number | null
  due_date?: string | null
  estimated_hours?: number | null
  order?: number
}
