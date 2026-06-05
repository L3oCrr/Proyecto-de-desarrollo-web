<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\Expense;
use App\Models\ExpenseStatus;
use App\Models\Role;
use App\Services\AuditService;

/**
 * Bandeja y decisiones de aprobación del Jefe de Área.
 */
final class ExpenseApprovalsController extends BaseController
{
    private const AREA_MANAGER_ROLE_CODE = 'JEF';

    private Expense $expenseModel;

    private ExpenseStatus $expenseStatusModel;

    private Role $roleModel;

    private AuditService $auditService;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->expenseStatusModel = new ExpenseStatus();
        $this->roleModel = new Role();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $this->assertAreaManagerRole();

        $areaId = (int) $_SESSION[AuthMiddleware::SESSION_AREA_ID];

        JsonResponder::send(200, [
            'data' => $this->expenseModel->listPendingByAreaId($areaId),
        ]);
    }

    public function approve(string $id): void
    {
        $this->assertAreaManagerRole();

        $expenseId = (int) $id;
        $managerUserId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];
        $areaId = (int) $_SESSION[AuthMiddleware::SESSION_AREA_ID];

        if ($expenseId <= 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El identificador del gasto no es válido.',
            ]);
        }

        $expense = $this->expenseModel->findPendingByIdForArea($expenseId, $areaId);

        if ($expense === null) {
            JsonResponder::send(404, [
                'error' => true,
                'message' => 'El gasto no existe, no está pendiente o no pertenece a su área.',
            ]);
        }

        if ((int) $expense['usuario_capturista_id'] === $managerUserId) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'No puede aprobar un gasto capturado por usted mismo.',
            ]);
        }

        $aprobadoId = $this->expenseStatusModel->findIdByCodigo('APROBADO');

        if ($aprobadoId === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No está configurado el estatus APROBADO en el catálogo.',
            ]);
        }

        $previousSnapshot = $this->auditService->snapshotExpense(
            $this->expenseModel->findPublicById($expenseId)
        );

        $this->expenseModel->approveByAreaManager($expenseId, $aprobadoId, $managerUserId);

        $updatedExpense = $this->expenseModel->findPublicById($expenseId);

        if ($updatedExpense === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible recuperar el gasto actualizado.',
            ]);
        }

        $this->auditService->log(
            $expenseId,
            AuditService::ACTION_APPROVE_MANAGER,
            $previousSnapshot,
            $this->auditService->snapshotExpense($updatedExpense) ?? []
        );

        JsonResponder::send(200, [
            'data' => $updatedExpense,
            'message' => 'Gasto aprobado correctamente.',
        ]);
    }

    public function reject(string $id): void
    {
        $this->assertAreaManagerRole();

        $expenseId = (int) $id;
        $managerUserId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];
        $areaId = (int) $_SESSION[AuthMiddleware::SESSION_AREA_ID];

        if ($expenseId <= 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El identificador del gasto no es válido.',
            ]);
        }

        $input = $this->parseJsonBody();
        $comentariosRechazo = $this->sanitizeString($input['comentarios_rechazo'] ?? null, 500);

        if ($comentariosRechazo === null) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Error de validación en los datos enviados.',
                'errors' => [
                    'comentarios_rechazo' => [
                        'El comentario de rechazo es obligatorio (máximo 500 caracteres).',
                    ],
                ],
            ]);
        }

        $expense = $this->expenseModel->findPendingByIdForArea($expenseId, $areaId);

        if ($expense === null) {
            JsonResponder::send(404, [
                'error' => true,
                'message' => 'El gasto no existe, no está pendiente o no pertenece a su área.',
            ]);
        }

        if ((int) $expense['usuario_capturista_id'] === $managerUserId) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'No puede rechazar un gasto capturado por usted mismo.',
            ]);
        }

        $rechazadoId = $this->expenseStatusModel->findIdByCodigo('RECHAZADO');

        if ($rechazadoId === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No está configurado el estatus RECHAZADO en el catálogo.',
            ]);
        }

        $previousSnapshot = $this->auditService->snapshotExpense(
            $this->expenseModel->findPublicById($expenseId)
        );

        $this->expenseModel->rejectByAreaManager($expenseId, $rechazadoId, $comentariosRechazo);

        $updatedExpense = $this->expenseModel->findPublicById($expenseId);

        if ($updatedExpense === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible recuperar el gasto actualizado.',
            ]);
        }

        $this->auditService->log(
            $expenseId,
            AuditService::ACTION_REJECT_MANAGER,
            $previousSnapshot,
            $this->auditService->snapshotExpense($updatedExpense) ?? []
        );

        JsonResponder::send(200, [
            'data' => $updatedExpense,
            'message' => 'Gasto rechazado correctamente.',
        ]);
    }

    private function assertAreaManagerRole(): void
    {
        AuthMiddleware::requireAuthentication();

        $rolId = $_SESSION[AuthMiddleware::SESSION_ROL_ID] ?? null;

        if (!is_int($rolId) && !(is_string($rolId) && ctype_digit($rolId))) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'Acceso denegado.',
            ]);
        }

        if (is_string($rolId)) {
            $rolId = (int) $rolId;
            $_SESSION[AuthMiddleware::SESSION_ROL_ID] = $rolId;
        }

        $roleCode = $this->roleModel->findCodigoById($rolId);

        if ($roleCode === null || strtoupper($roleCode) !== self::AREA_MANAGER_ROLE_CODE) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'Acceso denegado. Se requiere rol de Jefe de Área.',
            ]);
        }
    }
}
