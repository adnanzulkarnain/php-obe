<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * RencanaMingguan Repository
 * Handles weekly lecture plan database operations
 */
class RencanaMinggualRepository extends BaseRepository
{
    protected string $table = 'rencana_mingguan';
    protected string $primaryKey = 'id_minggu';

    /**
     * Find rencana by RPS ID
     */
    public function findByRPS(int $idRps): array
    {
        $sql = "
            SELECT
                rm.*,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk
            FROM {$this->table} rm
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            WHERE rm.id_rps = :id_rps
            ORDER BY rm.minggu_ke ASC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Find rencana by RPS ID and week number
     */
    public function findByRPSAndWeek(int $idRps, int $mingguKe): ?array
    {
        $sql = "
            SELECT
                rm.*,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk
            FROM {$this->table} rm
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            WHERE rm.id_rps = :id_rps AND rm.minggu_ke = :minggu_ke
        ";

        return $this->queryOne($sql, [
            'id_rps' => $idRps,
            'minggu_ke' => $mingguKe
        ]);
    }

    /**
     * Find rencana by kelas (through RPS)
     */
    public function findByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                rm.*,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk,
                rps.kode_mk,
                mk.nama_mk
            FROM {$this->table} rm
            JOIN rps ON rm.id_rps = rps.id_rps
            JOIN kelas k ON rps.id_rps = k.id_rps
            JOIN matakuliah mk ON rps.kode_mk = mk.kode_mk AND rps.id_kurikulum = mk.id_kurikulum
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            WHERE k.id_kelas = :id_kelas
            ORDER BY rm.minggu_ke ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Bulk insert rencana mingguan
     */
    public function bulkInsert(array $rencanaRecords): bool
    {
        if (empty($rencanaRecords)) {
            return true;
        }

        $values = [];
        $params = [];
        $i = 0;

        foreach ($rencanaRecords as $record) {
            $values[] = "(
                :id_rps_{$i},
                :minggu_ke_{$i},
                :id_subcpmk_{$i},
                :materi_{$i},
                :metode_{$i},
                :aktivitas_{$i},
                :media_software_{$i},
                :media_hardware_{$i},
                :pengalaman_belajar_{$i},
                :estimasi_waktu_menit_{$i}
            )";

            $params["id_rps_{$i}"] = $record['id_rps'];
            $params["minggu_ke_{$i}"] = $record['minggu_ke'];
            $params["id_subcpmk_{$i}"] = $record['id_subcpmk'] ?? null;
            $params["materi_{$i}"] = is_array($record['materi']) ? json_encode($record['materi']) : $record['materi'];
            $params["metode_{$i}"] = is_array($record['metode']) ? json_encode($record['metode']) : $record['metode'];
            $params["aktivitas_{$i}"] = is_array($record['aktivitas']) ? json_encode($record['aktivitas']) : $record['aktivitas'];
            $params["media_software_{$i}"] = $record['media_software'] ?? null;
            $params["media_hardware_{$i}"] = $record['media_hardware'] ?? null;
            $params["pengalaman_belajar_{$i}"] = $record['pengalaman_belajar'] ?? null;
            $params["estimasi_waktu_menit_{$i}"] = $record['estimasi_waktu_menit'] ?? 150;
            $i++;
        }

        $sql = "
            INSERT INTO {$this->table} (
                id_rps, minggu_ke, id_subcpmk, materi, metode, aktivitas,
                media_software, media_hardware, pengalaman_belajar, estimasi_waktu_menit
            )
            VALUES " . implode(', ', $values) . "
            ON CONFLICT (id_rps, minggu_ke)
            DO UPDATE SET
                id_subcpmk = EXCLUDED.id_subcpmk,
                materi = EXCLUDED.materi,
                metode = EXCLUDED.metode,
                aktivitas = EXCLUDED.aktivitas,
                media_software = EXCLUDED.media_software,
                media_hardware = EXCLUDED.media_hardware,
                pengalaman_belajar = EXCLUDED.pengalaman_belajar,
                estimasi_waktu_menit = EXCLUDED.estimasi_waktu_menit,
                updated_at = CURRENT_TIMESTAMP
        ";

        return $this->execute($sql, $params);
    }

    /**
     * Delete all rencana for an RPS
     */
    public function deleteByRPS(int $idRps): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_rps = :id_rps";
        return $this->execute($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get coverage statistics (how much of RPS is realized)
     */
    public function getCoverageByRPS(int $idRps): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_minggu,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM realisasi_pertemuan rp
                    WHERE rp.id_minggu = rm.id_minggu
                    AND rp.status = 'verified'
                ) THEN 1 END) as minggu_terlaksana,
                ROUND(
                    COUNT(CASE WHEN EXISTS (
                        SELECT 1 FROM realisasi_pertemuan rp
                        WHERE rp.id_minggu = rm.id_minggu
                        AND rp.status = 'verified'
                    ) THEN 1 END) * 100.0 / COUNT(*),
                    2
                ) as persentase_terlaksana
            FROM {$this->table} rm
            WHERE rm.id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]) ?? [];
    }
}
