<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * Database Configuration and Connection Manager
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Get database connection (Singleton pattern)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['DB_NAME'] ?? 'obe_system';
                $user = $_ENV['DB_USER'] ?? 'obe_user';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    // Enable persistent connections for better performance
                    PDO::ATTR_PERSISTENT => true,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException(
                    "Database connection failed: " . $e->getMessage(),
                    500
                );
            }
        }

        return self::$connection;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Check if in transaction
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }
}
