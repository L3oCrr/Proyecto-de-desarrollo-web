<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\CfdiInvoice;
use App\Models\Expense;
use App\Services\FileService;
use App\Services\FileValidationException;
use PDOException;
use RuntimeException;

/**
 * Carga segura de documentos XML asociados a un gasto en borrador.
 */
final class ExpenseDocumentsController extends BaseController
{
    private Expense $expenseModel;

    private CfdiInvoice $cfdiInvoiceModel;

    private FileService $fileService;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->cfdiInvoiceModel = new CfdiInvoice();
        $this->fileService = new FileService();
    }

    public function upload(string $id): void
    {
        AuthMiddleware::requireAuthentication();

        $expenseId = (int) $id;
        $userId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];

        if ($expenseId <= 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'El identificador del gasto no es válido.',
            ]);
        }

        $expense = $this->expenseModel->findOwnedById($expenseId, $userId);

        if ($expense === null) {
            JsonResponder::send(404, [
                'error' => true,
                'message' => 'El gasto no existe o no pertenece al usuario autenticado.',
            ]);
        }

        if (strcasecmp((string) ($expense['estatus_codigo'] ?? ''), 'BORRADOR') !== 0) {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Solo se pueden adjuntar documentos a gastos en estado Borrador.',
            ]);
        }

        if (!empty($expense['factura_cfdi_id'])) {
            JsonResponder::send(409, [
                'error' => true,
                'message' => 'El gasto ya tiene un documento XML asociado.',
            ]);
        }

        $storedFile = null;

        try {
            $storedFile = $this->fileService->storeXmlUpload();
        } catch (FileValidationException $exception) {
            JsonResponder::send($exception->getStatusCode(), [
                'error' => true,
                'message' => $exception->getMessage(),
            ]);
        } catch (RuntimeException $exception) {
            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible procesar la carga del archivo.',
            ]);
        }

        try {
            $this->expenseModel->beginTransaction();

            $facturaCfdiId = $this->cfdiInvoiceModel->createPendingUpload(
                $storedFile['relative_path']
            );

            $this->expenseModel->attachFacturaCfdi($expenseId, $facturaCfdiId);
            $this->expenseModel->commit();
        } catch (PDOException $exception) {
            $this->expenseModel->rollBack();

            if ($storedFile !== null) {
                $this->fileService->deleteStoredFile($storedFile['absolute_path']);
            }

            if ($exception->getCode() === '23000') {
                JsonResponder::send(409, [
                    'error' => true,
                    'message' => 'No fue posible vincular el documento al gasto.',
                ]);
            }

            throw $exception;
        }

        JsonResponder::send(201, [
            'data' => [
                'gasto_id' => $expenseId,
                'factura_cfdi_id' => $facturaCfdiId,
                'stored_name' => $storedFile['stored_name'],
                'xml_file_path' => $storedFile['relative_path'],
            ],
        ]);
    }
}
