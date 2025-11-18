<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * PrasyaratMK Repository
 * Handles database operations for course prerequisites
 */
class PrasyaratMKRepository extends BaseRepository
{
    protected string $table = 'prasyarat_mk';
    protected string $primaryKey = 'id_prasyarat';

    /**
     * Get all prerequisites for a course
     */
    public function findByMataKuliah(string $kodeMk, int $idKurikulum): array
    {
        $sql = "
            SELECT
                p.*,
                m.nama_mk as nama_mk_prasyarat,
                m.sks as sks_prasyarat
            FROM {$this->table} p
            JOIN matakuliah m ON p.kode_mk_prasyarat = m.kode_mk
                AND p.id_kurikulum = m.id_kurikulum
            WHERE p.kode_mk = :kode_mk AND p.id_kurikulum = :id_kurikulum
            ORDER BY p.jenis_prasyarat DESC, m.nama_mk ASC
        ";

        return $this->query($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Get all courses that have a specific course as prerequisite
     */
    public function findCoursesRequiring(string $kodeMk, int $idKurikulum): array
    {
        $sql = "
            SELECT
                p.*,
                m.nama_mk as nama_mk,
                m.sks
            FROM {$this->table} p
            JOIN matakuliah m ON p.kode_mk = m.kode_mk
                AND p.id_kurikulum = m.id_kurikulum
            WHERE p.kode_mk_prasyarat = :kode_mk AND p.id_kurikulum = :id_kurikulum
            ORDER BY m.nama_mk ASC
        ";

        return $this->query($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Check if prerequisite already exists
     */
    public function exists(string $kodeMk, int $idKurikulum, string $kodeMkPrasyarat): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE kode_mk = :kode_mk
                AND id_kurikulum = :id_kurikulum
                AND kode_mk_prasyarat = :kode_mk_prasyarat
        ";

        $result = $this->queryOne($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum,
            'kode_mk_prasyarat' => $kodeMkPrasyarat
        ]);

        return $result && $result['count'] > 0;
    }

    /**
     * Delete prerequisite by mata kuliah and prasyarat
     */
    public function deleteByMataKuliahAndPrasyarat(
        string $kodeMk,
        int $idKurikulum,
        string $kodeMkPrasyarat
    ): bool {
        $sql = "
            DELETE FROM {$this->table}
            WHERE kode_mk = :kode_mk
                AND id_kurikulum = :id_kurikulum
                AND kode_mk_prasyarat = :kode_mk_prasyarat
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum,
            'kode_mk_prasyarat' => $kodeMkPrasyarat
        ]);
    }

    /**
     * Check if student has completed prerequisite
     * Returns true if prerequisite is fulfilled
     */
    public function checkPrerequisiteFulfilled(
        string $nim,
        string $kodeMk,
        int $idKurikulum
    ): array {
        $sql = "
            SELECT
                p.id_prasyarat,
                p.kode_mk_prasyarat,
                p.jenis_prasyarat,
                m.nama_mk as nama_mk_prasyarat,
                CASE
                    WHEN e.status = 'lulus' AND e.nilai_huruf NOT IN ('E', 'D')
                    THEN true
                    ELSE false
                END as is_fulfilled,
                e.nilai_huruf,
                e.status as status_enrollment
            FROM {$this->table} p
            JOIN matakuliah m ON p.kode_mk_prasyarat = m.kode_mk
                AND p.id_kurikulum = m.id_kurikulum
            LEFT JOIN enrollment e ON e.nim = :nim
            LEFT JOIN kelas k ON e.id_kelas = k.id_kelas
                AND k.kode_mk = p.kode_mk_prasyarat
                AND k.id_kurikulum = p.id_kurikulum
            WHERE p.kode_mk = :kode_mk
                AND p.id_kurikulum = :id_kurikulum
            ORDER BY p.jenis_prasyarat DESC, m.nama_mk ASC
        ";

        return $this->query($sql, [
            'nim' => $nim,
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Check for circular prerequisites
     * Returns true if circular dependency exists
     */
    public function hasCircularDependency(
        string $kodeMk,
        int $idKurikulum,
        string $kodeMkPrasyarat,
        int $depth = 0
    ): bool {
        // Prevent infinite recursion
        if ($depth > 10) {
            return true;
        }

        // Check if prasyarat has kodeMk as its prerequisite
        $sql = "
            SELECT kode_mk_prasyarat
            FROM {$this->table}
            WHERE kode_mk = :kode_mk AND id_kurikulum = :id_kurikulum
        ";

        $prerequisites = $this->query($sql, [
            'kode_mk' => $kodeMkPrasyarat,
            'id_kurikulum' => $idKurikulum
        ]);

        foreach ($prerequisites as $prereq) {
            if ($prereq['kode_mk_prasyarat'] === $kodeMk) {
                return true; // Circular dependency found
            }

            // Check recursively
            if ($this->hasCircularDependency(
                $kodeMk,
                $idKurikulum,
                $prereq['kode_mk_prasyarat'],
                $depth + 1
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get prerequisite tree for a course (recursive)
     */
    public function getPrerequisiteTree(string $kodeMk, int $idKurikulum): array
    {
        $sql = "
            WITH RECURSIVE prereq_tree AS (
                -- Base case: direct prerequisites
                SELECT
                    p.kode_mk,
                    p.kode_mk_prasyarat,
                    p.jenis_prasyarat,
                    m.nama_mk as nama_mk_prasyarat,
                    m.sks,
                    1 as level
                FROM {$this->table} p
                JOIN matakuliah m ON p.kode_mk_prasyarat = m.kode_mk
                    AND p.id_kurikulum = m.id_kurikulum
                WHERE p.kode_mk = :kode_mk AND p.id_kurikulum = :id_kurikulum

                UNION ALL

                -- Recursive case: prerequisites of prerequisites
                SELECT
                    p.kode_mk,
                    p.kode_mk_prasyarat,
                    p.jenis_prasyarat,
                    m.nama_mk as nama_mk_prasyarat,
                    m.sks,
                    pt.level + 1
                FROM {$this->table} p
                JOIN prereq_tree pt ON p.kode_mk = pt.kode_mk_prasyarat
                JOIN matakuliah m ON p.kode_mk_prasyarat = m.kode_mk
                    AND p.id_kurikulum = m.id_kurikulum
                WHERE p.id_kurikulum = :id_kurikulum AND pt.level < 5
            )
            SELECT * FROM prereq_tree
            ORDER BY level, nama_mk_prasyarat
        ";

        return $this->query($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Get statistics for prerequisites
     */
    public function getStatistics(int $idKurikulum): array
    {
        $sql = "
            SELECT
                COUNT(DISTINCT kode_mk) as total_mk_with_prereq,
                COUNT(*) as total_prereq_relations,
                SUM(CASE WHEN jenis_prasyarat = 'wajib' THEN 1 ELSE 0 END) as total_wajib,
                SUM(CASE WHEN jenis_prasyarat = 'alternatif' THEN 1 ELSE 0 END) as total_alternatif
            FROM {$this->table}
            WHERE id_kurikulum = :id_kurikulum
        ";

        return $this->queryOne($sql, ['id_kurikulum' => $idKurikulum]) ?: [];
    }
}
