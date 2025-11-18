<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Audit Log Repository
 */
class AuditLogRepository extends BaseRepository
{
    protected string $table = 'audit_log';
    protected string $primaryKey = 'id_audit';

    /**
     * Get logs for a specific table and record
     */
    public function findByTableAndRecord(string $tableName, int|string $recordId): array
    {
        return $this->findAll(
            ['table_name' => $tableName, 'record_id' => $recordId],
            ['created_at' => 'DESC']
        );
    }

    /**
     * Get logs by user
     */
    public function findByUser(int $userId, int $limit = 100): array
    {
        return $this->findAll(
            ['user_id' => $userId],
            ['created_at' => 'DESC'],
            $limit
        );
    }
}
