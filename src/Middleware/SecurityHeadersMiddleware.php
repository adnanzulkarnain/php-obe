<?php

namespace App\Middleware;

/**
 * Security Headers Middleware
 * Adds security headers to HTTP responses
 */
class SecurityHeadersMiddleware
{
    /**
     * Apply security headers
     */
    public static function handle(): void
    {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        $csp = self::getContentSecurityPolicy();
        header("Content-Security-Policy: {$csp}");

        // Permissions Policy (formerly Feature Policy)
        $permissionsPolicy = self::getPermissionsPolicy();
        header("Permissions-Policy: {$permissionsPolicy}");

        // HSTS (HTTP Strict Transport Security) - only for HTTPS
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Remove server information
        header_remove('X-Powered-By');
        header_remove('Server');
    }

    /**
     * Get Content Security Policy
     */
    private static function getContentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://unpkg.com",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];

        // Allow more relaxed CSP in development
        if (getenv('APP_ENV') === 'development') {
            $directives[] = "upgrade-insecure-requests";
        }

        return implode('; ', $directives);
    }

    /**
     * Get Permissions Policy
     */
    private static function getPermissionsPolicy(): string
    {
        $policies = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ];

        return implode(', ', $policies);
    }

    /**
     * Check if connection is HTTPS
     */
    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        return false;
    }
}
