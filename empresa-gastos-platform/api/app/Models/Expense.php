<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Acceso a datos de la entidad gastos.
 */
final class Expense extends Model
{
    protected function table(): string
    {
        return 'gastos';
    }

    protected function hasSoftDelete(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedById(int $expenseId, int $userId): ?array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.factura_cfdi_id,
                       g.estatus_gasto_id,
                       e.codigo AS estatus_codigo
                FROM gastos g
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                WHERE g.id = :id
                  AND g.usuario_capturista_id = :user_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $expenseId,
            'user_id' => $userId,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedForSubmission(int $expenseId, int $userId): ?array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.estatus_gasto_id,
                       e.codigo AS estatus_codigo
                FROM gastos g
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                WHERE g.id = :id
                  AND g.usuario_capturista_id = :user_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $expenseId,
            'user_id' => $userId,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicById(int $expenseId): ?array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.cuenta_contable_id,
                       g.estatus_gasto_id,
                       g.factura_cfdi_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.concepto_descripcion,
                       g.created_at,
                       g.updated_at,
                       e.codigo AS estatus_codigo,
                       e.nombre AS estatus_nombre
                FROM gastos g
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                WHERE g.id = :id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $expenseId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Suma consumida del periodo (PENDIENTE + APROBADO) optimizada para idx_gastos_presupuesto.
     */
    public function sumCommittedAmountForPeriod(
        int $centroCostosId,
        int $periodoMes,
        int $periodoAnio,
        ?int $excludeExpenseId = null
    ): string {
        $statusModel = new ExpenseStatus();
        $statusIds = $statusModel->findIdsByCodigos(['PENDIENTE', 'APROBADO']);

        if ($statusIds === []) {
            return '0.0000';
        }

        $fechaInicio = sprintf('%04d-%02d-01', $periodoAnio, $periodoMes);
        $fechaFin = (new \DateTimeImmutable($fechaInicio))
            ->modify('last day of this month')
            ->format('Y-m-d');

        $placeholders = implode(', ', array_fill(0, count($statusIds), '?'));

        $sql = "SELECT COALESCE(SUM(g.monto_total), 0.0000) AS total_consumido
                FROM gastos g
                WHERE g.centro_costos_id = ?
                  AND g.estatus_gasto_id IN ({$placeholders})
                  AND g.fecha_gasto BETWEEN ? AND ?";

        $params = array_merge(
            [$centroCostosId],
            $statusIds,
            [$fechaInicio, $fechaFin]
        );

        if ($excludeExpenseId !== null) {
            $sql .= ' AND g.id <> ?';
            $params[] = $excludeExpenseId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $total = $statement->fetchColumn();

        return number_format((float) $total, 4, '.', '');
    }

    public function attachFacturaCfdi(int $expenseId, int $facturaCfdiId): void
    {
        $statement = $this->db->prepare(
            'UPDATE gastos
             SET factura_cfdi_id = :factura_cfdi_id
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $expenseId,
            'factura_cfdi_id' => $facturaCfdiId,
        ]);
    }

    public function updateStatus(int $expenseId, int $estatusGastoId): void
    {
        $statement = $this->db->prepare(
            'UPDATE gastos
             SET estatus_gasto_id = :estatus_gasto_id
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $expenseId,
            'estatus_gasto_id' => $estatusGastoId,
        ]);
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
