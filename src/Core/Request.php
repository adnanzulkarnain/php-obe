<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request Helper
 */
class Request
{
    /**
     * Get all input data
     */
    public static function all(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            return $_GET;
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $json = file_get_contents('php://input');
                return json_decode($json, true) ?? [];
            }

            return $_POST;
        }

        return [];
    }

    /**
     * Get specific input field
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        $data = self::all();
        return $data[$key] ?? $default;
    }

    /**
     * Get only specified fields
     */
    public static function only(array $keys): array
    {
        $data = self::all();
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Get all except specified fields
     */
    public static function except(array $keys): array
    {
        $data = self::all();
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get request URI
     */
    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Get request header
     */
    public static function header(string $key, ?string $default = null): ?string
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $_SERVER["HTTP_{$key}"] ?? $default;
    }

    /**
     * Get bearer token from Authorization header
     */
    public static function bearerToken(): ?string
    {
        $header = self::header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
}
