<?php

namespace App\Exception;

/**
 * Database Exception
 * Thrown when database operations fail
 */
class DatabaseException extends BaseException
{
    protected int $statusCode = 500;

    public function __construct(string $message = 'Database error occurred', array $context = [])
    {
        parent::__construct($message, 500, null, $context);
    }
}
