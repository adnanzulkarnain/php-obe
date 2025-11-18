<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Utils\JWTHelper;

/**
 * Authentication Middleware
 */
class AuthMiddleware
{
    public function handle(): void
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::unauthorized('Token tidak ditemukan');
        }

        try {
            $payload = JWTHelper::decode($token);

            // Store user data in request context
            $_SESSION['user'] = [
                'id_user' => $payload->id_user,
                'user_type' => $payload->user_type,
                'ref_id' => $payload->ref_id,
            ];
        } catch (\Exception $e) {
            Response::unauthorized('Token tidak valid atau sudah kadaluarsa');
        }
    }

    /**
     * Get current authenticated user
     */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user && $user['user_type'] === $role;
    }

    /**
     * Require specific role
     */
    public static function requireRole(string ...$roles): void
    {
        $user = self::user();

        if (!$user || !in_array($user['user_type'], $roles)) {
            Response::forbidden('Anda tidak memiliki akses ke resource ini');
        }
    }
}
