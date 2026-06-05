<?php

declare(strict_types=1);

use App\Core\Router;

/**
 * Arranque principal de la aplicación API.
 */
function bootstrapApplication(): Router
{
    $rootPath = dirname(__DIR__);

    require_once $rootPath . '/bootstrap/environment.php';
    EnvironmentLoader::load($rootPath . '/.env');

    $autoloadPath = $rootPath . '/vendor/autoload.php';

    if (!is_readable($autoloadPath)) {
        throw new RuntimeException(
            'Ejecute "composer install" en el directorio api/ antes de usar la aplicación.'
        );
    }

    require_once $autoloadPath;

    require_once $rootPath . '/bootstrap/exceptions.php';
    ExceptionHandler::register(EnvironmentLoader::bool('APP_DEBUG', false));

    require_once $rootPath . '/bootstrap/routes.php';

    $router = new Router();
    registerRoutes($router);

    return $router;
}

/**
 * Resuelve la URI de la petición eliminando el prefijo del script público.
 */
function resolveRequestUri(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }

    return $uri === '' ? '/' : $uri;
}
