<?php

declare(strict_types=1);

/**
 * Front controller: punto de entrada único para la API REST.
 */
require_once dirname(__DIR__) . '/bootstrap/app.php';

$router = bootstrapApplication();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = resolveRequestUri();

$router->dispatch($method, $uri);
