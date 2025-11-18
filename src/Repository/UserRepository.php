<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * User Repository
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected string $primaryKey = 'id_user';

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findOne(['username' => $username]);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Get user with roles
     */
    public function findWithRoles(int $userId): ?array
    {
        $sql = "
            SELECT
                u.*,
                json_agg(
                    json_build_object(
                        'id_role', r.id_role,
                        'role_name', r.role_name,
                        'description', r.description
                    )
                ) as roles
            FROM users u
            LEFT JOIN user_roles ur ON ur.id_user = u.id_user
            LEFT JOIN roles r ON r.id_role = ur.id_role
            WHERE u.id_user = :user_id
            GROUP BY u.id_user
        ";

        return $this->queryOne($sql, ['user_id' => $userId]);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
}
