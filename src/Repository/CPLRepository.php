<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * CPL Repository
 */
class CPLRepository extends BaseRepository
{
    protected string $table = 'cpl';
    protected string $primaryKey = 'id_cpl';

    /**
     * Find CPL by kurikulum
     */
    public function findByKurikulum(int $idKurikulum, bool $activeOnly = true): array
    {
        $conditions = ['id_kurikulum' => $idKurikulum];

        if ($activeOnly) {
            $conditions['is_active'] = true;
        }

        return $this->findAll($conditions, ['urutan' => 'ASC', 'id_cpl' => 'ASC']);
    }

    /**
     * Find CPL by kode and kurikulum
     */
    public function findByKode(string $kodeCpl, int $idKurikulum): ?array
    {
        return $this->findOne([
            'kode_cpl' => $kodeCpl,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Get CPL grouped by kategori
     */
    public function findGroupedByKategori(int $idKurikulum): array
    {
        $sql = "
            SELECT
                kategori,
                json_agg(
                    json_build_object(
                        'id_cpl', id_cpl,
                        'kode_cpl', kode_cpl,
                        'deskripsi', deskripsi,
                        'urutan', urutan
                    ) ORDER BY urutan, id_cpl
                ) as cpl_list
            FROM {$this->table}
            WHERE id_kurikulum = :id_kurikulum AND is_active = true
            GROUP BY kategori
            ORDER BY
                CASE kategori
                    WHEN 'sikap' THEN 1
                    WHEN 'pengetahuan' THEN 2
                    WHEN 'keterampilan_umum' THEN 3
                    WHEN 'keterampilan_khusus' THEN 4
                END
        ";

        return $this->query($sql, ['id_kurikulum' => $idKurikulum]);
    }

    /**
     * Check if CPL is mapped to any CPMK
     */
    public function isMappedToCPMK(int $idCpl): bool
    {
        $sql = "SELECT COUNT(*) as count FROM relasi_cpmk_cpl WHERE id_cpl = :id_cpl";
        $result = $this->queryOne($sql, ['id_cpl' => $idCpl]);

        return $result['count'] > 0;
    }
}
