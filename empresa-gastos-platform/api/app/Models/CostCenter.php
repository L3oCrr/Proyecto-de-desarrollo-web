<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catálogo de centros de costos vinculados a un área.
 */
final class CostCenter extends Model
{
    protected function table(): string
    {
        return 'centro_costos';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $sql = sprintf(
            'SELECT id, area_id, codigo_contable, nombre, created_at, updated_at
             FROM %s
             WHERE %s
             ORDER BY id ASC',
            $this->table(),
            $this->softDeleteCondition()
        );

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @param array{area_id: int, codigo_contable: string, nombre: string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO centro_costos (area_id, codigo_contable, nombre)
             VALUES (:area_id, :codigo_contable, :nombre)'
        );
        $statement->execute([
            'area_id' => $data['area_id'],
            'codigo_contable' => $data['codigo_contable'],
            'nombre' => $data['nombre'],
        ]);

        $record = $this->findById((int) $this->db->lastInsertId());

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar el centro de costos creado.');
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
