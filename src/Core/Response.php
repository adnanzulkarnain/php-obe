<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response Helper
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): void {
        self::json([
            'success' => false,
            'error' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Send validation error response
     */
    public static function validationError(array $errors): void
    {
        self::json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json([
            'success' => false,
            'error' => $message,
        ], 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::json([
            'success' => false,
            'error' => $message,
        ], 403);
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json([
            'success' => false,
            'error' => $message,
        ], 404);
    }
}
