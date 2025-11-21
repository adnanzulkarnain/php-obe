import api from './api';

export interface DashboardSummary {
  total_kelas: number;
  total_mahasiswa: number;
  nilai_diinput: number;
  rata_nilai: number;
}

export interface RecentActivity {
  action: string;
  table_name: string;
  record_id: string;
  created_at: string;
  username: string;
  user_type: string;
}

export interface Alert {
  type: string;
  message: string;
  data: any;
}

export interface DashboardData {
  summary: DashboardSummary;
  recent_activity: RecentActivity[];
  alerts: Alert[];
}

export interface CPMKAchievement {
  id_cpmk: number;
  kode_cpmk: string;
  deskripsi: string;
  jumlah_mahasiswa: number;
  rata_rata_nilai: number;
  nilai_min: number;
  nilai_max: number;
  jumlah_lulus: number;
  persentase_lulus: number;
}

export interface CPLAchievement {
  id_cpl: number;
  kode_cpl: string;
  deskripsi: string;
  kategori: string;
  jumlah_mahasiswa: number;
  rata_rata_nilai: number;
  nilai_min: number;
  nilai_max: number;
  jumlah_lulus: number;
  persentase_lulus: number;
}

export interface TrendData {
  tahun_ajaran: string;
  rata_nilai: number;
  jumlah_mahasiswa: number;
  jumlah_lulus_baik: number;
}

export interface MahasiswaPerformance {
  nim: string;
  enrollments: any[];
  cpmk_achievements: any[];
  cpl_achievements: any[];
  gpa: number;
}

class AnalyticsService {
  /**
   * Get dashboard overview
   */
  async getDashboard(idProdi?: string, tahunAjaran?: string): Promise<DashboardData> {
    const params: any = {};
    if (idProdi) params.id_prodi = idProdi;
    if (tahunAjaran) params.tahun_ajaran = tahunAjaran;

    const response = await api.get('/analytics/dashboard', { params });
    return response.data.data;
  }

  /**
   * Get CPMK achievement report by kelas
   */
  async getCPMKReportByKelas(idKelas: number): Promise<{
    kelas: any;
    cpmk_achievements: CPMKAchievement[];
  }> {
    const response = await api.get(`/analytics/kelas/${idKelas}/cpmk-report`);
    return response.data.data;
  }

  /**
   * Get CPL achievement report by kurikulum
   */
  async getCPLReportByKurikulum(
    idKurikulum: number,
    angkatan?: string
  ): Promise<{
    id_kurikulum: number;
    angkatan: string;
    cpl_achievements: CPLAchievement[];
  }> {
    const params: any = {};
    if (angkatan) params.angkatan = angkatan;

    const response = await api.get(`/analytics/kurikulum/${idKurikulum}/cpl-report`, {
      params,
    });
    return response.data.data;
  }

  /**
   * Get mahasiswa performance detail
   */
  async getMahasiswaPerformance(nim: string): Promise<MahasiswaPerformance> {
    const response = await api.get(`/analytics/mahasiswa/${nim}/performance`);
    return response.data.data;
  }

  /**
   * Get trending data for analytics
   */
  async getTrends(
    idProdi?: string,
    startYear?: number,
    endYear?: number
  ): Promise<{ trends: TrendData[] }> {
    const params: any = {};
    if (idProdi) params.id_prodi = idProdi;
    if (startYear) params.start_year = startYear;
    if (endYear) params.end_year = endYear;

    const response = await api.get('/analytics/trends', { params });
    return response.data.data;
  }
}

export default new AnalyticsService();
