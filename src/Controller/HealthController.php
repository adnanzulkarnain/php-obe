<?php

namespace App\Controller;

use App\Core\Response;
use App\Config\Database;
use PDO;

/**
 * Health Check Controller
 * Provides system health and monitoring endpoints
 */
class HealthController
{
    /**
     * Basic health check
     * GET /api/health
     */
    public static function check(): void
    {
        Response::json([
            'status' => 'OK',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'version' => '1.0.0'
        ]);
    }

    /**
     * Detailed health check
     * GET /api/health/detailed
     */
    public static function detailed(): void
    {
        $checks = [
            'app' => self::checkApplication(),
            'database' => self::checkDatabase(),
            'storage' => self::checkStorage(),
            'dependencies' => self::checkDependencies()
        ];

        $allHealthy = true;
        foreach ($checks as $check) {
            if ($check['status'] !== 'healthy') {
                $allHealthy = false;
                break;
            }
        }

        Response::json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $checks,
            'system' => self::getSystemInfo()
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Application metrics
     * GET /api/health/metrics
     */
    public static function metrics(): void
    {
        $metrics = [
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => self::getMemoryLimit(),
                'current_formatted' => self::formatBytes(memory_get_usage(true)),
                'peak_formatted' => self::formatBytes(memory_get_peak_usage(true)),
                'limit_formatted' => self::formatBytes(self::getMemoryLimit())
            ],
            'uptime' => [
                'server' => self::getServerUptime(),
                'php' => $_SERVER['REQUEST_TIME'] ?? time()
            ],
            'requests' => [
                'current' => 1, // Would need counter in production
                'total' => 0 // Would need persistent storage
            ],
            'database' => self::getDatabaseMetrics()
        ];

        Response::json([
            'success' => true,
            'metrics' => $metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check application health
     */
    private static function checkApplication(): array
    {
        $status = 'healthy';
        $details = [];

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $requiredVersion = '8.3.0';

        if (version_compare($phpVersion, $requiredVersion, '<')) {
            $status = 'unhealthy';
            $details[] = "PHP version {$phpVersion} is below required {$requiredVersion}";
        }

        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_pgsql', 'json', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $status = 'unhealthy';
                $details[] = "Required extension '{$ext}' is not loaded";
            }
        }

        // Check memory limit
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();

        if ($memoryUsage > ($memoryLimit * 0.9)) {
            $status = 'warning';
            $details[] = "Memory usage is high: " . self::formatBytes($memoryUsage);
        }

        return [
            'status' => $status,
            'message' => $status === 'healthy' ? 'Application is running normally' : 'Application has issues',
            'details' => $details,
            'php_version' => $phpVersion
        ];
    }

    /**
     * Check database health
     */
    private static function checkDatabase(): array
    {
        try {
            Database::connect();
            $pdo = Database::getConnection();

            // Test connection
            $pdo->query('SELECT 1');

            // Get database version
            $stmt = $pdo->query('SELECT version()');
            $version = $stmt->fetchColumn();

            // Check connection count
            $stmt = $pdo->query('SELECT COUNT(*) FROM pg_stat_activity');
            $connections = $stmt->fetchColumn();

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'version' => $version,
                'connections' => (int)$connections,
                'response_time_ms' => 0 // Would measure in production
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage health
     */
    private static function checkStorage(): array
    {
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';
        $logsPath = $_ENV['LOG_PATH'] ?? 'logs/app.log';

        $issues = [];

        // Check upload directory
        if (!is_dir($uploadPath)) {
            $issues[] = "Upload directory not found: {$uploadPath}";
        } elseif (!is_writable($uploadPath)) {
            $issues[] = "Upload directory not writable: {$uploadPath}";
        }

        // Check logs directory
        $logsDir = dirname($logsPath);
        if (!is_dir($logsDir)) {
            $issues[] = "Logs directory not found: {$logsDir}";
        } elseif (!is_writable($logsDir)) {
            $issues[] = "Logs directory not writable: {$logsDir}";
        }

        // Check disk space
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        if ($usedPercent > 90) {
            $issues[] = "Disk usage is high: " . round($usedPercent, 2) . "%";
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'message' => empty($issues) ? 'Storage is accessible' : 'Storage has issues',
            'details' => $issues,
            'disk' => [
                'free' => self::formatBytes($freeSpace),
                'total' => self::formatBytes($totalSpace),
                'used_percent' => round($usedPercent, 2)
            ]
        ];
    }

    /**
     * Check dependencies
     */
    private static function checkDependencies(): array
    {
        $composerLock = __DIR__ . '/../../composer.lock';

        if (!file_exists($composerLock)) {
            return [
                'status' => 'warning',
                'message' => 'composer.lock not found',
                'details' => ['Run composer install']
            ];
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        $packageCount = count($lockData['packages'] ?? []) + count($lockData['packages-dev'] ?? []);

        return [
            'status' => 'healthy',
            'message' => 'Dependencies installed',
            'packages_count' => $packageCount
        ];
    }

    /**
     * Get system information
     */
    private static function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'hostname' => gethostname(),
            'ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown'
        ];
    }

    /**
     * Get database metrics
     */
    private static function getDatabaseMetrics(): array
    {
        try {
            Database::connect();
            $pdo = Database::getConnection();

            $stmt = $pdo->query('SELECT COUNT(*) FROM pg_stat_activity WHERE datname = current_database()');
            $connections = $stmt->fetchColumn();

            $stmt = $pdo->query('SELECT pg_database_size(current_database())');
            $dbSize = $stmt->fetchColumn();

            return [
                'connections' => (int)$connections,
                'size_bytes' => (int)$dbSize,
                'size_formatted' => self::formatBytes((int)$dbSize)
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get memory limit in bytes
     */
    private static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get server uptime
     */
    private static function getServerUptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (int)explode(' ', $uptime)[0];
                return self::formatUptime($seconds);
            }
        }

        return null;
    }

    /**
     * Format uptime seconds
     */
    private static function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '0m';
    }
}
