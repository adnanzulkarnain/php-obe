<?php

namespace App\Exception;

/**
 * Not Found Exception
 * Thrown when a requested resource is not found
 */
class NotFoundException extends BaseException
{
    protected int $statusCode = 404;

    public function __construct(string $resource = 'Resource', array $context = [])
    {
        $message = "$resource not found";
        parent::__construct($message, 404, null, $context);
    }
}
