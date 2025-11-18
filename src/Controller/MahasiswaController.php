<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\MahasiswaService;
use App\Middleware\AuthMiddleware;

/**
 * Mahasiswa Controller
 * API endpoints for mahasiswa (student) management
 */
class MahasiswaController
{
    private MahasiswaService $service;

    public function __construct()
    {
        $this->service = new MahasiswaService();
    }

    /**
     * Get mahasiswa list with filters
     * GET /api/mahasiswa?status=aktif&angkatan=2024&id_prodi=PRODI001&q=search
     */
    public function index(): void
    {
        try {
            $status = Request::input('status');
            $angkatan = Request::input('angkatan');
            $idProdi = Request::input('id_prodi');
            $idKurikulum = Request::input('id_kurikulum');
            $search = Request::input('q');

            if ($search) {
                // Search mode
                $filters = [];
                if ($status) $filters['status'] = $status;
                if ($angkatan) $filters['angkatan'] = $angkatan;
                if ($idProdi) $filters['id_prodi'] = $idProdi;

                $mahasiswa = $this->service->search($search, $filters);
            } else {
                // List mode with filters
                $filters = [];
                if ($status) $filters['status'] = $status;
                if ($angkatan) $filters['angkatan'] = $angkatan;
                if ($idProdi) $filters['id_prodi'] = $idProdi;
                if ($idKurikulum) $filters['id_kurikulum'] = (int)$idKurikulum;

                $mahasiswa = $this->service->getAll($filters);
            }

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa by NIM with details
     * GET /api/mahasiswa/:nim
     */
    public function show(string $nim): void
    {
        try {
            $mahasiswa = $this->service->getByNim($nim);

            if (!$mahasiswa) {
                Response::error('Mahasiswa not found', 404);
                return;
            }

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create new mahasiswa
     * POST /api/mahasiswa
     */
    public function create(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $nim = $this->service->create($data, $userId);

            Response::success([
                'message' => 'Mahasiswa created successfully',
                'nim' => $nim,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Update mahasiswa
     * PUT /api/mahasiswa/:nim
     * NOTE: id_kurikulum is IMMUTABLE and cannot be changed
     */
    public function update(string $nim): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $success = $this->service->update($nim, $data, $userId);

            if ($success) {
                Response::success(['message' => 'Mahasiswa updated successfully']);
            } else {
                Response::error('Failed to update mahasiswa', 500);
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
     * Delete mahasiswa
     * DELETE /api/mahasiswa/:nim
     */
    public function delete(string $nim): void
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $success = $this->service->delete($nim, $userId);

            if ($success) {
                Response::success(['message' => 'Mahasiswa deleted successfully']);
            } else {
                Response::error('Failed to delete mahasiswa', 500);
            }
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409); // Conflict
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Change mahasiswa status
     * POST /api/mahasiswa/:nim/change-status
     */
    public function changeStatus(string $nim): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['status'])) {
                Response::error('Status is required', 400);
                return;
            }

            $success = $this->service->changeStatus($nim, $data['status'], $userId);

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
     * Get mahasiswa by prodi
     * GET /api/prodi/:id/mahasiswa
     */
    public function getByProdi(string $id): void
    {
        try {
            $status = Request::input('status');
            $angkatan = Request::input('angkatan');

            $filters = [];
            if ($status) $filters['status'] = $status;
            if ($angkatan) $filters['angkatan'] = $angkatan;

            $mahasiswa = $this->service->getByProdi($id, $filters);

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa by kurikulum
     * GET /api/kurikulum/:id/mahasiswa
     */
    public function getByKurikulum(string $id): void
    {
        try {
            $status = Request::input('status');
            $angkatan = Request::input('angkatan');

            $filters = [];
            if ($status) $filters['status'] = $status;
            if ($angkatan) $filters['angkatan'] = $angkatan;

            $mahasiswa = $this->service->getByKurikulum((int)$id, $filters);

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa by angkatan
     * GET /api/mahasiswa/angkatan/:angkatan
     */
    public function getByAngkatan(string $angkatan): void
    {
        try {
            $status = Request::input('status');
            $idProdi = Request::input('id_prodi');

            $filters = [];
            if ($status) $filters['status'] = $status;
            if ($idProdi) $filters['id_prodi'] = $idProdi;

            $mahasiswa = $this->service->getByAngkatan($angkatan, $filters);

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa by status
     * GET /api/mahasiswa/status/:status
     */
    public function getByStatus(string $status): void
    {
        try {
            $mahasiswa = $this->service->getByStatus($status);
            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa statistics
     * GET /api/mahasiswa/statistics
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
     * Get mahasiswa with academic data (IPK, SKS)
     * GET /api/mahasiswa/academic-data?status=aktif&angkatan=2024
     */
    public function getAcademicData(): void
    {
        try {
            $status = Request::input('status');
            $angkatan = Request::input('angkatan');
            $idProdi = Request::input('id_prodi');

            $filters = [];
            if ($status) $filters['status'] = $status;
            if ($angkatan) $filters['angkatan'] = $angkatan;
            if ($idProdi) $filters['id_prodi'] = $idProdi;

            $mahasiswa = $this->service->getMahasiswaWithAcademicData($filters);

            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create user account for mahasiswa
     * POST /api/mahasiswa/:nim/create-user
     */
    public function createUserAccount(string $nim): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['password'])) {
                Response::error('Password is required', 400);
                return;
            }

            $idUser = $this->service->createUserAccount($nim, $data, $userId);

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
     * Bulk create mahasiswa
     * POST /api/mahasiswa/bulk
     */
    public function bulkCreate(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['mahasiswa']) || !is_array($data['mahasiswa'])) {
                Response::error('Array of mahasiswa is required in "mahasiswa" field', 400);
                return;
            }

            $results = $this->service->bulkCreate($data['mahasiswa'], $userId);

            Response::success([
                'message' => 'Bulk create completed',
                'total_processed' => count($data['mahasiswa']),
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
