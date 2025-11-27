<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * CPMK Repository
 */
class CPMKRepository extends BaseRepository
{
    protected string $table = 'cpmk';
    protected string $primaryKey = 'id_cpmk';

    /**
     * Find CPMK by ID with details
     */
    public function findByIdWithDetails(int $idCpmk): ?array
    {
        $sql = "
            SELECT
                c.*,
                r.kode_mk,
                mk.nama_mk
            FROM {$this->table} c
            JOIN rps r ON c.id_rps = r.id_rps
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            WHERE c.id_cpmk = :id_cpmk
        ";

        return $this->queryOne($sql, ['id_cpmk' => $idCpmk]);
    }

    /**
     * Find all CPMK by RPS
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT
                c.*
            FROM {$this->table} c
            WHERE c.id_rps = :id_rps
            ORDER BY c.urutan ASC, c.id_cpmk ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Find all CPMK by RPS with SubCPMK
     * Optimized to use single query with LEFT JOIN to avoid N+1 problem
     */
    public function findByRPSWithSubCPMK(int $idRps): array
    {
        $sql = "
            SELECT
                c.*,
                s.id_subcpmk,
                s.kode_subcpmk,
                s.deskripsi as subcpmk_deskripsi,
                s.indikator as subcpmk_indikator,
                s.urutan as subcpmk_urutan,
                s.created_at as subcpmk_created_at,
                s.updated_at as subcpmk_updated_at
            FROM {$this->table} c
            LEFT JOIN subcpmk s ON c.id_cpmk = s.id_cpmk
            WHERE c.id_rps = :id_rps
            ORDER BY c.urutan ASC, c.id_cpmk ASC, s.urutan ASC, s.id_subcpmk ASC
        ";

        $rows = $this->query($sql, ['id_rps' => $idRps]);

        // Group SubCPMK under their parent CPMK
        $cpmkList = [];
        $currentCpmkId = null;
        $currentIndex = -1;

        foreach ($rows as $row) {
            // If this is a new CPMK, add it to the list
            if ($row['id_cpmk'] !== $currentCpmkId) {
                $currentCpmkId = $row['id_cpmk'];
                $currentIndex++;

                // Extract CPMK fields
                $cpmkList[$currentIndex] = [
                    'id_cpmk' => $row['id_cpmk'],
                    'id_rps' => $row['id_rps'],
                    'kode_cpmk' => $row['kode_cpmk'],
                    'deskripsi' => $row['deskripsi'],
                    'urutan' => $row['urutan'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'subcpmk' => []
                ];
            }

            // Add SubCPMK if it exists
            if ($row['id_subcpmk'] !== null) {
                $cpmkList[$currentIndex]['subcpmk'][] = [
                    'id_subcpmk' => $row['id_subcpmk'],
                    'id_cpmk' => $row['id_cpmk'],
                    'kode_subcpmk' => $row['kode_subcpmk'],
                    'deskripsi' => $row['subcpmk_deskripsi'],
                    'indikator' => $row['subcpmk_indikator'],
                    'urutan' => $row['subcpmk_urutan'],
                    'created_at' => $row['subcpmk_created_at'],
                    'updated_at' => $row['subcpmk_updated_at']
                ];
            }
        }

        return $cpmkList;
    }

    /**
     * Count CPMK by RPS
     */
    public function countByRPS(int $idRps): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id_rps = :id_rps";
        $result = $this->queryOne($sql, ['id_rps' => $idRps]);
        return (int)$result['count'];
    }

    /**
     * Get next urutan for RPS
     */
    public function getNextUrutan(int $idRps): int
    {
        $sql = "SELECT COALESCE(MAX(urutan), 0) + 1 as next_urutan FROM {$this->table} WHERE id_rps = :id_rps";
        $result = $this->queryOne($sql, ['id_rps' => $idRps]);
        return (int)$result['next_urutan'];
    }

    // ===================================
    // SUBCPMK METHODS
    // ===================================

