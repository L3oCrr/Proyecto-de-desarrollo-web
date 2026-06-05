<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\Expense;

/**
 * Exportación de reportes operativos en formato CSV.
 */
final class ReportsController extends BaseController
{
    private Expense $expenseModel;

    public function __construct()
    {
        $this->expenseModel = new Expense();
    }

    public function exportExpenses(): void
    {
        AuthMiddleware::requireAuthentication();

        $filters = $this->resolveFiltersFromQuery();

        if (isset($filters['error'])) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Parámetros de filtrado inválidos.',
                'errors' => $filters['error'],
            ]);
        }

        $rows = $this->expenseModel->getFilteredExpenses($filters);

        $filename = 'reporte_gastos_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        if ($output === false) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible generar el archivo CSV.',
            ]);
        }

        fputcsv($output, [
            'ID Gasto',
            'Fecha Gasto',
            'Nombre Capturista',
            'Nombre Área',
            'Nombre Centro Costos',
            'Código Cuenta',
            'Monto Total',
            'Concepto',
            'Estatus',
            'UUID Factura',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['gasto_id'] ?? '',
                $row['fecha_gasto'] ?? '',
                $row['capturista_nombre'] ?? '',
                $row['area_nombre'] ?? '',
                $row['centro_costos_nombre'] ?? '',
                $row['codigo_cuenta'] ?? '',
                $row['monto_total'] ?? '',
                $row['concepto_descripcion'] ?? '',
                $row['estatus_nombre'] ?? '',
                $row['factura_uuid'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * @return array<string, mixed>|array{error: array<string, list<string>>}
     */
    private function resolveFiltersFromQuery(): array
    {
        $filters = [];
        $errors = [];

        $fechaInicio = $this->parseDateFilter($_GET['fecha_inicio'] ?? null, 'fecha_inicio', $errors);
        $fechaFin = $this->parseDateFilter($_GET['fecha_fin'] ?? null, 'fecha_fin', $errors);

        if ($fechaInicio !== null) {
            $filters['fecha_inicio'] = $fechaInicio;
        }

        if ($fechaFin !== null) {
            $filters['fecha_fin'] = $fechaFin;
        }

        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            $errors['fecha_fin'][] = 'La fecha fin no puede ser anterior a la fecha inicio.';
        }

        $centroCostosId = $this->parsePositiveInt($_GET['centro_costos_id'] ?? null);

        if (isset($_GET['centro_costos_id']) && $_GET['centro_costos_id'] !== '' && $centroCostosId === null) {
            $errors['centro_costos_id'][] = 'El centro_costos_id debe ser un entero positivo válido.';
        } elseif ($centroCostosId !== null) {
            $filters['centro_costos_id'] = $centroCostosId;
        }

        $estatusCodigo = $this->parseStatusCodeFilter($_GET['estatus_codigo'] ?? null);

        if (isset($_GET['estatus_codigo']) && trim((string) $_GET['estatus_codigo']) !== '' && $estatusCodigo === null) {
            $errors['estatus_codigo'][] = 'El estatus_codigo no es válido.';
        } elseif ($estatusCodigo !== null) {
            $filters['estatus_codigo'] = $estatusCodigo;
        }

        if ($errors !== []) {
            return ['error' => $errors];
        }

        return $filters;
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function parseDateFilter(mixed $value, string $field, array &$errors): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $errors[$field][] = 'Debe ser una fecha con formato YYYY-MM-DD.';

            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($dateErrors['warning_count'] ?? 0) > 0
            || ($dateErrors['error_count'] ?? 0) > 0
            || $date->format('Y-m-d') !== $value
        ) {
            $errors[$field][] = 'Debe ser una fecha con formato YYYY-MM-DD.';

            return null;
        }

        return $date->format('Y-m-d');
    }

    private function parseStatusCodeFilter(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        if ($normalized === '' || !preg_match('/^[A-Z0-9_]+$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
