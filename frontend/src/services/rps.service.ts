import api from './api';
import type { ApiResponse } from '../types/api';

export interface RPS {
  id_rps: number;
  kode_mk: string;
  id_kurikulum: number;
  id_dosen: number;
  tahun_akademik: string;
  semester: string;
  status: 'draft' | 'submitted' | 'approved' | 'active' | 'archived';
  versi: number;
  tanggal_berlaku?: string;
  created_at?: string;
  updated_at?: string;
}

export const rpsService = {
  // Get all RPS
  getAll: (params?: { kode_mk?: string; id_kurikulum?: number; status?: string }) => {
    return api.get<ApiResponse<RPS[]>>('/rps', { params });
  },

  // Get single RPS by ID
  getById: (id: number) => {
    return api.get<ApiResponse<RPS>>(`/rps/${id}`);
  },

  // Create new RPS
  create: (data: {
    kode_mk: string;
    id_kurikulum: number;
    id_dosen: number;
    tahun_akademik: string;
    semester: string;
  }) => {
    return api.post<ApiResponse<RPS>>('/rps', data);
  },

  // Update existing RPS
  update: (id: number, data: Partial<RPS>) => {
    return api.put<ApiResponse<RPS>>(`/rps/${id}`, data);
  },

  // Delete RPS
  delete: (id: number) => {
    return api.delete<ApiResponse<void>>(`/rps/${id}`);
  },

  // Submit RPS for approval
  submit: (id: number) => {
    return api.post<ApiResponse<RPS>>(`/rps/${id}/submit`);
  },

  // Activate RPS
  activate: (id: number) => {
    return api.post<ApiResponse<RPS>>(`/rps/${id}/activate`);
  },

  // Archive RPS
  archive: (id: number) => {
    return api.post<ApiResponse<RPS>>(`/rps/${id}/archive`);
  },

  // Get pending approvals
  getPendingApprovals: () => {
    return api.get<ApiResponse<RPS[]>>('/rps/pending-approvals');
  },

  // Get statistics
  getStatistics: () => {
    return api.get<ApiResponse<any>>('/rps/statistics');
  },
};
