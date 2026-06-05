<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catálogo de estatus del ciclo de vida del gasto.
 */
final class ExpenseStatus extends Model
{
    protected function table(): string
    {
        return 'estatus_gastos';
    }

    protected function hasSoftDelete(): bool
    {
        return false;
    }

    public function findIdByCodigo(string $codigo): ?int
    {
        $statement = $this->db->prepare(
            'SELECT id FROM estatus_gastos WHERE codigo = :codigo LIMIT 1'
        );
        $statement->execute(['codigo' => strtoupper($codigo)]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * @return list<int>
     */
    public function findIdsByCodigos(array $codigos): array
    {
        if ($codigos === []) {
            return [];
        }

        $normalized = array_map(static fn (string $codigo): string => strtoupper($codigo), $codigos);
        $placeholders = implode(', ', array_fill(0, count($normalized), '?'));

        $statement = $this->db->prepare(
            "SELECT id FROM estatus_gastos WHERE codigo IN ({$placeholders})"
        );
        $statement->execute($normalized);

        return array_map(static fn ($id): int => (int) $id, $statement->fetchAll(\PDO::FETCH_COLUMN));
    }
}
