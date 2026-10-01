import type { User } from './auth'

export type ProjectStatus = 'PLANNING' | 'ACTIVE' | 'ON_HOLD' | 'COMPLETED' | 'ARCHIVED'
export type ProjectRole = 'OWNER' | 'MANAGER' | 'MEMBER' | 'VIEWER'

export interface ProjectMember {
  id: number
  project_id: number
  user_id: number
  role: ProjectRole
  role_label: string
  user?: User
  joined_at?: string
}

export interface Project {
  id: number
  name: string
  code: string
  description?: string | null
  status: ProjectStatus
  status_label: string
  start_date?: string | null
  due_date?: string | null
  owner_id: number
  owner?: User
  members_count?: number
  members?: ProjectMember[]
  current_user_role?: ProjectRole | null
  can_manage?: boolean
  created_at?: string
  updated_at?: string
}
