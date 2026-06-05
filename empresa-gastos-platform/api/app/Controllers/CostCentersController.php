<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Models\Area;
use App\Models\CostCenter;
use PDOException;

final class CostCentersController extends BaseController
{
    private CostCenter $model;

    private Area $areaModel;

    public function __construct()
    {
        $this->model = new CostCenter();
        $this->areaModel = new Area();
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

        $areaId = $this->parsePositiveInt($input['area_id'] ?? null);
        $codigoContable = $this->sanitizeString($input['codigo_contable'] ?? null, 20);
        $nombre = $this->sanitizeString($input['nombre'] ?? null, 100);

        $errors = [];

        if ($areaId === null) {
            $errors['area_id'][] = 'El area_id es obligatorio y debe ser un entero positivo válido.';
        } elseif (!$this->areaModel->existsActive($areaId)) {
            $errors['area_id'][] = 'El area_id no corresponde a un área activa.';
        }

        if ($codigoContable === null) {
            $errors['codigo_contable'][] = 'El código contable es obligatorio (máximo 20 caracteres).';
        }

        if ($nombre === null) {
            $errors['nombre'][] = 'El nombre es obligatorio (máximo 100 caracteres).';
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        try {
            $record = $this->model->create([
                'area_id' => $areaId,
                'codigo_contable' => $codigoContable,
                'nombre' => $nombre,
            ]);
        } catch (PDOException $exception) {
            $this->handlePersistenceException($exception);
        }

        JsonResponder::send(201, [
            'data' => $record,
        ]);
    }
}
