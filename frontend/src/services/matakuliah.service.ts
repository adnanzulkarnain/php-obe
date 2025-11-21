import api from './api';
import type { ApiResponse, MataKuliah } from '../types/api';

export const mataKuliahService = {
  // Get all Mata Kuliah
  getAll: (params?: { id_kurikulum?: number }) => {
    return api.get<ApiResponse<MataKuliah[]>>('/matakuliah', { params });
  },

  // Create new Mata Kuliah
  create: (data: {
    kode_mk: string;
    id_kurikulum: number;
    nama_mk: string;
    sks: number;
    semester: number;
    jenis_mk: 'wajib' | 'pilihan';
    deskripsi?: string;
  }) => {
    return api.post<ApiResponse<MataKuliah>>('/matakuliah', data);
  },

  // Update existing Mata Kuliah
  update: (kode_mk: string, id_kurikulum: number, data: {
    nama_mk?: string;
    sks?: number;
    semester?: number;
    jenis_mk?: 'wajib' | 'pilihan';
    deskripsi?: string;
  }) => {
    return api.put<ApiResponse<MataKuliah>>(`/matakuliah/${kode_mk}/${id_kurikulum}`, data);
  },

  // Delete Mata Kuliah
  delete: (kode_mk: string, id_kurikulum: number) => {
    return api.delete<ApiResponse<void>>(`/matakuliah/${kode_mk}/${id_kurikulum}`);
  },
};
