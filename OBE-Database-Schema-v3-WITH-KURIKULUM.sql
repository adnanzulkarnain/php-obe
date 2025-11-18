-- =============================================================
-- DATABASE SCHEMA: Sistem Informasi Kurikulum OBE
-- Version: 3.0 (WITH KURIKULUM MANAGEMENT)
-- Date: October 22, 2025
-- DBMS: PostgreSQL 14+
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

-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- =============================================================
-- 1. MASTER DATA: Fakultas, Prodi
-- =============================================================

CREATE TABLE fakultas (
    id_fakultas VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE prodi (
    id_prodi VARCHAR(20) PRIMARY KEY,
    id_fakultas VARCHAR(20) REFERENCES fakultas(id_fakultas) ON DELETE RESTRICT,
    nama VARCHAR(100) NOT NULL,
    jenjang VARCHAR(10) CHECK (jenjang IN ('D3','D4','S1','S2','S3')),
    akreditasi VARCHAR(5),
    tahun_berdiri INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- =============================================================
-- 2. KURIKULUM MANAGEMENT (NEW CORE ENTITY)
-- =============================================================

CREATE TABLE kurikulum (
    id_kurikulum SERIAL PRIMARY KEY,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi) ON DELETE RESTRICT,
    kode_kurikulum VARCHAR(20) NOT NULL,
    nama_kurikulum VARCHAR(100) NOT NULL,
    tahun_berlaku INT NOT NULL,
    tahun_berakhir INT,
    deskripsi TEXT,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','review','approved','aktif','non-aktif','arsip')),
    is_primary BOOLEAN DEFAULT FALSE, -- Only one primary curriculum per prodi
    
    -- SK Kurikulum
    nomor_sk VARCHAR(100),
    tanggal_sk DATE,
    file_sk_path VARCHAR(500),
    
    -- Metadata
    created_by VARCHAR(20), -- will FK to dosen after dosen table created
    approved_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    approved_at TIMESTAMP,
    
    UNIQUE (id_prodi, kode_kurikulum)
);

COMMENT ON TABLE kurikulum IS 'Curriculum definition - container for CPL, MK structure, and learning outcomes per academic year';
COMMENT ON COLUMN kurikulum.is_primary IS 'Only one primary (default) curriculum per prodi for new students';
COMMENT ON COLUMN kurikulum.tahun_berakhir IS 'NULL if still accepting new students';

-- Constraint: Only one primary curriculum per prodi
CREATE UNIQUE INDEX idx_kurikulum_primary 
ON kurikulum (id_prodi) 
WHERE is_primary = TRUE;

-- =============================================================
-- 3. USER MANAGEMENT
-- =============================================================

CREATE TABLE dosen (
    id_dosen VARCHAR(20) PRIMARY KEY,
    nidn VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','cuti','pensiun')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE mahasiswa (
    nim VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi),
    id_kurikulum INT REFERENCES kurikulum(id_kurikulum) ON DELETE RESTRICT, -- IMMUTABLE
    angkatan VARCHAR(10) NOT NULL,
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','cuti','lulus','DO','keluar')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

COMMENT ON COLUMN mahasiswa.id_kurikulum IS 'IMMUTABLE - Student follows one curriculum throughout their study, assigned at enrollment';

-- User authentication and authorization
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
    id_user INT REFERENCES users(id_user) ON DELETE CASCADE,
    id_role INT REFERENCES roles(id_role) ON DELETE CASCADE,
    granted_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (id_user, id_role)
);

-- Add FK from kurikulum to dosen (now that dosen exists)
ALTER TABLE kurikulum ADD CONSTRAINT fk_kurikulum_created_by 
    FOREIGN KEY (created_by) REFERENCES dosen(id_dosen);
ALTER TABLE kurikulum ADD CONSTRAINT fk_kurikulum_approved_by 
    FOREIGN KEY (approved_by) REFERENCES dosen(id_dosen);

