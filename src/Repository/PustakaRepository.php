<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Pustaka Repository
 * Handles learning resources and references database operations
 */
class PustakaRepository extends BaseRepository
{
    protected string $table = 'pustaka';
    protected string $primaryKey = 'id_pustaka';

    /**
     * Get all pustaka by RPS
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT *
            FROM pustaka
            WHERE id_rps = :id_rps
            ORDER BY jenis ASC, tahun DESC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get pustaka by jenis
     */
    public function findByJenis(int $idRps, string $jenis): array
    {
        $sql = "
            SELECT *
            FROM pustaka
            WHERE id_rps = :id_rps AND jenis = :jenis
            ORDER BY tahun DESC
        ";

        return $this->query($sql, ['id_rps' => $idRps, 'jenis' => $jenis]);
    }

    /**
     * Create pustaka
     */
    public function createPustaka(array $data): int
    {
        $sql = "
            INSERT INTO pustaka
            (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url, created_at)
            VALUES
            (:id_rps, :jenis, :referensi, :penulis, :tahun, :penerbit, :isbn, :url, NOW())
            RETURNING id_pustaka
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_pustaka'];
    }

    /**
     * Update pustaka
     */
    public function updatePustaka(int $idPustaka, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE pustaka SET " . implode(', ', $set) . " WHERE id_pustaka = :id_pustaka";
        $data['id_pustaka'] = $idPustaka;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete pustaka
     */
    public function deletePustaka(int $idPustaka): bool
    {
        $sql = "DELETE FROM pustaka WHERE id_pustaka = :id_pustaka";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_pustaka' => $idPustaka]);
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $idRps): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN jenis = 'utama' THEN 1 END) as utama,
                COUNT(CASE WHEN jenis = 'pendukung' THEN 1 END) as pendukung
            FROM pustaka
            WHERE id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]) ?: [];
    }
}
