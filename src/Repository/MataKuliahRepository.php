<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Mata Kuliah Repository
 */
class MataKuliahRepository extends BaseRepository
{
    protected string $table = 'matakuliah';
    protected string $primaryKey = 'kode_mk, id_kurikulum'; // Composite key

    /**
     * Find MK by composite key
     */
    public function findByKodeAndKurikulum(string $kodeMk, int $idKurikulum): ?array
    {
        return $this->findOne([
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Find all MK by kurikulum
     */
    public function findByKurikulum(int $idKurikulum, bool $activeOnly = true): array
    {
        $conditions = ['id_kurikulum' => $idKurikulum];

        if ($activeOnly) {
            $conditions['is_active'] = true;
        }

        return $this->findAll($conditions, ['semester' => 'ASC', 'kode_mk' => 'ASC']);
    }

    /**
     * Get MK grouped by semester
     */
    public function findGroupedBySemester(int $idKurikulum): array
    {
        $sql = "
            SELECT
                semester,
                json_agg(
                    json_build_object(
                        'kode_mk', kode_mk,
                        'nama_mk', nama_mk,
                        'sks', sks,
                        'jenis_mk', jenis_mk
                    ) ORDER BY kode_mk
                ) as mk_list,
                SUM(sks) as total_sks
            FROM {$this->table}
            WHERE id_kurikulum = :id_kurikulum AND is_active = true
            GROUP BY semester
            ORDER BY semester
        ";

        return $this->query($sql, ['id_kurikulum' => $idKurikulum]);
    }

    /**
     * Create MK with composite key
     */
    public function createMK(array $data): bool
    {
        $sql = "
            INSERT INTO {$this->table}
            (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active, created_at, updated_at)
            VALUES
            (:kode_mk, :id_kurikulum, :nama_mk, :nama_mk_eng, :sks, :semester, :rumpun, :jenis_mk, :is_active, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Update MK with composite key
     */
    public function updateMK(string $kodeMk, int $idKurikulum, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE kode_mk = :kode_mk AND id_kurikulum = :id_kurikulum";

        $data['kode_mk'] = $kodeMk;
        $data['id_kurikulum'] = $idKurikulum;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Soft delete MK
     */
    public function softDeleteMK(string $kodeMk, int $idKurikulum): bool
    {
        return $this->updateMK($kodeMk, $idKurikulum, [
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if MK has RPS
     */
    public function hasRPS(string $kodeMk, int $idKurikulum): bool
    {
        $sql = "SELECT COUNT(*) as count FROM rps WHERE kode_mk = :kode_mk AND id_kurikulum = :id_kurikulum";
        $result = $this->queryOne($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);

        return $result['count'] > 0;
    }
}
