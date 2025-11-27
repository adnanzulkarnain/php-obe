<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Penilaian Repository
 * Handles all grading-related operations
 */
class PenilaianRepository extends BaseRepository
{
    protected string $table = 'template_penilaian';
    protected string $primaryKey = 'id_template';

    // ===================================
    // TEMPLATE PENILAIAN METHODS
    // ===================================

    /**
     * Find template by RPS
     */
    public function findTemplateByRPS(int $idRps): array
    {
        $sql = "
            SELECT
                tp.*,
                c.kode_cpmk,
                c.deskripsi as deskripsi_cpmk,
                jp.nama_jenis
            FROM template_penilaian tp
            JOIN cpmk c ON tp.id_cpmk = c.id_cpmk
            JOIN jenis_penilaian jp ON tp.id_jenis = jp.id_jenis
            WHERE tp.id_rps = :id_rps
            ORDER BY c.urutan ASC, jp.nama_jenis ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get total bobot per CPMK
     */
    public function getTotalBobotPerCPMK(int $idRps): array
    {
        $sql = "
            SELECT
                id_cpmk,
                SUM(bobot) as total_bobot
            FROM template_penilaian
            WHERE id_rps = :id_rps
            GROUP BY id_cpmk
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Check if template exists
     */
    public function templateExists(int $idRps, int $idCpmk, int $idJenis): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM template_penilaian
            WHERE id_rps = :id_rps AND id_cpmk = :id_cpmk AND id_jenis = :id_jenis
        ";

        $result = $this->queryOne($sql, [
            'id_rps' => $idRps,
            'id_cpmk' => $idCpmk,
            'id_jenis' => $idJenis
        ]);

        return $result['count'] > 0;
    }

    // ===================================
    // KOMPONEN PENILAIAN METHODS
    // ===================================

    /**
     * Create komponen penilaian
     */
    public function createKomponen(array $data): int
    {
        $sql = "
            INSERT INTO komponen_penilaian
            (id_kelas, id_template, nama_komponen, deskripsi, tanggal_pelaksanaan, deadline, bobot_realisasi, nilai_maksimal, created_at, updated_at)
            VALUES
            (:id_kelas, :id_template, :nama_komponen, :deskripsi, :tanggal_pelaksanaan, :deadline, :bobot_realisasi, :nilai_maksimal, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Find komponen by kelas
     */
    public function findKomponenByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                kp.*,
                tp.id_cpmk,
                c.kode_cpmk,
                jp.nama_jenis
            FROM komponen_penilaian kp
            LEFT JOIN template_penilaian tp ON kp.id_template = tp.id_template
            LEFT JOIN cpmk c ON tp.id_cpmk = c.id_cpmk
            LEFT JOIN jenis_penilaian jp ON tp.id_jenis = jp.id_jenis
            WHERE kp.id_kelas = :id_kelas
            ORDER BY kp.tanggal_pelaksanaan ASC, kp.nama_komponen ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Find komponen by ID
     */
    public function findKomponen(int $idKomponen): ?array
    {
        $sql = "
            SELECT
                kp.*,
                tp.id_cpmk,
                c.kode_cpmk,
                jp.nama_jenis
            FROM komponen_penilaian kp
            LEFT JOIN template_penilaian tp ON kp.id_template = tp.id_template
            LEFT JOIN cpmk c ON tp.id_cpmk = c.id_cpmk
            LEFT JOIN jenis_penilaian jp ON tp.id_jenis = jp.id_jenis
            WHERE kp.id_komponen = :id_komponen
        ";

        return $this->queryOne($sql, ['id_komponen' => $idKomponen]);
    }

    /**
     * Update komponen
     */
    public function updateKomponen(int $idKomponen, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE komponen_penilaian SET " . implode(', ', $set) . " WHERE id_komponen = :id_komponen";
        $data['id_komponen'] = $idKomponen;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete komponen
     */
    public function deleteKomponen(int $idKomponen): bool
    {
        $sql = "DELETE FROM komponen_penilaian WHERE id_komponen = :id_komponen";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_komponen' => $idKomponen]);
    }

    // ===================================
    // NILAI DETAIL METHODS
    // ===================================

    /**
     * Create atau update nilai
     */
    public function upsertNilai(array $data): int
    {
        $sql = "
            INSERT INTO nilai_detail
            (id_enrollment, id_komponen, nilai_mentah, nilai_tertimbang, catatan, dinilai_oleh, tanggal_input, updated_at)
            VALUES
            (:id_enrollment, :id_komponen, :nilai_mentah, :nilai_tertimbang, :catatan, :dinilai_oleh, :tanggal_input, :updated_at)
            ON DUPLICATE KEY UPDATE
                nilai_mentah = VALUES(nilai_mentah),
                nilai_tertimbang = VALUES(nilai_tertimbang),
                catatan = VALUES(catatan),
                dinilai_oleh = VALUES(dinilai_oleh),
                updated_at = VALUES(updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Find nilai by enrollment
     */
    public function findNilaiByEnrollment(int $idEnrollment): array
    {
        $sql = "
            SELECT
                nd.*,
                kp.nama_komponen,
                kp.bobot_realisasi,
                kp.nilai_maksimal,
                tp.id_cpmk,
                c.kode_cpmk
            FROM nilai_detail nd
            JOIN komponen_penilaian kp ON nd.id_komponen = kp.id_komponen
            LEFT JOIN template_penilaian tp ON kp.id_template = tp.id_template
            LEFT JOIN cpmk c ON tp.id_cpmk = c.id_cpmk
            WHERE nd.id_enrollment = :id_enrollment
            ORDER BY kp.tanggal_pelaksanaan ASC
        ";

        return $this->query($sql, ['id_enrollment' => $idEnrollment]);
    }

    /**
     * Find nilai by komponen
     */
    public function findNilaiByKomponen(int $idKomponen): array
    {
        $sql = "
            SELECT
                nd.*,
                e.nim,
                m.nama as nama_mahasiswa
            FROM nilai_detail nd
            JOIN enrollment e ON nd.id_enrollment = e.id_enrollment
            JOIN mahasiswa m ON e.nim = m.nim
            WHERE nd.id_komponen = :id_komponen
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_komponen' => $idKomponen]);
    }

    /**
     * Find specific nilai
     */
    public function findNilai(int $idEnrollment, int $idKomponen): ?array
    {
        $sql = "
            SELECT
                nd.*,
                kp.nama_komponen,
                kp.bobot_realisasi,
                kp.nilai_maksimal
            FROM nilai_detail nd
            JOIN komponen_penilaian kp ON nd.id_komponen = kp.id_komponen
            WHERE nd.id_enrollment = :id_enrollment AND nd.id_komponen = :id_komponen
        ";

        return $this->queryOne($sql, [
            'id_enrollment' => $idEnrollment,
            'id_komponen' => $idKomponen
        ]);
    }

    /**
     * Delete nilai
     */
    public function deleteNilai(int $idEnrollment, int $idKomponen): bool
    {
        $sql = "DELETE FROM nilai_detail WHERE id_enrollment = :id_enrollment AND id_komponen = :id_komponen";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_enrollment' => $idEnrollment,
            'id_komponen' => $idKomponen
        ]);
    }

    // ===================================
    // CALCULATION METHODS
    // ===================================

    /**
     * Calculate nilai akhir untuk enrollment
     */
    public function calculateNilaiAkhir(int $idEnrollment): float
    {
        $sql = "
            SELECT COALESCE(SUM(nilai_tertimbang), 0) as total
            FROM nilai_detail
            WHERE id_enrollment = :id_enrollment
        ";

        $result = $this->queryOne($sql, ['id_enrollment' => $idEnrollment]);
        return (float)$result['total'];
    }

    /**
     * Calculate CPMK achievement
     */
    public function calculateCPMKAchievement(int $idEnrollment, int $idCpmk): ?float
    {
        $sql = "
            SELECT
                SUM(nd.nilai_tertimbang) / NULLIF(SUM(kp.bobot_realisasi), 0) * 100 as achievement
            FROM nilai_detail nd
            JOIN komponen_penilaian kp ON nd.id_komponen = kp.id_komponen
            JOIN template_penilaian tp ON kp.id_template = tp.id_template
            WHERE nd.id_enrollment = :id_enrollment
            AND tp.id_cpmk = :id_cpmk
        ";

        $result = $this->queryOne($sql, [
            'id_enrollment' => $idEnrollment,
            'id_cpmk' => $idCpmk
        ]);

        return $result && $result['achievement'] !== null ? (float)$result['achievement'] : null;
    }

    /**
     * Get nilai summary per kelas
     */
    public function getNilaiSummaryByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                e.id_enrollment,
                e.nim,
                m.nama as nama_mahasiswa,
                COUNT(DISTINCT kp.id_komponen) as total_komponen,
                COUNT(nd.id_nilai_detail) as komponen_dinilai,
                COALESCE(SUM(nd.nilai_tertimbang), 0) as nilai_akhir
            FROM enrollment e
            JOIN mahasiswa m ON e.nim = m.nim
            CROSS JOIN komponen_penilaian kp
            LEFT JOIN nilai_detail nd ON e.id_enrollment = nd.id_enrollment AND kp.id_komponen = nd.id_komponen
            WHERE e.id_kelas = :id_kelas AND kp.id_kelas = :id_kelas
            GROUP BY e.id_enrollment, e.nim, m.nama
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Get statistics per komponen
     */
    public function getKomponenStatistics(int $idKomponen): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_mahasiswa,
                AVG(nilai_mentah) as rata_rata,
                MIN(nilai_mentah) as nilai_min,
                MAX(nilai_mentah) as nilai_max,
                STDDEV(nilai_mentah) as std_dev
            FROM nilai_detail
            WHERE id_komponen = :id_komponen
        ";

        return $this->queryOne($sql, ['id_komponen' => $idKomponen]) ?: [];
    }

    // ===================================
    // JENIS PENILAIAN METHODS
    // ===================================

    /**
     * Get all jenis penilaian
     */
    public function getAllJenisPenilaian(): array
    {
        $sql = "SELECT * FROM jenis_penilaian ORDER BY nama_jenis ASC";
        return $this->query($sql);
    }

    /**
     * Create jenis penilaian
     */
    public function createJenisPenilaian(string $namaJenis, ?string $deskripsi = null): int
    {
        $sql = "
            INSERT INTO jenis_penilaian (nama_jenis, deskripsi)
            VALUES (:nama_jenis, :deskripsi)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nama_jenis' => $namaJenis,
            'deskripsi' => $deskripsi
        ]);

        return (int)$this->db->lastInsertId();
    }

    // ===================================
    // KETERCAPAIAN CPMK METHODS
    // ===================================

    /**
     * Upsert ketercapaian CPMK
     * Persist CPMK achievement after nilai input
     */
    public function upsertKetercapaianCPMK(int $idEnrollment, int $idCpmk, float $nilaiCpmk, bool $statusTercapai): int
    {
        $sql = "
            INSERT INTO ketercapaian_cpmk
            (id_enrollment, id_cpmk, nilai_cpmk, status_tercapai, created_at, updated_at)
            VALUES
            (:id_enrollment, :id_cpmk, :nilai_cpmk, :status_tercapai, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                nilai_cpmk = VALUES(nilai_cpmk),
                status_tercapai = VALUES(status_tercapai),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_enrollment' => $idEnrollment,
            'id_cpmk' => $idCpmk,
            'nilai_cpmk' => $nilaiCpmk,
            'status_tercapai' => $statusTercapai
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get all CPMK for an enrollment's RPS
     */
    public function getAllCPMKForEnrollment(int $idEnrollment): array
    {
        $sql = "
            SELECT DISTINCT c.id_cpmk
            FROM enrollment e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN cpmk c ON c.id_rps = k.id_rps
            WHERE e.id_enrollment = :id_enrollment
        ";

        return $this->query($sql, ['id_enrollment' => $idEnrollment]);
    }

    /**
     * Get ketercapaian CPMK by enrollment
     */
    public function getKetercapaianCPMKByEnrollment(int $idEnrollment): array
    {
        $sql = "
            SELECT
                kc.*,
                c.kode_cpmk,
                c.deskripsi as deskripsi_cpmk
            FROM ketercapaian_cpmk kc
            JOIN cpmk c ON kc.id_cpmk = c.id_cpmk
            WHERE kc.id_enrollment = :id_enrollment
            ORDER BY c.urutan ASC
        ";

        return $this->query($sql, ['id_enrollment' => $idEnrollment]);
    }

    // ===================================
    // KETERCAPAIAN CPL METHODS
    // ===================================

    /**
     * Calculate and upsert ketercapaian CPL
     * Aggregates from CPMK achievements using relasi_cpmk_cpl
     */
    public function calculateAndPersistCPL(int $idEnrollment, float $batasKelulusanCpl = 40.01): array
    {
        $sql = "
            SELECT
                rcl.id_cpl,
                cpl.kode_cpl,
                cpl.deskripsi as deskripsi_cpl,
                -- Weighted average of CPMK achievements
                SUM(kc.nilai_cpmk * rcl.bobot_kontribusi / 100) / NULLIF(SUM(rcl.bobot_kontribusi / 100), 0) as nilai_cpl
            FROM enrollment e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN cpmk cm ON cm.id_rps = k.id_rps
            JOIN ketercapaian_cpmk kc ON kc.id_enrollment = e.id_enrollment AND kc.id_cpmk = cm.id_cpmk
            JOIN relasi_cpmk_cpl rcl ON rcl.id_cpmk = cm.id_cpmk
            JOIN cpl ON cpl.id_cpl = rcl.id_cpl
            WHERE e.id_enrollment = :id_enrollment
            GROUP BY rcl.id_cpl, cpl.kode_cpl, cpl.deskripsi
        ";

        $cplResults = $this->query($sql, ['id_enrollment' => $idEnrollment]);

        $results = [];
        foreach ($cplResults as $cpl) {
            $nilaiCpl = (float)$cpl['nilai_cpl'];
            $statusTercapai = $nilaiCpl >= $batasKelulusanCpl;

            $idKetercapaian = $this->upsertKetercapaianCPL(
                $idEnrollment,
                (int)$cpl['id_cpl'],
                $nilaiCpl,
                $statusTercapai
            );

            $results[] = [
                'id_ketercapaian' => $idKetercapaian,
                'id_cpl' => $cpl['id_cpl'],
                'kode_cpl' => $cpl['kode_cpl'],
                'nilai_cpl' => $nilaiCpl,
                'status_tercapai' => $statusTercapai
            ];
        }

        return $results;
    }

    /**
     * Upsert ketercapaian CPL
     */
    public function upsertKetercapaianCPL(int $idEnrollment, int $idCpl, float $nilaiCpl, bool $statusTercapai): int
    {
        $sql = "
            INSERT INTO ketercapaian_cpl
            (id_enrollment, id_cpl, nilai_cpl, status_tercapai, created_at, updated_at)
            VALUES
            (:id_enrollment, :id_cpl, :nilai_cpl, :status_tercapai, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                nilai_cpl = VALUES(nilai_cpl),
                status_tercapai = VALUES(status_tercapai),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_enrollment' => $idEnrollment,
            'id_cpl' => $idCpl,
            'nilai_cpl' => $nilaiCpl,
            'status_tercapai' => $statusTercapai
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get ketercapaian CPL by enrollment
     */
    public function getKetercapaianCPLByEnrollment(int $idEnrollment): array
    {
        $sql = "
            SELECT
                kcpl.*,
                cpl.kode_cpl,
                cpl.deskripsi as deskripsi_cpl,
                cpl.kategori
            FROM ketercapaian_cpl kcpl
            JOIN cpl ON kcpl.id_cpl = cpl.id_cpl
            WHERE kcpl.id_enrollment = :id_enrollment
            ORDER BY cpl.kategori, cpl.urutan ASC
        ";

        return $this->query($sql, ['id_enrollment' => $idEnrollment]);
    }

    /**
     * Get ambang batas from RPS
     */
    public function getAmbangBatasByEnrollment(int $idEnrollment): ?array
    {
        $sql = "
            SELECT ab.*
            FROM enrollment e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN ambang_batas ab ON ab.id_rps = k.id_rps
            WHERE e.id_enrollment = :id_enrollment
            LIMIT 1
        ";

        return $this->queryOne($sql, ['id_enrollment' => $idEnrollment]);
    }
}
