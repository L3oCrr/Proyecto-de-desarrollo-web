<?php

declare(strict_types=1);

use App\Controllers\AccountCatalogsController;
use App\Controllers\AreasController;
use App\Controllers\AuthController;
use App\Controllers\CostCentersController;
use App\Controllers\RolesController;
use App\Controllers\UsersController;
use App\Core\Http\JsonResponder;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
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
    $usersController = new UsersController();
    $authController = new AuthController();

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

    // B-005: autenticación y usuarios (registro público para bootstrap inicial)
    $router->post('/api/users', [$usersController, 'store']);
    $router->post('/api/auth/login', [$authController, 'login']);
    $router->post('/api/auth/logout', [$authController, 'logout']);
    $router->get('/api/auth/me', [$authController, 'me']);

    // B-004: catálogos base (requieren sesión autenticada)
    $router->get('/api/roles', AuthMiddleware::guard([$rolesController, 'index']));
    $router->post('/api/roles', AuthMiddleware::guard([$rolesController, 'store']));

    $router->get('/api/areas', AuthMiddleware::guard([$areasController, 'index']));
    $router->post('/api/areas', AuthMiddleware::guard([$areasController, 'store']));

    $router->get('/api/centros-costo', AuthMiddleware::guard([$costCentersController, 'index']));
    $router->post('/api/centros-costo', AuthMiddleware::guard([$costCentersController, 'store']));

    $router->get('/api/cuentas', AuthMiddleware::guard([$accountCatalogsController, 'index']));
    $router->post('/api/cuentas', AuthMiddleware::guard([$accountCatalogsController, 'store']));
}
