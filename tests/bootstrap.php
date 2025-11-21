<?php

/**
 * PHPUnit Bootstrap File
 * Loads autoloader and sets up test environment
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
if (file_exists(__DIR__ . '/../.env.test')) {
    $lines = file(__DIR__ . '/../.env.test', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('Asia/Jakarta');
