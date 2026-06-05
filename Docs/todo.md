# Base structure and API routing implementation
_Exported on 6/5/2026 at 15:47:12 CST from Cursor (3.6.31)_

---

**User**

Actúa como un desarrollador PHP experto trabajando bajo Vanilla PHP 8+ (sin frameworks full-stack). Nuestro objetivo es implementar el incremento [B-001]: Estructura Base, Ruteo API y Conexión a BD para un sistema de gestión de gastos.

CONTEXTO ARQUITECTÓNICO:
- La aplicación backend funcionará como una API RESTful que devuelve exclusivamente respuestas JSON.
- No utilizaremos frameworks (ni Laravel, ni Symfony), todo debe ser código nativo orientado a objetos.

OBJETIVO PUNTUAL:
Generar la fundación técnica inicial configurando el punto de entrada de la API, un enrutador básico, la carga de variables de entorno y una clase de conexión a la base de datos segura.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/public/index.php` (Front controller que atrape todas las peticiones).
2. `empresa-gastos-platform/api/public/.htaccess` (Reglas de reescritura para Apache para redirigir todo a index.php).
3. `empresa-gastos-platform/api/public/health.php` (Endpoint simple de healthcheck que devuelva un JSON HTTP 200).
4. `empresa-gastos-platform/api/.env.example` y un parser muy básico en PHP para leerlo (ya que no usaremos dependencias externas complejas de Composer si es posible evitarlo, o si requieres `vlucas/phpdotenv`, indícalo explícitamente y crea el `composer.json`).
5. `empresa-gastos-platform/api/app/Infrastructure/Database.php` (Clase estática o Singleton usando PDO para conectar a MySQL, forzando manejo de errores mediante excepciones y preparadas).
6. Un manejador global básico de excepciones que asegure que cualquier error (incluso fatal) se devuelva como un JSON con código HTTP 500.

REGLAS DE NEGOCIO Y RESTRICCIONES:
- Todo el output del backend debe tener el header `Content-Type: application/json`.
- La clase de base de datos PDO debe configurarse con `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` y `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.
- No debe existir NADA de renderizado HTML en esta capa.

ENTREGABLES ESPERADOS:
Genera el código limpio y comentado de los archivos mencionados. Al final de tu respuesta, entrégame un breve resumen de cómo probar el enrutador desde mi navegador o Postman.

---

**Cursor**

Revisando la documentación del proyecto y la estructura existente antes de implementar la fundación técnica B-001.


Creando la estructura base del incremento B-001 según la documentación del proyecto.


### A. Resumen breve de lo implementado

Se creó la fundación técnica **B-001** para la API REST en Vanilla PHP: front controller con `.htaccess`, enrutador regex, parser `.env` sin dependencias externas, conexión PDO singleton, manejador global de excepciones/errores fatales en JSON y endpoints de healthcheck.

### B. Supuestos mínimos aplicados

- **Sin `vlucas/phpdotenv`**: parser propio en `bootstrap/environment.php`.
- **Autoload PSR-4** vía Composer (solo PHP ≥ 8.0, sin paquetes de terceros).
- Rutas registradas: `/api/health` y `/health` (criterio de aceptación del backlog).
- `health.php` es un endpoint directo independiente del enrutador (útil para monitoreo).

### C. Archivos creados o modificados

| Archivo | Propósito |
|---------|-----------|
| `api/public/index.php` | Front controller |
| `api/public/.htaccess` | Rewrite a `index.php` |
| `api/public/health.php` | Healthcheck directo |
| `api/.env.example` | Plantilla de variables |
| `api/bootstrap/environment.php` | Parser `.env` |
| `api/bootstrap/exceptions.php` | Manejador global JSON 500 |
| `api/bootstrap/routes.php` | Registro de rutas |
| `api/bootstrap/app.php` | Bootstrap de la app |
| `api/app/Core/Router.php` | Enrutador HTTP |
| `api/app/Core/Http/JsonResponder.php` | Respuestas JSON |
| `api/app/Infrastructure/Database.php` | PDO singleton |
| `api/composer.json` | Autoload PSR-4 |
| `api/.gitignore` | Excluye `.env` y `vendor/` |

### D. Código clave

**Front controller** (`index.php`):

```1:14:empresa-gastos-platform/api/public/index.php
<?php

declare(strict_types=1);

/**
 * Front controller: punto de entrada único para la API REST.
 */
require_once dirname(__DIR__) . '/bootstrap/app.php';

$router = bootstrapApplication();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = resolveRequestUri();

$router->dispatch($method, $uri);
```

**PDO con excepciones** (`Database.php`):

```36:52:empresa-gastos-platform/api/app/Infrastructure/Database.php
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
```

**Manejador global** (`exceptions.php`): captura excepciones no controladas, errores convertidos a `ErrorException` y errores fatales vía `register_shutdown_function`; todo responde HTTP 500 en JSON.

### E. Integración

1. Copiar `.env.example` → `.env` y ajustar credenciales MySQL.
2. En `api/`, ejecutar:
   ```bash
   composer install
   ```
3. Apache debe tener `mod_rewrite` activo; el document root efectivo es `api/public/`.
4. `Database::getConnection()` queda listo para usarse en B-002+; aún no se invoca en rutas de esta fase.

### F. Pasos de prueba manual

**Preparación (una sola vez):**
```powershell
cd C:\xampp\htdocs\Proyecto\empresa-gastos-platform\api
copy .env.example .env
php composer.phar install
```
(Asegúrate de que Apache esté iniciado en XAMPP.)

**Desde navegador o Postman:**

| Prueba | URL | Resultado esperado |
|--------|-----|-------------------|
| Health vía router | `http://localhost/Proyecto/empresa-gastos-platform/api/public/api/health` | HTTP **200**, JSON `{ "status": "ok", ... }` |
| Health directo | `http://localhost/Proyecto/empresa-gastos-platform/api/public/health.php` | HTTP **200**, JSON con `"source": "health.php"` |
| Ruta inexistente | `http://localhost/Proyecto/empresa-gastos-platform/api/public/cualquier-cosa` | HTTP **404**, JSON `{ "error": true, "message": "La ruta solicitada no existe." }` |

**Con cURL (PowerShell):**
```powershell
curl.exe "http://localhost/Proyecto/empresa-gastos-platform/api/public/api/health"
curl.exe "http://localhost/Proyecto/empresa-gastos-platform/api/public/ruta-falsa"
```

**Postman:** método `GET`, header `Accept: application/json`, misma URL base.

### G. Límites del incremento

- No hay migraciones SQL (B-002).
- No hay middleware CSRF/sesiones (B-003).
- `Database` está implementada pero no expuesta en un endpoint aún.
- No se incluyen controladores de negocio ni CORS.

---

**Nota:** No se usa `vlucas/phpdotenv`; el parser es nativo. Si Apache devuelve 404 en rutas amigables, verifica que `mod_rewrite` esté habilitado y que `AllowOverride All` aplique al directorio del proyecto.

---

**User**

<system_notification>
The following task has finished. If you were already aware, ignore this notification and do not restate prior responses.

<task>
kind: shell
status: error
task_id: 548555
title: Test Apache health endpoint with curl.exe
detail: exit_code=7
output_path: C:\Users\Leonardo\.cursor\projects\c-xampp-htdocs-Proyecto\terminals\548555.txt
</task>
</system_notification>
<user_query>Briefly inform the user about the task result and perform any follow-up actions (if needed). If there's no follow-ups needed, don't explicitly say that.</user_query>

---

**Cursor**

La prueba contra Apache no llegó a ejecutarse: `curl` devolvió **HTTP:000** (código de salida 7), lo que indica que **no había conexión con `localhost`** — casi seguro Apache no estaba levantado en XAMPP en ese momento.

La implementación **B-001** ya quedó lista y las rutas se validaron por CLI (`/api/health` → 200 JSON, ruta inexistente → 404 JSON). Para probar en navegador o Postman, inicia **Apache** desde el panel de XAMPP y abre:

`http://localhost/Proyecto/empresa-gastos-platform/api/public/api/health`

---

**User**

Actúa como un Administrador de Base de Datos experto en MySQL/MariaDB. Nuestro objetivo es implementar el incremento [B-002]: Esquema de Base de Datos y Migración Inicial.

CONTEXTO:
Estamos construyendo una API REST en Vanilla PHP para un Sistema de Gestión de Gastos. La base de datos debe ser relacional, transaccional y robusta para auditorías.

OBJETIVO PUNTUAL:
Generar un único script SQL DDL que cree la base de datos y todas sus tablas con sus respectivas relaciones, basándote en el "Documento de Diseño de Base de Datos: Diccionario de Datos".

ARCHIVOS A CREAR:
1. `empresa-gastos-platform/api/database/migrations/init.sql`

REGLAS Y RESTRICCIONES DDL:
- Usa el motor transaccional `InnoDB` para todas las tablas y el charset `utf8mb4_unicode_ci`.
- Las tablas deben estar en plural y minúsculas con notación `snake_case` (ej. `centro_costos`).
- Las Primary Keys (PK) deben ser `id BIGINT UNSIGNED AUTO_INCREMENT`.
- Las Foreign Keys (FK) deben mantener integridad referencial estricta.
- Los montos monetarios deben ser `DECIMAL(12,4)`.
- Aplica borrado lógico agregando `deleted_at DATETIME NULL` en catálogos y tablas maestras.
- Añade campos de auditoría (`created_at`, `updated_at`) donde aplique.
- NO crees Stored Procedures ni Triggers.
- INCLUYE los dos índices recomendados en la tabla de gastos: `idx_gastos_presupuesto` (centro_costos_id, estatus_gasto_id, fecha_gasto, monto_total) e `idx_gastos_busqueda` (fecha_gasto, estatus_gasto_id).

TABLAS A CREAR (en orden para evitar errores de FK):
1. roles
2. areas
3. centro_costos
4. presupuestos
5. usuarios
6. catalogo_cuentas
7. estatus_gastos
8. facturas_cfdi
9. gastos
10. bitacora_auditoria

ENTREGABLES ESPERADOS:
Genera exclusivamente el código SQL limpio, ordenado y comentado para el archivo `init.sql`. Incluye sentencias `DROP TABLE IF EXISTS` al inicio (en orden inverso) para que el script sea idempotente.

---

**Cursor**

Revisando el diccionario de datos en la documentación para generar el script DDL completo.


Generando `init.sql` según el diccionario de datos del documento de diseño.


### A. Resumen breve de lo implementado

Se creó el script DDL idempotente `init.sql` con la base de datos `empresa_gastos`, las 10 tablas del diccionario de datos, integridad referencial estricta, borrado lógico en catálogos/maestras, auditoría temporal y los dos índices recomendados en `gastos`. El script se ejecutó correctamente en MySQL/MariaDB de XAMPP.

### B. Supuestos mínimos aplicados

- Nombre de BD alineado con `.env.example`: `empresa_gastos`.
- `presupuestos`, `estatus_gastos`, `facturas_cfdi`, `gastos` y `bitacora_auditoria` **sin** `deleted_at` (no aparece en el diccionario para esas tablas).
- FK con `ON DELETE RESTRICT` y `ON UPDATE CASCADE` para preservar históricos de auditoría.
- `CHECK (periodo_mes BETWEEN 1 AND 12)` en `presupuestos` como validación mínima de integridad.

### C. Archivos creados

- `empresa-gastos-platform/api/database/migrations/init.sql`

### D. Código completo

El SQL está en:

`empresa-gastos-platform/api/database/migrations/init.sql`

Incluye: `CREATE DATABASE`, `DROP TABLE IF EXISTS` en orden inverso, creación de tablas en el orden solicitado, InnoDB, `utf8mb4_unicode_ci`, PK `BIGINT UNSIGNED AUTO_INCREMENT`, montos `DECIMAL(12,4)`, `idx_gastos_presupuesto` e `idx_gastos_busqueda`.

### E. Integración

Coincide con `DB_DATABASE=empresa_gastos` del `.env` de B-001. Tras ejecutarlo, `Database::getConnection()` puede conectarse a un esquema listo para B-003 en adelante.

### F. Pasos de prueba manual

```powershell
# Desde consola MySQL de XAMPP
C:\xampp\mysql\bin\mysql.exe -u root -p < C:\xampp\htdocs\Proyecto\empresa-gastos-platform\api\database\migrations\init.sql

# Verificar tablas
C:\xampp\mysql\bin\mysql.exe -u root -e "USE empresa_gastos; SHOW TABLES;"
```

**Resultado esperado:** 10 tablas, motor `InnoDB`, collation `utf8mb4_unicode_ci`. Re-ejecutar el script debe completarse sin errores (idempotente).

### G. Límites del incremento

