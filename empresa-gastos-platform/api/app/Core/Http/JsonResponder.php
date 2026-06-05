<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Emisor centralizado de respuestas JSON para toda la API.
 */
final class JsonResponder
{
    public static function send(int $statusCode, array $payload): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        exit;
    }
}
