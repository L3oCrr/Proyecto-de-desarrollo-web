<?php

declare(strict_types=1);

namespace App\Services;

use App\Middleware\AuthMiddleware;
use App\Models\AuditLog;
use JsonException;
use RuntimeException;

/**
 * Registro centralizado de eventos de auditoría sobre gastos.
 */
final class AuditService
{
    public const ACTION_CREATE_DRAFT = 'CREAR_BORRADOR';
    public const ACTION_SUBMIT_APPROVAL = 'ENVIAR_APROBACION';
    public const ACTION_APPROVE_MANAGER = 'APROBAR_JEFE';
    public const ACTION_REJECT_MANAGER = 'RECHAZAR_JEFE';
    public const ACTION_PROCESS_CXP = 'PROCESAR_CXP';

    private AuditLog $auditLogModel;

    public function __construct(?AuditLog $auditLogModel = null)
    {
        $this->auditLogModel = $auditLogModel ?? new AuditLog();
    }

    /**
     * @param array<string, mixed>|null $valoresAnteriores
     * @param array<string, mixed> $valoresNuevos
     */
    public function log(
        int $gastoId,
        string $accionRealizada,
        ?array $valoresAnteriores,
        array $valoresNuevos
    ): int {
        $usuarioId = $this->resolveSessionUserId();
        $ipAddress = $this->resolveClientIpAddress();

        try {
            return $this->auditLogModel->create(
                $gastoId,
                $usuarioId,
                $accionRealizada,
                $this->encodeJson($valoresAnteriores),
                $this->encodeJson($valoresNuevos),
                $ipAddress
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'No fue posible serializar los datos de auditoría.',
                0,
                $exception
            );
        }
    }

    /**
     * Normaliza un gasto para almacenarlo en la bitácora sin datos sensibles.
     *
     * @param array<string, mixed>|null $expense
     * @return array<string, mixed>|null
     */
    public function snapshotExpense(?array $expense): ?array
    {
        if ($expense === null) {
            return null;
        }

        $fields = [
            'id',
            'usuario_capturista_id',
            'centro_costos_id',
            'cuenta_contable_id',
            'estatus_gasto_id',
            'estatus_codigo',
            'estatus_nombre',
            'factura_cfdi_id',
            'monto_total',
            'fecha_gasto',
            'concepto_descripcion',
            'comentarios_rechazo',
            'folio_contable_interno',
            'usuario_aprobador_jefe_id',
            'usuario_aprobador_cxp_id',
        ];

        $snapshot = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $expense)) {
                $snapshot[$field] = $expense[$field];
            }
        }

        return $snapshot;
    }

    private function resolveSessionUserId(): int
    {
        $userId = $_SESSION[AuthMiddleware::SESSION_USER_ID] ?? null;

        if (is_int($userId) && $userId > 0) {
            return $userId;
        }

        if (is_string($userId) && ctype_digit($userId) && (int) $userId > 0) {
            return (int) $userId;
        }

        throw new RuntimeException('No hay un usuario autenticado para registrar auditoría.');
    }

    private function resolveClientIpAddress(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!is_string($ip) || $ip === '') {
            return '0.0.0.0';
        }

        if (strlen($ip) > 45) {
            return substr($ip, 0, 45);
        }

        return $ip;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function encodeJson(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
