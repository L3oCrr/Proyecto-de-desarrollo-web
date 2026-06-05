<?php

declare(strict_types=1);

use App\Controllers\AccountCatalogsController;
use App\Controllers\AreasController;
use App\Controllers\CostCentersController;
use App\Controllers\RolesController;
use App\Core\Http\JsonResponder;
use App\Core\Router;
use App\Middleware\SecurityMiddleware;

/**
 * Registra las rutas HTTP disponibles en esta fase del proyecto.
 */
function registerRoutes(Router $router): void
{
    $rolesController = new RolesController();
    $areasController = new AreasController();
    $costCentersController = new CostCentersController();
    $accountCatalogsController = new AccountCatalogsController();

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

    $router->get('/api/csrf-token', static function (): void {
        JsonResponder::send(200, [
            'csrf_token' => SecurityMiddleware::getCsrfToken(),
        ]);
    });

    $router->post('/api/csrf-test', static function (): void {
        JsonResponder::send(200, [
            'success' => true,
            'message' => 'Token CSRF válido. Petición mutativa permitida.',
        ]);
    });

    // B-004: catálogos base
    $router->get('/api/roles', [$rolesController, 'index']);
    $router->post('/api/roles', [$rolesController, 'store']);

    $router->get('/api/areas', [$areasController, 'index']);
    $router->post('/api/areas', [$areasController, 'store']);

    $router->get('/api/centros-costo', [$costCentersController, 'index']);
    $router->post('/api/centros-costo', [$costCentersController, 'store']);

    $router->get('/api/cuentas', [$accountCatalogsController, 'index']);
    $router->post('/api/cuentas', [$accountCatalogsController, 'store']);
}
