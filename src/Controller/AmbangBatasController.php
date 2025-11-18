<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\AmbangBatasService;
use App\Middleware\AuthMiddleware;

/**
 * Ambang Batas Controller
 * Handles threshold/passing grade management endpoints
 */
class AmbangBatasController
{
    private AmbangBatasService $service;

    public function __construct()
    {
        $this->service = new AmbangBatasService();
    }

    /**
     * Get all thresholds for RPS
     * GET /api/rps/:id/ambang-batas
     */
    public function getByRPS(string $idRps): void
    {
        try {
            $thresholds = $this->service->getByRPS((int)$idRps);
            Response::success($thresholds);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get threshold by ID
     * GET /api/ambang-batas/:id
     */
    public function show(string $id): void
    {
        try {
            $threshold = $this->service->getById((int)$id);
            Response::success($threshold);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create threshold
     * POST /api/ambang-batas
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_rps',
                'id_jenis',
                'nilai_minimal',
                'keterangan'
            ]);

            $threshold = $this->service->create($data, $user['id_user']);

            Response::success($threshold, 'Ambang batas berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update threshold
     * PUT /api/ambang-batas/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'nilai_minimal',
                'keterangan'
            ]);

            $threshold = $this->service->update((int)$id, $data, $user['id_user']);

            Response::success($threshold, 'Ambang batas berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete threshold
     * DELETE /api/ambang-batas/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'Ambang batas berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Bulk create thresholds
     * POST /api/rps/:id/ambang-batas/bulk
     */
    public function bulkCreate(string $idRps): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $thresholds = Request::input('thresholds');

            if (!is_array($thresholds)) {
                Response::error('thresholds wajib berupa array', 400);
                return;
            }

            $results = $this->service->bulkCreate((int)$idRps, $thresholds, $user['id_user']);

            Response::success($results, 'Bulk create ambang batas selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get threshold summary for RPS
     * GET /api/rps/:id/ambang-batas/summary
     */
    public function getSummary(string $idRps): void
    {
        try {
            $summary = $this->service->getThresholdSummary((int)$idRps);
            Response::success($summary);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
