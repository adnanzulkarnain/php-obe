<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Mahasiswa Repository
 * Handles database operations for mahasiswa (student) entity
 */
class MahasiswaRepository extends BaseRepository
{
    protected string $table = 'mahasiswa';
    protected string $primaryKey = 'nim';

    /**
     * Find mahasiswa by NIM with details
     */
    public function findByNimWithDetails(string $nim): ?array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                p.jenjang,
                k.nama_kurikulum,
                k.tahun_berlaku as tahun_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.nim = :nim
        ";

        return $this->queryOne($sql, ['nim' => $nim]);
    }

    /**
     * Find mahasiswa by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Find all mahasiswa by prodi
     */
    public function findByProdi(string $idProdi, ?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.id_prodi = :id_prodi
        ";

        $params = ['id_prodi' => $idProdi];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        $sql .= " ORDER BY m.angkatan DESC, m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all mahasiswa by kurikulum
     */
    public function findByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.id_kurikulum = :id_kurikulum
        ";

        $params = ['id_kurikulum' => $idKurikulum];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        $sql .= " ORDER BY m.angkatan DESC, m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all mahasiswa by angkatan
     */
    public function findByAngkatan(string $angkatan, ?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.angkatan = :angkatan
        ";

        $params = ['angkatan' => $angkatan];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND m.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        $sql .= " ORDER BY m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all mahasiswa by status
     */
    public function findByStatus(string $status): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.status = :status
            ORDER BY m.angkatan DESC, m.nama ASC
        ";

        return $this->query($sql, ['status' => $status]);
    }

    /**
     * Search mahasiswa by name or NIM
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE (
                m.nama ILIKE :keyword
                OR m.nim ILIKE :keyword
                OR m.email ILIKE :keyword
            )
        ";

        $params = ['keyword' => "%{$keyword}%"];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND m.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        $sql .= " ORDER BY m.angkatan DESC, m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Get all mahasiswa with details
     */
    public function getAllWithDetails(?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                p.jenjang,
                k.nama_kurikulum,
                k.tahun_berlaku as tahun_kurikulum
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE 1=1
        ";

        $params = [];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND m.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        if (isset($filters['id_kurikulum'])) {
            $sql .= " AND m.id_kurikulum = :id_kurikulum";
            $params['id_kurikulum'] = $filters['id_kurikulum'];
        }

        $sql .= " ORDER BY m.angkatan DESC, m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Check if email already exists (excluding specific mahasiswa)
     */
    public function emailExists(string $email, ?string $excludeNim = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeNim !== null) {
            $sql .= " AND nim != :nim";
            $params['nim'] = $excludeNim;
        }

        $result = $this->queryOne($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Get mahasiswa statistics by status
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
     * Get mahasiswa statistics by prodi
     */
    public function getStatisticsByProdi(): array
    {
        $sql = "
            SELECT
                p.id_prodi,
                p.nama as nama_prodi,
                COUNT(m.nim) as jumlah_mahasiswa,
                SUM(CASE WHEN m.status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif,
                SUM(CASE WHEN m.status = 'cuti' THEN 1 ELSE 0 END) as jumlah_cuti,
                SUM(CASE WHEN m.status = 'lulus' THEN 1 ELSE 0 END) as jumlah_lulus,
                SUM(CASE WHEN m.status = 'DO' THEN 1 ELSE 0 END) as jumlah_do,
                SUM(CASE WHEN m.status = 'keluar' THEN 1 ELSE 0 END) as jumlah_keluar
            FROM prodi p
            LEFT JOIN {$this->table} m ON p.id_prodi = m.id_prodi
            GROUP BY p.id_prodi, p.nama
            ORDER BY p.nama ASC
        ";

        return $this->query($sql);
    }

    /**
     * Get mahasiswa statistics by angkatan
     */
    public function getStatisticsByAngkatan(): array
    {
        $sql = "
            SELECT
                angkatan,
                COUNT(*) as jumlah_total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif,
                SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) as jumlah_cuti,
                SUM(CASE WHEN status = 'lulus' THEN 1 ELSE 0 END) as jumlah_lulus,
                SUM(CASE WHEN status = 'DO' THEN 1 ELSE 0 END) as jumlah_do,
                SUM(CASE WHEN status = 'keluar' THEN 1 ELSE 0 END) as jumlah_keluar
            FROM {$this->table}
            GROUP BY angkatan
            ORDER BY angkatan DESC
        ";

        return $this->query($sql);
    }

    /**
     * Get mahasiswa with academic performance (IPK, SKS)
     */
    public function getMahasiswaWithAcademicData(?array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.nama_kurikulum,
                COUNT(DISTINCT e.id_enrollment) as total_enrollment,
                SUM(CASE WHEN e.status = 'lulus' THEN mk.sks ELSE 0 END) as total_sks_lulus,
                ROUND(
                    SUM(CASE
                        WHEN e.nilai_huruf IN ('A','A-','AB','B+','B','B-','BC','C+','C','C-','D')
                        THEN
                            CASE e.nilai_huruf
                                WHEN 'A' THEN 4.00
                                WHEN 'A-' THEN 3.75
                                WHEN 'AB' THEN 3.50
                                WHEN 'B+' THEN 3.25
                                WHEN 'B' THEN 3.00
                                WHEN 'B-' THEN 2.75
                                WHEN 'BC' THEN 2.50
                                WHEN 'C+' THEN 2.25
                                WHEN 'C' THEN 2.00
                                WHEN 'C-' THEN 1.75
                                WHEN 'D' THEN 1.00
                                ELSE 0
                            END * mk.sks
                        ELSE 0
                    END) / NULLIF(SUM(CASE WHEN e.nilai_huruf IS NOT NULL THEN mk.sks ELSE 0 END), 0),
                    2
                ) as ipk
            FROM {$this->table} m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            LEFT JOIN enrollment e ON m.nim = e.nim
            LEFT JOIN kelas kls ON e.id_kelas = kls.id_kelas
            LEFT JOIN matakuliah mk ON kls.kode_mk = mk.kode_mk AND kls.id_kurikulum = mk.id_kurikulum
            WHERE 1=1
        ";

        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        if (isset($filters['id_prodi'])) {
            $sql .= " AND m.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        $sql .= "
            GROUP BY m.nim, m.nama, m.email, m.id_prodi, m.id_kurikulum, m.angkatan, m.status,
                     m.created_at, m.updated_at, p.nama, k.nama_kurikulum
            ORDER BY m.angkatan DESC, m.nama ASC
        ";

        return $this->query($sql, $params);
    }
}
