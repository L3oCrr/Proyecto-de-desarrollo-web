<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use PDOException;

/**
 * Utilidades compartidas para controladores de la API.
 */
abstract class BaseController
{
    /**
     * @return array<string, mixed>
     */
    protected function parseJsonBody(): array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        // Elimina BOM UTF-8 si el cliente lo envía (común en editores Windows).
        $rawBody = preg_replace('/^\xEF\xBB\xBF/', '', $rawBody) ?? $rawBody;

        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El cuerpo de la petición debe ser un JSON válido.',
            ]);
        }

        return $decoded;
    }

    protected function sanitizeString(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($normalized, 0, $maxLength);
        }

        return substr($normalized, 0, $maxLength);
    }

    protected function parsePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $parsed = (int) $value;

            return $parsed > 0 ? $parsed : null;
        }

        return null;
    }

    /**
     * @param array<string, list<string>> $errors
     */
    protected function validationError(array $errors): never
    {
        JsonResponder::send(422, [
            'error' => true,
            'message' => 'Error de validación en los datos enviados.',
            'errors' => $errors,
        ]);
    }

    protected function handlePersistenceException(PDOException $exception): never
    {
        if ($exception->getCode() === '23000') {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Conflicto de datos: el registro ya existe o la referencia no es válida.',
            ]);
        }

        throw $exception;
    }
}
