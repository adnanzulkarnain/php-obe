<?php

namespace App\Exception;

use Exception;

/**
 * Base Exception
 * Parent class for all custom exceptions
 */
abstract class BaseException extends Exception
{
    protected int $statusCode = 500;
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => $this->getMessage(),
            'code' => $this->statusCode,
            'context' => $this->context
        ];
    }
}
