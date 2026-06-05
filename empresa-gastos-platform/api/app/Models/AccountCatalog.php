<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catálogo de cuentas contables (Grupo 600).
 */
final class AccountCatalog extends Model
{
    protected function table(): string
    {
        return 'catalogo_cuentas';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $sql = sprintf(
            'SELECT id, numero_cuenta, descripcion, created_at, updated_at
             FROM %s
             WHERE %s
             ORDER BY id ASC',
            $this->table(),
            $this->softDeleteCondition()
        );

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @param array{numero_cuenta: string, descripcion: string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO catalogo_cuentas (numero_cuenta, descripcion)
             VALUES (:numero_cuenta, :descripcion)'
        );
        $statement->execute([
            'numero_cuenta' => $data['numero_cuenta'],
            'descripcion' => $data['descripcion'],
        ]);

        $record = $this->findById((int) $this->db->lastInsertId());

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar la cuenta creada.');
        }

        return $record;
    }

    public function existsActive(int $id): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM %s WHERE id = :id AND %s LIMIT 1',
            $this->table(),
            $this->softDeleteCondition()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
