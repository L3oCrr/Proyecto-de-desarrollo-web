<?php

declare(strict_types=1);

namespace App\Models;

use App\Infrastructure\Database;
use PDO;

/**
 * Clase base para modelos con acceso PDO compartido.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    abstract protected function table(): string;

    protected function hasSoftDelete(): bool
    {
        return true;
    }

    protected function softDeleteCondition(string $alias = ''): string
    {
        if (!$this->hasSoftDelete()) {
            return '1=1';
        }

        $prefix = $alias !== '' ? $alias . '.' : '';

        return $prefix . 'deleted_at IS NULL';
    }

    protected function findById(int $id, array $columns = ['*']): ?array
    {
        $columnList = implode(', ', $columns);
        $sql = sprintf(
            'SELECT %s FROM %s WHERE id = :id AND %s LIMIT 1',
            $columnList,
            $this->table(),
            $this->softDeleteCondition()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
