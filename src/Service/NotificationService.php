<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\BaseRepository;

/**
 * Notification Service
 * Business logic for notification management
 */
class NotificationService
{
    private BaseRepository $repository;

    public function __construct()
    {
        $this->repository = new BaseRepository();
        $this->repository->setTable('notifications');
        $this->repository->setPrimaryKey('id_notif');
    }

    /**
     * Get notifications by user
     */
    public function getByUser(int $userId, ?bool $unreadOnly = false): array
    {
        $sql = "
            SELECT *
            FROM notifications
            WHERE user_id = :user_id
        ";

        $params = ['user_id' => $userId];

        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";

        return $this->repository->query($sql, $params);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = :user_id AND is_read = FALSE
        ";

        $result = $this->repository->queryOne($sql, ['user_id' => $userId]);
        return (int)$result['count'];
    }

    /**
     * Create notification
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        $sql = "
            INSERT INTO notifications
            (user_id, type, title, message, link, is_read, created_at)
            VALUES
            (:user_id, :type, :title, :message, :link, FALSE, NOW())
            RETURNING id_notif
        ";

        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link
        ]);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_notif'];
    }

    /**
     * Mark as read
     */
    public function markAsRead(int $idNotif, int $userId): void
    {
        $sql = "
            UPDATE notifications
            SET is_read = TRUE, read_at = NOW()
            WHERE id_notif = :id_notif AND user_id = :user_id
        ";

        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute([
            'id_notif' => $idNotif,
            'user_id' => $userId
        ]);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(int $userId): void
    {
        $sql = "
            UPDATE notifications
            SET is_read = TRUE, read_at = NOW()
            WHERE user_id = :user_id AND is_read = FALSE
        ";

        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Delete notification
     */
    public function delete(int $idNotif, int $userId): void
    {
        $sql = "DELETE FROM notifications WHERE id_notif = :id_notif AND user_id = :user_id";

        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute([
            'id_notif' => $idNotif,
            'user_id' => $userId
        ]);
    }

    /**
     * Broadcast notification to multiple users
     */
    public function broadcast(array $userIds, string $type, string $title, string $message, ?string $link = null): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            try {
                $idNotif = $this->create((int)$userId, $type, $title, $message, $link);
                $results['success'][] = $userId;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Create notification for role
     * Send to all users with specific role
     */
    public function notifyByRole(string $role, string $type, string $title, string $message, ?string $link = null): array
    {
        // Get all users with role
        $sql = "
            SELECT DISTINCT u.id_user
            FROM users u
            WHERE u.user_type = :role AND u.is_active = TRUE
        ";

        $users = $this->repository->query($sql, ['role' => $role]);
        $userIds = array_column($users, 'id_user');

        return $this->broadcast($userIds, $type, $title, $message, $link);
    }
}
