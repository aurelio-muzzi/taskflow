import type { Task } from './task'
import type { AuditLog } from './audit'
import type { Project } from './project'
import type { User } from './auth'

export interface ProjectMetrics {
  total: number
  active: number
  planning: number
  completed: number
  on_hold: number
  archived: number
  by_status: Record<string, number>
}

export interface TaskMetrics {
  total: number
  pending: number
  completed: number
  overdue: number
  completion_rate: number
  by_status: {
    todo: number
    in_progress: number
    review: number
    done: number
  }
  by_priority: {
    low: number
    medium: number
    high: number
    urgent: number
  }
}

export interface MyTaskMetrics {
  total: number
  pending: number
  completed: number
  overdue: number
  by_status: {
    todo: number
    in_progress: number
    review: number
    done: number
  }
}

export interface DashboardMetrics {
  projects: ProjectMetrics
  tasks: TaskMetrics
  my_tasks: MyTaskMetrics
  upcoming_deadlines: Task[]
  recent_activities: AuditLog[]
}

export interface GlobalSearchResults {
  projects: Project[]
  tasks: Task[]
  users: User[]
}
