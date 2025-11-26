# Dokumentasi Sistem Berita Acara Perkuliahan

## 📋 Overview

Sistem Berita Acara Perkuliahan memungkinkan dosen untuk menginputkan laporan perkuliahan setiap pertemuan, dan Kaprodi melakukan verifikasi kesesuaian materi dengan RPS (Rencana Pembelajaran Semester).

## 🎯 Fitur Utama

### Untuk Dosen
- ✅ Membuat berita acara perkuliahan
- ✅ Input kehadiran mahasiswa per pertemuan
- ✅ Menyimpan sebagai draft atau langsung submit untuk verifikasi
- ✅ Edit berita acara yang berstatus draft atau ditolak
- ✅ Lihat riwayat berita acara
- ✅ Export berita acara ke PDF
- ✅ Lihat statistik perkuliahan

### Untuk Kaprodi
- ✅ Review berita acara yang disubmit dosen
- ✅ Bandingkan materi dengan RPS
- ✅ Lihat detail kehadiran mahasiswa
- ✅ Approve atau reject dengan komentar
- ✅ Dashboard antrian verifikasi

## 🔄 Workflow

```
1. DOSEN
   ├─ Buat berita acara (draft)
   ├─ Lengkapi data perkuliahan
   ├─ Input kehadiran mahasiswa
   ├─ Simpan draft ATAU submit langsung
   └─ Status: draft

2. SUBMIT FOR VERIFICATION
   ├─ Dosen submit berita acara
   └─ Status: draft → submitted

3. KAPRODI REVIEW
   ├─ Muncul di dashboard kaprodi
   ├─ Review detail perkuliahan
   ├─ Bandingkan dengan RPS
   └─ Keputusan:
       ├─ APPROVE → Status: verified
       └─ REJECT → Status: rejected

4. JIKA DITOLAK
   ├─ Dosen menerima feedback
   ├─ Edit berita acara
   └─ Submit ulang
```

## 📁 File Structure

### Backend (PHP)
```
src/
├── Entity/
│   ├── RealisasiPertemuan.php      # Entity berita acara
│   ├── RencanaMingguan.php         # Entity rencana mingguan dari RPS
│   └── Kehadiran.php               # Entity kehadiran mahasiswa
│
├── Repository/
│   ├── RealisasiPertemuanRepository.php  # Database queries
│   ├── RencanaMinggualRepository.php
│   └── KehadiranRepository.php
│
├── Service/
│   └── RealisasiPertemuanService.php  # Business logic
│
└── Controller/
    └── RealisasiPertemuanController.php  # REST API endpoints
```

### Frontend (React + TypeScript)
```
frontend/src/
├── pages/RealisasiPertemuan/
│   ├── RealisasiPertemuanList.tsx      # List berita acara (Dosen)
│   ├── RealisasiPertemuanForm.tsx      # Form create/edit
│   ├── VerificationDashboard.tsx       # Dashboard verifikasi (Kaprodi)
│   └── index.tsx                       # Exports
│
├── components/
│   ├── KehadiranInput.tsx              # Component input kehadiran
│   └── VerificationModal.tsx           # Modal verifikasi kaprodi
│
├── services/
│   └── realisasi-pertemuan.service.ts  # API client
│
├── types/
│   └── api.ts                          # TypeScript interfaces
│
└── hooks/
    └── useAuth.ts                      # Auth hook
```

## 🔌 API Endpoints

### CRUD Operations
```typescript
GET    /api/realisasi-pertemuan
       Query params: id_kelas, id_dosen, status, tanggal_dari, tanggal_sampai
       Response: Array<RealisasiPertemuan>

GET    /api/realisasi-pertemuan/:id
       Response: RealisasiPertemuan (dengan kehadiran)

POST   /api/realisasi-pertemuan
       Body: CreateRealisasiData
       Response: RealisasiPertemuan

PUT    /api/realisasi-pertemuan/:id
       Body: UpdateRealisasiData
       Response: RealisasiPertemuan

DELETE /api/realisasi-pertemuan/:id
       Response: success
```

### Workflow Endpoints
```typescript
POST   /api/realisasi-pertemuan/:id/submit
       Description: Submit berita acara untuk verifikasi
       Response: RealisasiPertemuan (status: submitted)

POST   /api/realisasi-pertemuan/:id/verify
       Body: { approved: boolean, komentar?: string }
       Description: Verifikasi oleh kaprodi
       Response: RealisasiPertemuan (status: verified/rejected)

GET    /api/realisasi-pertemuan/pending-verification
       Description: Daftar berita acara pending (untuk kaprodi)
       Response: Array<RealisasiPertemuan>
```

### Feature Endpoints
```typescript
GET    /api/realisasi-pertemuan/:id/compare-rps
       Description: Bandingkan dengan RPS
       Response: RPSComparison

GET    /api/realisasi-pertemuan/:id/export-pdf
       Description: Export ke PDF
       Response: PDF file (download)

GET    /api/kelas/:id/realisasi-statistics
       Description: Statistik per kelas
       Response: RealisasiStatistics

GET    /api/dosen/:id_dosen/realisasi-statistics
       Description: Statistik per dosen
       Response: RealisasiStatistics

GET    /api/realisasi-pertemuan/:id/kehadiran
       Description: Data kehadiran detail
       Response: { kehadiran: Array<Kehadiran>, summary: KehadiranSummary }

GET    /api/kelas/:id/kehadiran-statistics
       Description: Statistik kehadiran per kelas
       Response: Array<StudentAttendanceStats>
```

## 💻 Cara Penggunaan

### 1. Setup Routing

Tambahkan routes di `frontend/src/App.tsx` atau router config:

