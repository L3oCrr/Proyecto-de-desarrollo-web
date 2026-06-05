<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Models\AccountCatalog;
use PDOException;

final class AccountCatalogsController extends BaseController
{
    private AccountCatalog $model;

    public function __construct()
    {
        $this->model = new AccountCatalog();
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
        $numeroCuenta = $this->sanitizeString($input['numero_cuenta'] ?? null, 30);
        $descripcion = $this->sanitizeString($input['descripcion'] ?? null, 150);

        $errors = [];

        if ($numeroCuenta === null) {
            $errors['numero_cuenta'][] = 'El número de cuenta es obligatorio (máximo 30 caracteres).';
        }

        if ($descripcion === null) {
            $errors['descripcion'][] = 'La descripción es obligatoria (máximo 150 caracteres).';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        try {
            $record = $this->model->create([
                'numero_cuenta' => $numeroCuenta,
                'descripcion' => $descripcion,
            ]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        JsonResponder::send(201, [
            'data' => $record,
        ]);
    }
}
