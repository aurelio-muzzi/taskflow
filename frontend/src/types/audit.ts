import type { User } from './auth'

export interface AuditLog {
  id: number
  user_id: number | null
  user?: User | null
  auditable_type: string
  auditable_name: string
  auditable_id: number
  event: string
  description: string | null
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
}

export interface AuditLogFilterParams {
  event?: string
  auditable_type?: string
  user_id?: number
  page?: number
  per_page?: number
}
