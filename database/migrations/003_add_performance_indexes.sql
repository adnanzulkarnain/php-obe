-- Migration: Add Performance Indexes
-- Created: 2025-11-23
-- Description: Add composite and covering indexes to improve query performance

-- Index for enrollment queries filtered by nim and status
-- Optimizes queries filtering enrollments by student and status
CREATE INDEX IF NOT EXISTS idx_enrollment_nim_status ON enrollment(nim, status);

-- Index for enrollment queries by kelas and status (for enrollment counts)
-- Optimizes enrollment count queries for classes
CREATE INDEX IF NOT EXISTS idx_enrollment_kelas_status ON enrollment(id_kelas, status);

-- Index for notification queries - MySQL doesn't support partial indexes with WHERE clause
-- We create a regular index instead
-- Optimizes queries for notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_read_created ON notifications(user_id, is_read, created_at);

-- Index for tugas_mengajar lookups by kelas
-- Optimizes teaching assignment lookups by class
CREATE INDEX IF NOT EXISTS idx_tugas_mengajar_kelas ON tugas_mengajar(id_kelas, peran);

-- Index for ketercapaian_cpmk lookups by enrollment
-- Optimizes CPMK achievement lookups by enrollment
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpmk_enrollment ON ketercapaian_cpmk(id_enrollment, id_cpmk);

-- Index for ketercapaian_cpl lookups by enrollment
-- Optimizes CPL achievement lookups by enrollment
-- Note: idx_ketercapaian_cpl_enrollment already exists from migration 001, creating composite index with different name
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpl_enrollment_cpl ON ketercapaian_cpl(id_enrollment, id_cpl);

-- Index for subcpmk lookups by cpmk
-- Optimizes SubCPMK lookups with proper ordering
CREATE INDEX IF NOT EXISTS idx_subcpmk_cpmk_urutan ON subcpmk(id_cpmk, urutan);

-- Index for cpmk lookups by rps with urutan
-- Optimizes CPMK lookups by RPS with ordering
CREATE INDEX IF NOT EXISTS idx_cpmk_rps_urutan ON cpmk(id_rps, urutan);

-- Index for relasi_cpmk_cpl lookups
-- Optimizes CPMK-CPL relationship lookups from CPMK side
CREATE INDEX IF NOT EXISTS idx_relasi_cpmk_cpl_cpmk ON relasi_cpmk_cpl(id_cpmk, id_cpl);

-- Optimizes CPMK-CPL relationship lookups from CPL side
CREATE INDEX IF NOT EXISTS idx_relasi_cpmk_cpl_cpl ON relasi_cpmk_cpl(id_cpl, id_cpmk);

-- Index for kelas queries by semester and tahun_ajaran
-- Optimizes class queries by semester and year
CREATE INDEX IF NOT EXISTS idx_kelas_semester_tahun ON kelas(semester, tahun_ajaran, status);

-- Optimizes class queries by curriculum
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum ON kelas(id_kurikulum, status);

-- Index for nilai_detail lookups
-- Optimizes grade detail lookups
CREATE INDEX IF NOT EXISTS idx_nilai_detail_enrollment_komponen ON nilai_detail(id_enrollment, id_komponen);

-- Index for komponen_penilaian lookups by kelas (fixed: changed from id_rps to id_kelas)
-- Optimizes assessment component lookups by class
CREATE INDEX IF NOT EXISTS idx_komponen_penilaian_kelas ON komponen_penilaian(id_kelas);

-- Index for rps lookups by matakuliah
-- Optimizes RPS lookups by course
CREATE INDEX IF NOT EXISTS idx_rps_matakuliah ON rps(kode_mk, id_kurikulum);
