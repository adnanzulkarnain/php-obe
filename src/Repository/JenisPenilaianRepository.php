<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Jenis Penilaian Repository
 * Data access layer for assessment types (UTS, UAS, Tugas, etc)
 */
class JenisPenilaianRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->setTable('jenis_penilaian');
        $this->setPrimaryKey('id_jenis');
    }

    /**
     * Get all jenis penilaian
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM jenis_penilaian WHERE 1=1";
        $params = [];

        if (!empty($filters['kategori'])) {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }

        $sql .= " ORDER BY kategori, urutan ASC";

        return $this->query($sql, $params);
    }

    /**
     * Get by kategori
     */
    public function getByKategori(string $kategori): array
    {
        $sql = "
            SELECT * FROM jenis_penilaian
            WHERE kategori = :kategori
            ORDER BY urutan ASC
        ";

        return $this->query($sql, ['kategori' => $kategori]);
    }
}