- Sin datos semilla (roles, estatus_gastos, etc.) — corresponde a seeders futuros.
- Sin stored procedures, triggers ni vistas SQL.
- Restricciones de negocio avanzadas (presupuesto, encadenamiento de estados) se aplicarán en la capa de servicios PHP.

---

**User**

Actúa como un desarrollador backend experto en seguridad usando Vanilla PHP 8+. Nuestro objetivo es implementar el incremento [B-003]: Seguridad, CSRF y Manejo de Sesiones Seguras.

CONTEXTO:
Estamos construyendo una API REST. Ya tenemos un enrutador básico y necesitamos blindarlo antes de empezar a recibir datos mutativos (POST/PUT/DELETE).

OBJETIVO PUNTUAL:
Crear un middleware de seguridad que inicialice de forma robusta las sesiones PHP y provea protección CSRF mediante un token que el frontend deberá enviar en los headers.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Middleware/SecurityMiddleware.php` (Clase nueva para inicializar sesión y validar CSRF).
2. `empresa-gastos-platform/api/app/Core/Security/HashHelper.php` (Clase utilitaria estática simple que envuelva `password_hash` con BCRYPT y `password_verify`).
3. `empresa-gastos-platform/api/public/index.php` (Modificar para ejecutar el inicio de sesión seguro en cada petición).
4. `empresa-gastos-platform/api/app/Core/Router.php` (Integrar el SecurityMiddleware para proteger rutas POST, PUT o DELETE).

REGLAS DE SEGURIDAD Y RESTRICCIONES:
- La sesión debe iniciar configurando obligatoriamente: `cookie_httponly` en true, `use_strict_mode` en true y `cookie_samesite` en 'Strict'.
- El token CSRF debe generarse y almacenarse en `$_SESSION['csrf_token']` si no existe.
- Crea un endpoint temporal exclusivo de prueba (ej. `GET /api/csrf-token`) que devuelva el token actual en un JSON.
- Toda petición mutativa (POST, PUT, DELETE, PATCH) debe ser interceptada por el middleware. Se debe verificar que el token exista en el header `X-CSRF-TOKEN` de la petición y que coincida con la sesión.
- Si la validación CSRF falla, el sistema debe abortar devolviendo un código HTTP 403 y un JSON de error genérico.

ENTREGABLES ESPERADOS:
Genera el código limpio y comentado. Al finalizar, explícame brevemente cómo inyectar el header en Postman para probar que la protección CSRF bloquea y permite el acceso correctamente.

---

**Cursor**

Revisando la estructura actual para integrar el middleware de seguridad y CSRF.


Implementando el middleware de seguridad, integrándolo en el enrutador y añadiendo los endpoints de prueba.


Corrigiendo el orden de carga: la sesión debe iniciarse después del autoload de Composer.


### A. Resumen breve de lo implementado

Se añadió **sesión segura** en cada petición, **validación CSRF** en el router para métodos mutativos, utilidad **BCRYPT** (`HashHelper`), endpoint `GET /api/csrf-token` y un `POST /api/csrf-test` temporal para probar el bloqueo.

### B. Supuestos mínimos aplicados

- `cookie_secure` solo en HTTPS (en local HTTP queda en `false` para no romper la sesión en XAMPP).
- La validación CSRF corre **antes** de resolver la ruta (toda petición mutativa exige token, incluso rutas inexistentes).
- `POST /api/csrf-test` es temporal solo para pruebas de B-003.

### C. Archivos creados o modificados

| Archivo | Acción |
|---------|--------|
| `app/Middleware/SecurityMiddleware.php` | Creado |
| `app/Core/Security/HashHelper.php` | Creado |
| `app/Core/Router.php` | Modificado (PUT/DELETE/PATCH + CSRF) |
| `public/index.php` | Modificado (sesión segura) |
| `bootstrap/routes.php` | Modificado (`/api/csrf-token`, `/api/csrf-test`) |

### D. Comportamiento clave

**Sesión** (`SecurityMiddleware::initializeSecureSession`):
- `session.use_strict_mode = 1`
- Cookie: `httponly = true`, `samesite = Strict`
- Token en `$_SESSION['csrf_token']` (64 caracteres hex)

**CSRF** (en `Router::dispatch`):
- Métodos protegidos: `POST`, `PUT`, `DELETE`, `PATCH`
- Header requerido: `X-CSRF-TOKEN`
- Fallo → HTTP **403** + `{ "error": true, "message": "Acceso denegado." }`
- Comparación con `hash_equals()` (timing-safe)

### E. Integración

Flujo en `index.php`:

1. `bootstrapApplication()` (autoload + rutas)
2. `SecurityMiddleware::initializeSecureSession()`
3. `dispatch()`

El frontend debe: obtener token con `GET /api/csrf-token` (misma sesión/cookie) y enviarlo en mutaciones.

### F. Prueba en Postman

**Importante:** activa en Postman **Cookies** (o “Automatically follow redirects”) para que la cookie `PHPSESSID` se guarde entre peticiones.

**1. Obtener token (sin header CSRF)**

- Método: `GET`
- URL: `http://localhost/Proyecto/empresa-gastos-platform/api/public/api/csrf-token`
- Respuesta esperada: `200` con `{ "csrf_token": "..." }`

**2. Probar bloqueo (debe fallar)**

- Método: `POST`
- URL: `http://localhost/Proyecto/empresa-gastos-platform/api/public/api/csrf-test`
- Sin header `X-CSRF-TOKEN`
- Respuesta esperada: **403** — `Acceso denegado.`

**3. Probar acceso permitido**

- Misma petición `POST` a `/api/csrf-test`
- En **Headers** agrega:
  - Key: `X-CSRF-TOKEN`
  - Value: *(pega el valor de `csrf_token` del paso 1)*
- Respuesta esperada: **200** — `Token CSRF válido. Petición mutativa permitida.`

**Tip Postman:** en la pestaña Headers, no uses `Authorization`; el token va solo en `X-CSRF-TOKEN`. Si cambias de colección o borras cookies, repite el paso 1 para un token nuevo.

### G. Límites del incremento

- Sin login ni RBAC (B-005).
- `HashHelper` listo pero aún no usado en endpoints.
- `POST /api/csrf-test` debe eliminarse cuando existan rutas reales mutativas.
- CORS no configurado; el frontend debe ser same-site o compartir dominio/cookies según despliegue.

---

**User**

Actúa como un desarrollador backend experto en seguridad usando Vanilla PHP 8+. Nuestro objetivo es implementar el incremento [B-003]: Seguridad, CSRF y Manejo de Sesiones Seguras.

CONTEXTO:
Estamos construyendo una API REST. Ya tenemos un enrutador básico y necesitamos blindarlo antes de empezar a recibir datos mutativos (POST/PUT/DELETE).

OBJETIVO PUNTUAL:
Crear un middleware de seguridad que inicialice de forma robusta las sesiones PHP y provea protección CSRF mediante un token que el frontend deberá enviar en los headers.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Middleware/SecurityMiddleware.php` (Clase nueva para inicializar sesión y validar CSRF).
2. `empresa-gastos-platform/api/app/Core/Security/HashHelper.php` (Clase utilitaria estática simple que envuelva `password_hash` con BCRYPT y `password_verify`).
3. `empresa-gastos-platform/api/public/index.php` (Modificar para ejecutar el inicio de sesión seguro en cada petición).
4. `empresa-gastos-platform/api/app/Core/Router.php` (Integrar el SecurityMiddleware para proteger rutas POST, PUT o DELETE).

REGLAS DE SEGURIDAD Y RESTRICCIONES:
- La sesión debe iniciar configurando obligatoriamente: `cookie_httponly` en true, `use_strict_mode` en true y `cookie_samesite` en 'Strict'.
- El token CSRF debe generarse y almacenarse en `$_SESSION['csrf_token']` si no existe.
- Crea un endpoint temporal exclusivo de prueba (ej. `GET /api/csrf-token`) que devuelva el token actual en un JSON.
- Toda petición mutativa (POST, PUT, DELETE, PATCH) debe ser interceptada por el middleware. Se debe verificar que el token exista en el header `X-CSRF-TOKEN` de la petición y que coincida con la sesión.
- Si la validación CSRF falla, el sistema debe abortar devolviendo un código HTTP 403 y un JSON de error genérico.

ENTREGABLES ESPERADOS:
Genera el código limpio y comentado. Al finalizar, explícame brevemente cómo inyectar el header en Postman para probar que la protección CSRF bloquea y permite el acceso correctamente.

---

**Cursor**

Comprobando si B-003 ya está implementado y si cumple todos los requisitos.


**B-003 ya está implementado** en el proyecto. Resumen de lo que tienes y cómo probarlo.

### A. Resumen

- **Sesión segura** en cada petición (`httponly`, `use_strict_mode`, `SameSite=Strict`).
- **Token CSRF** en `$_SESSION['csrf_token']`.
- **Validación** en `POST`, `PUT`, `DELETE`, `PATCH` vía header `X-CSRF-TOKEN`.
- **403** con JSON genérico si falla la validación.
- **`HashHelper`** con BCRYPT listo para login (B-005).

### B. Archivos

| Archivo | Rol |
|---------|-----|
| `app/Middleware/SecurityMiddleware.php` | Sesión + CSRF |
| `app/Core/Security/HashHelper.php` | `password_hash` / `password_verify` |
| `public/index.php` | Bootstrap → sesión → dispatch |
| `app/Core/Router.php` | Intercepta métodos mutativos |
| `bootstrap/routes.php` | `GET /api/csrf-token`, `POST /api/csrf-test` |

### C. Flujo

```12:19:empresa-gastos-platform/api/public/index.php
$router = bootstrapApplication();

SecurityMiddleware::initializeSecureSession();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = resolveRequestUri();

$router->dispatch($method, $uri);
```

```59:61:empresa-gastos-platform/api/app/Core/Router.php
        if (SecurityMiddleware::isMutativeMethod($method)) {
            SecurityMiddleware::validateCsrfForMutativeRequest($method);
        }
```

---

## Cómo probar en Postman

**Base URL:**  
`http://localhost/Proyecto/empresa-gastos-platform/api/public`

Activa el manejo de **cookies** en Postman para conservar `PHPSESSID` entre peticiones.

### 1. Obtener el token

- **GET** `/api/csrf-token`
- Respuesta: `{ "csrf_token": "abc123..." }`

### 2. Probar bloqueo (sin header)

- **POST** `/api/csrf-test`
- Sin header `X-CSRF-TOKEN`
- Esperado: **403** → `{ "error": true, "message": "Acceso denegado." }`

### 3. Probar acceso permitido

- Misma **POST** `/api/csrf-test`
- En **Headers** agrega:

| Key | Value |
|-----|--------|
| `X-CSRF-TOKEN` | *(valor de `csrf_token` del paso 1)* |

- Esperado: **200** → `{ "success": true, "message": "Token CSRF válido..." }`

**Nota:** Si borras cookies o abres otra pestaña sin sesión, repite el paso 1; el token debe coincidir con la misma sesión que envía la cookie.

### Límites

- `POST /api/csrf-test` es solo para pruebas; quítalo cuando existan endpoints reales.
- `HashHelper` aún no se usa en rutas (llegará con autenticación en B-005).

Si quieres, el siguiente paso puede ser **B-004** (catálogos) o ajustar CORS para el frontend.

---

**User**

Actúa como un desarrollador backend experto en Vanilla PHP 8+. Nuestro objetivo es implementar el incremento [B-004]: Catálogos Base.

CONTEXTO:
Estamos construyendo una API REST. Ya tenemos nuestra conexión PDO (Database.php), el enrutador base y un SecurityMiddleware que protege las peticiones mutativas con CSRF. La base de datos ya tiene las tablas creadas.

