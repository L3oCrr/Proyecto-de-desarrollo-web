<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseStatus;
use DateTimeImmutable;

/**
 * Evaluación presupuestal en tiempo real por centro de costos y periodo.
 */
final class BudgetService
{
    private Budget $budgetModel;

    private Expense $expenseModel;

    private ExpenseStatus $expenseStatusModel;

    public function __construct(
        ?Budget $budgetModel = null,
        ?Expense $expenseModel = null,
        ?ExpenseStatus $expenseStatusModel = null
    ) {
        $this->budgetModel = $budgetModel ?? new Budget();
        $this->expenseModel = $expenseModel ?? new Expense();
        $this->expenseStatusModel = $expenseStatusModel ?? new ExpenseStatus();
    }

    /**
     * Valida que el gasto pueda enviarse sin exceder el presupuesto del periodo.
     *
     * @param array{
     *     centro_costos_id: int|string,
     *     fecha_gasto: string,
     *     monto_total: string|float|int,
     *     id?: int|string
     * } $expense
     */
    public function assertSufficientFundsForExpense(array $expense): void
    {
        $snapshot = $this->buildBudgetSnapshot($expense);

        if ($snapshot['assigned'] === null) {
            throw new BudgetValidationException(
                'No hay presupuesto asignado para este centro de costos en el periodo actual.'
            );
        }

        $proposedTotal = (float) $expense['monto_total'];
        $available = (float) $snapshot['available'];

        if (($snapshot['consumed'] + $proposedTotal) > (float) $snapshot['assigned']) {
            throw new BudgetValidationException(
                sprintf(
                    'Presupuesto insuficiente. Fondos disponibles: $%s',
                    $this->formatMoney($available)
                )
            );
        }
    }

    /**
     * Resumen presupuestal reutilizable en reportes y validaciones.
     *
     * @param array{
     *     centro_costos_id: int|string,
     *     fecha_gasto: string,
     *     monto_total?: string|float|int,
     *     id?: int|string
     * } $expense
     * @return array{
     *     centro_costos_id: int,
     *     periodo_mes: int,
     *     periodo_anio: int,
     *     assigned: string|null,
     *     consumed: string,
     *     available: string
     * }
     */
    public function buildBudgetSnapshot(array $expense): array
    {
        $centroCostosId = (int) $expense['centro_costos_id'];
        $expenseDate = new DateTimeImmutable((string) $expense['fecha_gasto']);
        $periodoMes = (int) $expenseDate->format('n');
        $periodoAnio = (int) $expenseDate->format('Y');
        $excludeExpenseId = isset($expense['id']) ? (int) $expense['id'] : null;

        $budget = $this->budgetModel->findForPeriod($centroCostosId, $periodoMes, $periodoAnio);
        $assigned = $budget === null ? null : $this->normalizeDecimal((string) $budget['monto_assigned']);

        $consumed = $this->expenseModel->sumCommittedAmountForPeriod(
            $centroCostosId,
            $periodoMes,
            $periodoAnio,
            $excludeExpenseId
        );

        $available = $assigned === null
            ? '0.0000'
            : $this->normalizeDecimal((string) max(0, (float) $assigned - (float) $consumed));

        return [
            'centro_costos_id' => $centroCostosId,
            'periodo_mes' => $periodoMes,
            'periodo_anio' => $periodoAnio,
            'assigned' => $assigned,
            'consumed' => $consumed,
            'available' => $available,
        ];
    }

    private function normalizeDecimal(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}
