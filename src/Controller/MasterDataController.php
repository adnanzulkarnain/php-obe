<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\FakultasRepository;
use App\Repository\ProdiRepository;
use App\Service\AuditLogService;
use App\Middleware\AuthMiddleware;

/**
 * Master Data Controller
 * Handles Fakultas and Prodi management
 */
class MasterDataController
{
    private FakultasRepository $fakultasRepo;
    private ProdiRepository $prodiRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->fakultasRepo = new FakultasRepository();
        $this->prodiRepo = new ProdiRepository();
        $this->auditLog = new AuditLogService();
    }

    // ============================================
    // FAKULTAS ENDPOINTS
    // ============================================

    /**
     * Get all fakultas
     * GET /api/fakultas
     */
    public function getAllFakultas(): void
    {
        try {
            $fakultas = $this->fakultasRepo->getAll();
            Response::success($fakultas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get fakultas by ID
     * GET /api/fakultas/:id
     */
    public function getFakultas(string $id): void
    {
        try {
            $fakultas = $this->fakultasRepo->findWithDetails($id);

            if (!$fakultas) {
                Response::error('Fakultas tidak ditemukan', 404);
                return;
            }

            Response::success($fakultas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Create fakultas
     * POST /api/fakultas
     */
    public function createFakultas(): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_fakultas', 'nama']);

            if (empty($data['id_fakultas']) || empty($data['nama'])) {
                Response::error('id_fakultas dan nama wajib diisi', 400);
                return;
            }

            if ($this->fakultasRepo->exists($data['id_fakultas'])) {
                Response::error('ID Fakultas sudah digunakan', 400);
                return;
            }

            $idFakultas = $this->fakultasRepo->createFakultas($data);

            $this->auditLog->log('fakultas', $idFakultas, 'INSERT', null, $data, $user['id_user']);

            $fakultas = $this->fakultasRepo->findWithDetails($idFakultas);
            Response::success($fakultas, 'Fakultas berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update fakultas
     * PUT /api/fakultas/:id
     */
    public function updateFakultas(string $id): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $existing = $this->fakultasRepo->find($id);

            if (!$existing) {
                Response::error('Fakultas tidak ditemukan', 404);
                return;
            }

            $data = Request::only(['nama']);

            if (empty($data['nama'])) {
                Response::error('nama wajib diisi', 400);
                return;
            }

            $this->fakultasRepo->updateFakultas($id, $data);

            $this->auditLog->log('fakultas', $id, 'UPDATE', $existing, $data, $user['id_user']);

            $fakultas = $this->fakultasRepo->findWithDetails($id);
            Response::success($fakultas, 'Fakultas berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // ============================================
    // PRODI ENDPOINTS
    // ============================================

    /**
     * Get all prodi
     * GET /api/prodi
     */
    public function getAllProdi(): void
    {
        try {
            $filters = [
                'id_fakultas' => Request::get('id_fakultas')
            ];

            $prodi = $this->prodiRepo->getAll(array_filter($filters));
            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get prodi by ID
     * GET /api/prodi/:id
     */
    public function getProdi(string $id): void
    {
        try {
            $prodi = $this->prodiRepo->findWithDetails($id);

            if (!$prodi) {
                Response::error('Prodi tidak ditemukan', 404);
                return;
            }

            Response::success($prodi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Create prodi
     * POST /api/prodi
     */
    public function createProdi(): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_prodi', 'id_fakultas', 'nama', 'jenjang', 'akreditasi', 'tahun_berdiri']);

            $required = ['id_prodi', 'id_fakultas', 'nama', 'jenjang'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field {$field} wajib diisi", 400);
                    return;
                }
            }

            if ($this->prodiRepo->exists($data['id_prodi'])) {
                Response::error('ID Prodi sudah digunakan', 400);
                return;
            }

            $prodiData = [
                'id_prodi' => $data['id_prodi'],
                'id_fakultas' => $data['id_fakultas'],
                'nama' => $data['nama'],
                'jenjang' => $data['jenjang'],
                'akreditasi' => $data['akreditasi'] ?? null,
                'tahun_berdiri' => !empty($data['tahun_berdiri']) ? (int)$data['tahun_berdiri'] : null
            ];

            $idProdi = $this->prodiRepo->createProdi($prodiData);

            $this->auditLog->log('prodi', $idProdi, 'INSERT', null, $prodiData, $user['id_user']);

            $prodi = $this->prodiRepo->findWithDetails($idProdi);
            Response::success($prodi, 'Prodi berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update prodi
     * PUT /api/prodi/:id
     */
    public function updateProdi(string $id): void
    {
        try {
            AuthMiddleware::requireRole('admin');

            $user = AuthMiddleware::user();
            $existing = $this->prodiRepo->find($id);

            if (!$existing) {
                Response::error('Prodi tidak ditemukan', 404);
                return;
            }

            $data = Request::only(['nama', 'jenjang', 'akreditasi', 'tahun_berdiri']);

            $updateData = [];
            $allowedFields = ['nama', 'jenjang', 'akreditasi', 'tahun_berdiri'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                Response::error('Tidak ada data yang diupdate', 400);
                return;
            }

            $this->prodiRepo->updateProdi($id, $updateData);

            $this->auditLog->log('prodi', $id, 'UPDATE', $existing, $updateData, $user['id_user']);

            $prodi = $this->prodiRepo->findWithDetails($id);
            Response::success($prodi, 'Prodi berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
