<?php

namespace App\Service;

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Utils\EmailHelper;
use App\Entity\Notification;

/**
 * Notification Service
 * Business logic for notification management
 */
class NotificationService
{
    private NotificationRepository $repository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->repository = new NotificationRepository();
        $this->userRepository = new UserRepository();
    }

    /**
     * Get all notifications for a user
     */
    public function getUserNotifications(int $idUser, bool $unreadOnly = false): array
    {
        return $this->repository->findByUser($idUser, $unreadOnly);
    }

    /**
     * Get notification by ID
     */
    public function getNotificationById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $idUser): int
    {
        return $this->repository->getUnreadCount($idUser);
    }

    /**
     * Create and send notification
     *
     * @param int $idUser Target user ID
     * @param string $type Notification type
     * @param string $judul Title
     * @param string $pesan Message
     * @param string|null $link Optional link
     * @param bool $sendEmail Whether to send email
     * @return int Notification ID
     */
    public function createNotification(
        int $idUser,
        string $type,
        string $judul,
        string $pesan,
        ?string $link = null,
        bool $sendEmail = true
    ): int {
        // Create notification in database
        $data = [
            'id_user' => $idUser,
            'tipe_notifikasi' => $type,
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'is_read' => false,
            'is_sent_email' => false
        ];

        $notificationId = $this->repository->create($data);

        // Send email if requested
        if ($sendEmail) {
            $this->sendNotificationEmail($notificationId);
        }

        return $notificationId;
    }

    /**
     * Send notification email
     */
    public function sendNotificationEmail(int $notificationId): bool
    {
        $notification = $this->repository->findById($notificationId);

        if (!$notification) {
            return false;
        }

        // Get user email
        $user = $this->userRepository->findById($notification['id_user']);

        if (!$user || empty($user['email'])) {
            return false;
        }

        // Send email
        $success = EmailHelper::sendNotification(
            $user['email'],
            $notification['judul'],
            $notification['pesan'],
            $notification['link']
        );

        // Update email sent status
        if ($success) {
            $this->repository->markEmailSent($notificationId);
        }

        return $success;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id, int $idUser): bool
    {
        // Verify notification belongs to user
        $notification = $this->repository->findById($id);

        if (!$notification || $notification['id_user'] !== $idUser) {
            return false;
        }

        return $this->repository->markAsRead($id);
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead(int $idUser): bool
    {
        return $this->repository->markAllAsReadForUser($idUser);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $id, int $idUser): bool
    {
        // Verify notification belongs to user
        $notification = $this->repository->findById($id);

        if (!$notification || $notification['id_user'] !== $idUser) {
            return false;
        }

        return $this->repository->delete($id);
    }

    /**
     * Send RPS approval/rejection notification
     */
    public function notifyRPSApproval(
        int $idDosen,
        string $mataKuliah,
        bool $approved,
        ?string $komentar = null
    ): int {
        $type = $approved ? Notification::TYPE_RPS_APPROVAL : Notification::TYPE_RPS_REJECTION;
        $status = $approved ? 'Disetujui' : 'Ditolak';

        $judul = "RPS $mataKuliah - $status";
        $pesan = "RPS untuk mata kuliah $mataKuliah telah $status.";

        if ($komentar) {
            $pesan .= " Komentar: $komentar";
        }

        $notificationId = $this->createNotification(
            $idDosen,
            $type,
            $judul,
            $pesan,
            "/rps/detail",
            true
        );

        // Send custom RPS email
        $user = $this->userRepository->findById($idDosen);
        if ($user && !empty($user['email'])) {
            $dosenName = $user['nama'] ?? $user['username'];
            EmailHelper::sendRPSApprovalNotification(
                $user['email'],
                $dosenName,
                $mataKuliah,
                $status,
                $komentar
            );
        }

        return $notificationId;
    }

    /**
     * Send deadline reminder notification
     */
    public function notifyDeadline(
        int $idUser,
        string $taskName,
        string $deadline,
        string $link = null
    ): int {
        $judul = "Pengingat Deadline: $taskName";
        $pesan = "Deadline untuk $taskName adalah pada $deadline. Mohon segera menyelesaikan.";

        $notificationId = $this->createNotification(
            $idUser,
            Notification::TYPE_NILAI_DEADLINE,
            $judul,
            $pesan,
            $link,
            true
        );

        // Send custom deadline email
        $user = $this->userRepository->findById($idUser);
        if ($user && !empty($user['email'])) {
            $userName = $user['nama'] ?? $user['username'];
            EmailHelper::sendDeadlineReminder(
                $user['email'],
                $userName,
                $taskName,
                $deadline
            );
        }

        return $notificationId;
    }

    /**
     * Send announcement to multiple users
     */
    public function sendAnnouncement(
        array $userIds,
        string $judul,
        string $pesan,
        ?string $link = null
    ): array {
        $notificationIds = [];

        foreach ($userIds as $idUser) {
            $notificationIds[] = $this->createNotification(
                $idUser,
                Notification::TYPE_ANNOUNCEMENT,
                $judul,
                $pesan,
                $link,
                true
            );
        }

        return $notificationIds;
    }

    /**
     * Cleanup old notifications
     */
    public function cleanupOldNotifications(int $days = 90): int
    {
        return $this->repository->deleteOldNotifications($days);
    }
}
