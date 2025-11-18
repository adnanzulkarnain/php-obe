<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\KehadiranService;
use App\Middleware\AuthMiddleware;

/**
 * Kehadiran Controller
 * Handles attendance and class realization endpoints
 */
class KehadiranController
{
    private KehadiranService $service;

    public function __construct()
    {
        $this->service = new KehadiranService();
    }

    // ========== REALISASI PERTEMUAN ENDPOINTS ==========

    /**
     * Get realisasi pertemuan by kelas
     * GET /api/kelas/:id/realisasi-pertemuan
     */
    public function getRealisasiByKelas(string $idKelas): void
    {
        try {
            $realisasi = $this->service->getRealisasiByKelas((int)$idKelas);
            Response::success($realisasi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get realisasi pertemuan by ID with kehadiran
     * GET /api/realisasi-pertemuan/:id
     */
    public function getRealisasiById(string $id): void
    {
        try {
            $realisasi = $this->service->getRealisasiById((int)$id);
            Response::success($realisasi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create realisasi pertemuan
     * POST /api/realisasi-pertemuan
     */
    public function createRealisasi(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['username']; // Assuming username is id_dosen

            $data = Request::only([
                'id_kelas',
                'id_minggu',
                'tanggal_pelaksanaan',
                'materi_disampaikan',
                'metode_digunakan',
                'kendala',
                'catatan_dosen'
            ]);

            $realisasi = $this->service->createRealisasi($data, $user['id_user'], $idDosen);

            Response::success($realisasi, 'Realisasi pertemuan berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update realisasi pertemuan
     * PUT /api/realisasi-pertemuan/:id
     */
    public function updateRealisasi(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_minggu',
                'tanggal_pelaksanaan',
                'materi_disampaikan',
                'metode_digunakan',
                'kendala',
                'catatan_dosen'
            ]);

            $realisasi = $this->service->updateRealisasi((int)$id, $data, $user['id_user']);

            Response::success($realisasi, 'Realisasi pertemuan berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete realisasi pertemuan
     * DELETE /api/realisasi-pertemuan/:id
     */
    public function deleteRealisasi(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->deleteRealisasi((int)$id, $user['id_user']);

            Response::success(null, 'Realisasi pertemuan berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ========== KEHADIRAN ENDPOINTS ==========

    /**
     * Input kehadiran (bulk)
     * POST /api/realisasi-pertemuan/:id/kehadiran
     */
    public function inputKehadiran(string $idRealisasi): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $kehadiranList = Request::input('kehadiran_list');

            if (!is_array($kehadiranList)) {
                Response::error('kehadiran_list wajib berupa array', 400);
                return;
            }

            $results = $this->service->inputKehadiran((int)$idRealisasi, $kehadiranList, $user['id_user']);

            Response::success($results, 'Input kehadiran selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kehadiran by mahasiswa
     * GET /api/mahasiswa/:nim/kehadiran/kelas/:id_kelas
     */
    public function getKehadiranByMahasiswa(string $nim, string $idKelas): void
    {
        try {
            $kehadiran = $this->service->getKehadiranByMahasiswa($nim, (int)$idKelas);
            Response::success($kehadiran);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get attendance summary by kelas
     * GET /api/kelas/:id/attendance-summary
     */
    public function getAttendanceSummary(string $idKelas): void
    {
        try {
            $summary = $this->service->getAttendanceSummary((int)$idKelas);
            Response::success($summary);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
