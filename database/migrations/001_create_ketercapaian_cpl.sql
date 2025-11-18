-- =============================================================
-- Migration: Create ketercapaian_cpl table
-- Date: 2025-11-18
-- Purpose: Store CPL achievements aggregated from CPMK
-- =============================================================

-- Create table ketercapaian_cpl
CREATE TABLE IF NOT EXISTS ketercapaian_cpl (
    id_ketercapaian SERIAL PRIMARY KEY,
    id_enrollment INT REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    id_cpl INT REFERENCES cpl(id_cpl) ON DELETE CASCADE,
    nilai_cpl NUMERIC(5,2),
    status_tercapai BOOLEAN,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (id_enrollment, id_cpl)
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpl_enrollment ON ketercapaian_cpl(id_enrollment);
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpl_cpl ON ketercapaian_cpl(id_cpl);
CREATE INDEX IF NOT EXISTS idx_ketercapaian_cpl_status ON ketercapaian_cpl(id_enrollment, status_tercapai);

-- Add comments
COMMENT ON TABLE ketercapaian_cpl IS 'CPL achievements per student - aggregated from CPMK achievements using relasi_cpmk_cpl';
COMMENT ON COLUMN ketercapaian_cpl.nilai_cpl IS 'Weighted average of CPMK achievements based on bobot_kontribusi';
COMMENT ON COLUMN ketercapaian_cpl.status_tercapai IS 'TRUE if nilai_cpl >= batas_kelulusan_cpmk (default 40.01)';

-- Add trigger for updated_at
CREATE TRIGGER update_ketercapaian_cpl_updated_at
BEFORE UPDATE ON ketercapaian_cpl
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
