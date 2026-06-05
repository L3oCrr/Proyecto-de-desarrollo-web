/**
 * Dashboard — protección de ruta, bienvenida y cierre de sesión.
 */
(function () {
    'use strict';

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

    const loadingOverlay = document.getElementById('app-loading');
    const welcomeTitle = document.getElementById('user-welcome');
    const welcomeMeta = document.getElementById('user-meta');
    const logoutButton = document.getElementById('logout-button');

    function redirectToLogin() {
        sessionStorage.removeItem('auth_user');
        window.location.replace('index.html');
    }

    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('is-hidden');
        }

        document.body.classList.add('app-ready');
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

    async function fetchCurrentUser() {
        return fetch(API_BASE_URL + '/api/auth/me', {
            method: 'GET',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });
    }

    function renderUser(user) {
        const nombre = typeof user.nombre === 'string' && user.nombre.trim() !== ''
            ? user.nombre.trim()
            : 'Usuario';

        if (welcomeTitle) {
            welcomeTitle.textContent = 'Bienvenido, ' + nombre;
        }

        if (welcomeMeta) {
            const email = typeof user.email === 'string' ? user.email : '';
            welcomeMeta.textContent = email !== '' ? email : 'Sesión activa';
        }

        sessionStorage.setItem('auth_user', JSON.stringify(user));
    }

    async function verifySession() {
        try {
            const response = await fetchCurrentUser();

            if (response.status === 401) {
                redirectToLogin();
                return;
            }

            if (!response.ok) {
                redirectToLogin();
                return;
            }

            const payload = await response.json();
            const user = payload.data ?? null;

            if (!user || typeof user !== 'object') {
                redirectToLogin();
                return;
            }

            renderUser(user);
            hideLoading();
        } catch (error) {
            redirectToLogin();
        }
    }

    async function logout() {
        if (logoutButton) {
            logoutButton.disabled = true;
            logoutButton.textContent = 'Cerrando sesión...';
        }

        try {
            const csrfToken = await fetchCsrfToken();

            const response = await fetch(API_BASE_URL + '/api/auth/logout', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('No fue posible cerrar la sesión.');
            }

            redirectToLogin();
        } catch (error) {
            if (logoutButton) {
                logoutButton.disabled = false;
                logoutButton.textContent = 'Cerrar Sesión';
            }

            window.alert(
                error instanceof Error
                    ? error.message
                    : 'Error al cerrar sesión.'
            );
        }
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', function () {
            logout();
        });
    }

    verifySession();
})();
