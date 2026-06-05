<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Models\Role;
use PDOException;

final class RolesController extends BaseController
{
    private Role $model;

    public function __construct()
    {
        $this->model = new Role();
    }

    public function index(): void
    {
        JsonResponder::send(200, [
            'data' => $this->model->all(),
        ]);
    }

    public function store(): void
    {
        $input = $this->parseJsonBody();
        $nombre = $this->sanitizeString($input['nombre'] ?? null, 50);
        $codigo = $this->sanitizeString($input['codigo'] ?? null, 30);

        $errors = [];

        if ($nombre === null) {
            $errors['nombre'][] = 'El nombre es obligatorio (máximo 50 caracteres).';
        }

        if ($codigo === null) {
            $errors['codigo'][] = 'El código es obligatorio (máximo 30 caracteres).';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        try {
            $record = $this->model->create([
                'nombre' => $nombre,
                'codigo' => strtoupper($codigo),
            ]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        JsonResponder::send(201, [
            'data' => $record,
        ]);
    }
}
