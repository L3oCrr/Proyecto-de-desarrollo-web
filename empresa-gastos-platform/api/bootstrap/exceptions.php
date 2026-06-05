<?php

declare(strict_types=1);

use App\Core\Http\JsonResponder;

/**
 * Registra manejadores globales para garantizar respuestas JSON ante errores.
 */
final class ExceptionHandler
{
    public static function register(bool $debug = false): void
    {
        set_exception_handler(static function (Throwable $exception) use ($debug): void {
            self::renderThrowable($exception, $debug);
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(static function () use ($debug): void {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

            if (!in_array($error['type'], $fatalTypes, true)) {
                return;
            }

            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            self::renderThrowable($exception, $debug);
        });
    }

    private static function renderThrowable(Throwable $exception, bool $debug): void
    {
        $payload = [
            'error' => true,
            'message' => 'Error interno del servidor.',
        ];

        if ($debug) {
            $payload['debug'] = [
                'type' => $exception::class,
                'detail' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        JsonResponder::send(500, $payload);
    }
}
