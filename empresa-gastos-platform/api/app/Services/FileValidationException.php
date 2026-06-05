<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Error de validación en la carga de archivos.
 */
final class FileValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
