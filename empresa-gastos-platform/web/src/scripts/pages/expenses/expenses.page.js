/**
 * Mis Gastos — listado, catálogos y captura manual en borrador.
 */
(function () {
    'use strict';

    const API_BASE_URL = 'http://localhost/Proyecto/empresa-gastos-platform/api/public';

    const loadingOverlay = document.getElementById('app-loading');
    const welcomeTitle = document.getElementById('user-welcome');
    const welcomeMeta = document.getElementById('user-meta');
    const logoutButton = document.getElementById('logout-button');
    const expensesTableBody = document.getElementById('expenses-table-body');
    const expensesAlert = document.getElementById('expenses-alert');
    const expenseForm = document.getElementById('expense-form');
    const expenseSubmitButton = document.getElementById('expense-submit-button');
    const centroCostosSelect = document.getElementById('centro_costos_id');
    const cuentaContableSelect = document.getElementById('cuenta_contable_id');
    const expenseModalElement = document.getElementById('expense-modal');
    const xmlModalElement = document.getElementById('xml-modal');
    const xmlForm = document.getElementById('xml-form');
    const xmlFileInput = document.getElementById('xml-file');
    const xmlSubmitButton = document.getElementById('xml-submit-button');
    const xmlModalAlert = document.getElementById('xml-modal-alert');
    const xmlModalExpenseLabel = document.getElementById('xml-modal-expense-label');

    let expenseModalInstance = null;
    let xmlModalInstance = null;
    let currentXmlExpenseId = null;

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

    function getExpenseModal() {
        if (!expenseModalElement || typeof bootstrap === 'undefined') {
            return null;
        }

        if (!expenseModalInstance) {
            expenseModalInstance = bootstrap.Modal.getOrCreateInstance(expenseModalElement);
        }

        return expenseModalInstance;
    }

    function getXmlModal() {
        if (!xmlModalElement || typeof bootstrap === 'undefined') {
            return null;
        }

        if (!xmlModalInstance) {
            xmlModalInstance = bootstrap.Modal.getOrCreateInstance(xmlModalElement);
        }

        return xmlModalInstance;
    }

    function showAlert(message, type) {
        if (!expensesAlert) {
            return;
        }

        expensesAlert.textContent = message;
        expensesAlert.className = 'alert alert-' + type;
        expensesAlert.classList.remove('d-none');
    }

    function hideAlert() {
        if (expensesAlert) {
            expensesAlert.classList.add('d-none');
            expensesAlert.textContent = '';
        }
    }

    function showXmlModalAlert(message, type) {
        if (!xmlModalAlert) {
            return;
        }

        xmlModalAlert.textContent = message;
        xmlModalAlert.className = 'alert alert-' + type;
        xmlModalAlert.classList.remove('d-none');
    }

    function hideXmlModalAlert() {
        if (xmlModalAlert) {
            xmlModalAlert.classList.add('d-none');
            xmlModalAlert.textContent = '';
        }
    }

    function isDraftExpense(expense) {
        return String(expense.estatus_codigo || '').toUpperCase() === 'BORRADOR';
    }

    function renderActionsCell(expense) {
        if (!isDraftExpense(expense)) {
            return '<span class="text-muted">—</span>';
        }

        const expenseId = escapeHtml(expense.id);

        return (
            '<div class="d-flex flex-wrap gap-1 justify-content-end">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm btn-upload-xml" ' +
                    'data-expense-id="' + expenseId + '" title="Adjuntar CFDI">' +
                    'Subir XML' +
                '</button>' +
                '<button type="button" class="btn btn-success btn-sm btn-submit-approval" ' +
                    'data-expense-id="' + expenseId + '" title="Enviar a revisión">' +
                    'Enviar' +
                '</button>' +
            '</div>'
        );
    }

    function formatCurrency(value) {
        const amount = Number(value);

        if (Number.isNaN(amount)) {
            return value;
        }

        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    }

    function formatDate(value) {
        if (typeof value !== 'string' || value === '') {
            return '—';
        }

        const parts = value.split('-');

        if (parts.length !== 3) {
            return value;
        }

        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
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

    function populateSelect(selectElement, items, placeholder, labelBuilder) {
        if (!selectElement) {
            return;
        }

        selectElement.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        selectElement.appendChild(placeholderOption);

        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = labelBuilder(item);
            selectElement.appendChild(option);
        });
    }

    async function loadCatalogs() {
        const [costCentersResponse, accountsResponse] = await Promise.all([
            fetch(API_BASE_URL + '/api/centros-costo', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                },
            }),
            fetch(API_BASE_URL + '/api/cuentas', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                },
            }),
        ]);

        if (costCentersResponse.status === 401 || accountsResponse.status === 401) {
            redirectToLogin();
            return;
        }

        if (!costCentersResponse.ok || !accountsResponse.ok) {
            throw new Error('No fue posible cargar los catálogos del formulario.');
        }

        const costCentersPayload = await costCentersResponse.json();
        const accountsPayload = await accountsResponse.json();

        populateSelect(
            centroCostosSelect,
            Array.isArray(costCentersPayload.data) ? costCentersPayload.data : [],
            'Seleccione un centro de costos',
            function (item) {
                const codigo = item.codigo_contable ? item.codigo_contable + ' — ' : '';
                return codigo + (item.nombre || 'Centro de costos');
            }
        );

        populateSelect(
            cuentaContableSelect,
            Array.isArray(accountsPayload.data) ? accountsPayload.data : [],
            'Seleccione una cuenta contable',
            function (item) {
                const numero = item.numero_cuenta ? item.numero_cuenta + ' — ' : '';
                return numero + (item.descripcion || 'Cuenta contable');
            }
        );
    }

    function renderExpensesTable(expenses) {
        if (!expensesTableBody) {
            return;
        }

        if (!Array.isArray(expenses) || expenses.length === 0) {
            expensesTableBody.innerHTML =
                '<tr><td colspan="5" class="text-center text-muted py-4">No hay gastos registrados.</td></tr>';
            return;
        }

        expensesTableBody.innerHTML = expenses.map(function (expense) {
            const concepto = escapeHtml(expense.concepto_descripcion || '—');
            const fecha = escapeHtml(formatDate(expense.fecha_gasto));
            const monto = escapeHtml(formatCurrency(expense.monto_total));
            const estatus = escapeHtml(expense.estatus_nombre || expense.estatus_codigo || '—');
            const acciones = renderActionsCell(expense);
            const rowId = escapeHtml(expense.id);

            return (
                '<tr data-expense-id="' + rowId + '">' +
                    '<td>' + concepto + '</td>' +
                    '<td>' + fecha + '</td>' +
                    '<td class="text-end">' + monto + '</td>' +
                    '<td class="expense-status-cell">' + estatus + '</td>' +
                    '<td class="text-end expense-actions-cell">' + acciones + '</td>' +
                '</tr>'
            );
        }).join('');
    }

    function openXmlUploadModal(expenseId, concepto) {
        currentXmlExpenseId = expenseId;
        hideXmlModalAlert();

        if (xmlForm) {
            xmlForm.reset();
        }

        if (xmlModalExpenseLabel) {
            const label = concepto && concepto.trim() !== ''
                ? 'Gasto: ' + concepto.trim()
                : 'Gasto #' + expenseId;
            xmlModalExpenseLabel.textContent = label;
        }

        const modal = getXmlModal();

        if (modal) {
            modal.show();
        }
    }

    function updateExpenseRowToPending(expenseId, statusLabel) {
        if (!expensesTableBody) {
            return;
        }

        const row = expensesTableBody.querySelector('tr[data-expense-id="' + expenseId + '"]');

        if (!row) {
            return;
        }

        const statusCell = row.querySelector('.expense-status-cell');
        const actionsCell = row.querySelector('.expense-actions-cell');

        if (statusCell) {
            statusCell.textContent = statusLabel || 'Pendiente de Aprobación';
        }

        if (actionsCell) {
            actionsCell.innerHTML = '<span class="text-muted">—</span>';
        }
    }

    async function uploadXml(event) {
        event.preventDefault();
        hideXmlModalAlert();
        hideAlert();

        if (!xmlForm || !xmlForm.checkValidity()) {
            xmlForm.reportValidity();
            return;
        }

        if (!currentXmlExpenseId) {
            showXmlModalAlert('No se identificó el gasto seleccionado.', 'danger');
            return;
        }

        const file = xmlFileInput && xmlFileInput.files ? xmlFileInput.files[0] : null;

        if (!file) {
            showXmlModalAlert('Seleccione un archivo XML válido.', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('documento', file);

        if (xmlSubmitButton) {
            xmlSubmitButton.disabled = true;
            xmlSubmitButton.textContent = 'Subiendo...';
        }

        try {
            const csrfToken = await fetchCsrfToken();

            const response = await fetch(
                API_BASE_URL + '/api/gastos/' + currentXmlExpenseId + '/documento',
                {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                }
            );

            if (response.status === 401) {
                redirectToLogin();
                return;
            }

            const responsePayload = await response.json().catch(function () {
                return {};
            });

            if (response.status === 201) {
                const modal = getXmlModal();

                if (modal) {
                    modal.hide();
                }

                currentXmlExpenseId = null;
                showAlert('Comprobante XML cargado y procesado correctamente.', 'success');
                await loadExpenses();
                return;
            }

            const message = responsePayload.message
                || 'No fue posible subir el archivo XML.';

            if (response.status === 422) {
                showXmlModalAlert(message, 'danger');
                showAlert(message, 'danger');
                return;
            }

            showXmlModalAlert(message, 'danger');
        } catch (error) {
            console.dir(error);
            console.error('Detalle del fallo (subir XML):', error instanceof Error ? error.message : error);
            showXmlModalAlert(
                error instanceof Error
                    ? error.message
                    : 'Error de red al subir el XML.',
                'danger'
            );
        } finally {
            if (xmlSubmitButton) {
                xmlSubmitButton.disabled = false;
                xmlSubmitButton.textContent = 'Subir XML';
            }
        }
    }

    async function submitExpenseForApproval(expenseId) {
        const confirmed = window.confirm(
            '¿Desea enviar este gasto a aprobación? Se validará el presupuesto disponible del centro de costos.'
        );

        if (!confirmed) {
            return;
        }

        hideAlert();

        try {
            const csrfToken = await fetchCsrfToken();

            const response = await fetch(
                API_BASE_URL + '/api/gastos/' + expenseId + '/enviar',
                {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                }
            );

            if (response.status === 401) {
                redirectToLogin();
                return;
            }

            const responsePayload = await response.json().catch(function () {
                return {};
            });

            if (response.status === 200) {
                const updatedExpense = responsePayload.data || {};
                const statusLabel = updatedExpense.estatus_nombre
                    || updatedExpense.estatus_codigo
                    || 'Pendiente de Aprobación';

                showAlert('Gasto enviado a aprobación correctamente.', 'success');
                updateExpenseRowToPending(expenseId, statusLabel);
                return;
            }

            if (response.status === 422) {
                const message = responsePayload.message
                    || 'No fue posible enviar el gasto a aprobación.';
                showAlert(message, 'danger');
                return;
            }

            const message = responsePayload.message
                || 'No fue posible enviar el gasto a aprobación.';

            showAlert(message, 'danger');
        } catch (error) {
            console.dir(error);
            console.error('Detalle del fallo (enviar aprobación):', error instanceof Error ? error.message : error);
            showAlert(
                error instanceof Error
                    ? error.message
                    : 'Error de red al enviar el gasto a aprobación.',
                'danger'
            );
        }
    }

    function handleTableClick(event) {
        const uploadButton = event.target.closest('.btn-upload-xml');

        if (uploadButton) {
            const expenseId = Number(uploadButton.getAttribute('data-expense-id'));
            const row = uploadButton.closest('tr');
            const conceptoCell = row ? row.querySelector('td') : null;
            const concepto = conceptoCell ? conceptoCell.textContent : '';

            if (expenseId > 0) {
                openXmlUploadModal(expenseId, concepto);
            }

            return;
        }

        const submitButton = event.target.closest('.btn-submit-approval');

        if (submitButton) {
            const expenseId = Number(submitButton.getAttribute('data-expense-id'));

            if (expenseId > 0) {
                submitExpenseForApproval(expenseId);
            }
        }
    }

    async function loadExpenses() {
        const response = await fetch(API_BASE_URL + '/api/gastos', {
            method: 'GET',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });

        if (response.status === 401) {
            redirectToLogin();
            return;
        }

        if (!response.ok) {
            throw new Error('No fue posible cargar el historial de gastos.');
        }

        const payload = await response.json();
        renderExpensesTable(payload.data);
    }

    async function verifySession() {
        const response = await fetchCurrentUser();

        if (response.status === 401 || !response.ok) {
            redirectToLogin();
            return false;
        }

        const payload = await response.json();
        const user = payload.data ?? null;

        if (!user || typeof user !== 'object') {
            redirectToLogin();
            return false;
        }

        renderUser(user);
        hideLoading();
        return true;
    }

    async function initializePage() {
        try {
            const isAuthenticated = await verifySession();

            if (!isAuthenticated) {
                return;
            }

            await loadCatalogs();
            await loadExpenses();
        } catch (error) {
            console.dir(error);
            console.error('Detalle del fallo (expenses):', error instanceof Error ? error.message : error);
            showAlert(
                error instanceof Error
                    ? error.message
                    : 'Ocurrió un error al cargar la pantalla de gastos.',
                'danger'
            );
            hideLoading();
        }
    }

    async function submitExpense(event) {
        event.preventDefault();
        hideAlert();

        if (!expenseForm || !expenseForm.checkValidity()) {
            expenseForm.reportValidity();
            return;
        }

        const formData = new FormData(expenseForm);
        const payload = {
            concepto_descripcion: String(formData.get('concepto_descripcion') || '').trim(),
            monto_total: Number(formData.get('monto_total')),
            fecha_gasto: String(formData.get('fecha_gasto') || '').trim(),
            centro_costos_id: Number(formData.get('centro_costos_id')),
            cuenta_contable_id: Number(formData.get('cuenta_contable_id')),
        };

        if (expenseSubmitButton) {
            expenseSubmitButton.disabled = true;
            expenseSubmitButton.textContent = 'Guardando...';
        }

        try {
            const csrfToken = await fetchCsrfToken();

            const response = await fetch(API_BASE_URL + '/api/gastos', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (response.status === 401) {
                redirectToLogin();
                return;
            }

            const responsePayload = await response.json().catch(function () {
                return {};
            });

            if (response.status === 201) {
                const modal = getExpenseModal();

                if (modal) {
                    modal.hide();
                }

                expenseForm.reset();
                showAlert('Gasto guardado correctamente en estado Borrador.', 'success');
                await loadExpenses();
                return;
            }

            const message = responsePayload.message
                || 'No fue posible guardar el gasto. Verifique los datos e intente de nuevo.';

            showAlert(message, 'danger');
        } catch (error) {
            console.dir(error);
            console.error('Detalle del fallo (crear gasto):', error instanceof Error ? error.message : error);
            showAlert(
                error instanceof Error
                    ? error.message
                    : 'Error de red al guardar el gasto.',
                'danger'
            );
        } finally {
            if (expenseSubmitButton) {
                expenseSubmitButton.disabled = false;
                expenseSubmitButton.textContent = 'Guardar Borrador';
            }
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
            console.dir(error);
            console.error('Detalle del fallo (logout):', error instanceof Error ? error.message : error);

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

    if (expenseForm) {
        expenseForm.addEventListener('submit', submitExpense);
    }

    if (expenseModalElement) {
        expenseModalElement.addEventListener('hidden.bs.modal', function () {
            if (expenseForm) {
                expenseForm.reset();
            }
        });
    }

    if (xmlForm) {
        xmlForm.addEventListener('submit', uploadXml);
    }

    if (xmlModalElement) {
        xmlModalElement.addEventListener('hidden.bs.modal', function () {
            currentXmlExpenseId = null;
            hideXmlModalAlert();

            if (xmlForm) {
                xmlForm.reset();
            }
        });
    }

    if (expensesTableBody) {
        expensesTableBody.addEventListener('click', handleTableClick);
    }

    initializePage();
})();
