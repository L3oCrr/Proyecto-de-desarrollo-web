<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Extracción segura de datos fiscales de CFDI 3.3 y 4.0.
 */
final class XmlParserService
{
    /**
     * @return array{
     *     uuid: string,
     *     emisor_rfc: string,
     *     emisor_razon_social: string,
     *     receptor_rfc: string,
     *     monto_subtotal: string,
     *     monto_iva: string,
     *     monto_total: string,
     *     fecha_emision: string
     * }
     */
    public function parseCfdiFile(string $absolutePath): array
    {
        if (!is_readable($absolutePath)) {
            throw new XmlParseException('No fue posible leer el archivo XML almacenado.');
        }

        $document = $this->loadSecureXmlDocument($absolutePath);
        $xpath = new DOMXPath($document);

        $comprobante = $xpath->query("/*[local-name()='Comprobante']")->item(0);

        if (!$comprobante instanceof DOMElement) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        $emisor = $this->findFirstChildElement($xpath, $comprobante, 'Emisor');
        $receptor = $this->findFirstChildElement($xpath, $comprobante, 'Receptor');
        $timbre = $this->findFirstChildElement($xpath, $comprobante, 'TimbreFiscalDigital');
        $impuestos = $this->findFirstChildElement($xpath, $comprobante, 'Impuestos');

        $uuid = $this->readRequiredAttribute($timbre, 'UUID');
        $emisorRfc = $this->readRequiredAttribute($emisor, 'Rfc');
        $receptorRfc = $this->readRequiredAttribute($receptor, 'Rfc');
        $subtotal = $this->readRequiredAttribute($comprobante, 'SubTotal');
        $total = $this->readRequiredAttribute($comprobante, 'Total');
        $fechaEmision = $this->normalizeFechaEmision(
            $this->readRequiredAttribute($comprobante, 'Fecha')
        );

        $emisorRazonSocial = $emisor instanceof DOMElement
            ? trim($emisor->getAttribute('Nombre'))
            : '';

        if ($emisorRazonSocial === '') {
            $emisorRazonSocial = $emisorRfc;
        }

        $ivaTrasladado = '0';

        if ($impuestos instanceof DOMElement) {
            $ivaAttribute = trim($impuestos->getAttribute('TotalImpuestosTrasladados'));

            if ($ivaAttribute !== '') {
                $ivaTrasladado = $ivaAttribute;
            }
        }

        if (!$this->isValidUuid($uuid)) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        return [
            'uuid' => strtoupper($uuid),
            'emisor_rfc' => strtoupper($emisorRfc),
            'emisor_razon_social' => $this->truncate($emisorRazonSocial, 250),
            'receptor_rfc' => strtoupper($receptorRfc),
            'monto_subtotal' => $this->normalizeDecimal($subtotal),
            'monto_iva' => $this->normalizeDecimal($ivaTrasladado),
            'monto_total' => $this->normalizeDecimal($total),
            'fecha_emision' => $fechaEmision,
        ];
    }

    private function loadSecureXmlDocument(string $absolutePath): DOMDocument
    {
        $previousLoaderState = null;

        if (function_exists('libxml_disable_entity_loader')) {
            $previousLoaderState = libxml_disable_entity_loader(true);
        }

        if (function_exists('libxml_set_external_entity_loader')) {
            libxml_set_external_entity_loader(static function (): ?object {
                return null;
            });
        }

        $previousInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument();
        $loaded = $document->load($absolutePath, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        if (function_exists('libxml_disable_entity_loader') && $previousLoaderState !== null) {
            libxml_disable_entity_loader($previousLoaderState);
        }

        if ($loaded !== true) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        return $document;
    }

    private function findFirstChildElement(
        DOMXPath $xpath,
        DOMElement $context,
        string $localName
    ): ?DOMElement {
        $node = $xpath->query(
            sprintf('.//*[local-name()="%s"]', $localName),
            $context
        )->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function readRequiredAttribute(?DOMElement $element, string $attributeName): string
    {
        if (!$element instanceof DOMElement) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        $value = trim($element->getAttribute($attributeName));

        if ($value === '') {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        return $value;
    }

    private function normalizeFechaEmision(string $fecha): string
    {
        $fecha = trim($fecha);

        if (str_contains($fecha, 'T')) {
            $fecha = explode('T', $fecha)[0];
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        if ($date === false) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        return $date->format('Y-m-d');
    }

    private function normalizeDecimal(string $value): string
    {
        if (!is_numeric($value)) {
            throw new XmlParseException('El archivo no es un CFDI válido.');
        }

        return number_format((float) $value, 4, '.', '');
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }
}
