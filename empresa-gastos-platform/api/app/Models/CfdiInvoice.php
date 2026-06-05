<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Registro de facturas CFDI vinculadas a gastos.
 */
final class CfdiInvoice extends Model
{
    private const PUBLIC_COLUMNS = 'id, uuid, emisor_rfc, emisor_razon_social, receptor_rfc,
        monto_subtotal, monto_iva, monto_total, fecha_emision, xml_file_path, created_at';

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

    /**
     * Actualiza los campos fiscales extraídos del XML parseado.
     *
     * @param array{
     *     uuid: string,
     *     emisor_rfc: string,
     *     emisor_razon_social: string,
     *     receptor_rfc: string,
     *     monto_subtotal: string,
     *     monto_iva: string,
     *     monto_total: string,
     *     fecha_emision: string
     * } $parsedData
     * @return array<string, mixed>
     */
    public function updateFromParsedData(int $id, array $parsedData): array
    {
        $statement = $this->db->prepare(
            'UPDATE facturas_cfdi
             SET uuid = :uuid,
                 emisor_rfc = :emisor_rfc,
                 emisor_razon_social = :emisor_razon_social,
                 receptor_rfc = :receptor_rfc,
                 monto_subtotal = :monto_subtotal,
                 monto_iva = :monto_iva,
                 monto_total = :monto_total,
                 fecha_emision = :fecha_emision
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'uuid' => $parsedData['uuid'],
            'emisor_rfc' => $parsedData['emisor_rfc'],
            'emisor_razon_social' => $parsedData['emisor_razon_social'],
            'receptor_rfc' => $parsedData['receptor_rfc'],
            'monto_subtotal' => $parsedData['monto_subtotal'],
            'monto_iva' => $parsedData['monto_iva'],
            'monto_total' => $parsedData['monto_total'],
            'fecha_emision' => $parsedData['fecha_emision'],
        ]);

        $record = $this->findPublicById($id);

        if ($record === null) {
            throw new \RuntimeException('No fue posible recuperar la factura CFDI actualizada.');
        }

        return $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicById(int $id): ?array
    {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE id = :id LIMIT 1',
            self::PUBLIC_COLUMNS,
            $this->table()
        );

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    private function generatePendingUuid(): string
    {
        return sprintf(
            '00000000-0000-4000-8000-%012s',
            bin2hex(random_bytes(6))
        );
    }
}
