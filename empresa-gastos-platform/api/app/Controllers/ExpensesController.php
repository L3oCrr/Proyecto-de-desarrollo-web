<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\Expense;
use App\Models\ExpenseStatus;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Services\BudgetValidationException;

/**
 * Operaciones del flujo transaccional de gastos.
 */
final class ExpensesController extends BaseController
{
    private Expense $expenseModel;

    private ExpenseStatus $expenseStatusModel;

    private BudgetService $budgetService;

    private AuditService $auditService;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->expenseStatusModel = new ExpenseStatus();
        $this->budgetService = new BudgetService();
        $this->auditService = new AuditService();
    }

    /**
     * Registra auditoría al crear un gasto en borrador (invocable desde store/create).
     *
     * @param array<string, mixed> $createdExpense
     */
    public function auditExpenseCreation(array $createdExpense): void
    {
        $expenseId = (int) ($createdExpense['id'] ?? 0);

        if ($expenseId <= 0) {
            return;
        }

        $this->auditService->log(
            $expenseId,
            AuditService::ACTION_CREATE_DRAFT,
            null,
            $this->auditService->snapshotExpense($createdExpense) ?? []
        );
    }

    /**
     * Envía un gasto en borrador a pendiente de aprobación tras validar presupuesto.
     */
    public function submitForApproval(string $id): void
    {
        AuthMiddleware::requireAuthentication();

        $expenseId = (int) $id;
        $userId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];

        if ($expenseId <= 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El identificador del gasto no es válido.',
            ]);
        }

        $expense = $this->expenseModel->findOwnedForSubmission($expenseId, $userId);

        if ($expense === null) {
            JsonResponder::send(404, [
                'error' => true,
                'message' => 'El gasto no existe o no pertenece al usuario autenticado.',
            ]);
        }

        if (strcasecmp((string) ($expense['estatus_codigo'] ?? ''), 'BORRADOR') !== 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Solo los gastos en estado Borrador pueden enviarse a aprobación.',
            ]);
        }

        $previousSnapshot = $this->auditService->snapshotExpense(
            $this->expenseModel->findPublicById($expenseId)
        );

        try {
            $this->budgetService->assertSufficientFundsForExpense($expense);
        } catch (BudgetValidationException $exception) {
            JsonResponder::send($exception->getStatusCode(), [
                'error' => true,
                'message' => $exception->getMessage(),
                'presupuesto' => $this->budgetService->buildBudgetSnapshot($expense),
            ]);
        }

        $pendienteId = $this->expenseStatusModel->findIdByCodigo('PENDIENTE');

        if ($pendienteId === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No está configurado el estatus PENDIENTE en el catálogo.',
            ]);
        }

        $this->expenseModel->updateStatus($expenseId, $pendienteId);

        $updatedExpense = $this->expenseModel->findPublicById($expenseId);

        if ($updatedExpense === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible recuperar el gasto actualizado.',
            ]);
        }

        $this->auditService->log(
            $expenseId,
            AuditService::ACTION_SUBMIT_APPROVAL,
            $previousSnapshot,
            $this->auditService->snapshotExpense($updatedExpense) ?? []
        );

        JsonResponder::send(200, [
            'data' => $updatedExpense,
            'presupuesto' => $this->budgetService->buildBudgetSnapshot($expense),
        ]);
    }
}
