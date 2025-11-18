<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\PustakaRepository;
use App\Repository\MediaPembelajaranRepository;
use App\Repository\RPSRepository;
use App\Service\AuditLogService;
use App\Middleware\AuthMiddleware;

/**
 * Sumber Belajar Controller
 * Handles learning resources (Pustaka & Media Pembelajaran)
 */
class SumberBelajarController
{
    private PustakaRepository $pustakaRepo;
    private MediaPembelajaranRepository $mediaRepo;
    private RPSRepository $rpsRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->pustakaRepo = new PustakaRepository();
        $this->mediaRepo = new MediaPembelajaranRepository();
        $this->rpsRepo = new RPSRepository();
        $this->auditLog = new AuditLogService();
    }

    // ========== PUSTAKA ENDPOINTS ==========

    /**
     * Get pustaka by RPS
     * GET /api/rps/:id/pustaka
     */
    public function getPustakaByRPS(string $idRps): void
    {
        try {
            $pustaka = $this->pustakaRepo->findByRPS((int)$idRps);
            Response::success($pustaka);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get pustaka by ID
     * GET /api/pustaka/:id
     */
    public function getPustaka(string $id): void
    {
        try {
            $pustaka = $this->pustakaRepo->find((int)$id);

            if (!$pustaka) {
                Response::error('Pustaka tidak ditemukan', 404);
                return;
            }

            Response::success($pustaka);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Create pustaka
     * POST /api/pustaka
     */
    public function createPustaka(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_rps', 'jenis', 'referensi', 'penulis', 'tahun', 'penerbit', 'isbn', 'url']);

            // Validate required
            if (empty($data['id_rps']) || empty($data['jenis']) || empty($data['referensi'])) {
                Response::error('id_rps, jenis, dan referensi wajib diisi', 400);
                return;
            }

            // Validate jenis
            if (!in_array($data['jenis'], ['utama', 'pendukung'])) {
                Response::error('jenis harus utama atau pendukung', 400);
                return;
            }

            // Check if RPS exists
            $rps = $this->rpsRepo->find((int)$data['id_rps']);
            if (!$rps) {
                Response::error('RPS tidak ditemukan', 404);
                return;
            }

            $pustakaData = [
                'id_rps' => (int)$data['id_rps'],
                'jenis' => $data['jenis'],
                'referensi' => $data['referensi'],
                'penulis' => $data['penulis'] ?? null,
                'tahun' => !empty($data['tahun']) ? (int)$data['tahun'] : null,
                'penerbit' => $data['penerbit'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'url' => $data['url'] ?? null
            ];

            $idPustaka = $this->pustakaRepo->createPustaka($pustakaData);

            $this->auditLog->log('pustaka', $idPustaka, 'INSERT', null, $pustakaData, $user['id_user']);

            $pustaka = $this->pustakaRepo->find($idPustaka);
            Response::success($pustaka, 'Pustaka berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update pustaka
     * PUT /api/pustaka/:id
     */
    public function updatePustaka(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $existing = $this->pustakaRepo->find((int)$id);

            if (!$existing) {
                Response::error('Pustaka tidak ditemukan', 404);
                return;
            }

            $data = Request::only(['jenis', 'referensi', 'penulis', 'tahun', 'penerbit', 'isbn', 'url']);

            $updateData = [];
            $allowedFields = ['jenis', 'referensi', 'penulis', 'tahun', 'penerbit', 'isbn', 'url'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                Response::error('Tidak ada data yang diupdate', 400);
                return;
            }

            $this->pustakaRepo->updatePustaka((int)$id, $updateData);

            $this->auditLog->log('pustaka', (int)$id, 'UPDATE', $existing, $updateData, $user['id_user']);

            $pustaka = $this->pustakaRepo->find((int)$id);
            Response::success($pustaka, 'Pustaka berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete pustaka
     * DELETE /api/pustaka/:id
     */
    public function deletePustaka(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $pustaka = $this->pustakaRepo->find((int)$id);

            if (!$pustaka) {
                Response::error('Pustaka tidak ditemukan', 404);
                return;
            }

            $this->pustakaRepo->deletePustaka((int)$id);

            $this->auditLog->log('pustaka', (int)$id, 'DELETE', $pustaka, null, $user['id_user']);

            Response::success(null, 'Pustaka berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // ========== MEDIA PEMBELAJARAN ENDPOINTS ==========

    /**
     * Get media by RPS
     * GET /api/rps/:id/media-pembelajaran
     */
    public function getMediaByRPS(string $idRps): void
    {
        try {
            $media = $this->mediaRepo->findByRPS((int)$idRps);
            Response::success($media);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get media by ID
     * GET /api/media-pembelajaran/:id
     */
    public function getMedia(string $id): void
    {
        try {
            $media = $this->mediaRepo->find((int)$id);

            if (!$media) {
                Response::error('Media pembelajaran tidak ditemukan', 404);
                return;
            }

            Response::success($media);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Create media pembelajaran
     * POST /api/media-pembelajaran
     */
    public function createMedia(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_rps', 'kategori', 'nama', 'deskripsi']);

            // Validate required
            if (empty($data['id_rps']) || empty($data['kategori']) || empty($data['nama'])) {
                Response::error('id_rps, kategori, dan nama wajib diisi', 400);
                return;
            }

            // Validate kategori
            if (!in_array($data['kategori'], ['software', 'hardware', 'platform'])) {
                Response::error('kategori harus software, hardware, atau platform', 400);
                return;
            }

            // Check if RPS exists
            $rps = $this->rpsRepo->find((int)$data['id_rps']);
            if (!$rps) {
                Response::error('RPS tidak ditemukan', 404);
                return;
            }

            $mediaData = [
                'id_rps' => (int)$data['id_rps'],
                'kategori' => $data['kategori'],
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?? null
            ];

            $idMedia = $this->mediaRepo->createMedia($mediaData);

            $this->auditLog->log('media_pembelajaran', $idMedia, 'INSERT', null, $mediaData, $user['id_user']);

            $media = $this->mediaRepo->find($idMedia);
            Response::success($media, 'Media pembelajaran berhasil ditambahkan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Update media pembelajaran
     * PUT /api/media-pembelajaran/:id
     */
    public function updateMedia(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $existing = $this->mediaRepo->find((int)$id);

            if (!$existing) {
                Response::error('Media pembelajaran tidak ditemukan', 404);
                return;
            }

            $data = Request::only(['kategori', 'nama', 'deskripsi']);

            $updateData = [];
            $allowedFields = ['kategori', 'nama', 'deskripsi'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                Response::error('Tidak ada data yang diupdate', 400);
                return;
            }

            $this->mediaRepo->updateMedia((int)$id, $updateData);

            $this->auditLog->log('media_pembelajaran', (int)$id, 'UPDATE', $existing, $updateData, $user['id_user']);

            $media = $this->mediaRepo->find((int)$id);
            Response::success($media, 'Media pembelajaran berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete media pembelajaran
     * DELETE /api/media-pembelajaran/:id
     */
    public function deleteMedia(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $media = $this->mediaRepo->find((int)$id);

            if (!$media) {
                Response::error('Media pembelajaran tidak ditemukan', 404);
                return;
            }

            $this->mediaRepo->deleteMedia((int)$id);

            $this->auditLog->log('media_pembelajaran', (int)$id, 'DELETE', $media, null, $user['id_user']);

            Response::success(null, 'Media pembelajaran berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // ========== STATISTICS ENDPOINTS ==========

    /**
     * Get statistics for RPS resources
     * GET /api/rps/:id/sumber-belajar-stats
     */
    public function getStats(string $idRps): void
    {
        try {
            $pustakaStats = $this->pustakaRepo->getStatistics((int)$idRps);
            $mediaStats = $this->mediaRepo->getStatistics((int)$idRps);

            Response::success([
                'pustaka' => $pustakaStats,
                'media' => $mediaStats
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
