<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Prodi Repository
 * Handles database operations for prodi (study program) entity
 */
class ProdiRepository extends BaseRepository
{
    protected string $table = 'prodi';
    protected string $primaryKey = 'id_prodi';

    /**
     * Get all prodi with fakultas info
     */
    public function getAllWithFakultas(?array $filters = []): array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas
            FROM {$this->table} p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            WHERE 1=1
        ";

        $params = [];

        // Add optional filters
        if (isset($filters['id_fakultas'])) {
            $sql .= " AND p.id_fakultas = :id_fakultas";
            $params['id_fakultas'] = $filters['id_fakultas'];
        }

        if (isset($filters['jenjang'])) {
            $sql .= " AND p.jenjang = :jenjang";
            $params['jenjang'] = $filters['jenjang'];
        }

        if (isset($filters['akreditasi'])) {
            $sql .= " AND p.akreditasi = :akreditasi";
            $params['akreditasi'] = $filters['akreditasi'];
        }

        $sql .= " ORDER BY f.nama ASC, p.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Get prodi by ID with details
     */
    public function findByIdWithDetails(string $idProdi): ?array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas,
                COUNT(DISTINCT k.id_kurikulum) as jumlah_kurikulum,
                COUNT(DISTINCT d.id_dosen) as jumlah_dosen,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa
            FROM {$this->table} p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            LEFT JOIN kurikulum k ON p.id_prodi = k.id_prodi
            LEFT JOIN dosen d ON p.id_prodi = d.id_prodi
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            WHERE p.id_prodi = :id_prodi
            GROUP BY p.id_prodi, p.id_fakultas, p.nama, p.jenjang, p.akreditasi,
                     p.tahun_berdiri, p.created_at, p.updated_at, f.nama
        ";

        return $this->queryOne($sql, ['id_prodi' => $idProdi]);
    }

    /**
     * Get all prodi by fakultas
     */
    public function findByFakultas(string $idFakultas): array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa
            FROM {$this->table} p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            WHERE p.id_fakultas = :id_fakultas
            GROUP BY p.id_prodi, p.id_fakultas, p.nama, p.jenjang, p.akreditasi,
                     p.tahun_berdiri, p.created_at, p.updated_at, f.nama
            ORDER BY p.nama ASC
        ";

        return $this->query($sql, ['id_fakultas' => $idFakultas]);
    }

    /**
     * Get prodi by jenjang
     */
    public function findByJenjang(string $jenjang): array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas
            FROM {$this->table} p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            WHERE p.jenjang = :jenjang
            ORDER BY f.nama ASC, p.nama ASC
        ";

        return $this->query($sql, ['jenjang' => $jenjang]);
    }

    /**
     * Search prodi by name
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        $sql = "
            SELECT
                p.*,
                f.nama as nama_fakultas
            FROM {$this->table} p
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            WHERE p.nama ILIKE :keyword
        ";

        $params = ['keyword' => "%{$keyword}%"];

        // Add optional filters
        if (isset($filters['id_fakultas'])) {
            $sql .= " AND p.id_fakultas = :id_fakultas";
            $params['id_fakultas'] = $filters['id_fakultas'];
        }

        if (isset($filters['jenjang'])) {
            $sql .= " AND p.jenjang = :jenjang";
            $params['jenjang'] = $filters['jenjang'];
        }

        $sql .= " ORDER BY f.nama ASC, p.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Get prodi statistics
     */
    public function getStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(DISTINCT p.id_prodi) as total_prodi,
                COUNT(DISTINCT CASE WHEN p.jenjang IN ('D3','D4') THEN p.id_prodi END) as total_diploma,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S1' THEN p.id_prodi END) as total_sarjana,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S2' THEN p.id_prodi END) as total_magister,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S3' THEN p.id_prodi END) as total_doktor,
                COUNT(DISTINCT CASE WHEN p.akreditasi IN ('A','Unggul') THEN p.id_prodi END) as total_akreditasi_a
            FROM {$this->table} p
        ";

        return $this->queryOne($sql) ?: [];
    }

    /**
     * Get statistics by fakultas
     */
    public function getStatisticsByFakultas(): array
    {
        $sql = "
            SELECT
                f.id_fakultas,
                f.nama as nama_fakultas,
                COUNT(DISTINCT p.id_prodi) as jumlah_prodi,
                COUNT(DISTINCT CASE WHEN p.jenjang IN ('D3','D4') THEN p.id_prodi END) as jumlah_diploma,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S1' THEN p.id_prodi END) as jumlah_sarjana,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S2' THEN p.id_prodi END) as jumlah_magister,
                COUNT(DISTINCT CASE WHEN p.jenjang = 'S3' THEN p.id_prodi END) as jumlah_doktor,
                COUNT(DISTINCT d.id_dosen) as jumlah_dosen,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa
            FROM fakultas f
            LEFT JOIN {$this->table} p ON f.id_fakultas = p.id_fakultas
            LEFT JOIN dosen d ON p.id_prodi = d.id_prodi
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            GROUP BY f.id_fakultas, f.nama
            ORDER BY f.nama ASC
        ";

        return $this->query($sql);
    }

    /**
     * Get statistics by jenjang
     */
    public function getStatisticsByJenjang(): array
    {
        $sql = "
            SELECT
                p.jenjang,
                COUNT(DISTINCT p.id_prodi) as jumlah_prodi,
                COUNT(DISTINCT m.nim) as jumlah_mahasiswa,
                COUNT(DISTINCT d.id_dosen) as jumlah_dosen
            FROM {$this->table} p
            LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
            LEFT JOIN dosen d ON p.id_prodi = d.id_prodi
            GROUP BY p.jenjang
            ORDER BY p.jenjang ASC
        ";

        return $this->query($sql);
    }
}
