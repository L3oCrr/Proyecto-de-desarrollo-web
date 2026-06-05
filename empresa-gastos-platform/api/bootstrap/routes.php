<?php

declare(strict_types=1);

use App\Core\Http\JsonResponder;
use App\Core\Router;

/**
 * Registra las rutas HTTP disponibles en esta fase del proyecto.
 */
function registerRoutes(Router $router): void
{
    $router->get('/api/health', static function (): void {
        JsonResponder::send(200, [
            'status' => 'ok',
            'service' => 'empresa-gastos-api',
            'timestamp' => date(DATE_ATOM),
        ]);
    });

    // Alias corto para healthcheck desde el front controller.
    $router->get('/health', static function (): void {
        JsonResponder::send(200, [
            'status' => 'ok',
            'service' => 'empresa-gastos-api',
            'timestamp' => date(DATE_ATOM),
        ]);
    });
}
