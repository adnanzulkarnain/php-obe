<?php

namespace App\Middleware;

use App\Core\Request;
use App\Utils\Logger;

/**
 * Request Logging Middleware
 * Logs all incoming HTTP requests
 */
class RequestLoggingMiddleware
{
    private static float $startTime;

    /**
     * Start request logging
     */
    public static function start(Request $request): void
    {
        self::$startTime = microtime(true);

        // Log request
        Logger::logRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            [
                'ip' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'query' => $_GET,
                'body_size' => $_SERVER['CONTENT_LENGTH'] ?? 0
            ]
        );
    }

    /**
     * End request logging
     */
    public static function end(int $statusCode = 200, $responseData = null): void
    {
        $duration = (microtime(true) - self::$startTime) * 1000; // Convert to ms

        Logger::logResponse($statusCode, [
            'duration_ms' => round($duration, 2),
            'memory_usage' => self::formatBytes(memory_get_peak_usage(true))
        ]);

        // Log performance warning if slow
        if ($duration > 1000) {
            Logger::warning("Slow request detected", [
                'duration_ms' => round($duration, 2),
                'uri' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD']
            ]);
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
