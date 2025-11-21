<?php

namespace App\Core;

use App\Exception\BaseException;
use App\Utils\Logger;
use Throwable;

/**
 * Exception Handler
 * Centralized exception handling
 */
class ExceptionHandler
{
    /**
     * Handle exception
     */
    public static function handle(Throwable $e): void
    {
        // Log exception
        self::logException($e);

        // Determine status code
        $statusCode = self::getStatusCode($e);

        // Build response
        $response = self::buildResponse($e);

        // Send response
        Response::json($response, $statusCode);
    }

    /**
     * Get HTTP status code from exception
     */
    private static function getStatusCode(Throwable $e): int
    {
        if ($e instanceof BaseException) {
            return $e->getStatusCode();
        }

        // Map standard exceptions to status codes
        $code = $e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    /**
     * Build error response
     */
    private static function buildResponse(Throwable $e): array
    {
        if ($e instanceof BaseException) {
            return $e->toArray();
        }

        $response = [
            'success' => false,
            'error' => $e->getMessage() ?: 'An error occurred',
            'code' => self::getStatusCode($e)
        ];

        // Add debug info in development
        if (self::isDebugMode()) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace()
            ];
        }

        return $response;
    }

    /**
     * Log exception
     */
    private static function logException(Throwable $e): void
    {
        // Use structured logging
        Logger::logException($e);

        // Also use error_log for backwards compatibility
        $message = sprintf(
            "[%s] %s in %s:%d",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
    }

    /**
     * Check if in debug mode
     */
    private static function isDebugMode(): bool
    {
        return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }

    /**
     * Register exception handler
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
