import api from './api';
import type {
  ApiResponse,
  RealisasiPertemuan,
  Kehadiran,
  KehadiranSummary,
  RealisasiStatistics,
  RPSComparison
} from '../types/api';

export interface CreateRealisasiData {
  id_kelas: number;
  id_minggu?: number;
  tanggal_pelaksanaan: string;
  materi_disampaikan?: string;
  metode_digunakan?: string;
  kendala?: string;
  catatan_dosen?: string;
  kehadiran?: Kehadiran[];
}

export interface UpdateRealisasiData {
  id_minggu?: number;
  tanggal_pelaksanaan?: string;
  materi_disampaikan?: string;
  metode_digunakan?: string;
  kendala?: string;
  catatan_dosen?: string;
  kehadiran?: Kehadiran[];
}

export interface RealisasiFilters {
  id_kelas?: number;
  id_dosen?: string;
  status?: 'draft' | 'submitted' | 'verified' | 'rejected';
  minggu_ke?: number;
  tanggal_dari?: string;
  tanggal_sampai?: string;
}

export interface VerifyData {
  approved: boolean;
  komentar?: string;
}

export const realisasiPertemuanService = {
  // Get all realisasi with filters
  getAll: (filters?: RealisasiFilters) => {
    return api.get<ApiResponse<RealisasiPertemuan[]>>('/realisasi-pertemuan', {
      params: filters
    });
  },

  // Get single realisasi by ID with full details
  getById: (id: number) => {
    return api.get<ApiResponse<RealisasiPertemuan>>(`/realisasi-pertemuan/${id}`);
  },

  // Create new berita acara
  create: (data: CreateRealisasiData) => {
    return api.post<ApiResponse<RealisasiPertemuan>>('/realisasi-pertemuan', data);
  },

  // Update existing berita acara
  update: (id: number, data: UpdateRealisasiData) => {
    return api.put<ApiResponse<RealisasiPertemuan>>(`/realisasi-pertemuan/${id}`, data);
  },

  // Delete berita acara
  delete: (id: number) => {
    return api.delete<ApiResponse<void>>(`/realisasi-pertemuan/${id}`);
  },

  // Submit berita acara for verification
  submit: (id: number) => {
    return api.post<ApiResponse<RealisasiPertemuan>>(`/realisasi-pertemuan/${id}/submit`);
  },

  // Verify berita acara (kaprodi)
  verify: (id: number, data: VerifyData) => {
    return api.post<ApiResponse<RealisasiPertemuan>>(`/realisasi-pertemuan/${id}/verify`, data);
  },

  // Get pending verifications for kaprodi
  getPendingVerification: () => {
    return api.get<ApiResponse<RealisasiPertemuan[]>>('/realisasi-pertemuan/pending-verification');
  },

  // Compare berita acara with RPS plan
  compareWithRPS: (id: number) => {
    return api.get<ApiResponse<RPSComparison>>(`/realisasi-pertemuan/${id}/compare-rps`);
  },

  // Export berita acara to PDF
  exportPDF: (id: number) => {
    return api.get(`/realisasi-pertemuan/${id}/export-pdf`, {
      responseType: 'blob'
    });
  },

  // Get statistics by kelas
  getStatisticsByKelas: (idKelas: number) => {
    return api.get<ApiResponse<RealisasiStatistics>>(`/kelas/${idKelas}/realisasi-statistics`);
  },

  // Get statistics by dosen
  getStatisticsByDosen: (idDosen: string) => {
    return api.get<ApiResponse<RealisasiStatistics>>(`/dosen/${idDosen}/realisasi-statistics`);
  },

  // Get kehadiran (attendance) by realisasi
  getKehadiran: (idRealisasi: number) => {
    return api.get<ApiResponse<{
      kehadiran: Kehadiran[];
      summary: KehadiranSummary;
    }>>(`/realisasi-pertemuan/${idRealisasi}/kehadiran`);
  },

  // Get kehadiran statistics by kelas
  getKehadiranStatistics: (idKelas: number) => {
    return api.get<ApiResponse<any[]>>(`/kelas/${idKelas}/kehadiran-statistics`);
  },
};
