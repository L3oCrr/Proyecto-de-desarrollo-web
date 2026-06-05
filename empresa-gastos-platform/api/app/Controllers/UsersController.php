<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Core\Security\HashHelper;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use PDOException;

final class UsersController extends BaseController
{
    private User $model;

    private Role $roleModel;

    private Area $areaModel;

    public function __construct()
    {
        $this->model = new User();
        $this->roleModel = new Role();
        $this->areaModel = new Area();
    }

    public function store(): void
    {
        $input = $this->parseJsonBody();

        $rolId = $this->parsePositiveInt($input['rol_id'] ?? null);
        $areaId = $this->parsePositiveInt($input['area_id'] ?? null);
        $nombre = $this->sanitizeString($input['nombre'] ?? null, 150);
        $email = $this->sanitizeEmail($input['email'] ?? null);
        $password = $this->parsePassword($input['password'] ?? null);

        $errors = [];

        if ($rolId === null) {
            $errors['rol_id'][] = 'El rol_id es obligatorio y debe ser un entero positivo válido.';
        } elseif (!$this->roleModel->existsActive($rolId)) {
            $errors['rol_id'][] = 'El rol_id no corresponde a un rol activo.';
        }

        if ($areaId === null) {
            $errors['area_id'][] = 'El area_id es obligatorio y debe ser un entero positivo válido.';
        } elseif (!$this->areaModel->existsActive($areaId)) {
            $errors['area_id'][] = 'El area_id no corresponde a un área activa.';
        }

        if ($nombre === null) {
            $errors['nombre'][] = 'El nombre es obligatorio (máximo 150 caracteres).';
        }

        if ($email === null) {
            $errors['email'][] = 'El correo electrónico es obligatorio y debe tener un formato válido.';
        } elseif ($this->model->emailExists($email)) {
            $errors['email'][] = 'El correo electrónico ya está registrado.';
        }

        if ($password === null) {
            $errors['password'][] = 'La contraseña es obligatoria (mínimo 8 caracteres).';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        try {
            $record = $this->model->create([
                'rol_id' => $rolId,
                'area_id' => $areaId,
                'nombre' => $nombre,
                'email' => $email,
                'password' => HashHelper::hash($password),
            ]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        JsonResponder::send(201, [
            'data' => $record,
        ]);
    }

    private function sanitizeEmail(mixed $value): ?string
    {
        $email = $this->sanitizeString($value, 100);

        if ($email === null) {
            return null;
        }

        $email = strtolower($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function parsePassword(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $password = $value;

        if (strlen($password) < 8) {
            return null;
        }

        return $password;
    }
}
