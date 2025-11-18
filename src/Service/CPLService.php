<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CPLRepository;
use App\Repository\KurikulumRepository;

/**
 * CPL Service - Business Logic
 */
class CPLService
{
    private CPLRepository $repository;
    private KurikulumRepository $kurikulumRepository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new CPLRepository();
        $this->kurikulumRepository = new KurikulumRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create CPL for kurikulum (UC-K04)
     */
    public function create(array $data, int $userId): array
    {
        // Validate
        $this->validateCreate($data);

        // Check if kurikulum exists
        $kurikulum = $this->kurikulumRepository->find($data['id_kurikulum']);
        if (!$kurikulum) {
            throw new \Exception('Kurikulum tidak ditemukan', 404);
        }

        // Check if already archived
        if ($kurikulum['status'] === 'arsip') {
            throw new \Exception('Tidak dapat menambah CPL pada kurikulum yang sudah diarsipkan', 400);
        }

        // Check if kode_cpl already exists in this kurikulum
        $existing = $this->repository->findByKode($data['kode_cpl'], $data['id_kurikulum']);
        if ($existing) {
            throw new \Exception('Kode CPL sudah digunakan dalam kurikulum ini', 400);
        }

        // Create CPL
        $cplData = [
            'id_kurikulum' => $data['id_kurikulum'],
            'kode_cpl' => $data['kode_cpl'],
            'deskripsi' => $data['deskripsi'],
            'kategori' => $data['kategori'],
            'urutan' => $data['urutan'] ?? null,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $idCpl = $this->repository->create($cplData);

        // Audit log
        $this->auditLog->log('cpl', $idCpl, 'INSERT', null, $cplData, $userId);

        return $this->repository->find($idCpl);
    }

    /**
     * Update CPL
     */
    public function update(int $idCpl, array $data, int $userId): array
    {
        $cpl = $this->repository->find($idCpl);

        if (!$cpl) {
            throw new \Exception('CPL tidak ditemukan', 404);
        }

        // Check if CPL is mapped to CPMK
        if ($this->repository->isMappedToCPMK($idCpl)) {
            throw new \Exception('CPL sudah dipetakan ke CPMK, tidak dapat diubah', 400);
        }

        // Prepare update data
        $updateData = [
            'deskripsi' => $data['deskripsi'] ?? $cpl['deskripsi'],
            'kategori' => $data['kategori'] ?? $cpl['kategori'],
            'urutan' => $data['urutan'] ?? $cpl['urutan'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->update($idCpl, $updateData);

        // Audit log
        $this->auditLog->log('cpl', $idCpl, 'UPDATE', $cpl, array_merge($cpl, $updateData), $userId);

        return $this->repository->find($idCpl);
    }

    /**
     * Deactivate CPL
     */
    public function deactivate(int $idCpl, int $userId): bool
    {
        $cpl = $this->repository->find($idCpl);

        if (!$cpl) {
            throw new \Exception('CPL tidak ditemukan', 404);
        }

        // Check if CPL is mapped to CPMK
        if ($this->repository->isMappedToCPMK($idCpl)) {
            throw new \Exception('CPL sudah dipetakan ke CPMK, tidak dapat dinonaktifkan', 400);
        }

        $result = $this->repository->softDelete($idCpl);

        // Audit log
        $this->auditLog->log('cpl', $idCpl, 'DELETE', $cpl, null, $userId);

        return $result;
    }

    /**
     * Get CPL by kurikulum
     */
    public function getByKurikulum(int $idKurikulum): array
    {
        return $this->repository->findByKurikulum($idKurikulum);
    }

    /**
     * Get CPL grouped by kategori
     */
    public function getGroupedByKategori(int $idKurikulum): array
    {
        return $this->repository->findGroupedByKategori($idKurikulum);
    }

    /**
     * Validate create data
     */
    private function validateCreate(array $data): void
    {
        $required = ['id_kurikulum', 'kode_cpl', 'deskripsi', 'kategori'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Validate kategori
        $validKategori = ['sikap', 'pengetahuan', 'keterampilan_umum', 'keterampilan_khusus'];
        if (!in_array($data['kategori'], $validKategori)) {
            throw new \Exception('Kategori tidak valid', 400);
        }
    }
}
