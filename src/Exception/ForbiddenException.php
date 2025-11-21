<?php

namespace App\Exception;

/**
 * Forbidden Exception
 * Thrown when user doesn't have permission to access resource
 */
class ForbiddenException extends BaseException
{
    protected int $statusCode = 403;

    public function __construct(string $message = 'Access forbidden', array $context = [])
    {
        parent::__construct($message, 403, null, $context);
    }
}
