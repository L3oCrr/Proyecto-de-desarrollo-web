<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Acceso a datos de presupuestos mensuales por centro de costos.
 */
final class Budget extends Model
{
    protected function table(): string
    {
        return 'presupuestos';
    }

    protected function hasSoftDelete(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForPeriod(int $centroCostosId, int $periodoMes, int $periodoAnio): ?array
    {
        $sql = 'SELECT id,
                       centro_costos_id,
                       periodo_mes,
                       periodo_anio,
                       monto_assigned
                FROM presupuestos
                WHERE centro_costos_id = :centro_costos_id
                  AND periodo_mes = :periodo_mes
                  AND periodo_anio = :periodo_anio
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'centro_costos_id' => $centroCostosId,
            'periodo_mes' => $periodoMes,
            'periodo_anio' => $periodoAnio,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
