export interface User {
  id_user: number;
  username: string;
  email: string;
  nama: string;
  role: 'admin' | 'kaprodi' | 'dosen' | 'mahasiswa';
}

export interface LoginResponse {
  success: boolean;
  token: string;
  user: User;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
  errors?: Record<string, string>;
}

export interface Kurikulum {
  id_kurikulum: number;
  id_prodi: string;
  kode_kurikulum: string;
  nama_kurikulum: string;
  tahun_berlaku: number;
  status: 'draft' | 'approved' | 'active' | 'inactive';
  deskripsi?: string;
  created_at?: string;
  updated_at?: string;
}

export interface CPL {
  id_cpl: number;
  id_kurikulum: number;
  kode_cpl: string;
  deskripsi: string;
  kategori?: string;
  is_active: boolean;
}

export interface CPMK {
  id_cpmk: number;
  kode_mk: string;
  id_kurikulum: number;
  kode_cpmk: string;
  deskripsi: string;
  is_active: boolean;
}

export interface MataKuliah {
  kode_mk: string;
  id_kurikulum: number;
  nama_mk: string;
  sks: number;
  semester: number;
  jenis_mk: 'wajib' | 'pilihan';
  deskripsi?: string;
}

export interface Notification {
  id_notification: number;
  id_user: number;
  tipe_notifikasi: string;
  judul: string;
  pesan: string;
  link?: string;
  is_read: number; // 0 = unread, 1 = read
  is_sent_email: number; // 0 = not sent, 1 = sent
  created_at: string;
  read_at?: string;
}

export interface Document {
  id_document: number;
  nama_file: string;
  file_path: string;
  tipe_file: string;
  ukuran_file: number;
  kategori_dokumen: string;
  id_ref?: number;
  uploaded_by: number;
  deskripsi?: string;
  created_at: string;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface DashboardStats {
  total_kurikulum: number;
  total_cpl: number;
  total_cpmk: number;
  total_mk: number;
  active_kurikulum: number;
}

// Berita Acara Perkuliahan Types
export interface RencanaMingguan {
  id_minggu: number;
  id_rps: number;
  minggu_ke: number;
  id_subcpmk?: number;
  materi: any;
  metode: any;
  aktivitas: any;
  media_software?: string;
  media_hardware?: string;
  pengalaman_belajar?: string;
  estimasi_waktu_menit: number;
  created_at?: string;
  updated_at?: string;
  // Joined fields
  kode_mk?: string;
  nama_mk?: string;
  nama_subcpmk?: string;
}

export interface RealisasiPertemuan {
  id_realisasi?: number;
  id_kelas: number;
  id_minggu?: number;
  tanggal_pelaksanaan: string;
  materi_disampaikan?: string;
  metode_digunakan?: string;
  kendala?: string;
  catatan_dosen?: string;
  status: 'draft' | 'submitted' | 'verified' | 'rejected';
  verified_by?: string;
  verified_at?: string;
  komentar_kaprodi?: string;
  created_by?: string;
  created_at?: string;
  updated_at?: string;
  // Joined fields
  nama_kelas?: string;
  kode_mk?: string;
  nama_mk?: string;
  minggu_ke?: number;
  nama_dosen?: string;
  nama_verifier?: string;
  hari?: string;
  jam_mulai?: string;
  jam_selesai?: string;
  // Additional data
  kehadiran?: Kehadiran[];
  kehadiran_summary?: KehadiranSummary;
  rencana?: any;
}

export interface Kehadiran {
  id_kehadiran?: number;
  id_realisasi: number;
  nim: string;
  status: 'hadir' | 'izin' | 'sakit' | 'alpha';
  keterangan?: string;
  created_at?: string;
  // Joined fields
  nama_mahasiswa?: string;
  tanggal_pelaksanaan?: string;
  nama_mk?: string;
}

export interface KehadiranSummary {
  total_mahasiswa: number;
  hadir: number;
  izin: number;
  sakit: number;
  alpha: number;
  persentase_kehadiran: number;
}

export interface RealisasiStatistics {
  total_pertemuan: number;
  verified_count: number;
  pending_count: number;
  draft_count: number;
  rejected_count: number;
  first_meeting?: string;
  last_meeting?: string;
  total_kelas?: number;
}

export interface RPSComparison {
  id_realisasi: number;
  tanggal_pelaksanaan: string;
  materi_disampaikan?: string;
  metode_digunakan?: string;
  minggu_ke?: number;
  rencana_materi?: any;
  rencana_metode?: any;
  rencana_aktivitas?: any;
  rencana_media_software?: string;
  rencana_media_hardware?: string;
  rencana_waktu?: number;
  nama_kelas?: string;
  nama_mk?: string;
  kode_subcpmk?: string;
  deskripsi_subcpmk?: string;
  analysis?: {
    has_plan: boolean;
    deviations: any[];
    material_match?: number;
  };
}
