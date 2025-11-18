<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CPMK;
use App\Entity\SubCPMK;
use App\Entity\RelasiCPMKCPL;
use App\Repository\CPMKRepository;
use App\Repository\RPSRepository;
use App\Repository\CPLRepository;

/**
 * CPMK Service
 */
class CPMKService
{
    private CPMKRepository $repository;
    private RPSRepository $rpsRepository;
    private CPLRepository $cplRepository;
    private AuditLogService $auditLog;

    // Business rules constants
    private const MIN_CPMK_PER_RPS = 3;
    private const MAX_CPMK_PER_RPS = 12;

    public function __construct()
    {
        $this->repository = new CPMKRepository();
        $this->rpsRepository = new RPSRepository();
        $this->cplRepository = new CPLRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create CPMK
     */
    public function create(array $data, int $userId): array
    {
        // Create entity and validate
        $cpmk = CPMK::fromArray($data);
        $errors = $cpmk->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if RPS exists
        $rps = $this->rpsRepository->find($cpmk->id_rps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Business rule: Check max CPMK per RPS
        $currentCount = $this->repository->countByRPS($cpmk->id_rps);
        if ($currentCount >= self::MAX_CPMK_PER_RPS) {
            throw new \Exception(
                sprintf('Maksimal %d CPMK per RPS. Saat ini sudah ada %d CPMK.', self::MAX_CPMK_PER_RPS, $currentCount),
                400
            );
        }

        // Get next urutan if not provided
        if ($cpmk->urutan === null) {
            $cpmk->urutan = $this->repository->getNextUrutan($cpmk->id_rps);
        }

        // Create CPMK
        $cpmkData = [
            'id_rps' => $cpmk->id_rps,
            'kode_cpmk' => $cpmk->kode_cpmk,
            'deskripsi' => $cpmk->deskripsi,
            'urutan' => $cpmk->urutan,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idCpmk = $this->repository->create($cpmkData);

        // Audit log
        $this->auditLog->log(
            'cpmk',
            $idCpmk,
            'INSERT',
            null,
            $cpmkData,
            $userId
        );

        return $this->repository->findByIdWithDetails($idCpmk);
    }

    /**
     * Update CPMK
     */
    public function update(int $idCpmk, array $data, int $userId): array
    {
        $cpmk = $this->repository->find($idCpmk);

        if (!$cpmk) {
            throw new \Exception('CPMK tidak ditemukan', 404);
        }

        // Prepare update data
        $updateData = [
            'kode_cpmk' => $data['kode_cpmk'] ?? $cpmk['kode_cpmk'],
            'deskripsi' => $data['deskripsi'] ?? $cpmk['deskripsi'],
            'urutan' => $data['urutan'] ?? $cpmk['urutan'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Update CPMK
        $this->repository->update($idCpmk, $updateData);

        // Audit log
        $this->auditLog->log(
            'cpmk',
            $idCpmk,
            'UPDATE',
            $cpmk,
            array_merge($cpmk, $updateData),
            $userId
        );

        return $this->repository->findByIdWithDetails($idCpmk);
    }

    /**
     * Delete CPMK
     */
    public function delete(int $idCpmk, int $userId): void
    {
        $cpmk = $this->repository->find($idCpmk);

        if (!$cpmk) {
            throw new \Exception('CPMK tidak ditemukan', 404);
        }

        // Business rule: Check min CPMK per RPS
        $currentCount = $this->repository->countByRPS($cpmk['id_rps']);
        if ($currentCount <= self::MIN_CPMK_PER_RPS) {
            throw new \Exception(
                sprintf('Minimal %d CPMK harus ada per RPS. Tidak dapat menghapus CPMK ini.', self::MIN_CPMK_PER_RPS),
                400
            );
        }

        // Delete CPMK (will cascade delete SubCPMK and mappings)
        $this->repository->delete($idCpmk);

        // Audit log
        $this->auditLog->log(
            'cpmk',
            $idCpmk,
            'DELETE',
            $cpmk,
            null,
            $userId
        );
    }

    /**
     * Get CPMK by ID with full details
     */
    public function getById(int $idCpmk): array
    {
        $cpmk = $this->repository->getCPMKWithFullDetails($idCpmk);

        if (!$cpmk) {
            throw new \Exception('CPMK tidak ditemukan', 404);
        }

        return $cpmk;
    }

    /**
     * Get CPMK by RPS
     */
    public function getByRPS(int $idRps, bool $includeSubCPMK = false): array
    {
        if ($includeSubCPMK) {
            return $this->repository->findByRPSWithSubCPMK($idRps);
        }

        return $this->repository->findByRPS($idRps);
    }

    // ===================================
    // SUBCPMK METHODS
    // ===================================

    /**
     * Create SubCPMK
     */
    public function createSubCPMK(array $data, int $userId): array
    {
        // Create entity and validate
        $subcpmk = SubCPMK::fromArray($data);
        $errors = $subcpmk->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if CPMK exists
        $cpmk = $this->repository->find($subcpmk->id_cpmk);
        if (!$cpmk) {
            throw new \Exception('CPMK tidak ditemukan', 404);
        }

        // Get next urutan if not provided
        if ($subcpmk->urutan === null) {
            $subcpmk->urutan = $this->repository->getNextSubCPMKUrutan($subcpmk->id_cpmk);
        }

        // Create SubCPMK
        $subcpmkData = [
            'id_cpmk' => $subcpmk->id_cpmk,
            'kode_subcpmk' => $subcpmk->kode_subcpmk,
            'deskripsi' => $subcpmk->deskripsi,
            'indikator' => $subcpmk->indikator,
            'urutan' => $subcpmk->urutan,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idSubcpmk = $this->repository->createSubCPMK($subcpmkData);

        // Audit log
        $this->auditLog->log(
            'subcpmk',
            $idSubcpmk,
            'INSERT',
            null,
            $subcpmkData,
            $userId
        );

        return $this->repository->findSubCPMK($idSubcpmk);
    }

    /**
     * Update SubCPMK
     */
    public function updateSubCPMK(int $idSubcpmk, array $data, int $userId): array
    {
        $subcpmk = $this->repository->findSubCPMK($idSubcpmk);

        if (!$subcpmk) {
            throw new \Exception('SubCPMK tidak ditemukan', 404);
        }

        // Prepare update data
        $updateData = [
            'kode_subcpmk' => $data['kode_subcpmk'] ?? $subcpmk['kode_subcpmk'],
            'deskripsi' => $data['deskripsi'] ?? $subcpmk['deskripsi'],
            'indikator' => $data['indikator'] ?? $subcpmk['indikator'],
            'urutan' => $data['urutan'] ?? $subcpmk['urutan'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Update SubCPMK
        $this->repository->updateSubCPMK($idSubcpmk, $updateData);

        // Audit log
        $this->auditLog->log(
            'subcpmk',
            $idSubcpmk,
            'UPDATE',
            $subcpmk,
            array_merge($subcpmk, $updateData),
            $userId
        );

        return $this->repository->findSubCPMK($idSubcpmk);
    }

    /**
     * Delete SubCPMK
     */
    public function deleteSubCPMK(int $idSubcpmk, int $userId): void
    {
        $subcpmk = $this->repository->findSubCPMK($idSubcpmk);

        if (!$subcpmk) {
            throw new \Exception('SubCPMK tidak ditemukan', 404);
        }

        // Delete SubCPMK
        $this->repository->deleteSubCPMK($idSubcpmk);

        // Audit log
        $this->auditLog->log(
            'subcpmk',
            $idSubcpmk,
            'DELETE',
            $subcpmk,
            null,
            $userId
        );
    }

    /**
     * Get SubCPMK by CPMK
     */
    public function getSubCPMKByCPMK(int $idCpmk): array
    {
        return $this->repository->getSubCPMKByCPMK($idCpmk);
    }

    // ===================================
    // CPMK-CPL MAPPING METHODS
    // ===================================

    /**
     * Map CPMK to CPL
     */
    public function mapToCPL(int $idCpmk, int $idCpl, float $bobotKontribusi, int $userId): array
    {
        // Validate entities exist
        $cpmk = $this->repository->find($idCpmk);
        if (!$cpmk) {
            throw new \Exception('CPMK tidak ditemukan', 404);
        }

        $cpl = $this->cplRepository->find($idCpl);
        if (!$cpl) {
            throw new \Exception('CPL tidak ditemukan', 404);
        }

        // Business rule: CPMK and CPL must be in same curriculum (enforced by DB trigger)
        // Get kurikulum from CPMK
        $rps = $this->rpsRepository->find($cpmk['id_rps']);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Check if CPL is in same kurikulum
        if ($cpl['id_kurikulum'] !== $rps['id_kurikulum']) {
            throw new \Exception('CPMK dan CPL harus berada dalam kurikulum yang sama', 400);
        }

        // Check if mapping already exists
        if ($this->repository->mappingExists($idCpmk, $idCpl)) {
            throw new \Exception('Mapping CPMK-CPL sudah ada', 400);
        }

        // Create mapping
        $mappingData = [
            'id_cpmk' => $idCpmk,
            'id_cpl' => $idCpl,
            'bobot_kontribusi' => $bobotKontribusi,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $idRelasi = $this->repository->createCPMKCPLMapping($mappingData);

        // Audit log
        $this->auditLog->log(
            'relasi_cpmk_cpl',
            $idRelasi,
            'INSERT',
            null,
            $mappingData,
            $userId
        );

        return $this->repository->findCPMKCPLMapping($idRelasi);
    }

    /**
     * Update CPMK-CPL mapping bobot
     */
    public function updateMappingBobot(int $idRelasi, float $bobotKontribusi, int $userId): array
    {
        $mapping = $this->repository->findCPMKCPLMapping($idRelasi);

        if (!$mapping) {
            throw new \Exception('Mapping tidak ditemukan', 404);
        }

        // Validate bobot
        if ($bobotKontribusi <= 0 || $bobotKontribusi > 100) {
            throw new \Exception('Bobot kontribusi harus antara 0-100', 400);
        }

        // Update mapping
        $this->repository->updateCPMKCPLMapping($idRelasi, $bobotKontribusi);

        // Audit log
        $this->auditLog->log(
            'relasi_cpmk_cpl',
            $idRelasi,
            'UPDATE',
            ['bobot_kontribusi' => $mapping['bobot_kontribusi']],
            ['bobot_kontribusi' => $bobotKontribusi],
            $userId
        );

        return $this->repository->findCPMKCPLMapping($idRelasi);
    }

    /**
     * Delete CPMK-CPL mapping
     */
    public function deleteMapping(int $idRelasi, int $userId): void
    {
        $mapping = $this->repository->findCPMKCPLMapping($idRelasi);

        if (!$mapping) {
            throw new \Exception('Mapping tidak ditemukan', 404);
        }

        // Delete mapping
        $this->repository->deleteCPMKCPLMapping($idRelasi);

        // Audit log
        $this->auditLog->log(
            'relasi_cpmk_cpl',
            $idRelasi,
            'DELETE',
            $mapping,
            null,
            $userId
        );
    }

    /**
     * Get CPL mappings for CPMK
     */
    public function getCPLMappingsByCPMK(int $idCpmk): array
    {
        return $this->repository->getCPLMappingsByCPMK($idCpmk);
    }

    /**
     * Get CPMK mappings for CPL
     */
    public function getCPMKMappingsByCPL(int $idCpl): array
    {
        return $this->repository->getCPMKMappingsByCPL($idCpl);
    }

    /**
     * Get RPS statistics
     */
    public function getRPSStatistics(int $idRps): array
    {
        $stats = $this->repository->getRPSStatistics($idRps);

        // Add validation status
        $cpmkCount = (int)$stats['total_cpmk'];
        $stats['meets_minimum'] = $cpmkCount >= self::MIN_CPMK_PER_RPS;
        $stats['within_maximum'] = $cpmkCount <= self::MAX_CPMK_PER_RPS;
        $stats['min_required'] = self::MIN_CPMK_PER_RPS;
        $stats['max_allowed'] = self::MAX_CPMK_PER_RPS;

        return $stats;
    }

    /**
     * Validate CPMK completeness for RPS
     */
    public function validateRPSCompleteness(int $idRps): array
    {
        $cpmkCount = $this->repository->countByRPS($idRps);

        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'cpmk_count' => $cpmkCount
        ];

        if ($cpmkCount < self::MIN_CPMK_PER_RPS) {
            $validation['is_valid'] = false;
            $validation['errors'][] = sprintf(
                'Minimal %d CPMK diperlukan. Saat ini hanya ada %d CPMK.',
                self::MIN_CPMK_PER_RPS,
                $cpmkCount
            );
        }

        if ($cpmkCount > self::MAX_CPMK_PER_RPS) {
            $validation['is_valid'] = false;
            $validation['errors'][] = sprintf(
                'Maksimal %d CPMK diperbolehkan. Saat ini ada %d CPMK.',
                self::MAX_CPMK_PER_RPS,
                $cpmkCount
            );
        }

        // Check if all CPMK have CPL mappings
        $cpmkList = $this->repository->findByRPS($idRps);
        foreach ($cpmkList as $cpmk) {
            $mappings = $this->repository->getCPLMappingsByCPMK($cpmk['id_cpmk']);
            if (empty($mappings)) {
                $validation['warnings'][] = sprintf(
                    'CPMK "%s" belum dipetakan ke CPL',
                    $cpmk['kode_cpmk']
                );
            }
        }

        return $validation;
    }
}
