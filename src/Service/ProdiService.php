<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProdiRepository;
use App\Repository\FakultasRepository;
use App\Service\AuditLogService;
use App\Entity\Prodi;
use InvalidArgumentException;
use RuntimeException;

/**
 * Prodi Service
 * Business logic for prodi (study program) management
 */
class ProdiService
{
    private ProdiRepository $prodiRepo;
    private FakultasRepository $fakultasRepo;
    private AuditLogService $auditLog;

    public function __construct(
        ProdiRepository $prodiRepo,
        FakultasRepository $fakultasRepo,
        AuditLogService $auditLog
    ) {
        $this->prodiRepo = $prodiRepo;
        $this->fakultasRepo = $fakultasRepo;
        $this->auditLog = $auditLog;
    }

    /**
     * Create a new prodi
     */
    public function create(array $data, int $userId): string
    {
        // Check if ID already exists
        if ($this->prodiRepo->findById($data['id_prodi'])) {
            throw new InvalidArgumentException('ID Prodi already exists');
        }

        // Validate fakultas exists
        if (!$this->fakultasRepo->findById($data['id_fakultas'])) {
            throw new InvalidArgumentException('Fakultas not found');
        }

        // Validate entity
        $prodi = new Prodi($data);

        // Insert into database
        $idProdi = $this->prodiRepo->create($prodi->toArray());

        // Log audit
        $this->auditLog->log(
            'prodi',
            $idProdi,
            'create',
            null,
            $prodi->toArray(),
            $userId
        );

        return $idProdi;
    }

    /**
     * Update an existing prodi
     */
    public function update(string $idProdi, array $data, int $userId): bool
    {
        // Get existing prodi
        $existing = $this->prodiRepo->findById($idProdi);
        if (!$existing) {
            throw new InvalidArgumentException('Prodi not found');
        }

        // If fakultas is being changed, validate it exists
        if (isset($data['id_fakultas']) && $data['id_fakultas'] !== $existing['id_fakultas']) {
            if (!$this->fakultasRepo->findById($data['id_fakultas'])) {
                throw new InvalidArgumentException('Fakultas not found');
            }
        }

        // Merge with existing data
        $updateData = array_merge($existing, $data);
        $updateData['id_prodi'] = $idProdi;

        // Validate entity
        $prodi = new Prodi($updateData);

        // Update in database
        $success = $this->prodiRepo->update($idProdi, $prodi->toArray());

        if ($success) {
            // Log audit
            $this->auditLog->log(
                'prodi',
                $idProdi,
                'update',
                $existing,
                $prodi->toArray(),
                $userId
            );
        }

        return $success;
    }

    /**
     * Delete a prodi
     */
    public function delete(string $idProdi, int $userId): bool
    {
        // Get existing prodi
        $existing = $this->prodiRepo->findById($idProdi);
        if (!$existing) {
            throw new InvalidArgumentException('Prodi not found');
        }

        // Business rule: Cannot delete prodi with related data
        // This will be enforced by database foreign key constraints
        try {
            $success = $this->prodiRepo->delete($idProdi);

            if ($success) {
                // Log audit
                $this->auditLog->log(
                    'prodi',
                    $idProdi,
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
                    'Cannot delete prodi with existing kurikulum, dosen, mahasiswa, or related data'
                );
            }
            throw $e;
        }
    }

    /**
     * Get prodi by ID with details
     */
    public function getById(string $idProdi): ?array
    {
        return $this->prodiRepo->findByIdWithDetails($idProdi);
    }

    /**
     * Get all prodi with filters
     */
    public function getAll(?array $filters = []): array
    {
        return $this->prodiRepo->getAllWithFakultas($filters);
    }

    /**
     * Get prodi by fakultas
     */
    public function getByFakultas(string $idFakultas): array
    {
        // Validate fakultas exists
        if (!$this->fakultasRepo->findById($idFakultas)) {
            throw new InvalidArgumentException('Fakultas not found');
        }

        return $this->prodiRepo->findByFakultas($idFakultas);
    }

    /**
     * Get prodi by jenjang
     */
    public function getByJenjang(string $jenjang): array
    {
        // Validate jenjang
        if (!in_array($jenjang, Prodi::getValidJenjang())) {
            throw new InvalidArgumentException('Invalid jenjang');
        }

        return $this->prodiRepo->findByJenjang($jenjang);
    }

    /**
     * Search prodi
     */
    public function search(string $keyword, ?array $filters = []): array
    {
        if (strlen($keyword) < 2) {
            throw new InvalidArgumentException('Search keyword must be at least 2 characters');
        }

        return $this->prodiRepo->search($keyword, $filters);
    }

    /**
     * Get prodi statistics
     */
    public function getStatistics(): array
    {
        return [
            'general' => $this->prodiRepo->getStatistics(),
            'by_fakultas' => $this->prodiRepo->getStatisticsByFakultas(),
            'by_jenjang' => $this->prodiRepo->getStatisticsByJenjang(),
        ];
    }

    /**
     * Get statistics by fakultas
     */
    public function getStatisticsByFakultas(): array
    {
        return $this->prodiRepo->getStatisticsByFakultas();
    }

    /**
     * Get statistics by jenjang
     */
    public function getStatisticsByJenjang(): array
    {
        return $this->prodiRepo->getStatisticsByJenjang();
    }
}
