<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MahasiswaRepository;
use App\Repository\KurikulumRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use App\Entity\Mahasiswa;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mahasiswa Service
 * Business logic for mahasiswa (student) management
 */
class MahasiswaService
{
    private MahasiswaRepository $mahasiswaRepo;
    private KurikulumRepository $kurikulumRepo;
    private UserRepository $userRepo;
    private AuditLogService $auditLog;

    public function __construct(
        MahasiswaRepository $mahasiswaRepo,
        KurikulumRepository $kurikulumRepo,
        UserRepository $userRepo,
        AuditLogService $auditLog
    ) {
        $this->mahasiswaRepo = $mahasiswaRepo;
        $this->kurikulumRepo = $kurikulumRepo;
        $this->userRepo = $userRepo;
        $this->auditLog = $auditLog;
    }

    /**
     * Create a new mahasiswa
     */
    public function create(array $data, int $userId): string
    {
        // Validate email uniqueness
        if ($this->mahasiswaRepo->emailExists($data['email'])) {
            throw new InvalidArgumentException('Email already exists');
        }

        // Validate kurikulum exists
        $kurikulum = $this->kurikulumRepo->findById($data['id_kurikulum']);
        if (!$kurikulum) {
            throw new InvalidArgumentException('Kurikulum not found');
        }

        // Validate entity
        $mahasiswa = new Mahasiswa($data);

        // Insert into database
        $nim = $this->mahasiswaRepo->create($mahasiswa->toArray());

        // Log audit
        $this->auditLog->log(
            'mahasiswa',
            $nim,
            'create',
            null,
            $mahasiswa->toArray(),
            $userId
        );

        return $nim;
    }

