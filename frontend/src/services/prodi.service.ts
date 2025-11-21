import api from './api';
import type { ApiResponse } from '../types/api';

export interface Prodi {
  id_prodi: string;
  id_fakultas: string;
  nama_prodi: string;
  jenjang: 'D3' | 'D4' | 'S1' | 'S2' | 'S3';
  kode_prodi?: string;
  akreditasi?: string;
  created_at?: string;
  updated_at?: string;
}

export const prodiService = {
  // Get all Prodi
  getAll: (params?: { id_fakultas?: string; jenjang?: string }) => {
    return api.get<ApiResponse<Prodi[]>>('/prodi', { params });
  },

  // Get single Prodi by ID
  getById: (id: string) => {
    return api.get<ApiResponse<Prodi>>(`/prodi/${id}`);
  },

  // Create new Prodi
  create: (data: {
    id_prodi: string;
    id_fakultas: string;
    nama_prodi: string;
    jenjang: string;
    kode_prodi?: string;
    akreditasi?: string;
  }) => {
    return api.post<ApiResponse<Prodi>>('/prodi', data);
  },

  // Update existing Prodi
  update: (id: string, data: Partial<Prodi>) => {
    return api.put<ApiResponse<Prodi>>(`/prodi/${id}`, data);
  },

  // Delete Prodi
  delete: (id: string) => {
    return api.delete<ApiResponse<void>>(`/prodi/${id}`);
  },
};
