<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\NotificationService;
use App\Middleware\AuthMiddleware;

/**
 * Notification Controller
 * Handles notification-related API endpoints
 */
class NotificationController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    /**
     * Get all notifications for current user
     * GET /api/notifications
     */
    public function index(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $unreadOnly = $request->getQuery('unread_only') === 'true';

        $notifications = $this->service->getUserNotifications($user['id_user'], $unreadOnly);
        $unreadCount = $this->service->getUnreadCount($user['id_user']);

        Response::json([
            'success' => true,
            'data' => $notifications,
            'meta' => [
                'unread_count' => $unreadCount,
                'total' => count($notifications)
            ]
        ]);
    }

    /**
     * Get unread count
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $count = $this->service->getUnreadCount($user['id_user']);

        Response::json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Get notification by ID
     * GET /api/notifications/:id
     */
    public function show(Request $request, int $id): void
    {
        $notification = $this->service->getNotificationById($id);

        if (!$notification) {
            Response::json([
                'success' => false,
                'error' => 'Notification not found'
            ], 404);
            return;
        }

        // Verify ownership
        $user = AuthMiddleware::getCurrentUser();
        if ($notification['id_user'] !== $user['id_user']) {
            Response::json([
                'success' => false,
                'error' => 'Unauthorized access'
            ], 403);
            return;
        }

        Response::json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Mark notification as read
     * POST /api/notifications/:id/read
     */
    public function markAsRead(Request $request, int $id): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $success = $this->service->markAsRead($id, $user['id_user']);

        if (!$success) {
            Response::json([
                'success' => false,
                'error' => 'Failed to mark notification as read'
            ], 400);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     * POST /api/notifications/read-all
     */
    public function markAllAsRead(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $success = $this->service->markAllAsRead($user['id_user']);

        if (!$success) {
            Response::json([
                'success' => false,
                'error' => 'Failed to mark all notifications as read'
            ], 400);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete notification
     * DELETE /api/notifications/:id
     */
    public function delete(Request $request, int $id): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $success = $this->service->deleteNotification($id, $user['id_user']);

        if (!$success) {
            Response::json([
                'success' => false,
                'error' => 'Failed to delete notification'
            ], 400);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Create announcement (Admin/Kaprodi only)
     * POST /api/notifications/announcement
     */
    public function createAnnouncement(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();

        // Only admin and kaprodi can send announcements
        if (!in_array($user['role'], ['admin', 'kaprodi'])) {
            Response::json([
                'success' => false,
                'error' => 'Unauthorized. Only admin and kaprodi can send announcements.'
            ], 403);
            return;
        }

        $data = $request->getBody();

        // Validate required fields
        if (empty($data['user_ids']) || empty($data['judul']) || empty($data['pesan'])) {
            Response::json([
                'success' => false,
                'error' => 'Missing required fields: user_ids, judul, pesan'
            ], 400);
            return;
        }

        if (!is_array($data['user_ids'])) {
            Response::json([
                'success' => false,
                'error' => 'user_ids must be an array'
            ], 400);
            return;
        }

        $notificationIds = $this->service->sendAnnouncement(
            $data['user_ids'],
            $data['judul'],
            $data['pesan'],
            $data['link'] ?? null
        );

        Response::json([
            'success' => true,
            'message' => 'Announcement sent successfully',
            'data' => [
                'notification_ids' => $notificationIds,
                'recipients_count' => count($notificationIds)
            ]
        ], 201);
    }

    /**
     * Test email notification (for development)
     * POST /api/notifications/test-email
     */
    public function testEmail(Request $request): void
    {
        // Only in development/testing mode
        if (getenv('APP_ENV') !== 'development') {
            Response::json([
                'success' => false,
                'error' => 'Endpoint only available in development mode'
            ], 403);
            return;
        }

        $user = AuthMiddleware::getCurrentUser();

        $notificationId = $this->service->createNotification(
            $user['id_user'],
            'system',
            'Test Notification',
            'This is a test notification email from OBE System.',
            '/test',
            true
        );

        Response::json([
            'success' => true,
            'message' => 'Test notification created and email sent',
            'data' => [
                'notification_id' => $notificationId
            ]
        ]);
    }
}
