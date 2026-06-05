<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Error de validación al parsear un CFDI.
 */
final class XmlParseException extends FileValidationException
{
    public function __construct(string $message = 'El archivo no es un CFDI válido.')
    {
        parent::__construct($message, 422);
    }
}
