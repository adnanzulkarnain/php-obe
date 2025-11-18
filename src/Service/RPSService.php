<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RPS;
use App\Entity\RPSApproval;
use App\Repository\RPSRepository;
use App\Repository\MataKuliahRepository;

/**
 * RPS Service
 */
class RPSService
{
    private RPSRepository $repository;
    private MataKuliahRepository $mataKuliahRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new RPSRepository();
        $this->mataKuliahRepo = new MataKuliahRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create RPS
     */
    public function create(array $data, int $userId, string $idDosen): array
    {
        // Create entity and validate
        $rps = RPS::fromArray($data);
        $errors = $rps->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if mata kuliah exists
        $mk = $this->mataKuliahRepo->findByKodeAndKurikulum($rps->kode_mk, $rps->id_kurikulum);
        if (!$mk) {
            throw new \Exception('Mata Kuliah tidak ditemukan', 404);
        }

        // Check if RPS already exists for this semester
        if ($this->repository->exists(
            $rps->kode_mk,
            $rps->id_kurikulum,
            $rps->semester_berlaku,
            $rps->tahun_ajaran
        )) {
            throw new \Exception('RPS untuk MK, semester, dan tahun ajaran ini sudah ada', 400);
        }

        // Create RPS
        $rpsData = [
            'kode_mk' => $rps->kode_mk,
            'id_kurikulum' => $rps->id_kurikulum,
            'semester_berlaku' => $rps->semester_berlaku,
            'tahun_ajaran' => $rps->tahun_ajaran,
            'status' => 'draft',
            'ketua_pengembang' => $data['ketua_pengembang'] ?? $idDosen,
            'tanggal_disusun' => date('Y-m-d'),
            'deskripsi_mk' => $data['deskripsi_mk'] ?? null,
            'deskripsi_singkat' => $data['deskripsi_singkat'] ?? null,
            'created_by' => $idDosen,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idRps = $this->repository->create($rpsData);

        // Create initial version
        $this->createVersion($idRps, $rpsData, $idDosen, 'Initial version');

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'INSERT',
            null,
            $rpsData,
            $userId
        );

        return $this->repository->findByIdWithDetails($idRps);
    }

    /**
     * Update RPS
     */
    public function update(int $idRps, array $data, int $userId, string $idDosen): array
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Business rule: Can only update if status is draft or revised
        if (!in_array($rps['status'], ['draft', 'revised'])) {
            throw new \Exception('RPS hanya dapat diubah jika berstatus draft atau revised', 400);
        }

        // Prepare update data
        $updateData = [
            'deskripsi_mk' => $data['deskripsi_mk'] ?? $rps['deskripsi_mk'],
            'deskripsi_singkat' => $data['deskripsi_singkat'] ?? $rps['deskripsi_singkat'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Update RPS
        $this->repository->update($idRps, $updateData);

        // Create new version for this update
        $updatedRps = array_merge($rps, $updateData);
        $latestVersion = $this->repository->getLatestVersionNumber($idRps);
        $this->createVersion($idRps, $updatedRps, $idDosen, 'Updated content', $latestVersion + 1);

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'UPDATE',
            $rps,
            $updatedRps,
            $userId
        );

        return $this->repository->findByIdWithDetails($idRps);
    }

    /**
     * Submit RPS for approval
     */
    public function submitForApproval(int $idRps, array $approvers, int $userId): array
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Business rule: Can only submit if status is draft or revised
        if (!in_array($rps['status'], ['draft', 'revised'])) {
            throw new \Exception('RPS hanya dapat diajukan jika berstatus draft atau revised', 400);
        }

        // Validate approvers structure
        if (!isset($approvers['level1']) || !isset($approvers['level2']) || !isset($approvers['level3'])) {
            throw new \Exception('Approver untuk 3 level (level1, level2, level3) wajib diisi', 400);
        }

        $this->repository->db->beginTransaction();

