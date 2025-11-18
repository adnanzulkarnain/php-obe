<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Dosen Repository
 * Handles all lecturer-related database operations
 */
class DosenRepository extends BaseRepository
{
    protected string $table = 'dosen';
    protected string $primaryKey = 'id_dosen';

    /**
     * Get all dosen with filters
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi,
                COUNT(DISTINCT pk.id_kelas) as total_kelas
            FROM dosen d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN pengampu_kelas pk ON d.id_dosen = pk.id_dosen
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['id_prodi'])) {
            $sql .= " AND d.id_prodi = :id_prodi";
            $params['id_prodi'] = $filters['id_prodi'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.nama ILIKE :search OR d.nidn ILIKE :search OR d.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY d.id_dosen, p.nama ORDER BY d.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find dosen by ID with details
     */
    public function findWithDetails(string $idDosen): ?array
    {
        $sql = "
            SELECT
                d.*,
                p.nama as nama_prodi,
                p.id_fakultas,
                f.nama as nama_fakultas
            FROM dosen d
            LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
            WHERE d.id_dosen = :id_dosen
        ";

        return $this->queryOne($sql, ['id_dosen' => $idDosen]);
    }

    /**
     * Check if dosen exists by ID
     */
    public function exists(string $idDosen): bool
    {
        $result = $this->queryOne(
            "SELECT COUNT(*) as count FROM dosen WHERE id_dosen = :id_dosen",
            ['id_dosen' => $idDosen]
        );

        return $result['count'] > 0;
    }

    /**
     * Check if NIDN already exists
     */
    public function nidnExists(string $nidn, ?string $excludeIdDosen = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM dosen WHERE nidn = :nidn";
        $params = ['nidn' => $nidn];

        if ($excludeIdDosen !== null) {
            $sql .= " AND id_dosen != :id_dosen";
            $params['id_dosen'] = $excludeIdDosen;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Check if email already exists
     */
    public function emailExists(string $email, ?string $excludeIdDosen = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM dosen WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeIdDosen !== null) {
            $sql .= " AND id_dosen != :id_dosen";
            $params['id_dosen'] = $excludeIdDosen;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Create dosen
     */
    public function createDosen(array $data): string
    {
        $sql = "
            INSERT INTO dosen
            (id_dosen, nidn, nama, email, phone, id_prodi, status, created_at, updated_at)
            VALUES
            (:id_dosen, :nidn, :nama, :email, :phone, :id_prodi, :status, NOW(), NOW())
            RETURNING id_dosen
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetch(\PDO::FETCH_ASSOC)['id_dosen'];
    }

    /**
     * Update dosen
     */
    public function updateDosen(string $idDosen, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE dosen SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id_dosen = :id_dosen";
        $data['id_dosen'] = $idDosen;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Get dosen kelas (teaching assignments)
     */
    public function getTeachingAssignments(string $idDosen): array
    {
        $sql = "
            SELECT
                pk.*,
                k.nama_kelas,
                k.semester,
                k.tahun_ajaran,
                mk.nama_mk,
                COUNT(e.id_enrollment) as jumlah_mahasiswa
            FROM pengampu_kelas pk
            JOIN kelas k ON pk.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN enrollment e ON k.id_kelas = e.id_kelas
            WHERE pk.id_dosen = :id_dosen
            GROUP BY pk.id_pengampu, k.id_kelas, k.nama_kelas, k.semester, k.tahun_ajaran, mk.nama_mk
            ORDER BY k.tahun_ajaran DESC, k.semester DESC
        ";

        return $this->query($sql, ['id_dosen' => $idDosen]);
    }
}
