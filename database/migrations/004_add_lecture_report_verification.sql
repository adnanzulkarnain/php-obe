-- Migration: Add Lecture Report Verification System
-- Description: Add status and verification columns to realisasi_pertemuan table
-- Date: 2025-11-25

-- Add status and verification columns to realisasi_pertemuan
ALTER TABLE realisasi_pertemuan
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'draft'
    CHECK (status IN ('draft', 'submitted', 'verified', 'rejected')),
ADD COLUMN IF NOT EXISTS verified_by VARCHAR(20) REFERENCES dosen(id_dosen),
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS komentar_kaprodi TEXT,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW();

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

-- Add trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_realisasi_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_update_realisasi_updated_at ON realisasi_pertemuan;
CREATE TRIGGER trigger_update_realisasi_updated_at
    BEFORE UPDATE ON realisasi_pertemuan
    FOR EACH ROW
    EXECUTE FUNCTION update_realisasi_updated_at();

-- Add comment to table
COMMENT ON COLUMN realisasi_pertemuan.status IS 'Status berita acara: draft, submitted, verified, rejected';
COMMENT ON COLUMN realisasi_pertemuan.verified_by IS 'ID dosen (kaprodi) yang melakukan verifikasi';
COMMENT ON COLUMN realisasi_pertemuan.verified_at IS 'Waktu verifikasi dilakukan';
COMMENT ON COLUMN realisasi_pertemuan.komentar_kaprodi IS 'Komentar/feedback dari kaprodi';
