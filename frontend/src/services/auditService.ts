import api from './api'
import type { ApiResponse, PaginatedData } from '../types/api'
import type { AuditLog, AuditLogFilterParams } from '../types/audit'

export const auditService = {
  /**
   * Lista a trilha geral de auditoria com paginação e filtros.
   */
  async getAuditLogs(params: AuditLogFilterParams = {}): Promise<ApiResponse<PaginatedData<AuditLog>>> {
    const response = await api.get<ApiResponse<PaginatedData<AuditLog>>>('/audit-logs', { params })
    return response.data
  },

  /**
   * Lista o histórico de alterações de uma tarefa específica.
   */
  async getTaskAuditLogs(taskId: number): Promise<ApiResponse<AuditLog[]>> {
    const response = await api.get<ApiResponse<AuditLog[]>>(`/tasks/${taskId}/audit-logs`)
    return response.data
  }
}