OBJETIVO PUNTUAL:
Desarrollar los Modelos y Controladores para gestionar (Listar y Crear) los cuatro catálogos principales del sistema: `roles`, `areas`, `centro_costos` y `catalogo_cuentas`. Registrar las rutas correspondientes.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Models/Model.php` (Clase base abstracta opcional para reutilizar la conexión PDO).
2. `empresa-gastos-platform/api/app/Models/Role.php`, `Area.php`, `CostCenter.php`, `AccountCatalog.php` (Modelos con métodos `all()` y `create()`).
3. `empresa-gastos-platform/api/app/Controllers/RolesController.php`, `AreasController.php`, `CostCentersController.php`, `AccountCatalogsController.php` (Controladores que procesen la petición y usen `JsonResponder`).
4. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar las rutas GET y POST para `/api/roles`, `/api/areas`, `/api/centros-costo`, `/api/cuentas`).

REGLAS DE NEGOCIO Y RESTRICCIONES DDL:
- Para `centro_costos`, la creación (POST) requiere obligatoriamente enviar un `area_id` válido.
- Para todos los POST, se deben sanitizar los inputs y validar los tipos de datos requeridos por la base de datos antes de insertar.
- Todo acceso a base de datos debe ser mediante sentencias preparadas de PDO para evitar SQL Injection.
- Devuelve códigos HTTP correctos: 200 para listas (GET), 201 para creaciones exitosas (POST) y 422 para errores de validación.

ENTREGABLES ESPERADOS:
Genera el código limpio y comentado. Aprovecha la clase de base de datos existente. Explica al final cómo se estructuran los JSON requeridos para los endpoints POST.

---

**Cursor**

Implementando B-004: revisando la estructura existente y el esquema de las tablas de catálogos.


Implementando modelos, controladores base y rutas de catálogos.


Mejorando el parseo JSON para ignorar BOM (PowerShell lo añade al guardar archivos).


### A. Resumen breve de lo implementado

Se implementó **B-004**: listado (GET → 200) y creación (POST → 201) de los cuatro catálogos, con validación (422), PDO preparado, borrado lógico en consultas (`deleted_at IS NULL`) y verificación de `area_id` activo al crear centros de costo.

### B. Supuestos mínimos aplicados

- Los **modelos** actúan como capa de acceso a datos (sin servicios separados en este incremento).
- `BaseController` centraliza parseo JSON, sanitización y respuestas 422.
- Duplicados o FK inválidos en BD → **422** genérico (código SQL `23000`).
- El código de rol se normaliza a **mayúsculas** al guardar.

### C. Archivos creados o modificados

| Archivo | Rol |
|---------|-----|
| `app/Models/Model.php` | Base PDO + soft delete |
| `app/Models/Role.php`, `Area.php`, `CostCenter.php`, `AccountCatalog.php` | `all()` / `create()` |
| `app/Controllers/BaseController.php` | JSON, validación, errores |
| `app/Controllers/RolesController.php` | Orquestación roles |
| `app/Controllers/AreasController.php` | Orquestación áreas |
| `app/Controllers/CostCentersController.php` | Orquestación centros de costo |
| `app/Controllers/AccountCatalogsController.php` | Orquestación cuentas |
| `bootstrap/routes.php` | Rutas GET/POST |

### D. Rutas registradas

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/api/roles` | Listar roles |
| POST | `/api/roles` | Crear rol |
| GET | `/api/areas` | Listar áreas |
| POST | `/api/areas` | Crear área |
| GET | `/api/centros-costo` | Listar centros de costo |
| POST | `/api/centros-costo` | Crear centro de costo |
| GET | `/api/cuentas` | Listar cuentas contables |
| POST | `/api/cuentas` | Crear cuenta |

**POST** requieren header `X-CSRF-TOKEN` (B-003) y `Content-Type: application/json`.

### E. Integración

Flujo: `Router` → `Controller` → `Model` → `Database::getConnection()` con sentencias preparadas.

Orden recomendado al poblar datos: **áreas** → **centros de costo** (necesitan `area_id`) → roles y cuentas en paralelo.

### F. JSON requeridos para POST

**1. Roles — `POST /api/roles`**

```json
{
  "nombre": "Capturista",
  "codigo": "CAP"
}
```

| Campo | Tipo | Obligatorio | Máx. |
|-------|------|-------------|------|
| `nombre` | string | Sí | 50 |
| `codigo` | string | Sí | 30 |

---

**2. Áreas — `POST /api/areas`**

```json
{
  "nombre": "Tecnologías de la Información"
}
```

| Campo | Tipo | Obligatorio | Máx. |
|-------|------|-------------|------|
| `nombre` | string | Sí | 100 |

---

**3. Centros de costo — `POST /api/centros-costo`**

```json
{
  "area_id": 1,
  "codigo_contable": "CC-001",
  "nombre": "Desarrollo Backend"
}
```

| Campo | Tipo | Obligatorio | Notas |
|-------|------|-------------|-------|
| `area_id` | entero positivo | Sí | Debe existir en `areas` y no estar borrada lógicamente |
| `codigo_contable` | string | Sí | Máx. 20, único en BD |
| `nombre` | string | Sí | Máx. 100 |

---

**4. Cuentas contables — `POST /api/cuentas`**

```json
{
  "numero_cuenta": "601-01-002",
  "descripcion": "Viáticos y gastos de viaje"
}
```

| Campo | Tipo | Obligatorio | Máx. |
|-------|------|-------------|------|
| `numero_cuenta` | string | Sí | 30 |
| `descripcion` | string | Sí | 150 |

---

**Respuestas**

- **201** creación: `{ "data": { ...registro con id y timestamps... } }`
- **200** listado: `{ "data": [ ... ] }`
- **422** validación:

```json
{
  "error": true,
  "message": "Error de validación en los datos enviados.",
  "errors": {
    "area_id": ["El area_id es obligatorio y debe ser un entero positivo válido."]
  }
}
```

### G. Prueba rápida en Postman

1. `GET /api/csrf-token` (guardar cookie de sesión).
2. `POST /api/areas` con body JSON de área + header `X-CSRF-TOKEN`.
3. `POST /api/centros-costo` usando el `id` del área creada.
4. `GET /api/areas` y `GET /api/centros-costo` para verificar listados.

### H. Límites del incremento

- Solo **listar y crear** (sin PUT/DELETE).
- Sin autenticación RBAC (B-005).
- Sin paginación ni filtros en GET.

---

**User**

<system_notification>
The following task has finished. If you were already aware, ignore this notification and do not restate prior responses.

<task>
kind: shell
status: success
task_id: 929171
title: Start PHP built-in server and test GET /api/roles
output_path: C:\Users\Leonardo\.cursor\projects\c-xampp-htdocs-Proyecto\terminals\929171.txt
</task>
</system_notification>
<user_query>Briefly inform the user about the task result and perform any follow-up actions (if needed). If there's no follow-ups needed, don't explicitly say that.</user_query>

---

**Cursor**

**B-004 (Catálogos base)** quedó implementado: modelos, controladores y rutas para roles, áreas, centros de costo y cuentas contables.

La prueba con el servidor PHP embebido (`127.0.0.1:8765`) fue correcta: **`GET /api/roles`** respondió **200** con `{"data":[]}` (lista vacía porque aún no hay registros).

Para probar creaciones en Postman: primero `GET /api/csrf-token`, luego los `POST` con header `X-CSRF-TOKEN` y JSON según cada catálogo (crear área antes que centro de costo por el `area_id`).

---

**User**

Actúa como un desarrollador backend experto en seguridad usando Vanilla PHP 8+. Nuestro objetivo es implementar el incremento [B-005]: Gestión de Usuarios y Autenticación (Login).

CONTEXTO:
Estamos construyendo una API REST. Ya tenemos catálogos base (áreas, roles), conexión PDO, y un SecurityMiddleware que inicializa sesiones seguras y valida CSRF. Además, tenemos la clase `HashHelper` para contraseñas.

OBJETIVO PUNTUAL:
Crear los endpoints para registrar usuarios (hasheando su contraseña) y un sistema de Login/Logout basado en las sesiones seguras de PHP. Además, protegeremos los endpoints existentes (como los catálogos) para que requieran autenticación.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Models/User.php` (Modelo para tabla `usuarios` con un método para buscar por email).
2. `empresa-gastos-platform/api/app/Controllers/UsersController.php` (Controlador con el método `create` que use `HashHelper::hash` antes de guardar).
3. `empresa-gastos-platform/api/app/Controllers/AuthController.php` (Manejo de `login`, `logout` y `me`).
4. `empresa-gastos-platform/api/app/Middleware/AuthMiddleware.php` (Clase nueva para verificar que exista `$_SESSION['user_id']`. Si no existe, aborta con 401 Unauthorized).
5. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar las rutas y envolver los catálogos en el AuthMiddleware).

REGLAS DE NEGOCIO Y RESTRICCIONES:
- En la creación de un usuario (POST), el correo electrónico debe ser único en la BD. Las contraseñas deben cifrarse OBLIGATORIAMENTE usando `HashHelper` antes de insertarse en la BD.
- Nunca devuelvas el hash de la contraseña en los JSON de respuesta.
- Al hacer Login (POST), usa `HashHelper::verify`. Si es exitoso, regenera el ID de sesión para prevenir Session Fixation (`session_regenerate_id(true)`) y guarda el `id`, `area_id

---

**Cursor**

Implementando B-005: revisando la estructura actual y el esquema de `usuarios`.


Implementando autenticación, modelo de usuario y protección de rutas de catálogos.


### A. Resumen breve de lo implementado

Se implementó **B-005**: registro de usuarios con contraseña hasheada (`HashHelper`), login/logout/me con sesión segura (`session_regenerate_id` en login), `AuthMiddleware` (401 si no hay sesión) y catálogos protegidos con `AuthMiddleware::guard()`.

### B. Supuestos mínimos aplicados

- **`POST /api/users`** queda **público** para poder crear el primer usuario sin login previo (bootstrap).
- En sesión se guardan `user_id`, `area_id` y `rol_id`.
- Contraseña mínima: **8 caracteres**.
- Login fallido → **401** con mensaje genérico (sin revelar si el email existe).
- El hash **nunca** se incluye en respuestas JSON.

### C. Archivos creados o modificados

| Archivo | Rol |
|---------|-----|
| `app/Models/User.php` | `findByEmail`, `create`, `findPublicById` |
| `app/Controllers/UsersController.php` | Registro con `HashHelper::hash` |
| `app/Controllers/AuthController.php` | `login`, `logout`, `me` |
| `app/Middleware/AuthMiddleware.php` | Verificación de sesión + `guard()` |
| `bootstrap/routes.php` | Rutas auth + catálogos protegidos |
| `app/Models/Role.php` | `existsActive()` para validar `rol_id` |

### D. Rutas

| Método | Ruta | Auth | CSRF (mutativas) |
|--------|------|------|------------------|
| POST | `/api/users` | No | Sí |
| POST | `/api/auth/login` | No | Sí |
| POST | `/api/auth/logout` | Sí | Sí |
| GET | `/api/auth/me` | Sí | No |
| GET/POST | `/api/roles`, `/api/areas`, `/api/centros-costo`, `/api/cuentas` | **Sí** | POST sí |

### E. JSON para Postman

**1. Registrar usuario — `POST /api/users`**

```json
{
  "rol_id": 1,
  "area_id": 1,
  "nombre": "Juan Pérez",
  "email": "juan@empresa.local",
  "password": "MiClave123"
}
```

*(Requiere rol y área existentes en BD.)*

**2. Login — `POST /api/auth/login`**

```json
{
  "email": "juan@empresa.local",
  "password": "MiClave123"
}
```

Respuesta **200**: `{ "data": { "id", "rol_id", "area_id", "nombre", "email", ... } }` (sin `password`).

**3. Perfil — `GET /api/auth/me`** (con cookie de sesión)

**4. Logout — `POST /api/auth/logout`** (sesión + `X-CSRF-TOKEN`)

### F. Flujo de prueba en Postman

1. `GET /api/csrf-token` → guardar cookie + token.
2. Crear rol y área (si no existen) o insertarlos en MySQL.
3. `POST /api/users` con JSON + header `X-CSRF-TOKEN`.
4. `POST /api/auth/login` con mismas credenciales + CSRF.
5. `GET /api/roles` → **200** (autenticado).
6. Sin login, `GET /api/roles` → **401** `{ "message": "No autenticado." }`.

### G. Límites del incremento

- Sin RBAC por rol en rutas (solo “logueado o no”).
- Sin listado/edición de usuarios.
- Sin refresh token ni JWT (solo sesión PHP).

---

**User**

Actúa como un desarrollador Frontend experto en HTML5, Bootstrap 5 y Vanilla JavaScript. Nuestro objetivo es implementar la primera interfaz visual del sistema: la pantalla de Login.

CONTEXTO:
Tenemos una arquitectura separada. Nuestro backend (API REST en PHP) ya está completamente funcional en la carpeta `/api/public/`. Ahora necesitamos construir la interfaz gráfica en la carpeta `/web/` que consuma esos endpoints.

