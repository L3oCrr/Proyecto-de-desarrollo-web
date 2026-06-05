<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\JsonResponder;

/**
 * Verifica que exista una sesión de usuario autenticado.
 */
final class AuthMiddleware
{
    public const SESSION_USER_ID = 'user_id';
    public const SESSION_AREA_ID = 'area_id';
    public const SESSION_ROL_ID = 'rol_id';

    private function __construct()
    {
    }

    public static function requireAuthentication(): void
    {
        $userId = $_SESSION[self::SESSION_USER_ID] ?? null;

        if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
            JsonResponder::send(401, [
                'error' => true,
                'message' => 'No autenticado.',
            ]);
        }

        if (is_string($userId)) {
            $_SESSION[self::SESSION_USER_ID] = (int) $userId;
        }
    }

    public static function isAuthenticated(): bool
    {
        $userId = $_SESSION[self::SESSION_USER_ID] ?? null;

        return is_int($userId) || (is_string($userId) && ctype_digit($userId) && (int) $userId > 0);
    }

    /**
     * Envuelve un handler exigiendo autenticación previa.
     */
    public static function guard(callable $handler): callable
    {
        return static function (mixed ...$params) use ($handler): void {
            self::requireAuthentication();
            $handler(...$params);
        };
    }
}
