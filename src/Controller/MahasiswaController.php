<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\MahasiswaService;
use App\Middleware\AuthMiddleware;

/**
 * Mahasiswa Controller
 * Handles student management endpoints
 */
class MahasiswaController
{
    private MahasiswaService $service;

    public function __construct()
    {
        $this->service = new MahasiswaService();
    }

    /**
     * Get all mahasiswa
     * GET /api/mahasiswa
     */
    public function index(): void
    {
        try {
            $filters = [
                'id_prodi' => Request::get('id_prodi'),
                'id_kurikulum' => Request::get('id_kurikulum'),
                'angkatan' => Request::get('angkatan'),
                'status' => Request::get('status'),
                'search' => Request::get('search')
            ];

            $mahasiswa = $this->service->getAll(array_filter($filters));
            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get mahasiswa by NIM
     * GET /api/mahasiswa/:nim
     */
    public function show(string $nim): void
    {
        try {
            $mahasiswa = $this->service->getByNim($nim);
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
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $user = AuthMiddleware::user();
            $data = Request::only(['nim', 'nama', 'email', 'id_prodi', 'id_kurikulum', 'angkatan', 'status']);

            $mahasiswa = $this->service->create($data, $user['id_user']);

            Response::success($mahasiswa, 'Mahasiswa berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update mahasiswa
     * PUT /api/mahasiswa/:nim
     */
    public function update(string $nim): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $user = AuthMiddleware::user();
            $data = Request::only(['nama', 'email', 'id_prodi', 'angkatan', 'status']);

            $mahasiswa = $this->service->update($nim, $data, $user['id_user']);

            Response::success($mahasiswa, 'Mahasiswa berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete mahasiswa
     * DELETE /api/mahasiswa/:nim
     */
    public function delete(string $nim): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $this->service->delete($nim, $user['id_user']);

            Response::success(null, 'Mahasiswa berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get statistics by prodi
     * GET /api/prodi/:id_prodi/mahasiswa-statistics
     */
    public function getStatisticsByProdi(string $idProdi): void
    {
        try {
            $stats = $this->service->getStatisticsByProdi($idProdi);
            Response::success($stats);
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
            $mahasiswa = $this->service->getByAngkatan($angkatan);
            Response::success($mahasiswa);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
