---
Universidad: "FACULTAD DE ESTUDIOS SUPERIORES CUAUTITLAN"
Asignatura: "Seminario de desarrollo de aplicaciones web"
Trabajo: "Backlog generado mediante Vibe Coding e IA"
Profesor: "Prof. Marco Alberto Silva Reyes"
Equipo: "No programadores"
Integrantes:
  - Aguilar Carrillo Leonardo Axel
  - Barrera Aranguthy Ernesto
  - Grifaldo Rangel Fernando
Grupo: "2851"
Fecha: "04/06/2026"
---

# 1. Resumen ejecutivo del backlog

El objetivo de este backlog es guiar la construcción del Sistema de Gestión de Gastos Empresariales. Se trata de una aplicación monolítica on-premise, basada en un patrón MVC con Vanilla PHP, que gestionará el ciclo de vida completo de los gastos operativos y viáticos de una organización.

**Criterio de priorización:**
La estrategia sigue un modelo de construcción en vertical slices (rebanadas verticales) apilables. La prioridad se rige por la "Regla de Desbloqueo Funcional":

1. Fundación Técnica y Persistencia: No podemos guardar datos sin una base de datos segura y un enrutador HTTP robusto.
2. Identidad y Accesos: No hay roles ni áreas sin usuarios.
3. Catálogos Core: No hay gastos sin centros de costos y cuentas.
4. Flujo Operativo (Happy Path): Primero la captura manual, luego la carga de XML (que es más compleja).
5. Reglas de Negocio: Aplicación de presupuestos y bloqueos.
6. Aprobaciones y Workflow: Jerarquías y liberación de pagos.
7. Cierre Operativo: Auditoría final y exportación de reportes.

---

# 2. Supuestos de trabajo

Para estructurar este backlog sin bloquear la planeación, he tomado los siguientes supuestos mínimos:

* **Aislamiento de la lógica:** Aunque el documento indica un patrón "MVC clásico en PHP", la estructura de carpetas sugiere fuertemente una separación física entre el backend (/api) y el frontend (/web), donde el frontend consume la API vía AJAX. Asumiremos que el backend actuará como una API JSON/RESTful pura consumida por JavaScript (jQuery/Fetch), y no renderizará plantillas HTML directamente con PHP (como Blade o Twig).
* **Borrado Lógico Paginado:** El "Soft Delete" (campo deleted_at) se aplicará a nivel de consultas de base de datos; asumimos que las vistas de la interfaz ocultarán por defecto estos registros.
* **Procesamiento Asíncrono Simulado:** Como no hay frameworks pesados ni colas de mensajería (RabbitMQ/Redis) detalladas, asumimos que el "procesamiento en background del archivo" se referirá a llamadas AJAX no bloqueantes en el cliente o tareas cron a nivel servidor, y no a workers complejos.

---

# 3. Huecos, ambigüedades o contradicciones detectadas

Antes de comenzar el desarrollo, debemos tener en cuenta las siguientes áreas que requieren clarificación:

* **Técnicas (Arquitectura vs. Carpetas):** Hay una ligera contradicción arquitectónica. El documento cita "renderizado HTML dentro de los modelos no, utilizando controladores... hacia la vista", lo cual implica MVC tradicional (vistas renderizadas en servidor). Sin embargo, la estructura de carpetas tiene un bloque api/ puro y un bloque web/ con componentes JS/AJAX extensos. Resolución para el backlog: Trataremos el sistema como una arquitectura API-First (el backend provee endpoints, el frontend consume JSON).
* **Funcionales (Reglas de sobrescritura XML):** Si un capturista ingresa un monto manual y luego carga un XML, el sistema "puebla la solicitud y bloquea los campos correspondientes". Hueco: ¿Qué pasa si el XML incluye conceptos no deducibles o montos mixtos? Resolución temporal: El sistema tomará el monto total y RFC del XML como verdad absoluta inmutable.
* **De Flujo (Transiciones de Estado):** ¿El rechazo de un Jefe de Área elimina la asociación del XML o solo cambia el estado a 'Rechazado' permitiendo edición? Resolución temporal: El estado cambia a 'Borrador' o 'Rechazado', permitiendo al usuario editar los campos no bloqueados sin volver a subir el XML.
* **De Datos (Inicialización de Presupuestos):** La tabla presupuestos tiene monto_assigned mensual. Hueco: No se especifica cómo se calcula el "acumulado" mensual gastado al cambiar de año o mes en curso. Resolución temporal: Se calculará en tiempo real con un SUM(monto_total) de gastos en estado "Aprobado" y "Pendiente" del mes/año corriente.

