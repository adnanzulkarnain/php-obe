<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Rencana Mingguan Repository
 * Handles weekly learning plan database operations
 */
class RencanaMingguanRepository extends BaseRepository
{
    protected string $table = 'rencana_mingguan';
    protected string $primaryKey = 'id_minggu';

    /**
     * Get all rencana mingguan by RPS
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT
                rm.*,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk,
                c.kode_cpmk
            FROM rencana_mingguan rm
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            LEFT JOIN cpmk c ON sc.id_cpmk = c.id_cpmk
            WHERE rm.id_rps = :id_rps
            ORDER BY rm.minggu_ke ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Find rencana mingguan by ID with details
     */
    public function findWithDetails(int $idMinggu): ?array
    {
        $sql = "
            SELECT
                rm.*,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk,
                c.kode_cpmk,
                c.deskripsi as deskripsi_cpmk
            FROM rencana_mingguan rm
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            LEFT JOIN cpmk c ON sc.id_cpmk = c.id_cpmk
            WHERE rm.id_minggu = :id_minggu
        ";

        return $this->queryOne($sql, ['id_minggu' => $idMinggu]);
    }

    /**
     * Check if minggu_ke exists for RPS
     */
    public function mingguExists(int $idRps, int $mingguKe, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM rencana_mingguan WHERE id_rps = :id_rps AND minggu_ke = :minggu_ke";
        $params = ['id_rps' => $idRps, 'minggu_ke' => $mingguKe];

        if ($excludeId !== null) {
            $sql .= " AND id_minggu != :id_minggu";
            $params['id_minggu'] = $excludeId;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Create rencana mingguan
     */
    public function createRencanaMinggu(array $data): int
    {
        $sql = "
            INSERT INTO rencana_mingguan
            (id_rps, minggu_ke, id_subcpmk, materi, metode, aktivitas, media_software, media_hardware, pengalaman_belajar, estimasi_waktu_menit, created_at, updated_at)
            VALUES
            (:id_rps, :minggu_ke, :id_subcpmk, :materi, :metode, :aktivitas, :media_software, :media_hardware, :pengalaman_belajar, :estimasi_waktu_menit, NOW(), NOW())
            RETURNING id_minggu
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_minggu'];
    }

    /**
     * Update rencana mingguan
     */
    public function updateRencanaMinggu(int $idMinggu, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE rencana_mingguan SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id_minggu = :id_minggu";
        $data['id_minggu'] = $idMinggu;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete rencana mingguan
     */
    public function deleteRencanaMinggu(int $idMinggu): bool
    {
        $sql = "DELETE FROM rencana_mingguan WHERE id_minggu = :id_minggu";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_minggu' => $idMinggu]);
    }

    /**
     * Get completion statistics for RPS
     */
    public function getCompletionStats(int $idRps): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_minggu,
                COUNT(CASE WHEN materi IS NOT NULL AND materi::text != '{}' THEN 1 END) as minggu_terisi
            FROM rencana_mingguan
            WHERE id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]) ?: [];
    }
}
