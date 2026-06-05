/**
 * Pantalla de Login — consumo de API REST con protección CSRF.
 */
(function () {
    'use strict';

    // 1. FORZAMOS LA RUTA EXACTA (Adiós al cálculo dinámico que causaba el Failed to fetch)
    const API_BASE_URL = 'http://localhost/Proyecto/empresa-gastos-platform/api/public';

    const form = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitButton = document.getElementById('login-submit');
    const errorAlert = document.getElementById('login-error');
    const successAlert = document.getElementById('login-success');

    if (!form || !emailInput || !passwordInput || !submitButton) {
        console.error("Faltan elementos HTML en el DOM. Verifica los IDs.");
        return;
    }

    function showAlert(alertElement, message) {
        if (!alertElement) return;
        alertElement.textContent = message;
        alertElement.classList.add('is-visible');
    }

    function hideAlert(alertElement) {
        if (!alertElement) return;
        alertElement.textContent = '';
        alertElement.classList.remove('is-visible');
    }

    function setLoading(isLoading) {
        submitButton.disabled = isLoading;
        submitButton.textContent = isLoading ? 'Iniciando sesión...' : 'Iniciar Sesión';
    }

    async function fetchCsrfToken() {
        console.log(`Solicitando token CSRF a: ${API_BASE_URL}/api/csrf-token`);
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
        console.log(`Enviando login a: ${API_BASE_URL}/api/auth/login`);
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
        // Vital para que la página no recargue y mate el Fetch
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
                    if (errorPayload.message) message = errorPayload.message;
                } catch (parseError) {}
                showAlert(errorAlert, message);
                return;
            }

            const payload = await response.json();

            showAlert(successAlert, 'Inicio de sesión exitoso. Redirigiendo al panel...');
            sessionStorage.setItem('auth_user', JSON.stringify(payload.data ?? {}));

            // Redirección al Dashboard
            window.setTimeout(function () {
                window.location.href = 'dashboard.html';
            }, 1200);

        } catch (error) {
            console.dir(error);
            console.error('Detalle del fallo:', error instanceof Error ? error.message : error);
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