# Software Specification Document (SDD)
## Sistem Informasi Kurikulum OBE (Outcome-Based Education)

**Version:** 2.0  
**Date:** October 22, 2025  
**Status:** Draft for Review  
**Document Type:** Specification-Driven Development

---

## 📋 Document Control

| Role | Name | Date |
|------|------|------|
| **Author** | Development Team | Oct 22, 2025 |
| **Reviewer** | Kaprodi / IT Manager | - |
| **Approver** | Dekan / Direktur IT | - |

### Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Oct 22, 2025 | Dev Team | Initial draft with basic features |
| 2.0 | Oct 22, 2025 | Dev Team | Major revision: Fixed database design, added critical features |

---

## 🎯 Executive Summary

### Purpose
Sistem Informasi Kurikulum OBE adalah platform digital yang dirancang untuk mendukung implementasi Outcome-Based Education (OBE) di perguruan tinggi, mencakup perencanaan kurikulum, pelaksanaan pembelajaran, penilaian, dan evaluasi ketercapaian capaian pembelajaran.

### Scope
- **Program Studi:** Multi-prodi dalam satu institusi
- **Users:** Kaprodi, Dosen, Mahasiswa, Admin
- **Core Modules:** Manajemen CPL/CPMK, RPS Digital, Penilaian, Analytics
- **Integration:** SIAKAD, LMS (CeLOE/Moodle)

### Key Objectives
1. Digitalisasi proses penyusunan dan pengelolaan RPS
2. Otomasi perhitungan ketercapaian CPMK dan CPL
3. Real-time monitoring progress pembelajaran mahasiswa
4. Analisis dan pelaporan OBE compliance
5. Audit trail untuk akreditasi

---

## 🎓 Kurikulum Management Strategy

### Business Context

Sistem harus mendukung **multiple curricula** yang berjalan paralel dalam satu program studi. Setiap angkatan mahasiswa mengikuti kurikulum tertentu dan tidak dapat berpindah kurikulum.

### Business Rules

| Rule ID | Business Rule | Implementation |
|---------|--------------|----------------|
| **BR-K01** | Mahasiswa mengikuti satu kurikulum sepanjang studi | Mahasiswa assigned ke kurikulum saat enrollment, **immutable** |
| **BR-K02** | MK dengan kode sama di kurikulum berbeda = MK berbeda | Composite PK: `(kode_mk, id_kurikulum)` |
| **BR-K03** | MK tidak dapat dihapus dari kurikulum | Soft delete only (`is_active = FALSE`) |
| **BR-K04** | Mahasiswa hanya bisa ambil MK dari kurikulumnya | Validation di enrollment |
| **BR-K05** | Support 2+ kurikulum berjalan paralel | Multiple active curricula allowed |
| **BR-K06** | Pemetaan MK antar kurikulum untuk konversi | Table `pemetaan_mk_kurikulum` |
| **BR-K07** | CPL terikat ke kurikulum (bisa berbeda antar kurikulum) | `cpl.id_kurikulum` FK |
| **BR-K08** | Hanya 1 kurikulum boleh dalam status 'primary' per prodi | Business logic enforcement |

### Kurikulum Lifecycle

```
┌─────────┐     ┌─────────┐     ┌──────────┐     ┌────────┐     ┌──────────┐
│  Draft  │────>│ Review  │────>│ Approved │────>│ Aktif  │────>│ Non-Aktif│
└─────────┘     └─────────┘     └──────────┘     └────────┘     └──────────┘
                                                       │              │
                                                       │              │
                                                       └──────────────┴────>┌────────┐
                                                                            │ Arsip  │
                                                                            └────────┘
```

**Status Definitions:**
- **Draft**: Kurikulum sedang disusun
- **Review**: Dalam proses review oleh senat/pimpinan
- **Approved**: Disetujui, siap diaktifkan
- **Aktif**: Sedang digunakan oleh mahasiswa aktif
- **Non-Aktif**: Tidak menerima mahasiswa baru, tapi masih ada mahasiswa yang mengikuti
- **Arsip**: Semua mahasiswa sudah lulus, purely historical

### Example Scenario

```
Program Studi: Teknik Informatika

Kurikulum 2024 (K2024):
├── Status: Aktif
├── Angkatan: 2024, 2025, 2026, 2027, 2028
├── Total CPL: 10
├── Total MK: 48
└── Mahasiswa Aktif: 523

Kurikulum 2029 (K2029):
├── Status: Aktif (mulai berlaku)
├── Angkatan: 2029, 2030, ...
├── Total CPL: 12 (ada perubahan)
├── Total MK: 52 (ada penambahan 4 MK baru)
└── Mahasiswa Aktif: 156

Periode Transisi (2029):
├── K2024: Masih aktif untuk angkatan lama
├── K2029: Aktif untuk angkatan baru
└── Keduanya berjalan paralel
```

### MK Mapping Between Curricula

Ketika kurikulum berubah, perlu **mapping** untuk:
- Transfer mahasiswa antar prodi
- Konversi nilai mahasiswa pindahan
- RPL (Rekognisi Pembelajaran Lampau)

**Mapping Types:**
1. **Ekuivalen (100%)**: MK lama = MK baru sepenuhnya
2. **Sebagian (50-99%)**: MK lama sebagian sesuai dengan MK baru
3. **Diganti**: MK lama diganti dengan kombinasi beberapa MK baru
4. **Dihapus**: MK lama tidak ada padanannya di kurikulum baru

---

## 📐 System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │  Kaprodi │  │  Dosen   │  │Mahasiswa │  │  Admin  │ │
│  │   Web    │  │   Web    │  │   Web    │  │   Web   │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                   Application Layer                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │    RPS   │  │ Penilaian│  │ Analytics│  │  Report │ │
│  │  Service │  │  Service │  │  Service │  │ Service │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │   Auth   │  │Notification│ │ Workflow │              │
│  │  Service │  │  Service │  │  Service │              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                     Data Layer                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │PostgreSQL│  │  Redis   │  │  MinIO   │              │
│  │   DB     │  │  Cache   │  │  Storage │              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                Integration Layer                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │  SIAKAD  │  │   LMS    │  │   SSO    │              │
│  │   API    │  │   API    │  │   API    │              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
```

### Technology Stack

| Layer | Technology | 
|-------|-----------|
| **Frontend** | Javascript Native |
| **Backend** | PHP 8.3 |
| **Database** | PostgreSQL 14+ |
| **Auth** | JWT + OAuth2 |

---

## 🗄️ Database Design (REVISED)

### Entity Relationship Diagram (ERD)

```
┌──────────────┐         ┌──────────────┐
│   fakultas   │────────<│    prodi     │
└──────────────┘         └──────────────┘
                                │
                                │
                         ┌──────┴──────┐
                         │             │
                    ┌────▼────┐   ┌────▼────┐
                    │   cpl   │   │matakuliah│
                    └────┬────┘   └────┬────┘
                         │             │
                         │        ┌────▼────┐
                    ┌────▼────┐   │   rps   │
                    │  cpmk   │◄──┴─────────┘
                    └────┬────┘
                         │
                    ┌────▼────┐
                    │subcpmk  │
                    └─────────┘
