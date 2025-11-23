<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Enrollment Repository
 */
class EnrollmentRepository extends BaseRepository
{
    protected string $table = 'enrollment';
    protected string $primaryKey = 'id_enrollment';

    /**
     * Find enrollment by ID with details
     */
    public function findByIdWithDetails(int $idEnrollment): ?array
    {
        $sql = "
            SELECT
                e.*,
                m.nama as nama_mahasiswa,
                m.email as email_mahasiswa,
                k.kode_mk,
                k.nama_kelas,
                k.semester,
                k.tahun_ajaran,
                k.id_kurikulum,
                mk.nama_mk,
                mk.sks
            FROM {$this->table} e
            JOIN mahasiswa m ON e.nim = m.nim
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE e.id_enrollment = :id_enrollment
        ";

        return $this->queryOne($sql, ['id_enrollment' => $idEnrollment]);
    }

    /**
     * Find enrollments by mahasiswa (NIM)
     */
    public function findByMahasiswa(string $nim, ?array $filters = []): array
    {
        $sql = "
            SELECT
                e.*,
                k.kode_mk,
                k.nama_kelas,
                k.semester,
                k.tahun_ajaran,
                k.status as status_kelas,
                mk.nama_mk,
                mk.sks,
                mk.semester as semester_mk
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE e.nim = :nim
        ";

        $params = ['nim' => $nim];

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
            $sql .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY k.tahun_ajaran DESC, k.semester DESC, mk.semester ASC, k.kode_mk ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find enrollments by kelas
     */
    public function findByKelas(int $idKelas, ?array $filters = []): array
    {
        $sql = "
            SELECT
                e.*,
                m.nama as nama_mahasiswa,
                m.email as email_mahasiswa,
                m.angkatan
            FROM {$this->table} e
            JOIN mahasiswa m ON e.nim = m.nim
            WHERE e.id_kelas = :id_kelas
        ";

        $params = ['id_kelas' => $idKelas];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY m.nama ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find enrollments by semester and tahun ajaran
     */
    public function findBySemesterTahunAjaran(string $semester, string $tahunAjaran, ?string $nim = null): array
    {
        $sql = "
            SELECT
                e.*,
                k.kode_mk,
                k.nama_kelas,
                k.semester,
                k.tahun_ajaran,
                mk.nama_mk,
                mk.sks,
                mk.semester as semester_mk
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE k.semester = :semester AND k.tahun_ajaran = :tahun_ajaran
        ";

        $params = [
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ];

        if ($nim !== null) {
            $sql .= " AND e.nim = :nim";
            $params['nim'] = $nim;
        }

        $sql .= " ORDER BY mk.semester ASC, k.kode_mk ASC";

        return $this->query($sql, $params);
    }

    /**
     * Check if mahasiswa already enrolled in kelas
     */
    public function isEnrolled(string $nim, int $idKelas): bool
    {
        $result = $this->findOne([
            'nim' => $nim,
            'id_kelas' => $idKelas
        ]);

        return $result !== null;
    }

    /**
     * Get active enrollment count for a mahasiswa in a semester
     */
    public function getActiveEnrollmentCount(string $nim, string $semester, string $tahunAjaran): int
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            WHERE e.nim = :nim
            AND k.semester = :semester
            AND k.tahun_ajaran = :tahun_ajaran
            AND e.status IN ('aktif', 'mengulang')
        ";

        $result = $this->queryOne($sql, [
            'nim' => $nim,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return (int)$result['count'];
    }

    /**
     * Get total SKS enrolled for a mahasiswa in a semester
     */
    public function getTotalSKSEnrolled(string $nim, string $semester, string $tahunAjaran): int
    {
        $sql = "
            SELECT COALESCE(SUM(mk.sks), 0) as total_sks
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE e.nim = :nim
            AND k.semester = :semester
            AND k.tahun_ajaran = :tahun_ajaran
            AND e.status IN ('aktif', 'mengulang')
        ";

        $result = $this->queryOne($sql, [
            'nim' => $nim,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return (int)$result['total_sks'];
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(int $idEnrollment, string $status): bool
    {
        return $this->update($idEnrollment, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update grades
     */
    public function updateGrades(int $idEnrollment, float $nilaiAkhir, string $nilaiHuruf): bool
    {
        return $this->update($idEnrollment, [
            'nilai_akhir' => $nilaiAkhir,
            'nilai_huruf' => $nilaiHuruf,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Bulk enroll students
     * Optimized: Uses single multi-row INSERT instead of individual INSERTs
     */
    public function bulkEnroll(array $enrollments): bool
    {
        if (empty($enrollments)) {
            return true;
        }

        $this->db->beginTransaction();

        try {
            // Build multi-row INSERT statement
            $values = [];
            $params = [];
            $now = date('Y-m-d H:i:s');
            $today = date('Y-m-d');

            foreach ($enrollments as $index => $enrollment) {
                $values[] = "(:nim_{$index}, :id_kelas_{$index}, :tanggal_daftar_{$index}, :status_{$index}, :created_at_{$index}, :updated_at_{$index})";

                $params["nim_{$index}"] = $enrollment['nim'];
                $params["id_kelas_{$index}"] = $enrollment['id_kelas'];
                $params["tanggal_daftar_{$index}"] = $enrollment['tanggal_daftar'] ?? $today;
                $params["status_{$index}"] = $enrollment['status'] ?? 'aktif';
                $params["created_at_{$index}"] = $now;
                $params["updated_at_{$index}"] = $now;
            }

            $sql = "
                INSERT INTO {$this->table}
                (nim, id_kelas, tanggal_daftar, status, created_at, updated_at)
                VALUES " . implode(', ', $values);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get KRS summary for a mahasiswa in a semester
     */
    public function getKRSSummary(string $nim, string $semester, string $tahunAjaran): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_mk,
                COUNT(CASE WHEN e.status = 'aktif' THEN 1 END) as mk_aktif,
                COUNT(CASE WHEN e.status = 'mengulang' THEN 1 END) as mk_mengulang,
                COUNT(CASE WHEN e.status = 'drop' THEN 1 END) as mk_drop,
                COUNT(CASE WHEN e.status = 'lulus' THEN 1 END) as mk_lulus,
                SUM(CASE WHEN e.status IN ('aktif', 'mengulang') THEN mk.sks ELSE 0 END) as total_sks,
                ROUND(AVG(CASE WHEN e.status = 'lulus' THEN e.nilai_akhir END), 2) as rata_rata_nilai
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE e.nim = :nim
            AND k.semester = :semester
            AND k.tahun_ajaran = :tahun_ajaran
        ";

        return $this->queryOne($sql, [
            'nim' => $nim,
            'semester' => $semester,
            'tahun_ajaran' => $tahunAjaran
        ]) ?: [];
    }

    /**
     * Get academic transcript for a mahasiswa
     */
    public function getTranscript(string $nim): array
    {
        $sql = "
            SELECT
                e.id_enrollment,
                k.semester,
                k.tahun_ajaran,
                k.kode_mk,
                mk.nama_mk,
                mk.sks,
                e.status,
                e.nilai_akhir,
                e.nilai_huruf
            FROM {$this->table} e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            WHERE e.nim = :nim
            ORDER BY k.tahun_ajaran ASC, k.semester ASC, mk.semester ASC, k.kode_mk ASC
        ";

        return $this->query($sql, ['nim' => $nim]);
    }

    /**
     * Get statistics for a kelas
     */
    public function getKelasStatistics(int $idKelas): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_mahasiswa,
                COUNT(CASE WHEN status = 'aktif' THEN 1 END) as mahasiswa_aktif,
                COUNT(CASE WHEN status = 'mengulang' THEN 1 END) as mahasiswa_mengulang,
                COUNT(CASE WHEN status = 'drop' THEN 1 END) as mahasiswa_drop,
                COUNT(CASE WHEN status = 'lulus' THEN 1 END) as mahasiswa_lulus,
                ROUND(AVG(CASE WHEN status = 'lulus' AND nilai_akhir IS NOT NULL THEN nilai_akhir END), 2) as rata_rata_nilai,
                COUNT(CASE WHEN nilai_huruf IN ('A', 'A-') THEN 1 END) as lulus_a,
                COUNT(CASE WHEN nilai_huruf IN ('AB', 'B+', 'B', 'B-') THEN 1 END) as lulus_b,
                COUNT(CASE WHEN nilai_huruf IN ('BC', 'C+', 'C') THEN 1 END) as lulus_c,
                COUNT(CASE WHEN nilai_huruf IN ('C-', 'D', 'E') THEN 1 END) as lulus_d_e
            FROM {$this->table}
            WHERE id_kelas = :id_kelas
        ";

        return $this->queryOne($sql, ['id_kelas' => $idKelas]) ?: [];
    }

    /**
     * Check if mahasiswa can drop enrollment
     */
    public function canDrop(int $idEnrollment): bool
    {
        $enrollment = $this->find($idEnrollment);
        if (!$enrollment) {
            return false;
        }

        // Can only drop if status is 'aktif' or 'mengulang'
        return in_array($enrollment['status'], ['aktif', 'mengulang']);
    }

    /**
     * Drop enrollment
     */
    public function drop(int $idEnrollment): bool
    {
        return $this->updateStatus($idEnrollment, 'drop');
    }

    /**
     * Get comprehensive mahasiswa performance data in a single optimized query
     * Optimized to reduce database round trips from 4 queries to 1
     */
    public function getMahasiswaPerformanceData(string $nim): array
    {
        $sql = "
            WITH enrollments_data AS (
                SELECT
                    e.*,
                    k.kode_mk,
                    k.nama_kelas,
                    k.semester as semester_kelas,
                    k.tahun_ajaran,
                    mk.nama_mk,
                    mk.sks
                FROM {$this->table} e
                JOIN kelas k ON e.id_kelas = k.id_kelas
                JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
                WHERE e.nim = :nim
            ),
            cpmk_data AS (
                SELECT
                    kc.id_enrollment,
                    json_agg(
                        json_build_object(
                            'id_ketercapaian', kc.id_ketercapaian,
                            'id_cpmk', kc.id_cpmk,
                            'kode_cpmk', c.kode_cpmk,
                            'deskripsi_cpmk', c.deskripsi,
                            'nilai_cpmk', kc.nilai_cpmk,
                            'status_tercapai', kc.status_tercapai,
                            'nama_kelas', k.nama_kelas,
                            'nama_mk', mk.nama_mk
                        ) ORDER BY k.semester, k.tahun_ajaran
                    ) as cpmk_achievements
                FROM ketercapaian_cpmk kc
                JOIN cpmk c ON kc.id_cpmk = c.id_cpmk
                JOIN enrollment e ON kc.id_enrollment = e.id_enrollment
                JOIN kelas k ON e.id_kelas = k.id_kelas
                JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
                WHERE e.nim = :nim
                GROUP BY kc.id_enrollment
            ),
            cpl_data AS (
                SELECT
                    kcpl.id_enrollment,
                    json_agg(
                        json_build_object(
                            'id_ketercapaian', kcpl.id_ketercapaian,
                            'id_cpl', kcpl.id_cpl,
                            'kode_cpl', cpl.kode_cpl,
                            'deskripsi_cpl', cpl.deskripsi,
                            'kategori', cpl.kategori,
                            'nilai_cpl', kcpl.nilai_cpl,
                            'status_tercapai', kcpl.status_tercapai
                        ) ORDER BY cpl.kategori, cpl.urutan
                    ) as cpl_achievements
                FROM ketercapaian_cpl kcpl
                JOIN cpl ON kcpl.id_cpl = cpl.id_cpl
                JOIN enrollment e ON kcpl.id_enrollment = e.id_enrollment
                WHERE e.nim = :nim
                GROUP BY kcpl.id_enrollment
            ),
            gpa_data AS (
                SELECT
                    AVG(
                        CASE nilai_huruf
                            WHEN 'A' THEN 4.0
                            WHEN 'A-' THEN 3.7
                            WHEN 'AB' THEN 3.5
                            WHEN 'B+' THEN 3.3
                            WHEN 'B' THEN 3.0
                            WHEN 'B-' THEN 2.7
                            WHEN 'BC' THEN 2.5
                            WHEN 'C+' THEN 2.3
                            WHEN 'C' THEN 2.0
                            WHEN 'C-' THEN 1.7
                            WHEN 'D' THEN 1.0
                            ELSE 0
                        END
                    ) as gpa
                FROM {$this->table}
                WHERE nim = :nim AND nilai_huruf IS NOT NULL
            )
            SELECT
                ed.*,
                COALESCE(cpmk.cpmk_achievements, '[]'::json) as cpmk_achievements,
                COALESCE(cpl.cpl_achievements, '[]'::json) as cpl_achievements,
                (SELECT ROUND(COALESCE(gpa, 0)::numeric, 2) FROM gpa_data) as gpa
            FROM enrollments_data ed
            LEFT JOIN cpmk_data cpmk ON ed.id_enrollment = cpmk.id_enrollment
            LEFT JOIN cpl_data cpl ON ed.id_enrollment = cpl.id_enrollment
            ORDER BY ed.tahun_ajaran DESC, ed.semester_kelas DESC
        ";

        return $this->query($sql, ['nim' => $nim]);
    }
}
