<?php

declare(strict_types=1);

namespace App\Core\Security;

/**
 * Utilidad de hashing de contraseñas con BCRYPT (nativo PHP).
 */
final class HashHelper
{
    private function __construct()
    {
    }

    public static function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    public static function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }
}
