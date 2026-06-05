<?php

declare(strict_types=1);

use App\Controllers\AccountCatalogsController;
use App\Controllers\AccountsPayableController;
use App\Controllers\AreasController;
use App\Controllers\AuthController;
use App\Controllers\CostCentersController;
use App\Controllers\ExpenseApprovalsController;
use App\Controllers\ExpenseDocumentsController;
use App\Controllers\ExpensesController;
use App\Controllers\ReportsController;
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
    $expenseDocumentsController = new ExpenseDocumentsController();
    $expensesController = new ExpensesController();
    $expenseApprovalsController = new ExpenseApprovalsController();
    $accountsPayableController = new AccountsPayableController();
    $reportsController = new ReportsController();

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

    // B-007: carga segura de XML CFDI vinculado a un gasto
    $router->post(
        '/api/gastos/{id}/documento',
        AuthMiddleware::guard([$expenseDocumentsController, 'upload'])
    );

    // B-009: envío a aprobación con validación presupuestal
    $router->put(
        '/api/gastos/{id}/enviar',
        AuthMiddleware::guard([$expensesController, 'submitForApproval'])
    );

    // B-010: bandeja y autorización del Jefe de Área
    $router->get(
        '/api/aprobaciones/jefe',
        AuthMiddleware::guard([$expenseApprovalsController, 'index'])
    );
    $router->put(
        '/api/aprobaciones/jefe/{id}/aprobar',
        AuthMiddleware::guard([$expenseApprovalsController, 'approve'])
    );
    $router->put(
        '/api/aprobaciones/jefe/{id}/rechazar',
        AuthMiddleware::guard([$expenseApprovalsController, 'reject'])
    );

    // B-011: bandeja global y cierre por Cuentas por Pagar
    $router->get(
        '/api/cxp/gastos',
        AuthMiddleware::guard([$accountsPayableController, 'index'])
    );
    $router->put(
        '/api/cxp/gastos/{id}/procesar',
        AuthMiddleware::guard([$accountsPayableController, 'process'])
    );

    // B-013: reporte exportable de gastos en CSV
    $router->get(
        '/api/reportes/gastos',
        AuthMiddleware::guard([$reportsController, 'exportExpenses'])
    );
}
