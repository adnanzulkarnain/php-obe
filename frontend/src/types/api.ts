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
