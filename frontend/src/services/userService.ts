import api from './api'
import type { ApiResponse, PaginatedData } from '../types/api'
import type { User } from '../types/auth'

export interface UserFilterParams {
  q?: string
  status?: string
  role?: string
  role_id?: number
  sort_by?: string
  direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export const userService = {
  async getUsers(params: UserFilterParams = {}): Promise<ApiResponse<PaginatedData<User>>> {
    const response = await api.get<ApiResponse<PaginatedData<User>>>('/users', { params })
    return response.data
  },

  async getUser(id: number): Promise<ApiResponse<User>> {
    const response = await api.get<ApiResponse<User>>(`/users/${id}`)
    return response.data
  },

  async createUser(data: Record<string, any>): Promise<ApiResponse<User>> {
    const response = await api.post<ApiResponse<User>>('/users', data)
    return response.data
  },

  async updateUser(id: number, data: Record<string, any>): Promise<ApiResponse<User>> {
    const response = await api.put<ApiResponse<User>>(`/users/${id}`, data)
    return response.data
  },

  async toggleStatus(id: number): Promise<ApiResponse<User>> {
    const response = await api.patch<ApiResponse<User>>(`/users/${id}/toggle-status`)
    return response.data
  },

  async deleteUser(id: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`/users/${id}`)
    return response.data
  },
}
