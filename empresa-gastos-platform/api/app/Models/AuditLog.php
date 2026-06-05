<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Bitácora inmutable de auditoría (solo inserciones).
 */
final class AuditLog extends Model
{
    protected function table(): string
    {
        return 'bitacora_auditoria';
    }

    protected function hasSoftDelete(): bool
    {
        return false;
    }

    /**
     * Registra un evento de auditoría. No expone operaciones de actualización ni borrado.
     */
    public function create(
        int $gastoId,
        int $usuarioId,
        string $accionRealizada,
        ?string $valoresAnterioresJson,
        string $valoresNuevosJson,
        string $ipAddress
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO bitacora_auditoria (
                gasto_id,
                usuario_id,
                accion_realizada,
                valores_anteriores_json,
                valores_nuevos_json,
                ip_address
            ) VALUES (
                :gasto_id,
                :usuario_id,
                :accion_realizada,
                :valores_anteriores_json,
                :valores_nuevos_json,
                :ip_address
            )'
        );

        $statement->execute([
            'gasto_id' => $gastoId,
            'usuario_id' => $usuarioId,
            'accion_realizada' => $accionRealizada,
            'valores_anteriores_json' => $valoresAnterioresJson,
            'valores_nuevos_json' => $valoresNuevosJson,
            'ip_address' => $ipAddress,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
