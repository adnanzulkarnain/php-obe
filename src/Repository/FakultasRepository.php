<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Fakultas Repository
 */
class FakultasRepository extends BaseRepository
{
    protected string $table = 'fakultas';
    protected string $primaryKey = 'id_fakultas';

    /**
     * Get all fakultas with prodi count
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                f.*,
                COUNT(DISTINCT p.id_prodi) as jumlah_prodi
            FROM fakultas f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            GROUP BY f.id_fakultas
            ORDER BY f.nama ASC
        ";

        return $this->query($sql);
    }

    /**
     * Find fakultas by ID with details
     */
    public function findWithDetails(string $idFakultas): ?array
    {
        $sql = "
            SELECT
                f.*,
                COUNT(DISTINCT p.id_prodi) as jumlah_prodi
            FROM fakultas f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            WHERE f.id_fakultas = :id_fakultas
            GROUP BY f.id_fakultas
        ";

        return $this->queryOne($sql, ['id_fakultas' => $idFakultas]);
    }

    /**
     * Check if fakultas exists
     */
    public function exists(string $idFakultas): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM fakultas WHERE id_fakultas = :id_fakultas",
            ['id_fakultas' => $idFakultas]
        );

        return $result['count'] > 0;
    }

    /**
     * Create fakultas
     */
    public function createFakultas(array $data): string
    {
        $sql = "
            INSERT INTO fakultas (id_fakultas, nama, created_at, updated_at)
            VALUES (:id_fakultas, :nama, NOW(), NOW())
            RETURNING id_fakultas
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetch(\PDO::FETCH_ASSOC)['id_fakultas'];
    }

    /**
     * Update fakultas
     */
    public function updateFakultas(string $idFakultas, array $data): bool
    {
        $sql = "UPDATE fakultas SET nama = :nama, updated_at = NOW() WHERE id_fakultas = :id_fakultas";
        $data['id_fakultas'] = $idFakultas;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
