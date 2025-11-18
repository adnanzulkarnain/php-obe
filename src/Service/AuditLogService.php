<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;

/**
 * Audit Log Service
 */
class AuditLogService
{
    private AuditLogRepository $repository;

    public function __construct()
    {
        $this->repository = new AuditLogRepository();
    }

    /**
     * Log an action
     */
    public function log(
        string $tableName,
        int|string $recordId,
        string $action,
        ?array $oldData,
        ?array $newData,
        int $userId
    ): void {
        $logData = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->create($logData);
    }
}
