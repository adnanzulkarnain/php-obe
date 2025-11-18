<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\MataKuliahService;
use App\Middleware\AuthMiddleware;

/**
 * Mata Kuliah Controller
 */
class MataKuliahController
{
    private MataKuliahService $service;

    public function __construct()
    {
        $this->service = new MataKuliahService();
    }

    /**
     * Get MK by kurikulum
     * GET /api/matakuliah?id_kurikulum=1
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
                $mk = $this->service->getGroupedBySemester((int) $idKurikulum);
            } else {
                $mk = $this->service->getByKurikulum((int) $idKurikulum);
            }

            Response::success($mk);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create Mata Kuliah (UC-K05)
     * POST /api/matakuliah
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_kurikulum',
                'kode_mk',
                'nama_mk',
                'nama_mk_eng',
                'sks',
                'semester',
                'rumpun',
                'jenis_mk'
            ]);

            $mk = $this->service->create($data, $user['id_user']);

            Response::success($mk, 'Mata Kuliah berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update Mata Kuliah
     * PUT /api/matakuliah/:kode_mk/:id_kurikulum
     */
    public function update(string $kodeMk, string $idKurikulum): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'nama_mk',
                'nama_mk_eng',
                'sks',
                'semester',
                'rumpun',
                'jenis_mk'
            ]);

            $mk = $this->service->update($kodeMk, (int) $idKurikulum, $data, $user['id_user']);

            Response::success($mk, 'Mata Kuliah berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Deactivate Mata Kuliah
     * DELETE /api/matakuliah/:kode_mk/:id_kurikulum
     */
    public function delete(string $kodeMk, string $idKurikulum): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();

            $this->service->deactivate($kodeMk, (int) $idKurikulum, $user['id_user']);

            Response::success(null, 'Mata Kuliah berhasil dinonaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