---

# 4. Backlog inicial priorizado

> Nota: Para mantener la legibilidad y utilidad para un prompt de IA, el formato estructurado se presenta como "fichas" de requerimientos en lugar de una tabla Markdown excesivamente ancha.

### [B-001] Estructura Base, Ruteo API y Conexión a BD
* **Tipo de item:** Foundation / Backend
* **Objetivo:** Configurar el punto de entrada, variables de entorno, contenedor de dependencias simple y la conexión segura PDO a MySQL.
* **Descripción breve:** Creación del index.php frontal, parseo básico de rutas (sin frameworks) y clase singleton/estática para la conexión PDO a base de datos protegiendo inyecciones SQL.
* **Valor:** Habilita la construcción de cualquier endpoint subsecuente.
* **Dependencias:** Ninguna.
* **Alcance Incluido:** Enrutador básico HTTP, carga de .env, clase de base de datos (PDO), manejo global de excepciones en formato JSON.
* **Alcance Excluido:** Interfaz de usuario, migraciones automáticas complejas.
* **Riesgos:** Crear un enrutador "casero" con vulnerabilidades. (Mitigación: usar regex estricto).
* **Criterios de Aceptación:** Llamar a /api/health retorna HTTP 200 con un JSON de estado. Peticiones a rutas inexistentes devuelven HTTP 404 estructurado.
* **Pruebas manuales:** Peticiones mediante Postman/cURL a endpoints simulados.
* **Prioridad:** Crítica | Tamaño: S | Fase: 1 | Orden: 1

### [B-002] Esquema de Base de Datos y Migración Inicial
* **Tipo de item:** Foundation / Data
* **Objetivo:** Traducir el diccionario de datos a scripts SQL DDL.
* **Descripción breve:** Creación del archivo de migración o volcado SQL inicial con todas las tablas descritas (roles, areas, usuarios, etc.), llaves foráneas y constraints (incluyendo libxml_disable_entity_loader).
* **Valor:** Prepara el terreno físico para guardar la información con integridad relacional.
* **Dependencias:** B-001.
* **Alcance Incluido:** Tablas de diccionario de datos, índices especificados (idx_gastos_presupuesto), borrado lógico genérico.
* **Alcance Excluido:** Procedimientos almacenados o triggers (todo se hará por código).
* **Riesgos:** Errores de sintaxis en llaves foráneas.
* **Criterios de Aceptación:** El script SQL corre sin errores desde cero, creando todas las tablas con motor InnoDB.
* **Pruebas manuales:** Ejecución del script DDL en la consola de MySQL/MariaDB.
* **Prioridad:** Crítica | Tamaño: M | Fase: 1 | Orden: 2

### [B-003] Seguridad: Cifrado, CSRF y Manejo de Sesiones Seguras
* **Tipo de item:** Seguridad / Foundation
* **Objetivo:** Establecer los mecanismos nativos de protección antes de gestionar usuarios.
* **Descripción breve:** Implementación de generación/validación de token CSRF para peticiones mutativas, configuración de cookies HttpOnly, y capa de hash genérica (PASSWORD_BCRYPT).
* **Valor:** Cumplimiento de los requisitos de seguridad corporativa.
* **Dependencias:** B-001.
* **Alcance Incluido:** Funciones para generar hash, middleware de validación CSRF, inicialización segura de sesiones PHP.
* **Alcance Excluido:** Login visual de usuarios (solo el middleware interno).
* **Riesgos:** Bloqueo accidental de endpoints legítimos por validación estricta.
* **Criterios de Aceptación:** Peticiones POST sin el header/parámetro CSRF válido retornan error HTTP 403.
* **Pruebas manuales:** Interceptar y modificar el token CSRF en una petición POST de prueba.
* **Prioridad:** Crítica | Tamaño: S | Fase: 1 | Orden: 3