-- =============================================================
-- 4. CPL: Capaian Pembelajaran Lulusan (KURIKULUM LEVEL)
-- =============================================================

CREATE TABLE cpl (
    id_cpl SERIAL PRIMARY KEY,
    id_kurikulum INT REFERENCES kurikulum(id_kurikulum) ON DELETE CASCADE,
    kode_cpl VARCHAR(20) NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori VARCHAR(50) CHECK (kategori IN ('sikap','pengetahuan','keterampilan_umum','keterampilan_khusus')),
    urutan INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_kurikulum, kode_cpl)
);

COMMENT ON TABLE cpl IS 'Program Learning Outcomes - defined at KURIKULUM level, can differ between curricula';
COMMENT ON COLUMN cpl.kategori IS 'According to SN-DIKTI: attitude, knowledge, general skills, specific skills';

-- =============================================================
-- 5. MATA KULIAH (KURIKULUM-SPECIFIC)
-- =============================================================

CREATE TABLE matakuliah (
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT REFERENCES kurikulum(id_kurikulum) ON DELETE RESTRICT,
    nama_mk VARCHAR(100) NOT NULL,
    nama_mk_eng VARCHAR(100),
    sks INT CHECK (sks > 0 AND sks <= 6),
    semester INT CHECK (semester BETWEEN 1 AND 14),
    rumpun VARCHAR(50),
    jenis_mk VARCHAR(50) CHECK (jenis_mk IN ('wajib','pilihan','MKWU')),
    is_active BOOLEAN DEFAULT TRUE, -- Soft delete only
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (kode_mk, id_kurikulum)
);

COMMENT ON TABLE matakuliah IS 'Course definition per curriculum - same kode_mk can exist in multiple curricula with different content';
COMMENT ON COLUMN matakuliah.is_active IS 'Soft delete only - MK cannot be hard deleted per business rule BR-K03';

-- Mata Kuliah Prasyarat (within same curriculum)
CREATE TABLE prasyarat_mk (
    id_prasyarat SERIAL PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    kode_mk_prasyarat VARCHAR(20) NOT NULL,
    jenis_prasyarat VARCHAR(20) DEFAULT 'wajib' CHECK (jenis_prasyarat IN ('wajib','alternatif')),
    created_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum),
    FOREIGN KEY (kode_mk_prasyarat, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum)
);

-- Pemetaan MK antar Kurikulum (for conversion/transfer)
CREATE TABLE pemetaan_mk_kurikulum (
    id_pemetaan SERIAL PRIMARY KEY,
    kode_mk_lama VARCHAR(20) NOT NULL,
    id_kurikulum_lama INT NOT NULL,
    kode_mk_baru VARCHAR(20) NOT NULL,
    id_kurikulum_baru INT NOT NULL,
    tipe_pemetaan VARCHAR(20) CHECK (tipe_pemetaan IN ('ekuivalen','sebagian','diganti','dihapus')),
    bobot_konversi NUMERIC(5,2) DEFAULT 100.00 CHECK (bobot_konversi >= 0 AND bobot_konversi <= 100),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (kode_mk_lama, id_kurikulum_lama) REFERENCES matakuliah(kode_mk, id_kurikulum),
    FOREIGN KEY (kode_mk_baru, id_kurikulum_baru) REFERENCES matakuliah(kode_mk, id_kurikulum)
);

COMMENT ON TABLE pemetaan_mk_kurikulum IS 'MK mapping between curricula for student transfer and grade conversion';
COMMENT ON COLUMN pemetaan_mk_kurikulum.tipe_pemetaan IS 'ekuivalen=100% same, sebagian=partial match, diganti=replaced, dihapus=removed';

-- =============================================================
-- 6. RPS (RENCANA PEMBELAJARAN SEMESTER)
-- =============================================================

