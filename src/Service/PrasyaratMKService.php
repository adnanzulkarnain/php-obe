<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PrasyaratMKRepository;
use App\Repository\MataKuliahRepository;
use App\Service\AuditLogService;
use App\Entity\PrasyaratMK;
use InvalidArgumentException;
use RuntimeException;

/**
 * PrasyaratMK Service
 * Business logic for course prerequisites management
 */
class PrasyaratMKService
{
    private PrasyaratMKRepository $prasyaratRepo;
    private MataKuliahRepository $mkRepo;
    private AuditLogService $auditLog;

    public function __construct(
        PrasyaratMKRepository $prasyaratRepo,
        MataKuliahRepository $mkRepo,
        AuditLogService $auditLog
    ) {
        $this->prasyaratRepo = $prasyaratRepo;
        $this->mkRepo = $mkRepo;
        $this->auditLog = $auditLog;
    }

    /**
     * Add a prerequisite to a course
     */
    public function create(array $data, int $userId): int
    {
        // Validate mata kuliah exists
        $mk = $this->mkRepo->findOne([
            'kode_mk' => $data['kode_mk'],
            'id_kurikulum' => $data['id_kurikulum']
        ]);
        if (!$mk) {
            throw new InvalidArgumentException('Mata Kuliah not found');
        }

        // Validate prasyarat mata kuliah exists
        $mkPrasyarat = $this->mkRepo->findOne([
            'kode_mk' => $data['kode_mk_prasyarat'],
            'id_kurikulum' => $data['id_kurikulum']
        ]);
        if (!$mkPrasyarat) {
            throw new InvalidArgumentException('Prerequisite Mata Kuliah not found');
        }

        // Check if prerequisite already exists
        if ($this->prasyaratRepo->exists(
            $data['kode_mk'],
            $data['id_kurikulum'],
            $data['kode_mk_prasyarat']
        )) {
            throw new InvalidArgumentException('This prerequisite relationship already exists');
        }

        // Check for circular dependency
        if ($this->prasyaratRepo->hasCircularDependency(
            $data['kode_mk'],
            $data['id_kurikulum'],
            $data['kode_mk_prasyarat']
        )) {
            throw new RuntimeException(
                'Cannot add prerequisite: would create circular dependency'
            );
        }

        // Validate entity
        $prasyarat = new PrasyaratMK($data);

        // Insert into database
        $idPrasyarat = $this->prasyaratRepo->create($prasyarat->toArray());

        // Log audit
        $this->auditLog->log(
            'prasyarat_mk',
            $idPrasyarat,
            'create',
            null,
            $prasyarat->toArray(),
            $userId
        );

        return $idPrasyarat;
    }

