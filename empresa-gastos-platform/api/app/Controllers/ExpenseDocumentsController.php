<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Middleware\AuthMiddleware;
use App\Models\CfdiInvoice;
use App\Models\Expense;
use App\Services\FileService;
use App\Services\FileValidationException;
use App\Services\XmlParseException;
use App\Services\XmlParserService;
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

    private XmlParserService $xmlParserService;

    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->cfdiInvoiceModel = new CfdiInvoice();
        $this->fileService = new FileService();
        $this->xmlParserService = new XmlParserService();
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

        $facturaCfdiId = null;
        $parsedCfdi = null;

        try {
            $this->expenseModel->beginTransaction();

            $facturaCfdiId = $this->cfdiInvoiceModel->createPendingUpload(
                $storedFile['relative_path']
            );

            $this->expenseModel->attachFacturaCfdi($expenseId, $facturaCfdiId);

            $parsedCfdi = $this->xmlParserService->parseCfdiFile($storedFile['absolute_path']);

            $cfdiRecord = $this->cfdiInvoiceModel->updateFromParsedData(
                $facturaCfdiId,
                $parsedCfdi
            );

            $this->expenseModel->commit();
        } catch (XmlParseException $exception) {
            $this->rollbackUpload($storedFile['absolute_path']);

            JsonResponder::send($exception->getStatusCode(), [
                'error' => true,
                'message' => $exception->getMessage(),
            ]);
        } catch (PDOException $exception) {
            $this->rollbackUpload($storedFile['absolute_path']);

            if ($exception->getCode() === '23000') {
                JsonResponder::send(409, [
                    'error' => true,
                    'message' => 'El UUID del CFDI ya está registrado en el sistema.',
                ]);
            }

            throw $exception;
        } catch (RuntimeException $exception) {
            $this->rollbackUpload($storedFile['absolute_path']);

            JsonResponder::send(500, [
                'error' => true,
                'message' => 'No fue posible procesar el CFDI.',
            ]);
        }

        JsonResponder::send(201, [
            'data' => [
                'gasto_id' => $expenseId,
                'factura_cfdi_id' => $facturaCfdiId,
                'stored_name' => $storedFile['stored_name'],
                'xml_file_path' => $storedFile['relative_path'],
                'cfdi' => [
                    'uuid' => $cfdiRecord['uuid'] ?? $parsedCfdi['uuid'],
                    'emisor_rfc' => $cfdiRecord['emisor_rfc'] ?? $parsedCfdi['emisor_rfc'],
                    'emisor_razon_social' => $cfdiRecord['emisor_razon_social'] ?? $parsedCfdi['emisor_razon_social'],
                    'receptor_rfc' => $cfdiRecord['receptor_rfc'] ?? $parsedCfdi['receptor_rfc'],
                    'monto_subtotal' => $cfdiRecord['monto_subtotal'] ?? $parsedCfdi['monto_subtotal'],
                    'monto_iva' => $cfdiRecord['monto_iva'] ?? $parsedCfdi['monto_iva'],
                    'monto_total' => $cfdiRecord['monto_total'] ?? $parsedCfdi['monto_total'],
                    'fecha_emision' => $cfdiRecord['fecha_emision'] ?? $parsedCfdi['fecha_emision'],
                ],
            ],
        ]);
    }

    private function rollbackUpload(string $absolutePath): void
    {
        $this->expenseModel->rollBack();
        $this->fileService->deleteStoredFile($absolutePath);
    }
}
