import api from './api'
import type { ApiResponse } from '../types/api'
import type { AuthData, LoginCredentials, User } from '../types/auth'

export const authService = {
  async login(credentials: LoginCredentials): Promise<ApiResponse<AuthData>> {
    const response = await api.post<ApiResponse<AuthData>>('/auth/login', credentials)
    return response.data
  },

  async logout(): Promise<ApiResponse<null>> {
    const response = await api.post<ApiResponse<null>>('/auth/logout')
    return response.data
  },

  async getMe(): Promise<ApiResponse<User>> {
    const response = await api.get<ApiResponse<User>>('/auth/me')
    return response.data
  },

  async updateProfile(data: { name: string; email: string; avatar_path?: string }): Promise<ApiResponse<User>> {
    const response = await api.put<ApiResponse<User>>('/auth/profile', data)
    return response.data
  },

  async changePassword(data: { current_password: string; password: string; password_confirmation: string }): Promise<ApiResponse<null>> {
    const response = await api.put<ApiResponse<null>>('/auth/change-password', data)
    return response.data
  },
}
