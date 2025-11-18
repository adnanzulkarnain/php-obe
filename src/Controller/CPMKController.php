<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\CPMKService;
use App\Middleware\AuthMiddleware;

/**
 * CPMK (Capaian Pembelajaran Mata Kuliah) Controller
 */
class CPMKController
{
    private CPMKService $service;

    public function __construct()
    {
        $this->service = new CPMKService();
    }

    /**
     * Get CPMK by RPS
     * GET /api/cpmk?id_rps=1&include_subcpmk=true
     */
    public function index(): void
    {
        try {
            $idRps = Request::input('id_rps');
            $includeSubCPMK = Request::input('include_subcpmk', false);

            if (!$idRps) {
                Response::error('id_rps wajib diisi', 400);
                return;
            }

            $cpmk = $this->service->getByRPS((int)$idRps, (bool)$includeSubCPMK);

            Response::success($cpmk);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get CPMK by ID with full details
     * GET /api/cpmk/:id
     */
    public function show(string $id): void
    {
        try {
            $cpmk = $this->service->getById((int)$id);
            Response::success($cpmk);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create CPMK
     * POST /api/cpmk
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_rps',
                'kode_cpmk',
                'deskripsi',
                'urutan'
            ]);

            $cpmk = $this->service->create($data, $user['id_user']);

            Response::success($cpmk, 'CPMK berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update CPMK
     * PUT /api/cpmk/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'kode_cpmk',
                'deskripsi',
                'urutan'
            ]);

            $cpmk = $this->service->update((int)$id, $data, $user['id_user']);

            Response::success($cpmk, 'CPMK berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete CPMK
     * DELETE /api/cpmk/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'CPMK berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // SUBCPMK ENDPOINTS
    // ===================================

    /**
     * Create SubCPMK
     * POST /api/cpmk/:id/subcpmk
     */
    public function createSubCPMK(string $idCpmk): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'kode_subcpmk',
                'deskripsi',
                'indikator',
                'urutan'
            ]);
            $data['id_cpmk'] = (int)$idCpmk;

            $subcpmk = $this->service->createSubCPMK($data, $user['id_user']);

            Response::success($subcpmk, 'SubCPMK berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update SubCPMK
     * PUT /api/subcpmk/:id
     */
    public function updateSubCPMK(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'kode_subcpmk',
                'deskripsi',
                'indikator',
                'urutan'
            ]);

            $subcpmk = $this->service->updateSubCPMK((int)$id, $data, $user['id_user']);

            Response::success($subcpmk, 'SubCPMK berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete SubCPMK
     * DELETE /api/subcpmk/:id
     */
    public function deleteSubCPMK(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->deleteSubCPMK((int)$id, $user['id_user']);

            Response::success(null, 'SubCPMK berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get SubCPMK by CPMK
     * GET /api/cpmk/:id/subcpmk
     */
    public function getSubCPMK(string $idCpmk): void
    {
        try {
            $subcpmk = $this->service->getSubCPMKByCPMK((int)$idCpmk);
            Response::success($subcpmk);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // CPMK-CPL MAPPING ENDPOINTS
    // ===================================

    /**
     * Map CPMK to CPL
     * POST /api/cpmk/:id/map-cpl
     */
    public function mapToCPL(string $idCpmk): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idCpl = Request::input('id_cpl');
            $bobotKontribusi = Request::input('bobot_kontribusi', 100.0);

            if (!$idCpl) {
                Response::error('id_cpl wajib diisi', 400);
                return;
            }

            $mapping = $this->service->mapToCPL(
                (int)$idCpmk,
                (int)$idCpl,
                (float)$bobotKontribusi,
                $user['id_user']
            );

            Response::success($mapping, 'CPMK berhasil dipetakan ke CPL', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update mapping bobot
     * PUT /api/cpmk-cpl-mapping/:id
     */
    public function updateMappingBobot(string $idRelasi): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $bobotKontribusi = Request::input('bobot_kontribusi');

            if ($bobotKontribusi === null) {
                Response::error('bobot_kontribusi wajib diisi', 400);
                return;
            }

            $mapping = $this->service->updateMappingBobot(
                (int)$idRelasi,
                (float)$bobotKontribusi,
                $user['id_user']
            );

            Response::success($mapping, 'Bobot kontribusi berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete mapping
     * DELETE /api/cpmk-cpl-mapping/:id
     */
    public function deleteMapping(string $idRelasi): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->deleteMapping((int)$idRelasi, $user['id_user']);

            Response::success(null, 'Mapping berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get CPL mappings for CPMK
     * GET /api/cpmk/:id/cpl-mappings
     */
    public function getCPLMappings(string $idCpmk): void
    {
        try {
            $mappings = $this->service->getCPLMappingsByCPMK((int)$idCpmk);
            Response::success($mappings);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get CPMK mappings for CPL
     * GET /api/cpl/:id/cpmk-mappings
     */
    public function getCPMKMappingsByCPL(string $idCpl): void
    {
        try {
            $mappings = $this->service->getCPMKMappingsByCPL((int)$idCpl);
            Response::success($mappings);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // STATISTICS & VALIDATION
    // ===================================

    /**
     * Get RPS statistics
     * GET /api/rps/:id/cpmk-statistics
     */
    public function getRPSStatistics(string $idRps): void
    {
        try {
            $stats = $this->service->getRPSStatistics((int)$idRps);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Validate RPS completeness
     * GET /api/rps/:id/validate-cpmk
     */
    public function validateRPSCompleteness(string $idRps): void
    {
        try {
            $validation = $this->service->validateRPSCompleteness((int)$idRps);
            Response::success($validation);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