### [B-004] Catálogos Base (Áreas, Roles y Centro de Costos)
* **Tipo de item:** Backend / Catálogos
* **Objetivo:** Proveer la estructura organizacional fundamental.
* **Descripción breve:** Endpoints CRUD simples (solo backend por ahora) para alimentar las tablas paramétricas areas, roles, centro_costos y catalogo_cuentas.
* **Valor:** Permite registrar usuarios y asignarles pertenencia operativa.
* **Dependencias:** B-002, B-003.
* **Alcance Incluido:** Modelos base, validación estricta de tipos.
* **Alcance Excluido:** Interfaz gráfica.
* **Criterios de Aceptación:** Endpoints /api/areas y equivalentes responden adecuadamente con JSON.
* **Prioridad:** Alta | Tamaño: M | Fase: 2 | Orden: 1

### [B-005] Identidad: Gestión de Usuarios (CRUD y Login)
* **Tipo de item:** Full Slice (Backend + Frontend)
* **Objetivo:** Permitir el acceso al sistema mediante credenciales.
* **Descripción breve:** Pantalla visual de login, endpoint de autenticación, y generación de sesión vinculando al usuario con su rol y área correspondiente.
* **Valor:** El sistema comienza a tener dueños y reglas de control de acceso (RBAC).
* **Dependencias:** B-004.
* **Alcance Incluido:** Vista login, enrutamiento web, controlador de autenticación, restricción de rutas no autenticadas.
* **Riesgos:** Exposición de contraseñas.
* **Criterios de Aceptación:** Un usuario registrado puede hacer login y el sistema "recuerda" su area_id.
* **Prioridad:** Alta | Tamaño: M | Fase: 2 | Orden: 2

### [B-006] Core: Captura Manual de Gasto (Estado Borrador)
* **Tipo de item:** Full Slice
* **Objetivo:** Permitir el registro de cajas chicas o gastos sin factura electrónica asociada.
* **Descripción breve:** Interfaz visual y endpoint para registrar un gasto menor, atando el egreso al centro de costos del capturista y un estatus inicial "Borrador".
* **Valor:** Inicio del flujo transaccional principal.
* **Dependencias:** B-005.
* **Alcance Incluido:** Formulario HTML (Bootstrap), sanitización de inputs (evitar XSS), validación numérica de monto, inserción en la tabla gastos.
* **Alcance Excluido:** Aprobación, presupuesto, subida de archivos XML.
* **Criterios de Aceptación:** Gasto se guarda en BD con el usuario_capturista_id extraído directamente de la sesión, no del formulario.
* **Prioridad:** Crítica | Tamaño: M | Fase: 3 | Orden: 1

### [B-007] Archivos: Servicio de Carga y Almacenamiento Local de XML
* **Tipo de item:** Backend / Archivos
* **Objetivo:** Subir de forma segura archivos al file system local.
* **Descripción breve:** Lógica para recibir un multipart form-data, validar MIME type (solo XML), renombrar con hash único y almacenar en el servidor físico on-premise.
* **Valor:** Prepara el terreno para extraer información fiscal.
* **Dependencias:** B-006.
* **Alcance Incluido:** Subida a ruta local bloqueada al acceso web público.
* **Alcance Excluido:** Parsing/Lectura interna del XML.
* **Riesgos:** Riesgo XXE o subida de shells. (Mitigación: validación estricta de extensión/MIME).
* **Criterios de Aceptación:** Archivos .xml se guardan en /storage/uploads/xml/. Cualquier otro formato es rechazado con error 422.
* **Prioridad:** Alta | Tamaño: S | Fase: 3 | Orden: 2

### [B-008] Flujo: Parsing Automático de CFDI e Inserción
* **Tipo de item:** Backend / Flujo
* **Objetivo:** Extraer datos financieros del CFDI automáticamente.
* **Descripción breve:** Uso de PHP (con carga de entidades externas deshabilitada) para parsear el XML guardado en B-007. Extraer UUID, RFC, Subtotal, IVA y Total. Registrar en tabla facturas_cfdi.
* **Valor:** Automatiza la captura y reduce fraudes por alteración manual.
* **Dependencias:** B-007.
* **Alcance Incluido:** Clase de Servicio XmlParserService dedicada, prevención XXE obligatoria.
* **Criterios de Aceptación:** Extrae los nodos UUID, RFC emisor, RFC receptor y montos de forma correcta sin errores fatales y guarda en BD.
* **Prioridad:** Alta | Tamaño: M | Fase: 3 | Orden: 3

