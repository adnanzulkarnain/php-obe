import { api } from './api';
import type { Kurikulum, ApiResponse } from '../types/api';

export const kurikulumService = {
  async getAll(): Promise<ApiResponse<Kurikulum[]>> {
    const response = await api.get<ApiResponse<Kurikulum[]>>('/kurikulum');
    return response.data;
  },

  async getById(id: number): Promise<ApiResponse<Kurikulum>> {
    const response = await api.get<ApiResponse<Kurikulum>>(`/kurikulum/${id}`);
    return response.data;
  },

  async create(data: Partial<Kurikulum>): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>('/kurikulum', data);
    return response.data;
  },

  async update(id: number, data: Partial<Kurikulum>): Promise<ApiResponse<any>> {
    const response = await api.put<ApiResponse<any>>(`/kurikulum/${id}`, data);
    return response.data;
  },

  async approve(id: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(`/kurikulum/${id}/approve`);
    return response.data;
  },

  async activate(id: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(`/kurikulum/${id}/activate`);
    return response.data;
  },

  async deactivate(id: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(`/kurikulum/${id}/deactivate`);
    return response.data;
  },

  async compare(ids: number[]): Promise<ApiResponse<any>> {
    const response = await api.get<ApiResponse<any>>(`/kurikulum/compare?ids=${ids.join(',')}`);
    return response.data;
  },
};
