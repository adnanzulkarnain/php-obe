import api from './api';
import type { ApiResponse, CPL } from '../types/api';

export const cplService = {
  // Get all CPL
  getAll: (params?: { id_kurikulum?: number }) => {
    return api.get<ApiResponse<CPL[]>>('/cpl', { params });
  },

  // Get single CPL by ID
  getById: (id: number) => {
    return api.get<ApiResponse<CPL>>(`/cpl/${id}`);
  },

  // Create new CPL
  create: (data: {
    id_kurikulum: number;
    kode_cpl: string;
    deskripsi: string;
    kategori: string;
  }) => {
    return api.post<ApiResponse<CPL>>('/cpl', data);
  },

  // Update existing CPL
  update: (id: number, data: {
    kode_cpl?: string;
    deskripsi?: string;
    kategori?: string;
  }) => {
    return api.put<ApiResponse<CPL>>(`/cpl/${id}`, data);
  },

  // Delete CPL
  delete: (id: number) => {
    return api.delete<ApiResponse<void>>(`/cpl/${id}`);
  },
};
