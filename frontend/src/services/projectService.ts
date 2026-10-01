import api from './api'
import type { ApiResponse, PaginatedData } from '../types/api'
import type { Project, ProjectMember, ProjectRole } from '../types/project'

export interface ProjectFilterParams {
  q?: string
  status?: string
  sort_by?: string
  direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export const projectService = {
  async getProjects(params: ProjectFilterParams = {}): Promise<ApiResponse<PaginatedData<Project>>> {
    const response = await api.get<ApiResponse<PaginatedData<Project>>>('/projects', { params })
    return response.data
  },

  async getProject(id: number): Promise<ApiResponse<Project>> {
    const response = await api.get<ApiResponse<Project>>(`/projects/${id}`)
    return response.data
  },

  async createProject(data: Record<string, any>): Promise<ApiResponse<Project>> {
    const response = await api.post<ApiResponse<Project>>('/projects', data)
    return response.data
  },

  async updateProject(id: number, data: Record<string, any>): Promise<ApiResponse<Project>> {
    const response = await api.put<ApiResponse<Project>>(`/projects/${id}`, data)
    return response.data
  },

  async deleteProject(id: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`/projects/${id}`)
    return response.data
  },

  async getMembers(projectId: number): Promise<ApiResponse<ProjectMember[]>> {
    const response = await api.get<ApiResponse<ProjectMember[]>>(`/projects/${projectId}/members`)
    return response.data
  },

  async addMember(projectId: number, data: { user_id: number; role: ProjectRole }): Promise<ApiResponse<ProjectMember>> {
    const response = await api.post<ApiResponse<ProjectMember>>(`/projects/${projectId}/members`, data)
    return response.data
  },

  async updateMemberRole(projectId: number, userId: number, role: ProjectRole): Promise<ApiResponse<ProjectMember>> {
    const response = await api.put<ApiResponse<ProjectMember>>(`/projects/${projectId}/members/${userId}`, {
      user_id: userId,
      role,
    })
    return response.data
  },

  async removeMember(projectId: number, userId: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`/projects/${projectId}/members/${userId}`)
    return response.data
  },
}
