<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\AccountCatalog;
use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\ExpenseStatus;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Services\BudgetValidationException;
use PDOException;

/**
 * Operaciones del flujo transaccional de gastos.
 */
final class ExpensesController extends BaseController
{
    private Expense $expenseModel;

    private ExpenseStatus $expenseStatusModel;

    private BudgetService $budgetService;

    private AuditService $auditService;

    private CostCenter $costCenterModel;

    private AccountCatalog $accountCatalogModel;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->expenseStatusModel = new ExpenseStatus();
        $this->budgetService = new BudgetService();
        $this->auditService = new AuditService();
        $this->costCenterModel = new CostCenter();
        $this->accountCatalogModel = new AccountCatalog();
    }

    /**
     * Lista los gastos del usuario autenticado.
     */
    public function index(): void
    {
        AuthMiddleware::requireAuthentication();

        $userId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];

        JsonResponder::send(200, [
            'data' => $this->expenseModel->listByUserId($userId),
        ]);
    }

    /**
     * Registra un gasto manual en estado Borrador.
     */
    public function store(): void
    {
        AuthMiddleware::requireAuthentication();

        $input = $this->parseJsonBody();
        $userId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];

        $conceptoDescripcion = $this->sanitizeString($input['concepto_descripcion'] ?? null, 255);
        $montoTotal = $this->parsePositiveDecimal($input['monto_total'] ?? null);
        $fechaGasto = $this->parseDateValue($input['fecha_gasto'] ?? null);
        $centroCostosId = $this->parsePositiveInt($input['centro_costos_id'] ?? null);
        $cuentaContableId = $this->parsePositiveInt($input['cuenta_contable_id'] ?? null);

        $errors = [];

        if ($conceptoDescripcion === null) {
            $errors['concepto_descripcion'][] = 'El concepto es obligatorio (máximo 255 caracteres).';
        }

        if ($montoTotal === null) {
            $errors['monto_total'][] = 'El monto total es obligatorio y debe ser un número mayor a cero.';
        }

        if ($fechaGasto === null) {
            $errors['fecha_gasto'][] = 'La fecha del gasto es obligatoria y debe tener formato YYYY-MM-DD.';
        }

        if ($centroCostosId === null) {
            $errors['centro_costos_id'][] = 'El centro de costos es obligatorio.';
        } elseif (!$this->costCenterModel->existsActive($centroCostosId)) {
            $errors['centro_costos_id'][] = 'El centro de costos no corresponde a un registro activo.';
        }

        if ($cuentaContableId === null) {
            $errors['cuenta_contable_id'][] = 'La cuenta contable es obligatoria.';
        } elseif (!$this->accountCatalogModel->existsActive($cuentaContableId)) {
            $errors['cuenta_contable_id'][] = 'La cuenta contable no corresponde a un registro activo.';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        $borradorId = $this->expenseStatusModel->findIdByCodigo('BORRADOR');

        if ($borradorId === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No está configurado el estatus BORRADOR en el catálogo.',
            ]);
        }

        try {
            $createdExpense = $this->expenseModel->create([
                'usuario_capturista_id' => $userId,
                'centro_costos_id' => $centroCostosId,
                'cuenta_contable_id' => $cuentaContableId,
                'estatus_gasto_id' => $borradorId,
                'monto_total' => $montoTotal,
                'fecha_gasto' => $fechaGasto,
                'concepto_descripcion' => $conceptoDescripcion,
            ]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        $this->auditExpenseCreation($createdExpense);

        JsonResponder::send(201, [
            'data' => $createdExpense,
        ]);
    }

    private function parsePositiveDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        if ($amount <= 0) {
            return null;
        }

        return number_format($amount, 4, '.', '');
    }

    private function parseDateValue(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = trim($value);
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $normalized);

        if ($date === false || $date->format('Y-m-d') !== $normalized) {
            return null;
        }

        return $normalized;
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
