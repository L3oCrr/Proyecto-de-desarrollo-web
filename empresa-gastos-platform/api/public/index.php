<?php

declare(strict_types=1);

/**
 * CORS global para consumo del frontend (credenciales + preflight OPTIONS).
 */
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedMethods = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';
$allowedHeaders = 'Content-Type, Authorization, X-CSRF-TOKEN, Accept';

if ($requestOrigin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i', $requestOrigin) === 1) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: ' . $allowedMethods);
header('Access-Control-Allow-Headers: ' . $allowedHeaders);
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

use App\Middleware\SecurityMiddleware;

/**
 * Front controller: punto de entrada único para la API REST.
 */
require_once dirname(__DIR__) . '/bootstrap/app.php';

$router = bootstrapApplication();

SecurityMiddleware::initializeSecureSession();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = resolveRequestUri();

$router->dispatch($method, $uri);
