<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\DosenRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use App\Entity\Dosen;
use InvalidArgumentException;
use RuntimeException;

/**
 * Dosen Service
 * Business logic for dosen (lecturer/faculty) management
 */
class DosenService
{
    private DosenRepository $dosenRepo;
    private UserRepository $userRepo;
    private AuditLogService $auditLog;

    public function __construct(
        DosenRepository $dosenRepo,
        UserRepository $userRepo,
        AuditLogService $auditLog
    ) {
        $this->dosenRepo = $dosenRepo;
        $this->userRepo = $userRepo;
        $this->auditLog = $auditLog;
    }

    /**
     * Create a new dosen
     */
    public function create(array $data, int $userId): int
    {
        // Validate unique constraints
        if (!empty($data['nidn']) && $this->dosenRepo->nidnExists($data['nidn'])) {
            throw new InvalidArgumentException('NIDN already exists');
        }

        if ($this->dosenRepo->emailExists($data['email'])) {
            throw new InvalidArgumentException('Email already exists');
        }

        // Validate entity
        $dosen = new Dosen($data);

        // Insert into database
        $idDosen = $this->dosenRepo->create($dosen->toArray());

        // Log audit
        $this->auditLog->log(
            'dosen',
            $idDosen,
            'create',
            null,
            $dosen->toArray(),
            $userId
        );

        return $idDosen;
    }

    /**
     * Update an existing dosen
     */
    public function update(string $idDosen, array $data, int $userId): bool
    {
        // Get existing dosen
        $existing = $this->dosenRepo->findById($idDosen);
        if (!$existing) {
            throw new InvalidArgumentException('Dosen not found');
        }

        // Validate unique constraints (excluding current dosen)
        if (!empty($data['nidn']) && $this->dosenRepo->nidnExists($data['nidn'], $idDosen)) {
            throw new InvalidArgumentException('NIDN already exists');
        }

        if (isset($data['email']) && $this->dosenRepo->emailExists($data['email'], $idDosen)) {
            throw new InvalidArgumentException('Email already exists');
        }

        // Merge with existing data
        $updateData = array_merge($existing, $data);
        $updateData['id_dosen'] = $idDosen;

        // Validate entity
        $dosen = new Dosen($updateData);

        // Update in database
        $success = $this->dosenRepo->update($idDosen, $dosen->toArray());

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'dosen',
                $idDosen,
                'update',
                $existing,
                $dosen->toArray(),
                $userId
            );
        }

        return $success;
    }

    /**
     * Change dosen status
     */
    public function changeStatus(string $idDosen, string $newStatus, int $userId): bool
    {
        // Get existing dosen
        $existing = $this->dosenRepo->findById($idDosen);
        if (!$existing) {
            throw new InvalidArgumentException('Dosen not found');
        }

        // Validate status
        if (!in_array($newStatus, Dosen::getValidStatus())) {
            throw new InvalidArgumentException('Invalid status');
        }

        // Check if status is actually changing
        if ($existing['status'] === $newStatus) {
            return true; // No change needed
        }

        // Business rule: Cannot reactivate a retired dosen
        if ($existing['status'] === 'pensiun' && $newStatus === 'aktif') {
            throw new RuntimeException('Cannot reactivate a retired dosen');
        }

        // Update status
        $success = $this->dosenRepo->update($idDosen, ['status' => $newStatus]);

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'dosen',
                $idDosen,
                'status_change',
                ['status' => $existing['status']],
                ['status' => $newStatus],
                $userId
            );
        }

        return $success;
    }

    /**
     * Delete a dosen
     */
    public function delete(string $idDosen, int $userId): bool
    {
        // Get existing dosen
        $existing = $this->dosenRepo->findById($idDosen);
        if (!$existing) {
            throw new InvalidArgumentException('Dosen not found');
        }

        // Business rule: Cannot delete dosen with active teaching assignments
        // This will be enforced by database foreign key constraints
        // If violation occurs, catch and throw user-friendly message

        try {
            $success = $this->dosenRepo->delete($idDosen);

            if ($success) {
                // Log audit
                $this->auditLog->log(
                    'dosen',
                    $idDosen,
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
                    'Cannot delete dosen with existing teaching assignments or related data'
                );
            }
            throw $e;
        }
    }

    /**
     * Get dosen by ID with details
     */
    public function getById(string $idDosen): ?array
    {
        return $this->dosenRepo->findByIdWithDetails($idDosen);
    }

    /**
     * Get dosen by NIDN
     */
    public function getByNidn(string $nidn): ?array
    {
        return $this->dosenRepo->findByNidn($nidn);
    }

    /**
     * Get all dosen with filters
     */
    public function getAll(?array $filters = []): array
    {
        return $this->dosenRepo->getAllWithDetails($filters);
    }

    /**
     * Get dosen by prodi
     */
    public function getByProdi(string $idProdi, ?array $filters = []): array
    {
        return $this->dosenRepo->findByProdi($idProdi, $filters);
    }

    /**
     * Get dosen by status
     */
    public function getByStatus(string $status): array
    {
        return $this->dosenRepo->findByStatus($status);
    }

    /**
     * Search dosen
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        if (strlen($keyword) < 2) {
            throw new InvalidArgumentException('Search keyword must be at least 2 characters');
        }

        return $this->dosenRepo->search($keyword, $filters);
    }

    /**
     * Get dosen statistics
     */
    public function getStatistics(): array
    {
        return [
            'by_status' => $this->dosenRepo->getStatisticsByStatus(),
            'by_prodi' => $this->dosenRepo->getStatisticsByProdi(),
        ];
    }

    /**
     * Get dosen with teaching load
     */
    public function getDosenWithTeachingLoad(?string $tahunAjaran = null, ?string $semester = null): array
    {
        return $this->dosenRepo->getDosenWithTeachingLoad($tahunAjaran, $semester);
    }

    /**
     * Create user account for dosen
     */
    public function createUserAccount(string $idDosen, array $userData, int $createdBy): int
    {
        // Get dosen info
        $dosen = $this->dosenRepo->findById($idDosen);
        if (!$dosen) {
            throw new InvalidArgumentException('Dosen not found');
        }

        // Prepare user data
        $userCreateData = [
            'username' => $userData['username'] ?? $idDosen,
            'email' => $dosen['email'],
            'password' => $userData['password'],
            'user_type' => 'dosen',
            'ref_id' => $idDosen,
        ];

        // Check if user already exists
        $existingUser = $this->userRepo->findOne(['ref_id' => $idDosen, 'user_type' => 'dosen']);
        if ($existingUser) {
            throw new RuntimeException('User account already exists for this dosen');
        }

        // Create user account
        $idUser = $this->userRepo->create($userCreateData);

        // Assign role (default: dosen role)
        $this->userRepo->assignRole($idUser, 'dosen');

        // Log audit
        $this->auditLog->log(
            'dosen',
            $idDosen,
            'user_account_created',
            null,
            ['id_user' => $idUser, 'username' => $userCreateData['username']],
            $createdBy
        );

        return $idUser;
    }
}
