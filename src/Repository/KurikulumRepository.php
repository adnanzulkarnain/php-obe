<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Kurikulum Repository
 */
class KurikulumRepository extends BaseRepository
{
    protected string $table = 'kurikulum';
    protected string $primaryKey = 'id_kurikulum';

    /**
     * Find kurikulum by kode and prodi
     */
    public function findByKode(string $kode, string $idProdi): ?array
    {
        return $this->findOne([
            'kode_kurikulum' => $kode,
            'id_prodi' => $idProdi
        ]);
    }

    /**
     * Get all kurikulum for a prodi
     */
    public function findByProdi(string $idProdi, ?string $status = null): array
    {
        $conditions = ['id_prodi' => $idProdi];

        if ($status) {
            $conditions['status'] = $status;
        }

        return $this->findAll($conditions, ['tahun_berlaku' => 'DESC']);
    }

    /**
     * Get active kurikulum for a prodi
     */
    public function findActiveByProdi(string $idProdi): array
    {
        return $this->findByProdi($idProdi, 'aktif');
    }

    /**
     * Get primary kurikulum for a prodi
     */
    public function findPrimaryByProdi(string $idProdi): ?array
    {
        return $this->findOne([
            'id_prodi' => $idProdi,
            'is_primary' => true
        ]);
    }

    /**
     * Get kurikulum with statistics
     */
    public function findWithStatistics(int $idKurikulum): ?array
    {
        $sql = "
            SELECT
                k.*,
                COUNT(DISTINCT cpl.id_cpl) as total_cpl,
                COUNT(DISTINCT mk.kode_mk) as total_mk,
                COALESCE(SUM(mk.sks), 0) as total_sks,
                COUNT(DISTINCT m.nim) as total_mahasiswa,
                COUNT(DISTINCT CASE WHEN m.status = 'aktif' THEN m.nim END) as mahasiswa_aktif
            FROM kurikulum k
            LEFT JOIN cpl ON cpl.id_kurikulum = k.id_kurikulum AND cpl.is_active = true
            LEFT JOIN matakuliah mk ON mk.id_kurikulum = k.id_kurikulum AND mk.is_active = true
            LEFT JOIN mahasiswa m ON m.id_kurikulum = k.id_kurikulum
            WHERE k.id_kurikulum = :id_kurikulum
            GROUP BY k.id_kurikulum
        ";

        return $this->queryOne($sql, ['id_kurikulum' => $idKurikulum]);
    }

    /**
     * Remove primary flag from other kurikulum in the same prodi
     */
    public function removePrimaryFlag(string $idProdi): bool
    {
        $sql = "UPDATE {$this->table} SET is_primary = false WHERE id_prodi = :id_prodi";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id_prodi' => $idProdi]);
    }

    /**
     * Get kurikulum comparison data
     */
    public function getComparisonData(array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT
                k.*,
                json_agg(DISTINCT
                    json_build_object(
                        'id_cpl', cpl.id_cpl,
                        'kode_cpl', cpl.kode_cpl,
                        'kategori', cpl.kategori
                    )
                ) FILTER (WHERE cpl.id_cpl IS NOT NULL) as cpl_list,
                json_agg(DISTINCT
                    json_build_object(
                        'kode_mk', mk.kode_mk,
                        'nama_mk', mk.nama_mk,
                        'sks', mk.sks,
                        'semester', mk.semester
                    )
                ) FILTER (WHERE mk.kode_mk IS NOT NULL) as mk_list
            FROM kurikulum k
            LEFT JOIN cpl ON cpl.id_kurikulum = k.id_kurikulum AND cpl.is_active = true
            LEFT JOIN matakuliah mk ON mk.id_kurikulum = k.id_kurikulum AND mk.is_active = true
            WHERE k.id_kurikulum IN ($placeholders)
            GROUP BY k.id_kurikulum
            ORDER BY k.tahun_berlaku
        ";

        return $this->query($sql, $ids);
    }

    /**
     * Check if kurikulum can be deleted
     */
    public function canDelete(int $idKurikulum): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM mahasiswa WHERE id_kurikulum = :id) as total_mahasiswa,
                (SELECT COUNT(*) FROM cpl WHERE id_kurikulum = :id) as total_cpl,
                (SELECT COUNT(*) FROM matakuliah WHERE id_kurikulum = :id) as total_mk
        ";

        return $this->queryOne($sql, ['id' => $idKurikulum]);
    }
}
