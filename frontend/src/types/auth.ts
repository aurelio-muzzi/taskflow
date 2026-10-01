export type UserStatus = 'ACTIVE' | 'INACTIVE'
export type RoleSlug = 'admin' | 'manager' | 'user'

export interface Role {
  id: number
  name: string
  slug: RoleSlug
  description?: string | null
}

export interface User {
  id: number
  name: string
  email: string
  avatar_url?: string | null
  status: UserStatus
  role?: Role
  is_admin: boolean
  is_manager: boolean
  is_user: boolean
  created_at?: string
  updated_at?: string
}

export interface LoginCredentials {
  email: string
  password: string
  device_name?: string
}

export interface AuthData {
  token: string
  user: User
}
