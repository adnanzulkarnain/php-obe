<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Kehadiran Repository
 * Handles attendance database operations
 */
class KehadiranRepository extends BaseRepository
{
    protected string $table = 'realisasi_pertemuan';
    protected string $primaryKey = 'id_realisasi';

    /**
     * Get all realisasi pertemuan by kelas
     */
    public function findRealisasiByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                rp.*,
                rm.minggu_ke,
                rm.materi as materi_rencana,
                d.nama as nama_dosen,
                COUNT(k.id_kehadiran) as total_kehadiran
            FROM realisasi_pertemuan rp
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            LEFT JOIN dosen d ON rp.created_by = d.id_dosen
            LEFT JOIN kehadiran k ON rp.id_realisasi = k.id_realisasi
            WHERE rp.id_kelas = :id_kelas
            GROUP BY rp.id_realisasi, rm.minggu_ke, rm.materi, d.nama
            ORDER BY rp.tanggal_pelaksanaan DESC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }

    /**
     * Find realisasi with details
     */
    public function findRealisasiWithDetails(int $idRealisasi): ?array
    {
        $sql = "
            SELECT
                rp.*,
                rm.minggu_ke,
                rm.materi as materi_rencana,
                rm.metode as metode_rencana,
                d.nama as nama_dosen
            FROM realisasi_pertemuan rp
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            LEFT JOIN dosen d ON rp.created_by = d.id_dosen
            WHERE rp.id_realisasi = :id_realisasi
        ";

        return $this->queryOne($sql, ['id_realisasi' => $idRealisasi]);
    }

    /**
     * Create realisasi pertemuan
     */
    public function createRealisasi(array $data): int
    {
        $sql = "
            INSERT INTO realisasi_pertemuan
            (id_kelas, id_minggu, tanggal_pelaksanaan, materi_disampaikan, metode_digunakan, kendala, catatan_dosen, created_by, created_at)
            VALUES
            (:id_kelas, :id_minggu, :tanggal_pelaksanaan, :materi_disampaikan, :metode_digunakan, :kendala, :catatan_dosen, :created_by, NOW())
            RETURNING id_realisasi
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_realisasi'];
    }

    /**
     * Update realisasi pertemuan
     */
    public function updateRealisasi(int $idRealisasi, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE realisasi_pertemuan SET " . implode(', ', $set) . " WHERE id_realisasi = :id_realisasi";
        $data['id_realisasi'] = $idRealisasi;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete realisasi pertemuan
     */
    public function deleteRealisasi(int $idRealisasi): bool
    {
        $sql = "DELETE FROM realisasi_pertemuan WHERE id_realisasi = :id_realisasi";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_realisasi' => $idRealisasi]);
    }

    // ========== KEHADIRAN METHODS ==========

    /**
     * Get kehadiran by realisasi
     */
    public function findKehadiranByRealisasi(int $idRealisasi): array
    {
        $sql = "
            SELECT
                k.*,
                m.nama as nama_mahasiswa,
                m.nim
            FROM kehadiran k
            JOIN mahasiswa m ON k.nim = m.nim
            WHERE k.id_realisasi = :id_realisasi
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_realisasi' => $idRealisasi]);
    }

    /**
     * Get kehadiran by mahasiswa
     */
    public function findKehadiranByMahasiswa(string $nim, int $idKelas): array
    {
        $sql = "
            SELECT
                k.*,
                rp.tanggal_pelaksanaan,
                rm.minggu_ke
            FROM kehadiran k
            JOIN realisasi_pertemuan rp ON k.id_realisasi = rp.id_realisasi
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE k.nim = :nim AND rp.id_kelas = :id_kelas
            ORDER BY rp.tanggal_pelaksanaan ASC
        ";

        return $this->query($sql, ['nim' => $nim, 'id_kelas' => $idKelas]);
    }

    /**
     * Upsert kehadiran
     */
    public function upsertKehadiran(array $data): int
    {
        $sql = "
            INSERT INTO kehadiran
            (id_realisasi, nim, status, keterangan, created_at)
            VALUES
            (:id_realisasi, :nim, :status, :keterangan, NOW())
            ON CONFLICT (id_realisasi, nim)
            DO UPDATE SET
                status = EXCLUDED.status,
                keterangan = EXCLUDED.keterangan
            RETURNING id_kehadiran
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_kehadiran'];
    }

    /**
     * Get attendance summary by kelas
     */
    public function getAttendanceSummary(int $idKelas): array
    {
        $sql = "
            SELECT
                m.nim,
                m.nama,
                COUNT(k.id_kehadiran) as total_pertemuan,
                COUNT(CASE WHEN k.status = 'hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN k.status = 'izin' THEN 1 END) as izin,
                COUNT(CASE WHEN k.status = 'sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN k.status = 'alpha' THEN 1 END) as alpha,
                ROUND(COUNT(CASE WHEN k.status = 'hadir' THEN 1 END)::NUMERIC / NULLIF(COUNT(k.id_kehadiran), 0) * 100, 2) as persentase_hadir
            FROM enrollment e
            JOIN mahasiswa m ON e.nim = m.nim
            LEFT JOIN kehadiran k ON m.nim = k.nim
            LEFT JOIN realisasi_pertemuan rp ON k.id_realisasi = rp.id_realisasi AND rp.id_kelas = :id_kelas
            WHERE e.id_kelas = :id_kelas
            GROUP BY m.nim, m.nama
            ORDER BY m.nama ASC
        ";

        return $this->query($sql, ['id_kelas' => $idKelas]);
    }
}
