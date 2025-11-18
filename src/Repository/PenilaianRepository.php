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
            RETURNING id_komponen
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_komponen'];
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
            ON CONFLICT (id_enrollment, id_komponen)
            DO UPDATE SET
                nilai_mentah = EXCLUDED.nilai_mentah,
                nilai_tertimbang = EXCLUDED.nilai_tertimbang,
                catatan = EXCLUDED.catatan,
                dinilai_oleh = EXCLUDED.dinilai_oleh,
                updated_at = EXCLUDED.updated_at
            RETURNING id_nilai_detail
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_nilai_detail'];
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
            RETURNING id_jenis
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nama_jenis' => $namaJenis,
            'deskripsi' => $deskripsi
        ]);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_jenis'];
    }
}
