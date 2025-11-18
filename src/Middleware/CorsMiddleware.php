<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * CORS Middleware
 */
class CorsMiddleware
{
    public function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');

        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }

        header('Access-Control-Allow-Methods: ' . ($_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,PATCH,OPTIONS'));
        header('Access-Control-Allow-Headers: ' . ($_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With'));
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Credentials: true');
    }
}