    /**
     * Update an existing mahasiswa
     *
     * NOTE: id_kurikulum is IMMUTABLE and cannot be updated
     */
    public function update(string $nim, array $data, int $userId): bool
    {
        // Get existing mahasiswa
        $existing = $this->mahasiswaRepo->findById($nim);
        if (!$existing) {
            throw new InvalidArgumentException('Mahasiswa not found');
        }

        // IMPORTANT: Prevent id_kurikulum change
        if (isset($data['id_kurikulum']) && $data['id_kurikulum'] != $existing['id_kurikulum']) {
            throw new RuntimeException(
                'Kurikulum is immutable and cannot be changed after student registration'
            );
        }

        // Validate email uniqueness (excluding current mahasiswa)
        if (isset($data['email']) && $this->mahasiswaRepo->emailExists($data['email'], $nim)) {
            throw new InvalidArgumentException('Email already exists');
        }

        // Merge with existing data (ensure id_kurikulum remains unchanged)
        $updateData = array_merge($existing, $data);
        $updateData['nim'] = $nim;
        $updateData['id_kurikulum'] = $existing['id_kurikulum']; // Force original value

        // Validate entity
        $mahasiswa = new Mahasiswa($updateData);

        // Update in database
        $success = $this->mahasiswaRepo->update($nim, $mahasiswa->toArray());

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'mahasiswa',
                $nim,
                'update',
                $existing,
                $mahasiswa->toArray(),
                $userId
            );
        }

        return $success;
    }

    /**
     * Change mahasiswa status
     */
    public function changeStatus(string $nim, string $newStatus, int $userId): bool
    {
        // Get existing mahasiswa
        $existing = $this->mahasiswaRepo->findById($nim);
        if (!$existing) {
            throw new InvalidArgumentException('Mahasiswa not found');
        }

        // Validate status
        if (!in_array($newStatus, Mahasiswa::getValidStatus())) {
            throw new InvalidArgumentException('Invalid status');
        }

        // Check if status is actually changing
        if ($existing['status'] === $newStatus) {
            return true; // No change needed
        }

        // Business rules for status transitions
        $this->validateStatusTransition($existing['status'], $newStatus);

        // Update status
        $success = $this->mahasiswaRepo->update($nim, ['status' => $newStatus]);

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'mahasiswa',
                $nim,
                'status_change',
                ['status' => $existing['status']],
                ['status' => $newStatus],
                $userId
            );
        }

        return $success;
    }

    /**
     * Validate status transition rules
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        // Cannot change from terminal statuses (lulus, DO, keluar)
        if (in_array($currentStatus, ['lulus', 'DO', 'keluar'])) {
            throw new RuntimeException(
                "Cannot change status from '{$currentStatus}' - this is a terminal status"
            );
        }

        // Cannot go from cuti to lulus directly
        if ($currentStatus === 'cuti' && $newStatus === 'lulus') {
            throw new RuntimeException(
                'Student must be reactivated (aktif) before graduating'
            );
        }
    }

    /**
     * Delete a mahasiswa
     */
    public function delete(string $nim, int $userId): bool
    {
        // Get existing mahasiswa
        $existing = $this->mahasiswaRepo->findById($nim);
        if (!$existing) {
            throw new InvalidArgumentException('Mahasiswa not found');
        }

        // Business rule: Cannot delete mahasiswa with enrollments
        // This will be enforced by database foreign key constraints

        try {
            $success = $this->mahasiswaRepo->delete($nim);

            if ($success) {
                // Log audit
                $this->auditLog->log(
                    'mahasiswa',
                    $nim,
                    'delete',
                    $existing,
                    null,
                    $userId
                );
            }

            return $success;
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                throw new RuntimeException(
                    'Cannot delete mahasiswa with existing enrollments or related data'
                );
            }
            throw $e;
        }
    }

    /**
     * Get mahasiswa by NIM with details
     */
    public function getByNim(string $nim): ?array
    {
        return $this->mahasiswaRepo->findByNimWithDetails($nim);
    }

    /**
     * Get all mahasiswa with filters
     */
    public function getAll(?array $filters = []): array
    {
        return $this->mahasiswaRepo->getAllWithDetails($filters);
    }

    /**
     * Get mahasiswa by prodi
     */
    public function getByProdi(string $idProdi, ?array $filters = []): array
    {
        return $this->mahasiswaRepo->findByProdi($idProdi, $filters);
    }

    /**
     * Get mahasiswa by kurikulum
     */
    public function getByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        return $this->mahasiswaRepo->findByKurikulum($idKurikulum, $filters);
    }

    /**
     * Get mahasiswa by angkatan
     */
    public function getByAngkatan(string $angkatan, ?array $filters = []): array
    {
        return $this->mahasiswaRepo->findByAngkatan($angkatan, $filters);
    }

    /**
     * Get mahasiswa by status
     */
    public function getByStatus(string $status): array
    {
        return $this->mahasiswaRepo->findByStatus($status);
    }

    /**
     * Search mahasiswa
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        if (strlen($keyword) < 2) {
            throw new InvalidArgumentException('Search keyword must be at least 2 characters');
        }

        return $this->mahasiswaRepo->search($keyword, $filters);
    }

    /**
     * Get mahasiswa statistics
     */
    public function getStatistics(): array
    {
        return [
            'by_status' => $this->mahasiswaRepo->getStatisticsByStatus(),
            'by_prodi' => $this->mahasiswaRepo->getStatisticsByProdi(),
            'by_angkatan' => $this->mahasiswaRepo->getStatisticsByAngkatan(),
        ];
    }

    /**
     * Get mahasiswa with academic data (IPK, SKS)
     */
    public function getMahasiswaWithAcademicData(?array $filters = []): array
    {
        return $this->mahasiswaRepo->getMahasiswaWithAcademicData($filters);
    }

    /**
     * Create user account for mahasiswa
     */
    public function createUserAccount(string $nim, array $userData, int $createdBy): int
    {
        // Get mahasiswa info
        $mahasiswa = $this->mahasiswaRepo->findById($nim);
        if (!$mahasiswa) {
            throw new InvalidArgumentException('Mahasiswa not found');
        }

        // Prepare user data
        $userCreateData = [
            'username' => $userData['username'] ?? $nim,
            'email' => $mahasiswa['email'],
            'password' => $userData['password'],
            'user_type' => 'mahasiswa',
            'ref_id' => $nim,
        ];

        // Check if user already exists
        $existingUser = $this->userRepo->findOne(['ref_id' => $nim, 'user_type' => 'mahasiswa']);
        if ($existingUser) {
            throw new RuntimeException('User account already exists for this mahasiswa');
        }

        // Create user account
        $idUser = $this->userRepo->create($userCreateData);

        // Assign role (default: mahasiswa role)
        $this->userRepo->assignRole($idUser, 'mahasiswa');

        // Log audit
        $this->auditLog->log(
            'mahasiswa',
            $nim,
            'user_account_created',
            null,
            ['id_user' => $idUser, 'username' => $userCreateData['username']],
            $createdBy
        );

        return $idUser;
    }

    /**
     * Bulk create mahasiswa from array data
     */
    public function bulkCreate(array $mahasiswaList, int $userId): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($mahasiswaList as $index => $data) {
            try {
                $nim = $this->create($data, $userId);
                $results['success'][] = [
                    'index' => $index,
                    'nim' => $nim,
                    'nama' => $data['nama'],
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
