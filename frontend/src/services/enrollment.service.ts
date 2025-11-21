import api from './api';

export interface Enrollment {
  id_enrollment: number;
  nim: string;
  id_kelas: number;
  status_enrollment: 'aktif' | 'lulus' | 'tidak_lulus' | 'mengulang' | 'batal';
  nilai_akhir?: number;
  nilai_huruf?: string;
  tanggal_enrollment: string;
  tanggal_drop?: string;
  nama_mahasiswa?: string;
  nama_kelas?: string;
  nama_mk?: string;
  kode_mk?: string;
  semester?: number;
  tahun_ajaran?: string;
}

export interface EnrollDTO {
  nim: string;
  id_kelas: number;
}

export interface BulkEnrollDTO {
  id_kelas: number;
  nim_list: string[];
}

export interface UpdateGradesDTO {
  nilai_akhir: number;
  nilai_huruf: string;
}

export interface KRS {
  semester: number;
  tahun_ajaran: string;
  enrollments: Enrollment[];
  total_sks: number;
}

export interface Transcript {
  nim: string;
  nama_mahasiswa: string;
  enrollments: Enrollment[];
  gpa: number;
  total_sks: number;
  sks_lulus: number;
}

export interface EnrollmentCapacity {
  can_enroll: boolean;
  current_sks: number;
  max_sks: number;
  remaining_sks: number;
}

export interface KelasStatistics {
  total_enrolled: number;
  capacity: number;
  available_seats: number;
  by_status: Record<string, number>;
  grade_distribution: Record<string, number>;
  average_grade: number;
}

export interface EnrollmentEligibility {
  eligible: boolean;
  reason?: string;
  missing_prerequisites?: string[];
}

class EnrollmentService {
  /**
   * Get enrollment by ID
   */
  async getById(id: number): Promise<Enrollment> {
    const response = await api.get(`/enrollment/${id}`);
    return response.data.data;
  }

  /**
   * Enroll a student to a class
   */
  async enroll(data: EnrollDTO): Promise<Enrollment> {
    const response = await api.post('/enrollment', data);
    return response.data.data;
  }

  /**
   * Bulk enroll students to a class
   */
  async bulkEnroll(data: BulkEnrollDTO): Promise<Enrollment[]> {
    const response = await api.post('/enrollment/bulk', data);
    return response.data.data;
  }

  /**
   * Drop an enrollment
   */
  async drop(id: number): Promise<void> {
    await api.post(`/enrollment/${id}/drop`);
  }

  /**
   * Update enrollment status
   */
  async updateStatus(id: number, status: string): Promise<Enrollment> {
    const response = await api.put(`/enrollment/${id}/status`, { status });
    return response.data.data;
  }

  /**
   * Update enrollment grades
   */
  async updateGrades(id: number, data: UpdateGradesDTO): Promise<Enrollment> {
    const response = await api.put(`/enrollment/${id}/grades`, data);
    return response.data.data;
  }

  /**
   * Get enrollments by mahasiswa
   */
  async getByMahasiswa(
    nim: string,
    params?: { tahun_ajaran?: string; semester?: number }
  ): Promise<Enrollment[]> {
    const response = await api.get(`/mahasiswa/${nim}/enrollment`, { params });
    return response.data.data;
  }

  /**
   * Get KRS (Kartu Rencana Studi) for a student
   */
  async getKRS(
    nim: string,
    params?: { tahun_ajaran?: string; semester?: number }
  ): Promise<KRS> {
    const response = await api.get(`/mahasiswa/${nim}/krs`, { params });
    return response.data.data;
  }

  /**
   * Get transcript for a student
   */
  async getTranscript(nim: string): Promise<Transcript> {
    const response = await api.get(`/mahasiswa/${nim}/transcript`);
    return response.data.data;
  }

  /**
   * Validate enrollment capacity for a student
   */
  async validateCapacity(nim: string, semester: number): Promise<EnrollmentCapacity> {
    const response = await api.get(`/mahasiswa/${nim}/enrollment-capacity`, {
      params: { semester },
    });
    return response.data.data;
  }

  /**
   * Get enrollments by kelas
   */
  async getByKelas(idKelas: number): Promise<Enrollment[]> {
    const response = await api.get(`/kelas/${idKelas}/enrollment`);
    return response.data.data;
  }

  /**
   * Get enrollment statistics for a kelas
   */
  async getKelasStatistics(idKelas: number): Promise<KelasStatistics> {
    const response = await api.get(`/kelas/${idKelas}/statistics`);
    return response.data.data;
  }

  /**
   * Check enrollment eligibility for a student
   */
  async checkEnrollmentEligibility(
    nim: string,
    kodeMk: string
  ): Promise<EnrollmentEligibility> {
    const response = await api.get(`/students/${nim}/enrollment-eligibility/${kodeMk}`);
    return response.data.data;
  }
}

export default new EnrollmentService();
