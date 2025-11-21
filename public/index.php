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
use App\Core\Request;
use App\Core\ExceptionHandler;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', (string) ($_ENV['APP_DEBUG'] ?? '0'));

// Register exception handler
ExceptionHandler::register();

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

// Apply rate limiting
$request = new Request();
RateLimitMiddleware::handle($request);

// Load routes
require_once __DIR__ . '/../src/routes.php';

// Dispatch request
$router->dispatch();
