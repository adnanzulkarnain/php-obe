<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Utils\JWTHelper;

/**
 * Authentication Service
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Login user
     */
    public function login(string $username, string $password): array
    {
        // Find user
        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            throw new \Exception('Username atau password salah', 401);
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception('Username atau password salah', 401);
        }

        // Check if user is active
        if (!$user['is_active']) {
            throw new \Exception('Akun Anda tidak aktif', 403);
        }

        // Generate JWT token
        $tokenData = [
            'id_user' => $user['id_user'],
            'username' => $user['username'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'ref_id' => $user['ref_id'],
        ];

        $token = JWTHelper::encode($tokenData);
        $refreshToken = JWTHelper::encodeRefreshToken(['id_user' => $user['id_user']]);

        // Update last login
        $this->userRepository->updateLastLogin($user['id_user']);

        return [
            'user' => [
                'id_user' => $user['id_user'],
                'username' => $user['username'],
                'email' => $user['email'],
                'user_type' => $user['user_type'],
                'ref_id' => $user['ref_id'],
            ],
            'token' => $token,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Get user profile
     */
    public function getProfile(int $userId): array
    {
        $user = $this->userRepository->findWithRoles($userId);

        if (!$user) {
            throw new \Exception('User tidak ditemukan', 404);
        }

        unset($user['password_hash']);

        return $user;
    }

    /**
     * Change password
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('User tidak ditemukan', 404);
        }

        // Verify old password
        if (!password_verify($oldPassword, $user['password_hash'])) {
            throw new \Exception('Password lama tidak sesuai', 400);
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new \Exception('Password minimal 8 karakter', 400);
        }

        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->userRepository->update($userId, ['password_hash' => $passwordHash]);
    }
}