OBJETIVO PUNTUAL:
Crear una página de Login atractiva, moderna y corporativa usando Bootstrap, y escribir la lógica en JavaScript para capturar los datos del formulario, obtener el token CSRF de la API y enviar la petición de inicio de sesión.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/web/public/index.html` (Será la pantalla visual de Login).
2. `empresa-gastos-platform/web/src/scripts/pages/auth/login.page.js` (Lógica JavaScript para procesar el formulario).
3. `empresa-gastos-platform/web/src/styles/pages/login.css` (Estilos personalizados mínimos para centrar el formulario y darle aspecto corporativo).

REGLAS DE NEGOCIO Y TÉCNICAS:
- El formulario HTML debe contener campos para 'email' y 'password', y un botón de "Iniciar Sesión".
- En el archivo JS, intercepta el evento 'submit' del formulario (evitando la recarga con e.preventDefault()).
- Paso 1 del JS: Haz un fetch GET a `/api/public/api/csrf-token` para obtener el token de seguridad. (Asegúrate de incluir credentials: 'include' en el fetch para manejar las cookies de sesión).
- Paso 2 del JS: Haz un fetch POST a `/api/public/api/auth/login`, enviando el JSON con el email y password, e inyectando el token en el header `X-CSRF-TOKEN`.
- Manejo de Errores: Si la API devuelve un 401, muestra una alerta visual (puede ser un alert() simple o un div de Bootstrap) indicando "Credenciales incorrectas".
- Éxito: Si la API devuelve 200, muestra un mensaje de éxito y simula una redirección (ej. window.location.href = 'dashboard.html', aunque esa página aún no exista).

ENTREGABLES ESPERADOS:
Genera el código de los tres archivos solicitados. El diseño debe ser responsivo y verse profesional.

---

**Cursor**

Revisando la estructura de `/web` y las rutas de la API para alinear las URLs del login.


Creando la pantalla de login y la lógica que consume la API con CSRF y cookies de sesión.


### A. Resumen breve de lo implementado

Pantalla de **Login** corporativa con Bootstrap 5, estilos propios y JavaScript que obtiene el token CSRF, envía credenciales con `credentials: 'include'` y maneja errores 401 y éxito con redirección simulada.

### B. Supuestos mínimos aplicados

- La URL base de la API se resuelve automáticamente: desde `web/public/` apunta a `api/public` del mismo proyecto.
- Endpoints usados: `GET /api/csrf-token` y `POST /api/auth/login` (bajo `api/public`).
- Tras login exitoso, el usuario se guarda en `sessionStorage` y redirige a `dashboard.html` (aún no existe).

### C. Archivos creados

| Archivo | Descripción |
|---------|-------------|
| `web/public/index.html` | Vista de login |
| `web/src/scripts/pages/auth/login.page.js` | Lógica fetch + CSRF |
| `web/src/styles/pages/login.css` | Estilos corporativos |

### D. Flujo JavaScript

1. `submit` → `preventDefault()`
2. `GET {API_BASE}/api/csrf-token` con `credentials: 'include'`
3. `POST {API_BASE}/api/auth/login` con JSON `{ email, password }` y header `X-CSRF-TOKEN`
4. **401** → alerta Bootstrap: *"Credenciales incorrectas"*
5. **200** → mensaje de éxito → redirección a `dashboard.html` tras 1.2 s

### E. Cómo probar

1. Abre en el navegador:  
   `http://localhost/Proyecto/empresa-gastos-platform/web/public/index.html`
2. Asegúrate de tener un usuario registrado en la API (rol y área previos en BD).
3. Ingresa email y contraseña → **Iniciar Sesión**.

### F. Límites del incremento

- Sin `dashboard.html` ni rutas protegidas en frontend.
- Sin cliente API centralizado (`api.client.js` vendrá después).
- Requiere que web y API estén en el **mismo dominio** (cookies de sesión PHP con `path=/`).

**Nota:** Si el login falla por cookies, verifica que Apache sirva ambas carpetas bajo `localhost` y que el navegador no bloquee cookies de terceros (no aplica en mismo sitio).

---

**User**

Actúa como un desarrollador backend experto en seguridad y manejo de archivos en Vanilla PHP 8+. Nuestro objetivo es implementar el incremento [B-007]: Servicio de Carga y Almacenamiento Local de XML.

CONTEXTO:
Tenemos un sistema de gastos donde ya se pueden registrar gastos en estado "Borrador". Ahora necesitamos permitir que el usuario adjunte el archivo XML de su factura (CFDI).

OBJETIVO PUNTUAL:
Desarrollar el endpoint para subir archivos XML de forma segura y almacenarlos en el servidor, vinculándolos al gasto correspondiente.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Controllers/ExpenseDocumentsController.php` (Nuevo controlador para manejar la subida).
2. `empresa-gastos-platform/api/app/Services/FileService.php` (Servicio para validar extensiones, tipos MIME y mover el archivo a `/storage/uploads/xml/`).
3. `empresa-gastos-platform/bootstrap/routes.php` (Registrar ruta POST `/api/gastos/{id}/documento`).

REGLAS DE SEGURIDAD Y RESTRICCIONES:
- El directorio de destino debe estar fuera del root público (`/public`) para evitar acceso directo.
- Validación estricta: Solo permitir extensión `.xml` y tipo MIME `text/xml` o `application/xml`.
- Renombrar el archivo al guardarlo usando un nombre único (ej. `uniqid()` o `bin2hex(random_bytes(16))`) para evitar colisiones y ataques de sobreescritura.
- El controlador debe verificar que el gasto exista y pertenezca al usuario en sesión antes de permitir la subida.
- El sistema debe guardar la ruta del archivo en una tabla (o actualizar la tabla gastos si es 1 a 1 temporalmente).

ENTREGABLES ESPERADOS:
Genera el código limpio y seguro. Incluye manejo de errores si el archivo excede el tamaño permitido o si no es un XML válido. Explica cómo realizar la prueba en Postman enviando un archivo en el body (form-data).

---

**Cursor**

Explorando el código de gastos y el esquema de BD para implementar la carga segura de XML.


Implementando el servicio de archivos, modelos mínimos de gastos/CFDI, soporte de rutas con parámetros y el controlador de subida.


### A. Resumen breve de lo implementado

Endpoint **POST `/api/gastos/{id}/documento`** para subir XML CFDI de forma segura: validación de extensión, MIME y estructura XML, almacenamiento fuera de `/public` con nombre único, registro en `facturas_cfdi` y vínculo en `gastos.factura_cfdi_id`. Solo el capturista dueño del gasto en estado **BORRADOR** puede subir.

### B. Supuestos mínimos aplicados

- Ruta registrada en `api/bootstrap/routes.php` (no en la raíz del monorepo).
- Registro en `facturas_cfdi` con datos **pendientes de parseo** (B-008 completará UUID, RFC y montos).
- Campo form-data principal: **`documento`** (también acepta `archivo` o `file`).
- Modelos auxiliares `Expense` y `CfdiInvoice` necesarios para ownership y persistencia.

### C. Archivos creados o modificados

| Archivo | Acción |
|---------|--------|
| `api/app/Controllers/ExpenseDocumentsController.php` | Creado |
| `api/app/Services/FileService.php` | Creado |
| `api/app/Services/FileValidationException.php` | Creado |
| `api/app/Models/Expense.php` | Creado |
| `api/app/Models/CfdiInvoice.php` | Creado |
| `api/app/Core/Router.php` | Soporte `{id}` en rutas |
| `api/bootstrap/routes.php` | Ruta POST documento |
| `storage/uploads/xml/` | Directorio seguro + `.gitignore` |

### D. Seguridad aplicada

- Almacenamiento en `empresa-gastos-platform/storage/uploads/xml/` (fuera de `api/public`).
- Solo `.xml` con MIME `text/xml` o `application/xml`.
- Nombre único: `{32_hex}.xml` vía `random_bytes(16)`.
- Validación XML con `DOMDocument` + `LIBXML_NONET` (mitigación XXE).
- Verificación de propiedad: `usuario_capturista_id` = sesión activa.
- Rollback de BD y borrado del archivo si falla la transacción.

### E. Códigos HTTP

| Código | Situación |
|--------|-----------|
| **201** | XML guardado y vinculado |
| **401** | Sin sesión |
| **403** | CSRF inválido |
| **404** | Gasto inexistente o no es del usuario |
| **409** | Gasto ya tiene documento |
| **413** | Archivo excede tamaño del servidor |
| **422** | Extensión/MIME/XML inválido o gasto no en Borrador |

---

## Prueba en Postman (form-data)

**Pre-requisitos:** usuario autenticado, gasto en BD con `estatus_gastos.codigo = 'BORRADOR'` y `factura_cfdi_id` NULL.

**1. Obtener CSRF y sesión**
- `GET .../api/public/api/csrf-token` (guardar cookie `PHPSESSID`).

**2. Login** (si aún no hay sesión)
- `POST .../api/public/api/auth/login` con JSON + `X-CSRF-TOKEN`.

**3. Subir XML**
- Método: **POST**
- URL: `http://localhost/Proyecto/empresa-gastos-platform/api/public/api/gastos/1/documento`
- Headers:
  - `X-CSRF-TOKEN`: *(token del paso 1)*
- Body: **form-data** (no raw JSON)

| Key | Type | Value |
|-----|------|-------|
| `documento` | **File** | Selecciona tu archivo `.xml` CFDI |

**Respuesta 201 esperada:**
```json
{
  "data": {
    "gasto_id": 1,
    "factura_cfdi_id": 5,
    "stored_name": "a1b2c3....xml",
    "xml_file_path": "storage/uploads/xml/a1b2c3....xml"
  }
}
```

**Errores comunes:**
- Subir `.txt` renombrado → **422** (MIME/extensión).
- Archivo corrupto → **422** (“XML bien formado”).
- Gasto de otro usuario → **404**.
- Sin header CSRF → **403**.

### F. Límites del incremento

- Sin parseo CFDI (B-008).
- Sin reemplazo de documento ya vinculado.
- Sin endpoint de descarga del XML.
- Requiere que exista el gasto en borrador (B-006 o inserción manual en BD).

---

**User**

Actúa como un desarrollador backend experto en PHP 8+ y procesamiento de XML. Nuestro objetivo es implementar el incremento [B-008]: Parsing Automático de CFDI e Inserción.

CONTEXTO:
Acabamos de implementar el endpoint que sube archivos XML y registra la ruta en la base de datos. Ahora necesitamos leer ese archivo y extraer la información fiscal del Comprobante Fiscal Digital por Internet (CFDI).

