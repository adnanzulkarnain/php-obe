<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\AuditLogRepository;
use App\Middleware\AuthMiddleware;

/**
 * Audit Log Controller
 * Handles audit trail and activity logging endpoints
 */
class AuditLogController
{
    private AuditLogRepository $repository;

    public function __construct()
    {
        $this->repository = new AuditLogRepository();
    }

    /**
     * Get all audit logs with filters and pagination
     * GET /api/audit-logs?table_name=xxx&action=xxx&user_id=xxx&page=1&limit=50
     */
    public function index(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $filters = [
                'table_name' => Request::input('table_name'),
                'action' => Request::input('action'),
                'user_id' => Request::input('user_id'),
                'date_from' => Request::input('date_from'),
                'date_to' => Request::input('date_to')
            ];

            // Remove null filters
            $filters = array_filter($filters, fn($value) => $value !== null);

            $page = max(1, (int)Request::input('page', 1));
            $limit = min(100, max(10, (int)Request::input('limit', 50)));
            $offset = ($page - 1) * $limit;

            $logs = $this->repository->getAllWithFilters($filters, $limit, $offset);
            $total = $this->repository->countWithFilters($filters);

            Response::success([
                'logs' => $logs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get audit logs for specific table and record
     * GET /api/audit-logs/:table/:record_id
     */
    public function getByRecord(string $tableName, string $recordId): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi', 'dosen');

            $logs = $this->repository->findByTableAndRecord($tableName, $recordId);

            Response::success($logs);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get audit logs by user
     * GET /api/audit-logs/user/:user_id
     */
    public function getByUser(string $userId): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $limit = min(100, max(10, (int)Request::input('limit', 100)));
            $logs = $this->repository->findByUser((int)$userId, $limit);

            Response::success($logs);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get recent activity
     * GET /api/audit-logs/recent
     */
    public function getRecent(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $limit = min(100, max(10, (int)Request::input('limit', 50)));
            $logs = $this->repository->getRecentActivity($limit);

            Response::success($logs);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get my audit logs (current user)
     * GET /api/audit-logs/me
     */
    public function getMyLogs(): void
    {
        try {
            $user = AuthMiddleware::user();
            $limit = min(100, max(10, (int)Request::input('limit', 50)));

            $logs = $this->repository->findByUser($user['id_user'], $limit);

            Response::success($logs);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
