-- Migration: Add Performance Indexes
-- Created: 2025-11-23
-- Description: Add composite and covering indexes to improve query performance

-- Index for enrollment queries filtered by nim and status
CREATE INDEX IF NOT EXISTS idx_enrollment_nim_status ON enrollment(nim, status);

-- Index for enrollment queries by kelas and status (for enrollment counts)
CREATE INDEX IF NOT EXISTS idx_enrollment_kelas_status ON enrollment(id_kelas, status);

-- Covering index for notification queries (user_id, is_read, created_at already has index)
-- The existing index should be sufficient, but we'll add a partial index for unread notifications
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, created_at) WHERE is_read = FALSE;

-- Index for tugas_mengajar lookups by kelas
CREATE INDEX IF NOT EXISTS idx_tugas_mengajar_kelas ON tugas_mengajar(id_kelas, peran);

-- Index for ketercapaian_cpmk lookups by enrollment
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpmk_enrollment ON ketercapaian_cpmk(id_enrollment, id_cpmk);

-- Index for ketercapaian_cpl lookups by enrollment
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpl_enrollment ON ketercapaian_cpl(id_enrollment, id_cpl);

-- Index for subcpmk lookups by cpmk
CREATE INDEX IF NOT EXISTS idx_subcpmk_cpmk_urutan ON subcpmk(id_cpmk, urutan);

-- Index for cpmk lookups by rps with urutan
CREATE INDEX IF NOT EXISTS idx_cpmk_rps_urutan ON cpmk(id_rps, urutan);

-- Index for relasi_cpmk_cpl lookups
CREATE INDEX IF NOT EXISTS idx_relasi_cpmk_cpl_cpmk ON relasi_cpmk_cpl(id_cpmk, id_cpl);
CREATE INDEX IF NOT EXISTS idx_relasi_cpmk_cpl_cpl ON relasi_cpmk_cpl(id_cpl, id_cpmk);

-- Index for kelas queries by semester and tahun_ajaran
CREATE INDEX IF NOT EXISTS idx_kelas_semester_tahun ON kelas(semester, tahun_ajaran, status);
CREATE INDEX IF NOT EXISTS idx_kelas_kurikulum ON kelas(id_kurikulum, status);

-- Index for nilai_detail lookups
CREATE INDEX IF NOT EXISTS idx_nilai_detail_enrollment_komponen ON nilai_detail(id_enrollment, id_komponen);

-- Index for komponen_penilaian lookups by rps
CREATE INDEX IF NOT EXISTS idx_komponen_penilaian_rps ON komponen_penilaian(id_rps, urutan);

-- Index for rps lookups by matakuliah
CREATE INDEX IF NOT EXISTS idx_rps_matakuliah ON rps(kode_mk, id_kurikulum);

-- Add comments to explain the purpose of these indexes
COMMENT ON INDEX idx_enrollment_nim_status IS 'Optimizes queries filtering enrollments by student and status';
COMMENT ON INDEX idx_enrollment_kelas_status IS 'Optimizes enrollment count queries for classes';
COMMENT ON INDEX idx_notifications_unread IS 'Optimizes queries for unread notifications';
COMMENT ON INDEX idx_tugas_mengajar_kelas IS 'Optimizes teaching assignment lookups by class';
COMMENT ON INDEX idx_ketercapaian_cpmk_enrollment IS 'Optimizes CPMK achievement lookups by enrollment';
COMMENT ON INDEX idx_ketercapaian_cpl_enrollment IS 'Optimizes CPL achievement lookups by enrollment';
COMMENT ON INDEX idx_subcpmk_cpmk_urutan IS 'Optimizes SubCPMK lookups with proper ordering';
COMMENT ON INDEX idx_cpmk_rps_urutan IS 'Optimizes CPMK lookups by RPS with ordering';
COMMENT ON INDEX idx_relasi_cpmk_cpl_cpmk IS 'Optimizes CPMK-CPL relationship lookups from CPMK side';
COMMENT ON INDEX idx_relasi_cpmk_cpl_cpl IS 'Optimizes CPMK-CPL relationship lookups from CPL side';
COMMENT ON INDEX idx_kelas_semester_tahun IS 'Optimizes class queries by semester and year';
COMMENT ON INDEX idx_kelas_kurikulum IS 'Optimizes class queries by curriculum';
COMMENT ON INDEX idx_nilai_detail_enrollment_komponen IS 'Optimizes grade detail lookups';
COMMENT ON INDEX idx_komponen_penilaian_rps IS 'Optimizes assessment component lookups by RPS';
COMMENT ON INDEX idx_rps_matakuliah IS 'Optimizes RPS lookups by course';
