import api from './api'
import type { ApiResponse } from '../types/api'
import type { TaskComment } from '../types/comment'

export const commentService = {
  /**
   * Lista todos os comentários de uma tarefa.
   */
  async getComments(taskId: number): Promise<ApiResponse<TaskComment[]>> {
    const response = await api.get<ApiResponse<TaskComment[]>>(`/tasks/${taskId}/comments`)
    return response.data
  },

  /**
   * Adiciona um novo comentário à tarefa.
   */
  async createComment(taskId: number, content: string): Promise<ApiResponse<TaskComment>> {
    const response = await api.post<ApiResponse<TaskComment>>(`/tasks/${taskId}/comments`, { content })
    return response.data
  },

  /**
   * Exclui um comentário.
   */
  async deleteComment(commentId: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`/comments/${commentId}`)
    return response.data
  }
}