### [B-009] Reglas: Motor de Presupuesto Mensual y Bloqueo
* **Tipo de item:** Backend / Reglas Financieras
* **Objetivo:** Evitar sobregiros por área.
* **Descripción breve:** Clase de servicio que evalúe (Presupuesto asignado - Gastos del mes). Si el nuevo gasto supera el diferencial, se devuelve un error de validación y no se permite enviar a "Pendiente de Aprobación".
* **Valor:** Control de capital. Principal propuesta de valor para el negocio.
* **Dependencias:** B-004, B-006.
* **Criterios de Aceptación:** Sumatoria SQL con el índice idx_gastos_presupuesto. Intento de gasto que sobrepasa el presupuesto asignado del mes/año debe fracasar.
* **Prioridad:** Crítica | Tamaño: M | Fase: 4 | Orden: 1

### [B-010] Flujo: Bandeja y Autorización por Jefe de Área
* **Tipo de item:** Full Slice
* **Objetivo:** Enrutamiento dinámico al superior.
* **Descripción breve:** Interfaz tipo Dashboard para usuarios con rol 'Jefe de Área' donde ven gastos pendientes de su mismo centro de costos. Botones para "Aprobar" o "Rechazar" (con comentario obligatorio).
* **Dependencias:** B-006, B-009.
* **Criterios de Aceptación:** El capturista no puede auto-aprobarse. El jefe solo ve solicitudes de su departamento.
* **Prioridad:** Alta | Tamaño: L | Fase: 4 | Orden: 2

### [B-011] Flujo: Validación Final por Cuentas por Pagar
* **Tipo de item:** Full Slice
* **Objetivo:** Cierre del ciclo contable y asignación de folio interno.
* **Descripción breve:** Bandeja global para el rol 'Cuentas por Pagar'. Visualización de gastos previamente aprobados por los jefes, asignación a subcuenta del catálogo, y marcado final.
* **Dependencias:** B-010.
* **Prioridad:** Media | Tamaño: M | Fase: 4 | Orden: 3

### [B-012] Auditoría: Inserción en Bitácora Inmutable
* **Tipo de item:** Backend / Auditoría
* **Objetivo:** Rastro inalterable de operaciones.
* **Descripción breve:** Middleware o hook en el controlador base que intercepte cualquier creación/modificación de gastos y escriba los valores JSON anteriores/nuevos en bitacora_auditoria garantizando IP y Timestamp.
* **Dependencias:** B-001, B-006.
* **Prioridad:** Alta | Tamaño: S | Fase: 5 | Orden: 1

### [B-013] Reportes: Exportación CSV/Excel y Filtros
* **Tipo de item:** Backend / Reportes
* **Objetivo:** Visibilidad de la ejecución financiera.
* **Descripción breve:** Motor de consulta con parámetros GET (rango fechas, centro costo). Retorno directo de la sábana de datos procesados mediante headers HTTP para forzar descarga CSV nativa.
* **Dependencias:** B-006, B-011.
* **Prioridad:** Media | Tamaño: M | Fase: 5 | Orden: 2

---

# 5. Agrupación por fases

* **Fase 1: Fundaciones Técnicas (B-001, B-002, B-003)**
* **Objetivo Fase 1:** Levantar el esqueleto técnico, la seguridad base (CSRF, contraseñas) y preparar el modelo de datos.
* **Razón Fase 1:** No podemos desarrollar ninguna funcionalidad de negocio sin un canal de HTTP seguro, un ruteador y la base de datos lista.

* **Fase 2: Identidad y Estructura Organizacional (B-004, B-005)**
* **Objetivo Fase 2:** Autenticación y catálogos estáticos.
* **Razón Fase 2:** Cada transacción en el sistema es dueña de un "área" y la realiza un "usuario" específico; estas identidades deben existir antes de crear gastos.

