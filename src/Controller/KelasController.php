<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\KelasService;
use App\Middleware\AuthMiddleware;

/**
 * Kelas Controller
 */
class KelasController
{
    private KelasService $service;

    public function __construct()
    {
        $this->service = new KelasService();
    }

    /**
     * Get kelas list with filters
     * GET /api/kelas?id_kurikulum=1&semester=Ganjil&tahun_ajaran=2024/2025
     */
    public function index(): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');
            $kodeMk = Request::input('kode_mk');
            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');
            $status = Request::input('status');

            $filters = [];
            if ($semester) {
                $filters['semester'] = $semester;
            }
            if ($tahunAjaran) {
                $filters['tahun_ajaran'] = $tahunAjaran;
            }
            if ($status) {
                $filters['status'] = $status;
            }

            if ($kodeMk && $idKurikulum) {
                // Get by mata kuliah
                $kelas = $this->service->getByMataKuliah($kodeMk, (int)$idKurikulum, $filters);
            } elseif ($idKurikulum) {
                // Get by kurikulum
                $kelas = $this->service->getByKurikulum((int)$idKurikulum, $filters);
            } elseif ($semester && $tahunAjaran) {
                // Get by semester and tahun ajaran
                $kelas = $this->service->getBySemesterTahunAjaran($semester, $tahunAjaran, $idKurikulum ? (int)$idKurikulum : null);
            } else {
                Response::error('Minimal id_kurikulum atau kombinasi semester+tahun_ajaran harus diisi', 400);
                return;
            }

            Response::success($kelas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kelas by ID with details
     * GET /api/kelas/:id
     */
    public function show(string $id): void
    {
        try {
            $kelas = $this->service->getById((int)$id);
            Response::success($kelas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create kelas
     * POST /api/kelas
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'kode_mk',
                'id_kurikulum',
                'id_rps',
                'nama_kelas',
                'semester',
                'tahun_ajaran',
                'kapasitas',
                'hari',
                'jam_mulai',
                'jam_selesai',
                'ruangan',
                'status'
            ]);

            $kelas = $this->service->create($data, $user['id_user']);

            Response::success($kelas, 'Kelas berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update kelas
     * PUT /api/kelas/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'nama_kelas',
                'kapasitas',
                'hari',
                'jam_mulai',
                'jam_selesai',
                'ruangan'
            ]);

            $kelas = $this->service->update((int)$id, $data, $user['id_user']);

            Response::success($kelas, 'Kelas berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Change kelas status
     * POST /api/kelas/:id/status
     */
    public function changeStatus(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $status = Request::input('status');

            if (!$status) {
                Response::error('Status wajib diisi', 400);
                return;
            }

            $kelas = $this->service->changeStatus((int)$id, $status, $user['id_user']);

            Response::success($kelas, 'Status kelas berhasil diubah');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete kelas
     * DELETE /api/kelas/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'Kelas berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get teaching assignments for a kelas
     * GET /api/kelas/:id/dosen
     */
    public function getTeachingAssignments(string $id): void
    {
        try {
            $assignments = $this->service->getTeachingAssignments((int)$id);
            Response::success($assignments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Assign dosen to kelas
     * POST /api/kelas/:id/dosen
     */
    public function assignDosen(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['id_dosen', 'peran']);

            $assignment = $this->service->assignDosen((int)$id, $data, $user['id_user']);

            Response::success($assignment, 'Dosen berhasil ditugaskan', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update dosen peran
     * PUT /api/kelas/:id/dosen/:id_dosen
     */
    public function updateDosenPeran(string $id, string $idDosen): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $peran = Request::input('peran');

            if (!$peran) {
                Response::error('Peran wajib diisi', 400);
                return;
            }

            $assignment = $this->service->updateDosenPeran((int)$id, $idDosen, $peran, $user['id_user']);

            Response::success($assignment, 'Peran dosen berhasil diubah');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Remove dosen from kelas
     * DELETE /api/kelas/:id/dosen/:id_dosen
     */
    public function removeDosen(string $id, string $idDosen): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->removeDosen((int)$id, $idDosen, $user['id_user']);

            Response::success(null, 'Dosen berhasil dihapus dari kelas');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get teaching assignments for a dosen
     * GET /api/dosen/:id_dosen/kelas
     */
    public function getDosenKelas(string $idDosen): void
    {
        try {
            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');
            $peran = Request::input('peran');

            $filters = [];
            if ($semester) {
                $filters['semester'] = $semester;
            }
            if ($tahunAjaran) {
                $filters['tahun_ajaran'] = $tahunAjaran;
            }
            if ($peran) {
                $filters['peran'] = $peran;
            }

            $assignments = $this->service->getTeachingAssignmentsByDosen($idDosen, $filters);
            Response::success($assignments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get statistics
     * GET /api/kelas/statistics
     */
    public function statistics(): void
    {
        try {
            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');
            $idKurikulum = Request::input('id_kurikulum');

            if (!$semester || !$tahunAjaran) {
                Response::error('Semester dan tahun_ajaran wajib diisi', 400);
                return;
            }

            $stats = $this->service->getStatistics(
                $semester,
                $tahunAjaran,
                $idKurikulum ? (int)$idKurikulum : null
            );

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get teaching load stats for a dosen
     * GET /api/dosen/:id_dosen/teaching-load
     */
    public function getTeachingLoadStats(string $idDosen): void
    {
        try {
            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');

            if (!$semester || !$tahunAjaran) {
                Response::error('Semester dan tahun_ajaran wajib diisi', 400);
                return;
            }

            $stats = $this->service->getTeachingLoadStats($idDosen, $semester, $tahunAjaran);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
