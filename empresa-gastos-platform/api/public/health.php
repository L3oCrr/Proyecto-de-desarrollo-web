<?php

declare(strict_types=1);

/**
 * Healthcheck directo accesible sin pasar por el enrutador.
 * Útil para monitoreo básico del servidor web.
 */
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

echo json_encode([
    'status' => 'ok',
    'service' => 'empresa-gastos-api',
    'source' => 'health.php',
    'timestamp' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE);