OBJETIVO PUNTUAL:
Crear un servicio de parsing que extraiga los atributos de los nodos del XML (versiones 3.3 o 4.0 del CFDI de México) y actualice el registro correspondiente en la tabla `facturas_cfdi`. Este proceso debe ser invocado inmediatamente después de que el archivo se guarda exitosamente en el endpoint actual de subida (`ExpenseDocumentsController`).

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Services/XmlParserService.php` (Servicio dedicado a la extracción de datos).
2. `empresa-gastos-platform/api/app/Controllers/ExpenseDocumentsController.php` (Llamar al `XmlParserService` después de mover el archivo).
3. `empresa-gastos-platform/api/app/Models/CfdiInvoice.php` (Añadir un método para hacer UPDATE de los campos extraídos usando el ID).

REGLAS DE NEGOCIO Y RESTRICCIONES:
- Seguridad XXE: Asegúrate de utilizar `libxml_disable_entity_loader(true)` (si aplica a la versión de PHP) o configurar el parser de forma segura para evitar Inyección de Entidades Externas.
- Nodos a extraer del XML (namespace `cfdi` o `tfd`):
  1. `UUID` (del nodo TimbreFiscalDigital).
  2. `Rfc` (del nodo Emisor).
  3. `Rfc` (del nodo Receptor).
  4. `SubTotal` (del nodo Comprobante).
  5. `Total` (del nodo Comprobante).
  6. `TotalImpuestosTrasladados` (del nodo Impuestos, comúnmente usado para el IVA. Si no existe, asume 0).
- Todos los montos extraídos deben guardarse en la BD respetando el formato `DECIMAL(12,4)`.
- Si el XML carece de UUID o de los nodos básicos de un CFDI, lanza una excepción de validación (ej. HTTP 422) indicando que "El archivo no es un CFDI válido", y revierte la transacción en la BD (eliminando también el archivo físico).

ENTREGABLES ESPERADOS:
Genera el código de las clases. El controlador ahora debe devolver en su JSON (HTTP 201) no solo la ruta del archivo, sino también los datos fiscales extraídos (UUID, RFC, montos).

---

**Cursor**

Revisando el flujo de subida y el modelo CFDI para integrar el parseo automático.


### A. Resumen breve de lo implementado

Tras guardar el XML, el sistema **parsea el CFDI 3.3/4.0** de forma segura, actualiza `facturas_cfdi` con los datos fiscales y devuelve **HTTP 201** con UUID, RFCs y montos. Si el CFDI es inválido, revierte la transacción y elimina el archivo físico.

### B. Supuestos mínimos aplicados

- Nodos localizados por `local-name()` (compatible con namespaces `cfdi` y `tfd`).
- `emisor_razon_social` se toma del atributo `Nombre` del Emisor (requerido en BD).
- `fecha_emision` se extrae del atributo `Fecha` del Comprobante (parte de fecha).
- Si no hay `TotalImpuestosTrasladados`, el IVA se guarda como `0.0000`.

### C. Archivos creados o modificados

| Archivo | Cambio |
|---------|--------|
| `app/Services/XmlParserService.php` | Creado — parseo seguro CFDI |
| `app/Services/XmlParseException.php` | Creado — error 422 |
| `app/Models/CfdiInvoice.php` | `updateFromParsedData()`, `findPublicById()` |
| `app/Controllers/ExpenseDocumentsController.php` | Integra parseo post-carga + rollback |

### D. Flujo en el controlador

1. Guardar archivo (`FileService`)
2. Iniciar transacción → registro pendiente → vincular gasto
3. **`XmlParserService::parseCfdiFile()`**
4. **`CfdiInvoice::updateFromParsedData()`**
5. Commit → respuesta 201 con bloque `cfdi`

Si falla el parseo o hay UUID duplicado: **rollback**, borrado del archivo, **422** o **409**.

### E. Seguridad XXE

- `libxml_disable_entity_loader(true)` cuando está disponible
- `libxml_set_external_entity_loader()` → `null`
- Carga con `LIBXML_NONET`
- Consultas XPath por `local-name()` sin resolver entidades externas

### F. Respuesta 201 esperada

```json
{
  "data": {
    "gasto_id": 1,
    "factura_cfdi_id": 5,
    "stored_name": "abc123....xml",
    "xml_file_path": "storage/uploads/xml/abc123....xml",
    "cfdi": {
      "uuid": "F7A8B9C0-D1E2-4F3A-8B7C-9D0E1F2A3B4C",
      "emisor_rfc": "AAA010101AAA",
      "emisor_razon_social": "PROVEEDOR DEMO SA DE CV",
      "receptor_rfc": "BBB020202BBB",
      "monto_subtotal": "1000.0000",
      "monto_iva": "160.0000",
      "monto_total": "1160.0000",
      "fecha_emision": "2024-06-15"
    }
  }
}
```

### G. Prueba en Postman

Mismo flujo que B-007: sesión + CSRF → **POST** `form-data` con campo `documento` (archivo `.xml` CFDI real timbrado).

- CFDI válido → **201** con datos fiscales en `cfdi`.
- XML sin timbre/UUID → **422**: *"El archivo no es un CFDI válido"* (sin registro en BD ni archivo en disco).
- UUID ya registrado → **409**.

### H. Límites del incremento

- No actualiza montos del gasto (`gastos.monto_total`) — puede hacerse en B-009+.
- No valida cancelación ante el SAT.
- No soporta complementos de pago ni CFDI sin timbre (se exige `TimbreFiscalDigital`).

---

**User**

Actúa como un desarrollador backend experto y analista financiero en PHP 8+. Nuestro objetivo es implementar el incremento [B-009]: Motor de Presupuesto Mensual y Bloqueo.

CONTEXTO:
Tenemos gastos en estado 'BORRADOR'. Para que el Jefe de Área pueda revisarlos, el capturista debe "enviarlos" (cambiar su estado a 'PENDIENTE DE APROBACIÓN'). Sin embargo, antes de permitir este cambio de estado, el sistema debe garantizar que el centro de costos tiene presupuesto suficiente en el mes corriente.

OBJETIVO PUNTUAL:
Crear la lógica financiera que valide en tiempo real si el centro de costos tiene fondos. Si el gasto propuesto supera el saldo disponible (Presupuesto Asignado - Gastos Acumulados del mes), se rechaza el cambio de estado.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Services/BudgetService.php` (Servicio dedicado a la evaluación presupuestal).
2. `empresa-gastos-platform/api/app/Models/Budget.php` (Modelo para la tabla `presupuestos`).
3. `empresa-gastos-platform/api/app/Controllers/ExpensesController.php` (Agregar el método `submitForApproval`).
4. `empresa-gastos-platform/api/bootstrap/routes.php` (Ruta PUT `/api/gastos/{id}/enviar`).

REGLAS DE NEGOCIO FINANCIERAS:
- Cálculo del Disponible: Busca el registro en `presupuestos` para el `centro_costos_id` en el `periodo_mes` y `periodo_anio` correspondientes a la `fecha_gasto`. 
- Si no existe un presupuesto asignado para ese mes/año, lanza una excepción (HTTP 422) indicando "No hay presupuesto asignado para este centro de costos en el periodo actual."
- Sumatoria de consumos: Suma el `monto_total` de todos los gastos que pertenezcan a ese centro de costos, en el mismo mes y año, cuyo `estatus_gasto_id` equivalga a 'PENDIENTE DE APROBACIÓN' o 'APROBADO'. (Utiliza obligatoriamente una consulta SQL SUM() optimizada con `idx_gastos_presupuesto`).
- Evaluación: Si `(Sumatoria_Consumida + Gasto_Nuevo) > Monto_Presupuesto_Asignado`, aborta con un error HTTP 422 indicando "Presupuesto insuficiente. Fondos disponibles: $X".
- Cambio de Estado: Si hay fondos, actualiza el `estatus_gasto_id` del gasto a 'PENDIENTE DE APROBACIÓN'.

ENTREGABLES ESPERADOS:
Código del servicio, controlador y rutas, usando sentencias preparadas de PDO. El servicio debe aislarse bien para poder ser reutilizado en futuros reportes.

---

**Cursor**

Revisando modelos y esquema existentes para implementar el motor de presupuesto.


Implementando el motor de presupuesto, extendiendo el modelo de gastos y creando el controlador con la ruta PUT.


### A. Resumen breve de lo implementado

Motor presupuestal **B-009**: al enviar un gasto (`PUT /api/gastos/{id}/enviar`), el sistema valida presupuesto del mes de `fecha_gasto`, suma consumos **PENDIENTE + APROBADO** con `SUM()` y bloquea si no hay fondos; si pasa, cambia el estatus a **PENDIENTE**.

### B. Supuestos mínimos aplicados

- Código de estatus en BD: `BORRADOR`, `PENDIENTE`, `APROBADO` (nombre legible: *Pendiente de Aprobación*).
- El gasto en **BORRADOR** no entra en la suma consumida (solo al enviarse se valida contra el total propuesto).
- Periodo derivado de `fecha_gasto` (mes/año calendario).
- Modelo auxiliar `ExpenseStatus` para resolver IDs por código.

### C. Archivos creados o modificados

| Archivo | Rol |
|---------|-----|
| `app/Services/BudgetService.php` | Evaluación y snapshot presupuestal |
| `app/Services/BudgetValidationException.php` | Errores 422 |
| `app/Models/Budget.php` | Consulta `presupuestos` |
| `app/Models/ExpenseStatus.php` | IDs de estatus |
| `app/Models/Expense.php` | `sumCommittedAmountForPeriod()`, `updateStatus()` |
| `app/Controllers/ExpensesController.php` | `submitForApproval()` |
| `bootstrap/routes.php` | `PUT /api/gastos/{id}/enviar` |

### D. Lógica financiera

```
Disponible = monto_assigned - SUM(gastos PENDIENTE|APROBADO del mismo centro/mes/año)

Si (consumido + monto_gasto) > asignado → 422 "Presupuesto insuficiente. Fondos disponibles: $X"
Si no hay fila en presupuestos → 422 "No hay presupuesto asignado..."
Si OK → estatus → PENDIENTE
```

Consulta optimizada para `idx_gastos_presupuesto`:

```sql
WHERE centro_costos_id = ?
  AND estatus_gasto_id IN (pendiente_id, aprobado_id)
  AND fecha_gasto BETWEEN inicio_mes AND fin_mes
```

### E. Respuestas HTTP

**200** — envío exitoso:
```json
{
  "data": { "id": 1, "estatus_codigo": "PENDIENTE", "monto_total": "1500.0000", ... },
  "presupuesto": {
    "centro_costos_id": 1,
    "periodo_mes": 6,
    "periodo_anio": 2026,
    "assigned": "10000.0000",
    "consumed": "3000.0000",
    "available": "7000.0000"
  }
}
```

**422** — sin presupuesto o fondos insuficientes (incluye bloque `presupuesto`).

### F. Prueba en Postman

1. Sesión + `GET /api/csrf-token`
2. Gasto en **BORRADOR** con `fecha_gasto` del mes a evaluar
3. Presupuesto en `presupuestos` para ese `centro_costos_id` + mes/año
4. **PUT** `.../api/public/api/gastos/{id}/enviar`
   - Header: `X-CSRF-TOKEN`
   - Sin body

### G. Reutilización en reportes

`BudgetService::buildBudgetSnapshot($expense)` devuelve `assigned`, `consumed` y `available` sin mutar estado — listo para reportes futuros (B-013).

### H. Límites del incremento

- Sin bloqueo pesimista/transaccional avanzado (lectura + update simple).
- Sin auditoría en bitácora (B-012).
- Requiere catálogo `estatus_gastos` y presupuestos poblados en BD.

---

**User**

Actúa como un desarrollador backend experto en seguridad y reglas de negocio en PHP 8+. Nuestro objetivo es implementar el incremento [B-010]: Bandeja y Autorización por Jefe de Área.

CONTEXTO:
Tenemos gastos en estado 'PENDIENTE' que acaban de pasar la validación de presupuesto. Necesitamos crear la lógica para que los usuarios con rol de Jefe de Área puedan revisar, aprobar o rechazar los gastos de su propio centro de costos/área.

OBJETIVO PUNTUAL:
Desarrollar los endpoints que permitan al Jefe de Área listar las solicitudes pendientes de su área, aprobarlas (cambiando estado a 'APROBADO') o rechazarlas (cambiando estado a 'RECHAZADO', exigiendo un comentario).

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Controllers/ExpenseApprovalsController.php` (Nuevo controlador para manejar las aprobaciones del Jefe).
2. `empresa-gastos-platform/api/app/Models/Expense.php` (Añadir métodos para listar gastos por `area_id` y estado, y actualizar el rechazo/aprobación).
3. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar rutas: `GET /api/aprobaciones/jefe`, `PUT /api/aprobaciones/jefe/{id}/aprobar`, `PUT /api/aprobaciones/jefe/{id}/rechazar`).

REGLAS DE NEGOCIO Y SEGURIDAD:
- Autorización estricta: Los 3 endpoints deben validar que el usuario en sesión (`$_SESSION['rol_id']`) corresponda al código de rol 'JEF' (Jefe de Área). Si no, devuelve 403 Forbidden.
- Aislamiento de datos: El endpoint GET solo debe listar los gastos en estado 'PENDIENTE' que pertenezcan a los centros de costos asociados al `area_id` del Jefe en sesión.
- Validaciones al Aprobar/Rechazar:
  1. El gasto debe existir, estar en estado 'PENDIENTE' y pertenecer al `area_id` del Jefe. Si no, devuelve 404 o 403.
  2. Un jefe NO puede aprobar un gasto donde él mismo sea el `usuario_capturista_id` (Prevención de auto-aprobación).
  3. Si se APRUEBA, actualiza el `estatus_gasto_id` a 'APROBADO' y guarda el ID del jefe en `usuario_aprobador_jefe_id`.
  4. Si se RECHAZA, el payload JSON debe incluir obligatoriamente el campo `comentarios_rechazo`. Actualiza el estado a 'RECHAZADO' y guarda el comentario.
  
ENTREGABLES ESPERADOS:
Código limpio usando PDO preparado. Respuestas JSON estructuradas (200 OK).

Actúa como un desarrollador backend experto en seguridad y reglas de negocio en PHP 8+. Nuestro objetivo es implementar el incremento [B-010]: Bandeja y Autorización por Jefe de Área.

CONTEXTO:
Tenemos gastos en estado 'PENDIENTE' que acaban de pasar la validación de presupuesto. Necesitamos crear la lógica para que los usuarios con rol de Jefe de Área puedan revisar, aprobar o rechazar los gastos de su propio centro de costos/área.

OBJETIVO PUNTUAL:
Desarrollar los endpoints que permitan al Jefe de Área listar las solicitudes pendientes de su área, aprobarlas (cambiando estado a 'APROBADO') o rechazarlas (cambiando estado a 'RECHAZADO', exigiendo un comentario).

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Controllers/ExpenseApprovalsController.php` (Nuevo controlador para manejar las aprobaciones del Jefe).
2. `empresa-gastos-platform/api/app/Models/Expense.php` (Añadir métodos para listar gastos por `area_id` y estado, y actualizar el rechazo/aprobación).
3. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar rutas: `GET /api/aprobaciones/jefe`, `PUT /api/aprobaciones/jefe/{id}/aprobar`, `PUT /api/aprobaciones/jefe/{id}/rechazar`).

