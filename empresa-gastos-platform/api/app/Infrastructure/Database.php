<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión singleton a MySQL mediante PDO con consultas preparadas y excepciones activas.
 */
final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = \EnvironmentLoader::get('DB_HOST', '127.0.0.1');
        $port = \EnvironmentLoader::get('DB_PORT', '3306');
        $database = \EnvironmentLoader::get('DB_DATABASE', 'empresa_gastos');
        $username = \EnvironmentLoader::get('DB_USERNAME', 'root');
        $password = \EnvironmentLoader::get('DB_PASSWORD', '');
        $charset = \EnvironmentLoader::get('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'No fue posible conectar con la base de datos.',
                0,
                $exception
            );
        }

        return self::$connection;
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
