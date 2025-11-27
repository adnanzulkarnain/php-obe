<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Kehadiran Repository
 * Handles student attendance database operations
 */
class KehadiranRepository extends BaseRepository
{
    protected string $table = 'kehadiran';
    protected string $primaryKey = 'id_kehadiran';

    /**
     * Find attendance by realisasi ID
     */
    public function findByRealisasi(int $idRealisasi): array
    {
        $sql = "
            SELECT
                k.*,
                m.nama as nama_mahasiswa,
                m.nim
            FROM {$this->table} k
            JOIN mahasiswa m ON k.nim = m.nim
            WHERE k.id_realisasi = :id_realisasi
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_realisasi' => $idRealisasi]);
    }

    /**
     * Find attendance by student (NIM)
     */
    public function findByMahasiswa(string $nim, ?array $filters = []): array
    {
        $sql = "
            SELECT
                k.*,
                rp.tanggal_pelaksanaan,
                mk.nama_mk,
                kls.nama_kelas
            FROM {$this->table} k
            JOIN realisasi_pertemuan rp ON k.id_realisasi = rp.id_realisasi
            JOIN kelas kls ON rp.id_kelas = kls.id_kelas
            JOIN matakuliah mk ON kls.kode_mk = mk.kode_mk AND kls.id_kurikulum = mk.id_kurikulum
            WHERE k.nim = :nim
        ";

        $params = ['nim' => $nim];

        // Add optional filters
        if (isset($filters['id_kelas'])) {
            $sql .= " AND rp.id_kelas = :id_kelas";
            $params['id_kelas'] = $filters['id_kelas'];
        }

        $sql .= " ORDER BY rp.tanggal_pelaksanaan DESC";

        return $this->query($sql, $params);
    }

    /**
     * Bulk insert attendance records
     */
    public function bulkInsert(array $attendanceRecords): bool
    {
        if (empty($attendanceRecords)) {
            return true;
        }

        $values = [];
        $params = [];
        $i = 0;

        foreach ($attendanceRecords as $record) {
            $values[] = "(:id_realisasi_{$i}, :nim_{$i}, :status_{$i}, :keterangan_{$i})";
            $params["id_realisasi_{$i}"] = $record['id_realisasi'];
            $params["nim_{$i}"] = $record['nim'];
            $params["status_{$i}"] = $record['status'];
            $params["keterangan_{$i}"] = $record['keterangan'] ?? null;
            $i++;
        }

        $sql = "
            INSERT INTO {$this->table} (id_realisasi, nim, status, keterangan)
            VALUES " . implode(', ', $values) . "
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                keterangan = VALUES(keterangan)
        ";

        return $this->execute($sql, $params);
    }

    /**
     * Get attendance statistics for a class
     */
    public function getStatisticsByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                k.nim,
                m.nama as nama_mahasiswa,
                COUNT(*) as total_pertemuan,
                COUNT(CASE WHEN k.status = 'hadir' THEN 1 END) as hadir_count,
                COUNT(CASE WHEN k.status = 'izin' THEN 1 END) as izin_count,
                COUNT(CASE WHEN k.status = 'sakit' THEN 1 END) as sakit_count,
                COUNT(CASE WHEN k.status = 'alpha' THEN 1 END) as alpha_count,
                ROUND(
                    COUNT(CASE WHEN k.status = 'hadir' THEN 1 END) * 100.0 / COUNT(*),
                    2
                ) as persentase_kehadiran
            FROM {$this->table} k
            JOIN realisasi_pertemuan rp ON k.id_realisasi = rp.id_realisasi
            JOIN mahasiswa m ON k.nim = m.nim
            WHERE rp.id_kelas = :id_kelas
            GROUP BY k.nim, m.nama
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Get attendance summary for a realisasi
     */
    public function getSummaryByRealisasi(int $idRealisasi): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_mahasiswa,
                COUNT(CASE WHEN status = 'hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN status = 'alpha' THEN 1 END) as alpha,
                ROUND(
                    COUNT(CASE WHEN status = 'hadir' THEN 1 END) * 100.0 / COUNT(*),
                    2
                ) as persentase_kehadiran
            FROM {$this->table}
            WHERE id_realisasi = :id_realisasi
        ";

        return $this->queryOne($sql, ['id_realisasi' => $idRealisasi]) ?? [];
    }

    /**
     * Delete all attendance for a realisasi
     */
    public function deleteByRealisasi(int $idRealisasi): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_realisasi = :id_realisasi";
        return $this->execute($sql, ['id_realisasi' => $idRealisasi]);
    }
}