REGLAS DE NEGOCIO Y SEGURIDAD:
- Autorización estricta: Los 3 endpoints deben validar que el usuario en sesión (`$_SESSION['rol_id']`) corresponda al código de rol 'JEF' (Jefe de Área). Si no, devuelve 403 Forbidden.
- Aislamiento de datos: El endpoint GET solo debe listar los gastos en estado 'PENDIENTE' que pertenezcan a los centros de costos asociados al `area_id` del Jefe en sesión.
- Validaciones al Aprobar/Rechazar:
  1. El gasto debe existir, estar en estado 'PENDIENTE' y pertenecer al `area_id` del Jefe. Si no, devuelve 404 o 403.
  2. Un jefe NO puede aprobar un gasto donde él mismo sea el `usuario_capturista_id` (Prevención de auto-aprobación).
  3. Si se APRUEBA, actualiza el `estatus_gasto_id` a 'APROBADO' y guarda el ID del jefe en `usuario_aprobador_jefe_id`.
  4. Si se RECHAZA, el payload JSON debe incluir obligatoriamente el campo `comentarios_rechazo`. Actualiza el estado a 'RECHAZADO' y guarda el comentario.
  
ENTREGABLES ESPERADOS:
Código limpio usando PDO preparado. Respuestas JSON estructuradas (200 OK).

---

**Cursor**

Revisando modelos y sesión existentes para implementar la bandeja del Jefe de Área.


Implementando el controlador de aprobaciones, extendiendo el modelo `Expense` y registrando las rutas.


### A. Resumen breve de lo implementado

Bandeja del **Jefe de Área (rol `JEF`)**: listar gastos **PENDIENTE** de su área, aprobar (`APROBADO` + `usuario_aprobador_jefe_id`) o rechazar (`RECHAZADO` + comentario obligatorio), con bloqueo de auto-aprobación.

### B. Supuestos mínimos aplicados

- El `area_id` del jefe viene de `$_SESSION['area_id']` (login B-005).
- Los gastos se filtran por centros de costo con `centro_costos.area_id` = área del jefe.
- Códigos de estatus: `PENDIENTE`, `APROBADO`, `RECHAZADO`.
- Auto-aprobación bloqueada también en **rechazo** (misma regla de seguridad).

### C. Archivos creados o modificados

| Archivo | Cambio |
|---------|--------|
| `app/Controllers/ExpenseApprovalsController.php` | Creado |
| `app/Models/Expense.php` | Listado, búsqueda por área, approve/reject |
| `app/Models/Role.php` | `findCodigoById()` |
| `bootstrap/routes.php` | 3 rutas de aprobación |

### D. Rutas

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/api/aprobaciones/jefe` | Bandeja pendientes del área |
| PUT | `/api/aprobaciones/jefe/{id}/aprobar` | Aprobar gasto |
| PUT | `/api/aprobaciones/jefe/{id}/rechazar` | Rechazar con comentario |

Todas requieren **sesión autenticada**, **rol `JEF`** y CSRF en PUT.

### E. Respuestas

**GET 200:**
```json
{
  "data": [
    {
      "id": 1,
      "monto_total": "1500.0000",
      "concepto_descripcion": "...",
      "capturista_nombre": "...",
      "centro_costos_nombre": "...",
      "estatus_codigo": "PENDIENTE"
    }
  ]
}
```

**PUT aprobar 200:**
```json
{
  "data": { "estatus_codigo": "APROBADO", "usuario_aprobador_jefe_id": 2, ... },
  "message": "Gasto aprobado correctamente."
}
```

**PUT rechazar** — body JSON obligatorio:
```json
{
  "comentarios_rechazo": "Falta justificación del viático."
}
```

### F. Códigos de error

| Código | Caso |
|--------|------|
| **403** | Rol distinto de `JEF` o auto-aprobación/rechazo propio |
| **404** | Gasto inexistente, no pendiente o fuera del área |
| **422** | ID inválido o falta `comentarios_rechazo` |

### G. Prueba en Postman

1. Usuario con rol **`JEF`** (código en tabla `roles`) y `area_id` asignado.
2. `GET /api/csrf-token` → login como jefe.
3. **GET** `/api/aprobaciones/jefe` → ver bandeja.
4. **PUT** `/api/aprobaciones/jefe/1/aprobar` + header `X-CSRF-TOKEN`.
5. **PUT** `/api/aprobaciones/jefe/2/rechazar` + body JSON con comentario.

### H. Límites del incremento

- Sin notificaciones ni bitácora (B-012).
- Sin validación CxP (B-011).
- El jefe solo ve gastos de **su** `area_id` en sesión, no de toda la empresa.

---

**User**

Actúa como un desarrollador backend experto en reglas de negocio en PHP 8+. Nuestro objetivo es implementar el incremento [B-011]: Validación Final por Cuentas por Pagar (CxP).

CONTEXTO:
Los gastos ya fueron aprobados por los Jefes de Área y ahora tienen el estado 'APROBADO'. El equipo de Cuentas por Pagar necesita revisar estos gastos a nivel global (de todas las áreas) y asignarles su clasificación contable final y folio.

OBJETIVO PUNTUAL:
Desarrollar los endpoints que permitan al analista de Cuentas por Pagar (Rol 'CXP') listar todos los gastos listos para pago, y un endpoint para procesar el cierre del gasto.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Controllers/AccountsPayableController.php` (Nuevo controlador para CxP).
2. `empresa-gastos-platform/api/app/Models/Expense.php` (Añadir métodos para listar gastos globales aprobados y actualizar los datos contables).
3. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar rutas: `GET /api/cxp/gastos` y `PUT /api/cxp/gastos/{id}/procesar`).

REGLAS DE NEGOCIO Y SEGURIDAD:
- Autorización estricta: Ambos endpoints deben validar que el usuario en sesión (`$_SESSION['rol_id']`) corresponda al código de rol 'CXP'. Si no, devuelve 403.
- Visibilidad global: A diferencia de los Jefes, CxP debe poder ver los gastos de TODAS las áreas, pero EXCLUSIVAMENTE aquellos cuyo estatus sea 'APROBADO' por un Jefe.
- Validación de Procesamiento (PUT):
  1. El payload JSON debe requerir obligatoriamente `cuenta_contable_id` y `folio_contable_interno`.
  2. Verifica que la `cuenta_contable_id` exista y no esté borrada lógicamente.
  3. Actualiza el registro del gasto asignando esos campos y guardando el ID del analista en sesión en el campo `usuario_aprobador_cxp_id`.

ENTREGABLES ESPERADOS:
Código limpio usando PDO preparado. Respuestas JSON estructuradas. Asegúrate de retornar códigos HTTP 403, 404 y 422 donde corresponda.

---

**Cursor**

Revisando modelos existentes para implementar la bandeja de Cuentas por Pagar.


### A. Resumen breve de lo implementado

Bandeja global de **Cuentas por Pagar (rol `CXP`)**: listar gastos **APROBADO** por jefe pendientes de cierre y procesarlos asignando cuenta contable, folio interno y `usuario_aprobador_cxp_id`.

### B. Supuestos mínimos aplicados

- **Listado:** `APROBADO` + `usuario_aprobador_jefe_id` definido + `folio_contable_interno IS NULL` (pendientes de CxP).
- **Procesar:** el estatus permanece **APROBADO**; el cierre se refleja en folio y aprobador CxP.
- Visibilidad **global** (todas las áreas), sin filtro por `area_id` de sesión.

### C. Archivos creados o modificados

| Archivo | Cambio |
|---------|--------|
| `app/Controllers/AccountsPayableController.php` | Creado |
| `app/Models/Expense.php` | Listado global, búsqueda y `processByAccountsPayable()` |
| `app/Models/AccountCatalog.php` | `existsActive()` |
| `bootstrap/routes.php` | Rutas CxP |

### D. Rutas

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/api/cxp/gastos` | Bandeja global aprobados pendientes |
| PUT | `/api/cxp/gastos/{id}/procesar` | Cierre contable |

Requieren sesión, rol **`CXP`** y CSRF en PUT.

### E. Procesamiento (PUT)

**Body JSON obligatorio:**
```json
{
  "cuenta_contable_id": 3,
  "folio_contable_interno": "POL-2026-0042"
}
```

**200 OK:**
```json
{
  "data": {
    "id": 1,
    "estatus_codigo": "APROBADO",
    "cuenta_contable_id": 3,
    "folio_contable_interno": "POL-2026-0042",
    "usuario_aprobador_cxp_id": 5,
    ...
  },
  "message": "Gasto procesado correctamente por Cuentas por Pagar."
}
```

### F. Códigos HTTP

| Código | Caso |
|--------|------|
| **403** | Rol distinto de `CXP` |
| **404** | Gasto inexistente o no aprobado por jefe |
| **422** | Validación, cuenta inactiva o gasto ya procesado |

### G. Prueba en Postman

1. Login con usuario rol **`CXP`** (`codigo` en tabla `roles`).
2. **GET** `/api/cxp/gastos` → bandeja global.
3. **PUT** `/api/cxp/gastos/{id}/procesar` con `X-CSRF-TOKEN` y JSON de arriba.

### H. Límites del incremento

- Sin nuevo estatus post-CxP (p. ej. “Pagado”).
- Sin bitácora de auditoría (B-012).
- Gastos procesados salen de la bandeja al tener `folio_contable_interno` asignado.

---

**User**

Actúa como un desarrollador backend experto en seguridad y trazabilidad en PHP 8+. Nuestro objetivo es implementar el incremento [B-012]: Auditoría (Inserción en Bitácora Inmutable).

CONTEXTO:
El ciclo de vida del gasto ya está completo (Borrador -> Pendiente -> Aprobado -> Procesado por CxP). Necesitamos registrar un rastro de auditoría inmutable cada vez que ocurra una modificación en el gasto para cumplir con regulaciones internas.

OBJETIVO PUNTUAL:
Desarrollar un servicio de auditoría y conectarlo a los controladores existentes que mutan el gasto, para que registren automáticamente el evento en la tabla `bitacora_auditoria`.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Services/AuditService.php` (Servicio nuevo dedicado a registrar los logs).
2. `empresa-gastos-platform/api/app/Models/AuditLog.php` (Modelo para la tabla `bitacora_auditoria`).
3. `empresa-gastos-platform/api/app/Controllers/ExpensesController.php` (Llamar al AuditService al crear un gasto y al enviarlo a revisión).
4. `empresa-gastos-platform/api/app/Controllers/ExpenseApprovalsController.php` (Llamar al AuditService al aprobar/rechazar).
5. `empresa-gastos-platform/api/app/Controllers/AccountsPayableController.php` (Llamar al AuditService al procesar).

REGLAS DE NEGOCIO Y SEGURIDAD:
- El `AuditService` debe recibir como parámetros: el ID del gasto, la acción realizada (ej. 'CREAR_BORRADOR', 'ENVIAR_APROBACION', 'APROBAR_JEFE', 'RECHAZAR_JEFE', 'PROCESAR_CXP'), un array JSON con los valores anteriores (si aplica) y un array JSON con los valores nuevos.
- El servicio DEBE extraer automáticamente la IP del cliente (`$_SERVER['REMOTE_ADDR']`) y el `usuario_id` del usuario activo en la sesión (`$_SESSION['user_id']`).
- La inserción debe hacerse mediante una sentencia preparada usando la conexión PDO base.
- Nunca hagas `UPDATE` ni `DELETE` en el modelo `AuditLog`, solo métodos de creación.

ENTREGABLES ESPERADOS:
Genera el código de las nuevas clases y muestra cómo modificaste uno de los controladores (por ejemplo, el de Aprobación por Jefe) para inyectar este log justo después del commit exitoso a la base de datos.

---

**Cursor**

Revisando los controladores que mutan gastos para integrar el servicio de auditoría.


Creando `AuditService` y `AuditLog`, e integrándolos en los controladores que mutan gastos.


### A. Resumen breve de lo implementado

Servicio de **auditoría inmutable** (`AuditService` + `AuditLog`) conectado a los controladores que mutan gastos. Cada operación exitosa inserta un registro en `bitacora_auditoria` con acción, JSON anterior/nuevo, `usuario_id` de sesión e IP del cliente.

### B. Supuestos mínimos aplicados

- El log se escribe **después** de la actualización exitosa en BD (no hay transacción unificada en todos los flujos).
- `CREAR_BORRADOR` queda en `auditExpenseCreation()` listo para invocarse cuando exista el endpoint `store` (B-006 aún no implementado).
- `valores_anteriores_json` es `NULL` en creaciones.

### C. Archivos creados o modificados

