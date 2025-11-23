<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Kelas Repository
 */
class KelasRepository extends BaseRepository
{
    protected string $table = 'kelas';
    protected string $primaryKey = 'id_kelas';

    /**
     * Find kelas by ID with mata kuliah info
     */
    public function findByIdWithDetails(int $idKelas): ?array
    {
        $sql = "
            SELECT
                k.*,
                m.nama_mk,
                m.sks,
                m.jenis_mk
            FROM {$this->table} k
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE k.id_kelas = :id_kelas
        ";

        return $this->queryOne($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Find all kelas by mata kuliah
     */
    public function findByMataKuliah(string $kodeMk, int $idKurikulum, ?array $filters = []): array
    {
        $conditions = [
            'k.kode_mk' => $kodeMk,
            'k.id_kurikulum' => $idKurikulum
        ];

        $sql = "
            SELECT
                k.*,
                m.nama_mk,
                m.sks,
                m.jenis_mk
            FROM {$this->table} k
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE k.kode_mk = :kode_mk AND k.id_kurikulum = :id_kurikulum
        ";

        $params = [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ];

        // Add optional filters
        if (isset($filters['semester'])) {
            $sql .= " AND k.semester = :semester";
            $params['semester'] = $filters['semester'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND k.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY k.tahun_ajaran DESC, k.semester DESC, k.nama_kelas ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all kelas by kurikulum
     */
    public function findByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        $sql = "
            SELECT
                k.*,
                m.nama_mk,
                m.sks,
                m.jenis_mk
            FROM {$this->table} k
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE k.id_kurikulum = :id_kurikulum
        ";

        $params = ['id_kurikulum' => $idKurikulum];

        // Add optional filters
        if (isset($filters['semester'])) {
            $sql .= " AND k.semester = :semester";
            $params['semester'] = $filters['semester'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND k.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY k.tahun_ajaran DESC, k.semester DESC, m.semester ASC, k.nama_kelas ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find kelas by semester and tahun ajaran
     */
    public function findBySemesterTahunAjaran(string $semester, string $tahunAjaran, ?int $idKurikulum = null): array
    {
        $sql = "
            SELECT
                k.*,
                m.nama_mk,
                m.sks,
                m.jenis_mk,
                m.semester as semester_mk
            FROM {$this->table} k
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE k.semester = :semester AND k.tahun_ajaran = :tahun_ajaran
        ";

        $params = [
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ];

        if ($idKurikulum !== null) {
            $sql .= " AND k.id_kurikulum = :id_kurikulum";
            $params['id_kurikulum'] = $idKurikulum;
        }

        $sql .= " ORDER BY m.semester ASC, k.kode_mk ASC, k.nama_kelas ASC";

        return $this->query($sql, $params);
    }

    /**
     * Check if kelas already exists
     */
    public function exists(string $kodeMk, int $idKurikulum, string $namaKelas, string $semester, string $tahunAjaran): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE kode_mk = :kode_mk
            AND id_kurikulum = :id_kurikulum
            AND nama_kelas = :nama_kelas
            AND semester = :semester
            AND tahun_ajaran = :tahun_ajaran
        ";

        $result = $this->queryOne($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum,
            'nama_kelas' => $namaKelas,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return $result['count'] > 0;
    }

    /**
     * Update kuota terisi (enrollment count)
     */
    public function updateKuotaTerisi(int $idKelas, int $kuotaTerisi): bool
    {
        return $this->update($idKelas, [
            'kuota_terisi' => $kuotaTerisi,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Increment kuota terisi
     */
    public function incrementKuotaTerisi(int $idKelas): bool
    {
        $sql = "UPDATE {$this->table} SET kuota_terisi = kuota_terisi + 1, updated_at = NOW() WHERE id_kelas = :id_kelas";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_kelas' => $idKelas]);
    }

    /**
     * Decrement kuota terisi
     */
    public function decrementKuotaTerisi(int $idKelas): bool
    {
        $sql = "UPDATE {$this->table} SET kuota_terisi = GREATEST(0, kuota_terisi - 1), updated_at = NOW() WHERE id_kelas = :id_kelas";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_kelas' => $idKelas]);
    }

    /**
     * Get enrollment count
     */
    public function getEnrollmentCount(int $idKelas): int
    {
        $sql = "SELECT COUNT(*) as count FROM enrollment WHERE id_kelas = :id_kelas AND status = 'aktif'";
        $result = $this->queryOne($sql, ['id_kelas' => $idKelas]);
        return (int)$result['count'];
    }

    /**
     * Check if kelas has capacity
     */
    public function hasCapacity(int $idKelas): bool
    {
        $sql = "SELECT (kapasitas - kuota_terisi) as available FROM {$this->table} WHERE id_kelas = :id_kelas";
        $result = $this->queryOne($sql, ['id_kelas' => $idKelas]);
        return isset($result['available']) && $result['available'] > 0;
    }

    /**
     * Change status
     */
    public function changeStatus(int $idKelas, string $status): bool
    {
        return $this->update($idKelas, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get kelas with teaching assignments
     * Optimized to use single query with LEFT JOIN to avoid multiple queries
     */
    public function findWithTeachingAssignments(int $idKelas): ?array
    {
        $sql = "
            SELECT
                k.*,
                m.nama_mk,
                m.sks,
                m.jenis_mk,
                tm.id_tugas_mengajar,
                tm.id_dosen,
                tm.peran,
                tm.created_at as tm_created_at,
                tm.updated_at as tm_updated_at,
                d.nama as nama_dosen,
                d.email as email_dosen
            FROM {$this->table} k
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            LEFT JOIN tugas_mengajar tm ON k.id_kelas = tm.id_kelas
            LEFT JOIN dosen d ON tm.id_dosen = d.id_dosen
            WHERE k.id_kelas = :id_kelas
            ORDER BY
                CASE tm.peran
                    WHEN 'koordinator' THEN 1
                    WHEN 'pengampu' THEN 2
                    WHEN 'asisten' THEN 3
                END
        ";

        $rows = $this->query($sql, ['id_kelas' => $idKelas]);

        if (empty($rows)) {
            return null;
        }

        // First row contains kelas data
        $kelas = [
            'id_kelas' => $rows[0]['id_kelas'],
            'kode_mk' => $rows[0]['kode_mk'],
            'id_kurikulum' => $rows[0]['id_kurikulum'],
            'nama_kelas' => $rows[0]['nama_kelas'],
            'semester' => $rows[0]['semester'],
            'tahun_ajaran' => $rows[0]['tahun_ajaran'],
            'kapasitas' => $rows[0]['kapasitas'],
            'kuota_terisi' => $rows[0]['kuota_terisi'],
            'status' => $rows[0]['status'],
            'created_at' => $rows[0]['created_at'],
            'updated_at' => $rows[0]['updated_at'],
            'nama_mk' => $rows[0]['nama_mk'],
            'sks' => $rows[0]['sks'],
            'jenis_mk' => $rows[0]['jenis_mk'],
            'dosen' => []
        ];

        // Extract teaching assignments
        foreach ($rows as $row) {
            if ($row['id_tugas_mengajar'] !== null) {
                $kelas['dosen'][] = [
                    'id_tugas_mengajar' => $row['id_tugas_mengajar'],
                    'id_kelas' => $row['id_kelas'],
                    'id_dosen' => $row['id_dosen'],
                    'peran' => $row['peran'],
                    'created_at' => $row['tm_created_at'],
                    'updated_at' => $row['tm_updated_at'],
                    'nama_dosen' => $row['nama_dosen'],
                    'email_dosen' => $row['email_dosen']
                ];
            }
        }

        return $kelas;
    }

    /**
     * Get statistics for a semester
     */
    public function getStatistics(string $semester, string $tahunAjaran, ?int $idKurikulum = null): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_kelas,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as kelas_open,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as kelas_closed,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as kelas_draft,
                SUM(kapasitas) as total_kapasitas,
                SUM(kuota_terisi) as total_terisi,
                ROUND(AVG(CAST(kuota_terisi AS DECIMAL) / NULLIF(kapasitas, 0) * 100), 2) as avg_fill_rate
            FROM {$this->table}
            WHERE semester = :semester AND tahun_ajaran = :tahun_ajaran
        ";

        $params = [
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ];

        if ($idKurikulum !== null) {
            $sql .= " AND id_kurikulum = :id_kurikulum";
            $params['id_kurikulum'] = $idKurikulum;
        }

        return $this->queryOne($sql, $params) ?: [];
    }
}
