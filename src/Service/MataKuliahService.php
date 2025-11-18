<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MataKuliahRepository;
use App\Repository\KurikulumRepository;

/**
 * Mata Kuliah Service
 */
class MataKuliahService
{
    private MataKuliahRepository $repository;
    private KurikulumRepository $kurikulumRepository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new MataKuliahRepository();
        $this->kurikulumRepository = new KurikulumRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create Mata Kuliah (UC-K05)
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
            throw new \Exception('Tidak dapat menambah MK pada kurikulum yang sudah diarsipkan', 400);
        }

        // Check if kode_mk already exists in this kurikulum
        $existing = $this->repository->findByKodeAndKurikulum($data['kode_mk'], $data['id_kurikulum']);
        if ($existing) {
            throw new \Exception('Kode MK sudah digunakan dalam kurikulum ini', 400);
        }

        // Create MK
        $mkData = [
            'kode_mk' => $data['kode_mk'],
            'id_kurikulum' => $data['id_kurikulum'],
            'nama_mk' => $data['nama_mk'],
            'nama_mk_eng' => $data['nama_mk_eng'] ?? null,
            'sks' => $data['sks'],
            'semester' => $data['semester'],
            'rumpun' => $data['rumpun'] ?? null,
            'jenis_mk' => $data['jenis_mk'],
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->createMK($mkData);

        // Audit log
        $this->auditLog->log(
            'matakuliah',
            $data['kode_mk'] . '_' . $data['id_kurikulum'],
            'INSERT',
            null,
            $mkData,
            $userId
        );

        return $this->repository->findByKodeAndKurikulum($data['kode_mk'], $data['id_kurikulum']);
    }

    /**
     * Update Mata Kuliah
     */
    public function update(string $kodeMk, int $idKurikulum, array $data, int $userId): array
    {
        $mk = $this->repository->findByKodeAndKurikulum($kodeMk, $idKurikulum);

        if (!$mk) {
            throw new \Exception('Mata Kuliah tidak ditemukan', 404);
        }

        // Prepare update data
        $updateData = [
            'nama_mk' => $data['nama_mk'] ?? $mk['nama_mk'],
            'nama_mk_eng' => $data['nama_mk_eng'] ?? $mk['nama_mk_eng'],
            'sks' => $data['sks'] ?? $mk['sks'],
            'semester' => $data['semester'] ?? $mk['semester'],
            'rumpun' => $data['rumpun'] ?? $mk['rumpun'],
            'jenis_mk' => $data['jenis_mk'] ?? $mk['jenis_mk'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->updateMK($kodeMk, $idKurikulum, $updateData);

        // Audit log
        $this->auditLog->log(
            'matakuliah',
            $kodeMk . '_' . $idKurikulum,
            'UPDATE',
            $mk,
            array_merge($mk, $updateData),
            $userId
        );

        return $this->repository->findByKodeAndKurikulum($kodeMk, $idKurikulum);
    }

    /**
     * Deactivate Mata Kuliah (Soft Delete)
     */
    public function deactivate(string $kodeMk, int $idKurikulum, int $userId): bool
    {
        $mk = $this->repository->findByKodeAndKurikulum($kodeMk, $idKurikulum);

        if (!$mk) {
            throw new \Exception('Mata Kuliah tidak ditemukan', 404);
        }

        // Check if MK has RPS
        if ($this->repository->hasRPS($kodeMk, $idKurikulum)) {
            throw new \Exception('MK sudah memiliki RPS, tidak dapat dinonaktifkan', 400);
        }

        $result = $this->repository->softDeleteMK($kodeMk, $idKurikulum);

        // Audit log
        $this->auditLog->log('matakuliah', $kodeMk . '_' . $idKurikulum, 'DELETE', $mk, null, $userId);

        return $result;
    }

    /**
     * Get MK by kurikulum
     */
    public function getByKurikulum(int $idKurikulum): array
    {
        return $this->repository->findByKurikulum($idKurikulum);
    }

    /**
     * Get MK grouped by semester
     */
    public function getGroupedBySemester(int $idKurikulum): array
    {
        return $this->repository->findGroupedBySemester($idKurikulum);
    }

    /**
     * Validate create data
     */
    private function validateCreate(array $data): void
    {
        $required = ['id_kurikulum', 'kode_mk', 'nama_mk', 'sks', 'semester', 'jenis_mk'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Validate SKS
        if ($data['sks'] < 1 || $data['sks'] > 6) {
            throw new \Exception('SKS harus antara 1-6', 400);
        }

        // Validate semester
        if ($data['semester'] < 1 || $data['semester'] > 14) {
            throw new \Exception('Semester harus antara 1-14', 400);
        }

        // Validate jenis_mk
        $validJenis = ['wajib', 'pilihan', 'MKWU'];
        if (!in_array($data['jenis_mk'], $validJenis)) {
            throw new \Exception('Jenis MK tidak valid', 400);
        }
    }
}
