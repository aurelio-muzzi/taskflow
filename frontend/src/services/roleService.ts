import api from './api'
import type { ApiResponse } from '../types/api'
import type { Role } from '../types/auth'

export const roleService = {
  async getRoles(): Promise<ApiResponse<Role[]>> {
    const response = await api.get<ApiResponse<Role[]>>('/roles')
    return response.data
  },
}
