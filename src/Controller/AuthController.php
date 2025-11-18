<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Middleware\AuthMiddleware;

/**
 * Authentication Controller
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Login
     * POST /api/auth/login
     */
    public function login(): void
    {
        try {
            $data = Request::only(['username', 'password']);

            // Validation
            if (empty($data['username']) || empty($data['password'])) {
                Response::validationError([
                    'username' => empty($data['username']) ? 'Username wajib diisi' : null,
                    'password' => empty($data['password']) ? 'Password wajib diisi' : null,
                ]);
            }

            $result = $this->authService->login($data['username'], $data['password']);

            Response::success($result, 'Login berhasil');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get current user profile
     * GET /api/auth/profile
     */
    public function profile(): void
    {
        try {
            $user = AuthMiddleware::user();

            if (!$user) {
                Response::unauthorized('User tidak ditemukan');
            }

            $profile = $this->authService->getProfile($user['id_user']);

            Response::success($profile);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Logout
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        // For stateless JWT, logout is handled on client side
        // Here we can add token to blacklist if needed

        Response::success(null, 'Logout berhasil');
    }

    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword(): void
    {
        try {
            $user = AuthMiddleware::user();
            $data = Request::only(['old_password', 'new_password']);

            // Validation
            if (empty($data['old_password']) || empty($data['new_password'])) {
                Response::validationError([
                    'old_password' => empty($data['old_password']) ? 'Password lama wajib diisi' : null,
                    'new_password' => empty($data['new_password']) ? 'Password baru wajib diisi' : null,
                ]);
            }

            $this->authService->changePassword(
                $user['id_user'],
                $data['old_password'],
                $data['new_password']
            );

            Response::success(null, 'Password berhasil diubah');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
