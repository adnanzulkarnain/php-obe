<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Rate Limit Middleware
 * Implements token bucket algorithm for API rate limiting
 *
 * Note: This is a simple in-memory implementation.
 * For production with multiple servers, use Redis or database.
 */
class RateLimitMiddleware
{
    private static array $storage = [];
    private static int $maxRequests = 100; // Max requests per window
    private static int $windowSeconds = 60; // Time window in seconds
    private static bool $enabled = true;

    /**
     * Initialize rate limiting configuration
     */
    public static function init(): void
    {
        self::$maxRequests = (int) (getenv('RATE_LIMIT_MAX') ?: 100);
        self::$windowSeconds = (int) (getenv('RATE_LIMIT_WINDOW') ?: 60);
        self::$enabled = getenv('RATE_LIMIT_ENABLED') !== 'false';
    }

    /**
     * Handle rate limiting
     */
    public static function handle(Request $request): bool
    {
        if (!self::$enabled) {
            return true;
        }

        if (self::$maxRequests === null) {
            self::init();
        }

        // Get identifier (IP address or user ID)
        $identifier = self::getIdentifier($request);

        // Check rate limit
        if (!self::checkLimit($identifier)) {
            self::sendRateLimitResponse();
            return false;
        }

        // Add rate limit headers
        self::addRateLimitHeaders($identifier);

        return true;
    }

    /**
     * Check if request is within rate limit
     */
    private static function checkLimit(string $identifier): bool
    {
        $now = time();

        // Initialize storage for identifier if not exists
        if (!isset(self::$storage[$identifier])) {
            self::$storage[$identifier] = [
                'requests' => [],
                'blocked_until' => 0
            ];
        }

        $data = &self::$storage[$identifier];

        // Check if currently blocked
        if ($data['blocked_until'] > $now) {
            return false;
        }

        // Remove old requests outside the window
        $data['requests'] = array_filter(
            $data['requests'],
            fn($timestamp) => $timestamp > ($now - self::$windowSeconds)
        );

        // Check if limit exceeded
        if (count($data['requests']) >= self::$maxRequests) {
            // Block for remaining window time
            $oldestRequest = min($data['requests']);
            $data['blocked_until'] = $oldestRequest + self::$windowSeconds;
            return false;
        }

        // Add current request
        $data['requests'][] = $now;

        return true;
    }

    /**
     * Get identifier for rate limiting
     */
    private static function getIdentifier(Request $request): string
    {
        // Try to get user ID from auth middleware
        $user = AuthMiddleware::getCurrentUser();

        if ($user) {
            return 'user_' . $user['id_user'];
        }

        // Fallback to IP address
        return 'ip_' . self::getClientIP();
    }

    /**
     * Get client IP address
     */
    private static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle multiple IPs in X-Forwarded-For
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
     * Get remaining requests for identifier
     */
    private static function getRemaining(string $identifier): int
    {
        if (!isset(self::$storage[$identifier])) {
            return self::$maxRequests;
        }

        $now = time();
        $data = self::$storage[$identifier];

        // Count valid requests in current window
        $validRequests = array_filter(
            $data['requests'],
            fn($timestamp) => $timestamp > ($now - self::$windowSeconds)
        );

        return max(0, self::$maxRequests - count($validRequests));
    }

    /**
     * Get reset time for identifier
     */
    private static function getResetTime(string $identifier): int
    {
        if (!isset(self::$storage[$identifier]) || empty(self::$storage[$identifier]['requests'])) {
            return time() + self::$windowSeconds;
        }

        $oldestRequest = min(self::$storage[$identifier]['requests']);
        return $oldestRequest + self::$windowSeconds;
    }

    /**
     * Add rate limit headers to response
     */
    private static function addRateLimitHeaders(string $identifier): void
    {
        header('X-RateLimit-Limit: ' . self::$maxRequests);
        header('X-RateLimit-Remaining: ' . self::getRemaining($identifier));
        header('X-RateLimit-Reset: ' . self::getResetTime($identifier));
        header('X-RateLimit-Window: ' . self::$windowSeconds);
    }

    /**
     * Send rate limit exceeded response
     */
    private static function sendRateLimitResponse(): void
    {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . self::$windowSeconds);

        Response::json([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => self::$windowSeconds
        ], 429);

        exit;
    }

    /**
     * Clear rate limit for identifier (useful for testing)
     */
    public static function clearLimit(string $identifier): void
    {
        unset(self::$storage[$identifier]);
    }

    /**
     * Clear all rate limits (useful for testing)
     */
    public static function clearAll(): void
    {
        self::$storage = [];
    }

    /**
     * Set rate limit configuration (useful for testing)
     */
    public static function setConfig(int $maxRequests, int $windowSeconds): void
    {
        self::$maxRequests = $maxRequests;
        self::$windowSeconds = $windowSeconds;
    }

    /**
     * Enable/disable rate limiting
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Get current storage (for debugging)
     */
    public static function getStorage(): array
    {
        return self::$storage;
    }
}
