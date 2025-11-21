import api from './api';
import type { ApiResponse } from '../types/api';

export interface RPS {
  id_rps: number;
  kode_mk: string;
  id_kurikulum: number;
  semester_berlaku: string; // 'Ganjil' | 'Genap'
  tahun_ajaran: string; // '2024/2025'
  status: 'draft' | 'submitted' | 'revised' | 'approved' | 'active' | 'archived';
  ketua_pengembang?: string;
  tanggal_disusun?: string;
  deskripsi_mk?: string;
  deskripsi_singkat?: string;
  created_by?: string;
  created_at?: string;
  updated_at?: string;
  // Additional fields from joins
  nama_mk?: string;
  sks?: number;
  nama_ketua?: string;
  current_version?: string;
}

export interface CreateRPSData {
  kode_mk: string;
  id_kurikulum: number;
  semester_berlaku: string;
  tahun_ajaran: string;
  ketua_pengembang?: string;
  tanggal_disusun?: string;
  deskripsi_mk?: string;
  deskripsi_singkat?: string;
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
  create: (data: CreateRPSData) => {
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

  // Process approval (approve/reject)
  processApproval: (idApproval: number, action: 'approve' | 'reject', catatan?: string) => {
    return api.post<ApiResponse<any>>(`/rps/approval/${idApproval}`, { action, catatan });
  },

  // Get RPS versions
  getVersions: (idRps: number) => {
    return api.get<ApiResponse<any[]>>(`/rps/${idRps}/versions`);
  },

  // Get statistics
  getStatistics: () => {
    return api.get<ApiResponse<any>>('/rps/statistics');
  },
};
