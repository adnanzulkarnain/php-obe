<?php

namespace App\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

/**
 * Application Logger
 * Provides structured logging using Monolog
 */
class Logger
{
    private static ?MonologLogger $logger = null;
    private static ?string $logPath = null;
    private static ?string $logLevel = null;

    /**
     * Initialize logger
     */
    private static function init(): void
    {
        if (self::$logger !== null) {
            return;
        }

        self::$logPath = getenv('LOG_PATH') ?: 'logs/app.log';
        self::$logLevel = strtoupper(getenv('LOG_LEVEL') ?: 'DEBUG');

        // Create logger instance
        self::$logger = new MonologLogger('OBE-System');

        // Ensure log directory exists
        $logDir = dirname(self::$logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Add rotating file handler
        $handler = new RotatingFileHandler(
            self::$logPath,
            30, // Keep 30 days of logs
            Level::fromName(self::$logLevel)
        );

        // Custom formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s",
            true,
            true
        );

        $handler->setFormatter($formatter);
        self::$logger->pushHandler($handler);

        // Add console handler for development
        if (getenv('APP_ENV') === 'development') {
            $consoleHandler = new StreamHandler('php://stdout', Level::Debug);
            $consoleHandler->setFormatter($formatter);
            self::$logger->pushHandler($consoleHandler);
        }
    }

    /**
     * Get logger instance
     */
    public static function getInstance(): MonologLogger
    {
        if (self::$logger === null) {
            self::init();
        }

        return self::$logger;
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    /**
     * Log notice message
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getInstance()->notice($message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
    }

    /**
     * Log alert message
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getInstance()->alert($message, $context);
    }

    /**
     * Log emergency message
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getInstance()->emergency($message, $context);
    }

    /**
     * Log HTTP request
     */
    public static function logRequest(string $method, string $uri, array $data = []): void
    {
        self::info("HTTP Request: $method $uri", [
            'method' => $method,
            'uri' => $uri,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    /**
     * Log HTTP response
     */
    public static function logResponse(int $statusCode, $data = null): void
    {
        self::info("HTTP Response: $statusCode", [
            'status_code' => $statusCode,
            'data' => $data
        ]);
    }

    /**
     * Log database query
     */
    public static function logQuery(string $sql, array $params = [], float $executionTime = 0): void
    {
        self::debug("Database Query", [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime . 'ms'
        ]);
    }

    /**
     * Log authentication attempt
     */
    public static function logAuthAttempt(string $username, bool $success): void
    {
        $message = $success ? "Authentication successful: $username" : "Authentication failed: $username";
        $level = $success ? 'info' : 'warning';

        self::$level($message, [
            'username' => $username,
            'success' => $success,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Log exception
     */
    public static function logException(\Throwable $e): void
    {
        self::error("Exception: " . $e->getMessage(), [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        self::warning("Security Event: $event", array_merge($context, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    /**
     * Log performance metric
     */
    public static function logPerformance(string $operation, float $duration, array $context = []): void
    {
        self::info("Performance: $operation", array_merge($context, [
            'operation' => $operation,
            'duration_ms' => $duration,
            'duration_formatted' => number_format($duration, 2) . 'ms'
        ]));
    }
}
