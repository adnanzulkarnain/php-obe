<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\CPLService;
use App\Middleware\AuthMiddleware;

/**
 * CPL Controller
 */
class CPLController
{
    private CPLService $service;

    public function __construct()
    {
        $this->service = new CPLService();
    }

    /**
     * Get CPL by kurikulum
     * GET /api/cpl?id_kurikulum=1
     */
    public function index(): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');
            $grouped = Request::input('grouped', false);

            if (!$idKurikulum) {
                Response::error('id_kurikulum wajib diisi', 400);
            }

            if ($grouped) {
                $cpl = $this->service->getGroupedByKategori((int) $idKurikulum);
            } else {
                $cpl = $this->service->getByKurikulum((int) $idKurikulum);
            }

            Response::success($cpl);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create CPL (UC-K04)
     * POST /api/cpl
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'dosen', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_kurikulum',
                'kode_cpl',
                'deskripsi',
                'kategori',
                'urutan'
            ]);

            $cpl = $this->service->create($data, $user['id_user']);

            Response::success($cpl, 'CPL berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update CPL
     * PUT /api/cpl/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'dosen', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['deskripsi', 'kategori', 'urutan']);

            $cpl = $this->service->update((int) $id, $data, $user['id_user']);

            Response::success($cpl, 'CPL berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Deactivate CPL
     * DELETE /api/cpl/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();

            $this->service->deactivate((int) $id, $user['id_user']);

            Response::success(null, 'CPL berhasil dinonaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
