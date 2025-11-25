<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * RealisasiPertemuan Repository
 * Handles lecture report database operations
 */
class RealisasiPertemuanRepository extends BaseRepository
{
    protected string $table = 'realisasi_pertemuan';
    protected string $primaryKey = 'id_realisasi';

    /**
     * Find realisasi by ID with full details
     */
    public function findByIdWithDetails(int $idRealisasi): ?array
    {
        $sql = "
            SELECT
                rp.*,
                k.nama_kelas,
                k.kode_mk,
                k.hari,
                k.jam_mulai,
                k.jam_selesai,
                mk.nama_mk,
                mk.sks,
                d.nama as nama_dosen,
                dv.nama as nama_verifier,
                rm.minggu_ke,
                rm.materi as rencana_materi,
                rm.metode as rencana_metode,
                rm.aktivitas as rencana_aktivitas
            FROM {$this->table} rp
            JOIN kelas k ON rp.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON rp.created_by = d.id_dosen
            LEFT JOIN dosen dv ON rp.verified_by = dv.id_dosen
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE rp.id_realisasi = :id_realisasi
        ";

        return $this->queryOne($sql, ['id_realisasi' => $idRealisasi]);
    }

    /**
     * Find all realisasi by kelas with filters
     */
    public function findByKelas(int $idKelas, ?array $filters = []): array
    {
        $sql = "
            SELECT
                rp.*,
                k.nama_kelas,
                k.kode_mk,
                mk.nama_mk,
                d.nama as nama_dosen,
                rm.minggu_ke
            FROM {$this->table} rp
            JOIN kelas k ON rp.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON rp.created_by = d.id_dosen
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE rp.id_kelas = :id_kelas
        ";

        $params = ['id_kelas' => $idKelas];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND rp.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['minggu_ke'])) {
            $sql .= " AND rm.minggu_ke = :minggu_ke";
            $params['minggu_ke'] = $filters['minggu_ke'];
        }

        if (isset($filters['tanggal_dari'])) {
            $sql .= " AND rp.tanggal_pelaksanaan >= :tanggal_dari";
            $params['tanggal_dari'] = $filters['tanggal_dari'];
        }

        if (isset($filters['tanggal_sampai'])) {
            $sql .= " AND rp.tanggal_pelaksanaan <= :tanggal_sampai";
            $params['tanggal_sampai'] = $filters['tanggal_sampai'];
        }

        $sql .= " ORDER BY rp.tanggal_pelaksanaan DESC, rm.minggu_ke ASC";

        return $this->query($sql, $params);
    }

    /**
     * Find all realisasi by dosen (created_by)
     */
    public function findByDosen(string $idDosen, ?array $filters = []): array
    {
        $sql = "
            SELECT
                rp.*,
                k.nama_kelas,
                k.kode_mk,
                k.hari,
                k.jam_mulai,
                k.jam_selesai,
                mk.nama_mk,
                rm.minggu_ke
            FROM {$this->table} rp
            JOIN kelas k ON rp.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE rp.created_by = :id_dosen
        ";

        $params = ['id_dosen' => $idDosen];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND rp.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['id_kelas'])) {
            $sql .= " AND rp.id_kelas = :id_kelas";
            $params['id_kelas'] = $filters['id_kelas'];
        }

        if (isset($filters['tanggal_dari'])) {
            $sql .= " AND rp.tanggal_pelaksanaan >= :tanggal_dari";
            $params['tanggal_dari'] = $filters['tanggal_dari'];
        }

        if (isset($filters['tanggal_sampai'])) {
            $sql .= " AND rp.tanggal_pelaksanaan <= :tanggal_sampai";
            $params['tanggal_sampai'] = $filters['tanggal_sampai'];
        }

        $sql .= " ORDER BY rp.tanggal_pelaksanaan DESC";

        return $this->query($sql, $params);
    }

    /**
     * Get pending verifications for kaprodi
     * Kaprodi can verify reports from their program study
     */
    public function findPendingVerification(?string $kaprodiId = null): array
    {
        $sql = "
            SELECT
                rp.*,
                k.nama_kelas,
                k.kode_mk,
                mk.nama_mk,
                mk.id_prodi,
                d.nama as nama_dosen,
                rm.minggu_ke
            FROM {$this->table} rp
            JOIN kelas k ON rp.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON rp.created_by = d.id_dosen
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE rp.status = 'submitted'
        ";

        $params = [];

        // Filter by kaprodi's prodi if specified
        if ($kaprodiId !== null) {
            $sql .= " AND mk.id_prodi IN (
                SELECT id_prodi FROM dosen WHERE id_dosen = :kaprodi_id
            )";
            $params['kaprodi_id'] = $kaprodiId;
        }

        $sql .= " ORDER BY rp.created_at ASC"; // FIFO for verification queue

        return $this->query($sql, $params);
    }

    /**
     * Compare realisasi with rencana (RPS plan)
     */
    public function compareWithRencana(int $idRealisasi): ?array
    {
        $sql = "
            SELECT
                rp.id_realisasi,
                rp.tanggal_pelaksanaan,
                rp.materi_disampaikan,
                rp.metode_digunakan,
                rm.minggu_ke,
                rm.materi as rencana_materi,
                rm.metode as rencana_metode,
                rm.aktivitas as rencana_aktivitas,
                rm.media_software as rencana_media_software,
                rm.media_hardware as rencana_media_hardware,
                rm.estimasi_waktu_menit as rencana_waktu,
                k.nama_kelas,
                mk.nama_mk,
                sc.kode_subcpmk,
                sc.deskripsi as deskripsi_subcpmk
            FROM {$this->table} rp
            LEFT JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            JOIN kelas k ON rp.id_kelas = k.id_kelas
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN subcpmk sc ON rm.id_subcpmk = sc.id_subcpmk
            WHERE rp.id_realisasi = :id_realisasi
        ";

        return $this->queryOne($sql, ['id_realisasi' => $idRealisasi]);
    }

    /**
     * Get statistics for kelas
     */
    public function getStatisticsByKelas(int $idKelas): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_pertemuan,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_count,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                MIN(tanggal_pelaksanaan) as first_meeting,
                MAX(tanggal_pelaksanaan) as last_meeting
            FROM {$this->table}
            WHERE id_kelas = :id_kelas
        ";

        return $this->queryOne($sql, ['id_kelas' => $idKelas]) ?? [];
    }

    /**
     * Get statistics for dosen
     */
    public function getStatisticsByDosen(string $idDosen): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_pertemuan,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_count,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(DISTINCT id_kelas) as total_kelas
            FROM {$this->table}
            WHERE created_by = :id_dosen
        ";

        return $this->queryOne($sql, ['id_dosen' => $idDosen]) ?? [];
    }

    /**
     * Check if realisasi exists for specific kelas and week
     */
    public function existsByKelasAndWeek(int $idKelas, int $mingguKe): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table} rp
            JOIN rencana_mingguan rm ON rp.id_minggu = rm.id_minggu
            WHERE rp.id_kelas = :id_kelas AND rm.minggu_ke = :minggu_ke
        ";

        $result = $this->queryOne($sql, [
            'id_kelas' => $idKelas,
            'minggu_ke' => $mingguKe
        ]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Update verification status
     */
    public function updateVerificationStatus(
        int $idRealisasi,
        string $status,
        string $verifiedBy,
        ?string $komentar = null
    ): bool {
        $sql = "
            UPDATE {$this->table}
            SET
                status = :status,
                verified_by = :verified_by,
                verified_at = CURRENT_TIMESTAMP,
                komentar_kaprodi = :komentar,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_realisasi = :id_realisasi
        ";

        return $this->execute($sql, [
            'id_realisasi' => $idRealisasi,
            'status' => $status,
            'verified_by' => $verifiedBy,
            'komentar' => $komentar
        ]);
    }

    /**
     * Submit realisasi for verification
     */
    public function submitForVerification(int $idRealisasi): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET
                status = 'submitted',
                updated_at = CURRENT_TIMESTAMP
            WHERE id_realisasi = :id_realisasi
              AND status IN ('draft', 'rejected')
        ";

        return $this->execute($sql, ['id_realisasi' => $idRealisasi]);
    }
}
