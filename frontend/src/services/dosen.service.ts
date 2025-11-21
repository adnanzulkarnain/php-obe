import api from './api';
import type { ApiResponse } from '../types/api';

export interface Dosen {
  id_dosen: number;
  nidn: string;
  id_prodi: string;
  nama: string;
  email: string;
  no_telepon?: string;
  alamat?: string;
  pendidikan_terakhir?: string;
  jabatan_fungsional?: string;
  status: 'aktif' | 'cuti' | 'pensiun' | 'non_aktif';
  created_at?: string;
  updated_at?: string;
}

export const dosenService = {
  // Get all Dosen
  getAll: (params?: {
    id_prodi?: string;
    status?: string;
  }) => {
    return api.get<ApiResponse<Dosen[]>>('/dosen', { params });
  },

  // Get single Dosen by ID
  getById: (id: number) => {
    return api.get<ApiResponse<Dosen>>(`/dosen/${id}`);
  },

  // Get Dosen by NIDN
  getByNidn: (nidn: string) => {
    return api.get<ApiResponse<Dosen>>(`/dosen/nidn/${nidn}`);
  },

  // Create new Dosen
  create: (data: {
    nidn: string;
    id_prodi: string;
    nama: string;
    email: string;
    no_telepon?: string;
    alamat?: string;
    pendidikan_terakhir?: string;
    jabatan_fungsional?: string;
  }) => {
    return api.post<ApiResponse<Dosen>>('/dosen', data);
  },

  // Update existing Dosen
  update: (id: number, data: Partial<Dosen>) => {
    return api.put<ApiResponse<Dosen>>(`/dosen/${id}`, data);
  },

  // Delete Dosen
  delete: (id: number) => {
    return api.delete<ApiResponse<void>>(`/dosen/${id}`);
  },

  // Change Dosen status
  changeStatus: (id: number, status: string) => {
    return api.post<ApiResponse<Dosen>>(`/dosen/${id}/change-status`, { status });
  },

  // Get statistics
  getStatistics: () => {
    return api.get<ApiResponse<any>>('/dosen/statistics');
  },

  // Get teaching load
  getTeachingLoad: () => {
    return api.get<ApiResponse<any>>('/dosen/teaching-load');
  },
};