        try {
            // Update status to submitted
            $this->repository->updateStatus($idRps, 'submitted');

            // Create approval requests for 3 levels
            $approvalData = [
                [
                    'id_rps' => $idRps,
                    'approver' => $approvers['level1'], // Ketua RPS
                    'approval_level' => 1,
                    'status' => 'pending',
                    'komentar' => null,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id_rps' => $idRps,
                    'approver' => $approvers['level2'], // Kaprodi
                    'approval_level' => 2,
                    'status' => 'pending',
                    'komentar' => null,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id_rps' => $idRps,
                    'approver' => $approvers['level3'], // Dekan
                    'approval_level' => 3,
                    'status' => 'pending',
                    'komentar' => null,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];

            foreach ($approvalData as $approval) {
                $this->repository->createApproval($approval);
            }

            // Audit log
            $this->auditLog->log(
                'rps',
                $idRps,
                'UPDATE',
                ['status' => $rps['status']],
                ['status' => 'submitted'],
                $userId
            );

            $this->repository->db->commit();

            return $this->repository->findByIdWithDetails($idRps);
        } catch (\Exception $e) {
            $this->repository->db->rollBack();
            throw $e;
        }
    }

    /**
     * Approve or reject RPS
     */
    public function processApproval(int $idApproval, string $decision, ?string $komentar, int $userId): array
    {
        // Validate decision
        if (!in_array($decision, ['approved', 'rejected', 'revised'])) {
            throw new \Exception('Decision harus approved, rejected, atau revised', 400);
        }

        // Get approval record
        $approvals = $this->repository->query(
            "SELECT a.*, r.status as rps_status FROM rps_approval a JOIN rps r ON a.id_rps = r.id_rps WHERE a.id_approval = :id",
            ['id' => $idApproval]
        );

        if (empty($approvals)) {
            throw new \Exception('Approval tidak ditemukan', 404);
        }

        $approval = $approvals[0];

        // Business rule: Can only approve if status is pending
        if ($approval['status'] !== 'pending') {
            throw new \Exception('Approval sudah diproses sebelumnya', 400);
        }

        $this->repository->db->beginTransaction();

        try {
            // Update approval status
            $this->repository->updateApprovalStatus($idApproval, $decision, $komentar);

            // Get RPS
            $rps = $this->repository->find($approval['id_rps']);

            // Handle based on decision
            if ($decision === 'rejected') {
                // If rejected, change RPS status to revised
                $this->repository->updateStatus($approval['id_rps'], 'revised');

                // Mark all pending approvals as revised
                $this->repository->db->prepare("
                    UPDATE rps_approval
                    SET status = 'revised'
                    WHERE id_rps = :id_rps AND status = 'pending'
                ")->execute(['id_rps' => $approval['id_rps']]);
            } elseif ($decision === 'revised') {
                // If needs revision, change RPS status to revised
                $this->repository->updateStatus($approval['id_rps'], 'revised');
            } elseif ($decision === 'approved') {
                // Check if all approvals are completed
                if ($this->repository->areAllApprovalsCompleted($approval['id_rps'])) {
                    // All approved, change status to approved
                    $this->repository->updateStatus($approval['id_rps'], 'approved');
                }
            }

            // Audit log
            $this->auditLog->log(
                'rps_approval',
                $idApproval,
                'UPDATE',
                ['status' => 'pending'],
                ['status' => $decision, 'komentar' => $komentar],
                $userId
            );

            $this->repository->db->commit();

            return $this->repository->findByIdWithDetails($approval['id_rps']);
        } catch (\Exception $e) {
            $this->repository->db->rollBack();
            throw $e;
        }
    }

    /**
     * Activate RPS
     */
    public function activate(int $idRps, int $userId): array
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Business rule: Can only activate if status is approved
        if ($rps['status'] !== 'approved') {
            throw new \Exception('RPS hanya dapat diaktifkan jika sudah approved', 400);
        }

        // Deactivate other active RPS for the same mata kuliah
        $this->repository->query("
            UPDATE rps
            SET status = 'archived'
            WHERE kode_mk = :kode_mk
            AND id_kurikulum = :id_kurikulum
            AND status = 'active'
        ", [
            'kode_mk' => $rps['kode_mk'],
            'id_kurikulum' => $rps['id_kurikulum']
        ]);

