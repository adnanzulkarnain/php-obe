<?php

namespace App\Exception;

/**
 * Bad Request Exception
 * Thrown when request is malformed or invalid
 */
class BadRequestException extends BaseException
{
    protected int $statusCode = 400;

    public function __construct(string $message = 'Bad request', array $context = [])
    {
        parent::__construct($message, 400, null, $context);
    }
}