| Archivo | Cambio |
|---------|--------|
| `app/Services/AuditService.php` | Creado |
| `app/Models/AuditLog.php` | Creado (solo `create`) |
| `app/Controllers/ExpensesController.php` | `ENVIAR_APROBACION` + helper creación |
| `app/Controllers/ExpenseApprovalsController.php` | `APROBAR_JEFE` / `RECHAZAR_JEFE` |
| `app/Controllers/AccountsPayableController.php` | `PROCESAR_CXP` |

### D. Acciones registradas

| Constante | Evento |
|-----------|--------|
| `CREAR_BORRADOR` | Alta de gasto |
| `ENVIAR_APROBACION` | Borrador → Pendiente |
| `APROBAR_JEFE` | Pendiente → Aprobado |
| `RECHAZAR_JEFE` | Pendiente → Rechazado |
| `PROCESAR_CXP` | Cierre contable CxP |

### E. Ejemplo: aprobación del Jefe (tras UPDATE exitoso)

```84:108:empresa-gastos-platform/api/app/Controllers/ExpenseApprovalsController.php
        $previousSnapshot = $this->auditService->snapshotExpense(
            $this->expenseModel->findPublicById($expenseId)
        );

        $this->expenseModel->approveByAreaManager($expenseId, $aprobadoId, $managerUserId);

        $updatedExpense = $this->expenseModel->findPublicById($expenseId);

        // ... validación ...

        $this->auditService->log(
            $expenseId,
            AuditService::ACTION_APPROVE_MANAGER,
            $previousSnapshot,
            $this->auditService->snapshotExpense($updatedExpense) ?? []
        );
```

### F. `AuditService::log()` — responsabilidades

- `usuario_id` desde `$_SESSION['user_id']`
- `ip_address` desde `$_SERVER['REMOTE_ADDR']`
- Serialización JSON con `JSON_THROW_ON_ERROR`
- Inserción vía PDO preparado en `AuditLog::create()`

### G. Verificación en BD

Tras aprobar un gasto:

```sql
SELECT accion_realizada, valores_anteriores_json, valores_nuevos_json, ip_address
FROM bitacora_auditoria
WHERE gasto_id = 1
ORDER BY id DESC;
```

Deberías ver `APROBAR_JEFE` con el cambio de `estatus_codigo` de `PENDIENTE` a `APROBADO`.

### H. Límites del incremento

- Sin endpoint de consulta de bitácora (solo escritura).
- Sin privilegios MySQL que bloqueen `UPDATE`/`DELETE` a nivel BD.
- Subida de XML (`ExpenseDocumentsController`) no audita en este incremento.
- Para `CREAR_BORRADOR`, llamar `$expensesController->auditExpenseCreation($created)` al implementar el `store` de gastos.

---

**User**

Actúa como un desarrollador backend experto en Vanilla PHP 8+ y optimización de datos. Nuestro objetivo es implementar el incremento final [B-013]: Reportes: Exportación CSV y Filtros.

CONTEXTO:
El sistema transaccional y de auditoría ya está completo. Ahora necesitamos un endpoint protegido que permita consultar el historial de gastos utilizando filtros dinámicos y exportar los resultados en formato CSV nativo.

OBJETIVO PUNTUAL:
Crear un motor de reportes que reciba parámetros GET, ejecute una consulta dinámica a la base de datos (con JOINs a catálogos para traer nombres legibles) y devuelva el resultado como un archivo descargable.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/api/app/Controllers/ReportsController.php` (Nuevo controlador).
2. `empresa-gastos-platform/api/app/Models/Expense.php` (Añadir método `getFilteredExpenses($filters)` que construya dinámicamente la consulta SQL con JOINs).
3. `empresa-gastos-platform/api/bootstrap/routes.php` (Registrar ruta `GET /api/reportes/gastos` protegida por `AuthMiddleware`).

REGLAS DE NEGOCIO Y TÉCNICAS:
- Parámetros GET opcionales permitidos: `fecha_inicio`, `fecha_fin`, `centro_costos_id`, `estatus_codigo`.
- El modelo debe sanitizar y aplicar estos filtros a la consulta SQL usando sentencias preparadas. 
- La consulta debe traer columnas legibles: ID Gasto, Fecha Gasto, Nombre Capturista, Nombre Área, Nombre Centro Costos, Código Cuenta, Monto Total, Concepto, Estatus y UUID de Factura (si tiene).
- En el controlador, NO devuelvas un JSON. Debes configurar los headers HTTP para forzar la descarga de un CSV:
  `header('Content-Type: text/csv; charset=utf-8');`
  `header('Content-Disposition: attachment; filename=reporte_gastos_' . date('Ymd') . '.csv');`
- Utiliza `fopen('php://output', 'w')` y `fputcsv()` para imprimir directamente las cabeceras y los registros, ahorrando memoria en el servidor.
- Asegúrate de imprimir un BOM de UTF-8 al inicio del archivo (`echo "\xEF\xBB\xBF";`) para que Excel lea los acentos correctamente.

ENTREGABLES ESPERADOS:
Código del controlador, la consulta dinámica en el modelo y el registro de la ruta. Explica cómo invocar este endpoint directamente en el navegador.

---

**Cursor**

Implementando el motor de reportes CSV: revisando el modelo `Expense` y las rutas existentes.


### A. Resumen breve de lo implementado

Endpoint **GET `/api/reportes/gastos`** protegido por autenticación: aplica filtros opcionales vía query string, consulta gastos con JOINs a catálogos y devuelve un **CSV descargable** con BOM UTF-8 para Excel.

### B. Supuestos mínimos aplicados

- Cualquier usuario **autenticado** puede exportar (sin RBAC adicional en este incremento).
- Filtros inválidos devuelven **422 JSON** antes de enviar headers CSV.
- Sin gastos → CSV solo con encabezados.

### C. Archivos creados o modificados

| Archivo | Cambio |
|---------|--------|
| `app/Controllers/ReportsController.php` | Creado |
| `app/Models/Expense.php` | `getFilteredExpenses($filters)` |
| `bootstrap/routes.php` | Ruta GET reportes |

### D. Parámetros GET opcionales

| Parámetro | Formato | Ejemplo |
|-----------|---------|---------|
| `fecha_inicio` | `YYYY-MM-DD` | `2026-01-01` |
| `fecha_fin` | `YYYY-MM-DD` | `2026-06-30` |
| `centro_costos_id` | entero positivo | `2` |
| `estatus_codigo` | código catálogo | `APROBADO` |

### E. Columnas del CSV

ID Gasto, Fecha Gasto, Nombre Capturista, Nombre Área, Nombre Centro Costos, Código Cuenta, Monto Total, Concepto, Estatus, UUID Factura.

### F. Cómo invocarlo en el navegador

1. Inicia sesión en la API (cookie `PHPSESSID` activa).
2. Abre una URL como:

```
http://localhost/Proyecto/empresa-gastos-platform/api/public/api/reportes/gastos
```

**Con filtros:**

```
http://localhost/Proyecto/empresa-gastos-platform/api/public/api/reportes/gastos?fecha_inicio=2026-01-01&fecha_fin=2026-06-30&estatus_codigo=APROBADO&centro_costos_id=1
```

El navegador descargará `reporte_gastos_YYYYMMDD.csv`.

**Nota:** Si no hay sesión activa, recibirás JSON **401** en lugar del CSV.

### G. Postman

- Método: **GET**
- URL: misma base + query params
- Pestaña **Cookies**: incluir `PHPSESSID` del login
- **Send and Download** para guardar el archivo

### H. Límites del incremento

- Sin paginación (exporta todo el resultado del filtro).
- Sin exportación Excel nativa (solo CSV).
- Sin restricción por rol/área en el reporte global.

---

**User**

Actúa como un desarrollador Frontend experto en HTML5, Bootstrap 5 y Vanilla JavaScript. Nuestro objetivo es implementar el incremento [F-002]: Dashboard, Navegación Principal y Protección de Rutas.

CONTEXTO:
El login en `index.html` ya funciona y redirige a `dashboard.html`, pero este archivo aún no existe (arrojando un 404). Necesitamos crear el layout principal de la aplicación y protegerlo.

OBJETIVO PUNTUAL:
Construir el archivo `dashboard.html` con un diseño corporativo (Sidebar izquierdo y Header superior) usando Bootstrap 5. Implementar la lógica JS para validar que el usuario está logueado y permitirle cerrar sesión.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/web/public/dashboard.html` (Vista principal con layout).
2. `empresa-gastos-platform/web/src/scripts/pages/dashboard/dashboard.page.js` (Lógica de protección y renderizado).
3. `empresa-gastos-platform/web/src/styles/layout/sidebar.css` y `header.css` (Estilos del layout).

REGLAS DE NEGOCIO Y TÉCNICAS:
- Estructura HTML: Crea un `div.d-flex` principal. A la izquierda, un `<aside>` (Sidebar) con enlaces (placeholders) para: "Dashboard", "Mis Gastos", "Aprobaciones" y "Reportes". A la derecha, un área de contenido principal con un `<header>` (Navbar) que tenga un botón de "Cerrar Sesión".
- Protección de Ruta (JS): Al cargar la página, haz un fetch `GET` a `/api/public/api/auth/me` (incluyendo credentials). Si devuelve 401, redirige inmediatamente a `index.html`.
- Renderizado de Usuario: Si devuelve 200, extrae el nombre del usuario del JSON y muéstralo en el Header (ej. "Bienvenido, Juan Pérez").
- Cierre de Sesión (JS): Al hacer clic en "Cerrar Sesión", haz un fetch `POST` a `/api/public/api/auth/logout` (necesitarás obtener el token CSRF con un GET a `/api/public/api/csrf-token` primero). Tras el éxito, redirige a `index.html`.

ENTREGABLES ESPERADOS:
Genera el código de los archivos solicitados con un diseño limpio y funcional.

---

**Cursor**

Revisando el login y la estructura web existente para mantener consistencia con el dashboard.


### A. Resumen breve de lo implementado

Se creó **`dashboard.html`** con layout corporativo (sidebar + header), estilos de layout y JS que valida sesión con `/api/auth/me`, muestra el nombre del usuario y permite cerrar sesión con CSRF.

### B. Archivos creados

| Archivo | Descripción |
|---------|-------------|
| `web/public/dashboard.html` | Vista principal con layout |
| `web/src/scripts/pages/dashboard/dashboard.page.js` | Protección, bienvenida y logout |
| `web/src/styles/layout/sidebar.css` | Estilos del menú lateral |
| `web/src/styles/layout/header.css` | Estilos del header y contenido |

### C. Comportamiento JS

1. Al cargar → `GET /api/auth/me` con `credentials: 'include'`
2. **401** → redirección a `index.html`
3. **200** → muestra *"Bienvenido, {nombre}"* y oculta el spinner
4. **Cerrar Sesión** → `GET /api/csrf-token` → `POST /api/auth/logout` con `X-CSRF-TOKEN` → `index.html`

### D. Cómo probar

1. Inicia sesión en `index.html`
2. Tras la redirección, abre:  
   `http://localhost/Proyecto/empresa-gastos-platform/web/public/dashboard.html`
3. Deberías ver el panel con tu nombre y el menú lateral
4. Si abres `dashboard.html` sin sesión → vuelve al login
5. **Cerrar Sesión** debe dejarte en `index.html`

### E. Límites del incremento

- Enlaces del sidebar son placeholders (`#`), excepto Dashboard
- Sin menú dinámico por rol (todos ven las mismas opciones)
- La URL base de la API se resuelve igual que en el login (`.../api/public`)

---

**User**

Actúa como un desarrollador Full-Stack experto en PHP 8+ y Vanilla JavaScript. Nuestro objetivo es solucionar un error persistente de "Failed to fetch" (CORS) entre nuestro Frontend y nuestra API REST.

CONTEXTO:
Tenemos el backend en /api/public/index.php y el frontend en /web/public/index.html. Al intentar hacer el fetch para el Login o el CSRF token, el navegador bloquea la petición arrojando "Failed to fetch". Esto se debe a que el frontend se está sirviendo desde un origen distinto al de Apache y nuestro backend no tiene configuradas las cabeceras CORS.

OBJETIVO PUNTUAL:

Añadir el manejo global de CORS en el entrypoint de la API para permitir peticiones locales.

Mejorar el bloque catch del frontend para que imprima el error exacto en consola.

ARCHIVOS A MODIFICAR:

empresa-gastos-platform/api/public/index.php

empresa-gastos-platform/web/src/scripts/pages/auth/login.page.js

INSTRUCCIONES TÉCNICAS Y REGLAS:

En index.php (Backend): Justo al inicio del archivo (después del declare(strict_types=1);), inyecta las cabeceras CORS necesarias. Debes permitir Access-Control-Allow-Origin: * (o el origen específico del request si necesitas credenciales), Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, y Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN.

