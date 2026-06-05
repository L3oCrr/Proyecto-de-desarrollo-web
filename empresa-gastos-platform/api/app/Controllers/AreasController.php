<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Models\Area;
use PDOException;

final class AreasController extends BaseController
{
    private Area $model;

    public function __construct()
    {
        $this->model = new Area();
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
        $nombre = $this->sanitizeString($input['nombre'] ?? null, 100);

        $errors = [];

        if ($nombre === null) {
            $errors['nombre'][] = 'El nombre es obligatorio (máximo 100 caracteres).';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        try {
            $record = $this->model->create(['nombre' => $nombre]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        JsonResponder::send(201, [
            'data' => $record,
        ]);
    }
}