* **Fase 3: Core Operativo (Captura y Facturas) (B-006, B-007, B-008)**
* **Objetivo Fase 3:** Lograr que un usuario pueda crear y almacenar una solicitud de gasto, manual o respaldada por un XML validado.
* **Razón Fase 3:** Es el corazón transaccional del sistema.

* **Fase 4: Motor Financiero y Autorizaciones (B-009, B-010, B-011)**
* **Objetivo Fase 4:** Implementar las transiciones de estado, los presupuestos bloqueantes y las vistas por perfil (Jefe y CxP).
* **Razón Fase 4:** Solo se pueden aprobar gastos que ya existan (Fase 3).

* **Fase 5: Gobernanza y Análisis (B-012, B-013)**
* **Objetivo Fase 5:** Asegurar el cumplimiento de 5 años inmutables y la exportación de datos.
* **Razón Fase 5:** La trazabilidad opera sobre un sistema ya funcional.

---

# 6. Secuencia recomendada de ejecución

1. **Orden estricto:** Debes iniciar construyendo el esqueleto PHP (B-001) y la base de datos (B-002) simultáneamente. A partir de ahí el orden es estrictamente secuencial de la B-003 a la B-013.
2. **Primer ítem ideal:** El B-001. Es el ancla técnica.
3. **No adelantar:** Bajo ninguna circunstancia se debe adelantar el parsing del XML (B-008) ni las reglas de presupuesto (B-009) si no existe un sistema funcional de login con la inyección segura del area_id en la sesión del usuario (B-005), de lo contrario se generaría una deuda técnica de acoplamiento y fraude desde el principio.

---

# 7. Identificación de items demasiado grandes

Del documento original, ciertas agrupaciones requerían división explícita:

* **"Módulo de captura de datos":** Altamente complejo. Pudo haber sido un solo ticket monolítico en otras metodologías.
* **Solución Módulo Captura:** Se dividió estratégicamente en 3: B-006 (Gasto manual base), B-007 (Infraestructura de subida de archivos), B-008 (Lógica de extracción de nodos XML).
* **"Módulo de aprobación":** Si se hacía de golpe, las reglas de negocio hubieran chocado.
* **Solución Módulo Aprobación:** Dividido en la bandeja del Jefe de Área (B-010) y la bandeja de CxP (B-011), dejando aislado el Motor de Presupuesto (B-009) como una dependencia previa estricta.

---

# 8. Backlog listo para usarse con una IA desarrolladora

Los 5 primeros incrementos que debes suministrar a tu herramienta de desarrollo (Cursor/Codex) en orden, copiando su contexto y objetivo, son:

1. **Generar Incremento 1: B-001 (Configuración Base):** Pide a la IA que cree el index.php, .htaccess, la clase Database.php usando PDO y la carga de .env. Desbloquea el proyecto entero.
2. **Generar Incremento 2: B-002 (Esquema SQL):** Pide a la IA que cree el script init.sql basándose en el Diccionario de Datos del contexto, prestando atención a los Foreign Keys y motor InnoDB. Desbloquea la persistencia.
3. **Generar Incremento 3: B-003 (Manejo de Sesión y CSRF):** Pide a la IA crear el trait/clase de seguridad SecurityMiddleware.php para validación de tokens en POST y configuración de sesión PHP. Desbloquea la manipulación de datos en un entorno web interno seguro.
4. **Generar Incremento 4: B-004 (Catálogos y Endpoints GET/POST básicos):** Pide a la IA que genere los controladores y modelos simples (con sentencias preparadas) para roles, areas, centro_costos. Desbloquea la estructura organizacional.
5. **Generar Incremento 5: B-005 (Autenticación - Usuarios):** Pide a la IA crear el proceso de registro interno (con contraseñas hasheadas en BCRYPT), y el endpoint/vista de Login que inicialice la sesión vinculada a su Área. Desbloquea la funcionalidad core de la aplicación.

Con este Backlog tienes una base estructurada para comenzar a iterar con prompts cortos, seguros y enfocados en agregar valor comprobable desde el primer commit.

## Evidencia requerida

| Herramienta |               Objetivo |                       Evidencia                        |
| ----------- | ---------------------: | :----------------------------------------------------: |
| Gemini      | Generación del Backlog | [enlace](https://gemini.google.com/share/e195db5d75d2) |