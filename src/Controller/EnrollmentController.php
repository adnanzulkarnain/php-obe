<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\EnrollmentService;
use App\Middleware\AuthMiddleware;

/**
 * Enrollment (KRS) Controller
 */
class EnrollmentController
{
    private EnrollmentService $service;

    public function __construct()
    {
        $this->service = new EnrollmentService();
    }

    /**
     * Get enrollment by ID
     * GET /api/enrollment/:id
     */
    public function show(string $id): void
    {
        try {
            $enrollment = $this->service->getById((int)$id);
            Response::success($enrollment);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Enroll mahasiswa to kelas
     * POST /api/enrollment
     */
    public function enroll(): void
    {
        try {
            AuthMiddleware::requireRole('mahasiswa', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only(['nim', 'id_kelas', 'status']);

            // If user is mahasiswa, force nim to be their own
            if ($user['role'] === 'mahasiswa') {
                $data['nim'] = $user['username']; // Assuming username is NIM for mahasiswa
            }

            $enrollment = $this->service->enroll($data, $user['id_user']);

            Response::success($enrollment, 'Berhasil mendaftar ke kelas', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Bulk enrollment
     * POST /api/enrollment/bulk
     */
    public function bulkEnroll(): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $enrollments = Request::input('enrollments');

            if (!is_array($enrollments) || empty($enrollments)) {
                Response::error('Enrollments wajib diisi dan harus berupa array', 400);
                return;
            }

            $results = $this->service->bulkEnroll($enrollments, $user['id_user']);

            Response::success($results, 'Bulk enrollment selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Drop enrollment
     * POST /api/enrollment/:id/drop
     */
    public function drop(string $id): void
    {
        try {
            AuthMiddleware::requireRole('mahasiswa', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();

            // If user is mahasiswa, verify it's their enrollment
            if ($user['role'] === 'mahasiswa') {
                $enrollment = $this->service->getById((int)$id);
                if ($enrollment['nim'] !== $user['username']) {
                    Response::error('Anda tidak memiliki akses untuk drop enrollment ini', 403);
                    return;
                }
            }

            $this->service->drop((int)$id, $user['id_user']);

            Response::success(null, 'Berhasil drop dari kelas');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update enrollment status
     * PUT /api/enrollment/:id/status
     */
    public function updateStatus(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $status = Request::input('status');

            if (!$status) {
                Response::error('Status wajib diisi', 400);
                return;
            }

            $enrollment = $this->service->updateStatus((int)$id, $status, $user['id_user']);

            Response::success($enrollment, 'Status enrollment berhasil diubah');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update grades
     * PUT /api/enrollment/:id/grades
     */
    public function updateGrades(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $nilaiAkhir = Request::input('nilai_akhir');
            $nilaiHuruf = Request::input('nilai_huruf');

            if ($nilaiAkhir === null || $nilaiHuruf === null) {
                Response::error('Nilai akhir dan nilai huruf wajib diisi', 400);
                return;
            }

            $enrollment = $this->service->updateGrades(
                (int)$id,
                (float)$nilaiAkhir,
                $nilaiHuruf,
                $user['id_user']
            );

            Response::success($enrollment, 'Nilai berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get enrollments by mahasiswa
     * GET /api/mahasiswa/:nim/enrollment
     */
    public function getByMahasiswa(string $nim): void
    {
        try {
            $user = AuthMiddleware::user();

            // If user is mahasiswa, verify it's their own NIM
            if ($user['role'] === 'mahasiswa' && $user['username'] !== $nim) {
                Response::error('Anda tidak memiliki akses untuk melihat enrollment mahasiswa lain', 403);
                return;
            }

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

            $enrollments = $this->service->getByMahasiswa($nim, $filters);

            Response::success($enrollments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get enrollments by kelas
     * GET /api/kelas/:id/enrollment
     */
    public function getByKelas(string $idKelas): void
    {
        try {
            $status = Request::input('status');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }

            $enrollments = $this->service->getByKelas((int)$idKelas, $filters);

            Response::success($enrollments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get KRS for a mahasiswa
     * GET /api/mahasiswa/:nim/krs
     */
    public function getKRS(string $nim): void
    {
        try {
            $user = AuthMiddleware::user();

            // If user is mahasiswa, verify it's their own NIM
            if ($user['role'] === 'mahasiswa' && $user['username'] !== $nim) {
                Response::error('Anda tidak memiliki akses untuk melihat KRS mahasiswa lain', 403);
                return;
            }

            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');

            if (!$semester || !$tahunAjaran) {
                Response::error('Semester dan tahun_ajaran wajib diisi', 400);
                return;
            }

            $krs = $this->service->getKRS($nim, $semester, $tahunAjaran);

            Response::success($krs);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get academic transcript
     * GET /api/mahasiswa/:nim/transcript
     */
    public function getTranscript(string $nim): void
    {
        try {
            $user = AuthMiddleware::user();

            // If user is mahasiswa, verify it's their own NIM
            if ($user['role'] === 'mahasiswa' && $user['username'] !== $nim) {
                Response::error('Anda tidak memiliki akses untuk melihat transkrip mahasiswa lain', 403);
                return;
            }

            $transcript = $this->service->getTranscript($nim);

            Response::success($transcript);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kelas statistics
     * GET /api/kelas/:id/statistics
     */
    public function getKelasStatistics(string $idKelas): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $stats = $this->service->getKelasStatistics((int)$idKelas);

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Validate enrollment capacity
     * GET /api/mahasiswa/:nim/enrollment-capacity
     */
    public function validateCapacity(string $nim): void
    {
        try {
            $user = AuthMiddleware::user();

            // If user is mahasiswa, verify it's their own NIM
            if ($user['role'] === 'mahasiswa' && $user['username'] !== $nim) {
                Response::error('Anda tidak memiliki akses untuk melihat kapasitas mahasiswa lain', 403);
                return;
            }

            $semester = Request::input('semester');
            $tahunAjaran = Request::input('tahun_ajaran');
            $additionalSKS = Request::input('additional_sks', 0);

            if (!$semester || !$tahunAjaran) {
                Response::error('Semester dan tahun_ajaran wajib diisi', 400);
                return;
            }

            $validation = $this->service->validateEnrollmentCapacity(
                $nim,
                $semester,
                $tahunAjaran,
                (int)$additionalSKS
            );

            Response::success($validation);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