CREATE TABLE rps (
    id_rps SERIAL PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    semester_berlaku VARCHAR(10) NOT NULL, -- "Ganjil" or "Genap"
    tahun_ajaran VARCHAR(10) NOT NULL, -- "2024/2025"
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','submitted','revised','approved','active','archived')),
    ketua_pengembang VARCHAR(20) REFERENCES dosen(id_dosen),
    tanggal_disusun DATE DEFAULT CURRENT_DATE,
    
    -- Deskripsi MK
    deskripsi_mk TEXT,
    deskripsi_singkat TEXT,
    
    -- Metadata
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum) ON DELETE RESTRICT
);

COMMENT ON TABLE rps IS 'Semester Learning Plan - one per MK per semester, belongs to specific curriculum';

-- RPS Version Control
CREATE TABLE rps_version (
    id_version SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    version_number INT NOT NULL,
    status VARCHAR(20),
    snapshot_data JSONB, -- Complete RPS data snapshot
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    approved_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW(),
    approved_at TIMESTAMP,
    keterangan TEXT,
    is_active BOOLEAN DEFAULT FALSE,
    UNIQUE (id_rps, version_number)
);

-- RPS Approval Workflow
CREATE TABLE rps_approval (
    id_approval SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    approver VARCHAR(20) REFERENCES dosen(id_dosen),
    approval_level INT, -- 1=Ketua RPS, 2=Kaprodi, 3=Dekan
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected','revised')),
    komentar TEXT,
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- =============================================================
-- 7. CPMK & SubCPMK (COURSE LEVEL)
-- =============================================================

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

-- Relasi CPMK ↔ CPL (Many-to-Many) within same curriculum
CREATE TABLE relasi_cpmk_cpl (
    id_relasi SERIAL PRIMARY KEY,
    id_cpmk INT REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    id_cpl INT REFERENCES cpl(id_cpl) ON DELETE CASCADE,
    bobot_kontribusi NUMERIC(5,2) DEFAULT 100.00 CHECK (bobot_kontribusi > 0 AND bobot_kontribusi <= 100),
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_cpmk, id_cpl)
);

COMMENT ON TABLE relasi_cpmk_cpl IS 'Maps CPMK to CPL with contribution weight - must be within same curriculum';

-- Validation: CPMK and CPL must belong to same curriculum
CREATE OR REPLACE FUNCTION validate_cpmk_cpl_same_kurikulum()
RETURNS TRIGGER AS $$
DECLARE
    v_kurikulum_cpmk INT;
    v_kurikulum_cpl INT;
BEGIN
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
        RAISE EXCEPTION 'CPMK and CPL must belong to the same curriculum';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_cpmk_cpl_kurikulum
BEFORE INSERT OR UPDATE ON relasi_cpmk_cpl
FOR EACH ROW EXECUTE FUNCTION validate_cpmk_cpl_same_kurikulum();

-- =============================================================
-- 8. KELAS & ENROLLMENT
-- =============================================================

CREATE TABLE kelas (
    id_kelas SERIAL PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL,
    id_kurikulum INT NOT NULL,
    id_rps INT REFERENCES rps(id_rps) ON DELETE RESTRICT,
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
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (kode_mk, id_kurikulum) REFERENCES matakuliah(kode_mk, id_kurikulum) ON DELETE RESTRICT,
    UNIQUE (kode_mk, id_kurikulum, nama_kelas, semester, tahun_ajaran)
);

COMMENT ON TABLE kelas IS 'Class offering - one MK can have multiple classes (A, B, C) per curriculum';

-- Dosen pengampu per kelas (supporting team teaching)
CREATE TABLE tugas_mengajar (
    id_tugas SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    id_dosen VARCHAR(20) REFERENCES dosen(id_dosen) ON DELETE RESTRICT,
    peran VARCHAR(50) CHECK (peran IN ('koordinator','pengampu','asisten')),
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_kelas, id_dosen)
);

