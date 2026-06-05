<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\JsonResponder;
use App\Core\Security\HashHelper;
use App\Middleware\AuthMiddleware;
use App\Models\User;

final class AuthController extends BaseController
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function login(): void
    {
        $input = $this->parseJsonBody();
        $email = $this->sanitizeLoginEmail($input['email'] ?? null);
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';

        if ($email === null || $password === '') {
            JsonResponder::send(422, [
                'error' => true,
                'message' => 'Error de validación en los datos enviados.',
                'errors' => [
                    'credentials' => ['Correo y contraseña son obligatorios.'],
                ],
            ]);
        }

        $user = $this->model->findByEmail($email);

        if ($user === null || !HashHelper::verify($password, (string) $user['password'])) {
            JsonResponder::send(401, [
                'error' => true,
                'message' => 'Credenciales inválidas.',
            ]);
        }

        session_regenerate_id(true);

        $_SESSION[AuthMiddleware::SESSION_USER_ID] = (int) $user['id'];
        $_SESSION[AuthMiddleware::SESSION_AREA_ID] = (int) $user['area_id'];
        $_SESSION[AuthMiddleware::SESSION_ROL_ID] = (int) $user['rol_id'];

        unset($user['password']);

        JsonResponder::send(200, [
            'data' => $user,
        ]);
    }

    public function logout(): void
    {
        AuthMiddleware::requireAuthentication();

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
            session_destroy();
        }

        JsonResponder::send(200, [
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(): void
    {
        AuthMiddleware::requireAuthentication();

        $userId = (int) $_SESSION[AuthMiddleware::SESSION_USER_ID];
        $user = $this->model->findPublicById($userId);

        if ($user === null) {
            $_SESSION = [];
            JsonResponder::send(401, [
                'error' => true,
                'message' => 'No autenticado.',
            ]);
        }

        JsonResponder::send(200, [
            'data' => $user,
        ]);
    }

    private function sanitizeLoginEmail(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $email = strtolower(trim($value));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }
}
