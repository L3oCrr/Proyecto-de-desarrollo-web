<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Gestión de usuarios del sistema (autenticación y registro).
 */
final class User extends Model
{
    private const PUBLIC_COLUMNS = 'id, rol_id, area_id, nombre, email, created_at, updated_at';

    protected function table(): string
    {
        return 'usuarios';
    }

    /**
     * Busca un usuario activo por correo incluyendo el hash (solo uso interno de auth).
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = sprintf(
            'SELECT id, rol_id, area_id, nombre, email, password, created_at, updated_at
             FROM %s
             WHERE email = :email AND %s
             LIMIT 1',
            $this->table(),
            $this->softDeleteCondition()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicById(int $id): ?array
    {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE id = :id AND %s LIMIT 1',
            self::PUBLIC_COLUMNS,
            $this->table(),
            $this->softDeleteCondition()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array{
     *     rol_id: int,
     *     area_id: int,
     *     nombre: string,
     *     email: string,
     *     password: string
     * } $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO usuarios (rol_id, area_id, nombre, email, password)
             VALUES (:rol_id, :area_id, :nombre, :email, :password)'
        );
        $statement->execute([
            'rol_id' => $data['rol_id'],
            'area_id' => $data['area_id'],
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $record = $this->findPublicById((int) $this->db->lastInsertId());

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar el usuario creado.');
        }

        return $record;
    }
}
