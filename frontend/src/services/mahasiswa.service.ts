import api from './api';
import type { ApiResponse } from '../types/api';

export interface Mahasiswa {
  nim: string;
  id_prodi: string;
  id_kurikulum: number;
  nama: string;
  email: string;
  tanggal_lahir?: string;
  jenis_kelamin?: 'L' | 'P';
  alamat?: string;
  no_telepon?: string;
  angkatan: number;
  status: 'aktif' | 'cuti' | 'lulus' | 'keluar' | 'non_aktif';
  created_at?: string;
  updated_at?: string;
}

export const mahasiswaService = {
  // Get all Mahasiswa
  getAll: (params?: {
    id_prodi?: string;
    angkatan?: number;
    status?: string;
    id_kurikulum?: number;
  }) => {
    return api.get<ApiResponse<Mahasiswa[]>>('/mahasiswa', { params });
  },

  // Get single Mahasiswa by NIM
  getByNim: (nim: string) => {
    return api.get<ApiResponse<Mahasiswa>>(`/mahasiswa/${nim}`);
  },

  // Create new Mahasiswa
  create: (data: {
    nim: string;
    id_prodi: string;
    id_kurikulum: number;
    nama: string;
    email: string;
    tanggal_lahir?: string;
    jenis_kelamin?: 'L' | 'P';
    alamat?: string;
    no_telepon?: string;
    angkatan: number;
  }) => {
    return api.post<ApiResponse<Mahasiswa>>('/mahasiswa', data);
  },

  // Update existing Mahasiswa
  update: (nim: string, data: Partial<Mahasiswa>) => {
    return api.put<ApiResponse<Mahasiswa>>(`/mahasiswa/${nim}`, data);
  },

  // Delete Mahasiswa
  delete: (nim: string) => {
    return api.delete<ApiResponse<void>>(`/mahasiswa/${nim}`);
  },

  // Change Mahasiswa status
  changeStatus: (nim: string, status: string) => {
    return api.post<ApiResponse<Mahasiswa>>(`/mahasiswa/${nim}/change-status`, { status });
  },

  // Get statistics
  getStatistics: () => {
    return api.get<ApiResponse<any>>('/mahasiswa/statistics');
  },
};
