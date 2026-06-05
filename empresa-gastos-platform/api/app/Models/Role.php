<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catálogo de roles del sistema (RBAC).
 */
final class Role extends Model
{
    protected function table(): string
    {
        return 'roles';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $sql = sprintf(
            'SELECT id, nombre, codigo, created_at, updated_at
             FROM %s
             WHERE %s
             ORDER BY id ASC',
            $this->table(),
            $this->softDeleteCondition()
        );

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @param array{nombre: string, codigo: string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO roles (nombre, codigo) VALUES (:nombre, :codigo)'
        );
        $statement->execute([
            'nombre' => $data['nombre'],
            'codigo' => $data['codigo'],
        ]);

        $record = $this->findById((int) $this->db->lastInsertId());

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar el rol creado.');
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

    public function findCodigoById(int $id): ?string
    {
        $sql = sprintf(
            'SELECT codigo FROM %s WHERE id = :id AND %s LIMIT 1',
            $this->table(),
            $this->softDeleteCondition()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $codigo = $statement->fetchColumn();

        return $codigo === false ? null : (string) $codigo;
    }
}
