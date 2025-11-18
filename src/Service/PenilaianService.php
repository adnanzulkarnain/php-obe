<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TemplatePenilaian;
use App\Entity\KomponenPenilaian;
use App\Entity\NilaiDetail;
use App\Repository\PenilaianRepository;
use App\Repository\RPSRepository;
use App\Repository\KelasRepository;
use App\Repository\EnrollmentRepository;

/**
 * Penilaian Service
 */
class PenilaianService
{
    private PenilaianRepository $repository;
    private RPSRepository $rpsRepository;
    private KelasRepository $kelasRepository;
    private EnrollmentRepository $enrollmentRepository;
    private AuditLogService $auditLog;

    // Grade conversion table
    private const GRADE_CONVERSION = [
        ['min' => 85, 'max' => 100, 'huruf' => 'A'],
        ['min' => 80, 'max' => 84.99, 'huruf' => 'A-'],
        ['min' => 75, 'max' => 79.99, 'huruf' => 'AB'],
        ['min' => 70, 'max' => 74.99, 'huruf' => 'B+'],
        ['min' => 65, 'max' => 69.99, 'huruf' => 'B'],
        ['min' => 60, 'max' => 64.99, 'huruf' => 'B-'],
        ['min' => 55, 'max' => 59.99, 'huruf' => 'BC'],
        ['min' => 50, 'max' => 54.99, 'huruf' => 'C+'],
        ['min' => 45, 'max' => 49.99, 'huruf' => 'C'],
        ['min' => 40, 'max' => 44.99, 'huruf' => 'C-'],
        ['min' => 35, 'max' => 39.99, 'huruf' => 'D'],
        ['min' => 0, 'max' => 34.99, 'huruf' => 'E'],
    ];

    public function __construct()
    {
        $this->repository = new PenilaianRepository();
        $this->rpsRepository = new RPSRepository();
        $this->kelasRepository = new KelasRepository();
        $this->enrollmentRepository = new EnrollmentRepository();
        $this->auditLog = new AuditLogService();
    }

    // ===================================
    // TEMPLATE PENILAIAN METHODS
    // ===================================

