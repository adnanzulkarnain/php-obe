<?php

namespace App\Repository;

use App\Core\BaseRepository;
use App\Entity\Notification;

/**
 * Notification Repository
 * Handles database operations for notifications
 */
class NotificationRepository extends BaseRepository
{
    /**
     * Find all notifications for a user
     */
    public function findByUser(int $idUser, bool $unreadOnly = false): array
    {
        $sql = "SELECT * FROM notifications WHERE id_user = :id_user";

        if ($unreadOnly) {
            $sql .= " AND is_read = false";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_user' => $idUser]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find notification by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM notifications WHERE id_notification = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Create new notification
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO notifications (
                    id_user, tipe_notifikasi, judul, pesan, link,
                    is_read, is_sent_email, created_at
                ) VALUES (
                    :id_user, :tipe_notifikasi, :judul, :pesan, :link,
                    :is_read, :is_sent_email, CURRENT_TIMESTAMP
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_user' => $data['id_user'],
            'tipe_notifikasi' => $data['tipe_notifikasi'],
            'judul' => $data['judul'],
            'pesan' => $data['pesan'],
            'link' => $data['link'] ?? null,
            'is_read' => $data['is_read'] ?? false,
            'is_sent_email' => $data['is_sent_email'] ?? false
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): bool
    {
        $sql = "UPDATE notifications
                SET is_read = true, read_at = NOW()
                WHERE id_notification = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadForUser(int $idUser): bool
    {
        $sql = "UPDATE notifications
                SET is_read = true, read_at = NOW()
                WHERE id_user = :id_user AND is_read = false";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id_user' => $idUser]);
    }

    /**
     * Update email sent status
     */
    public function markEmailSent(int $id): bool
    {
        $sql = "UPDATE notifications
                SET is_sent_email = true
                WHERE id_notification = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete notification
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM notifications WHERE id_notification = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $idUser): int
    {
        $sql = "SELECT COUNT(*) FROM notifications
                WHERE id_user = :id_user AND is_read = false";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_user' => $idUser]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find recent notifications (last 7 days)
     */
    public function findRecent(int $idUser, int $days = 7): array
    {
        $sql = "SELECT * FROM notifications
                WHERE id_user = :id_user
                AND created_at >= NOW() - INTERVAL '$days days'
                ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_user' => $idUser]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find notifications by type
     */
    public function findByType(int $idUser, string $type): array
    {
        $sql = "SELECT * FROM notifications
                WHERE id_user = :id_user
                AND tipe_notifikasi = :type
                ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_user' => $idUser,
            'type' => $type
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOldNotifications(int $days = 90): int
    {
        $sql = "DELETE FROM notifications
                WHERE created_at < NOW() - INTERVAL '$days days'
                AND is_read = true";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
