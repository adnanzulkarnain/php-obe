<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\ProdiService;
use App\Middleware\AuthMiddleware;

/**
 * Prodi Controller
 * API endpoints for prodi (study program) management
 */
class ProdiController
{
    private ProdiService $service;

    public function __construct()
    {
        $this->service = new ProdiService();
    }

    /**
     * Get all prodi with filters
     * GET /api/prodi?id_fakultas=FAK001&jenjang=S1&akreditasi=A&q=search
     */
    public function index(): void
    {
        try {
            $idFakultas = Request::input('id_fakultas');
            $jenjang = Request::input('jenjang');
            $akreditasi = Request::input('akreditasi');
            $search = Request::input('q');

            if ($search) {
                // Search mode
                $filters = [];
                if ($idFakultas) $filters['id_fakultas'] = $idFakultas;
                if ($jenjang) $filters['jenjang'] = $jenjang;

                $prodi = $this->service->search($search, $filters);
            } else {
                // List mode with filters
                $filters = [];
                if ($idFakultas) $filters['id_fakultas'] = $idFakultas;
                if ($jenjang) $filters['jenjang'] = $jenjang;
                if ($akreditasi) $filters['akreditasi'] = $akreditasi;

                $prodi = $this->service->getAll($filters);
            }

            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get prodi by ID with details
     * GET /api/prodi/:id
     */
    public function show(string $id): void
    {
        try {
            $prodi = $this->service->getById($id);

            if (!$prodi) {
                Response::error('Prodi not found', 404);
                return;
            }

            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create new prodi
     * POST /api/prodi
     */
    public function create(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $idProdi = $this->service->create($data, $userId);

            Response::success([
                'message' => 'Prodi created successfully',
                'id_prodi' => $idProdi,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Update prodi
     * PUT /api/prodi/:id
     */
    public function update(string $id): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $success = $this->service->update($id, $data, $userId);

            if ($success) {
                Response::success(['message' => 'Prodi updated successfully']);
            } else {
                Response::error('Failed to update prodi', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete prodi
     * DELETE /api/prodi/:id
     */
    public function delete(string $id): void
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $success = $this->service->delete($id, $userId);

            if ($success) {
                Response::success(['message' => 'Prodi deleted successfully']);
            } else {
                Response::error('Failed to delete prodi', 500);
            }
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409); // Conflict
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get prodi by fakultas
     * GET /api/fakultas/:id/prodi
     */
    public function getByFakultas(string $id): void
    {
        try {
            $prodi = $this->service->getByFakultas($id);
            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get prodi by jenjang
     * GET /api/prodi/jenjang/:jenjang
     */
    public function getByJenjang(string $jenjang): void
    {
        try {
            $prodi = $this->service->getByJenjang($jenjang);
            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get prodi statistics
     * GET /api/prodi/statistics
     */
    public function getStatistics(): void
    {
        try {
            $statistics = $this->service->getStatistics();
            Response::success($statistics);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get statistics by fakultas
     * GET /api/prodi/statistics/fakultas
     */
    public function getStatisticsByFakultas(): void
    {
        try {
            $statistics = $this->service->getStatisticsByFakultas();
            Response::success($statistics);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get statistics by jenjang
     * GET /api/prodi/statistics/jenjang
     */
    public function getStatisticsByJenjang(): void
    {
        try {
            $statistics = $this->service->getStatisticsByJenjang();
            Response::success($statistics);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
