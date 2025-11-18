<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Dosen Repository
 * Handles database operations for dosen (lecturer/faculty) entity
 */
class DosenRepository extends BaseRepository
{
    protected string $table = 'dosen';
    protected string $primaryKey = 'id_dosen';

    /**
     * Find dosen by ID with prodi info
     */
    public function findByIdWithDetails(string $idDosen): ?array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi,
                p.jenjang,
                p.id_fakultas
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            WHERE d.id_dosen = :id_dosen
        ";

        return $this->queryOne($sql, ['id_dosen' => $idDosen]);
    }

    /**
     * Find dosen by NIDN
     */
    public function findByNidn(string $nidn): ?array
    {
        return $this->findOne(['nidn' => $nidn]);
    }

    /**
     * Find dosen by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Find all dosen by prodi
     */
    public function findByProdi(string $idProdi, ?array $filters = []): array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            WHERE d.id_prodi = :id_prodi
        ";

        $params = ['id_prodi' => $idProdi];

        // Add status filter
        if (isset($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY d.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all dosen by status
     */
    public function findByStatus(string $status): array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            WHERE d.status = :status
            ORDER BY d.nama ASC
        ";

        return $this->query($sql, ['status' => $status]);
    }

    /**
     * Search dosen by name or NIDN
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            WHERE (
                d.nama ILIKE :keyword
                OR d.nidn ILIKE :keyword
                OR d.email ILIKE :keyword
            )
        ";

        $params = ['keyword' => "%{$keyword}%"];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND d.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        $sql .= " ORDER BY d.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Get all dosen with details
     */
    public function getAllWithDetails(?array $filters = []): array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi,
                p.jenjang
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            WHERE 1=1
        ";

        $params = [];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND d.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        $sql .= " ORDER BY d.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Check if NIDN already exists (excluding specific dosen)
     */
    public function nidnExists(string $nidn, ?string $excludeIdDosen = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nidn = :nidn";
        $params = ['nidn' => $nidn];

        if ($excludeIdDosen !== null) {
            $sql .= " AND id_dosen != :id_dosen";
            $params['id_dosen'] = $excludeIdDosen;
        }

        $result = $this->queryOne($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Check if email already exists (excluding specific dosen)
     */
    public function emailExists(string $email, ?string $excludeIdDosen = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeIdDosen !== null) {
            $sql .= " AND id_dosen != :id_dosen";
            $params['id_dosen'] = $excludeIdDosen;
        }

        $result = $this->queryOne($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Get dosen statistics by status
     */
    public function getStatisticsByStatus(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) as jumlah
            FROM {$this->table}
            GROUP BY status
            ORDER BY status ASC
        ";

        return $this->query($sql);
    }

    /**
     * Get dosen statistics by prodi
     */
    public function getStatisticsByProdi(): array
    {
        $sql = "
            SELECT
                p.id_prodi,
                p.nama as nama_prodi,
                COUNT(d.id_dosen) as jumlah_dosen,
                SUM(CASE WHEN d.status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif,
                SUM(CASE WHEN d.status = 'cuti' THEN 1 ELSE 0 END) as jumlah_cuti,
                SUM(CASE WHEN d.status = 'pensiun' THEN 1 ELSE 0 END) as jumlah_pensiun
            FROM prodi p
            LEFT JOIN {$this->table} d ON p.id_prodi = d.id_prodi
            GROUP BY p.id_prodi, p.nama
            ORDER BY p.nama ASC
        ";

        return $this->query($sql);
    }

    /**
     * Get dosen with teaching load
     */
    public function getDosenWithTeachingLoad(?string $tahunAjaran = null, ?string $semester = null): array
    {
        $sql = "
            SELECT
                d.id_dosen,
                d.nidn,
                d.nama,
                d.email,
                d.status,
                p.nama as nama_prodi,
                COUNT(DISTINCT tm.id_kelas) as jumlah_kelas,
                COUNT(DISTINCT CASE WHEN tm.peran = 'koordinator' THEN tm.id_kelas END) as jumlah_koordinator,
                SUM(DISTINCT m.sks) as total_sks
            FROM {$this->table} d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN tugas_mengajar tm ON d.id_dosen = tm.id_dosen
            LEFT JOIN kelas k ON tm.id_kelas = k.id_kelas
            LEFT JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE 1=1
        ";

        $params = [];

        if ($tahunAjaran !== null) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $tahunAjaran;
        }

        if ($semester !== null) {
            $sql .= " AND k.semester = :semester";
            $params['semester'] = $semester;
        }

        $sql .= "
            GROUP BY d.id_dosen, d.nidn, d.nama, d.email, d.status, p.nama
            ORDER BY d.nama ASC
        ";

        return $this->query($sql, $params);
    }
}
