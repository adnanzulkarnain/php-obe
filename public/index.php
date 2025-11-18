<?php

declare(strict_types=1);

/**
 * Entry Point - Sistem Informasi Kurikulum OBE
 *
 * PHP Version 8.3
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Response;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', (string) ($_ENV['APP_DEBUG'] ?? '0'));

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

    Response::json([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $_ENV['APP_DEBUG'] === 'true' ? $e->getTrace() : null
    ], $statusCode);
});

// Initialize router
$router = new Router();

// Apply global middleware
$corsMiddleware = new CorsMiddleware();
$corsMiddleware->handle();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::json(['status' => 'OK'], 200);
    exit;
}

// Load routes
require_once __DIR__ . '/../src/routes.php';

// Dispatch request
$router->dispatch();
