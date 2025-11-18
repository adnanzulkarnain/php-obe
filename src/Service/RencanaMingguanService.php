<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\RencanaMingguanRepository;
use App\Repository\RPSRepository;

/**
 * Rencana Mingguan Service
 * Business logic for weekly learning plans
 */
class RencanaMingguanService
{
    private RencanaMingguanRepository $repository;
    private RPSRepository $rpsRepository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new RencanaMingguanRepository();
        $this->rpsRepository = new RPSRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get all rencana mingguan by RPS
     */
    public function getByRPS(int $idRps): array
    {
        // Check if RPS exists
        $rps = $this->rpsRepository->find($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        return $this->repository->findByRPS($idRps);
    }

    /**
     * Get rencana mingguan by ID
     */
    public function getById(int $idMinggu): array
    {
        $minggu = $this->repository->findWithDetails($idMinggu);

        if (!$minggu) {
            throw new \Exception('Rencana mingguan tidak ditemukan', 404);
        }

        return $minggu;
    }

    /**
     * Create rencana mingguan
     */
    public function create(array $data, int $userId): array
    {
        // Validate required fields
        $required = ['id_rps', 'minggu_ke'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Check if RPS exists
        $rps = $this->rpsRepository->find($data['id_rps']);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        // Validate minggu_ke range
        $mingguKe = (int)$data['minggu_ke'];
        if ($mingguKe < 1 || $mingguKe > 16) {
            throw new \Exception('minggu_ke harus antara 1-16', 400);
        }

        // Check if minggu_ke already exists
        if ($this->repository->mingguExists($data['id_rps'], $mingguKe)) {
            throw new \Exception("Minggu ke-{$mingguKe} sudah ada untuk RPS ini", 400);
        }

        // Prepare data
        $mingguData = [
            'id_rps' => (int)$data['id_rps'],
            'minggu_ke' => $mingguKe,
            'id_subcpmk' => !empty($data['id_subcpmk']) ? (int)$data['id_subcpmk'] : null,
            'materi' => !empty($data['materi']) ? json_encode($data['materi']) : null,
            'metode' => !empty($data['metode']) ? json_encode($data['metode']) : null,
            'aktivitas' => !empty($data['aktivitas']) ? json_encode($data['aktivitas']) : null,
            'media_software' => $data['media_software'] ?? null,
            'media_hardware' => $data['media_hardware'] ?? null,
            'pengalaman_belajar' => $data['pengalaman_belajar'] ?? null,
            'estimasi_waktu_menit' => !empty($data['estimasi_waktu_menit']) ? (int)$data['estimasi_waktu_menit'] : 150
        ];

        // Create rencana mingguan
        $idMinggu = $this->repository->createRencanaMinggu($mingguData);

        // Audit log
        $this->auditLog->log('rencana_mingguan', $idMinggu, 'INSERT', null, $mingguData, $userId);

        return $this->repository->findWithDetails($idMinggu);
    }

    /**
     * Update rencana mingguan
     */
    public function update(int $idMinggu, array $data, int $userId): array
    {
        // Check if exists
        $existing = $this->repository->find($idMinggu);
        if (!$existing) {
            throw new \Exception('Rencana mingguan tidak ditemukan', 404);
        }

        // Check minggu_ke uniqueness if changed
        if (isset($data['minggu_ke'])) {
            $mingguKe = (int)$data['minggu_ke'];
            if ($mingguKe < 1 || $mingguKe > 16) {
                throw new \Exception('minggu_ke harus antara 1-16', 400);
            }

            if ($this->repository->mingguExists($existing['id_rps'], $mingguKe, $idMinggu)) {
                throw new \Exception("Minggu ke-{$mingguKe} sudah ada untuk RPS ini", 400);
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['minggu_ke', 'id_subcpmk', 'media_software', 'media_hardware', 'pengalaman_belajar', 'estimasi_waktu_menit'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // Handle JSONB fields
        $jsonFields = ['materi', 'metode', 'aktivitas'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = json_encode($data[$field]);
            }
        }

        if (empty($updateData)) {
            throw new \Exception('Tidak ada data yang diupdate', 400);
        }

        // Update rencana mingguan
        $this->repository->updateRencanaMinggu($idMinggu, $updateData);

        // Audit log
        $this->auditLog->log('rencana_mingguan', $idMinggu, 'UPDATE', $existing, $updateData, $userId);

        return $this->repository->findWithDetails($idMinggu);
    }

    /**
     * Delete rencana mingguan
     */
    public function delete(int $idMinggu, int $userId): void
    {
        // Check if exists
        $minggu = $this->repository->find($idMinggu);
        if (!$minggu) {
            throw new \Exception('Rencana mingguan tidak ditemukan', 404);
        }

        // Delete rencana mingguan
        $this->repository->deleteRencanaMinggu($idMinggu);

        // Audit log
        $this->auditLog->log('rencana_mingguan', $idMinggu, 'DELETE', $minggu, null, $userId);
    }

    /**
     * Bulk create rencana mingguan (16 weeks)
     */
    public function bulkCreate(int $idRps, int $userId): array
    {
        // Check if RPS exists
        $rps = $this->rpsRepository->find($idRps);
        if (!$rps) {
            throw new \Exception('RPS tidak ditemukan', 404);
        }

        $results = [
            'created' => [],
            'skipped' => []
        ];

        // Create 16 weeks
        for ($i = 1; $i <= 16; $i++) {
            // Skip if already exists
            if ($this->repository->mingguExists($idRps, $i)) {
                $results['skipped'][] = $i;
                continue;
            }

            $mingguData = [
                'id_rps' => $idRps,
                'minggu_ke' => $i,
                'id_subcpmk' => null,
                'materi' => null,
                'metode' => null,
                'aktivitas' => null,
                'media_software' => null,
                'media_hardware' => null,
                'pengalaman_belajar' => null,
                'estimasi_waktu_menit' => 150
            ];

            $idMinggu = $this->repository->createRencanaMinggu($mingguData);
            $results['created'][] = $idMinggu;

            // Audit log
            $this->auditLog->log('rencana_mingguan', $idMinggu, 'INSERT', null, $mingguData, $userId);
        }

        return $results;
    }

    /**
     * Get completion statistics
     */
    public function getCompletionStats(int $idRps): array
    {
        $stats = $this->repository->getCompletionStats($idRps);

        $total = (int)$stats['total_minggu'];
        $terisi = (int)$stats['minggu_terisi'];

        return [
            'total_minggu' => $total,
            'minggu_terisi' => $terisi,
            'minggu_kosong' => $total - $terisi,
            'persentase_lengkap' => $total > 0 ? round(($terisi / $total) * 100, 2) : 0
        ];
    }
}
