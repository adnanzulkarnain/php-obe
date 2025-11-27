-- =============================================================
-- Migration: Create ketercapaian_cpl table
-- Date: 2025-11-18
-- Purpose: Store CPL achievements aggregated from CPMK
-- =============================================================

-- Create table ketercapaian_cpl
CREATE TABLE IF NOT EXISTS ketercapaian_cpl (
    id_ketercapaian INT AUTO_INCREMENT PRIMARY KEY,
    id_enrollment INT,
    id_cpl INT,
    nilai_cpl DECIMAL(5,2) COMMENT 'Weighted average of CPMK achievements based on bobot_kontribusi',
    status_tercapai BOOLEAN COMMENT 'TRUE if nilai_cpl >= batas_kelulusan_cpmk (default 40.01)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment_cpl (id_enrollment, id_cpl),
    FOREIGN KEY (id_enrollment) REFERENCES enrollment(id_enrollment) ON DELETE CASCADE,
    FOREIGN KEY (id_cpl) REFERENCES cpl(id_cpl) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='CPL achievements per student - aggregated from CPMK achievements using relasi_cpmk_cpl';

-- Create indexes
CREATE INDEX idx_ketercapaian_cpl_enrollment ON ketercapaian_cpl(id_enrollment);
CREATE INDEX idx_ketercapaian_cpl_cpl ON ketercapaian_cpl(id_cpl);
CREATE INDEX idx_ketercapaian_cpl_status ON ketercapaian_cpl(id_enrollment, status_tercapai);
