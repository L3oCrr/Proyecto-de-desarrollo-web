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
                       g.comentarios_rechazo,
                       g.folio_contable_interno,
                       g.usuario_aprobador_jefe_id,
                       g.usuario_aprobador_cxp_id,
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

    /**
     * Bandeja de gastos pendientes del área del jefe (centros de costos asociados).
     *
     * @return list<array<string, mixed>>
     */
    public function listPendingByAreaId(int $areaId): array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.concepto_descripcion,
                       g.created_at,
                       cc.nombre AS centro_costos_nombre,
                       cc.codigo_contable,
                       u.nombre AS capturista_nombre,
                       e.codigo AS estatus_codigo,
                       e.nombre AS estatus_nombre
                FROM gastos g
                INNER JOIN centro_costos cc ON cc.id = g.centro_costos_id
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                INNER JOIN usuarios u ON u.id = g.usuario_capturista_id
                WHERE cc.area_id = :area_id
                  AND cc.deleted_at IS NULL
                  AND u.deleted_at IS NULL
                  AND e.codigo = :estatus_codigo
                ORDER BY g.created_at ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'area_id' => $areaId,
            'estatus_codigo' => 'PENDIENTE',
        ]);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPendingByIdForArea(int $expenseId, int $areaId): ?array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.concepto_descripcion,
                       g.estatus_gasto_id,
                       cc.area_id,
                       e.codigo AS estatus_codigo
                FROM gastos g
                INNER JOIN centro_costos cc ON cc.id = g.centro_costos_id
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                WHERE g.id = :id
                  AND cc.area_id = :area_id
                  AND cc.deleted_at IS NULL
                  AND e.codigo = :estatus_codigo
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $expenseId,
            'area_id' => $areaId,
            'estatus_codigo' => 'PENDIENTE',
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function approveByAreaManager(
        int $expenseId,
        int $estatusAprobadoId,
        int $managerUserId
    ): void {
        $statement = $this->db->prepare(
            'UPDATE gastos
             SET estatus_gasto_id = :estatus_gasto_id,
                 usuario_aprobador_jefe_id = :usuario_aprobador_jefe_id,
                 comentarios_rechazo = NULL
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $expenseId,
            'estatus_gasto_id' => $estatusAprobadoId,
            'usuario_aprobador_jefe_id' => $managerUserId,
        ]);
    }

    /**
     * Gastos aprobados por jefe pendientes de cierre por Cuentas por Pagar (vista global).
     *
     * @return list<array<string, mixed>>
     */
    public function listApprovedForAccountsPayable(): array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.cuenta_contable_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.concepto_descripcion,
                       g.usuario_aprobador_jefe_id,
                       g.folio_contable_interno,
                       g.created_at,
                       cc.nombre AS centro_costos_nombre,
                       cc.codigo_contable,
                       a.id AS area_id,
                       a.nombre AS area_nombre,
                       u.nombre AS capturista_nombre,
                       cat.numero_cuenta,
                       cat.descripcion AS cuenta_descripcion,
                       e.codigo AS estatus_codigo,
                       e.nombre AS estatus_nombre
                FROM gastos g
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                INNER JOIN centro_costos cc ON cc.id = g.centro_costos_id
                INNER JOIN areas a ON a.id = cc.area_id
                INNER JOIN usuarios u ON u.id = g.usuario_capturista_id
                LEFT JOIN catalogo_cuentas cat ON cat.id = g.cuenta_contable_id
                WHERE e.codigo = :estatus_codigo
                  AND g.usuario_aprobador_jefe_id IS NOT NULL
                  AND g.folio_contable_interno IS NULL
                  AND cc.deleted_at IS NULL
                  AND a.deleted_at IS NULL
                  AND u.deleted_at IS NULL
                ORDER BY g.created_at ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['estatus_codigo' => 'APROBADO']);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findApprovedForAccountsPayableById(int $expenseId): ?array
    {
        $sql = 'SELECT g.id,
                       g.usuario_capturista_id,
                       g.centro_costos_id,
                       g.cuenta_contable_id,
                       g.estatus_gasto_id,
                       g.monto_total,
                       g.fecha_gasto,
                       g.concepto_descripcion,
                       g.usuario_aprobador_jefe_id,
                       g.folio_contable_interno,
                       e.codigo AS estatus_codigo
                FROM gastos g
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                WHERE g.id = :id
                  AND e.codigo = :estatus_codigo
                  AND g.usuario_aprobador_jefe_id IS NOT NULL
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $expenseId,
            'estatus_codigo' => 'APROBADO',
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function processByAccountsPayable(
        int $expenseId,
        int $cuentaContableId,
        string $folioContableInterno,
        int $cxpUserId
    ): void {
        $statement = $this->db->prepare(
            'UPDATE gastos
             SET cuenta_contable_id = :cuenta_contable_id,
                 folio_contable_interno = :folio_contable_interno,
                 usuario_aprobador_cxp_id = :usuario_aprobador_cxp_id
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $expenseId,
            'cuenta_contable_id' => $cuentaContableId,
            'folio_contable_interno' => $folioContableInterno,
            'usuario_aprobador_cxp_id' => $cxpUserId,
        ]);
    }

    public function rejectByAreaManager(
        int $expenseId,
        int $estatusRechazadoId,
        string $comentariosRechazo
    ): void {
        $statement = $this->db->prepare(
            'UPDATE gastos
             SET estatus_gasto_id = :estatus_gasto_id,
                 comentarios_rechazo = :comentarios_rechazo,
                 usuario_aprobador_jefe_id = NULL
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $expenseId,
            'estatus_gasto_id' => $estatusRechazadoId,
            'comentarios_rechazo' => $comentariosRechazo,
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

    /**
     * Consulta de gastos para reportes con filtros dinámicos y columnas legibles.
     *
     * @param array{
     *     fecha_inicio?: string,
     *     fecha_fin?: string,
     *     centro_costos_id?: int,
     *     estatus_codigo?: string
     * } $filters
     * @return list<array<string, mixed>>
     */
    public function getFilteredExpenses(array $filters): array
    {
        $sql = 'SELECT g.id AS gasto_id,
                       g.fecha_gasto,
                       u.nombre AS capturista_nombre,
                       a.nombre AS area_nombre,
                       cc.nombre AS centro_costos_nombre,
                       cat.numero_cuenta AS codigo_cuenta,
                       g.monto_total,
                       g.concepto_descripcion,
                       e.nombre AS estatus_nombre,
                       e.codigo AS estatus_codigo,
                       fc.uuid AS factura_uuid
                FROM gastos g
                INNER JOIN usuarios u ON u.id = g.usuario_capturista_id
                INNER JOIN centro_costos cc ON cc.id = g.centro_costos_id
                INNER JOIN areas a ON a.id = cc.area_id
                INNER JOIN catalogo_cuentas cat ON cat.id = g.cuenta_contable_id
                INNER JOIN estatus_gastos e ON e.id = g.estatus_gasto_id
                LEFT JOIN facturas_cfdi fc ON fc.id = g.factura_cfdi_id
                WHERE u.deleted_at IS NULL
                  AND cc.deleted_at IS NULL
                  AND a.deleted_at IS NULL
                  AND cat.deleted_at IS NULL';

        $params = [];

        if (isset($filters['fecha_inicio'])) {
            $sql .= ' AND g.fecha_gasto >= :fecha_inicio';
            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }

        if (isset($filters['fecha_fin'])) {
            $sql .= ' AND g.fecha_gasto <= :fecha_fin';
            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        if (isset($filters['centro_costos_id'])) {
            $sql .= ' AND g.centro_costos_id = :centro_costos_id';
            $params['centro_costos_id'] = $filters['centro_costos_id'];
        }

        if (isset($filters['estatus_codigo'])) {
            $sql .= ' AND e.codigo = :estatus_codigo';
            $params['estatus_codigo'] = $filters['estatus_codigo'];
        }

        $sql .= ' ORDER BY g.fecha_gasto DESC, g.id DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }
}
