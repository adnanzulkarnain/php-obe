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

    /**
     * Get all logs with filters and pagination
     */
    public function getAllWithFilters(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM audit_log WHERE 1=1";
        $params = [];

        if (!empty($filters['table_name'])) {
            $sql .= " AND table_name = :table_name";
            $params['table_name'] = $filters['table_name'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->query($sql, $params);
    }

    /**
     * Count logs with filters
     */
    public function countWithFilters(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM audit_log WHERE 1=1";
        $params = [];

        if (!empty($filters['table_name'])) {
            $sql .= " AND table_name = :table_name";
            $params['table_name'] = $filters['table_name'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $result = $this->queryOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 50): array
    {
        $sql = "
            SELECT
                al.*,
                u.username,
                u.full_name
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id_user
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";

        return $this->query($sql, ['limit' => $limit]);
    }
}
