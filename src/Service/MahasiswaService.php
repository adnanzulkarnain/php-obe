<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MahasiswaRepository;

/**
 * Mahasiswa Service
 * Business logic for student management
 */
class MahasiswaService
{
    private MahasiswaRepository $repository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new MahasiswaRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get all mahasiswa with optional filters
     */
    public function getAll(array $filters = []): array
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Get mahasiswa by NIM
     */
    public function getByNim(string $nim): array
    {
        $mahasiswa = $this->repository->findWithDetails($nim);

        if (!$mahasiswa) {
            throw new \Exception('Mahasiswa tidak ditemukan', 404);
        }

        return $mahasiswa;
    }

    /**
     * Create new mahasiswa
     */
    public function create(array $data, int $userId): array
    {
        // Validate required fields
        $required = ['nim', 'nama', 'email', 'id_prodi', 'id_kurikulum', 'angkatan'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Check if NIM already exists
        if ($this->repository->exists($data['nim'])) {
            throw new \Exception('NIM sudah terdaftar', 400);
        }

        // Check if email already exists
        if ($this->repository->emailExists($data['email'])) {
            throw new \Exception('Email sudah terdaftar', 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid', 400);
        }

        // Prepare data
        $mahasiswaData = [
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'id_prodi' => $data['id_prodi'],
            'id_kurikulum' => (int)$data['id_kurikulum'],
            'angkatan' => $data['angkatan'],
            'status' => $data['status'] ?? 'aktif'
        ];

        // Create mahasiswa
        $nim = $this->repository->createMahasiswa($mahasiswaData);

        // Audit log
        $this->auditLog->log('mahasiswa', $nim, 'INSERT', null, $mahasiswaData, $userId);

        return $this->repository->findWithDetails($nim);
    }

    /**
     * Update mahasiswa
     */
    public function update(string $nim, array $data, int $userId): array
    {
        // Check if mahasiswa exists
        $existing = $this->repository->find($nim);
        if (!$existing) {
            throw new \Exception('Mahasiswa tidak ditemukan', 404);
        }

        // Check email uniqueness
        if (!empty($data['email']) && $this->repository->emailExists($data['email'], $nim)) {
            throw new \Exception('Email sudah terdaftar oleh mahasiswa lain', 400);
        }

        // Validate email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid', 400);
        }

        // Prepare update data (exclude id_kurikulum - immutable)
        $updateData = [];
        $allowedFields = ['nama', 'email', 'id_prodi', 'angkatan', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new \Exception('Tidak ada data yang diupdate', 400);
        }

        // Update mahasiswa
        $this->repository->updateMahasiswa($nim, $updateData);

        // Audit log
        $this->auditLog->log('mahasiswa', $nim, 'UPDATE', $existing, $updateData, $userId);

        return $this->repository->findWithDetails($nim);
    }

    /**
     * Delete mahasiswa (soft delete by changing status)
     */
    public function delete(string $nim, int $userId): void
    {
        // Check if mahasiswa exists
        $mahasiswa = $this->repository->find($nim);
        if (!$mahasiswa) {
            throw new \Exception('Mahasiswa tidak ditemukan', 404);
        }

        // Soft delete by setting status
        $this->repository->updateMahasiswa($nim, ['status' => 'keluar']);

        // Audit log
        $this->auditLog->log('mahasiswa', $nim, 'DELETE', $mahasiswa, null, $userId);
    }

    /**
     * Get statistics by prodi
     */
    public function getStatisticsByProdi(string $idProdi): array
    {
        return $this->repository->getStatisticsByProdi($idProdi);
    }

    /**
     * Get mahasiswa by angkatan
     */
    public function getByAngkatan(string $angkatan): array
    {
        return $this->repository->getByAngkatan($angkatan);
    }
}
