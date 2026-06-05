<?php

declare(strict_types=1);

use App\Core\Http\JsonResponder;
use App\Core\Router;
use App\Middleware\SecurityMiddleware;

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

    $router->get('/health', static function (): void {
        JsonResponder::send(200, [
            'status' => 'ok',
            'service' => 'empresa-gastos-api',
            'timestamp' => date(DATE_ATOM),
        ]);
    });

    // B-003: expone el token CSRF de la sesión activa (solo lectura).
    $router->get('/api/csrf-token', static function (): void {
        JsonResponder::send(200, [
            'csrf_token' => SecurityMiddleware::getCsrfToken(),
        ]);
    });

    // B-003: endpoint temporal para validar protección CSRF en POST.
    $router->post('/api/csrf-test', static function (): void {
        JsonResponder::send(200, [
            'success' => true,
            'message' => 'Token CSRF válido. Petición mutativa permitida.',
        ]);
    });
}
