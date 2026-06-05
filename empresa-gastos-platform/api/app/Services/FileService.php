<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Validación y almacenamiento seguro de archivos XML (CFDI).
 */
final class FileService
{
    private const ALLOWED_EXTENSIONS = ['xml'];

    /** @var list<string> */
    private const ALLOWED_MIME_TYPES = [
        'text/xml',
        'application/xml',
    ];

    private const UPLOAD_FIELD_NAMES = ['documento', 'archivo', 'file'];

    private string $xmlStorageDirectory;

    public function __construct(?string $xmlStorageDirectory = null)
    {
        $this->xmlStorageDirectory = $xmlStorageDirectory
            ?? dirname(__DIR__, 3) . '/storage/uploads/xml';
    }

    /**
     * Valida y almacena un XML subido vía multipart/form-data.
     *
     * @return array{stored_name: string, relative_path: string, absolute_path: string}
     */
    public function storeXmlUpload(?array $uploadedFile = null): array
    {
        $file = $uploadedFile ?? $this->resolveUploadedFile();

        $this->assertValidUpload($file);
        $this->assertValidXmlContent($file['tmp_name']);

        $this->ensureStorageDirectoryExists();

        $storedName = bin2hex(random_bytes(16)) . '.xml';
        $absolutePath = $this->xmlStorageDirectory . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('No fue posible guardar el archivo XML en el servidor.');
        }

        return [
            'stored_name' => $storedName,
            'relative_path' => 'storage/uploads/xml/' . $storedName,
            'absolute_path' => $absolutePath,
        ];
    }

    public function deleteStoredFile(string $absolutePath): void
    {
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveUploadedFile(): array
    {
        foreach (self::UPLOAD_FIELD_NAMES as $fieldName) {
            if (isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName])) {
                return $_FILES[$fieldName];
            }
        }

        throw new FileValidationException(
            'Debe enviar un archivo XML en el campo "documento" (form-data).',
            422
        );
    }

    /**
     * @param array<string, mixed> $file
     */
    private function assertValidUpload(array $file): void
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            throw new FileValidationException(
                'No se recibió ningún archivo en la petición.',
                422
            );
        }

        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            throw new FileValidationException(
                'El archivo excede el tamaño máximo permitido por el servidor.',
                413
            );
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new FileValidationException(
                'Ocurrió un error al recibir el archivo subido.',
                422
            );
        }

        if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new FileValidationException(
                'El archivo recibido no es una subida válida.',
                422
            );
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new FileValidationException(
                'Solo se permiten archivos con extensión .xml.',
                422
            );
        }

        $detectedMime = $this->detectMimeType($file['tmp_name']);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new FileValidationException(
                'El tipo MIME del archivo no es válido. Se esperaba text/xml o application/xml.',
                422
            );
        }

        $maxBytes = $this->resolveMaxUploadBytes();
        $fileSize = (int) ($file['size'] ?? 0);

        if ($fileSize <= 0) {
            throw new FileValidationException(
                'El archivo XML está vacío.',
                422
            );
        }

        if ($fileSize > $maxBytes) {
            throw new FileValidationException(
                'El archivo excede el tamaño máximo permitido.',
                413
            );
        }
    }

    private function assertValidXmlContent(string $temporaryPath): void
    {
        $previousLoaderState = null;

        if (function_exists('libxml_disable_entity_loader')) {
            $previousLoaderState = libxml_disable_entity_loader(true);
        }

        $previousInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new \DOMDocument();
        $loaded = $document->load($temporaryPath, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        if (function_exists('libxml_disable_entity_loader') && $previousLoaderState !== null) {
            libxml_disable_entity_loader($previousLoaderState);
        }

        if ($loaded !== true) {
            throw new FileValidationException(
                'El archivo no contiene un XML bien formado.',
                422
            );
        }

        $rootName = strtolower($document->documentElement->nodeName ?? '');

        if ($rootName === '' || !str_contains($rootName, 'comprobante')) {
            throw new FileValidationException(
                'El XML no parece ser un comprobante CFDI válido.',
                422
            );
        }
    }

    private function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return '';
        }

        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return is_string($mimeType) ? strtolower($mimeType) : '';
    }

    private function ensureStorageDirectoryExists(): void
    {
        if (is_dir($this->xmlStorageDirectory)) {
            return;
        }

        if (!mkdir($this->xmlStorageDirectory, 0750, true) && !is_dir($this->xmlStorageDirectory)) {
            throw new RuntimeException('No fue posible crear el directorio de almacenamiento XML.');
        }
    }

    private function resolveMaxUploadBytes(): int
    {
        $uploadMax = $this->parseIniSizeToBytes((string) ini_get('upload_max_filesize'));
        $postMax = $this->parseIniSizeToBytes((string) ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    private function parseIniSizeToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
