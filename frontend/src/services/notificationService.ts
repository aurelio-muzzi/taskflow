import api from './api'
import type { ApiResponse } from '../types/api'
import type { InternalNotification, NotificationListResponse } from '../types/notification'

export const notificationService = {
  /**
   * Obtém a lista de notificações e total de não lidas.
   */
  async getNotifications(): Promise<ApiResponse<NotificationListResponse>> {
    const response = await api.get<ApiResponse<NotificationListResponse>>('/notifications')
    return response.data
  },

  /**
   * Marca uma notificação como lida.
   */
  async markAsRead(id: string): Promise<ApiResponse<InternalNotification>> {
    const response = await api.patch<ApiResponse<InternalNotification>>(`/notifications/${id}/read`)
    return response.data
  },

  /**
   * Marca todas as notificações como lidas.
   */
  async markAllAsRead(): Promise<ApiResponse<null>> {
    const response = await api.post<ApiResponse<null>>('/notifications/mark-all-read')
    return response.data
  }
}
