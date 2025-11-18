<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\RPSService;
use App\Middleware\AuthMiddleware;

/**
 * RPS (Rencana Pembelajaran Semester) Controller
 */
class RPSController
{
    private RPSService $service;

    public function __construct()
    {
        $this->service = new RPSService();
    }

    /**
     * Get RPS list with filters
     * GET /api/rps?id_kurikulum=1&kode_mk=MK001&status=active
     */
    public function index(): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');
            $kodeMk = Request::input('kode_mk');
            $idDosen = Request::input('id_dosen');
            $semesterBerlaku = Request::input('semester_berlaku');
            $tahunAjaran = Request::input('tahun_ajaran');
            $status = Request::input('status');

            $filters = [];
            if ($semesterBerlaku) {
                $filters['semester_berlaku'] = $semesterBerlaku;
            }
            if ($tahunAjaran) {
                $filters['tahun_ajaran'] = $tahunAjaran;
            }
            if ($status) {
                $filters['status'] = $status;
            }

            if ($kodeMk && $idKurikulum) {
                // Get by mata kuliah
                $rps = $this->service->getByMataKuliah($kodeMk, (int)$idKurikulum, $filters);
            } elseif ($idDosen) {
                // Get by dosen
                $rps = $this->service->getByDosen($idDosen, $filters);
            } elseif ($idKurikulum) {
                // Get by kurikulum
                $rps = $this->service->getByKurikulum((int)$idKurikulum, $filters);
            } else {
                Response::error('Minimal id_kurikulum atau id_dosen harus diisi', 400);
                return;
            }

            Response::success($rps);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get RPS by ID with details
     * GET /api/rps/:id
     */
    public function show(string $id): void
    {
        try {
            $rps = $this->service->getById((int)$id);
            Response::success($rps);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create RPS
     * POST /api/rps
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'kode_mk',
                'id_kurikulum',
                'semester_berlaku',
                'tahun_ajaran',
                'ketua_pengembang',
                'deskripsi_mk',
                'deskripsi_singkat'
            ]);

            // Get id_dosen from user (assuming dosen is authenticated)
            $idDosen = $user['username']; // Or get from user table if needed

            $rps = $this->service->create($data, $user['id_user'], $idDosen);

            Response::success($rps, 'RPS berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update RPS
     * PUT /api/rps/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'deskripsi_mk',
                'deskripsi_singkat'
            ]);

            $idDosen = $user['username'];

            $rps = $this->service->update((int)$id, $data, $user['id_user'], $idDosen);

            Response::success($rps, 'RPS berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Submit RPS for approval
     * POST /api/rps/:id/submit
     */
    public function submit(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $approvers = Request::only(['level1', 'level2', 'level3']);

            if (empty($approvers['level1']) || empty($approvers['level2']) || empty($approvers['level3'])) {
                Response::error('Approver untuk semua level (level1, level2, level3) wajib diisi', 400);
                return;
            }

            $rps = $this->service->submitForApproval((int)$id, $approvers, $user['id_user']);

            Response::success($rps, 'RPS berhasil diajukan untuk approval');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Process approval (approve/reject/revise)
     * POST /api/rps/approval/:id_approval
     */
    public function processApproval(string $idApproval): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $decision = Request::input('decision');
            $komentar = Request::input('komentar');

            if (!$decision) {
                Response::error('Decision wajib diisi (approved/rejected/revised)', 400);
                return;
            }

            $rps = $this->service->processApproval(
                (int)$idApproval,
                $decision,
                $komentar,
                $user['id_user']
            );

            Response::success($rps, 'Approval berhasil diproses');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Activate RPS
     * POST /api/rps/:id/activate
     */
    public function activate(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $rps = $this->service->activate((int)$id, $user['id_user']);

            Response::success($rps, 'RPS berhasil diaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Archive RPS
     * POST /api/rps/:id/archive
     */
    public function archive(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $rps = $this->service->archive((int)$id, $user['id_user']);

            Response::success($rps, 'RPS berhasil diarsipkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete RPS
     * DELETE /api/rps/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'RPS berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get versions for RPS
     * GET /api/rps/:id/versions
     */
    public function getVersions(string $id): void
    {
        try {
            $versions = $this->service->getVersions((int)$id);
            Response::success($versions);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Set active version
     * POST /api/rps/:id/versions/:version_number/activate
     */
    public function setActiveVersion(string $id, string $versionNumber): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $rps = $this->service->setActiveVersion((int)$id, (int)$versionNumber, $user['id_user']);

            Response::success($rps, 'Version berhasil diaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get pending approvals for current user
     * GET /api/rps/pending-approvals
     */
    public function getPendingApprovals(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['username']; // Assuming username is id_dosen

            $approvals = $this->service->getPendingApprovalsForUser($idDosen);
            Response::success($approvals);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get RPS statistics
     * GET /api/rps/statistics
     */
    public function statistics(): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');
            $tahunAjaran = Request::input('tahun_ajaran');

            if (!$idKurikulum) {
                Response::error('id_kurikulum wajib diisi', 400);
                return;
            }

            $stats = $this->service->getStatistics(
                (int)$idKurikulum,
                $tahunAjaran
            );

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