    /**
     * Delete a prerequisite
     */
    public function delete(int $idPrasyarat, int $userId): bool
    {
        // Get existing prerequisite
        $existing = $this->prasyaratRepo->findById($idPrasyarat);
        if (!$existing) {
            throw new InvalidArgumentException('Prerequisite not found');
        }

        $success = $this->prasyaratRepo->delete($idPrasyarat);

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'prasyarat_mk',
                $idPrasyarat,
                'delete',
                $existing,
                null,
                $userId
            );
        }

        return $success;
    }

    /**
     * Delete prerequisite by mata kuliah and prasyarat
     */
    public function deleteByMataKuliahAndPrasyarat(
        string $kodeMk,
        int $idKurikulum,
        string $kodeMkPrasyarat,
        int $userId
    ): bool {
        // Check if exists
        if (!$this->prasyaratRepo->exists($kodeMk, $idKurikulum, $kodeMkPrasyarat)) {
            throw new InvalidArgumentException('Prerequisite relationship not found');
        }

        $success = $this->prasyaratRepo->deleteByMataKuliahAndPrasyarat(
            $kodeMk,
            $idKurikulum,
            $kodeMkPrasyarat
        );

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'prasyarat_mk',
                "{$kodeMk}-{$kodeMkPrasyarat}",
                'delete',
                [
                    'kode_mk' => $kodeMk,
                    'id_kurikulum' => $idKurikulum,
                    'kode_mk_prasyarat' => $kodeMkPrasyarat
                ],
                null,
                $userId
            );
        }

        return $success;
    }

    /**
     * Get all prerequisites for a course
     */
    public function getPrerequisites(string $kodeMk, int $idKurikulum): array
    {
        return $this->prasyaratRepo->findByMataKuliah($kodeMk, $idKurikulum);
    }

    /**
     * Get all courses that require a specific course as prerequisite
     */
    public function getCoursesRequiring(string $kodeMk, int $idKurikulum): array
    {
        return $this->prasyaratRepo->findCoursesRequiring($kodeMk, $idKurikulum);
    }

    /**
     * Get prerequisite tree (recursive)
     */
    public function getPrerequisiteTree(string $kodeMk, int $idKurikulum): array
    {
        return $this->prasyaratRepo->getPrerequisiteTree($kodeMk, $idKurikulum);
    }

    /**
     * Check if student can enroll in a course based on prerequisites
     * Returns array with:
     * - can_enroll (bool)
     * - fulfilled (array of fulfilled prerequisites)
     * - unfulfilled (array of unfulfilled prerequisites)
     * - message (string)
     */
    public function checkEnrollmentEligibility(
        string $nim,
        string $kodeMk,
        int $idKurikulum
    ): array {
        $prerequisites = $this->prasyaratRepo->checkPrerequisiteFulfilled(
            $nim,
            $kodeMk,
            $idKurikulum
        );

        if (empty($prerequisites)) {
            return [
                'can_enroll' => true,
                'fulfilled' => [],
                'unfulfilled' => [],
                'message' => 'No prerequisites required'
            ];
        }

        $fulfilled = [];
        $unfulfilled = [];
        $wajibUnfulfilled = [];
        $alternatifGroups = [];

        foreach ($prerequisites as $prereq) {
            if ($prereq['is_fulfilled'] === true || $prereq['is_fulfilled'] === 't') {
                $fulfilled[] = $prereq;
            } else {
                $unfulfilled[] = $prereq;

                if ($prereq['jenis_prasyarat'] === 'wajib') {
                    $wajibUnfulfilled[] = $prereq;
                } else {
                    // Group alternatif prerequisites
                    $alternatifGroups[$prereq['kode_mk_prasyarat']] = $prereq;
                }
            }
        }

        // Check if can enroll
        // All wajib prerequisites must be fulfilled
        // At least one of each alternatif group must be fulfilled
        $canEnroll = empty($wajibUnfulfilled);

        // Check alternatif prerequisites
        // For now, we consider all alternatif as independent
        // In more complex scenarios, you might group them

        $message = $this->buildEligibilityMessage($canEnroll, $wajibUnfulfilled, $unfulfilled);

        return [
            'can_enroll' => $canEnroll,
            'fulfilled' => $fulfilled,
            'unfulfilled' => $unfulfilled,
            'wajib_unfulfilled' => $wajibUnfulfilled,
            'message' => $message
        ];
    }

    /**
     * Build eligibility message
     */
    private function buildEligibilityMessage(
        bool $canEnroll,
        array $wajibUnfulfilled,
        array $unfulfilled
    ): string {
        if ($canEnroll) {
            return 'Student is eligible to enroll';
        }

        $messages = [];

        if (!empty($wajibUnfulfilled)) {
            $mkNames = array_map(function ($p) {
                return $p['nama_mk_prasyarat'];
            }, $wajibUnfulfilled);

            $messages[] = 'Must complete: ' . implode(', ', $mkNames);
        }

        return implode('. ', $messages);
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $idKurikulum): array
    {
        return $this->prasyaratRepo->getStatistics($idKurikulum);
    }

    /**
     * Bulk add prerequisites
     */
    public function bulkCreate(array $prerequisitesList, int $userId): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($prerequisitesList as $index => $data) {
            try {
                $idPrasyarat = $this->create($data, $userId);
                $results['success'][] = [
                    'index' => $index,
                    'id_prasyarat' => $idPrasyarat,
                    'kode_mk' => $data['kode_mk'],
                    'kode_mk_prasyarat' => $data['kode_mk_prasyarat'],
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
