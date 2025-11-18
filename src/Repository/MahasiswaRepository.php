<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Mahasiswa Repository
 * Handles all student-related database operations
 */
class MahasiswaRepository extends BaseRepository
{
    protected string $table = 'mahasiswa';
    protected string $primaryKey = 'nim';

    /**
     * Get all mahasiswa with filters
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                k.kode_kurikulum,
                k.nama_kurikulum,
                COUNT(DISTINCT e.id_enrollment) as total_enrollment
            FROM mahasiswa m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            LEFT JOIN enrollment e ON m.nim = e.nim
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['id_prodi'])) {
            $sql .= " AND m.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        if (!empty($filters['id_kurikulum'])) {
            $sql .= " AND m.id_kurikulum = :id_kurikulum";
            $params['id_kurikulum'] = $filters['id_kurikulum'];
        }

        if (!empty($filters['angkatan'])) {
            $sql .= " AND m.angkatan = :angkatan";
            $params['angkatan'] = $filters['angkatan'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND m.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (m.nama ILIKE :search OR m.nim ILIKE :search OR m.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY m.nim, p.nama, k.kode_kurikulum, k.nama_kurikulum ORDER BY m.nim DESC";

        return $this->query($sql, $params);
    }

    /**
     * Find mahasiswa by NIM with details
     */
    public function findWithDetails(string $nim): ?array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi,
                p.id_fakultas,
                f.nama as nama_fakultas,
                k.kode_kurikulum,
                k.nama_kurikulum,
                k.tahun_berlaku
            FROM mahasiswa m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            LEFT JOIN kurikulum k ON m.id_kurikulum = k.id_kurikulum
            WHERE m.nim = :nim
        ";

        return $this->queryOne($sql, ['nim' => $nim]);
    }

    /**
     * Check if mahasiswa exists
     */
    public function exists(string $nim): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM mahasiswa WHERE nim = :nim",
            ['nim' => $nim]
        );

        return $result['count'] > 0;
    }

    /**
     * Check if email already exists
     */
    public function emailExists(string $email, ?string $excludeNim = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM mahasiswa WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeNim !== null) {
            $sql .= " AND nim != :nim";
            $params['nim'] = $excludeNim;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Create mahasiswa
     */
    public function createMahasiswa(array $data): string
    {
        $sql = "
            INSERT INTO mahasiswa
            (nim, nama, email, id_prodi, id_kurikulum, angkatan, status, created_at, updated_at)
            VALUES
            (:nim, :nama, :email, :id_prodi, :id_kurikulum, :angkatan, :status, NOW(), NOW())
            RETURNING nim
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetch(\PDO::FETCH_ASSOC)['nim'];
    }

    /**
     * Update mahasiswa
     */
    public function updateMahasiswa(string $nim, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE mahasiswa SET " . implode(', ', $set) . ", updated_at = NOW() WHERE nim = :nim";
        $data['nim'] = $nim;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Get mahasiswa statistics by prodi
     */
    public function getStatisticsByProdi(string $idProdi): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) as jumlah
            FROM mahasiswa
            WHERE id_prodi = :id_prodi
            GROUP BY status
        ";

        return $this->query($sql, ['id_prodi' => $idProdi]);
    }

    /**
     * Get mahasiswa by angkatan
     */
    public function getByAngkatan(string $angkatan): array
    {
        $sql = "
            SELECT
                m.*,
                p.nama as nama_prodi
            FROM mahasiswa m
            LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
            WHERE m.angkatan = :angkatan
            ORDER BY m.nim ASC
        ";

        return $this->query($sql, ['angkatan' => $angkatan]);
    }
}
