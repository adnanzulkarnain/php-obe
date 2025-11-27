-- =============================================================
-- DATABASE SCHEMA: Sistem Informasi Kurikulum OBE
-- Version: 3.0 (WITH KURIKULUM MANAGEMENT)
-- Date: October 22, 2025
-- DBMS: MySQL 8.0+
-- =============================================================

-- =============================================================
-- MAJOR CHANGES FROM v2.0:
-- + Added KURIKULUM as core entity
-- + CPL now belongs to KURIKULUM (not PRODI)
-- + MATAKULIAH has composite PK (kode_mk, id_kurikulum)
-- + Added PEMETAAN_MK_KURIKULUM for MK conversion
-- + MAHASISWA assigned to KURIKULUM (immutable)
-- + Support for parallel curricula
-- + Soft delete for MK (cannot be deleted, only deactivated)
-- =============================================================

-- =============================================================
-- 1. MASTER DATA: Fakultas, Prodi
-- =============================================================

CREATE TABLE fakultas (
    id_fakultas VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prodi (
    id_prodi VARCHAR(20) PRIMARY KEY,
    id_fakultas VARCHAR(20),
    nama VARCHAR(100) NOT NULL,
    jenjang VARCHAR(10) CHECK (jenjang IN ('D3','D4','S1','S2','S3')),
    akreditasi VARCHAR(5),
    tahun_berdiri INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_fakultas) REFERENCES fakultas(id_fakultas) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 2. KURIKULUM MANAGEMENT (NEW CORE ENTITY)
-- =============================================================

CREATE TABLE kurikulum (
    id_kurikulum INT AUTO_INCREMENT PRIMARY KEY,
    id_prodi VARCHAR(20),
    kode_kurikulum VARCHAR(20) NOT NULL,
    nama_kurikulum VARCHAR(100) NOT NULL,
    tahun_berlaku INT NOT NULL,
    tahun_berakhir INT,
    deskripsi TEXT,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','review','approved','aktif','non-aktif','arsip')),
    is_primary BOOLEAN DEFAULT FALSE COMMENT 'Only one primary curriculum per prodi',

    -- SK Kurikulum
    nomor_sk VARCHAR(100),
    tanggal_sk DATE,
    file_sk_path VARCHAR(500),

    -- Metadata
    created_by VARCHAR(20), -- will FK to dosen after dosen table created
    approved_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,

    UNIQUE KEY unique_prodi_kode (id_prodi, kode_kurikulum),
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Curriculum definition - container for CPL, MK structure, and learning outcomes per academic year';

-- Constraint: Only one primary curriculum per prodi (handled by trigger in MySQL)

-- =============================================================
-- 3. USER MANAGEMENT
-- =============================================================

CREATE TABLE dosen (
    id_dosen VARCHAR(20) PRIMARY KEY,
    nidn VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    id_prodi VARCHAR(20),
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','cuti','pensiun')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mahasiswa (
    nim VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    id_prodi VARCHAR(20),
    id_kurikulum INT COMMENT 'IMMUTABLE - Student follows one curriculum throughout their study, assigned at enrollment',
    angkatan VARCHAR(10) NOT NULL,
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','cuti','lulus','DO','keluar')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi),
    FOREIGN KEY (id_kurikulum) REFERENCES kurikulum(id_kurikulum) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User authentication and authorization
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type VARCHAR(20) CHECK (user_type IN ('dosen','mahasiswa','admin','kaprodi')),
    ref_id VARCHAR(20), -- id_dosen or nim
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    id_user INT,
    id_role INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, id_role),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_role) REFERENCES roles(id_role) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK from kurikulum to dosen (now that dosen exists)
ALTER TABLE kurikulum ADD CONSTRAINT fk_kurikulum_created_by
    FOREIGN KEY (created_by) REFERENCES dosen(id_dosen);
ALTER TABLE kurikulum ADD CONSTRAINT fk_kurikulum_approved_by
    FOREIGN KEY (approved_by) REFERENCES dosen(id_dosen);

-- =============================================================
-- 4. CPL: Capaian Pembelajaran Lulusan (KURIKULUM LEVEL)
-- =============================================================

CREATE TABLE cpl (
    id_cpl INT AUTO_INCREMENT PRIMARY KEY,
    id_kurikulum INT,
    kode_cpl VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori VARCHAR(50) CHECK (kategori IN ('sikap','pengetahuan','keterampilan_umum','keterampilan_khusus')),
    urutan INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kurikulum_kode (id_kurikulum, kode_cpl),
    FOREIGN KEY (id_kurikulum) REFERENCES kurikulum(id_kurikulum) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Program Learning Outcomes - defined at KURIKULUM level, can differ between curricula. Kategori according to SN-DIKTI: attitude, knowledge, general skills, specific skills';

-- =============================================================
-- 5. MATA KULIAH (KURIKULUM-SPECIFIC)
-- =============================================================

CREATE TABLE matakuliah (
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT,
    nama_mk VARCHAR(100) NOT NULL,
    nama_mk_eng VARCHAR(100),
    sks INT CHECK (sks > 0 AND sks <= 6),
    semester INT CHECK (semester BETWEEN 1 AND 14),
    rumpun VARCHAR(50),
    jenis_mk VARCHAR(50) CHECK (jenis_mk IN ('wajib','pilihan','MKWU')),
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Soft delete only - MK cannot be hard deleted per business rule BR-K03',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (kode_mk, id_kurikulum),
    FOREIGN KEY (id_kurikulum) REFERENCES kurikulum(id_kurikulum) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Course definition per curriculum - same kode_mk can exist in multiple curricula with different content';

-- Mata Kuliah Prasyarat (within same curriculum)
CREATE TABLE prasyarat_mk (
    id_prasyarat INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    kode_mk_prasyarat VARCHAR(20) NOT NULL,
    jenis_prasyarat VARCHAR(20) DEFAULT 'wajib' CHECK (jenis_prasyarat IN ('wajib','alternatif')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum),
    FOREIGN KEY (kode_mk_prasyarat, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pemetaan MK antar Kurikulum (for conversion/transfer)
CREATE TABLE pemetaan_mk_kurikulum (
    id_pemetaan INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk_lama VARCHAR(20) NOT NULL,
    id_kurikulum_lama INT NOT NULL,
    kode_mk_baru VARCHAR(20) NOT NULL,
    id_kurikulum_baru INT NOT NULL,
    tipe_pemetaan VARCHAR(20) CHECK (tipe_pemetaan IN ('ekuivalen','sebagian','diganti','dihapus')),
    bobot_konversi DECIMAL(5,2) DEFAULT 100.00 CHECK (bobot_konversi >= 0 AND bobot_konversi <= 100),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_mk_lama, id_kurikulum_lama) REFERENCES matakuliah(kode_mk, id_kurikulum),
    FOREIGN KEY (kode_mk_baru, id_kurikulum_baru) REFERENCES matakuliah(kode_mk, id_kurikulum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='MK mapping between curricula for student transfer and grade conversion. Tipe: ekuivalen=100% same, sebagian=partial match, diganti=replaced, dihapus=removed';

-- =============================================================
-- 6. RPS (RENCANA PEMBELAJARAN SEMESTER)
-- =============================================================

CREATE TABLE rps (
    id_rps INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    semester_berlaku VARCHAR(10) NOT NULL, -- "Ganjil" or "Genap"
    tahun_ajaran VARCHAR(10) NOT NULL, -- "2024/2025"
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','submitted','revised','approved','active','archived')),
    ketua_pengembang VARCHAR(20),
    tanggal_disusun DATE DEFAULT (CURDATE()),

    -- Deskripsi MK
    deskripsi_mk TEXT,
    deskripsi_singkat TEXT,

    -- Metadata
    created_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum) ON DELETE RESTRICT,
    FOREIGN KEY (ketua_pengembang) REFERENCES dosen(id_dosen),
    FOREIGN KEY (created_by) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Semester Learning Plan - one per MK per semester, belongs to specific curriculum';

-- RPS Version Control
CREATE TABLE rps_version (
    id_version INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    version_number INT NOT NULL,
    status VARCHAR(20),
    snapshot_data JSON COMMENT 'Complete RPS data snapshot',
    created_by VARCHAR(20),
    approved_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    keterangan TEXT,
    is_active BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_rps_version (id_rps, version_number),
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES dosen(id_dosen),
    FOREIGN KEY (approved_by) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RPS Approval Workflow
CREATE TABLE rps_approval (
    id_approval INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    approver VARCHAR(20),
    approval_level INT, -- 1=Ketua RPS, 2=Kaprodi, 3=Dekan
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected','revised')),
    komentar TEXT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE,
    FOREIGN KEY (approver) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 7. CPMK & SubCPMK (COURSE LEVEL)
-- =============================================================

CREATE TABLE cpmk (
    id_cpmk INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    kode_cpmk VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    urutan INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subcpmk (
    id_subcpmk INT AUTO_INCREMENT PRIMARY KEY,
    id_cpmk INT,
    kode_subcpmk VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    indikator TEXT,
    urutan INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cpmk) REFERENCES cpmk(id_cpmk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relasi CPMK ↔ CPL (Many-to-Many) within same curriculum
CREATE TABLE relasi_cpmk_cpl (
    id_relasi INT AUTO_INCREMENT PRIMARY KEY,
    id_cpmk INT,
    id_cpl INT,
    bobot_kontribusi DECIMAL(5,2) DEFAULT 100.00 CHECK (bobot_kontribusi > 0 AND bobot_kontribusi <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cpmk_cpl (id_cpmk, id_cpl),
    FOREIGN KEY (id_cpmk) REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    FOREIGN KEY (id_cpl) REFERENCES cpl(id_cpl) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Maps CPMK to CPL with contribution weight - must be within same curriculum';

-- =============================================================
-- 8. KELAS & ENROLLMENT
-- =============================================================

CREATE TABLE kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    id_rps INT,
    nama_kelas VARCHAR(10) NOT NULL, -- A, B, C, etc
    semester VARCHAR(10) NOT NULL, -- "Ganjil" or "Genap"
    tahun_ajaran VARCHAR(10) NOT NULL, -- "2024/2025"
    kapasitas INT DEFAULT 40,
    kuota_terisi INT DEFAULT 0,

    -- Jadwal
    hari VARCHAR(20),
    jam_mulai TIME,
    jam_selesai TIME,
    ruangan VARCHAR(50),

    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','open','closed','completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum) ON DELETE RESTRICT,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE RESTRICT,
    UNIQUE KEY unique_kelas (kode_mk, id_kurikulum, nama_kelas, semester, tahun_ajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Class offering - one MK can have multiple classes (A, B, C) per curriculum';

-- Dosen pengampu per kelas (supporting team teaching)
CREATE TABLE tugas_mengajar (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    id_dosen VARCHAR(20),
    peran VARCHAR(50) CHECK (peran IN ('koordinator','pengampu','asisten')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kelas_dosen (id_kelas, id_dosen),
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollment mahasiswa (KRS)
CREATE TABLE enrollment (
    id_enrollment INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20),
    id_kelas INT,
    tanggal_daftar DATE DEFAULT (CURDATE()),
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','mengulang','drop','lulus')),
    nilai_akhir DECIMAL(5,2),
    nilai_huruf VARCHAR(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nim_kelas (nim, id_kelas),
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Student enrollment in a class - links mahasiswa to kelas';

-- =============================================================
-- 9. SISTEM PENILAIAN
-- =============================================================

-- Master jenis penilaian
CREATE TABLE jenis_penilaian (
    id_jenis INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template bobot penilaian per RPS
CREATE TABLE template_penilaian (
    id_template INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    id_cpmk INT,
    id_jenis INT,
    bobot DECIMAL(5,2) CHECK (bobot >= 0 AND bobot <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE,
    FOREIGN KEY (id_cpmk) REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    FOREIGN KEY (id_jenis) REFERENCES jenis_penilaian(id_jenis) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Komponen penilaian aktual per kelas
CREATE TABLE komponen_penilaian (
    id_komponen INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    id_template INT,
    nama_komponen VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    tanggal_pelaksanaan DATE,
    deadline DATE,
    bobot_realisasi DECIMAL(5,2),
    nilai_maksimal DECIMAL(5,2) DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_template) REFERENCES template_penilaian(id_template)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nilai mahasiswa per komponen
CREATE TABLE nilai_detail (
    id_nilai_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_enrollment INT,
    id_komponen INT,
    nilai_mentah DECIMAL(5,2) CHECK (nilai_mentah >= 0),
    nilai_tertimbang DECIMAL(5,2),
    catatan TEXT,
    dinilai_oleh VARCHAR(20),
    tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment_komponen (id_enrollment, id_komponen),
    FOREIGN KEY (id_enrollment) REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    FOREIGN KEY (id_komponen) REFERENCES komponen_penilaian(id_komponen) ON DELETE CASCADE,
    FOREIGN KEY (dinilai_oleh) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Summary ketercapaian CPMK per mahasiswa
CREATE TABLE ketercapaian_cpmk (
    id_ketercapaian INT AUTO_INCREMENT PRIMARY KEY,
    id_enrollment INT,
    id_cpmk INT,
    nilai_cpmk DECIMAL(5,2),
    status_tercapai BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment_cpmk (id_enrollment, id_cpmk),
    FOREIGN KEY (id_enrollment) REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    FOREIGN KEY (id_cpmk) REFERENCES cpmk(id_cpmk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 10. RENCANA PEMBELAJARAN MINGGUAN
-- =============================================================

CREATE TABLE rencana_mingguan (
    id_minggu INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    minggu_ke INT CHECK (minggu_ke > 0 AND minggu_ke <= 16),
    id_subcpmk INT,

    -- JSON for flexibility (converted from JSONB)
    materi JSON,
    metode JSON,
    aktivitas JSON,

    -- Media
    media_software TEXT,
    media_hardware TEXT,
    pengalaman_belajar TEXT,
    estimasi_waktu_menit INT DEFAULT 150,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rps_minggu (id_rps, minggu_ke),
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE,
    FOREIGN KEY (id_subcpmk) REFERENCES subcpmk(id_subcpmk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Realisasi pertemuan
CREATE TABLE realisasi_pertemuan (
    id_realisasi INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT,
    id_minggu INT,
    tanggal_pelaksanaan DATE NOT NULL,
    materi_disampaikan TEXT,
    metode_digunakan TEXT,
    kendala TEXT,
    catatan_dosen TEXT,
    created_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_minggu) REFERENCES rencana_mingguan(id_minggu),
    FOREIGN KEY (created_by) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kehadiran mahasiswa
CREATE TABLE kehadiran (
    id_kehadiran INT AUTO_INCREMENT PRIMARY KEY,
    id_realisasi INT,
    nim VARCHAR(20),
    status VARCHAR(10) CHECK (status IN ('hadir','izin','sakit','alpha')),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_realisasi_nim (id_realisasi, nim),
    FOREIGN KEY (id_realisasi) REFERENCES realisasi_pertemuan(id_realisasi) ON DELETE CASCADE,
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 11. PUSTAKA & MEDIA
-- =============================================================

CREATE TABLE pustaka (
    id_pustaka INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    jenis VARCHAR(20) CHECK (jenis IN ('utama','pendukung')),
    referensi TEXT NOT NULL,
    penulis VARCHAR(200),
    tahun INT,
    penerbit VARCHAR(100),
    isbn VARCHAR(20),
    url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_pembelajaran (
    id_media INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    kategori VARCHAR(20) CHECK (kategori IN ('software','hardware','platform')),
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 12. AMBANG BATAS & KONFIGURASI
-- =============================================================

CREATE TABLE ambang_batas (
    id_ambang INT AUTO_INCREMENT PRIMARY KEY,
    id_rps INT,
    batas_kelulusan_cpmk DECIMAL(5,2) DEFAULT 40.01,
    batas_kelulusan_mk DECIMAL(5,2) DEFAULT 50.00,
    persentase_mahasiswa_lulus DECIMAL(5,2) DEFAULT 75.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rps) REFERENCES rps(id_rps) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE konfigurasi_prodi (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    id_prodi VARCHAR(20),
    `key` VARCHAR(100) NOT NULL,
    value TEXT,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prodi_key (id_prodi, `key`),
    FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 13. AUDIT TRAIL & LOGGING
-- =============================================================

CREATE TABLE audit_log (
    id_audit INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    action VARCHAR(20) CHECK (action IN ('INSERT','UPDATE','DELETE','APPROVE','REJECT')),
    old_data JSON,
    new_data JSON,
    user_id INT,
    ip_address VARCHAR(45), -- IPv6 compatible
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 14. NOTIFICATION SYSTEM
-- =============================================================

CREATE TABLE notifications (
    id_notif INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 15. DOCUMENT MANAGEMENT
-- =============================================================

CREATE TABLE documents (
    id_document INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50),
    entity_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- PERFORMANCE INDEXES
-- =============================================================

-- Kurikulum indexes
CREATE INDEX idx_kurikulum_prodi ON kurikulum(id_prodi, status);
CREATE INDEX idx_kurikulum_tahun ON kurikulum(tahun_berlaku, tahun_berakhir);

-- CPL indexes
CREATE INDEX idx_cpl_kurikulum ON cpl(id_kurikulum);
CREATE INDEX idx_cpl_kategori ON cpl(id_kurikulum, kategori);

-- Matakuliah indexes
CREATE INDEX idx_mk_kurikulum ON matakuliah(id_kurikulum, is_active);
CREATE INDEX idx_mk_semester ON matakuliah(id_kurikulum, semester);

-- Mahasiswa indexes
CREATE INDEX idx_mahasiswa_kurikulum ON mahasiswa(id_kurikulum, status);
CREATE INDEX idx_mahasiswa_angkatan ON mahasiswa(id_kurikulum, angkatan);

-- RPS indexes
CREATE INDEX idx_rps_mk_kurikulum ON rps(kode_mk, id_kurikulum);
CREATE INDEX idx_rps_status ON rps(status);

-- CPMK indexes
CREATE INDEX idx_cpmk_rps ON cpmk(id_rps);
CREATE INDEX idx_subcpmk_cpmk ON subcpmk(id_cpmk);
CREATE INDEX idx_relasi_cpmk ON relasi_cpmk_cpl(id_cpmk);
CREATE INDEX idx_relasi_cpl ON relasi_cpmk_cpl(id_cpl);

-- Kelas & Enrollment indexes
CREATE INDEX idx_kelas_mk_kurikulum ON kelas(kode_mk, id_kurikulum, semester, tahun_ajaran);
CREATE INDEX idx_enrollment_mahasiswa ON enrollment(nim);
CREATE INDEX idx_enrollment_kelas ON enrollment(id_kelas);

-- Penilaian indexes
CREATE INDEX idx_template_rps ON template_penilaian(id_rps);
CREATE INDEX idx_komponen_kelas ON komponen_penilaian(id_kelas);
CREATE INDEX idx_nilai_enrollment ON nilai_detail(id_enrollment);
CREATE INDEX idx_ketercapaian_enrollment ON ketercapaian_cpmk(id_enrollment);

-- Audit & Notification indexes
CREATE INDEX idx_audit_table ON audit_log(table_name, record_id);
CREATE INDEX idx_audit_created ON audit_log(created_at DESC);
CREATE INDEX idx_notif_user ON notifications(user_id, is_read, created_at DESC);

-- =============================================================
-- VIEWS FOR ANALYTICS (MySQL doesn't support materialized views)
-- =============================================================

-- View: Ketercapaian CPMK per Kelas (with curriculum info)
CREATE OR REPLACE VIEW mv_ketercapaian_kelas AS
SELECT
    k.id_kelas,
    k.nama_kelas,
    k.semester,
    k.tahun_ajaran,
    mk.kode_mk,
    mk.nama_mk,
    kur.kode_kurikulum,
    kur.nama_kurikulum,
    cm.id_cpmk,
    cm.kode_cpmk,
    cm.deskripsi as deskripsi_cpmk,
    COUNT(DISTINCT e.nim) as jumlah_mahasiswa,
    AVG(kc.nilai_cpmk) as rata_nilai_cpmk,
    SUM(CASE WHEN kc.status_tercapai = TRUE THEN 1 ELSE 0 END) as jumlah_lulus,
    ROUND(SUM(CASE WHEN kc.status_tercapai = TRUE THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT e.nim), 0) * 100, 2) as persentase_lulus
FROM kelas k
JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
JOIN kurikulum kur ON mk.id_kurikulum = kur.id_kurikulum
JOIN enrollment e ON k.id_kelas = e.id_kelas
JOIN cpmk cm ON cm.id_rps = k.id_rps
LEFT JOIN ketercapaian_cpmk kc ON kc.id_enrollment = e.id_enrollment AND kc.id_cpmk = cm.id_cpmk
GROUP BY k.id_kelas, k.nama_kelas, k.semester, k.tahun_ajaran, mk.kode_mk, mk.nama_mk,
         kur.kode_kurikulum, kur.nama_kurikulum, cm.id_cpmk, cm.kode_cpmk, cm.deskripsi;

-- View: Ketercapaian CPL per Kurikulum
CREATE OR REPLACE VIEW mv_ketercapaian_cpl AS
SELECT
    kur.id_kurikulum,
    kur.kode_kurikulum,
    kur.nama_kurikulum,
    p.id_prodi,
    p.nama as nama_prodi,
    cpl.id_cpl,
    cpl.kode_cpl,
    cpl.deskripsi as deskripsi_cpl,
    cpl.kategori,
    COUNT(DISTINCT e.nim) as total_mahasiswa,
    AVG(kc.nilai_cpmk * rcl.bobot_kontribusi / 100) as rata_kontribusi_tertimbang,
    COUNT(DISTINCT rcl.id_cpmk) as jumlah_cpmk_terkait
FROM kurikulum kur
JOIN prodi p ON kur.id_prodi = p.id_prodi
JOIN cpl ON cpl.id_kurikulum = kur.id_kurikulum
JOIN relasi_cpmk_cpl rcl ON rcl.id_cpl = cpl.id_cpl
JOIN ketercapaian_cpmk kc ON kc.id_cpmk = rcl.id_cpmk
JOIN enrollment e ON e.id_enrollment = kc.id_enrollment
JOIN mahasiswa m ON m.nim = e.nim
WHERE cpl.is_active = TRUE AND m.id_kurikulum = kur.id_kurikulum
GROUP BY kur.id_kurikulum, kur.kode_kurikulum, kur.nama_kurikulum, p.id_prodi, p.nama,
         cpl.id_cpl, cpl.kode_cpl, cpl.deskripsi, cpl.kategori;

-- View: Statistik Kurikulum per Prodi
CREATE OR REPLACE VIEW mv_statistik_kurikulum AS
SELECT
    kur.id_kurikulum,
    kur.kode_kurikulum,
    kur.nama_kurikulum,
    kur.status,
    kur.tahun_berlaku,
    p.id_prodi,
    p.nama as nama_prodi,
    COUNT(DISTINCT cpl.id_cpl) as jumlah_cpl,
    COUNT(DISTINCT mk.kode_mk) as jumlah_mk,
    SUM(CASE WHEN m.status = 'aktif' THEN 1 ELSE 0 END) as mahasiswa_aktif,
    SUM(CASE WHEN m.status = 'lulus' THEN 1 ELSE 0 END) as mahasiswa_lulus,
    COUNT(DISTINCT m.angkatan) as jumlah_angkatan
FROM kurikulum kur
JOIN prodi p ON kur.id_prodi = p.id_prodi
LEFT JOIN cpl ON cpl.id_kurikulum = kur.id_kurikulum
LEFT JOIN matakuliah mk ON mk.id_kurikulum = kur.id_kurikulum AND mk.is_active = TRUE
LEFT JOIN mahasiswa m ON m.id_kurikulum = kur.id_kurikulum
GROUP BY kur.id_kurikulum, kur.kode_kurikulum, kur.nama_kurikulum, kur.status,
         kur.tahun_berlaku, p.id_prodi, p.nama;

-- =============================================================
-- TRIGGERS
-- =============================================================

DELIMITER $$

-- Trigger: Validate CPMK and CPL must be in same curriculum
CREATE TRIGGER trigger_validate_cpmk_cpl_kurikulum
BEFORE INSERT ON relasi_cpmk_cpl
FOR EACH ROW
BEGIN
    DECLARE v_kurikulum_cpmk INT;
    DECLARE v_kurikulum_cpl INT;

    -- Get kurikulum from CPMK
    SELECT rps.id_kurikulum INTO v_kurikulum_cpmk
    FROM cpmk
    JOIN rps ON rps.id_rps = cpmk.id_rps
    WHERE cpmk.id_cpmk = NEW.id_cpmk;

    -- Get kurikulum from CPL
    SELECT id_kurikulum INTO v_kurikulum_cpl
    FROM cpl
    WHERE id_cpl = NEW.id_cpl;

    -- Validate
    IF v_kurikulum_cpmk != v_kurikulum_cpl THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'CPMK and CPL must belong to the same curriculum';
    END IF;
END$$

-- Trigger: Validate enrollment kurikulum
CREATE TRIGGER trigger_validate_enrollment_kurikulum
BEFORE INSERT ON enrollment
FOR EACH ROW
BEGIN
    DECLARE v_kurikulum_mahasiswa INT;
    DECLARE v_kurikulum_kelas INT;

    -- Get kurikulum mahasiswa
    SELECT id_kurikulum INTO v_kurikulum_mahasiswa
    FROM mahasiswa
    WHERE nim = NEW.nim;

    -- Get kurikulum kelas
    SELECT id_kurikulum INTO v_kurikulum_kelas
    FROM kelas
    WHERE id_kelas = NEW.id_kelas;

    -- Validate
    IF v_kurikulum_mahasiswa != v_kurikulum_kelas THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Student can only enroll in classes from their curriculum (BR-K04)';
    END IF;
END$$

-- Trigger: Auto-calculate nilai_tertimbang
CREATE TRIGGER trigger_calculate_nilai_tertimbang
BEFORE INSERT ON nilai_detail
FOR EACH ROW
BEGIN
    DECLARE v_bobot DECIMAL(5,2);
    DECLARE v_nilai_maksimal DECIMAL(5,2);

    SELECT bobot_realisasi, nilai_maksimal
    INTO v_bobot, v_nilai_maksimal
    FROM komponen_penilaian
    WHERE id_komponen = NEW.id_komponen;

    SET NEW.nilai_tertimbang = (NEW.nilai_mentah / v_nilai_maksimal) * v_bobot;
END$$

CREATE TRIGGER trigger_calculate_nilai_tertimbang_update
BEFORE UPDATE ON nilai_detail
FOR EACH ROW
BEGIN
    DECLARE v_bobot DECIMAL(5,2);
    DECLARE v_nilai_maksimal DECIMAL(5,2);

    SELECT bobot_realisasi, nilai_maksimal
    INTO v_bobot, v_nilai_maksimal
    FROM komponen_penilaian
    WHERE id_komponen = NEW.id_komponen;

    SET NEW.nilai_tertimbang = (NEW.nilai_mentah / v_nilai_maksimal) * v_bobot;
END$$

-- Trigger: Prevent hard delete of MK (enforce soft delete)
CREATE TRIGGER trigger_prevent_mk_delete
BEFORE DELETE ON matakuliah
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Hard delete not allowed for matakuliah. Use is_active = FALSE instead (BR-K03)';
END$$

-- Trigger: Prevent changing mahasiswa kurikulum (immutable)
CREATE TRIGGER trigger_prevent_kurikulum_change
BEFORE UPDATE ON mahasiswa
FOR EACH ROW
BEGIN
    IF OLD.id_kurikulum != NEW.id_kurikulum THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot change mahasiswa curriculum - it is immutable (BR-K01)';
    END IF;
END$$

-- Trigger: Ensure only one primary curriculum per prodi
CREATE TRIGGER trigger_check_primary_kurikulum_insert
BEFORE INSERT ON kurikulum
FOR EACH ROW
BEGIN
    DECLARE v_count INT;

    IF NEW.is_primary = TRUE THEN
        SELECT COUNT(*) INTO v_count
        FROM kurikulum
        WHERE id_prodi = NEW.id_prodi AND is_primary = TRUE;

        IF v_count > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only one primary curriculum allowed per prodi';
        END IF;
    END IF;
END$$

CREATE TRIGGER trigger_check_primary_kurikulum_update
BEFORE UPDATE ON kurikulum
FOR EACH ROW
BEGIN
    DECLARE v_count INT;

    IF NEW.is_primary = TRUE AND OLD.is_primary = FALSE THEN
        SELECT COUNT(*) INTO v_count
        FROM kurikulum
        WHERE id_prodi = NEW.id_prodi AND is_primary = TRUE AND id_kurikulum != NEW.id_kurikulum;

        IF v_count > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only one primary curriculum allowed per prodi';
        END IF;
    END IF;
END$$

DELIMITER ;

-- =============================================================
-- SAMPLE DATA
-- =============================================================

-- Insert sample roles
INSERT INTO roles (role_name, description) VALUES
('admin', 'System Administrator'),
('kaprodi', 'Ketua Program Studi'),
('dosen', 'Dosen Pengampu'),
('mahasiswa', 'Mahasiswa');

-- Insert sample jenis penilaian
INSERT INTO jenis_penilaian (nama_jenis, deskripsi) VALUES
('Quiz', 'Kuis singkat'),
('Tugas', 'Tugas individu atau kelompok'),
('Praktikum', 'Praktikum atau lab'),
('UTS', 'Ujian Tengah Semester'),
('UAS', 'Ujian Akhir Semester'),
('Project', 'Project akhir');

-- Sample Fakultas & Prodi
INSERT INTO fakultas (id_fakultas, nama) VALUES
('FTI', 'Fakultas Teknologi Industri');

INSERT INTO prodi (id_prodi, id_fakultas, nama, jenjang, akreditasi) VALUES
('TIF', 'FTI', 'Teknik Informatika', 'S1', 'A');

-- Sample Kurikulum
INSERT INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, status, is_primary, nomor_sk) VALUES
('TIF', 'K2024', 'Kurikulum OBE 2024', 2024, 'aktif', FALSE, 'SK/001/2024'),
('TIF', 'K2029', 'Kurikulum OBE 2029', 2029, 'aktif', TRUE, 'SK/002/2029');

-- =============================================================
-- END OF SCHEMA
-- =============================================================

SELECT 'Database schema v3.0 created successfully!' as status,
       'WITH KURIKULUM MANAGEMENT (MySQL)' as feature,
       COUNT(*) as total_tables
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
