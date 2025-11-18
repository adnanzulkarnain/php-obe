<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\DosenService;
use App\Middleware\AuthMiddleware;

/**
 * Dosen Controller
 * API endpoints for dosen (lecturer/faculty) management
 */
class DosenController
{
    private DosenService $service;

    public function __construct()
    {
        $this->service = new DosenService();
    }

    /**
     * Get dosen list with filters
     * GET /api/dosen?status=aktif&id_prodi=PRODI001&q=search
     */
    public function index(): void
    {
        try {
            $status = Request::input('status');
            $idProdi = Request::input('id_prodi');
            $search = Request::input('q');

            if ($search) {
                // Search mode
                $filters = [];
                if ($status) $filters['status'] = $status;
                if ($idProdi) $filters['id_prodi'] = $idProdi;

                $dosen = $this->service->search($search, $filters);
            } else {
                // List mode with filters
                $filters = [];
                if ($status) $filters['status'] = $status;
                if ($idProdi) $filters['id_prodi'] = $idProdi;

                $dosen = $this->service->getAll($filters);
            }

            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get dosen by ID with details
     * GET /api/dosen/:id
     */
    public function show(string $id): void
    {
        try {
            $dosen = $this->service->getById($id);

            if (!$dosen) {
                Response::error('Dosen not found', 404);
                return;
            }

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
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $idDosen = $this->service->create($data, $userId);

            Response::success([
                'message' => 'Dosen created successfully',
                'id_dosen' => $idDosen,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Update dosen
     * PUT /api/dosen/:id
     */
    public function update(string $id): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $success = $this->service->update($id, $data, $userId);

            if ($success) {
                Response::success(['message' => 'Dosen updated successfully']);
            } else {
                Response::error('Failed to update dosen', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete dosen
     * DELETE /api/dosen/:id
     */
    public function delete(string $id): void
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $success = $this->service->delete($id, $userId);

            if ($success) {
                Response::success(['message' => 'Dosen deleted successfully']);
            } else {
                Response::error('Failed to delete dosen', 500);
            }
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409); // Conflict
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Change dosen status
     * POST /api/dosen/:id/change-status
     */
    public function changeStatus(string $id): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['status'])) {
                Response::error('Status is required', 400);
                return;
            }

            $success = $this->service->changeStatus($id, $data['status'], $userId);

            if ($success) {
                Response::success(['message' => 'Status changed successfully']);
            } else {
                Response::error('Failed to change status', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get dosen by prodi
     * GET /api/prodi/:id/dosen
     */
    public function getByProdi(string $id): void
    {
        try {
            $status = Request::input('status');

            $filters = [];
            if ($status) $filters['status'] = $status;

            $dosen = $this->service->getByProdi($id, $filters);

            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get dosen by status
     * GET /api/dosen/status/:status
     */
    public function getByStatus(string $status): void
    {
        try {
            $dosen = $this->service->getByStatus($status);
            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get dosen statistics
     * GET /api/dosen/statistics
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
     * Get dosen with teaching load
     * GET /api/dosen/teaching-load?tahun_ajaran=2024/2025&semester=Ganjil
     */
    public function getTeachingLoad(): void
    {
        try {
            $tahunAjaran = Request::input('tahun_ajaran');
            $semester = Request::input('semester');

            $dosen = $this->service->getDosenWithTeachingLoad($tahunAjaran, $semester);

            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create user account for dosen
     * POST /api/dosen/:id/create-user
     */
    public function createUserAccount(string $id): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['password'])) {
                Response::error('Password is required', 400);
                return;
            }

            $idUser = $this->service->createUserAccount($id, $data, $userId);

            Response::success([
                'message' => 'User account created successfully',
                'id_user' => $idUser,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get dosen by NIDN
     * GET /api/dosen/nidn/:nidn
     */
    public function getByNidn(string $nidn): void
    {
        try {
            $dosen = $this->service->getByNidn($nidn);

            if (!$dosen) {
                Response::error('Dosen not found', 404);
                return;
            }

            Response::success($dosen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