Manejo de Preflight (OPTIONS): Si el $_SERVER['REQUEST_METHOD'] es igual a 'OPTIONS', el script debe retornar un HTTP 200 y hacer exit; inmediatamente, sin pasar por el enrutador. Esto es vital para que las peticiones POST con headers personalizados funcionen.

En login.page.js (Frontend): En el bloque catch (error) del evento submit, añade un console.dir(error); y un console.error("Detalle del fallo:", error.message); para que, si vuelve a fallar, el desarrollador pueda ver exactamente qué política de red se rompió.

ENTREGABLES ESPERADOS:
Muestra las modificaciones exactas para ambos archivos. Explica brevemente cómo las peticiones OPTIONS solucionan el bloqueo del navegador.

---

**Cursor**

Revisando los archivos actuales para aplicar CORS correctamente con `credentials: 'include'`.


### Modificaciones aplicadas

**`api/public/index.php`** — CORS global al inicio:

```3:28:empresa-gastos-platform/api/public/index.php
/**
 * CORS global para consumo del frontend (credenciales + preflight OPTIONS).
 */
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedMethods = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';
$allowedHeaders = 'Content-Type, Authorization, X-CSRF-TOKEN, Accept';

if ($requestOrigin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i', $requestOrigin) === 1) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: ' . $allowedMethods);
header('Access-Control-Allow-Headers: ' . $allowedHeaders);
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

**`login.page.js`** — depuración en el `catch`:

```javascript
} catch (error) {
    console.dir(error);
    console.error('Detalle del fallo:', error instanceof Error ? error.message : error);
    showAlert(/* ... */);
}
```

---

### Por qué OPTIONS soluciona el bloqueo

Cuando el frontend hace un `POST` con headers personalizados (`Content-Type`, `X-CSRF-TOKEN`) y `credentials: 'include'`, el navegador lo trata como petición **no simple** y envía primero un **preflight** `OPTIONS` preguntando si el servidor autoriza ese origen, métodos y headers.

Si la API no responde a `OPTIONS` con **200** y las cabeceras CORS correctas, el navegador **no ejecuta** el `POST` real y aparece **"Failed to fetch"**.

Al responder `OPTIONS` con 200 y salir antes del router, el preflight queda resuelto y el login/CSRF pueden continuar.

---

### Nota sobre credenciales

El login usa `credentials: 'include'`, así que con origen `http://localhost` se devuelve ese origen concreto + `Access-Control-Allow-Credentials: true` (no se puede usar `*` en ese caso).

### Cómo probar

1. Recarga `index.html` (Ctrl+F5).
2. Abre **F12 → Consola** e intenta iniciar sesión.
3. En **Red**, deberías ver `OPTIONS` → 200 y luego `GET /api/csrf-token` / `POST /api/auth/login`.

Si sigue fallando, revisa en consola el mensaje de `console.error('Detalle del fallo:', ...)`.

---

**User**

Actúa como un desarrollador Frontend experto en HTML5, Bootstrap 5 y Vanilla JavaScript. Nuestro objetivo es implementar el incremento [F-002]: Dashboard, Navegación Principal y Protección de Rutas[cite: 735].

CONTEXTO:
El login en `index.html` ya funciona y redirige exitosamente a `dashboard.html`. Ya tenemos CORS configurado correctamente. Sin embargo, `dashboard.html` aún no existe en nuestro proyecto, arrojando un error 404[cite: 801, 802]. Necesitamos crear el layout principal de la aplicación y protegerlo.

OBJETIVO PUNTUAL:
Construir el archivo `dashboard.html` con un diseño corporativo (Sidebar izquierdo y Header superior) usando Bootstrap 5. Implementar la lógica JS para validar con el backend que el usuario está logueado y permitirle cerrar sesión.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/web/public/dashboard.html` (Vista principal con layout).
2. `empresa-gastos-platform/web/src/scripts/pages/dashboard/dashboard.page.js` (Lógica de protección y renderizado).
3. `empresa-gastos-platform/web/src/styles/layout/sidebar.css` y `header.css` (Estilos del layout).

REGLAS DE NEGOCIO Y TÉCNICAS:
- Estructura HTML: Crea un `div.d-flex` principal. A la izquierda, un `<aside>` (Sidebar estilo corporativo) con enlaces ficticios para: "Dashboard", "Mis Gastos", "Aprobaciones" y "Reportes". A la derecha, un área de contenido principal con un `<header>` (Navbar) que tenga un botón de "Cerrar Sesión".
- Variables Globales: Al inicio del archivo JS, define `const API_BASE_URL = 'http://localhost/Proyecto/empresa-gastos-platform/api/public';`
- Protección de Ruta (JS): Al cargar la página, haz un fetch `GET` a `${API_BASE_URL}/api/auth/me` (incluyendo credentials: 'include'). Si devuelve un error 401, redirige inmediatamente de vuelta a `index.html`.
- Renderizado de Usuario: Si la validación es 200 OK, extrae el nombre del usuario del JSON de la respuesta y muéstralo dinámicamente en el Navbar (ej. "Bienvenido, Juan Pérez").
- Cierre de Sesión (JS): Al hacer clic en "Cerrar Sesión", haz un fetch `POST` a `${API_BASE_URL}/api/auth/logout` (necesitarás obtener el token CSRF con un `GET` a `/api/csrf-token` primero). Tras el éxito, redirige a `index.html`.

ENTREGABLES ESPERADOS:
Genera el código de los archivos solicitados con un diseño limpio, moderno y responsivo usando las clases nativas de Bootstrap.

---

**Cursor**

Comprobando si los archivos del dashboard existen y si cumplen los requisitos actualizados (API_BASE_URL fija).


Los archivos del dashboard ya existen; actualizando `dashboard.page.js` para usar la misma `API_BASE_URL` fija que el login.


### A. Resumen

**F-002** ya estaba implementado en el proyecto. Se alineó `dashboard.page.js` con el login usando **`API_BASE_URL` fija** y mejor depuración en consola.

### B. Archivos

| Archivo | Estado |
|---------|--------|
| `web/public/dashboard.html` | Layout sidebar + header + contenido |
| `web/src/scripts/pages/dashboard/dashboard.page.js` | Protección `/api/auth/me`, logout con CSRF |
| `web/src/styles/layout/sidebar.css` | Estilos menú lateral |
| `web/src/styles/layout/header.css` | Estilos navbar y contenido |

### C. Flujo JS (`dashboard.page.js`)

```javascript
const API_BASE_URL = 'http://localhost/Proyecto/empresa-gastos-platform/api/public';
```

1. **Al cargar** → `GET ${API_BASE_URL}/api/auth/me` con `credentials: 'include'`
2. **401** → redirección a `index.html`
3. **200** → *"Bienvenido, {nombre}"* en el header
4. **Cerrar sesión** → `GET /api/csrf-token` → `POST /api/auth/logout` → `index.html`

### D. Estructura HTML

- `div.app-shell.d-flex`
- **Izquierda:** `<aside>` con Dashboard, Mis Gastos, Aprobaciones, Reportes
- **Derecha:** `<header>` con bienvenida + botón **Cerrar Sesión**
- **Main:** tarjeta de panel principal

### E. Cómo probar (evitar el 404)

Abre esta URL exacta en el navegador:

```
http://localhost/Proyecto/empresa-gastos-platform/web/public/dashboard.html
```

No uses `file://` ni rutas sin `/web/public/`.

1. Inicia sesión en `index.html`
2. Tras el redirect, deberías ver el dashboard con tu nombre
3. Sin sesión → vuelve al login
4. **Cerrar Sesión** → regresa a `index.html`

### F. Si sigue el 404

Comprueba que exista el archivo en:

`C:\xampp\htdocs\Proyecto\empresa-gastos-platform\web\public\dashboard.html`

y que **Apache** esté activo en XAMPP.

---

**User**

Actúa como un desarrollador Full-Stack experto en PHP 8+ y Vanilla JavaScript. Nuestro objetivo es implementar el incremento [F-003]: Pantalla de "Mis Gastos" (Listado y Captura Manual).

CONTEXTO:
El layout del `dashboard.html` ya funciona. Los enlaces del sidebar son ficticios. Necesitamos crear la pantalla real de "Mis Gastos" donde el usuario podrá ver su historial y capturar un nuevo gasto manual (Borrador).

OBJETIVO PUNTUAL:
1. Crear la vista `expenses.html` y vincularla en el sidebar.
2. Crear un Modal de Bootstrap para capturar un nuevo gasto.
3. Consumir los endpoints de catálogos para llenar los <select> del formulario.
4. Consumir el endpoint POST de gastos para guardarlo.
5. Asegurar que el backend tenga un endpoint GET para listar los gastos del usuario.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/web/public/dashboard.html` (Actualizar enlace del sidebar hacia `expenses.html`).
2. `empresa-gastos-platform/web/public/expenses.html` (Nueva vista copiando el layout, con una tabla y un Modal de captura).
3. `empresa-gastos-platform/web/src/scripts/pages/expenses/expenses.page.js` (Lógica JS para tabla y modal).
4. `empresa-gastos-platform/api/app/Controllers/ExpensesController.php` y `api/bootstrap/routes.php` (Añadir método GET y ruta `/api/gastos` si no existe, para listar solo los gastos del `$_SESSION['user_id']`).

REGLAS DE NEGOCIO Y TÉCNICAS:
- HTML/UI: La tabla debe mostrar Concepto, Fecha, Monto y Estatus. Añade un botón primario "Nuevo Gasto" que abra un Modal de Bootstrap 5.
- Modal Form: Debe tener inputs para `concepto_descripcion`, `monto_total` (number), `fecha_gasto` (date), `centro_costos_id` (select) y `cuenta_contable_id` (select).
- JS (Carga Inicial): Al abrir la página, haz fetch a `/api/centros-costo` y `/api/cuentas` para poblar los selects dinámicamente. Luego haz fetch a `/api/gastos` para llenar la tabla.
- JS (POST): Al enviar el formulario del Modal, haz POST a `/api/gastos` (enviando el CSRF token). Si es 201, cierra el modal, muestra alerta de éxito y recarga la tabla.

ENTREGABLES ESPERADOS:
Código limpio. Asegúrate de que las rutas relativas de los scripts y estilos en `expenses.html` apunten correctamente.

Actúa como un desarrollador Full-Stack experto en PHP 8+ y Vanilla JavaScript. Nuestro objetivo es implementar el incremento [F-003]: Pantalla de "Mis Gastos" (Listado y Captura Manual).

CONTEXTO:
El layout del `dashboard.html` ya funciona. Los enlaces del sidebar son ficticios. Necesitamos crear la pantalla real de "Mis Gastos" donde el usuario podrá ver su historial y capturar un nuevo gasto manual (Borrador).

OBJETIVO PUNTUAL:
1. Crear la vista `expenses.html` y vincularla en el sidebar.
2. Crear un Modal de Bootstrap para capturar un nuevo gasto.
3. Consumir los endpoints de catálogos para llenar los <select> del formulario.
4. Consumir el endpoint POST de gastos para guardarlo.
5. Asegurar que el backend tenga un endpoint GET para listar los gastos del usuario.

ARCHIVOS A CREAR/MODIFICAR:
1. `empresa-gastos-platform/web/public/dashboard.html` (Actualizar enlace del sidebar hacia `expenses.html`).
2. `empresa-gastos-platform/web/public/expenses.html` (Nueva vista copiando el layout, con una tabla y un Modal de captura).
3. `empresa-gastos-platform/web/src/scripts/pages/expenses/expenses.page.js` (Lógica JS para tabla y modal).
4. `empresa-gastos-platform/api/app/Controllers/ExpensesController.php` y `api/bootstrap/routes.php` (Añadir método GET y ruta `/api/gastos` si no existe, para listar solo los gastos del `$_SESSION['user_id']`).

REGLAS DE NEGOCIO Y TÉCNICAS:
- HTML/UI: La tabla debe mostrar Concepto, Fecha, Monto y Estatus. Añade un botón primario "Nuevo Gasto" que abra un Modal de Bootstrap 5.
- Modal Form: Debe tener inputs para `concepto_descripcion`, `monto_total` (number), `fecha_gasto` (date), `centro_costos_id` (select) y `cuenta_contable_id` (select).
- JS (Carga Inicial): Al abrir la página, haz fetch a `/api/centros-costo` y `/api/cuentas` para poblar los selects dinámicamente. Luego haz fetch a `/api/gastos` para llenar la tabla.
- JS (POST): Al enviar el formulario del Modal, haz POST a `/api/gastos` (enviando el CSRF token). Si es 201, cierra el modal, muestra alerta de éxito y recarga la tabla.

ENTREGABLES ESPERADOS:
Código limpio. Asegúrate de que las rutas relativas de los scripts y estilos en `expenses.html` apunten correctamente.

