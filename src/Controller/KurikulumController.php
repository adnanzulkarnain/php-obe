<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\KurikulumService;
use App\Middleware\AuthMiddleware;

/**
 * Kurikulum Controller
 */
class KurikulumController
{
    private KurikulumService $service;

    public function __construct()
    {
        $this->service = new KurikulumService();
    }

    /**
     * Get all kurikulum for a prodi
     * GET /api/kurikulum
     */
    public function index(): void
    {
        try {
            $idProdi = Request::input('id_prodi');
            $status = Request::input('status');

            if (!$idProdi) {
                Response::error('id_prodi wajib diisi', 400);
            }

            $kurikulum = $this->service->getByProdi($idProdi, $status);

            Response::success($kurikulum);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kurikulum detail
     * GET /api/kurikulum/:id
     */
    public function show(string $id): void
    {
        try {
            $kurikulum = $this->service->getDetail((int) $id);

            Response::success($kurikulum);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create new kurikulum (UC-K01)
     * POST /api/kurikulum
     */
    public function create(): void
    {
        try {
            // Require Kaprodi role
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_prodi',
                'kode_kurikulum',
                'nama_kurikulum',
                'tahun_berlaku',
                'deskripsi'
            ]);

            $kurikulum = $this->service->create($data, $user['id_user']);

            Response::success($kurikulum, 'Kurikulum berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Approve kurikulum (UC-K02)
     * POST /api/kurikulum/:id/approve
     */
    public function approve(string $id): void
    {
        try {
            // Require Kaprodi role
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['nomor_sk', 'tanggal_sk']);

            if (empty($data['nomor_sk']) || empty($data['tanggal_sk'])) {
                Response::validationError([
                    'nomor_sk' => empty($data['nomor_sk']) ? 'Nomor SK wajib diisi' : null,
                    'tanggal_sk' => empty($data['tanggal_sk']) ? 'Tanggal SK wajib diisi' : null,
                ]);
            }

            $kurikulum = $this->service->approve(
                (int) $id,
                $data['nomor_sk'],
                $data['tanggal_sk'],
                $user['id_user']
            );

            Response::success($kurikulum, 'Kurikulum berhasil disetujui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Activate kurikulum (UC-K03)
     * POST /api/kurikulum/:id/activate
     */
    public function activate(string $id): void
    {
        try {
            // Require Kaprodi role
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $setAsPrimary = Request::input('set_as_primary', false);

            $kurikulum = $this->service->activate(
                (int) $id,
                (bool) $setAsPrimary,
                $user['id_user']
            );

            Response::success($kurikulum, 'Kurikulum berhasil diaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Deactivate kurikulum (UC-K09)
     * POST /api/kurikulum/:id/deactivate
     */
    public function deactivate(string $id): void
    {
        try {
            // Require Kaprodi role
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();

            $kurikulum = $this->service->deactivate((int) $id, $user['id_user']);

            Response::success($kurikulum, 'Kurikulum berhasil dinonaktifkan');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Compare multiple kurikulum (UC-K08)
     * GET /api/kurikulum/compare
     */
    public function compare(): void
    {
        try {
            $idsParam = Request::input('ids');

            if (!$idsParam) {
                Response::error('Parameter ids wajib diisi', 400);
            }

            $ids = array_map('intval', explode(',', $idsParam));

            $comparison = $this->service->compare($ids);

            Response::success($comparison);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