```

### Core Tables (REVISED)

#### 1. Master Data

```sql
-- =================================================================
-- MASTER DATA: Fakultas, Prodi, Users
-- =================================================================

CREATE TABLE fakultas (
    id_fakultas VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE prodi (
    id_prodi VARCHAR(20) PRIMARY KEY,
    id_fakultas VARCHAR(20) REFERENCES fakultas(id_fakultas),
    nama VARCHAR(100) NOT NULL,
    jenjang VARCHAR(10) CHECK (jenjang IN ('D3','D4','S1','S2','S3')),
    akreditasi VARCHAR(5),
    tahun_berdiri INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE dosen (
    id_dosen VARCHAR(20) PRIMARY KEY,
    nidn VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    status VARCHAR(20) CHECK (status IN ('aktif','cuti','pensiun')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE mahasiswa (
    nim VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    angkatan VARCHAR(10) NOT NULL,
    status VARCHAR(20) CHECK (status IN ('aktif','cuti','lulus','DO')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- User authentication table
CREATE TABLE users (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type VARCHAR(20) CHECK (user_type IN ('dosen','mahasiswa','admin','kaprodi')),
    ref_id VARCHAR(20), -- id_dosen or nim
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE roles (
    id_role SERIAL PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE user_roles (
    id_user INT REFERENCES users(id_user),
    id_role INT REFERENCES roles(id_role),
    PRIMARY KEY (id_user, id_role)
);
```

#### 2. CPL (Program Learning Outcomes) - REVISED

```sql
-- =================================================================
-- CPL: Capaian Pembelajaran Lulusan (Program Level)
-- CRITICAL FIX: CPL adalah milik PRODI, bukan RPS
-- =================================================================

CREATE TABLE cpl (
    id_cpl SERIAL PRIMARY KEY,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi) ON DELETE CASCADE,
    kode_cpl VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori VARCHAR(50) CHECK (kategori IN ('sikap','pengetahuan','keterampilan_umum','keterampilan_khusus')),
    urutan INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_prodi, kode_cpl)
);

-- Contoh data CPL:
-- CPL-1: Mampu mengaplikasikan pemikiran logis, kritis, sistematis...
-- CPL-2: Mampu menunjukkan kinerja mandiri, bermutu, dan terukur...
```

#### 3. Mata Kuliah & RPS

```sql
-- =================================================================
-- MATA KULIAH & RPS
-- =================================================================

CREATE TABLE matakuliah (
    kode_mk VARCHAR(20) PRIMARY KEY,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    nama_mk VARCHAR(100) NOT NULL,
    nama_mk_eng VARCHAR(100),
    sks INT CHECK (sks > 0),
    semester INT CHECK (semester BETWEEN 1 AND 14),
    rumpun VARCHAR(50),
    jenis_mk VARCHAR(50) CHECK (jenis_mk IN ('wajib','pilihan','MKWU')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE rps (
    id_rps SERIAL PRIMARY KEY,
    kode_mk VARCHAR(20) REFERENCES matakuliah(kode_mk),
    semester_berlaku VARCHAR(10) NOT NULL, -- contoh: "2024/2025 Ganjil"
    tahun_ajaran VARCHAR(10) NOT NULL,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','submitted','revised','approved','active','archived')),
    ketua_pengembang VARCHAR(20) REFERENCES dosen(id_dosen),
    tanggal_disusun DATE DEFAULT CURRENT_DATE,
    
    -- Deskripsi MK
    deskripsi_mk TEXT,
    prasyarat TEXT,
    
    -- Metadata
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- RPS Version Control
CREATE TABLE rps_version (
    id_version SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    version_number INT NOT NULL,
    status VARCHAR(20),
    snapshot_data JSONB, -- Backup seluruh data RPS versi ini
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    approved_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW(),
    approved_at TIMESTAMP,
    keterangan TEXT,
    is_active BOOLEAN DEFAULT FALSE,
    UNIQUE (id_rps, version_number)
);

-- Workflow approval RPS
CREATE TABLE rps_approval (
    id_approval SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps),
    approver VARCHAR(20) REFERENCES dosen(id_dosen),
    approval_level INT, -- 1=Ketua RPS, 2=Ketua Prodi, 3=Dekan
    status VARCHAR(20) CHECK (status IN ('pending','approved','rejected','revised')),
    komentar TEXT,
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);
```

#### 4. CPMK & SubCPMK

```sql
-- =================================================================
-- CPMK: Capaian Pembelajaran Mata Kuliah (Course Level)
-- =================================================================

CREATE TABLE cpmk (
    id_cpmk SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    kode_cpmk VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    urutan INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE subcpmk (
    id_subcpmk SERIAL PRIMARY KEY,
    id_cpmk INT REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    kode_subcpmk VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    indikator TEXT,
    urutan INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Relasi CPMK ↔ CPL (Many-to-Many)
CREATE TABLE relasi_cpmk_cpl (
    id_relasi SERIAL PRIMARY KEY,
    id_cpmk INT REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    id_cpl INT REFERENCES cpl(id_cpl) ON DELETE CASCADE,
    bobot_kontribusi NUMERIC(5,2) DEFAULT 100.00, -- seberapa besar kontribusi CPMK ke CPL
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_cpmk, id_cpl)
);
```

#### 5. Kelas & Enrollment - NEW!

```sql
-- =================================================================
-- KELAS & ENROLLMENT (CRITICAL FIX)
-- =================================================================

CREATE TABLE kelas (
    id_kelas SERIAL PRIMARY KEY,
    kode_mk VARCHAR(20) REFERENCES matakuliah(kode_mk),
    id_rps INT REFERENCES rps(id_rps),
    nama_kelas VARCHAR(10) NOT NULL, -- A, B, C, etc
    semester VARCHAR(10) NOT NULL,
    tahun_ajaran VARCHAR(10) NOT NULL,
    kapasitas INT DEFAULT 40,
    kuota_terisi INT DEFAULT 0,
    
    -- Jadwal
    hari VARCHAR(20),
    jam_mulai TIME,
    jam_selesai TIME,
    ruangan VARCHAR(50),
    
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','open','closed','completed')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (kode_mk, nama_kelas, semester, tahun_ajaran)
);

-- Dosen pengampu per kelas
CREATE TABLE tugas_mengajar (
    id_tugas SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    id_dosen VARCHAR(20) REFERENCES dosen(id_dosen),
    peran VARCHAR(50) CHECK (peran IN ('koordinator','pengampu','asisten')),
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_kelas, id_dosen)
);

-- Enrollment mahasiswa (KRS)
CREATE TABLE enrollment (
    id_enrollment SERIAL PRIMARY KEY,
    nim VARCHAR(20) REFERENCES mahasiswa(nim),
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    tanggal_daftar DATE DEFAULT CURRENT_DATE,
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','mengulang','drop','lulus')),
    nilai_akhir NUMERIC(5,2),
    nilai_huruf VARCHAR(2),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (nim, id_kelas)
);
```

#### 6. Penilaian & Nilai - REVISED

```sql
-- =================================================================
-- SISTEM PENILAIAN (MAJOR REVISION)
-- =================================================================

-- Master jenis penilaian
CREATE TABLE jenis_penilaian (
    id_jenis SERIAL PRIMARY KEY,
    nama_jenis VARCHAR(50) NOT NULL UNIQUE, -- Quiz, Tugas, UTS, UAS, Praktikum, dll
    deskripsi TEXT
);

-- Template bobot penilaian per RPS (untuk semua kelas)
CREATE TABLE template_penilaian (
    id_template SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    id_cpmk INT REFERENCES cpmk(id_cpmk),
    id_jenis INT REFERENCES jenis_penilaian(id_jenis),
    bobot NUMERIC(5,2) CHECK (bobot >= 0 AND bobot <= 100),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Komponen penilaian aktual per kelas (instance dari template)
CREATE TABLE komponen_penilaian (
    id_komponen SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    id_template INT REFERENCES template_penilaian(id_template),
    nama_komponen VARCHAR(100) NOT NULL, -- "Quiz 1", "Tugas 2", "UTS", dll
    deskripsi TEXT,
    tanggal_pelaksanaan DATE,
    bobot_realisasi NUMERIC(5,2), -- bisa berbeda dari template
    nilai_maksimal NUMERIC(5,2) DEFAULT 100,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Nilai mahasiswa per komponen
CREATE TABLE nilai_detail (
    id_nilai_detail SERIAL PRIMARY KEY,
    id_enrollment INT REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    id_komponen INT REFERENCES komponen_penilaian(id_komponen),
    nilai_mentah NUMERIC(5,2) CHECK (nilai_mentah >= 0),
    nilai_tertimbang NUMERIC(5,2), -- auto-calculated
    catatan TEXT,
    dinilai_oleh VARCHAR(20) REFERENCES dosen(id_dosen),
    tanggal_input TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_enrollment, id_komponen)
);

-- Summary ketercapaian CPMK per mahasiswa per kelas
CREATE TABLE ketercapaian_cpmk (
    id_ketercapaian SERIAL PRIMARY KEY,
    id_enrollment INT REFERENCES enrollment(id_enrollment),
    id_cpmk INT REFERENCES cpmk(id_cpmk),
    nilai_cpmk NUMERIC(5,2), -- auto-calculated dari nilai_detail
    status_tercapai BOOLEAN, -- tercapai jika >= batas kelulusan
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_enrollment, id_cpmk)
);
```

#### 7. Rencana Pembelajaran Mingguan - REVISED

```sql
-- =================================================================
-- RENCANA PEMBELAJARAN MINGGUAN (RPM)
-- =================================================================

CREATE TABLE rencana_mingguan (
    id_minggu SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    minggu_ke INT CHECK (minggu_ke > 0 AND minggu_ke <= 16),
    id_subcpmk INT REFERENCES subcpmk(id_subcpmk),
    
    -- Materi, Metode, Aktivitas (JSONB for flexibility)
    materi JSONB, -- [{"topik": "Pengenalan OOP", "durasi_menit": 100}]
    metode JSONB, -- [{"jenis": "Ceramah", "durasi": 50}, {"jenis": "Diskusi", "durasi": 50}]
    aktivitas JSONB, -- [{"bentuk": "Quiz", "deskripsi": "...", "bobot": 10}]
    
    -- Media pembelajaran
    media_software TEXT,
    media_hardware TEXT,
    
    -- Pengalaman belajar mahasiswa
    pengalaman_belajar TEXT,
    
    -- Estimasi waktu
    estimasi_waktu_menit INT DEFAULT 150,
    
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_rps, minggu_ke)
);

-- Realisasi pertemuan (untuk tracking aktual pembelajaran)
CREATE TABLE realisasi_pertemuan (
    id_realisasi SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas),
    id_minggu INT REFERENCES rencana_mingguan(id_minggu),
    tanggal_pelaksanaan DATE NOT NULL,
    materi_disampaikan TEXT,
    metode_digunakan TEXT,
    kendala TEXT,
    catatan_dosen TEXT,
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Kehadiran mahasiswa per pertemuan
CREATE TABLE kehadiran (
    id_kehadiran SERIAL PRIMARY KEY,
    id_realisasi INT REFERENCES realisasi_pertemuan(id_realisasi),
    nim VARCHAR(20) REFERENCES mahasiswa(nim),
    status VARCHAR(10) CHECK (status IN ('hadir','izin','sakit','alpha')),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_realisasi, nim)
);
```

#### 8. Pustaka & Media

```sql
-- =================================================================
-- PUSTAKA & MEDIA PEMBELAJARAN
-- =================================================================

CREATE TABLE pustaka (
    id_pustaka SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    jenis VARCHAR(20) CHECK (jenis IN ('utama','pendukung')),
    referensi TEXT NOT NULL,
    penulis VARCHAR(200),
    tahun INT,
    penerbit VARCHAR(100),
    isbn VARCHAR(20),
    url TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE media_pembelajaran (
    id_media SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    kategori VARCHAR(20) CHECK (kategori IN ('software','hardware','platform')),
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

#### 9. Ambang Batas & Konfigurasi

```sql
-- =================================================================
-- AMBANG BATAS KELULUSAN
-- =================================================================

CREATE TABLE ambang_batas (
    id_ambang SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    batas_kelulusan_cpmk NUMERIC(5,2) DEFAULT 40.01,
    batas_kelulusan_mk NUMERIC(5,2) DEFAULT 50.00,
    persentase_mahasiswa_lulus NUMERIC(5,2) DEFAULT 75.00, -- minimal 75% mhs lulus untuk MK dianggap berhasil
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Konfigurasi sistem per prodi
CREATE TABLE konfigurasi_prodi (
    id_config SERIAL PRIMARY KEY,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    key VARCHAR(100) NOT NULL,
    value TEXT,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_prodi, key)
);
```

#### 10. Audit Trail & Logging

```sql
-- =================================================================
-- AUDIT TRAIL (CRITICAL for Accreditation)
-- =================================================================

CREATE TABLE audit_log (
    id_audit SERIAL PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    action VARCHAR(20) CHECK (action IN ('INSERT','UPDATE','DELETE','APPROVE','REJECT')),
    old_data JSONB,
    new_data JSONB,
    user_id INT REFERENCES users(id_user),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_audit_table ON audit_log(table_name, record_id);
CREATE INDEX idx_audit_user ON audit_log(user_id, created_at);
CREATE INDEX idx_audit_created ON audit_log(created_at DESC);
```

#### 11. Notifications

```sql
-- =================================================================
-- NOTIFICATION SYSTEM
-- =================================================================

CREATE TABLE notifications (
    id_notif SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id_user),
    type VARCHAR(50), -- 'rps_approval', 'nilai_input', 'deadline_reminder'
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    read_at TIMESTAMP
);

CREATE INDEX idx_notif_user ON notifications(user_id, is_read, created_at DESC);
```

#### 12. Documents & Attachments

```sql
-- =================================================================
-- DOCUMENT MANAGEMENT
-- =================================================================

CREATE TABLE documents (
    id_document SERIAL PRIMARY KEY,
    entity_type VARCHAR(50), -- 'rps', 'cpmk', 'kelas'
    entity_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    mime_type VARCHAR(100),
    uploaded_by INT REFERENCES users(id_user),
    description TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_doc_entity ON documents(entity_type, entity_id);
```

### Database Indexes (Performance Optimization)

```sql
-- =================================================================
-- PERFORMANCE INDEXES
-- =================================================================

-- Core indexes
CREATE INDEX idx_cpl_prodi ON cpl(id_prodi);
CREATE INDEX idx_cpmk_rps ON cpmk(id_rps);
CREATE INDEX idx_subcpmk_cpmk ON subcpmk(id_cpmk);
CREATE INDEX idx_relasi_cpmk ON relasi_cpmk_cpl(id_cpmk);
CREATE INDEX idx_relasi_cpl ON relasi_cpmk_cpl(id_cpl);

-- Kelas & Enrollment
CREATE INDEX idx_kelas_mk ON kelas(kode_mk, semester, tahun_ajaran);
CREATE INDEX idx_enrollment_mahasiswa ON enrollment(nim);
CREATE INDEX idx_enrollment_kelas ON enrollment(id_kelas);

-- Penilaian
CREATE INDEX idx_template_rps ON template_penilaian(id_rps);
CREATE INDEX idx_komponen_kelas ON komponen_penilaian(id_kelas);
CREATE INDEX idx_nilai_enrollment ON nilai_detail(id_enrollment);
CREATE INDEX idx_ketercapaian_enrollment ON ketercapaian_cpmk(id_enrollment);

-- RPS & Planning
CREATE INDEX idx_rps_mk ON rps(kode_mk);
CREATE INDEX idx_rps_status ON rps(status);
CREATE INDEX idx_minggu_rps ON rencana_mingguan(id_rps);
CREATE INDEX idx_realisasi_kelas ON realisasi_pertemuan(id_kelas);
```

### Materialized Views (for Analytics)

```sql
-- =================================================================
-- MATERIALIZED VIEWS (Auto-refresh untuk reporting)
-- =================================================================

-- View: Ketercapaian CPMK per Kelas
CREATE MATERIALIZED VIEW mv_ketercapaian_kelas AS
SELECT 
    k.id_kelas,
    k.nama_kelas,
    mk.nama_mk,
    cp.id_cpmk,
    cm.kode_cpmk,
    cm.deskripsi,
    COUNT(DISTINCT e.nim) as jumlah_mahasiswa,
    AVG(kc.nilai_cpmk) as rata_nilai_cpmk,
    COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END) as jumlah_lulus,
    ROUND(COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END)::NUMERIC / COUNT(DISTINCT e.nim) * 100, 2) as persentase_lulus
FROM kelas k
JOIN enrollment e ON k.id_kelas = e.id_kelas
JOIN cpmk cm ON cm.id_rps = k.id_rps
LEFT JOIN ketercapaian_cpmk kc ON kc.id_enrollment = e.id_enrollment AND kc.id_cpmk = cm.id_cpmk
LEFT JOIN matakuliah mk ON k.kode_mk = mk.kode_mk
GROUP BY k.id_kelas, k.nama_kelas, mk.nama_mk, cp.id_cpmk, cm.kode_cpmk, cm.deskripsi;

CREATE UNIQUE INDEX ON mv_ketercapaian_kelas (id_kelas, id_cpmk);

-- View: Ketercapaian CPL per Prodi
CREATE MATERIALIZED VIEW mv_ketercapaian_cpl AS
SELECT 
    p.id_prodi,
    p.nama as nama_prodi,
    cpl.id_cpl,
    cpl.kode_cpl,
    cpl.deskripsi,
    COUNT(DISTINCT e.nim) as total_mahasiswa,
    AVG(kc.nilai_cpmk) as rata_kontribusi,
    cpl.kategori
FROM prodi p
JOIN cpl ON cpl.id_prodi = p.id_prodi
JOIN relasi_cpmk_cpl rcl ON rcl.id_cpl = cpl.id_cpl
JOIN ketercapaian_cpmk kc ON kc.id_cpmk = rcl.id_cpmk
JOIN enrollment e ON e.id_enrollment = kc.id_enrollment
GROUP BY p.id_prodi, p.nama, cpl.id_cpl, cpl.kode_cpl, cpl.deskripsi, cpl.kategori;

CREATE UNIQUE INDEX ON mv_ketercapaian_cpl (id_prodi, id_cpl);

-- Refresh command (run by scheduled job)
-- REFRESH MATERIALIZED VIEW CONCURRENTLY mv_ketercapaian_kelas;
-- REFRESH MATERIALIZED VIEW CONCURRENTLY mv_ketercapaian_cpl;
```

---

## 🎭 User Roles & Permissions

### Role Matrix

| Feature | Admin | Kaprodi | Dosen | Mahasiswa |
|---------|-------|---------|-------|-----------|
| **Master Data** |
| Manage Prodi | ✅ | ❌ | ❌ | ❌ |
| Manage CPL | ✅ | ✅ | ❌ | ❌ |
| View CPL | ✅ | ✅ | ✅ | ✅ |
| **RPS Management** |
| Create RPS | ❌ | ✅ | ✅ | ❌ |
| Edit RPS (draft) | ❌ | ✅ | ✅ (own) | ❌ |
| Approve RPS | ❌ | ✅ | ❌ | ❌ |
| View RPS | ✅ | ✅ | ✅ | ✅ |
| **CPMK Management** |
| Create CPMK | ❌ | ✅ | ✅ | ❌ |
| Map CPMK-CPL | ❌ | ✅ | ✅ | ❌ |
| View CPMK | ✅ | ✅ | ✅ | ✅ |
| **Penilaian** |
| Input Nilai | ❌ | ❌ | ✅ | ❌ |
| View Nilai (all) | ✅ | ✅ | ✅ (kelas) | ❌ |
| View Nilai (own) | ❌ | ❌ | ❌ | ✅ |
| Override Nilai | ❌ | ✅ | ❌ | ❌ |
| **Analytics** |
| View Dashboard Prodi | ✅ | ✅ | ❌ | ❌ |
| View Dashboard Kelas | ❌ | ✅ | ✅ | ❌ |
| Generate Reports | ✅ | ✅ | ✅ | ❌ |
| Export Data | ✅ | ✅ | ✅ | ❌ |

---

## 📱 Functional Requirements

### FR-001: User Management

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-001.1 | System must support role-based authentication | - User can login with username/password<br>- Session expires after 2 hours<br>- Support SSO integration |
| FR-001.2 | System must support multiple roles per user | - A dosen can be Kaprodi<br>- Role assignment via admin panel |
| FR-001.3 | Password management | - Min 8 characters<br>- Password reset via email<br>- Change password feature |

### FR-002: CPL Management (Kaprodi)

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-002.1 | Kaprodi can create CPL for their prodi | - Input: kode, deskripsi, kategori<br>- Validation: unique kode per prodi<br>- Audit log created |
| FR-002.2 | CPL can be updated | - Only if no CPMK mapped yet OR with confirmation<br>- Version history maintained |
| FR-002.3 | CPL can be deactivated | - Soft delete (is_active = false)<br>- Cannot delete if used in active RPS |
| FR-002.4 | View all CPL for prodi | - Sortable by kategori, kode<br>- Filter by status (active/inactive) |

### FR-003: Mata Kuliah Management

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-003.1 | Admin/Kaprodi can create MK | - Input: kode, nama, SKS, semester<br>- Unique kode per prodi |
| FR-003.2 | MK can have prerequisites | - Multiple prerequisites supported<br>- Validation: circular dependency check |

### FR-004: RPS Management

**Priority:** CRITICAL

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-004.1 | Dosen can create RPS draft | - Select MK<br>- Input deskripsi, prasyarat<br>- Auto-save every 30 seconds<br>- Status = 'draft' |
| FR-004.2 | RPS can define CPMK | - Min 3, max 12 CPMK per MK<br>- Each CPMK must map to at least 1 CPL<br>- Validation: total bobot mapping = 100% |
| FR-004.3 | RPS can define SubCPMK | - Each CPMK can have 1-5 SubCPMK<br>- Each SubCPMK must have indicator |
| FR-004.4 | RPS approval workflow | - Draft → Submitted → Reviewed → Approved<br>- Kaprodi can reject with comments<br>- Email notification at each stage |
| FR-004.5 | RPS versioning | - New version created on approval<br>- Compare versions feature<br>- Rollback to previous version |
| FR-004.6 | Generate RPS document | - Export to PDF/DOCX<br>- Use official template<br>- Include all data: CPMK, CPL mapping, RPM, etc |

### FR-005: Rencana Pembelajaran Mingguan (RPM)

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-005.1 | Define weekly learning plan | - 14-16 weeks supported<br>- Each week maps to SubCPMK<br>- Input: materi, metode, aktivitas, media |
| FR-005.2 | Clone from previous semester | - Copy RPM from previous RPS<br>- Allow modifications |
| FR-005.3 | View weekly schedule | - Calendar view<br>- List view with filters |

### FR-006: Kelas Management

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-006.1 | Admin/Kaprodi can create kelas | - Select MK, RPS, semester<br>- Assign dosen pengampu<br>- Set schedule (hari, jam, ruangan) |
| FR-006.2 | Multiple classes per MK | - Support kelas A, B, C, etc<br>- Different schedules allowed |
| FR-006.3 | Assign team teaching | - Multiple dosen per kelas<br>- Define role: koordinator/pengampu |

### FR-007: Enrollment (KRS)

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-007.1 | Mahasiswa can enroll to kelas | - Integration with SIAKAD<br>- Check: kuota, prasyarat, jadwal conflict |
| FR-007.2 | View enrolled classes | - List view with schedule<br>- Access RPS and materials |
| FR-007.3 | Drop class | - Before deadline<br>- Update kuota |

### FR-008: Penilaian Management

**Priority:** CRITICAL

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-008.1 | Define penilaian template in RPS | - Select jenis: Quiz, Tugas, UTS, UAS<br>- Map to CPMK<br>- Set bobot per CPMK<br>- Validation: total bobot = 100% |
| FR-008.2 | Create komponen penilaian per kelas | - Based on template<br>- Can adjust bobot<br>- Set deadline |
| FR-008.3 | Input nilai mahasiswa | - Bulk import (Excel/CSV)<br>- Manual input per mahasiswa<br>- Validation: 0-100<br>- Auto-save |
| FR-008.4 | Calculate CPMK achievement | - Formula: Weighted average of all komponen mapped to CPMK<br>- Auto-update on nilai input<br>- Status: Tercapai if >= batas_kelulusan_cpmk |
| FR-008.5 | Calculate final grade | - Based on all CPMK<br>- Generate nilai huruf (A, B+, B, etc)<br>- Push to SIAKAD |
| FR-008.6 | Revision of nilai | - Audit log maintained<br>- Approval required for finalized grades |

**Calculation Example:**
```
CPMK-1 has 3 komponen:
- Quiz (bobot 20%, nilai 80) → kontribusi = 16
- Tugas (bobot 30%, nilai 90) → kontribusi = 27
- UTS (bobot 50%, nilai 75) → kontribusi = 37.5

Nilai CPMK-1 = 16 + 27 + 37.5 = 80.5
Status = Tercapai (if >= 40.01)
```

### FR-009: Analytics & Reporting

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-009.1 | Dashboard Kaprodi | - Summary: Total MK, RPS approved, avg CPMK achievement<br>- Chart: CPMK achievement trend<br>- Filter by semester |
| FR-009.2 | Dashboard Dosen | - List kelas with completion %<br>- Nilai distribution chart<br>- Alert for missing nilai |
| FR-009.3 | Dashboard Mahasiswa | - Progress tracker per MK<br>- CPMK achievement visualization<br>- Grade prediction |
| FR-009.4 | Report: Ketercapaian CPMK | - Per kelas, per MK, per semester<br>- Export to PDF/Excel<br>- Include statistics and charts |
| FR-009.5 | Report: Ketercapaian CPL | - Per prodi, per angkatan<br>- Aggregated from CPMK data<br>- Gap analysis |
| FR-009.6 | Report: OBE Compliance | - For accreditation<br>- Include all evidence: RPS, nilai, analysis |

### FR-010: Document Management

**Priority:** MEDIUM

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-010.1 | Upload attachments | - Support: PDF, DOCX, XLSX, PPT, images<br>- Max size: 10MB per file<br>- Stored in MinIO/S3 |
| FR-010.2 | Link documents to entities | - RPS, CPMK, Kelas, etc<br>- Multiple files per entity |
| FR-010.3 | Download and preview | - PDF preview in browser<br>- Direct download for others |

### FR-011: Notification System

**Priority:** MEDIUM

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-011.1 | Real-time notifications | - In-app notification badge<br>- Email notification (configurable) |
| FR-011.2 | Notification types | - RPS approval/rejection<br>- Nilai input deadline<br>- New announcement |
| FR-011.3 | Notification preferences | - User can enable/disable per type<br>- Email digest option (daily/weekly) |

### FR-012: Integration

**Priority:** HIGH

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-012.1 | SIAKAD integration | - Sync: Mahasiswa, MK, Enrollment<br>- Push: Nilai akhir<br>- REST API or DB sync |
| FR-012.2 | LMS integration | - Embed RPS in LMS<br>- Sync materials and assignments<br>- API-based |
| FR-012.3 | SSO integration | - Login via institutional SSO<br>- LDAP/OAuth2 support |

### FR-013: Kurikulum Management

**Priority:** CRITICAL

| ID | Requirement | Acceptance Criteria |
|----|-------------|-------------------|
| FR-013.1 | Kaprodi can create new kurikulum | - Input: kode, nama, tahun berlaku, deskripsi<br>- Upload SK dokumen<br>- Status default = 'draft'<br>- Validation: unique kode per prodi |
| FR-013.2 | Define CPL per kurikulum | - CPL belong to kurikulum (not prodi)<br>- Same kode_cpl can exist in different curricula<br>- Support 4 categories: sikap, pengetahuan, keterampilan umum/khusus |
| FR-013.3 | Define MK per kurikulum | - Same kode_mk can exist in multiple curricula with different content<br>- Composite key: (kode_mk, id_kurikulum)<br>- Cannot hard delete MK (soft delete only) |
| FR-013.4 | Set prasyarat MK within curriculum | - MK can have multiple prerequisites<br>- Prerequisites must be from same curriculum<br>- Support 'wajib' and 'alternatif' type |
| FR-013.5 | Kurikulum approval workflow | - Draft → Review → Approved → Aktif<br>- Requires SK number and date<br>- Email notification to stakeholders |
| FR-013.6 | Activate/deactivate kurikulum | - Only 1 'primary' curriculum per prodi<br>- Can have multiple 'aktif' curricula (parallel)<br>- Cannot delete if has enrolled students |
| FR-013.7 | Map MK between curricula | - Create equivalence mapping for transfer/conversion<br>- Types: ekuivalen (100%), sebagian, diganti, dihapus<br>- Set conversion weight (0-100%) |
| FR-013.8 | Assign mahasiswa to kurikulum | - Auto-assign based on angkatan<br>- IMMUTABLE - cannot change after assignment<br>- Display curriculum info in student profile |
| FR-013.9 | Enforce curriculum isolation | - Mahasiswa can only enroll in classes from their curriculum<br>- CPMK-CPL mapping within same curriculum<br>- System validation on enrollment |
| FR-013.10 | View curriculum comparison | - Side-by-side comparison of 2+ curricula<br>- Show: CPL changes, MK changes, credit changes<br>- Export comparison report |
| FR-013.11 | Curriculum statistics dashboard | - Per curriculum: total CPL, MK, students, classes<br>- Student distribution by angkatan<br>- Active vs completed curricula |
| FR-013.12 | Archive old curriculum | - Status: Non-Aktif → Arsip<br>- Only when all students have graduated<br>- Maintain historical data for accreditation |

**Business Rules Enforcement:**
- BR-K01: Student curriculum is immutable ✅
- BR-K02: Same MK code can exist in different curricula ✅  
- BR-K03: MK cannot be hard deleted ✅
- BR-K04: Students can only take courses from their curriculum ✅
- BR-K05: Support parallel curricula ✅
- BR-K06: MK mapping for conversion ✅
- BR-K07: CPL belongs to curriculum ✅

---

## 🔒 Non-Functional Requirements

### NFR-001: Performance

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-001.1 | Page load time | < 2 seconds (95th percentile) |
| NFR-001.2 | API response time | < 500ms (avg), < 2s (99th percentile) |
| NFR-001.3 | Database query time | < 100ms for simple queries, < 1s for complex analytics |
| NFR-001.4 | Concurrent users | Support 500 concurrent users without degradation |
| NFR-001.5 | Batch operations | Import 1000 nilai records in < 30 seconds |

### NFR-002: Security

| ID | Requirement | Implementation |
|----|-------------|----------------|
| NFR-002.1 | Authentication | JWT with 2-hour expiry, refresh token 7 days |
| NFR-002.2 | Authorization | Role-based access control (RBAC) |
| NFR-002.3 | Data encryption | TLS 1.3 for transport, AES-256 for sensitive data at rest |
| NFR-002.4 | Password policy | Min 8 chars, 1 uppercase, 1 number, 1 special char |
| NFR-002.5 | Audit logging | All write operations logged with user, timestamp, IP |
| NFR-002.6 | SQL injection prevention | Prepared statements, parameterized queries |
| NFR-002.7 | XSS prevention | Input sanitization, CSP headers |

### NFR-003: Availability & Reliability

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-003.1 | System uptime | 99.5% (excluding planned maintenance) |
| NFR-003.2 | Planned maintenance window | Max 4 hours/month, scheduled off-peak |
| NFR-003.3 | Backup frequency | Daily incremental, weekly full backup |
| NFR-003.4 | Backup retention | 30 days, with yearly archive |
| NFR-003.5 | Disaster recovery | RTO: 4 hours, RPO: 24 hours |

### NFR-004: Scalability

| ID | Requirement | Implementation |
|----|-------------|----------------|
| NFR-004.1 | Horizontal scaling | Stateless API servers, load-balanced |
| NFR-004.2 | Database scaling | Read replicas for analytics queries |
| NFR-004.3 | Storage scaling | Object storage (MinIO/S3) for documents |
| NFR-004.4 | Caching | Redis for session, frequently-accessed data |

### NFR-005: Usability

| ID | Requirement | Standard |
|----|-------------|----------|
| NFR-005.1 | UI responsiveness | Mobile-friendly (responsive design) |
| NFR-005.2 | Browser support | Chrome, Firefox, Safari, Edge (latest 2 versions) |
| NFR-005.3 | Accessibility | WCAG 2.1 Level AA compliance |
| NFR-005.4 | User documentation | Online help, video tutorials, FAQ |
| NFR-005.5 | Error messages | Clear, actionable, in Indonesian |

### NFR-006: Maintainability

| ID | Requirement | Implementation |
|----|-------------|----------------|
| NFR-006.1 | Code quality | Linting (ESLint), code review mandatory |
| NFR-006.2 | Testing coverage | Unit test: 70%, Integration test: 50% |
| NFR-006.3 | Documentation | API docs (Swagger/OpenAPI), code comments |
| NFR-006.4 | Version control | Git with feature branching, semantic versioning |
| NFR-006.5 | CI/CD pipeline | Automated build, test, deploy |

---

## 🧪 Testing Strategy

### Test Levels

| Level | Coverage | Tools |
|-------|----------|-------|
| **Unit Testing** | Individual functions, components | Jest, PyTest |
| **Integration Testing** | API endpoints, database operations | Supertest, Postman |
| **System Testing** | End-to-end workflows | Selenium, Cypress |
| **Performance Testing** | Load, stress, spike tests | JMeter, K6 |
| **Security Testing** | Penetration testing, vulnerability scan | OWASP ZAP, Burp Suite |
| **UAT** | Business scenarios validation | Manual + checklist |

### Test Cases (Sample)

**TC-001: Create RPS**
```
Precondition: User logged in as Dosen
Steps:
1. Navigate to RPS Management
2. Click "Buat RPS Baru"
3. Select Mata Kuliah "Algoritma dan Pemrograman"
4. Input deskripsi MK
5. Add CPMK-1: "Mahasiswa mampu memahami konsep OOP"
6. Map CPMK-1 to CPL-1 (bobot 100%)
7. Click "Simpan Draft"

Expected Result:
- RPS created with status 'draft'
- Success notification displayed
- Redirect to RPS detail page
- Audit log created
```

**TC-002: Calculate CPMK Achievement**
```
Precondition: 
- Nilai input completed for all komponen
- CPMK-1 mapped to: Quiz (20%), Tugas (30%), UTS (50%)

Test Data:
- Mahasiswa A: Quiz=80, Tugas=90, UTS=75

Steps:
1. Navigate to Penilaian page
2. Click "Hitung Ketercapaian CPMK"

Expected Result:
- Nilai CPMK-1 for Mahasiswa A = 80.5
- Status = "Tercapai" (if threshold <= 80.5)
- Display updated in dashboard
```

---

## 🚀 Deployment Strategy

### Environments

| Environment | Purpose | Access |
|-------------|---------|--------|
| **Development** | Active development | Developers only |
| **Staging** | Pre-production testing | Developers, QA, stakeholders |
| **Production** | Live system | All users |

### Deployment Pipeline

```
┌─────────────┐
│   Develop   │
│   Branch    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Build &   │
│   Unit Test │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Deploy to │
│   Staging   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│Integration  │
│   Tests     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│    UAT      │
│  Approval   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Deploy to  │
│ Production  │
└─────────────┘
```

### Rollback Strategy

- Keep last 3 versions deployable
- Blue-green deployment for zero-downtime
- Database migration rollback scripts mandatory

---

## 📊 Success Metrics

### KPIs

| Metric | Target | Measurement |
|--------|--------|-------------|
| RPS digitalization rate | 100% by semester 2 | % of RPS in system vs total |
| User adoption rate | 80% active users | Weekly active users / total users |
| Data accuracy | 95% | Audit checks vs manual reports |
| System uptime | 99.5% | Monitored by Prometheus |
| User satisfaction | 4/5 stars | Survey after 3 months |
| Report generation time | < 5 minutes | Avg time for OBE report |

---

## 📅 Implementation Timeline

### Phase 1: Foundation (Month 1-2)
- ✅ Database design and setup
- ✅ User authentication and authorization
- ✅ Master data management (Prodi, MK, CPL)
- ✅ Basic RPS creation

### Phase 2: Core Features (Month 3-4)
- ✅ CPMK management and CPL mapping
- ✅ Rencana Pembelajaran Mingguan
- ✅ Kelas and enrollment
- ✅ RPS approval workflow

### Phase 3: Assessment (Month 5-6)
- ✅ Penilaian template
- ✅ Komponen penilaian per kelas
- ✅ Nilai input (manual & import)
- ✅ Auto-calculation CPMK achievement

### Phase 4: Analytics (Month 7-8)
- ✅ Dashboards for all roles
- ✅ Report generation
- ✅ Materialized views optimization
- ✅ Export features

### Phase 5: Integration & Polish (Month 9-10)
- ✅ SIAKAD integration
- ✅ LMS integration
- ✅ Notification system
- ✅ Document management
- ✅ UAT and bug fixes

### Phase 6: Deployment (Month 11-12)
- ✅ Pilot program (1-2 prodi)
- ✅ Training for users
- ✅ Full rollout
- ✅ Monitoring and support

---

## 🛠️ Technical Specifications

### API Design

**REST API Conventions:**
- Base URL: `https://api.obe-system.ac.id/v1`
- Authentication: Bearer token in header
- Response format: JSON
- Status codes: Standard HTTP codes

**Sample Endpoints:**

```
POST   /auth/login
POST   /auth/logout
GET    /auth/profile

GET    /prodi
GET    /prodi/{id_prodi}

# Kurikulum Management (NEW)
GET    /kurikulum
POST   /kurikulum
GET    /kurikulum/{id_kurikulum}
PUT    /kurikulum/{id_kurikulum}
POST   /kurikulum/{id_kurikulum}/approve
POST   /kurikulum/{id_kurikulum}/activate
GET    /kurikulum/{id_kurikulum}/cpl
POST   /kurikulum/{id_kurikulum}/cpl
GET    /kurikulum/{id_kurikulum}/matakuliah
POST   /kurikulum/{id_kurikulum}/matakuliah
GET    /kurikulum/{id_kurikulum}/statistik
GET    /kurikulum/compare?ids={id1},{id2}

# CPL Management
GET    /cpl
POST   /cpl
GET    /cpl/{id_cpl}
PUT    /cpl/{id_cpl}
DELETE /cpl/{id_cpl}

# Mata Kuliah
GET    /matakuliah
GET    /matakuliah/{kode_mk}/{id_kurikulum}
POST   /matakuliah
PUT    /matakuliah/{kode_mk}/{id_kurikulum}
DELETE /matakuliah/{kode_mk}/{id_kurikulum}

# MK Mapping
GET    /pemetaan-mk
POST   /pemetaan-mk
GET    /pemetaan-mk/kurikulum/{id_kurikulum_lama}/to/{id_kurikulum_baru}

# RPS
GET    /rps
POST   /rps
GET    /rps/{id_rps}
PUT    /rps/{id_rps}
DELETE /rps/{id_rps}
POST   /rps/{id_rps}/submit
POST   /rps/{id_rps}/approve
POST   /rps/{id_rps}/reject
GET    /rps/{id_rps}/export/pdf

# Kelas & Enrollment
GET    /kelas
POST   /kelas
GET    /kelas/{id_kelas}
GET    /kelas/{id_kelas}/enrollment
POST   /kelas/{id_kelas}/enrollment

# Penilaian
GET    /kelas/{id_kelas}/penilaian
POST   /kelas/{id_kelas}/penilaian/komponen
POST   /nilai/import
GET    /nilai/mahasiswa/{nim}

# Analytics & Reports
GET    /analytics/dashboard/kaprodi
GET    /analytics/dashboard/dosen
GET    /analytics/ketercapaian-cpmk/{id_kelas}
GET    /analytics/ketercapaian-cpl/{id_kurikulum}
GET    /analytics/comparison/kurikulum

GET    /reports/obe-compliance/{id_prodi}
GET    /reports/kurikulum/{id_kurikulum}
```

### Database Connection

```python
# Example: Database configuration
DATABASE_CONFIG = {
    'host': 'localhost',
    'port': 5432,
    'database': 'obe_system',
    'user': 'obe_user',
    'password': '***',
    'pool_size': 20,
    'max_overflow': 10,
    'pool_timeout': 30,
    'pool_recycle': 3600
}
```

### Calculation Logic

**CPMK Achievement Calculation:**

```python
def calculate_cpmk_achievement(enrollment_id, cpmk_id):
    """
    Calculate CPMK achievement for a student in a class
    Formula: Weighted average of all komponen mapped to this CPMK
    """
    # Get all nilai_detail for this student and CPMK
    komponen_list = get_komponen_by_cpmk(cpmk_id)
    
    total_bobot = 0
    total_nilai_tertimbang = 0
    
    for komponen in komponen_list:
        nilai = get_nilai_detail(enrollment_id, komponen.id_komponen)
        if nilai:
            bobot = komponen.bobot_realisasi
            nilai_tertimbang = (nilai.nilai_mentah / komponen.nilai_maksimal) * bobot
            
            total_nilai_tertimbang += nilai_tertimbang
            total_bobot += bobot
    
    # Final CPMK score
    if total_bobot > 0:
        nilai_cpmk = (total_nilai_tertimbang / total_bobot) * 100
    else:
        nilai_cpmk = 0
    
    # Check if achieved
    batas = get_ambang_batas(cpmk_id)
    status_tercapai = nilai_cpmk >= batas.batas_kelulusan_cpmk
    
    # Save to ketercapaian_cpmk table
    save_ketercapaian(enrollment_id, cpmk_id, nilai_cpmk, status_tercapai)
    
    return nilai_cpmk, status_tercapai
```

**CPL Achievement Calculation:**

```python
def calculate_cpl_achievement(prodi_id, cpl_id, angkatan=None):
    """
    Calculate CPL achievement across all students in a prodi
    Based on CPMK achievements that map to this CPL
    """
    # Get all CPMK that map to this CPL
    relasi_list = get_relasi_cpmk_cpl(cpl_id)
    
    total_kontribusi = 0
    total_bobot = 0
    
    for relasi in relasi_list:
        cpmk_id = relasi.id_cpmk
        bobot_kontribusi = relasi.bobot_kontribusi
        
        # Get average CPMK achievement for all students
        avg_cpmk = get_average_cpmk_achievement(cpmk_id, prodi_id, angkatan)
        
        total_kontribusi += avg_cpmk * (bobot_kontribusi / 100)
        total_bobot += bobot_kontribusi
    
    # Final CPL achievement
    if total_bobot > 0:
        nilai_cpl = (total_kontribusi / total_bobot) * 100
    else:
        nilai_cpl = 0
    
    return nilai_cpl
```

---

## 📚 Appendix

### A. Glossary

| Term | Definition |
|------|------------|
| **OBE** | Outcome-Based Education - Pendekatan pendidikan yang berfokus pada capaian pembelajaran |
| **CPL** | Capaian Pembelajaran Lulusan (Program Learning Outcomes) - Kompetensi yang harus dikuasai lulusan program studi |
| **CPMK** | Capaian Pembelajaran Mata Kuliah (Course Learning Outcomes) - Kompetensi yang harus dikuasai setelah menyelesaikan mata kuliah |
| **SubCPMK** | Sub-CPMK - Detail capaian pembelajaran yang lebih spesifik dari CPMK |
| **RPS** | Rencana Pembelajaran Semester - Dokumen perencanaan pembelajaran untuk satu semester |
| **RPM** | Rencana Pembelajaran Mingguan - Detail pembelajaran per minggu |
| **SIAKAD** | Sistem Informasi Akademik |
| **LMS** | Learning Management System (e.g., Moodle, CeLOE) |

### B. References

1. Permendikbud No. 3 Tahun 2020 tentang Standar Nasional Pendidikan Tinggi
2. ABET (Accreditation Board for Engineering and Technology) - OBE Guidelines
3. PostgreSQL Documentation: https://www.postgresql.org/docs/
4. REST API Best Practices: https://restfulapi.net/

### C. Sample Data

**Sample CPL:**
```
CPL-1 (Sikap): Bertakwa kepada Tuhan Yang Maha Esa dan mampu menunjukkan sikap religius
CPL-2 (Pengetahuan): Menguasai konsep teoretis sains alam, aplikasi matematika rekayasa
CPL-3 (Keterampilan Umum): Mampu menerapkan pemikiran logis, kritis, sistematis, dan inovatif
CPL-4 (Keterampilan Khusus): Mampu merancang, mengimplementasi, dan mengevaluasi sistem perangkat lunak
```

**Sample CPMK (for course "Algoritma dan Pemrograman"):**
```
CPMK-1: Mahasiswa mampu memahami konsep algoritma dan flowchart (→ CPL-2)
CPMK-2: Mahasiswa mampu mengimplementasikan struktur data dasar (→ CPL-2, CPL-4)
CPMK-3: Mahasiswa mampu menganalisis kompleksitas algoritma (→ CPL-3, CPL-4)
CPMK-4: Mahasiswa mampu merancang solusi pemrograman untuk problem solving (→ CPL-3, CPL-4)
```

### D. Contact Information

**Project Team:**
- Project Manager: [Name] - [email]
- Lead Developer: [Name] - [email]
- Database Architect: [Name] - [email]
- QA Lead: [Name] - [email]

**Stakeholders:**
- Dekan: [Name] - [email]
- Kaprodi: [Name] - [email]
- IT Manager: [Name] - [email]

---

## ✅ Document Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Author** | Development Team | ___________ | Oct 22, 2025 |
| **Reviewed by** | Kaprodi | ___________ | _________ |
| **Approved by** | Dekan | ___________ | _________ |

---

**END OF DOCUMENT**

*This document is confidential and proprietary. Distribution is limited to authorized personnel only.*
