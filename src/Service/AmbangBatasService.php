<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AmbangBatasRepository;
use App\Repository\RPSRepository;
use App\Repository\JenisPenilaianRepository;

/**
 * Ambang Batas Service
 * Business logic for threshold/passing grade management
 */
class AmbangBatasService
{
    private AmbangBatasRepository $repository;
    private RPSRepository $rpsRepository;
    private JenisPenilaianRepository $jenisRepository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new AmbangBatasRepository();
        $this->rpsRepository = new RPSRepository();
        $this->jenisRepository = new JenisPenilaianRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get all thresholds for RPS
     */
    public function getByRPS(int $idRps): array
    {
        // Verify RPS exists
        $rps = $this->rpsRepository->find($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        return $this->repository->findByRPS($idRps);
    }

    /**
     * Get threshold by ID
     */
    public function getById(int $idAmbangBatas): array
    {
        $threshold = $this->repository->find($idAmbangBatas);

        if (!$threshold) {
            throw new \Exception('Ambang batas tidak ditemukan', 404);
        }

        return $threshold;
    }

    /**
     * Create threshold
     */
    public function create(array $data, int $userId): array
    {
        // Validate required fields
        $required = ['id_rps', 'id_jenis', 'nilai_minimal'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Validate RPS exists
        $rps = $this->rpsRepository->find($data['id_rps']);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Validate jenis penilaian exists
        $jenis = $this->jenisRepository->find($data['id_jenis']);
        if (!$jenis) {
            throw new \Exception('Jenis penilaian tidak ditemukan', 404);
        }

        // Check if threshold already exists for this jenis
        if ($this->repository->thresholdExists($data['id_rps'], $data['id_jenis'])) {
            throw new \Exception('Ambang batas untuk jenis penilaian ini sudah ada', 400);
        }

        // Validate nilai minimal (0-100)
        if ($data['nilai_minimal'] < 0 || $data['nilai_minimal'] > 100) {
            throw new \Exception('Nilai minimal harus antara 0-100', 400);
        }

        // Create threshold
        $idAmbangBatas = $this->repository->create($data);

        // Audit log
        $this->auditLog->log('ambang_batas', $idAmbangBatas, 'INSERT', null, $data, $userId);

        return $this->repository->find($idAmbangBatas);
    }

    /**
     * Update threshold
     */
    public function update(int $idAmbangBatas, array $data, int $userId): array
    {
        // Check if threshold exists
        $oldData = $this->repository->find($idAmbangBatas);
        if (!$oldData) {
            throw new \Exception('Ambang batas tidak ditemukan', 404);
        }

        // Validate nilai minimal if provided
        if (isset($data['nilai_minimal'])) {
            if ($data['nilai_minimal'] < 0 || $data['nilai_minimal'] > 100) {
                throw new \Exception('Nilai minimal harus antara 0-100', 400);
            }
        }

        // Update
        $this->repository->updateThreshold($idAmbangBatas, $data);

        // Audit log
        $newData = $this->repository->find($idAmbangBatas);
        $this->auditLog->log('ambang_batas', $idAmbangBatas, 'UPDATE', $oldData, $newData, $userId);

        return $newData;
    }

    /**
     * Delete threshold
     */
    public function delete(int $idAmbangBatas, int $userId): void
    {
        // Check if threshold exists
        $threshold = $this->repository->find($idAmbangBatas);
        if (!$threshold) {
            throw new \Exception('Ambang batas tidak ditemukan', 404);
        }

        // Delete
        $this->repository->deleteThreshold($idAmbangBatas);

        // Audit log
        $this->auditLog->log('ambang_batas', $idAmbangBatas, 'DELETE', $threshold, null, $userId);
    }

    /**
     * Bulk create thresholds
     */
    public function bulkCreate(int $idRps, array $thresholds, int $userId): array
    {
        // Validate RPS exists
        $rps = $this->rpsRepository->find($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Validate each threshold
        foreach ($thresholds as $index => $threshold) {
            if (!isset($threshold['id_jenis']) || !isset($threshold['nilai_minimal'])) {
                throw new \Exception("Threshold index {$index}: id_jenis dan nilai_minimal wajib diisi", 400);
            }

            if ($threshold['nilai_minimal'] < 0 || $threshold['nilai_minimal'] > 100) {
                throw new \Exception("Threshold index {$index}: nilai_minimal harus antara 0-100", 400);
            }
        }

        // Create thresholds
        $results = $this->repository->bulkCreate($idRps, $thresholds);

        // Audit log for successful creates
        foreach ($results['created'] as $idAmbangBatas) {
            $newData = $this->repository->find($idAmbangBatas);
            $this->auditLog->log('ambang_batas', $idAmbangBatas, 'INSERT', null, $newData, $userId);
        }

        return $results;
    }

    /**
     * Check if nilai meets threshold
     */
    public function checkThreshold(int $idRps, int $idJenis, float $nilai): bool
    {
        $threshold = $this->repository->getThresholdByJenis($idRps, $idJenis);

        if (!$threshold) {
            // No threshold defined, assume pass
            return true;
        }

        return $nilai >= $threshold['nilai_minimal'];
    }

    /**
     * Get thresholds summary for RPS
     */
    public function getThresholdSummary(int $idRps): array
    {
        $thresholds = $this->repository->findByRPS($idRps);

        return [
            'id_rps' => $idRps,
            'total_thresholds' => count($thresholds),
            'thresholds' => $thresholds,
            'coverage' => [
                'uts' => false,
                'uas' => false,
                'tugas' => false,
                'kuis' => false,
                'praktikum' => false
            ]
        ];
    }
}
