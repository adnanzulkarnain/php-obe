<?php

namespace App\Exception;

/**
 * Validation Exception
 * Thrown when validation fails
 */
class ValidationException extends BaseException
{
    protected int $statusCode = 422;
    protected array $errors = [];

    public function __construct(string $message = 'Validation failed', array $errors = [], array $context = [])
    {
        $this->errors = $errors;
        parent::__construct($message, 422, null, $context);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => $this->getMessage(),
            'code' => $this->statusCode,
            'errors' => $this->errors,
            'context' => $this->context
        ];
    }
}
