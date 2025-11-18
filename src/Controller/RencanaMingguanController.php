<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\RencanaMingguanService;
use App\Middleware\AuthMiddleware;

/**
 * Rencana Mingguan Controller
 * Handles weekly learning plan endpoints
 */
class RencanaMingguanController
{
    private RencanaMingguanService $service;

    public function __construct()
    {
        $this->service = new RencanaMingguanService();
    }

    /**
     * Get rencana mingguan by RPS
     * GET /api/rps/:id/rencana-mingguan
     */
    public function getByRPS(string $idRps): void
    {
        try {
            $minggu = $this->service->getByRPS((int)$idRps);
            Response::success($minggu);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get rencana mingguan by ID
     * GET /api/rencana-mingguan/:id
     */
    public function show(string $id): void
    {
        try {
            $minggu = $this->service->getById((int)$id);
            Response::success($minggu);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create rencana mingguan
     * POST /api/rencana-mingguan
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_rps',
                'minggu_ke',
                'id_subcpmk',
                'materi',
                'metode',
                'aktivitas',
                'media_software',
                'media_hardware',
                'pengalaman_belajar',
                'estimasi_waktu_menit'
            ]);

            $minggu = $this->service->create($data, $user['id_user']);

            Response::success($minggu, 'Rencana mingguan berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update rencana mingguan
     * PUT /api/rencana-mingguan/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'minggu_ke',
                'id_subcpmk',
                'materi',
                'metode',
                'aktivitas',
                'media_software',
                'media_hardware',
                'pengalaman_belajar',
                'estimasi_waktu_menit'
            ]);

            $minggu = $this->service->update((int)$id, $data, $user['id_user']);

            Response::success($minggu, 'Rencana mingguan berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete rencana mingguan
     * DELETE /api/rencana-mingguan/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'Rencana mingguan berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Bulk create rencana mingguan (16 weeks)
     * POST /api/rps/:id/rencana-mingguan/bulk-create
     */
    public function bulkCreate(string $idRps): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $results = $this->service->bulkCreate((int)$idRps, $user['id_user']);

            Response::success($results, 'Bulk create rencana mingguan selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get completion statistics
     * GET /api/rps/:id/rencana-mingguan/stats
     */
    public function getStats(string $idRps): void
    {
        try {
            $stats = $this->service->getCompletionStats((int)$idRps);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
