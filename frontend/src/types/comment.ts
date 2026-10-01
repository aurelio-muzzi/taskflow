import type { User } from './auth'

export interface TaskComment {
  id: number
  task_id: number
  user_id: number
  user?: User
  content: string
  created_at: string
  updated_at: string
}
