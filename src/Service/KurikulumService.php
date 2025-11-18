<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\KurikulumRepository;
use App\Service\AuditLogService;
use App\Config\Database;

/**
 * Kurikulum Service - Business Logic
 */
class KurikulumService
{
    private KurikulumRepository $repository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new KurikulumRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create new kurikulum (UC-K01)
     */
    public function create(array $data, int $userId): array
    {
        // Validation
        $this->validateCreate($data);

        // Check if kode already exists
        $existing = $this->repository->findByKode($data['kode_kurikulum'], $data['id_prodi']);
        if ($existing) {
            throw new \Exception('Kode kurikulum sudah digunakan', 400);
        }

        // Prepare data
        $kurikulumData = [
            'id_prodi' => $data['id_prodi'],
            'kode_kurikulum' => $data['kode_kurikulum'],
            'nama_kurikulum' => $data['nama_kurikulum'],
            'tahun_berlaku' => $data['tahun_berlaku'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'status' => 'draft',
            'is_primary' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Create kurikulum
        $idKurikulum = $this->repository->create($kurikulumData);

        // Audit log
        $this->auditLog->log(
            'kurikulum',
            $idKurikulum,
            'INSERT',
            null,
            $kurikulumData,
            $userId
        );

        // Return created kurikulum
        return $this->repository->find($idKurikulum);
    }

    /**
     * Approve kurikulum (UC-K02)
     */
    public function approve(int $idKurikulum, string $nomorSk, string $tanggalSk, int $userId): array
    {
        $kurikulum = $this->repository->find($idKurikulum);

        if (!$kurikulum) {
            throw new \Exception('Kurikulum tidak ditemukan', 404);
        }

        if ($kurikulum['status'] !== 'review') {
            throw new \Exception('Kurikulum harus dalam status review untuk disetujui', 400);
        }

        // Validate requirements
        $stats = $this->repository->findWithStatistics($idKurikulum);
        if ($stats['total_cpl'] < 1) {
            throw new \Exception('Kurikulum harus memiliki minimal 1 CPL', 400);
        }
        if ($stats['total_mk'] < 5) {
            throw new \Exception('Kurikulum harus memiliki minimal 5 Mata Kuliah', 400);
        }

        // Update status
        $updateData = [
            'status' => 'approved',
            'nomor_sk' => $nomorSk,
            'tanggal_sk' => $tanggalSk,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->update($idKurikulum, $updateData);

        // Audit log
        $this->auditLog->log(
            'kurikulum',
            $idKurikulum,
            'APPROVE',
            $kurikulum,
            array_merge($kurikulum, $updateData),
            $userId
        );

        return $this->repository->find($idKurikulum);
    }

    /**
     * Activate kurikulum (UC-K03)
     */
    public function activate(int $idKurikulum, bool $setAsPrimary, int $userId): array
    {
        $kurikulum = $this->repository->find($idKurikulum);

        if (!$kurikulum) {
            throw new \Exception('Kurikulum tidak ditemukan', 404);
        }

        if ($kurikulum['status'] !== 'approved') {
            throw new \Exception('Kurikulum harus disetujui terlebih dahulu', 400);
        }

        // Check tahun berlaku
        $currentYear = (int) date('Y');
        if ($kurikulum['tahun_berlaku'] > $currentYear) {
            throw new \Exception('Kurikulum belum dapat diaktifkan (tahun berlaku: ' . $kurikulum['tahun_berlaku'] . ')', 400);
        }

        Database::beginTransaction();

        try {
            // If set as primary, remove primary flag from others
            if ($setAsPrimary) {
                $this->repository->removePrimaryFlag($kurikulum['id_prodi']);
            }

            // Activate kurikulum
            $updateData = [
                'status' => 'aktif',
                'is_primary' => $setAsPrimary,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->repository->update($idKurikulum, $updateData);

            // Audit log
            $this->auditLog->log(
                'kurikulum',
                $idKurikulum,
                'UPDATE',
                $kurikulum,
                array_merge($kurikulum, $updateData),
                $userId
            );

            Database::commit();

            return $this->repository->find($idKurikulum);
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Deactivate kurikulum (UC-K09)
     */
    public function deactivate(int $idKurikulum, int $userId): array
    {
        $kurikulum = $this->repository->find($idKurikulum);

        if (!$kurikulum) {
            throw new \Exception('Kurikulum tidak ditemukan', 404);
        }

        if ($kurikulum['status'] !== 'aktif') {
            throw new \Exception('Hanya kurikulum aktif yang dapat dinonaktifkan', 400);
        }

        // Check if this is the only active kurikulum
        $activeKurikulum = $this->repository->findActiveByProdi($kurikulum['id_prodi']);
        if (count($activeKurikulum) === 1) {
            throw new \Exception('Tidak dapat menonaktifkan kurikulum terakhir yang aktif', 400);
        }

        // Deactivate
        $updateData = [
            'status' => 'non-aktif',
            'is_primary' => false,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->update($idKurikulum, $updateData);

        // Audit log
        $this->auditLog->log(
            'kurikulum',
            $idKurikulum,
            'UPDATE',
            $kurikulum,
            array_merge($kurikulum, $updateData),
            $userId
        );

        return $this->repository->find($idKurikulum);
    }

    /**
     * Get kurikulum list for a prodi
     */
    public function getByProdi(string $idProdi, ?string $status = null): array
    {
        return $this->repository->findByProdi($idProdi, $status);
    }

    /**
     * Get kurikulum detail with statistics
     */
    public function getDetail(int $idKurikulum): array
    {
        $kurikulum = $this->repository->findWithStatistics($idKurikulum);

        if (!$kurikulum) {
            throw new \Exception('Kurikulum tidak ditemukan', 404);
        }

        return $kurikulum;
    }

    /**
     * Compare multiple kurikulum (UC-K08)
     */
    public function compare(array $ids): array
    {
        if (count($ids) < 2) {
            throw new \Exception('Minimal 2 kurikulum untuk perbandingan', 400);
        }

        return $this->repository->getComparisonData($ids);
    }

    /**
     * Validate create data
     */
    private function validateCreate(array $data): void
    {
        $required = ['id_prodi', 'kode_kurikulum', 'nama_kurikulum', 'tahun_berlaku'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Validate tahun berlaku
        $currentYear = (int) date('Y');
        if ($data['tahun_berlaku'] < ($currentYear - 10)) {
            throw new \Exception('Tahun berlaku tidak valid (minimal ' . ($currentYear - 10) . ')', 400);
        }
    }
}
