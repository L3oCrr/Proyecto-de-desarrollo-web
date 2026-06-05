<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Registro de facturas CFDI vinculadas a gastos.
 */
final class CfdiInvoice extends Model
{
    protected function table(): string
    {
        return 'facturas_cfdi';
    }

    protected function hasSoftDelete(): bool
    {
        return false;
    }

    /**
     * Crea un registro pendiente de parseo (B-008 completará los datos fiscales).
     */
    public function createPendingUpload(string $relativeXmlPath): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO facturas_cfdi (
                uuid,
                emisor_rfc,
                emisor_razon_social,
                receptor_rfc,
                monto_subtotal,
                monto_iva,
                monto_total,
                fecha_emision,
                xml_file_path
            ) VALUES (
                :uuid,
                :emisor_rfc,
                :emisor_razon_social,
                :receptor_rfc,
                :monto_subtotal,
                :monto_iva,
                :monto_total,
                :fecha_emision,
                :xml_file_path
            )'
        );

        $statement->execute([
            'uuid' => $this->generatePendingUuid(),
            'emisor_rfc' => 'PENDIENTE',
            'emisor_razon_social' => 'PENDIENTE DE PARSEO',
            'receptor_rfc' => 'PENDIENTE',
            'monto_subtotal' => '0.0000',
            'monto_iva' => '0.0000',
            'monto_total' => '0.0000',
            'fecha_emision' => date('Y-m-d'),
            'xml_file_path' => $relativeXmlPath,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function generatePendingUuid(): string
    {
        return sprintf(
            '00000000-0000-4000-8000-%012s',
            bin2hex(random_bytes(6))
        );
    }
}
