import api from './api';
import type { ApiResponse } from '../types/api';

export interface KomponenPenilaian {
  id_komponen: number;
  id_kelas: number;
  id_template: number;
  nama_komponen: string;
  bobot: number;
  id_cpmk?: number;
  deskripsi?: string;
  tanggal_penilaian?: string;
  created_at?: string;
}

export interface TemplatePenilaian {
  id_template: number;
  id_rps: number;
  nama_template: string;
  jenis_penilaian: string;
  bobot_template: number;
  created_at?: string;
}

export interface Nilai {
  id_nilai: number;
  id_enrollment: number;
  id_komponen: number;
  nilai: number;
  catatan?: string;
  created_at?: string;
}

export const penilaianService = {
  // Template Penilaian
  getTemplatesByRPS: (id_rps: number) => {
    return api.get<ApiResponse<TemplatePenilaian[]>>(`/rps/${id_rps}/template-penilaian`);
  },

  createTemplate: (data: {
    id_rps: number;
    nama_template: string;
    jenis_penilaian: string;
    bobot_template: number;
  }) => {
    return api.post<ApiResponse<TemplatePenilaian>>('/template-penilaian', data);
  },

  // Komponen Penilaian
  getKomponenByKelas: (id_kelas: number) => {
    return api.get<ApiResponse<KomponenPenilaian[]>>(`/kelas/${id_kelas}/komponen-penilaian`);
  },

  createKomponen: (data: {
    id_kelas: number;
    id_template: number;
    nama_komponen: string;
    bobot: number;
    id_cpmk?: number;
    deskripsi?: string;
  }) => {
    return api.post<ApiResponse<KomponenPenilaian>>('/komponen-penilaian', data);
  },

  updateKomponen: (id: number, data: Partial<KomponenPenilaian>) => {
    return api.put<ApiResponse<KomponenPenilaian>>(`/komponen-penilaian/${id}`, data);
  },

  deleteKomponen: (id: number) => {
    return api.delete<ApiResponse<void>>(`/komponen-penilaian/${id}`);
  },

  // Nilai Input
  inputNilai: (data: {
    id_enrollment: number;
    id_komponen: number;
    nilai: number;
    catatan?: string;
  }) => {
    return api.post<ApiResponse<Nilai>>('/nilai', data);
  },

  bulkInputNilai: (data: Array<{
    id_enrollment: number;
    id_komponen: number;
    nilai: number;
  }>) => {
    return api.post<ApiResponse<any>>('/nilai/bulk', { nilai_data: data });
  },

  getNilaiByEnrollment: (id_enrollment: number) => {
    return api.get<ApiResponse<Nilai[]>>(`/enrollment/${id_enrollment}/nilai`);
  },

  getNilaiByKomponen: (id_komponen: number) => {
    return api.get<ApiResponse<Nilai[]>>(`/komponen-penilaian/${id_komponen}/nilai`);
  },

  // Summary & Statistics
  getNilaiSummaryByKelas: (id_kelas: number) => {
    return api.get<ApiResponse<any>>(`/kelas/${id_kelas}/nilai-summary`);
  },

  getKomponenStatistics: (id_komponen: number) => {
    return api.get<ApiResponse<any>>(`/komponen-penilaian/${id_komponen}/statistics`);
  },

  // Jenis Penilaian
  getAllJenisPenilaian: () => {
    return api.get<ApiResponse<any[]>>('/jenis-penilaian');
  },
};
