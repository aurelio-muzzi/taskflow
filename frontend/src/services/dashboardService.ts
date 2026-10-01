import api from './api'
import type { ApiResponse } from '../types/api'
import type { DashboardMetrics, GlobalSearchResults } from '../types/dashboard'

export const dashboardService = {
  /**
   * Obtém as métricas consolidadas do dashboard.
   */
  async getMetrics(): Promise<ApiResponse<DashboardMetrics>> {
    const response = await api.get<ApiResponse<DashboardMetrics>>('/dashboard/metrics')
    return response.data
  },

  /**
   * Realiza busca combinada em projetos, tarefas e usuários.
   */
  async globalSearch(q: string, limit = 5): Promise<ApiResponse<GlobalSearchResults>> {
    const response = await api.get<ApiResponse<GlobalSearchResults>>('/search', {
      params: { q, limit }
    })
    return response.data
  }
}