    /**
     * Create SubCPMK
     */
    public function createSubCPMK(array $data): int
    {
        $sql = "
            INSERT INTO subcpmk
            (id_cpmk, kode_subcpmk, deskripsi, indikator, urutan, created_at, updated_at)
            VALUES
            (:id_cpmk, :kode_subcpmk, :deskripsi, :indikator, :urutan, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get SubCPMK by CPMK
     */
    public function getSubCPMKByCPMK(int $idCpmk): array
    {
        $sql = "
            SELECT *
            FROM subcpmk
            WHERE id_cpmk = :id_cpmk
            ORDER BY urutan ASC, id_subcpmk ASC
        ";

        return $this->query($sql, ['id_cpmk' => $idCpmk]);
    }

    /**
     * Find SubCPMK by ID
     */
    public function findSubCPMK(int $idSubcpmk): ?array
    {
        $sql = "SELECT * FROM subcpmk WHERE id_subcpmk = :id_subcpmk";
        return $this->queryOne($sql, ['id_subcpmk' => $idSubcpmk]);
    }

    /**
     * Update SubCPMK
     */
    public function updateSubCPMK(int $idSubcpmk, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE subcpmk SET " . implode(', ', $set) . " WHERE id_subcpmk = :id_subcpmk";
        $data['id_subcpmk'] = $idSubcpmk;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete SubCPMK
     */
    public function deleteSubCPMK(int $idSubcpmk): bool
    {
        $sql = "DELETE FROM subcpmk WHERE id_subcpmk = :id_subcpmk";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_subcpmk' => $idSubcpmk]);
    }

    /**
     * Get next SubCPMK urutan
     */
    public function getNextSubCPMKUrutan(int $idCpmk): int
    {
        $sql = "SELECT COALESCE(MAX(urutan), 0) + 1 as next_urutan FROM subcpmk WHERE id_cpmk = :id_cpmk";
        $result = $this->queryOne($sql, ['id_cpmk' => $idCpmk]);
        return (int)$result['next_urutan'];
    }

    // ===================================
    // CPMK-CPL MAPPING METHODS
    // ===================================

    /**
     * Create CPMK-CPL mapping
     */
    public function createCPMKCPLMapping(array $data): int
    {
        $sql = "
            INSERT INTO relasi_cpmk_cpl
            (id_cpmk, id_cpl, bobot_kontribusi, created_at)
            VALUES
            (:id_cpmk, :id_cpl, :bobot_kontribusi, :created_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get CPL mappings for CPMK
     */
    public function getCPLMappingsByCPMK(int $idCpmk): array
    {
        $sql = "
            SELECT
                r.*,
                cpl.kode_cpl,
                cpl.deskripsi as deskripsi_cpl,
                cpl.kategori
            FROM relasi_cpmk_cpl r
            JOIN cpl ON r.id_cpl = cpl.id_cpl
            WHERE r.id_cpmk = :id_cpmk
            ORDER BY cpl.kode_cpl ASC
        ";

        return $this->query($sql, ['id_cpmk' => $idCpmk]);
    }

    /**
     * Get CPMK mappings for CPL
     */
    public function getCPMKMappingsByCPL(int $idCpl): array
    {
        $sql = "
            SELECT
                r.*,
                c.kode_cpmk,
                c.deskripsi as deskripsi_cpmk,
                rps.kode_mk,
                mk.nama_mk
            FROM relasi_cpmk_cpl r
            JOIN cpmk c ON r.id_cpmk = c.id_cpmk
            JOIN rps ON c.id_rps = rps.id_rps
            JOIN matakuliah mk ON rps.kode_mk = mk.kode_mk AND rps.id_kurikulum = mk.id_kurikulum
            WHERE r.id_cpl = :id_cpl
            ORDER BY rps.kode_mk ASC, c.urutan ASC
        ";

        return $this->query($sql, ['id_cpl' => $idCpl]);
    }

    /**
     * Find CPMK-CPL mapping
     */
    public function findCPMKCPLMapping(int $idRelasi): ?array
    {
        $sql = "
            SELECT
                r.*,
                c.kode_cpmk,
                cpl.kode_cpl,
                cpl.deskripsi as deskripsi_cpl
            FROM relasi_cpmk_cpl r
            JOIN cpmk c ON r.id_cpmk = c.id_cpmk
            JOIN cpl ON r.id_cpl = cpl.id_cpl
            WHERE r.id_relasi = :id_relasi
        ";

        return $this->queryOne($sql, ['id_relasi' => $idRelasi]);
    }

    /**
     * Check if CPMK-CPL mapping exists
     */
    public function mappingExists(int $idCpmk, int $idCpl): bool
    {
        $sql = "SELECT COUNT(*) as count FROM relasi_cpmk_cpl WHERE id_cpmk = :id_cpmk AND id_cpl = :id_cpl";
        $result = $this->queryOne($sql, [
            'id_cpmk' => $idCpmk,
            'id_cpl' => $idCpl
        ]);

        return $result['count'] > 0;
    }

    /**
     * Update CPMK-CPL mapping
     */
    public function updateCPMKCPLMapping(int $idRelasi, float $bobotKontribusi): bool
    {
        $sql = "UPDATE relasi_cpmk_cpl SET bobot_kontribusi = :bobot WHERE id_relasi = :id_relasi";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'bobot' => $bobotKontribusi,
            'id_relasi' => $idRelasi
        ]);
    }

    /**
     * Delete CPMK-CPL mapping
     */
    public function deleteCPMKCPLMapping(int $idRelasi): bool
    {
        $sql = "DELETE FROM relasi_cpmk_cpl WHERE id_relasi = :id_relasi";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_relasi' => $idRelasi]);
    }

    /**
     * Delete all mappings for CPMK
     */
    public function deleteAllMappingsByCPMK(int $idCpmk): bool
    {
        $sql = "DELETE FROM relasi_cpmk_cpl WHERE id_cpmk = :id_cpmk";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_cpmk' => $idCpmk]);
    }

    /**
     * Get CPMK with full details (SubCPMK and CPL mappings)
     */
    public function getCPMKWithFullDetails(int $idCpmk): ?array
    {
        $cpmk = $this->findByIdWithDetails($idCpmk);
        if (!$cpmk) {
            return null;
        }

        // Get SubCPMK
        $cpmk['subcpmk'] = $this->getSubCPMKByCPMK($idCpmk);

        // Get CPL mappings
        $cpmk['cpl_mappings'] = $this->getCPLMappingsByCPMK($idCpmk);

        return $cpmk;
    }

    /**
     * Get statistics for RPS
     */
    public function getRPSStatistics(int $idRps): array
    {
        $sql = "
            SELECT
                COUNT(c.id_cpmk) as total_cpmk,
                COUNT(s.id_subcpmk) as total_subcpmk,
                COUNT(DISTINCT r.id_cpl) as total_cpl_mapped
            FROM cpmk c
            LEFT JOIN subcpmk s ON c.id_cpmk = s.id_cpmk
            LEFT JOIN relasi_cpmk_cpl r ON c.id_cpmk = r.id_cpmk
            WHERE c.id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]) ?: [];
    }
}
