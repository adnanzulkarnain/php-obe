<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\FakultasRepository;
use App\Service\AuditLogService;
use App\Entity\Fakultas;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fakultas Service
 * Business logic for fakultas (faculty/school) management
 */
class FakultasService
{
    private FakultasRepository $fakultasRepo;
    private AuditLogService $auditLog;

    public function __construct(
        FakultasRepository $fakultasRepo,
        AuditLogService $auditLog
    ) {
        $this->fakultasRepo = $fakultasRepo;
        $this->auditLog = $auditLog;
    }

    /**
     * Create a new fakultas
     */
    public function create(array $data, int $userId): string
    {
        // Check if ID already exists
        if ($this->fakultasRepo->findById($data['id_fakultas'])) {
            throw new InvalidArgumentException('ID Fakultas already exists');
        }

        // Validate entity
        $fakultas = new Fakultas($data);

        // Insert into database
        $idFakultas = $this->fakultasRepo->create($fakultas->toArray());

        // Log audit
        $this->auditLog->log(
            'fakultas',
            $idFakultas,
            'create',
            null,
            $fakultas->toArray(),
            $userId
        );

        return $idFakultas;
    }

    /**
     * Update an existing fakultas
     */
    public function update(string $idFakultas, array $data, int $userId): bool
    {
        // Get existing fakultas
        $existing = $this->fakultasRepo->findById($idFakultas);
        if (!$existing) {
            throw new InvalidArgumentException('Fakultas not found');
        }

        // Merge with existing data
        $updateData = array_merge($existing, $data);
        $updateData['id_fakultas'] = $idFakultas;

        // Validate entity
        $fakultas = new Fakultas($updateData);

        // Update in database
        $success = $this->fakultasRepo->update($idFakultas, $fakultas->toArray());

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'fakultas',
                $idFakultas,
                'update',
                $existing,
                $fakultas->toArray(),
                $userId
            );
        }

        return $success;
    }

    /**
     * Delete a fakultas
     */
    public function delete(string $idFakultas, int $userId): bool
    {
        // Get existing fakultas
        $existing = $this->fakultasRepo->findById($idFakultas);
        if (!$existing) {
            throw new InvalidArgumentException('Fakultas not found');
        }

        // Business rule: Cannot delete fakultas with prodi
        // This will be enforced by database foreign key constraints
        try {
            $success = $this->fakultasRepo->delete($idFakultas);

            if ($success) {
                // Log audit
                $this->auditLog->log(
                    'fakultas',
                    $idFakultas,
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
                    'Cannot delete fakultas with existing prodi or related data'
                );
            }
            throw $e;
        }
    }

    /**
     * Get fakultas by ID
     */
    public function getById(string $idFakultas): ?array
    {
        return $this->fakultasRepo->findByIdWithDetails($idFakultas);
    }

    /**
     * Get all fakultas
     */
    public function getAll(): array
    {
        return $this->fakultasRepo->getAllWithProdiCount();
    }

    /**
     * Search fakultas
     */
    public function search(string $keyword): array
    {
        if (strlen($keyword) < 2) {
            throw new InvalidArgumentException('Search keyword must be at least 2 characters');
        }

        return $this->fakultasRepo->search($keyword);
    }

    /**
     * Get fakultas statistics
     */
    public function getStatistics(): array
    {
        return $this->fakultasRepo->getStatistics();
    }
}