-- Enrollment mahasiswa (KRS)
CREATE TABLE enrollment (
    id_enrollment SERIAL PRIMARY KEY,
    nim VARCHAR(20) REFERENCES mahasiswa(nim) ON DELETE CASCADE,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    tanggal_daftar DATE DEFAULT CURRENT_DATE,
    status VARCHAR(20) DEFAULT 'aktif' CHECK (status IN ('aktif','mengulang','drop','lulus')),
    nilai_akhir NUMERIC(5,2),
    nilai_huruf VARCHAR(2),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (nim, id_kelas)
);

COMMENT ON TABLE enrollment IS 'Student enrollment in a class - links mahasiswa to kelas';

-- Validation: Mahasiswa can only enroll in classes from their curriculum
CREATE OR REPLACE FUNCTION validate_enrollment_kurikulum()
RETURNS TRIGGER AS $$
DECLARE
    v_kurikulum_mahasiswa INT;
    v_kurikulum_kelas INT;
BEGIN
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
        RAISE EXCEPTION 'Student can only enroll in classes from their curriculum (BR-K04)';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_enrollment_kurikulum
BEFORE INSERT ON enrollment
FOR EACH ROW EXECUTE FUNCTION validate_enrollment_kurikulum();

-- =============================================================
-- 9. SISTEM PENILAIAN
-- =============================================================

-- Master jenis penilaian
CREATE TABLE jenis_penilaian (
    id_jenis SERIAL PRIMARY KEY,
    nama_jenis VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT
);

-- Template bobot penilaian per RPS
CREATE TABLE template_penilaian (
    id_template SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    id_cpmk INT REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    id_jenis INT REFERENCES jenis_penilaian(id_jenis) ON DELETE RESTRICT,
    bobot NUMERIC(5,2) CHECK (bobot >= 0 AND bobot <= 100),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Komponen penilaian aktual per kelas
CREATE TABLE komponen_penilaian (
    id_komponen SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    id_template INT REFERENCES template_penilaian(id_template),
    nama_komponen VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    tanggal_pelaksanaan DATE,
    deadline DATE,
    bobot_realisasi NUMERIC(5,2),
    nilai_maksimal NUMERIC(5,2) DEFAULT 100,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Nilai mahasiswa per komponen
CREATE TABLE nilai_detail (
    id_nilai_detail SERIAL PRIMARY KEY,
    id_enrollment INT REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    id_komponen INT REFERENCES komponen_penilaian(id_komponen) ON DELETE CASCADE,
    nilai_mentah NUMERIC(5,2) CHECK (nilai_mentah >= 0),
    nilai_tertimbang NUMERIC(5,2),
    catatan TEXT,
    dinilai_oleh VARCHAR(20) REFERENCES dosen(id_dosen),
    tanggal_input TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_enrollment, id_komponen)
);

-- Summary ketercapaian CPMK per mahasiswa
CREATE TABLE ketercapaian_cpmk (
    id_ketercapaian SERIAL PRIMARY KEY,
    id_enrollment INT REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    id_cpmk INT REFERENCES cpmk(id_cpmk) ON DELETE CASCADE,
    nilai_cpmk NUMERIC(5,2),
    status_tercapai BOOLEAN,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_enrollment, id_cpmk)
);

-- =============================================================
-- 10. RENCANA PEMBELAJARAN MINGGUAN
-- =============================================================

CREATE TABLE rencana_mingguan (
    id_minggu SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    minggu_ke INT CHECK (minggu_ke > 0 AND minggu_ke <= 16),
    id_subcpmk INT REFERENCES subcpmk(id_subcpmk),
    
    -- JSONB for flexibility
    materi JSONB,
    metode JSONB,
    aktivitas JSONB,
    
    -- Media
    media_software TEXT,
    media_hardware TEXT,
    pengalaman_belajar TEXT,
    estimasi_waktu_menit INT DEFAULT 150,
    
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_rps, minggu_ke)
);