    /**
     * Create template penilaian
     */
    public function createTemplate(array $data, int $userId): array
    {
        $template = TemplatePenilaian::fromArray($data);
        $errors = $template->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if RPS exists
        $rps = $this->rpsRepository->find($template->id_rps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Check if template already exists
        if ($this->repository->templateExists($template->id_rps, $template->id_cpmk, $template->id_jenis)) {
            throw new \Exception('Template untuk kombinasi RPS, CPMK, dan Jenis Penilaian ini sudah ada', 400);
        }

        // Create template
        $templateData = [
            'id_rps' => $template->id_rps,
            'id_cpmk' => $template->id_cpmk,
            'id_jenis' => $template->id_jenis,
            'bobot' => $template->bobot,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idTemplate = $this->repository->create($templateData);

        // Audit log
        $this->auditLog->log('template_penilaian', $idTemplate, 'INSERT', null, $templateData, $userId);

        return $this->repository->find($idTemplate);
    }

    /**
     * Get templates by RPS
     */
    public function getTemplatesByRPS(int $idRps): array
    {
        return $this->repository->findTemplateByRPS($idRps);
    }

    /**
     * Validate template bobot
     */
    public function validateTemplateBobot(int $idRps): array
    {
        $bobotPerCPMK = $this->repository->getTotalBobotPerCPMK($idRps);

        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        foreach ($bobotPerCPMK as $item) {
            $totalBobot = (float)$item['total_bobot'];

            if ($totalBobot != 100) {
                $validation['is_valid'] = false;
                $validation['errors'][] = sprintf(
                    'CPMK ID %d: Total bobot %.2f%% (harus 100%%)',
                    $item['id_cpmk'],
                    $totalBobot
                );
            }
        }

        return $validation;
    }

    // ===================================
    // KOMPONEN PENILAIAN METHODS
    // ===================================

    /**
     * Create komponen penilaian
     */
    public function createKomponen(array $data, int $userId): array
    {
        $komponen = KomponenPenilaian::fromArray($data);
        $errors = $komponen->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if kelas exists
        $kelas = $this->kelasRepository->find($komponen->id_kelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Create komponen
        $komponenData = [
            'id_kelas' => $komponen->id_kelas,
            'id_template' => $komponen->id_template,
            'nama_komponen' => $komponen->nama_komponen,
            'deskripsi' => $komponen->deskripsi,
            'tanggal_pelaksanaan' => $komponen->tanggal_pelaksanaan,
            'deadline' => $komponen->deadline,
            'bobot_realisasi' => $komponen->bobot_realisasi,
            'nilai_maksimal' => $komponen->nilai_maksimal,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idKomponen = $this->repository->createKomponen($komponenData);

        // Audit log
        $this->auditLog->log('komponen_penilaian', $idKomponen, 'INSERT', null, $komponenData, $userId);

        return $this->repository->findKomponen($idKomponen);
    }

    /**
     * Update komponen
     */
    public function updateKomponen(int $idKomponen, array $data, int $userId): array
    {
        $komponen = $this->repository->findKomponen($idKomponen);
        if (!$komponen) {
            throw new \Exception('Komponen penilaian tidak ditemukan', 404);
        }

        $updateData = [
            'nama_komponen' => $data['nama_komponen'] ?? $komponen['nama_komponen'],
            'deskripsi' => $data['deskripsi'] ?? $komponen['deskripsi'],
            'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'] ?? $komponen['tanggal_pelaksanaan'],
            'deadline' => $data['deadline'] ?? $komponen['deadline'],
            'bobot_realisasi' => $data['bobot_realisasi'] ?? $komponen['bobot_realisasi'],
            'nilai_maksimal' => $data['nilai_maksimal'] ?? $komponen['nilai_maksimal'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->repository->updateKomponen($idKomponen, $updateData);

        // Audit log
        $this->auditLog->log('komponen_penilaian', $idKomponen, 'UPDATE', $komponen, $updateData, $userId);

        return $this->repository->findKomponen($idKomponen);
    }

    /**
     * Delete komponen
     */
    public function deleteKomponen(int $idKomponen, int $userId): void
    {
        $komponen = $this->repository->findKomponen($idKomponen);
        if (!$komponen) {
            throw new \Exception('Komponen penilaian tidak ditemukan', 404);
        }

        // Delete komponen (will cascade delete all nilai)
        $this->repository->deleteKomponen($idKomponen);

        // Audit log
        $this->auditLog->log('komponen_penilaian', $idKomponen, 'DELETE', $komponen, null, $userId);
    }

    /**
     * Get komponen by kelas
     */
    public function getKomponenByKelas(int $idKelas): array
    {
        return $this->repository->findKomponenByKelas($idKelas);
    }

    // ===================================
    // NILAI DETAIL METHODS
    // ===================================

    /**
     * Input nilai (single)
     */
    public function inputNilai(int $idEnrollment, int $idKomponen, float $nilaiMentah, ?string $catatan, int $userId, string $idDosen): array
    {
        // Validate enrollment
        $enrollment = $this->enrollmentRepository->find($idEnrollment);
        if (!$enrollment) {
            throw new \Exception('Enrollment tidak ditemukan', 404);
        }

        // Validate komponen
        $komponen = $this->repository->findKomponen($idKomponen);
        if (!$komponen) {
            throw new \Exception('Komponen penilaian tidak ditemukan', 404);
        }

        // Validate nilai range
        if ($nilaiMentah < 0 || $nilaiMentah > $komponen['nilai_maksimal']) {
            throw new \Exception(
                sprintf('Nilai harus antara 0 - %.2f', $komponen['nilai_maksimal']),
                400
            );
        }

        // Calculate nilai tertimbang
        $nilaiTertimbang = $this->calculateNilaiTertimbang(
            $nilaiMentah,
            $komponen['nilai_maksimal'],
            $komponen['bobot_realisasi']
        );

        // Upsert nilai
        $nilaiData = [
            'id_enrollment' => $idEnrollment,
            'id_komponen' => $idKomponen,
            'nilai_mentah' => $nilaiMentah,
            'nilai_tertimbang' => $nilaiTertimbang,
            'catatan' => $catatan,
            'dinilai_oleh' => $idDosen,
            'tanggal_input' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->repository->upsertNilai($nilaiData);

        // Update nilai akhir di enrollment
        $this->updateEnrollmentGrades($idEnrollment, $userId);

        // Audit log
        $this->auditLog->log('nilai_detail', $idEnrollment . '_' . $idKomponen, 'UPSERT', null, $nilaiData, $userId);

        return $this->repository->findNilai($idEnrollment, $idKomponen);
    }

    /**
     * Bulk input nilai
     */
    public function bulkInputNilai(int $idKomponen, array $nilaiList, int $userId, string $idDosen): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($nilaiList as $item) {
            try {
                $nilai = $this->inputNilai(
                    $item['id_enrollment'],
                    $idKomponen,
                    $item['nilai_mentah'],
                    $item['catatan'] ?? null,
                    $userId,
                    $idDosen
                );

                $results['success'][] = [
                    'id_enrollment' => $item['id_enrollment'],
                    'nilai' => $nilai
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id_enrollment' => $item['id_enrollment'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get nilai by enrollment
     */
    public function getNilaiByEnrollment(int $idEnrollment): array
    {
        return $this->repository->findNilaiByEnrollment($idEnrollment);
    }

    /**
     * Get nilai by komponen
     */
    public function getNilaiByKomponen(int $idKomponen): array
    {
        return $this->repository->findNilaiByKomponen($idKomponen);
    }

    // ===================================
    // CALCULATION METHODS
    // ===================================

    /**
     * Calculate nilai tertimbang
     */
    private function calculateNilaiTertimbang(float $nilaiMentah, float $nilaiMaksimal, ?float $bobot): float
    {
        if ($bobot === null || $nilaiMaksimal == 0) {
            return 0;
        }

        return ($nilaiMentah / $nilaiMaksimal) * $bobot;
    }

    /**
     * Update enrollment grades
     */
    private function updateEnrollmentGrades(int $idEnrollment, int $userId): void
    {
        // Calculate nilai akhir
        $nilaiAkhir = $this->repository->calculateNilaiAkhir($idEnrollment);

        // Convert to huruf
        $nilaiHuruf = $this->convertToGradeHuruf($nilaiAkhir);

        // Update enrollment
        $this->enrollmentRepository->updateGrades($idEnrollment, $nilaiAkhir, $nilaiHuruf);

        // Audit log
        $this->auditLog->log('enrollment', $idEnrollment, 'UPDATE_GRADES', null, [
            'nilai_akhir' => $nilaiAkhir,
            'nilai_huruf' => $nilaiHuruf
        ], $userId);
    }

    /**
     * Convert nilai to grade huruf
     */
    private function convertToGradeHuruf(float $nilai): string
    {
        foreach (self::GRADE_CONVERSION as $grade) {
            if ($nilai >= $grade['min'] && $nilai <= $grade['max']) {
                return $grade['huruf'];
            }
        }

        return 'E'; // Default
    }

    /**
     * Get nilai summary by kelas
     */
    public function getNilaiSummaryByKelas(int $idKelas): array
    {
        return $this->repository->getNilaiSummaryByKelas($idKelas);
    }

    /**
     * Get komponen statistics
     */
    public function getKomponenStatistics(int $idKomponen): array
    {
        return $this->repository->getKomponenStatistics($idKomponen);
    }

    /**
     * Calculate CPMK achievement for enrollment
     */
    public function calculateCPMKAchievement(int $idEnrollment, int $idCpmk): ?float
    {
        return $this->repository->calculateCPMKAchievement($idEnrollment, $idCpmk);
    }

    /**
     * Recalculate all grades for kelas
     */
    public function recalculateKelasGrades(int $idKelas, int $userId): array
    {
        // Get all enrollments
        $enrollments = $this->enrollmentRepository->findByKelas($idKelas);

        $results = [
            'total' => count($enrollments),
            'updated' => 0,
            'errors' => []
        ];

        foreach ($enrollments as $enrollment) {
            try {
                $this->updateEnrollmentGrades($enrollment['id_enrollment'], $userId);
                $results['updated']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'id_enrollment' => $enrollment['id_enrollment'],
                    'nim' => $enrollment['nim'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get all jenis penilaian
     */
    public function getAllJenisPenilaian(): array
    {
        return $this->repository->getAllJenisPenilaian();
    }
}
