<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\NotificationService;
use App\Middleware\AuthMiddleware;

/**
 * Notification Controller
 * Handles notification endpoints
 */
class NotificationController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    /**
     * Get notifications for current user
     * GET /api/notifications
     */
    public function index(): void
    {
        try {
            $user = AuthMiddleware::user();
            $unreadOnly = Request::get('unread_only') === 'true';

            $notifications = $this->service->getByUser($user['id_user'], $unreadOnly);
            Response::success($notifications);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get unread count
     * GET /api/notifications/unread-count
     */
    public function getUnreadCount(): void
    {
        try {
            $user = AuthMiddleware::user();
            $count = $this->service->getUnreadCount($user['id_user']);

            Response::success(['unread_count' => $count]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Mark notification as read
     * POST /api/notifications/:id/read
     */
    public function markAsRead(string $id): void
    {
        try {
            $user = AuthMiddleware::user();
            $this->service->markAsRead((int)$id, $user['id_user']);

            Response::success(null, 'Notifikasi ditandai sudah dibaca');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Mark all as read
     * POST /api/notifications/mark-all-read
     */
    public function markAllAsRead(): void
    {
        try {
            $user = AuthMiddleware::user();
            $this->service->markAllAsRead($user['id_user']);

            Response::success(null, 'Semua notifikasi ditandai sudah dibaca');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete notification
     * DELETE /api/notifications/:id
     */
    public function delete(string $id): void
    {
        try {
            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'Notifikasi berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create notification (admin only)
     * POST /api/notifications/create
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $user = AuthMiddleware::user();
            $data = Request::only(['user_id', 'type', 'title', 'message', 'link']);

            if (empty($data['user_id']) || empty($data['type']) || empty($data['title']) || empty($data['message'])) {
                Response::error('user_id, type, title, dan message wajib diisi', 400);
                return;
            }

            $idNotif = $this->service->create(
                (int)$data['user_id'],
                $data['type'],
                $data['title'],
                $data['message'],
                $data['link'] ?? null
            );

            Response::success(['id_notif' => $idNotif], 'Notifikasi berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Broadcast notification to multiple users (admin only)
     * POST /api/notifications/broadcast
     */
    public function broadcast(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $data = Request::only(['user_ids', 'type', 'title', 'message', 'link']);

            if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
                Response::error('user_ids harus berupa array', 400);
                return;
            }

            if (empty($data['type']) || empty($data['title']) || empty($data['message'])) {
                Response::error('type, title, dan message wajib diisi', 400);
                return;
            }

            $results = $this->service->broadcast(
                $data['user_ids'],
                $data['type'],
                $data['title'],
                $data['message'],
                $data['link'] ?? null
            );

            Response::success($results, 'Broadcast notifikasi selesai');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Notify by role (admin only)
     * POST /api/notifications/notify-role
     */
    public function notifyByRole(): void
    {
        try {
            AuthMiddleware::requireRole('admin', 'kaprodi');

            $data = Request::only(['role', 'type', 'title', 'message', 'link']);

            if (empty($data['role']) || empty($data['type']) || empty($data['title']) || empty($data['message'])) {
                Response::error('role, type, title, dan message wajib diisi', 400);
                return;
            }

            $results = $this->service->notifyByRole(
                $data['role'],
                $data['type'],
                $data['title'],
                $data['message'],
                $data['link'] ?? null
            );

            Response::success($results, 'Notifikasi berhasil dikirim ke role ' . $data['role']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
