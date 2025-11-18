<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Fakultas Repository
 * Handles database operations for fakultas entity
 */
class FakultasRepository extends BaseRepository
{
    protected string $table = 'fakultas';
    protected string $primaryKey = 'id_fakultas';

    /**
     * Get all fakultas with prodi count
     */
    public function getAllWithProdiCount(): array
    {
        $sql = "
            SELECT
                f.*,
                COUNT(p.id_prodi) as jumlah_prodi
            FROM {$this->table} f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            GROUP BY f.id_fakultas, f.nama, f.created_at, f.updated_at
            ORDER BY f.nama ASC
        ";

        return $this->query($sql);
    }

    /**
     * Get fakultas by ID with details
     */
    public function findByIdWithDetails(string $idFakultas): ?array
    {
        $sql = "
            SELECT
                f.*,
                COUNT(p.id_prodi) as jumlah_prodi,
                COUNT(DISTINCT d.id_dosen) as jumlah_dosen,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa
            FROM {$this->table} f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            LEFT JOIN dosen d ON p.id_prodi = d.id_prodi
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            WHERE f.id_fakultas = :id_fakultas
            GROUP BY f.id_fakultas, f.nama, f.created_at, f.updated_at
        ";

        return $this->queryOne($sql, ['id_fakultas' => $idFakultas]);
    }

    /**
     * Search fakultas by name
     */
    public function search(string $keyword): array
    {
        $sql = "
            SELECT
                f.*,
                COUNT(p.id_prodi) as jumlah_prodi
            FROM {$this->table} f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            WHERE f.nama ILIKE :keyword
            GROUP BY f.id_fakultas, f.nama, f.created_at, f.updated_at
            ORDER BY f.nama ASC
        ";

        return $this->query($sql, ['keyword' => "%{$keyword}%"]);
    }

    /**
     * Get fakultas statistics
     */
    public function getStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(DISTINCT f.id_fakultas) as total_fakultas,
                COUNT(DISTINCT p.id_prodi) as total_prodi,
                COUNT(DISTINCT d.id_dosen) as total_dosen,
                COUNT(DISTINCT m.nim) as total_mahasiswa
            FROM {$this->table} f
            LEFT JOIN prodi p ON f.id_fakultas = p.id_fakultas
            LEFT JOIN dosen d ON p.id_prodi = d.id_prodi
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
        ";

        return $this->queryOne($sql) ?: [];
    }
}
