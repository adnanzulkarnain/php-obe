<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Enrollment;
use App\Repository\EnrollmentRepository;
use App\Repository\KelasRepository;

/**
 * Enrollment Service
 */
class EnrollmentService
{
    private EnrollmentRepository $repository;
    private KelasRepository $kelasRepository;
    private AuditLogService $auditLog;

    // Business rules constants
    private const MAX_SKS_PER_SEMESTER = 24;
    private const MIN_SKS_PER_SEMESTER = 12;

    public function __construct()
    {
        $this->repository = new EnrollmentRepository();
        $this->kelasRepository = new KelasRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Enroll mahasiswa to kelas
     */
    public function enroll(array $data, int $userId): array
    {
        // Create entity and validate
        $enrollment = Enrollment::fromArray($data);
        $errors = $enrollment->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if kelas exists
        $kelas = $this->kelasRepository->findByIdWithDetails($enrollment->id_kelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Business rule: Kelas must be open for enrollment
        if ($kelas['status'] !== 'open') {
            throw new \Exception('Kelas tidak terbuka untuk pendaftaran. Status kelas: ' . $kelas['status'], 400);
        }

        // Business rule: Check kelas capacity
        if (!$this->kelasRepository->hasCapacity($enrollment->id_kelas)) {
            throw new \Exception('Kelas sudah penuh. Kapasitas: ' . $kelas['kapasitas'] . ', Terisi: ' . $kelas['kuota_terisi'], 400);
        }

        // Check if already enrolled
        if ($this->repository->isEnrolled($enrollment->nim, $enrollment->id_kelas)) {
            throw new \Exception('Mahasiswa sudah terdaftar di kelas ini', 400);
        }

        // Get semester info from kelas
        $semester = $kelas['semester'];
        $tahunAjaran = $kelas['tahun_ajaran'];

        // Business rule: Check max SKS per semester
        $currentSKS = $this->repository->getTotalSKSEnrolled($enrollment->nim, $semester, $tahunAjaran);
        $newTotalSKS = $currentSKS + $kelas['sks'];

        if ($newTotalSKS > self::MAX_SKS_PER_SEMESTER) {
            throw new \Exception(
                sprintf(
                    'Melebihi batas maksimal SKS per semester. Saat ini: %d SKS, MK ini: %d SKS, Maksimal: %d SKS',
                    $currentSKS,
                    $kelas['sks'],
                    self::MAX_SKS_PER_SEMESTER
                ),
                400
            );
        }

        // Create enrollment
        $this->repository->db->beginTransaction();

        try {
            $enrollmentData = [
                'nim' => $enrollment->nim,
                'id_kelas' => $enrollment->id_kelas,
                'tanggal_daftar' => date('Y-m-d'),
                'status' => $enrollment->status ?? 'aktif',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $idEnrollment = $this->repository->create($enrollmentData);

            // Update kelas kuota
            $this->kelasRepository->incrementKuotaTerisi($enrollment->id_kelas);

            // Audit log
            $this->auditLog->log(
                'enrollment',
                $idEnrollment,
                'INSERT',
                null,
                $enrollmentData,
                $userId
            );

            $this->repository->db->commit();

            return $this->repository->findByIdWithDetails($idEnrollment);
        } catch (\Exception $e) {
            $this->repository->db->rollBack();

            // Check if it's the BR-K04 validation error from database trigger
            if (strpos($e->getMessage(), 'BR-K04') !== false) {
                throw new \Exception('Mahasiswa hanya dapat mendaftar di kelas yang sesuai dengan kurikulumnya (BR-K04)', 400);
            }

            throw $e;
        }
    }

    /**
     * Bulk enrollment
     */
    public function bulkEnroll(array $enrollments, int $userId): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($enrollments as $data) {
            try {
                $result = $this->enroll($data, $userId);
                $results['success'][] = [
                    'nim' => $data['nim'],
                    'id_kelas' => $data['id_kelas'],
                    'enrollment' => $result
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'nim' => $data['nim'],
                    'id_kelas' => $data['id_kelas'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Drop enrollment
     */
    public function drop(int $idEnrollment, int $userId): void
    {
        $enrollment = $this->repository->find($idEnrollment);

        if (!$enrollment) {
            throw new \Exception('Enrollment tidak ditemukan', 404);
        }

        // Business rule: Can only drop if status is aktif or mengulang
        if (!$this->repository->canDrop($idEnrollment)) {
            throw new \Exception('Tidak dapat drop enrollment dengan status: ' . $enrollment['status'], 400);
        }

        // Get kelas info for audit
        $kelas = $this->kelasRepository->find($enrollment['id_kelas']);

        $this->repository->db->beginTransaction();

        try {
            // Update status to drop
            $this->repository->drop($idEnrollment);

            // Update kelas kuota
            $this->kelasRepository->decrementKuotaTerisi($enrollment['id_kelas']);

            // Audit log
            $this->auditLog->log(
                'enrollment',
                $idEnrollment,
                'UPDATE',
                ['status' => $enrollment['status']],
                ['status' => 'drop'],
                $userId
            );

            $this->repository->db->commit();
        } catch (\Exception $e) {
            $this->repository->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(int $idEnrollment, string $status, int $userId): array
    {
        $enrollment = $this->repository->find($idEnrollment);

        if (!$enrollment) {
            throw new \Exception('Enrollment tidak ditemukan', 404);
        }

        // Validate status
        if (!in_array($status, ['aktif', 'mengulang', 'drop', 'lulus'])) {
            throw new \Exception('Status tidak valid', 400);
        }

        // Business rule: Cannot change from lulus to other status
        if ($enrollment['status'] === 'lulus' && $status !== 'lulus') {
            throw new \Exception('Tidak dapat mengubah status dari lulus ke status lain', 400);
        }

        // Business rule: To set status to lulus, must have nilai
        if ($status === 'lulus' && $enrollment['nilai_akhir'] === null) {
            throw new \Exception('Nilai akhir harus diisi sebelum status dapat diubah menjadi lulus', 400);
        }

        $oldStatus = $enrollment['status'];

        // Update status
        $this->repository->updateStatus($idEnrollment, $status);

        // Update kelas kuota if needed
        if ($oldStatus === 'drop' && in_array($status, ['aktif', 'mengulang'])) {
            // Re-enrolling from drop
            $this->kelasRepository->incrementKuotaTerisi($enrollment['id_kelas']);
        } elseif (in_array($oldStatus, ['aktif', 'mengulang']) && $status === 'drop') {
            // Dropping from active
            $this->kelasRepository->decrementKuotaTerisi($enrollment['id_kelas']);
        }

        // Audit log
        $this->auditLog->log(
            'enrollment',
            $idEnrollment,
            'UPDATE',
            ['status' => $oldStatus],
            ['status' => $status],
            $userId
        );

        return $this->repository->findByIdWithDetails($idEnrollment);
    }

    /**
     * Update grades
     */
    public function updateGrades(int $idEnrollment, float $nilaiAkhir, string $nilaiHuruf, int $userId): array
    {
        $enrollment = $this->repository->find($idEnrollment);

        if (!$enrollment) {
            throw new \Exception('Enrollment tidak ditemukan', 404);
        }

        // Validate nilai_akhir
        if ($nilaiAkhir < 0 || $nilaiAkhir > 100) {
            throw new \Exception('Nilai akhir harus berada dalam rentang 0-100', 400);
        }

        // Validate nilai_huruf
        $validGrades = ['A', 'A-', 'AB', 'B+', 'B', 'B-', 'BC', 'C+', 'C', 'C-', 'D', 'E'];
        if (!in_array($nilaiHuruf, $validGrades)) {
            throw new \Exception('Nilai huruf tidak valid', 400);
        }

        // Update grades
        $this->repository->updateGrades($idEnrollment, $nilaiAkhir, $nilaiHuruf);

        // Audit log
        $this->auditLog->log(
            'enrollment',
            $idEnrollment,
            'UPDATE',
            [
                'nilai_akhir' => $enrollment['nilai_akhir'],
                'nilai_huruf' => $enrollment['nilai_huruf']
            ],
            [
                'nilai_akhir' => $nilaiAkhir,
                'nilai_huruf' => $nilaiHuruf
            ],
            $userId
        );

        return $this->repository->findByIdWithDetails($idEnrollment);
    }

    /**
     * Get enrollment by ID
     */
    public function getById(int $idEnrollment): array
    {
        $enrollment = $this->repository->findByIdWithDetails($idEnrollment);

        if (!$enrollment) {
            throw new \Exception('Enrollment tidak ditemukan', 404);
        }

        return $enrollment;
    }

    /**
     * Get enrollments by mahasiswa
     */
    public function getByMahasiswa(string $nim, ?array $filters = []): array
    {
        return $this->repository->findByMahasiswa($nim, $filters);
    }

    /**
     * Get enrollments by kelas
     */
    public function getByKelas(int $idKelas, ?array $filters = []): array
    {
        return $this->repository->findByKelas($idKelas, $filters);
    }

    /**
     * Get KRS for a mahasiswa in a semester
     */
    public function getKRS(string $nim, string $semester, string $tahunAjaran): array
    {
        $enrollments = $this->repository->findBySemesterTahunAjaran($semester, $tahunAjaran, $nim);
        $summary = $this->repository->getKRSSummary($nim, $semester, $tahunAjaran);

        return [
            'enrollments' => $enrollments,
            'summary' => $summary
        ];
    }

    /**
     * Get KRS summary
     */
    public function getKRSSummary(string $nim, string $semester, string $tahunAjaran): array
    {
        return $this->repository->getKRSSummary($nim, $semester, $tahunAjaran);
    }

    /**
     * Get academic transcript
     */
    public function getTranscript(string $nim): array
    {
        $enrollments = $this->repository->getTranscript($nim);

        // Group by semester
        $grouped = [];
        $totalSKS = 0;
        $totalSKSLulus = 0;
        $totalNilai = 0;
        $countLulus = 0;

        foreach ($enrollments as $enrollment) {
            $key = $enrollment['tahun_ajaran'] . ' - ' . $enrollment['semester'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'tahun_ajaran' => $enrollment['tahun_ajaran'],
                    'semester' => $enrollment['semester'],
                    'courses' => [],
                    'total_sks' => 0,
                    'sks_lulus' => 0
                ];
            }

            $grouped[$key]['courses'][] = $enrollment;
            $grouped[$key]['total_sks'] += $enrollment['sks'];

            if ($enrollment['status'] === 'lulus') {
                $grouped[$key]['sks_lulus'] += $enrollment['sks'];
                $totalSKSLulus += $enrollment['sks'];

                if ($enrollment['nilai_akhir'] !== null) {
                    $totalNilai += $enrollment['nilai_akhir'];
                    $countLulus++;
                }
            }

            $totalSKS += $enrollment['sks'];
        }

        return [
            'by_semester' => array_values($grouped),
            'summary' => [
                'total_sks' => $totalSKS,
                'total_sks_lulus' => $totalSKSLulus,
                'ipk' => $countLulus > 0 ? round($totalNilai / $countLulus, 2) : 0,
                'total_mk' => count($enrollments),
                'mk_lulus' => $countLulus
            ]
        ];
    }

    /**
     * Get kelas statistics
     */
    public function getKelasStatistics(int $idKelas): array
    {
        return $this->repository->getKelasStatistics($idKelas);
    }

    /**
     * Validate enrollment capacity
     */
    public function validateEnrollmentCapacity(string $nim, string $semester, string $tahunAjaran, int $additionalSKS = 0): array
    {
        $currentSKS = $this->repository->getTotalSKSEnrolled($nim, $semester, $tahunAjaran);
        $newTotalSKS = $currentSKS + $additionalSKS;

        return [
            'current_sks' => $currentSKS,
            'additional_sks' => $additionalSKS,
            'new_total_sks' => $newTotalSKS,
            'max_sks' => self::MAX_SKS_PER_SEMESTER,
            'min_sks' => self::MIN_SKS_PER_SEMESTER,
            'can_enroll' => $newTotalSKS <= self::MAX_SKS_PER_SEMESTER,
            'remaining_sks' => self::MAX_SKS_PER_SEMESTER - $newTotalSKS,
            'below_minimum' => $newTotalSKS < self::MIN_SKS_PER_SEMESTER
        ];
    }
}
