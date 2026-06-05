<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Acceso a datos de la entidad gastos (operaciones mínimas para documentos).
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