-- Realisasi pertemuan
CREATE TABLE realisasi_pertemuan (
    id_realisasi SERIAL PRIMARY KEY,
    id_kelas INT REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    id_minggu INT REFERENCES rencana_mingguan(id_minggu),
    tanggal_pelaksanaan DATE NOT NULL,
    materi_disampaikan TEXT,
    metode_digunakan TEXT,
    kendala TEXT,
    catatan_dosen TEXT,
    created_by VARCHAR(20) REFERENCES dosen(id_dosen),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Kehadiran mahasiswa
CREATE TABLE kehadiran (
    id_kehadiran SERIAL PRIMARY KEY,
    id_realisasi INT REFERENCES realisasi_pertemuan(id_realisasi) ON DELETE CASCADE,
    nim VARCHAR(20) REFERENCES mahasiswa(nim) ON DELETE CASCADE,
    status VARCHAR(10) CHECK (status IN ('hadir','izin','sakit','alpha')),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_realisasi, nim)
);

-- =============================================================
-- 11. PUSTAKA & MEDIA
-- =============================================================

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

-- =============================================================
-- 12. AMBANG BATAS & KONFIGURASI
-- =============================================================

CREATE TABLE ambang_batas (
    id_ambang SERIAL PRIMARY KEY,
    id_rps INT REFERENCES rps(id_rps) ON DELETE CASCADE,
    batas_kelulusan_cpmk NUMERIC(5,2) DEFAULT 40.01,
    batas_kelulusan_mk NUMERIC(5,2) DEFAULT 50.00,
    persentase_mahasiswa_lulus NUMERIC(5,2) DEFAULT 75.00,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE konfigurasi_prodi (
    id_config SERIAL PRIMARY KEY,
    id_prodi VARCHAR(20) REFERENCES prodi(id_prodi) ON DELETE CASCADE,
    key VARCHAR(100) NOT NULL,
    value TEXT,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_prodi, key)
);

-- =============================================================
-- 13. AUDIT TRAIL & LOGGING
-- =============================================================

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

-- =============================================================
-- 14. NOTIFICATION SYSTEM
-- =============================================================

CREATE TABLE notifications (
    id_notif SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id_user) ON DELETE CASCADE,
    type VARCHAR(50),
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    read_at TIMESTAMP
);

-- =============================================================
-- 15. DOCUMENT MANAGEMENT
-- =============================================================

CREATE TABLE documents (
    id_document SERIAL PRIMARY KEY,
    entity_type VARCHAR(50),
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
-- MATERIALIZED VIEWS FOR ANALYTICS
-- =============================================================

-- View: Ketercapaian CPMK per Kelas (with curriculum info)
CREATE MATERIALIZED VIEW mv_ketercapaian_kelas AS
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
    COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END) as jumlah_lulus,
    ROUND(COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END)::NUMERIC / NULLIF(COUNT(DISTINCT e.nim), 0) * 100, 2) as persentase_lulus
FROM kelas k
JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
JOIN kurikulum kur ON mk.id_kurikulum = kur.id_kurikulum
JOIN enrollment e ON k.id_kelas = e.id_kelas
JOIN cpmk cm ON cm.id_rps = k.id_rps
LEFT JOIN ketercapaian_cpmk kc ON kc.id_enrollment = e.id_enrollment AND kc.id_cpmk = cm.id_cpmk
GROUP BY k.id_kelas, k.nama_kelas, k.semester, k.tahun_ajaran, mk.kode_mk, mk.nama_mk, 
         kur.kode_kurikulum, kur.nama_kurikulum, cm.id_cpmk, cm.kode_cpmk, cm.deskripsi;

CREATE UNIQUE INDEX ON mv_ketercapaian_kelas (id_kelas, id_cpmk);

-- View: Ketercapaian CPL per Kurikulum
CREATE MATERIALIZED VIEW mv_ketercapaian_cpl AS
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