```typescript
import {
  RealisasiPertemuanList,
  RealisasiPertemuanForm,
  VerificationDashboard,
} from './pages/RealisasiPertemuan';

// Untuk Dosen
<Route path="/dosen/berita-acara" element={<RealisasiPertemuanList />} />
<Route path="/dosen/berita-acara/create" element={<RealisasiPertemuanForm />} />
<Route path="/dosen/berita-acara/:id/edit" element={<RealisasiPertemuanForm />} />

// Untuk Kaprodi
<Route path="/kaprodi/verifikasi" element={<VerificationDashboard />} />
```

### 2. Migrasi Database

Jalankan migration untuk update database schema:

```bash
php migrate.php migrate
```

Migration file: `database/migrations/004_add_lecture_report_verification.sql`

### 3. Penggunaan Service di Frontend

```typescript
import { realisasiPertemuanService } from '../services/realisasi-pertemuan.service';

// Get list berita acara untuk dosen
const { data } = useQuery({
  queryKey: ['realisasi-pertemuan', idDosen],
  queryFn: () => realisasiPertemuanService.getAll({ id_dosen: idDosen }),
});

// Create berita acara
const createMutation = useMutation({
  mutationFn: (data) => realisasiPertemuanService.create(data),
  onSuccess: () => {
    // Handle success
  },
});

// Submit untuk verifikasi
const submitMutation = useMutation({
  mutationFn: (id) => realisasiPertemuanService.submit(id),
});

// Verifikasi (Kaprodi)
const verifyMutation = useMutation({
  mutationFn: ({ id, approved, komentar }) =>
    realisasiPertemuanService.verify(id, { approved, komentar }),
});

// Export PDF
const handleExport = async (id) => {
  const response = await realisasiPertemuanService.exportPDF(id);
  // Handle PDF download
};
```

### 4. Komponen Kehadiran

```typescript
import { KehadiranInput } from '../components/KehadiranInput';

// Dalam form
<KehadiranInput
  kehadiran={formData.kehadiran}
  onChange={(updated) => setFormData({ ...formData, kehadiran: updated })}
/>

// Read-only mode (untuk review)
<KehadiranInput
  kehadiran={data.kehadiran}
  onChange={() => {}}
  readonly={true}
/>
```

## 🔐 Authorization

### Role-based Access Control

```typescript
// Dosen
- Dapat create berita acara untuk kelas yang diampu
- Dapat edit berita acara dengan status 'draft' atau 'rejected'
- Tidak dapat edit setelah status 'submitted'
- Dapat view own berita acara
- Dapat submit untuk verifikasi

// Kaprodi
- Dapat view semua berita acara dengan status 'submitted'
- Dapat verify/reject berita acara
- Tidak dapat edit berita acara dosen
- Dapat export semua berita acara

// Admin
- Full access (create, edit, delete, verify)
```

### Implementasi di Backend

```php
// Di Controller
AuthMiddleware::requireRole('dosen', 'admin');  // Create/Edit
AuthMiddleware::requireRole('kaprodi', 'admin');  // Verify

// Di Service
$this->isDosenAuthorized($idDosen, $idKelas);  // Check dosen teaches class
```

## 📊 TypeScript Interfaces

```typescript
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
  // ... (joined fields dan additional data)
}

export interface Kehadiran {
  id_kehadiran?: number;
  id_realisasi: number;
  nim: string;
  status: 'hadir' | 'izin' | 'sakit' | 'alpha';
  keterangan?: string;
  nama_mahasiswa?: string;
}

export interface KehadiranSummary {
  total_mahasiswa: number;
  hadir: number;
  izin: number;
  sakit: number;
  alpha: number;
  persentase_kehadiran: number;
}

export interface RPSComparison {
  // Rencana dari RPS
  rencana_materi?: any;
  rencana_metode?: any;
  // Realisasi
  materi_disampaikan?: string;
  metode_digunakan?: string;
  // Analysis
  analysis?: {
    has_plan: boolean;
    deviations: any[];
    material_match?: number;
  };
}
```

## 🎨 UI Components

### Statistics Cards
Menampilkan ringkasan berita acara:
- Total pertemuan
- Draft count
- Pending verification
- Verified count
- Rejected count

### Kehadiran Input
Component untuk input kehadiran dengan features:
- Quick actions (Semua Hadir/Alpha)
- Search mahasiswa
- Filter by status
- Summary statistics
- Keterangan per mahasiswa

### Verification Modal
Modal untuk kaprodi review berita acara:
- Tab: Detail Perkuliahan
- Tab: Kehadiran Mahasiswa
- Tab: Perbandingan RPS
- Approve/Reject dengan komentar

## 🐛 Troubleshooting

### Issue: Cannot edit berita acara
**Solusi**: Berita acara hanya bisa diedit jika status = 'draft' atau 'rejected'

### Issue: Kehadiran tidak tersimpan
**Solusi**: Pastikan format data kehadiran sesuai interface:
```typescript
{
  id_realisasi: number,
  nim: string,
  status: 'hadir' | 'izin' | 'sakit' | 'alpha',
  keterangan?: string
}
```

### Issue: PDF export tidak berfungsi
**Solusi**: Pastikan library mPDF sudah terinstall via composer

### Issue: Comparison tidak muncul
**Solusi**: Pastikan berita acara memiliki `id_minggu` (linked ke rencana_mingguan)

## 📝 TODO / Future Enhancements

- [ ] Add notification system (email/push)
- [ ] Batch verification untuk kaprodi
- [ ] Advanced analytics dashboard
- [ ] Auto-generate draft dari RPS
- [ ] Integration dengan sistem penjadwalan
- [ ] Multi-language support
- [ ] Mobile responsive optimization
- [ ] Offline mode support

## 👥 Kontributor

Developed by Claude Code Assistant

## 📄 License

Part of PHP OBE System
