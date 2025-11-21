import api from './api';

export interface Kelas {
  id_kelas: number;
  id_kurikulum: number;
  kode_mk: string;
  nama_kelas: string;
  semester: number;
  tahun_ajaran: string;
  kapasitas: number;
  status_kelas: 'draft' | 'buka' | 'berlangsung' | 'selesai' | 'batal';
  id_rps?: number;
  created_at?: string;
  updated_at?: string;
  nama_mk?: string;
  nama_kurikulum?: string;
  jumlah_mahasiswa?: number;
}

export interface CreateKelasDTO {
  id_kurikulum: number;
  kode_mk: string;
  nama_kelas: string;
  semester: number;
  tahun_ajaran: string;
  kapasitas: number;
  id_rps?: number;
}

export interface UpdateKelasDTO {
  nama_kelas?: string;
  semester?: number;
  tahun_ajaran?: string;
  kapasitas?: number;
  id_rps?: number;
}

export interface TeachingAssignment {
  id_tugas_mengajar: number;
  id_kelas: number;
  id_dosen: number;
  peran: 'koordinator' | 'pengampu' | 'asisten';
  created_at: string;
  nama_dosen?: string;
  nidn?: string;
}

export interface DosenKelas {
  id_kelas: number;
  nama_kelas: string;
  nama_mk: string;
  semester: number;
  tahun_ajaran: string;
  peran: string;
  status_kelas: string;
}

export interface TeachingLoadStats {
  total_kelas: number;
  total_sks: number;
  by_semester: Array<{
    semester: number;
    tahun_ajaran: string;
    jumlah_kelas: number;
    total_sks: number;
  }>;
}

export interface KelasStatistics {
  total_kelas: number;
  by_status: Record<string, number>;
  by_semester: Record<string, number>;
  by_tahun_ajaran: Record<string, number>;
}

class KelasService {
  /**
   * Get all kelas with optional filters
   */
  async getAll(params?: {
    id_kurikulum?: number;
    semester?: number;
    tahun_ajaran?: string;
    status_kelas?: string;
    id_dosen?: number;
  }): Promise<Kelas[]> {
    const response = await api.get('/kelas', { params });
    return response.data.data;
  }

  /**
   * Get kelas by ID
   */
  async getById(id: number): Promise<Kelas> {
    const response = await api.get(`/kelas/${id}`);
    return response.data.data;
  }

  /**
   * Create new kelas
   */
  async create(data: CreateKelasDTO): Promise<Kelas> {
    const response = await api.post('/kelas', data);
    return response.data.data;
  }

  /**
   * Update kelas
   */
  async update(id: number, data: UpdateKelasDTO): Promise<Kelas> {
    const response = await api.put(`/kelas/${id}`, data);
    return response.data.data;
  }

  /**
   * Delete kelas
   */
  async delete(id: number): Promise<void> {
    await api.delete(`/kelas/${id}`);
  }

  /**
   * Change kelas status
   */
  async changeStatus(id: number, status: string): Promise<Kelas> {
    const response = await api.post(`/kelas/${id}/status`, { status });
    return response.data.data;
  }

  /**
   * Get kelas statistics
   */
  async getStatistics(params?: {
    id_kurikulum?: number;
    tahun_ajaran?: string;
  }): Promise<KelasStatistics> {
    const response = await api.get('/kelas/statistics', { params });
    return response.data.data;
  }

  /**
   * Get teaching assignments for a kelas
   */
  async getTeachingAssignments(idKelas: number): Promise<TeachingAssignment[]> {
    const response = await api.get(`/kelas/${idKelas}/dosen`);
    return response.data.data;
  }

  /**
   * Assign dosen to kelas
   */
  async assignDosen(
    idKelas: number,
    idDosen: number,
    peran: 'koordinator' | 'pengampu' | 'asisten'
  ): Promise<TeachingAssignment> {
    const response = await api.post(`/kelas/${idKelas}/dosen`, {
      id_dosen: idDosen,
      peran,
    });
    return response.data.data;
  }

  /**
   * Update dosen peran in kelas
   */
  async updateDosenPeran(
    idKelas: number,
    idDosen: number,
    peran: 'koordinator' | 'pengampu' | 'asisten'
  ): Promise<TeachingAssignment> {
    const response = await api.put(`/kelas/${idKelas}/dosen/${idDosen}`, { peran });
    return response.data.data;
  }

  /**
   * Remove dosen from kelas
   */
  async removeDosen(idKelas: number, idDosen: number): Promise<void> {
    await api.delete(`/kelas/${idKelas}/dosen/${idDosen}`);
  }

  /**
   * Get kelas taught by a dosen
   */
  async getDosenKelas(idDosen: number, params?: {
    tahun_ajaran?: string;
    semester?: number;
  }): Promise<DosenKelas[]> {
    const response = await api.get(`/dosen/${idDosen}/kelas`, { params });
    return response.data.data;
  }

  /**
   * Get teaching load statistics for a dosen
   */
  async getTeachingLoadStats(idDosen: number): Promise<TeachingLoadStats> {
    const response = await api.get(`/dosen/${idDosen}/teaching-load`);
    return response.data.data;
  }

  /**
   * Get enrollments for a kelas
   */
  async getEnrollments(idKelas: number): Promise<any[]> {
    const response = await api.get(`/kelas/${idKelas}/enrollment`);
    return response.data.data;
  }

  /**
   * Get enrollment statistics for a kelas
   */
  async getEnrollmentStatistics(idKelas: number): Promise<any> {
    const response = await api.get(`/kelas/${idKelas}/statistics`);
    return response.data.data;
  }
}

export default new KelasService();
