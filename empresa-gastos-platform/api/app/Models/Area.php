<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catálogo de áreas funcionales de la organización.
 */
final class Area extends Model
{
    protected function table(): string
    {
        return 'areas';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $sql = sprintf(
            'SELECT id, nombre, created_at, updated_at
             FROM %s
             WHERE %s
             ORDER BY id ASC',
            $this->table(),
            $this->softDeleteCondition()
        );

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @param array{nombre: string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO areas (nombre) VALUES (:nombre)'
        );
        $statement->execute(['nombre' => $data['nombre']]);

        $record = $this->findById((int) $this->db->lastInsertId());

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar el área creada.');
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
