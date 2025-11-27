<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * RPS Repository
 */
class RPSRepository extends BaseRepository
{
    protected string $table = 'rps';
    protected string $primaryKey = 'id_rps';

    /**
     * Find RPS by ID with details
     */
    public function findByIdWithDetails(int $idRps): ?array
    {
        $sql = "
            SELECT
                r.*,
                mk.nama_mk,
                mk.sks,
                mk.jenis_mk,
                d.nama as nama_ketua
            FROM {$this->table} r
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON r.ketua_pengembang = d.id_dosen
            WHERE r.id_rps = :id_rps
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]);
    }

    /**
     * Find RPS by mata kuliah
     */
    public function findByMataKuliah(string $kodeMk, int $idKurikulum, ?array $filters = []): array
    {
        $sql = "
            SELECT
                r.*,
                mk.nama_mk,
                mk.sks,
                d.nama as nama_ketua
            FROM {$this->table} r
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON r.ketua_pengembang = d.id_dosen
            WHERE r.kode_mk = :kode_mk AND r.id_kurikulum = :id_kurikulum
        ";

        $params = [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ];

        // Add optional filters
        if (isset($filters['semester_berlaku'])) {
            $sql .= " AND r.semester_berlaku = :semester_berlaku";
            $params['semester_berlaku'] = $filters['semester_berlaku'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND r.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY r.tahun_ajaran DESC, r.semester_berlaku DESC, r.created_at DESC";

        return $this->query($sql, $params);
    }

    /**
     * Find RPS by kurikulum
     */
    public function findByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        $sql = "
            SELECT
                r.*,
                mk.nama_mk,
                mk.sks,
                mk.semester as semester_mk,
                d.nama as nama_ketua
            FROM {$this->table} r
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            LEFT JOIN dosen d ON r.ketua_pengembang = d.id_dosen
            WHERE r.id_kurikulum = :id_kurikulum
        ";

        $params = ['id_kurikulum' => $idKurikulum];

        // Add optional filters
        if (isset($filters['semester_berlaku'])) {
            $sql .= " AND r.semester_berlaku = :semester_berlaku";
            $params['semester_berlaku'] = $filters['semester_berlaku'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND r.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY mk.semester ASC, r.kode_mk ASC, r.tahun_ajaran DESC";

        return $this->query($sql, $params);
    }

    /**
     * Find RPS by dosen (ketua pengembang)
     */
    public function findByDosen(string $idDosen, ?array $filters = []): array
    {
        $sql = "
            SELECT
                r.*,
                mk.nama_mk,
                mk.sks
            FROM {$this->table} r
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            WHERE r.ketua_pengembang = :id_dosen
        ";

        $params = ['id_dosen' => $idDosen];

        // Add optional filters
        if (isset($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['tahun_ajaran'])) {
            $sql .= " AND r.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $filters['tahun_ajaran'];
        }

        $sql .= " ORDER BY r.tahun_ajaran DESC, r.semester_berlaku DESC";

        return $this->query($sql, $params);
    }

    /**
     * Check if RPS exists
     */
    public function exists(string $kodeMk, int $idKurikulum, string $semesterBerlaku, string $tahunAjaran): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE kode_mk = :kode_mk
            AND id_kurikulum = :id_kurikulum
            AND semester_berlaku = :semester_berlaku
            AND tahun_ajaran = :tahun_ajaran
        ";

        $result = $this->queryOne($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum,
            'semester_berlaku' => $semesterBerlaku,
            'tahun_ajaran' => $tahunAjaran
        ]);

        return $result['count'] > 0;
    }

    /**
     * Update status
     */
    public function updateStatus(int $idRps, string $status): bool
    {
        return $this->update($idRps, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get active RPS for a mata kuliah
     */
    public function getActiveRPS(string $kodeMk, int $idKurikulum): ?array
    {
        $sql = "
            SELECT
                r.*,
                mk.nama_mk,
                mk.sks
            FROM {$this->table} r
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            WHERE r.kode_mk = :kode_mk
            AND r.id_kurikulum = :id_kurikulum
            AND r.status = 'active'
            ORDER BY r.tahun_ajaran DESC, r.semester_berlaku DESC
            LIMIT 1
        ";

        return $this->queryOne($sql, [
            'kode_mk' => $kodeMk,
            'id_kurikulum' => $idKurikulum
        ]);
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $idKurikulum, ?string $tahunAjaran = null): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_rps,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as rps_draft,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as rps_submitted,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as rps_approved,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as rps_active,
                COUNT(CASE WHEN status = 'archived' THEN 1 END) as rps_archived
            FROM {$this->table}
            WHERE id_kurikulum = :id_kurikulum
        ";

        $params = ['id_kurikulum' => $idKurikulum];

        if ($tahunAjaran !== null) {
            $sql .= " AND tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $tahunAjaran;
        }

        return $this->queryOne($sql, $params) ?: [];
    }

    // ===================================
    // VERSION CONTROL METHODS
    // ===================================

    /**
     * Create new version
     */
    public function createVersion(array $data): int
    {
        $sql = "
            INSERT INTO rps_version
            (id_rps, version_number, status, snapshot_data, created_by, keterangan, is_active, created_at)
            VALUES
            (:id_rps, :version_number, :status, :snapshot_data, :created_by, :keterangan, :is_active, :created_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get versions for RPS
     */
    public function getVersions(int $idRps): array
    {
        $sql = "
            SELECT
                v.*,
                d1.nama as nama_creator,
                d2.nama as nama_approver
            FROM rps_version v
            LEFT JOIN dosen d1 ON v.created_by = d1.id_dosen
            LEFT JOIN dosen d2 ON v.approved_by = d2.id_dosen
            WHERE v.id_rps = :id_rps
            ORDER BY v.version_number DESC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get active version
     */
    public function getActiveVersion(int $idRps): ?array
    {
        $sql = "
            SELECT
                v.*,
                d1.nama as nama_creator,
                d2.nama as nama_approver
            FROM rps_version v
            LEFT JOIN dosen d1 ON v.created_by = d1.id_dosen
            LEFT JOIN dosen d2 ON v.approved_by = d2.id_dosen
            WHERE v.id_rps = :id_rps AND v.is_active = true
            LIMIT 1
        ";

        return $this->queryOne($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get latest version number
     */
    public function getLatestVersionNumber(int $idRps): int
    {
        $sql = "SELECT COALESCE(MAX(version_number), 0) as max_version FROM rps_version WHERE id_rps = :id_rps";
        $result = $this->queryOne($sql, ['id_rps' => $idRps]);
        return (int)$result['max_version'];
    }

    /**
     * Set active version
     */
    public function setActiveVersion(int $idRps, int $versionNumber): bool
    {
        $this->db->beginTransaction();

        try {
            // Deactivate all versions
            $sql1 = "UPDATE rps_version SET is_active = false WHERE id_rps = :id_rps";
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute(['id_rps' => $idRps]);

            // Activate specific version
            $sql2 = "UPDATE rps_version SET is_active = true WHERE id_rps = :id_rps AND version_number = :version_number";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([
                'id_rps' => $idRps,
                'version_number' => $versionNumber
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ===================================
    // APPROVAL WORKFLOW METHODS
    // ===================================

    /**
     * Create approval request
     */
    public function createApproval(array $data): int
    {
        $sql = "
            INSERT INTO rps_approval
            (id_rps, approver, approval_level, status, komentar, created_at)
            VALUES
            (:id_rps, :approver, :approval_level, :status, :komentar, :created_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get approvals for RPS
     */
    public function getApprovals(int $idRps): array
    {
        $sql = "
            SELECT
                a.*,
                d.nama as nama_approver,
                d.email as email_approver
            FROM rps_approval a
            LEFT JOIN dosen d ON a.approver = d.id_dosen
            WHERE a.id_rps = :id_rps
            ORDER BY a.approval_level ASC, a.created_at DESC
        ";

        return $this->query($sql, ['id_rps' => $idRps]);
    }

    /**
     * Get pending approvals for a user
     */
    public function getPendingApprovalsForUser(string $idDosen): array
    {
        $sql = "
            SELECT
                a.*,
                r.kode_mk,
                r.semester_berlaku,
                r.tahun_ajaran,
                r.status as rps_status,
                mk.nama_mk,
                mk.sks
            FROM rps_approval a
            JOIN rps r ON a.id_rps = r.id_rps
            JOIN matakuliah mk ON r.kode_mk = mk.kode_mk AND r.id_kurikulum = mk.id_kurikulum
            WHERE a.approver = :id_dosen AND a.status = 'pending'
            ORDER BY a.created_at DESC
        ";

        return $this->query($sql, ['id_dosen' => $idDosen]);
    }

    /**
     * Update approval status
     */
    public function updateApprovalStatus(int $idApproval, string $status, ?string $komentar = null): bool
    {
        $data = [
            'status' => $status,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        if ($komentar !== null) {
            $data['komentar'] = $komentar;
        }

        $sql = "UPDATE rps_approval SET " . implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data))) . " WHERE id_approval = :id_approval";
        $data['id_approval'] = $idApproval;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Check if all approvals completed
     */
    public function areAllApprovalsCompleted(int $idRps): bool
    {
        $sql = "
            SELECT COUNT(*) as pending_count
            FROM rps_approval
            WHERE id_rps = :id_rps AND status = 'pending'
        ";

        $result = $this->queryOne($sql, ['id_rps' => $idRps]);
        return $result['pending_count'] == 0;
    }

    /**
     * Check if any approval rejected
     */
    public function hasRejectedApproval(int $idRps): bool
    {
        $sql = "
            SELECT COUNT(*) as rejected_count
            FROM rps_approval
            WHERE id_rps = :id_rps AND status = 'rejected'
        ";

        $result = $this->queryOne($sql, ['id_rps' => $idRps]);
        return $result['rejected_count'] > 0;
    }
}
