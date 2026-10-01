import api from './api'
import type { ApiResponse, PaginatedData } from '../types/api'
import type { CreateTaskPayload, Task, TaskFilterParams, TaskStatus, UpdateTaskPayload } from '../types/task'

export const taskService = {
  /**
   * Lista tarefas com busca, filtros, ordenação e paginação.
   */
  async getTasks(params: TaskFilterParams = {}): Promise<ApiResponse<PaginatedData<Task>>> {
    const response = await api.get<ApiResponse<PaginatedData<Task>>>('/tasks', { params })
    return response.data
  },

  /**
   * Lista tarefas de um projeto específico.
   */
  async getProjectTasks(projectId: number, params: TaskFilterParams = {}): Promise<ApiResponse<PaginatedData<Task> | Task[]>> {
    const response = await api.get<ApiResponse<PaginatedData<Task> | Task[]>>(`/projects/${projectId}/tasks`, { params })
    return response.data
  },

  /**
   * Obtém detalhes de uma tarefa específica.
   */
  async getTask(id: number): Promise<ApiResponse<Task>> {
    const response = await api.get<ApiResponse<Task>>(`/tasks/${id}`)
    return response.data
  },

  /**
   * Cria uma nova tarefa associada ao projeto.
   */
  async createTask(projectId: number, data: CreateTaskPayload): Promise<ApiResponse<Task>> {
    const response = await api.post<ApiResponse<Task>>(`/projects/${projectId}/tasks`, data)
    return response.data
  },

  /**
   * Atualiza dados de uma tarefa existente.
   */
  async updateTask(id: number, data: UpdateTaskPayload): Promise<ApiResponse<Task>> {
    const response = await api.put<ApiResponse<Task>>(`/tasks/${id}`, data)
    return response.data
  },

  /**
   * Atualiza rapidamente o status e/ou ordenação de uma tarefa.
   */
  async updateTaskStatus(id: number, status: TaskStatus, order?: number): Promise<ApiResponse<Task>> {
    const response = await api.patch<ApiResponse<Task>>(`/tasks/${id}/status`, { status, order })
    return response.data
  },

  /**
   * Remove (soft delete) uma tarefa.
   */
  async deleteTask(id: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`/tasks/${id}`)
    return response.data
  }
}
