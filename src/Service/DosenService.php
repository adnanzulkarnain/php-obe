<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\DosenRepository;

/**
 * Dosen Service
 * Business logic for lecturer management
 */
class DosenService
{
    private DosenRepository $repository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new DosenRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get all dosen with optional filters
     */
    public function getAll(array $filters = []): array
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Get dosen by ID
     */
    public function getById(string $idDosen): array
    {
        $dosen = $this->repository->findWithDetails($idDosen);

        if (!$dosen) {
            throw new \Exception('Dosen tidak ditemukan', 404);
        }

        return $dosen;
    }

    /**
     * Create new dosen
     */
    public function create(array $data, int $userId): array
    {
        // Validate required fields
        $required = ['id_dosen', 'nama', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Check if ID already exists
        if ($this->repository->exists($data['id_dosen'])) {
            throw new \Exception('ID Dosen sudah digunakan', 400);
        }

        // Check if NIDN already exists
        if (!empty($data['nidn']) && $this->repository->nidnExists($data['nidn'])) {
            throw new \Exception('NIDN sudah terdaftar', 400);
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
        $dosenData = [
            'id_dosen' => $data['id_dosen'],
            'nidn' => $data['nidn'] ?? null,
            'nama' => $data['nama'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'id_prodi' => $data['id_prodi'] ?? null,
            'status' => $data['status'] ?? 'aktif'
        ];

        // Create dosen
        $idDosen = $this->repository->createDosen($dosenData);

        // Audit log
        $this->auditLog->log('dosen', $idDosen, 'INSERT', null, $dosenData, $userId);

        return $this->repository->findWithDetails($idDosen);
    }

    /**
     * Update dosen
     */
    public function update(string $idDosen, array $data, int $userId): array
    {
        // Check if dosen exists
        $existing = $this->repository->find($idDosen);
        if (!$existing) {
            throw new \Exception('Dosen tidak ditemukan', 404);
        }

        // Check NIDN uniqueness
        if (!empty($data['nidn']) && $this->repository->nidnExists($data['nidn'], $idDosen)) {
            throw new \Exception('NIDN sudah terdaftar oleh dosen lain', 400);
        }

        // Check email uniqueness
        if (!empty($data['email']) && $this->repository->emailExists($data['email'], $idDosen)) {
            throw new \Exception('Email sudah terdaftar oleh dosen lain', 400);
        }

        // Validate email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid', 400);
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['nidn', 'nama', 'email', 'phone', 'id_prodi', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new \Exception('Tidak ada data yang diupdate', 400);
        }

        // Update dosen
        $this->repository->updateDosen($idDosen, $updateData);

        // Audit log
        $this->auditLog->log('dosen', $idDosen, 'UPDATE', $existing, $updateData, $userId);

        return $this->repository->findWithDetails($idDosen);
    }

    /**
     * Delete dosen (soft delete by changing status)
     */
    public function delete(string $idDosen, int $userId): void
    {
        // Check if dosen exists
        $dosen = $this->repository->find($idDosen);
        if (!$dosen) {
            throw new \Exception('Dosen tidak ditemukan', 404);
        }

        // Check if dosen has active teaching assignments
        $teachings = $this->repository->getTeachingAssignments($idDosen);
        $activeTeachings = array_filter($teachings, function ($t) {
            return $t['tahun_ajaran'] >= date('Y');
        });

        if (count($activeTeachings) > 0) {
            throw new \Exception('Dosen tidak dapat dihapus karena masih mengampu kelas aktif', 400);
        }

        // Soft delete by setting status to non-active
        $this->repository->updateDosen($idDosen, ['status' => 'pensiun']);

        // Audit log
        $this->auditLog->log('dosen', $idDosen, 'DELETE', $dosen, null, $userId);
    }

    /**
     * Get teaching assignments
     */
    public function getTeachingAssignments(string $idDosen): array
    {
        return $this->repository->getTeachingAssignments($idDosen);
    }
}