        // Activate this RPS
        $this->repository->updateStatus($idRps, 'active');

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'UPDATE',
            ['status' => $rps['status']],
            ['status' => 'active'],
            $userId
        );

        return $this->repository->findByIdWithDetails($idRps);
    }

    /**
     * Archive RPS
     */
    public function archive(int $idRps, int $userId): array
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Update status to archived
        $this->repository->updateStatus($idRps, 'archived');

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'UPDATE',
            ['status' => $rps['status']],
            ['status' => 'archived'],
            $userId
        );

        return $this->repository->findByIdWithDetails($idRps);
    }

    /**
     * Get RPS by ID
     */
    public function getById(int $idRps): array
    {
        $rps = $this->repository->findByIdWithDetails($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Get approvals
        $rps['approvals'] = $this->repository->getApprovals($idRps);

        // Get active version
        $rps['active_version'] = $this->repository->getActiveVersion($idRps);

        return $rps;
    }

    /**
     * Get RPS by mata kuliah
     */
    public function getByMataKuliah(string $kodeMk, int $idKurikulum, ?array $filters = []): array
    {
        return $this->repository->findByMataKuliah($kodeMk, $idKurikulum, $filters);
    }

    /**
     * Get RPS by kurikulum
     */
    public function getByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        return $this->repository->findByKurikulum($idKurikulum, $filters);
    }

    /**
     * Get RPS by dosen
     */
    public function getByDosen(string $idDosen, ?array $filters = []): array
    {
        return $this->repository->findByDosen($idDosen, $filters);
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovalsForUser(string $idDosen): array
    {
        return $this->repository->getPendingApprovalsForUser($idDosen);
    }

    /**
     * Get RPS statistics
     */
    public function getStatistics(int $idKurikulum, ?string $tahunAjaran = null): array
    {
        return $this->repository->getStatistics($idKurikulum, $tahunAjaran);
    }

    /**
     * Create version
     */
    private function createVersion(int $idRps, array $rpsData, string $createdBy, string $keterangan, ?int $versionNumber = null): int
    {
        if ($versionNumber === null) {
            $versionNumber = $this->repository->getLatestVersionNumber($idRps) + 1;
        }

        $versionData = [
            'id_rps' => $idRps,
            'version_number' => $versionNumber,
            'status' => $rpsData['status'] ?? 'draft',
            'snapshot_data' => json_encode($rpsData),
            'created_by' => $createdBy,
            'keterangan' => $keterangan,
            'is_active' => $versionNumber === 1, // First version is active by default
            'created_at' => date('Y-m-d H:i:s')
        ];

        $idVersion = $this->repository->createVersion($versionData);

        // Set as active version
        if ($versionNumber === 1) {
            $this->repository->setActiveVersion($idRps, $versionNumber);
        }

        return $idVersion;
    }

    /**
     * Get versions
     */
    public function getVersions(int $idRps): array
    {
        return $this->repository->getVersions($idRps);
    }

    /**
     * Set active version
     */
    public function setActiveVersion(int $idRps, int $versionNumber, int $userId): array
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Set active version
        $this->repository->setActiveVersion($idRps, $versionNumber);

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'UPDATE',
            ['active_version' => 'previous'],
            ['active_version' => $versionNumber],
            $userId
        );

        return $this->repository->findByIdWithDetails($idRps);
    }

    /**
     * Delete RPS
     */
    public function delete(int $idRps, int $userId): void
    {
        $rps = $this->repository->find($idRps);

        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Business rule: Can only delete if status is draft
        if ($rps['status'] !== 'draft') {
            throw new \Exception('Hanya RPS dengan status draft yang dapat dihapus', 400);
        }

        // Delete RPS (will cascade delete versions and approvals)
        $this->repository->delete($idRps);

        // Audit log
        $this->auditLog->log(
            'rps',
            $idRps,
            'DELETE',
            $rps,
            null,
            $userId
        );
    }
}
