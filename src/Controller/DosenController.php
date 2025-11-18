<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\DosenService;
use App\Middleware\AuthMiddleware;

/**
 * Dosen Controller
 * Handles lecturer management endpoints
 */
class DosenController
{
    private DosenService $service;

    public function __construct()
    {
        $this->service = new DosenService();
    }

    /**
     * Get all dosen
     * GET /api/dosen
     */
    public function index(): void
    {
        try {
            $filters = [
                'id_prodi' => Request::get('id_prodi'),
                'status' => Request::get('status'),
                'search' => Request::get('search')
            ];

            $dosen = $this->service->getAll(array_filter($filters));
            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get dosen by ID
     * GET /api/dosen/:id
     */
    public function show(string $id): void
    {
        try {
            $dosen = $this->service->getById($id);
            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create new dosen
     * POST /api/dosen
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_dosen', 'nidn', 'nama', 'email', 'phone', 'id_prodi', 'status']);

            $dosen = $this->service->create($data, $user['id_user']);

            Response::success($dosen, 'Dosen berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update dosen
     * PUT /api/dosen/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $user = AuthMiddleware::user();
            $data = Request::only(['nidn', 'nama', 'email', 'phone', 'id_prodi', 'status']);

            $dosen = $this->service->update($id, $data, $user['id_user']);

            Response::success($dosen, 'Dosen berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete dosen
     * DELETE /api/dosen/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $this->service->delete($id, $user['id_user']);

            Response::success(null, 'Dosen berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get teaching assignments
     * GET /api/dosen/:id/teaching-assignments
     */
    public function getTeachingAssignments(string $id): void
    {
        try {
            $assignments = $this->service->getTeachingAssignments($id);
            Response::success($assignments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
