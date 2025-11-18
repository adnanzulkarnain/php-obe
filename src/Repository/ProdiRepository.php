<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Prodi Repository
 */
class ProdiRepository extends BaseRepository
{
    protected string $table = 'prodi';
    protected string $primaryKey = 'id_prodi';

    /**
     * Get all prodi with details
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa
            FROM prodi p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['id_fakultas'])) {
            $sql .= " AND p.id_fakultas = :id_fakultas";
            $params['id_fakultas'] = $filters['id_fakultas'];
        }

        $sql .= " GROUP BY p.id_prodi, f.nama ORDER BY f.nama, p.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find prodi by ID with details
     */
    public function findWithDetails(string $idProdi): ?array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas
            FROM prodi p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            WHERE p.id_prodi = :id_prodi
        ";

        return $this->queryOne($sql, ['id_prodi' => $idProdi]);
    }

    /**
     * Check if prodi exists
     */
    public function exists(string $idProdi): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM prodi WHERE id_prodi = :id_prodi",
            ['id_prodi' => $idProdi]
        );

        return $result['count'] > 0;
    }

    /**
     * Create prodi
     */
    public function createProdi(array $data): string
    {
        $sql = "
            INSERT INTO prodi (id_prodi, id_fakultas, nama, jenjang, akreditasi, tahun_berdiri, created_at, updated_at)
            VALUES (:id_prodi, :id_fakultas, :nama, :jenjang, :akreditasi, :tahun_berdiri, NOW(), NOW())
            RETURNING id_prodi
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetch(\PDO::FETCH_ASSOC)['id_prodi'];
    }

    /**
     * Update prodi
     */
    public function updateProdi(string $idProdi, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE prodi SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id_prodi = :id_prodi";
        $data['id_prodi'] = $idProdi;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
