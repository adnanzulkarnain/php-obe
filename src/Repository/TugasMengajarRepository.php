<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Tugas Mengajar Repository
 */
class TugasMengajarRepository extends BaseRepository
{
    protected string $table = 'tugas_mengajar';
    protected string $primaryKey = 'id_tugas';

    /**
     * Find teaching assignments by kelas
     */
    public function findByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                tm.*,
                d.nama as nama_dosen,
                d.email as email_dosen,
                d.nidn
            FROM {$this->table} tm
            LEFT JOIN dosen d ON tm.id_dosen = d.id_dosen
            WHERE tm.id_kelas = :id_kelas
            ORDER BY
                CASE tm.peran
                    WHEN 'koordinator' THEN 1
                    WHEN 'pengampu' THEN 2
                    WHEN 'asisten' THEN 3
                END,
                d.nama ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Find teaching assignments by dosen
     */
    public function findByDosen(string $idDosen, ?array $filters = []): array
    {
        $sql = "
            SELECT
                tm.*,
                k.kode_mk,
                k.nama_kelas,
                k.semester,
                k.tahun_ajaran,
                k.status as status_kelas,
                k.hari,
                k.jam_mulai,
                k.jam_selesai,
                k.ruangan,
                m.nama_mk,
                m.sks
            FROM {$this->table} tm
            JOIN kelas k ON tm.id_kelas = k.id_kelas
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE tm.id_dosen = :id_dosen
        ";

        $params = ['id_dosen' => $idDosen];

        // Add optional filters
        if (isset($filters['semester'])) {
            $sql .= " AND k.semester = :semester";
            $params['semester'] = $filters['semester'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        if (isset($filters['peran'])) {
            $sql .= " AND tm.peran = :peran";
            $params['peran'] = $filters['peran'];
        }

        $sql .= " ORDER BY k.tahun_ajaran DESC, k.semester DESC, k.kode_mk ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find koordinator for a kelas
     */
    public function findKoordinator(int $idKelas): ?array
    {
        $sql = "
            SELECT
                tm.*,
                d.nama as nama_dosen,
                d.email as email_dosen,
                d.nidn
            FROM {$this->table} tm
            LEFT JOIN dosen d ON tm.id_dosen = d.id_dosen
            WHERE tm.id_kelas = :id_kelas AND tm.peran = 'koordinator'
            LIMIT 1
        ";

        return $this->queryOne($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Check if dosen already assigned to kelas
     */
    public function isDosenAssigned(int $idKelas, string $idDosen): bool
    {
        $result = $this->findOne([
            'id_kelas' => $idKelas,
            'id_dosen' => $idDosen
        ]);

        return $result !== null;
    }

    /**
     * Check if kelas has koordinator
     */
    public function hasKoordinator(int $idKelas): bool
    {
        $result = $this->findOne([
            'id_kelas' => $idKelas,
            'peran' => 'koordinator'
        ]);

        return $result !== null;
    }

    /**
     * Count teaching assignments by kelas
     */
    public function countByKelas(int $idKelas): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id_kelas = :id_kelas";
        $result = $this->queryOne($sql, ['id_kelas' => $idKelas]);
        return (int)$result['count'];
    }

    /**
     * Count teaching assignments by dosen in a semester
     */
    public function countByDosenSemester(string $idDosen, string $semester, string $tahunAjaran): int
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table} tm
            JOIN kelas k ON tm.id_kelas = k.id_kelas
            WHERE tm.id_dosen = :id_dosen
            AND k.semester = :semester
            AND k.tahun_ajaran = :tahun_ajaran
        ";

        $result = $this->queryOne($sql, [
            'id_dosen' => $idDosen,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return (int)$result['count'];
    }

    /**
     * Remove teaching assignment
     */
    public function removeAssignment(int $idKelas, string $idDosen): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_kelas = :id_kelas AND id_dosen = :id_dosen";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_kelas' => $idKelas,
            'id_dosen' => $idDosen
        ]);
    }

    /**
     * Remove all assignments for a kelas
     */
    public function removeAllByKelas(int $idKelas): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_kelas = :id_kelas";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_kelas' => $idKelas]);
    }

    /**
     * Update peran
     */
    public function updatePeran(int $idKelas, string $idDosen, string $peran): bool
    {
        $sql = "UPDATE {$this->table} SET peran = :peran WHERE id_kelas = :id_kelas AND id_dosen = :id_dosen";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'peran' => $peran,
            'id_kelas' => $idKelas,
            'id_dosen' => $idDosen
        ]);
    }

    /**
     * Get teaching load statistics for a dosen
     */
    public function getTeachingLoadStats(string $idDosen, string $semester, string $tahunAjaran): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_kelas,
                COUNT(CASE WHEN tm.peran = 'koordinator' THEN 1 END) as total_koordinator,
                COUNT(CASE WHEN tm.peran = 'pengampu' THEN 1 END) as total_pengampu,
                COUNT(CASE WHEN tm.peran = 'asisten' THEN 1 END) as total_asisten,
                SUM(m.sks) as total_sks
            FROM {$this->table} tm
            JOIN kelas k ON tm.id_kelas = k.id_kelas
            JOIN matakuliah m ON k.kode_mk = m.kode_mk AND k.id_kurikulum = m.id_kurikulum
            WHERE tm.id_dosen = :id_dosen
            AND k.semester = :semester
            AND k.tahun_ajaran = :tahun_ajaran
        ";

        return $this->queryOne($sql, [
            'id_dosen' => $idDosen,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]) ?: [];
    }
}
