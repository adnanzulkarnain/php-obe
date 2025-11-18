<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\PenilaianService;
use App\Middleware\AuthMiddleware;

/**
 * Penilaian Controller
 */
class PenilaianController
{
    private PenilaianService $service;

    public function __construct()
    {
        $this->service = new PenilaianService();
    }

    // ===================================
    // TEMPLATE PENILAIAN ENDPOINTS
    // ===================================

    /**
     * Get templates by RPS
     * GET /api/rps/:id/template-penilaian
     */
    public function getTemplatesByRPS(string $idRps): void
    {
        try {
            $templates = $this->service->getTemplatesByRPS((int)$idRps);
            Response::success($templates);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create template penilaian
     * POST /api/template-penilaian
     */
    public function createTemplate(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_rps', 'id_cpmk', 'id_jenis', 'bobot']);

            $template = $this->service->createTemplate($data, $user['id_user']);

            Response::success($template, 'Template penilaian berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Validate template bobot
     * GET /api/rps/:id/validate-template
     */
    public function validateTemplateBobot(string $idRps): void
    {
        try {
            $validation = $this->service->validateTemplateBobot((int)$idRps);
            Response::success($validation);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // KOMPONEN PENILAIAN ENDPOINTS
    // ===================================

    /**
     * Get komponen by kelas
     * GET /api/kelas/:id/komponen-penilaian
     */
    public function getKomponenByKelas(string $idKelas): void
    {
        try {
            $komponen = $this->service->getKomponenByKelas((int)$idKelas);
            Response::success($komponen);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create komponen penilaian
     * POST /api/komponen-penilaian
     */
    public function createKomponen(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_kelas',
                'id_template',
                'nama_komponen',
                'deskripsi',
                'tanggal_pelaksanaan',
                'deadline',
                'bobot_realisasi',
                'nilai_maksimal'
            ]);

            $komponen = $this->service->createKomponen($data, $user['id_user']);

            Response::success($komponen, 'Komponen penilaian berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update komponen penilaian
     * PUT /api/komponen-penilaian/:id
     */
    public function updateKomponen(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'nama_komponen',
                'deskripsi',
                'tanggal_pelaksanaan',
                'deadline',
                'bobot_realisasi',
                'nilai_maksimal'
            ]);

            $komponen = $this->service->updateKomponen((int)$id, $data, $user['id_user']);

            Response::success($komponen, 'Komponen penilaian berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete komponen penilaian
     * DELETE /api/komponen-penilaian/:id
     */
    public function deleteKomponen(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->deleteKomponen((int)$id, $user['id_user']);

            Response::success(null, 'Komponen penilaian berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // NILAI INPUT ENDPOINTS
    // ===================================

    /**
     * Input nilai (single)
     * POST /api/nilai
     */
    public function inputNilai(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['username']; // Assuming username is id_dosen

            $idEnrollment = Request::input('id_enrollment');
            $idKomponen = Request::input('id_komponen');
            $nilaiMentah = Request::input('nilai_mentah');
            $catatan = Request::input('catatan');

            if ($idEnrollment === null || $idKomponen === null || $nilaiMentah === null) {
                Response::error('id_enrollment, id_komponen, dan nilai_mentah wajib diisi', 400);
                return;
            }

            $nilai = $this->service->inputNilai(
                (int)$idEnrollment,
                (int)$idKomponen,
                (float)$nilaiMentah,
                $catatan,
                $user['id_user'],
                $idDosen
            );

            Response::success($nilai, 'Nilai berhasil diinput', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Bulk input nilai
     * POST /api/nilai/bulk
     */
    public function bulkInputNilai(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['username'];

            $idKomponen = Request::input('id_komponen');
            $nilaiList = Request::input('nilai_list');

            if ($idKomponen === null || !is_array($nilaiList)) {
                Response::error('id_komponen dan nilai_list wajib diisi', 400);
                return;
            }

            $results = $this->service->bulkInputNilai(
                (int)$idKomponen,
                $nilaiList,
                $user['id_user'],
                $idDosen
            );

            Response::success($results, 'Bulk input nilai selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get nilai by enrollment
     * GET /api/enrollment/:id/nilai
     */
    public function getNilaiByEnrollment(string $idEnrollment): void
    {
        try {
            $nilai = $this->service->getNilaiByEnrollment((int)$idEnrollment);
            Response::success($nilai);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get nilai by komponen
     * GET /api/komponen-penilaian/:id/nilai
     */
    public function getNilaiByKomponen(string $idKomponen): void
    {
        try {
            $nilai = $this->service->getNilaiByKomponen((int)$idKomponen);
            Response::success($nilai);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ===================================
    // SUMMARY & STATISTICS ENDPOINTS
    // ===================================

    /**
     * Get nilai summary by kelas
     * GET /api/kelas/:id/nilai-summary
     */
    public function getNilaiSummaryByKelas(string $idKelas): void
    {
        try {
            $summary = $this->service->getNilaiSummaryByKelas((int)$idKelas);
            Response::success($summary);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get komponen statistics
     * GET /api/komponen-penilaian/:id/statistics
     */
    public function getKomponenStatistics(string $idKomponen): void
    {
        try {
            $stats = $this->service->getKomponenStatistics((int)$idKomponen);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Calculate CPMK achievement
     * GET /api/enrollment/:id/cpmk-achievement/:id_cpmk
     */
    public function calculateCPMKAchievement(string $idEnrollment, string $idCpmk): void
    {
        try {
            $achievement = $this->service->calculateCPMKAchievement((int)$idEnrollment, (int)$idCpmk);

            Response::success([
                'id_enrollment' => (int)$idEnrollment,
                'id_cpmk' => (int)$idCpmk,
                'achievement' => $achievement
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Recalculate kelas grades
     * POST /api/kelas/:id/recalculate-grades
     */
    public function recalculateKelasGrades(string $idKelas): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $results = $this->service->recalculateKelasGrades((int)$idKelas, $user['id_user']);

            Response::success($results, 'Recalculation selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get all jenis penilaian
     * GET /api/jenis-penilaian
     */
    public function getAllJenisPenilaian(): void
    {
        try {
            $jenis = $this->service->getAllJenisPenilaian();
            Response::success($jenis);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
