<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\JsonResponder;

/**
 * Inicialización segura de sesión PHP y validación CSRF para peticiones mutativas.
 */
final class SecurityMiddleware
{
    private const SESSION_CSRF_KEY = 'csrf_token';

    private const MUTATIVE_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    private function __construct()
    {
    }

    /**
     * Configura e inicia la sesión con parámetros de seguridad obligatorios.
     */
    public static function initializeSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::ensureCsrfToken();

            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $isSecure = self::isHttpsRequest();

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();
        self::ensureCsrfToken();
    }

    /**
     * Genera o recupera el token CSRF almacenado en sesión.
     */
    public static function ensureCsrfToken(): string
    {
        if (
            !isset($_SESSION[self::SESSION_CSRF_KEY])
            || !is_string($_SESSION[self::SESSION_CSRF_KEY])
            || $_SESSION[self::SESSION_CSRF_KEY] === ''
        ) {
            $_SESSION[self::SESSION_CSRF_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_CSRF_KEY];
    }

    public static function getCsrfToken(): string
    {
        return self::ensureCsrfToken();
    }

    /**
     * Valida el header X-CSRF-TOKEN en peticiones POST, PUT, DELETE y PATCH.
     */
    public static function validateCsrfForMutativeRequest(string $method): void
    {
        $method = strtoupper($method);

        if (!in_array($method, self::MUTATIVE_METHODS, true)) {
            return;
        }

        $sessionToken = $_SESSION[self::SESSION_CSRF_KEY] ?? '';
        $headerToken = self::resolveCsrfHeaderToken();

        if (
            !is_string($sessionToken)
            || $sessionToken === ''
            || $headerToken === ''
            || !hash_equals($sessionToken, $headerToken)
        ) {
            JsonResponder::send(403, [
                'error' => true,
                'message' => 'Acceso denegado.',
            ]);
        }
    }

    public static function isMutativeMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::MUTATIVE_METHODS, true);
    }

    private static function resolveCsrfHeaderToken(): string
    {
        $serverToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (is_string($serverToken) && $serverToken !== '') {
            return $serverToken;
        }

        if (!function_exists('getallheaders')) {
            return '';
        }

        $headers = getallheaders();

        if ($headers === false) {
            return '';
        }

        foreach ($headers as $name => $value) {
            if (strcasecmp((string) $name, 'X-CSRF-TOKEN') === 0 && is_string($value)) {
                return $value;
            }
        }

        return '';
    }

    private static function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
    }
}
