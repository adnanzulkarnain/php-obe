<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Ambang Batas Repository
 * Data access layer for threshold/passing grade management
 */
class AmbangBatasRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->setTable('ambang_batas');
        $this->setPrimaryKey('id_ambang_batas');
    }

    /**
     * Get ambang batas by RPS
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT ab.*, jn.nama_jenis, jn.kategori
            FROM ambang_batas ab
            LEFT JOIN jenis_penilaian jn ON ab.id_jenis = jn.id_jenis
            WHERE ab.id_rps = :id_rps
            ORDER BY jn.kategori, jn.urutan
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get ambang batas by enrollment
     */
    public function findByEnrollment(int $idEnrollment): array
    {
        $sql = "
            SELECT ab.*, jn.nama_jenis, jn.kategori
            FROM ambang_batas ab
            LEFT JOIN jenis_penilaian jn ON ab.id_jenis = jn.id_jenis
            JOIN enrollment e ON ab.id_rps = e.id_kelas
            WHERE e.id_enrollment = :id_enrollment
            ORDER BY jn.kategori, jn.urutan
        ";

        return $this->query($sql, ['id_enrollment' => $idEnrollment]);
    }

    /**
     * Create ambang batas
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO ambang_batas
            (id_rps, id_jenis, nilai_minimal, keterangan, created_at, updated_at)
            VALUES
            (:id_rps, :id_jenis, :nilai_minimal, :keterangan, NOW(), NOW())
            RETURNING id_ambang_batas
        ";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            'id_rps' => $data['id_rps'],
            'id_jenis' => $data['id_jenis'],
            'nilai_minimal' => $data['nilai_minimal'],
            'keterangan' => $data['keterangan'] ?? null
        ]);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_ambang_batas'];
    }

    /**
     * Update ambang batas
     */
    public function updateThreshold(int $idAmbangBatas, array $data): bool
    {
        $sql = "
            UPDATE ambang_batas
            SET nilai_minimal = :nilai_minimal,
                keterangan = :keterangan,
                updated_at = NOW()
            WHERE id_ambang_batas = :id_ambang_batas
        ";

        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            'id_ambang_batas' => $idAmbangBatas,
            'nilai_minimal' => $data['nilai_minimal'],
            'keterangan' => $data['keterangan'] ?? null
        ]);
    }

    /**
     * Delete ambang batas
     */
    public function deleteThreshold(int $idAmbangBatas): bool
    {
        $sql = "DELETE FROM ambang_batas WHERE id_ambang_batas = :id_ambang_batas";

        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute(['id_ambang_batas' => $idAmbangBatas]);
    }

    /**
     * Check if jenis already has threshold for RPS
     */
    public function thresholdExists(int $idRps, int $idJenis, ?int $excludeId = null): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM ambang_batas
            WHERE id_rps = :id_rps AND id_jenis = :id_jenis
        ";

        $params = [
            'id_rps' => $idRps,
            'id_jenis' => $idJenis
        ];

        if ($excludeId !== null) {
            $sql .= " AND id_ambang_batas != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $result = $this->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Bulk create ambang batas
     */
    public function bulkCreate(int $idRps, array $thresholds): array
    {
        $results = ['created' => [], 'failed' => []];

        foreach ($thresholds as $threshold) {
            try {
                // Check if already exists
                if ($this->thresholdExists($idRps, $threshold['id_jenis'])) {
                    $results['failed'][] = [
                        'id_jenis' => $threshold['id_jenis'],
                        'error' => 'Threshold already exists for this jenis penilaian'
                    ];
                    continue;
                }

                $data = [
                    'id_rps' => $idRps,
                    'id_jenis' => $threshold['id_jenis'],
                    'nilai_minimal' => $threshold['nilai_minimal'],
                    'keterangan' => $threshold['keterangan'] ?? null
                ];

                $idAmbangBatas = $this->create($data);
                $results['created'][] = $idAmbangBatas;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id_jenis' => $threshold['id_jenis'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get threshold for specific jenis penilaian in RPS
     */
    public function getThresholdByJenis(int $idRps, int $idJenis): ?array
    {
        $sql = "
            SELECT ab.*, jn.nama_jenis, jn.kategori
            FROM ambang_batas ab
            LEFT JOIN jenis_penilaian jn ON ab.id_jenis = jn.id_jenis
            WHERE ab.id_rps = :id_rps AND ab.id_jenis = :id_jenis
        ";

        return $this->queryOne($sql, [
            'id_rps' => $idRps,
            'id_jenis' => $idJenis
        ]);
    }
}
