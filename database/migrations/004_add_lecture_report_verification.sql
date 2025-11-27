-- Migration: Add Lecture Report Verification System
-- Description: Add status and verification columns to realisasi_pertemuan table
-- Date: 2025-11-25

-- Add status and verification columns to realisasi_pertemuan
ALTER TABLE realisasi_pertemuan
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'draft'
    CHECK (status IN ('draft', 'submitted', 'verified', 'rejected'))
    COMMENT 'Status berita acara: draft, submitted, verified, rejected',
ADD COLUMN IF NOT EXISTS verified_by VARCHAR(20) COMMENT 'ID dosen (kaprodi) yang melakukan verifikasi',
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL COMMENT 'Waktu verifikasi dilakukan',
ADD COLUMN IF NOT EXISTS komentar_kaprodi TEXT COMMENT 'Komentar/feedback dari kaprodi',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign key for verified_by
ALTER TABLE realisasi_pertemuan
ADD CONSTRAINT fk_realisasi_verified_by FOREIGN KEY (verified_by) REFERENCES dosen(id_dosen);

-- Add index for faster queries
CREATE INDEX IF NOT EXISTS idx_realisasi_status ON realisasi_pertemuan(status);
CREATE INDEX IF NOT EXISTS idx_realisasi_verified_by ON realisasi_pertemuan(verified_by);
CREATE INDEX IF NOT EXISTS idx_realisasi_created_by ON realisasi_pertemuan(created_by);
CREATE INDEX IF NOT EXISTS idx_realisasi_tanggal ON realisasi_pertemuan(tanggal_pelaksanaan);

-- Add index for kehadiran
CREATE INDEX IF NOT EXISTS idx_kehadiran_realisasi ON kehadiran(id_realisasi);
CREATE INDEX IF NOT EXISTS idx_kehadiran_nim ON kehadiran(nim);

-- Add index for rencana_mingguan
CREATE INDEX IF NOT EXISTS idx_rencana_rps ON rencana_mingguan(id_rps);
CREATE INDEX IF NOT EXISTS idx_rencana_minggu ON rencana_mingguan(minggu_ke);
