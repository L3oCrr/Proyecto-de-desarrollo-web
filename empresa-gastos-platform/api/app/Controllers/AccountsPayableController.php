<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\AccountCatalog;
use App\Models\Expense;
use App\Models\Role;
use PDOException;

/**
 * Bandeja global y cierre contable de Cuentas por Pagar.
 */
final class AccountsPayableController extends BaseController
{
    private const ACCOUNTS_PAYABLE_ROLE_CODE = 'CXP';

    private Expense $expenseModel;

    private AccountCatalog $accountCatalogModel;

    private Role $roleModel;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->accountCatalogModel = new AccountCatalog();
        $this->roleModel = new Role();
    }

    public function index(): void
    {
        $this->assertAccountsPayableRole();

        JsonResponder::send(200, [
            'data' => $this->expenseModel->listApprovedForAccountsPayable(),
        ]);
    }

    public function process(string $id): void
    {
        $this->assertAccountsPayableRole();

        $expenseId = (int) $id;
        $cxpUserId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];

        if ($expenseId <= 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El identificador del gasto no es válido.',
            ]);
        }

        $input = $this->parseJsonBody();
        $cuentaContableId = $this->parsePositiveInt($input['cuenta_contable_id'] ?? null);
        $folioContableInterno = $this->sanitizeString($input['folio_contable_interno'] ?? null, 50);

        $errors = [];

        if ($cuentaContableId === null) {
            $errors['cuenta_contable_id'][] = 'El cuenta_contable_id es obligatorio y debe ser un entero positivo válido.';
        } elseif (!$this->accountCatalogModel->existsActive($cuentaContableId)) {
            $errors['cuenta_contable_id'][] = 'La cuenta contable no existe o no está activa.';
        }

        if ($folioContableInterno === null) {
            $errors['folio_contable_interno'][] = 'El folio_contable_interno es obligatorio (máximo 50 caracteres).';
        }

        if ($errors !== []) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Error de validación en los datos enviados.',
                'errors' => $errors,
            ]);
        }

        $expense = $this->expenseModel->findApprovedForAccountsPayableById($expenseId);

        if ($expense === null) {
            JsonResponder::send(404, [
                'error' => true,
                'message' => 'El gasto no existe o no está aprobado por un Jefe de Área.',
            ]);
        }

        if (!empty($expense['folio_contable_interno'])) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El gasto ya fue procesado por Cuentas por Pagar.',
            ]);
        }

        try {
            $this->expenseModel->processByAccountsPayable(
                $expenseId,
                $cuentaContableId,
                $folioContableInterno,
                $cxpUserId
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                JsonResponder::send(422, [
                    'error' => true,
                    'message' => 'Conflicto de datos al procesar el gasto.',
                ]);
            }

            throw $exception;
        }

        $updatedExpense = $this->expenseModel->findPublicById($expenseId);

        if ($updatedExpense === null) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible recuperar el gasto procesado.',
            ]);
        }

        JsonResponder::send(200, [
            'data' => $updatedExpense,
            'message' => 'Gasto procesado correctamente por Cuentas por Pagar.',
        ]);
    }

    private function assertAccountsPayableRole(): void
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

        if ($roleCode === null || strtoupper($roleCode) !== self::ACCOUNTS_PAYABLE_ROLE_CODE) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'Acceso denegado. Se requiere rol de Cuentas por Pagar.',
            ]);
        }
    }
}
