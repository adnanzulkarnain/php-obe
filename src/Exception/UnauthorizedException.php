<?php

namespace App\Exception;

/**
 * Unauthorized Exception
 * Thrown when authentication fails or is missing
 */
class UnauthorizedException extends BaseException
{
    protected int $statusCode = 401;

    public function __construct(string $message = 'Unauthorized', array $context = [])
    {
        parent::__construct($message, 401, null, $context);
    }
}