CREATE UNIQUE INDEX ON mv_ketercapaian_cpl (id_kurikulum, id_cpl);

-- View: Statistik Kurikulum per Prodi
CREATE MATERIALIZED VIEW mv_statistik_kurikulum AS
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
    COUNT(DISTINCT CASE WHEN m.status = 'aktif' THEN m.nim END) as mahasiswa_aktif,
    COUNT(DISTINCT CASE WHEN m.status = 'lulus' THEN m.nim END) as mahasiswa_lulus,
    COUNT(DISTINCT m.angkatan) as jumlah_angkatan
FROM kurikulum kur
JOIN prodi p ON kur.id_prodi = p.id_prodi
LEFT JOIN cpl ON cpl.id_kurikulum = kur.id_kurikulum
LEFT JOIN matakuliah mk ON mk.id_kurikulum = kur.id_kurikulum AND mk.is_active = TRUE
LEFT JOIN mahasiswa m ON m.id_kurikulum = kur.id_kurikulum
GROUP BY kur.id_kurikulum, kur.kode_kurikulum, kur.nama_kurikulum, kur.status, 
         kur.tahun_berlaku, p.id_prodi, p.nama;

CREATE UNIQUE INDEX ON mv_statistik_kurikulum (id_kurikulum);

-- =============================================================
-- TRIGGERS
-- =============================================================

-- Trigger: Update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_kurikulum_updated_at BEFORE UPDATE ON kurikulum FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_fakultas_updated_at BEFORE UPDATE ON fakultas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_prodi_updated_at BEFORE UPDATE ON prodi FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_dosen_updated_at BEFORE UPDATE ON dosen FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_mahasiswa_updated_at BEFORE UPDATE ON mahasiswa FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_cpl_updated_at BEFORE UPDATE ON cpl FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_matakuliah_updated_at BEFORE UPDATE ON matakuliah FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Trigger: Auto-calculate nilai_tertimbang
CREATE OR REPLACE FUNCTION calculate_nilai_tertimbang()
RETURNS TRIGGER AS $$
DECLARE
    v_bobot NUMERIC(5,2);
    v_nilai_maksimal NUMERIC(5,2);
BEGIN
    SELECT bobot_realisasi, nilai_maksimal 
    INTO v_bobot, v_nilai_maksimal
    FROM komponen_penilaian
    WHERE id_komponen = NEW.id_komponen;
    
    NEW.nilai_tertimbang = (NEW.nilai_mentah / v_nilai_maksimal) * v_bobot;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_calculate_nilai_tertimbang
BEFORE INSERT OR UPDATE ON nilai_detail
FOR EACH ROW EXECUTE FUNCTION calculate_nilai_tertimbang();

-- Trigger: Prevent hard delete of MK (enforce soft delete)
CREATE OR REPLACE FUNCTION prevent_mk_hard_delete()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Hard delete not allowed for matakuliah. Use is_active = FALSE instead (BR-K03)';
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_prevent_mk_delete
BEFORE DELETE ON matakuliah
FOR EACH ROW EXECUTE FUNCTION prevent_mk_hard_delete();

-- Trigger: Prevent changing mahasiswa kurikulum (immutable)
CREATE OR REPLACE FUNCTION prevent_mahasiswa_kurikulum_change()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.id_kurikulum IS DISTINCT FROM NEW.id_kurikulum THEN
        RAISE EXCEPTION 'Cannot change mahasiswa curriculum - it is immutable (BR-K01)';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_prevent_kurikulum_change
BEFORE UPDATE ON mahasiswa
FOR EACH ROW EXECUTE FUNCTION prevent_mahasiswa_kurikulum_change();

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
       'WITH KURIKULUM MANAGEMENT' as feature,
       COUNT(*) as total_tables
FROM information_schema.tables 
WHERE table_schema = 'public' AND table_type = 'BASE TABLE';
