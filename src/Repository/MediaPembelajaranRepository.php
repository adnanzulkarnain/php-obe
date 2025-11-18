<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Media Pembelajaran Repository
 * Handles learning media database operations
 */
class MediaPembelajaranRepository extends BaseRepository
{
    protected string $table = 'media_pembelajaran';
    protected string $primaryKey = 'id_media';

    /**
     * Get all media by RPS
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT *
            FROM media_pembelajaran
            WHERE id_rps = :id_rps
            ORDER BY kategori ASC, nama ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get media by kategori
     */
    public function findByKategori(int $idRps, string $kategori): array
    {
        $sql = "
            SELECT *
            FROM media_pembelajaran
            WHERE id_rps = :id_rps AND kategori = :kategori
            ORDER BY nama ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps, 'kategori' => $kategori]);
    }

    /**
     * Create media
     */
    public function createMedia(array $data): int
    {
        $sql = "
            INSERT INTO media_pembelajaran
            (id_rps, kategori, nama, deskripsi, created_at)
            VALUES
            (:id_rps, :kategori, :nama, :deskripsi, NOW())
            RETURNING id_media
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_media'];
    }

    /**
     * Update media
     */
    public function updateMedia(int $idMedia, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE media_pembelajaran SET " . implode(', ', $set) . " WHERE id_media = :id_media";
        $data['id_media'] = $idMedia;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete media
     */
    public function deleteMedia(int $idMedia): bool
    {
        $sql = "DELETE FROM media_pembelajaran WHERE id_media = :id_media";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_media' => $idMedia]);
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $idRps): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN kategori = 'software' THEN 1 END) as software,
                COUNT(CASE WHEN kategori = 'hardware' THEN 1 END) as hardware,
                COUNT(CASE WHEN kategori = 'platform' THEN 1 END) as platform
            FROM media_pembelajaran
            WHERE id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]) ?: [];
    }
}
