/**
 * Pantalla de Login — consumo de API REST con protección CSRF.
 */
(function () {
    'use strict';

    /**
     * Resuelve la URL base del backend según la ubicación del frontend.
     * Ejemplo: .../web/public/index.html → .../api/public
     */
    function resolveApiBaseUrl() {
        const { origin, pathname } = window.location;
        const webPublicMarker = '/web/public/';

        if (pathname.includes(webPublicMarker)) {
            const projectRoot = pathname.split(webPublicMarker)[0];

            return origin + projectRoot + '/api/public';
        }

        return origin + '/api/public';
    }

    const API_BASE_URL = resolveApiBaseUrl();

    const form = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitButton = document.getElementById('login-submit');
    const errorAlert = document.getElementById('login-error');
    const successAlert = document.getElementById('login-success');

    if (!form || !emailInput || !passwordInput || !submitButton) {
        return;
    }

    function showAlert(alertElement, message) {
        if (!alertElement) {
            return;
        }

        alertElement.textContent = message;
        alertElement.classList.add('is-visible');
    }

    function hideAlert(alertElement) {
        if (!alertElement) {
            return;
        }

        alertElement.textContent = '';
        alertElement.classList.remove('is-visible');
    }

    function setLoading(isLoading) {
        submitButton.disabled = isLoading;
        submitButton.textContent = isLoading ? 'Iniciando sesión...' : 'Iniciar Sesión';
    }

    async function fetchCsrfToken() {
        const response = await fetch(API_BASE_URL + '/api/csrf-token', {
            method: 'GET',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('No fue posible obtener el token de seguridad.');
        }

        const payload = await response.json();

        if (!payload.csrf_token) {
            throw new Error('Respuesta CSRF inválida.');
        }

        return payload.csrf_token;
    }

    async function login(email, password, csrfToken) {
        return fetch(API_BASE_URL + '/api/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                email: email,
                password: password,
            }),
        });
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        hideAlert(errorAlert);
        hideAlert(successAlert);

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (email === '' || password === '') {
            showAlert(errorAlert, 'Ingrese correo y contraseña.');
            return;
        }

        setLoading(true);

        try {
            const csrfToken = await fetchCsrfToken();
            const response = await login(email, password, csrfToken);

            if (response.status === 401) {
                showAlert(errorAlert, 'Credenciales incorrectas.');
                return;
            }

            if (!response.ok) {
                let message = 'No fue posible iniciar sesión. Intente nuevamente.';

                try {
                    const errorPayload = await response.json();
                    if (errorPayload.message) {
                        message = errorPayload.message;
                    }
                } catch (parseError) {
                    // Mantener mensaje genérico si el cuerpo no es JSON.
                }

                showAlert(errorAlert, message);
                return;
            }

            const payload = await response.json();

            showAlert(
                successAlert,
                'Inicio de sesión exitoso. Redirigiendo al panel...'
            );

            sessionStorage.setItem('auth_user', JSON.stringify(payload.data ?? {}));

            window.setTimeout(function () {
                window.location.href = 'dashboard.html';
            }, 1200);
        } catch (error) {
            showAlert(
                errorAlert,
                error instanceof Error
                    ? error.message
                    : 'Error de conexión con el servidor.'
            );
        } finally {
            setLoading(false);
        }
    });
})();
