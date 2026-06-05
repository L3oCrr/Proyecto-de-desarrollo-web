# Documento de Diseño

## Sistema de Gestión de Gastos Empresariales

---

## 1. Visión general de la aplicación

<p align="justify">

- **Naturaleza on-premise:** La aplicación se desplegará localmente en los servidores internos de la organización, garantizando el control absoluto de la infraestructura, el código fuente y el almacenamiento de la información sin dependencias externas en la nube.
- **Alcance organizacional (una sola empresa):** Diseñado como un sistema monolítico e interno para gestionar las operaciones financieras de una única entidad jurídica, adaptándose a su estructura operativa específica.
- **Objetivo de trazabilidad y control:** Mitigar riesgos de fugas de capital, fraudes y duplicidades mediante el rastreo inequívoco de cada egreso. El sistema asocia cada transacción a un responsable y a un propósito financiero claro.
- **Ciclo completo del gasto:** Gobierna desde el registro inicial del comprobante por parte del colaborador, pasando por las validaciones de negocio y flujos jerárquicos de autorización administrativa, hasta la liberación presupuestal y generación de reportes operativos.
- **Retención mínima de 5 años:** Cumplimiento del marco de persistencia de datos históricos para auditorías internas y revisiones fiscales posteriores, asegurando la inmutabilidad de los registros financieros acumulados.
- **Enfoque de diseño simple y mantenible:** Arquitectura libre de frameworks sobre-ingenierizados, priorizando la claridad del código, el uso de componentes desacoplados y una curva de aprendizaje mínima para el equipo de desarrollo interno.

---

## 2. Alcance funcional

### Incluido:

- **Hacer un registro manual de gastos:** Formulario web para la captura manual de erogaciones operativas, viáticos o gastos de caja chica.
- **Carga de factura en forma XML:** Componente de carga de archivos que acepta exclusivamente el formato XML estándar del Comprobante Fiscal Digital por Internet (CFDI).
- **Extracción de datos claves del XML:** Procesamiento del archivo XML cargado en el backend para parsear de manera automática el Registro Federal de Contribuyentes (RFC) del emisor, monto total de la operación, Impuesto al Valor Agregado (IVA) desglosado y fecha de emisión.
- **Flujo de aprobación por área:** Enrutamiento dinámico de las solicitudes basado en la estructura jerárquica y el centro de costos del área a la que pertenece el usuario solicitante.
- **Control de presupuesto:** Bloqueo preventivo o alertas en el sistema cuando un gasto propuesto supera el techo financiero asignado al departamento en el periodo mensual correspondiente.
- **Datos de auditoría básica:** Registro automático e inalterable de marcas de tiempo, direcciones IP, identificadores de usuario y acciones realizadas sobre cada registro de gasto.
- **Reportes operativos:** Módulo de visualización de datos y exportación de hojas de cálculo con el desglose de gastos ejercidos, clasificados por periodos, centros de costos y categorías contables.

### Fuera de alcance:

- **Integraciones con ERPs:** No se contempla comunicación por API, Web Services ni sincronización automatizada con sistemas ERP de terceros (como SAP, Oracle o Microsoft Dynamics).
- **Validación de RFC, conceptos, factura no cancelada, comprobantes de pago:** El sistema no realizará peticiones web en tiempo real a las listas de contribuyentes del SAT (LCO/EFOS), ni validará vigencia/cancelación del UUID ante el servicio de verificación del SAT, ni procesará complementos de pago asociados. Estas verificaciones técnicas recaen fuera del core de la aplicación para este release.

---

## 3. Contexto organizacional

### 3.1 Estructura organizacional

- **Áreas:** Divisiones funcionales de la empresa (vgr. Ventas, Marketing, Tecnologías de la Información, Producción) que ejecutan recursos financieros para sus operaciones diarias.
- **Centros de costos:** Unidades contables específicas asociadas a cada área para segmentar, rastrear y analizar detalladamente el destino del capital ejercido.
- **Jefe para autorización:** Cada área funcional tiene asignado un usuario con rol de superior jerárquico, facultado para evaluar la pertinencia operativa de los gastos y emitir aprobaciones o rechazos.
- **Un usuario solo puede meter datos a una área:** Restricción a nivel de base de datos y sesión que vincula rígidamente al usuario capturista con su respectivo centro de costos de adscripción, impidiendo el registro de transacciones a nombre de otros departamentos.

### 3.2 Control Presupuestal

- **Presupuesto por área y mensual:** Cuotas financieras máximas asignadas a cada centro de costos al inicio de cada mes, actuando como la frontera de control transaccional del sistema.
- **Comparativos de presupuestado vs real:** Vistas consolidadas que contrastan en tiempo real los techos financieros autorizados originalmente contra los gastos efectivamente devengados y aprobados dentro del periodo corriente.

---

## 4. Roles del sistema

- **Usuario capturista**
  - **Qué puede hacer:** Registrar nuevos gastos manuales, cargar archivos XML de comprobantes y editar sus propios registros en estado borrador o rechazados para su corrección.
  - **Qué puede visualizar:** El historial personal de sus solicitudes enviadas, los estados de sus aprobaciones en curso y el saldo disponible del presupuesto mensual asignado a su área operativa.
  - **Qué administra:** Exclusivamente su propio perfil de usuario y la documentación soporte de sus gastos cargados.

- **Jefe de área**
  - **Qué puede hacer:** Autorizar administrativamente o rechazar con comentarios los gastos solicitados por los capturistas adscritos a su respectiva área.
  - **Qué puede visualizar:** Bandeja de entrada con las solicitudes pendientes de su departamento, reportes de consumo presupuestal de su centro de costos y alertas de desviaciones financieras.
  - **Qué administra:** La aprobación y liberación de los flujos de gasto internos de su unidad organizacional.

- **Cuentas por pagar**
  - **Qué puede hacer:** Validar a nivel contable los gastos previamente aprobados por los jefes de área, clasificar el egreso en el catálogo de cuentas correspondiente y marcar las transacciones como listas para dispersión financiera o conciliadas.
  - **Qué puede visualizar:** El tablero general de gastos aprobados de todas las áreas de la organización, reportes consolidados y el estado de la suficiencia presupuestal global.
  - **Qué administra:** La correcta asignación contable (cargo/abono) y el estatus final de liquidación de las solicitudes validadas.

- **Administrador de la aplicación**
  - **Qué puede hacer:** Crear, modificar y deshabilitar usuarios; configurar la estructura de áreas, centros de costos y asignar los techos presupuestales mensuales.
  - **Qué puede visualizar:** La totalidad de las interfaces del sistema, incluyendo consolas de errores, registros de auditoría global y métricas de rendimiento de la base de datos.
  - **Qué administra:** Catálogos base de la aplicación, asignación de roles, flujos jerárquicos de escalamiento (workflows) y políticas de seguridad del sistema.

---

## 5. Arquitectura general

### 5.1 Enfoque arquitectónico

- **Patrón MVC clásico en PHP:** Implementación del patrón Modelo-Vista-Controlador de forma nativa para separar limpiamente la lógica de presentación, las reglas de negocio y el acceso a datos.
- **Separación de capas:** Desacoplamiento estricto que prohíbe consultas SQL directas en las vistas y renderizado HTML dentro de los modelos, utilizando controladores como orquestadores únicos.
- **Sin frameworks full-stack:** Descarte explícito de herramientas de terceros, recurriendo a Vanilla PHP organizado bajo principios de programación orientada a objetos (POO) y enrutamiento limpio basado en URLs amigables.

### 5.2 Componentes principales

#### web (frontend)

- **HTML5 / CSS3:** Maquetado semántico estándar para la interfaz de usuario.
- **Bootstrap:** Framework CSS adaptativo (utilizado únicamente mediante estilos locales compilados) para proveer un diseño responsivo y consistente en la red interna corporativa.
- **JavaScript / jQuery:** Interactividad en el cliente, validaciones de formularios antes del envío y llamadas AJAX puntuales para dinamizar la carga de datos sin recargar el navegador.

#### api (backend)

- **PHP:** Motor de ejecución del lado del servidor configurado en el servidor web local.
- **Controladores:** Clases encargadas de recibir las peticiones HTTP, validar las sesiones, procesar los parámetros de entrada y retornar las respuestas adecuadas hacia la vista.
- **Servicios:** Capa intermedia donde reside la lógica de negocio pura, como el parser extractor de los nodos XML del CFDI y las reglas de validación presupuestal mensual.
- **Modelos / Repositorios:** Clases dedicadas de manera exclusiva a la abstracción de las tablas de la base de datos y a la ejecución de consultas mediante sentencias preparadas.

#### Persistencia

- **MySQL / MariaDB:** Sistema de gestión de bases de datos relacionales para almacenar transacciones, catálogos, configuraciones e históricos de auditoría.
- **Almacenamiento local de XML:** Directorio seguro y restringido dentro del sistema de archivos del servidor on-premise para almacenar físicamente los comprobantes XML respaldados, vinculados mediante rutas únicas indexadas en la base de datos.

#### Infraestructura

- **Servidor on-premise:** Servidor físico o máquina virtual con sistema operativo Linux corriendo una pila Apache/Nginx.
- **Respaldo y mantenimiento:** Scripts automatizados a nivel de sistema operativo (Cron jobs) para ejecutar volcados de base de datos (dumps) y empaquetado de archivos XML de forma periódica hacia discos de almacenamiento redundante locales.

---

## 6. Módulos funcionales

- **Módulo de autenticación**
  - Control de acceso mediante credenciales de usuario y contraseña con manejo estricto de sesiones PHP seguras en el servidor.
  - Expiración automática de sesión por inactividad prolongada corporativa para mitigar accesos no autorizados en terminales abandonadas.
- **Módulo de administración de la aplicación**
  - Altas, bajas y cambios de la estructura organizacional, asignación de usuarios a áreas específicas y configuración del catálogo de cuentas básico.
  - Captura y edición de los techos presupuestales mensuales por centro de costos.
- **Módulo de captura de datos**
  - Interfaz para ingreso de conceptos de gasto manuales y carga de facturas XML.
  - Procesamiento en background del archivo cargado para el autollenado de los campos del formulario con la información fiscal del CFDI.
- **Módulo de aprobación**
  - Bandeja de decisiones para jefes de área con opción de aprobación/rechazo basada en presupuesto disponible.
  - Consola de revisión final para Cuentas por Pagar para la asignación de folios contables internos antes de proceder al pago.
- **Módulo para generar reportes**
  - Motor de consultas con filtros por rango de fechas, centros de costos, estados de solicitud y tipos de gasto.
  - Exportador directo a formato CSV/Excel de las sábanas de datos financieros procesados.
- **Módulo de auditoría**
  - Bitácora de seguimiento inmutable que almacena el rastro histórico completo de modificaciones a cualquier documento de gasto.
  - Visor restringido para el Administrador de las firmas digitales y marcas de tiempo del sistema.
- **Consulta de historial**
  - Pantalla de búsqueda avanzada y paginada que permite a los usuarios autorizados inspeccionar transacciones pasadas de periodos previos.
  - Acceso inmediato a la descarga del XML original y los datos de registro asociados a egresos cerrados.
- **Notificaciones**
  - Sistema interno de alertas visuales en el tablero de la aplicación (Dashboard) para avisar sobre nuevos gastos pendientes de firma o rechazos sufridos.

---

## 7. Flujo completo del gasto

### Pasos del Proceso:

1. **Capturar un gasto:** El usuario capturista inicia una solicitud seleccionando el tipo de gasto e ingresando los detalles preliminares de la transacción.
2. **Cargar XML de CFDI:** El capturista adjunta el archivo XML del comprobante fiscal al formulario de la aplicación.
3. **Procesar XML:** El componente de backend parsea de forma automática el archivo, extrae los montos, impuestos y RFC, poblando la solicitud y bloqueando los campos correspondientes para evitar alteraciones manuales.
4. **Mandar aprobación:** El usuario envía la solicitud terminada. El sistema valida que el monto se encuentre dentro del presupuesto disponible mensual del área y la enruta a la bandeja del revisor.
5. **Consulta aprobaciones de gasto:** El jefe de área accede a su bandeja y revisa la pertinencia, justificación y coherencia operativa de la transacción solicitada.
6. **Aprobación o rechazo:** El jefe determina la validez; si aprueba, el registro avanza en la fila hacia Cuentas por Pagar; si rechaza, devuelve la solicitudiniendo los motivos en el campo de retroalimentación.
7. **Corregir o validar:** El capturista corrige las observaciones de un gasto rechazado reiniciando el flujo, u operativamente Cuentas por Pagar valida e ingresa el registro contable final de la solicitud aprobada.
8. **Generar un reporte:** Los datos consolidados de la transacción finalizada quedan disponibles de forma inmediata para el cierre mensual y la exportación de reportes de control financiero.

### Estados definidos:

- **Borrador:** Registro en creación o edición por el capturista; aún no afecta presupuesto ni es visible por los aprobadores.
- **Pendiente de aprobación:** Solicitud enviada formalmente que se encuentra en la bandeja del jefe de área o de Cuentas por Pagar en espera de dictamen.
- **Aprobación:** Gasto validado administrativamente y contablemente de forma exitosa, cerrando el ciclo de validación del software.
- **Rechazo:** Solicitud devuelta al capturista por inconsistencias o falta de presupuesto, requiriendo acción correctiva o su cancelación definitiva.

---

## 8. Modelo de datos conceptual

### Entidades principales:

- **Usuario:** Almacena identificadores únicos, nombres, contraseñas hasheadas, correos y la clave del área operativa a la que pertenece de forma exclusiva.
- **Rol o perfil:** Define los privilegios específicos del sistema (Capturista, Jefe de área, Cuentas por pagar, Administrador de la aplicación).
- **Áreas:** Catálogo de las divisiones de la compañía que fungen como dueños de los recursos.
- **Presupuesto:** Registra los techos financieros numéricos asignados por mes e ID de centro de costos.
- **Gastos:** Entidad operativa transaccional central; almacena montos totales, fechas, descripciones, estados del flujo y los ID de los usuarios solicitantes y aprobadores.
- **Factura / CFDI:** Contiene la información extraída del comprobante electrónico: UUID (Folio fiscal), RFC emisor, RFC receptor de la empresa, monto de IVA y la ruta de almacenamiento físico del archivo XML en el servidor.
- **Catálogo:** Tabla (Cuentas de gastos) que contiene el listado de las cuentas contables de naturaleza deudora (Grupo 600) conforme a los requerimientos de estructura de la organización.
- **Centro de costos:** Entidad que vincula las áreas con sus códigos agrupadores y clasificaciones contables operativas.

### Relaciones clave:

- Un Usuario pertenece obligatoriamente a una sola Área transaccional.
- Un Área puede tener asignados uno o más Centros de costos para dividir sus gastos.
- Cada Centro de costos posee un único registro de Presupuesto por cada periodo mensual configurado.
- Un Gasto pertenece a un único Centro de costos y es iniciado por un único Usuario capturista.
- Un Gasto aprobado puede estar vinculado de forma opcional o mandatoria a una entidad Factura / CFDI.
- Cada registro de Gasto debe asociarse a una subcuenta específica dentro de la entidad Catálogo para su correcta póliza contable.
- Un Rol o perfil contiene a múltiples Usuarios, determinando dinámicamente las opciones de menús y accesos disponibles en la aplicación.

---

## 9. Consideraciones técnicas y de seguridad

- **RBAC:** Validación estricta a nivel de servidor de los permisos del perfil del usuario antes de procesar o renderizar cualquier recurso, script de backend o endpoint.
- **Validación backend obligatoria:** Prohibición absoluta de confiar en los datos validados del lado del cliente. Todos los parámetros recibidos por POST/GET pasan por sanetización y verificación rigurosa de tipos de datos en PHP.
- **Hash seguro de contraseñas:** Almacenamiento de contraseñas de usuarios utilizando el algoritmo nativo `password_hash()` de PHP con la función robusta `PASSWORD_BCRYPT`.
- **Protección CSRF:** Inserción obligatoria de tokens criptográficos aleatorios y temporales en cada formulario web de envío de datos, verificados rigurosamente en el backend en cada petición mutativa.
- **Protección XSS:** Escape exhaustivo de cualquier dato dinámico proveniente de la base de datos o entradas del usuario utilizando la función `htmlspecialchars()` con codificación UTF-8 antes de ser renderizado en las plantillas HTML.
- **Prevención SQL Injection:** Uso mandatorio y exclusivo de sentencias preparadas (Prepared Statements) mediante PDO (PHP Data Objects) para toda interacción, inserción o consulta con la base de datos MySQL/MariaDB, erradicando la concatenación directa de variables en cadenas SQL.
- **Manejo seguro de XML:** Configuración del parser de XML de PHP deshabilitando explícitamente la carga de entidades externas (`libxml_disable_entity_loader(true)`) para prevenir vectores de ataque de Inyección de Entidades Externas XML (XXE).
- **Bitácora inmutable:** Almacenamiento de registros de auditoría mediante inserciones puras (`INSERT` únicamente), bloqueando a nivel de base de datos privilegios de `UPDATE` o `DELETE` sobre las tablas de logs para garantizar la integridad histórica.
- **Retención 5 años:** Estructura de particionado físico en las tablas transaccionales de la base de datos optimizada para soportar la retención inalterable de datos por el periodo mínimo obligatorio de cinco ejercicios fiscales.
- **Respaldos periódicos:** Configuración automatizada de tareas programadas en el sistema operativo del servidor on-premise para realizar respaldos diarios calientes de los datos y copias de los ficheros de comprobantes XML a unidades de almacenamiento independientes.

---

## 10. Lineamientos de calidad y mantenibilidad

- **Separación por capas:** El desarrollo debe mantener fronteras de responsabilidad cristalinas. Los archivos de vista únicamente consumen variables previamente procesadas y preparadas por la lógica de los controladores.
- **Servicios de negocio centralizados:** Cualquier regla de negocio crítica (vgr. la lógica aritmética para evaluar el sobregiro de un presupuesto mensual o la extracción de nodos del CFDI) debe encapsularse en clases de servicio dedicadas y reutilizables, prohibiendo duplicar esta lógica en múltiples controladores.
- **Uso mínimo de librerías:** Restringir el uso de componentes de terceros exclusivamente a utilidades críticas que resuelvan necesidades donde el lenguaje nativo represente un costo de implementación excesivo, manteniendo el control directo sobre el core del software.
- **Convenciones claras:** Adopción de estándares rigurosos de codificación orientada a objetos (vgr. PascalCase para nombres de clases, camelCase para métodos/variables y nombres de tablas en plural bajo notación snake_case), acompañados de una estructura de directorios intuitiva (`/src/Controllers`, `/src/Models`, `/src/Services`, `/views`).
- **Preparación para crecimiento controlado:** Arquitectura basada en interfaces y bajo acoplamiento que facilite la escalabilidad futura o adición de nuevos módulos sin necesidad de refactorizar de forma disruptiva la base del código existente.

---

## 11. Conclusión

- **Solidez arquitectónica:** El diseño propuesto bajo el patrón MVC nativo en PHP asegura una base estructural firme, libre de la complejidad accidental de dependencias externas cambiantes, garantizando un rendimiento óptimo dentro de la infraestructura interna de la organización.
- **Control financiero:** La integración nativa de la asignación mensual de presupuestos por centros de costos dota a la empresa de una herramienta estricta de control preventivo contra gastos innecesarios, excesos o desviaciones de capital.
- **Trazabilidad:** El flujo secuencial inalterable de estados de un gasto unido a los registros inmutables de auditoría técnica provee visibilidad total sobre quién originó, quién aprobó y cómo se clasificó contablemente cada peso ejercido en la organización.
- **Mantenibilidad:** La limpieza del código resultante de la separación estricta en capas y la ausencia de frameworks pesados permite que el equipo de desarrollo técnico interno herede, mantenga y evolucione la aplicación con una curva de soporte técnica mínima a largo plazo.
- **Independencia tecnológica:** El despliegue on-premise puro y el uso de tecnologías web estándar aseguran la soberanía absoluta de los datos corporativos de la empresa, eliminando costos recurrentes de licenciamiento por volumen de transacciones o dependencias críticas de proveedores de nube externos.

<div style="page-break-after: always;"></div>

---

# Documento de Diseño de Base de Datos: Diccionario de Datos

**Proyecto:** Sistema de Gestión de Gastos Empresariales  
**Enfoque:** Monolítico on-premise, estructurado bajo el patrón MVC nativo en PHP, priorizando un diseño simple, mantenible y libre de sobreingeniería.

---

## 1. Convenciones y Consideraciones Técnicas

- **Motor de Base de Datos:** MySQL / MariaDB (versión 8.0+ / 10.4+) utilizando el motor transaccional **InnoDB** para garantizar el cumplimiento de propiedades ACID.
- **Convención de Nombres:**
  - **Tablas:** En plural utilizando letras minúsculas y notación `snake_case` (ej. `centro_costos`, `facturas_cfdi`).
  - **Columnas:** En singular utilizando `snake_case` (ej. `monto_total`, `fecha_emision`).
  - **Llaves Primarias (PK):** Nombradas de forma homogénea como `id`.
  - **Llaves Foráneas (FK):** Con el sufijo `_id` precedido por el nombre de la tabla relacionada en singular (ej. `usuario_id`).
- **Tipos de Datos Estándar:**
  - **Llaves Primarias (PK) y Foráneas (FK):** `BIGINT UNSIGNED` para garantizar escalabilidad e indexación eficiente.
  - **Montos Monetarios:** `DECIMAL(12,4)` para prevenir errores de redondeo flotante en cálculos financieros.
  - **Fechas y Marcas de Tiempo:** `DATE` para eventos calendáricos y `DATETIME` para auditoría temporal exacta.
  - **Cadenas de Texto Cortas:** `VARCHAR` con codificación de caracteres `utf8mb4_unicode_ci`.
- **Estrategia de Borrado (Soft Delete):** Para asegurar la inmutabilidad y cumplir con la retención mínima obligatoria de 5 años de datos fiscales para auditorías, no se aplicarán borrados físicos (`DELETE`). Se implementará la columna `deleted_at DATETIME NULL` en las tablas maestras y catálogos.
- **Auditoría Básica e Inmutable:** Las tablas operativas contarán con columnas de trazabilidad (`created_at`, `updated_at`, `ip_address`). La tabla de bitácora general funcionará bajo el esquema de inserciones puras (`INSERT` únicamente), bloqueando `UPDATE` y `DELETE` a nivel de privilegios del motor.
- **Manejo de Datos Sensibles (Cifrado Obligatorio):** Las contraseñas de los usuarios se almacenarán mediante el hash criptográfico robusto **BCRYPT** generado nativamente por PHP, requiriendo un almacenamiento fijo de `VARCHAR(255)`.

---

## 2. Diccionario de Datos por Tabla

### Tabla: roles

**Descripción general:** Define los perfiles o niveles de acceso del sistema que determinan dinámicamente los privilegios y menús del usuario (Capturista, Jefe de Área, Cuentas por Pagar o Administrador).  
**Relaciones:** Conectada con `usuarios` (1 a Muchos).  
**Llaves e Índices:** PK: `id` | Unique: `codigo`.

| Nombre         | Tipo            | Descripción                                                                         | Atributos        |
| :------------- | :-------------- | :---------------------------------------------------------------------------------- | :--------------- |
| **id**         | BIGINT UNSIGNED | Identificador único autoincremental de la entidad.                                  | PK, AI, NOT NULL |
| **nombre**     | VARCHAR(50)     | Nombre legible del rol (ej. Capturista, Jefe de Área).                              | NOT NULL         |
| **codigo**     | VARCHAR(30)     | Clave única corta para validaciones en código PHP (ej. 'CAP', 'JEF', 'CXP', 'ADM'). | UNIQUE, NOT NULL |
| **created_at** | DATETIME        | Fecha y hora de creación del registro.                                              | NOT NULL         |
| **updated_at** | DATETIME        | Fecha y hora de la última modificación.                                             | NULL             |
| **deleted_at** | DATETIME        | Control de borrado lógico de perfiles.                                              | NULL             |

---

### Tabla: areas

**Descripción general:** Catálogo maestro de las divisiones funcionales de la compañía que ejercen los recursos financieros de la organización.  
**Relaciones:** Conectada con `usuarios` (1 a Muchos) y con `centro_costos` (1 a Muchos).  
**Llaves e Índices:** PK: `id`.

| Nombre         | Tipo            | Descripción                                                          | Atributos        |
| :------------- | :-------------- | :------------------------------------------------------------------- | :--------------- |
| **id**         | BIGINT UNSIGNED | Identificador único autoincremental de la división de la empresa.    | PK, AI, NOT NULL |
| **nombre**     | VARCHAR(100)    | Nombre oficial del área (ej. Ventas, Tecnologías de la Información). | NOT NULL         |
| **created_at** | DATETIME        | Sello de tiempo de inserción del registro.                           | NOT NULL         |
| **updated_at** | DATETIME        | Sello de tiempo de actualización del registro.                       | NULL             |
| **deleted_at** | DATETIME        | Control de borrado lógico de áreas.                                  | NULL             |

---

### Tabla: usuarios

**Descripción general:** Registro de los colaboradores autorizados para interactuar con la aplicación web, vinculados rígidamente a un área de la organización.  
**Relaciones:** Conectada con `roles` (Muchos a 1), `areas` (Muchos a 1) y `gastos` (1 a Muchos).  
**Llaves e Índices:** PK: `id` | FK: `rol_id`, `area_id` | Unique: `email`.

| Nombre         | Tipo            | Descripción                                                      | Atributos        |
| :------------- | :-------------- | :--------------------------------------------------------------- | :--------------- |
| **id**         | BIGINT UNSIGNED | Identificador único autoincremental del usuario.                 | PK, AI, NOT NULL |
| **rol_id**     | BIGINT UNSIGNED | Referencia al rol/perfil para el control de accesos RBAC.        | FK, NOT NULL     |
| **area_id**    | BIGINT UNSIGNED | Vínculo estricto que limita al usuario a operar solo en su área. | FK, NOT NULL     |
| **nombre**     | VARCHAR(150)    | Nombre completo del colaborador.                                 | NOT NULL         |
| **email**      | VARCHAR(100)    | Correo electrónico institucional utilizado para el login.        | UNIQUE, NOT NULL |
| **password**   | VARCHAR(255)    | Hash seguro de la contraseña cifrada mediante BCRYPT.            | NOT NULL         |
| **created_at** | DATETIME        | Fecha de registro del usuario en la plataforma.                  | NOT NULL         |
| **updated_at** | DATETIME        | Sello de última edición de datos del perfil.                     | NULL             |
| **deleted_at** | DATETIME        | Baja lógica para deshabilitar usuarios sin romper históricos.    | NULL             |

---

### Tabla: centro_costos

**Descripción general:** Unidades contables específicas asociadas a las áreas funcionales que permiten segmentar y rastrear con precisión el destino del capital ejercido.  
**Relaciones:** Conectada con `areas` (Muchos a 1), `presupuestos` (1 a Muchos) y `gastos` (1 a Muchos).  
**Llaves e Índices:** PK: `id` | FK: `area_id` | Unique: `codigo_contable`.

| Nombre              | Tipo            | Descripción                                                         | Atributos        |
| :------------------ | :-------------- | :------------------------------------------------------------------ | :--------------- |
| **id**              | BIGINT UNSIGNED | Identificador único autoincremental del centro de costos.           | PK, AI, NOT NULL |
| **area_id**         | BIGINT UNSIGNED | Relación jerárquica con el área funcional propietaria del recurso.  | FK, NOT NULL     |
| **codigo_contable** | VARCHAR(20)     | Código alfanumérico agrupador contable para reportes financieros.   | UNIQUE, NOT NULL |
| **nombre**          | VARCHAR(100)    | Descripción clara de la unidad (ej. Subárea de Desarrollo Backend). | NOT NULL         |
| **created_at**      | DATETIME        | Registro de creación del centro contable.                           | NOT NULL         |
| **updated_at**      | DATETIME        | Registro de modificaciones de estructura.                           | NULL             |
| **deleted_at**      | DATETIME        | Borrado lógico del catálogo base.                                   | NULL             |

---

### Tabla: presupuestos

**Descripción general:** Almacena los techos financieros mensuales asignados numéricamente a cada centro de costos. El saldo consumido se calcula en tiempo real vía `SUM()` sobre la tabla de gastos aprobados.  
**Relaciones:** Conectada con `centro_costos` (Muchos a 1).  
**Llaves e Índices:** PK: `id` | FK: `centro_costos_id` | Unique Compuesto: `centro_costos_id` + `periodo_mes` + `periodo_anio`.

| Nombre               | Tipo              | Descripción                                                    | Atributos        |
| :------------------- | :---------------- | :------------------------------------------------------------- | :--------------- |
| **id**               | BIGINT UNSIGNED   | Identificador único del techo presupuestal asignado.           | PK, AI, NOT NULL |
| **centro_costos_id** | BIGINT UNSIGNED   | Relación con la unidad contable que ejercerá los fondos.       | FK, NOT NULL     |
| **periodo_mes**      | TINYINT UNSIGNED  | Número de mes calendario asignado (1 al 12).                   | NOT NULL         |
| **periodo_anio**     | SMALLINT UNSIGNED | Año fiscal correspondiente al presupuesto (ej. 2026).          | NOT NULL         |
| **monto_assigned**   | DECIMAL(12,4)     | Límite o frontera máxima monetaria autorizada para el periodo. | NOT NULL         |
| **created_at**       | DATETIME          | Fecha en que el Administrador fijó el presupuesto.             | NOT NULL         |
| **updated_at**       | DATETIME          | Sello temporal si ocurre alguna reasignación de fondos.        | NULL             |

---

### Tabla: catalogo_cuentas

**Descripción general:** Contiene el listado de subcuentas contables de naturaleza deudora (Grupo 600) requeridas para la clasificación final de los egresos.  
**Relaciones:** Conectada con `gastos` (1 a Muchos).  
**Llaves e Índices:** PK: `id` | Unique: `numero_cuenta`.

| Nombre            | Tipo            | Descripción                                                                 | Atributos        |
| :---------------- | :-------------- | :-------------------------------------------------------------------------- | :--------------- |
| **id**            | BIGINT UNSIGNED | Identificador único autoincremental de la cuenta contable.                  | PK, AI, NOT NULL |
| **numero_cuenta** | VARCHAR(30)     | Código de la subcuenta conforme al catálogo de la empresa (ej. 601-01-002). | UNIQUE, NOT NULL |
| **descripcion**   | VARCHAR(150)    | Nombre de la cuenta (ej. Viáticos y Gastos de Viaje, Papelería).            | NOT NULL         |
| **created_at**    | DATETIME        | Registro de inserción en el catálogo contable base.                         | NOT NULL         |
| **updated_at**    | DATETIME        | Última modificación del catálogo.                                           | NULL             |
| **deleted_at**    | DATETIME        | Borrado lógico del catálogo contable.                                       | NULL             |

---

### Tabla: estatus_gastos

**Descripción general:** Catálogo cerrado que define los estados secuenciales del ciclo de vida de un gasto.  
**Relaciones:** Conectada con `gastos` (1 a Muchos).  
**Llaves e Índices:** PK: `id` | Unique: `codigo`.

| Nombre     | Tipo            | Descripción                                                                               | Atributos        |
| :--------- | :-------------- | :---------------------------------------------------------------------------------------- | :--------------- |
| **id**     | BIGINT UNSIGNED | Identificador único del estatus.                                                          | PK, AI, NOT NULL |
| **nombre** | VARCHAR(50)     | Nombre legible (Borrador, Pendiente de Aprobación, Aprobado, Rechazado).                  | NOT NULL         |
| **codigo** | VARCHAR(30)     | Clave única de control en backend (ej. 'BORRADOR', 'PENDIENTE', 'APROBADO', 'RECHAZADO'). | UNIQUE, NOT NULL |

---

### Tabla: facturas_cfdi

**Descripción general:** Entidad tecnológica que almacena la información fiscal extraída de manera automatizada del archivo XML (CFDI) cargado por el usuario, protegiendo su inmutabilidad histórica.  
**Relaciones:** Conectada de forma unívoca (1 a 1) con la tabla `gastos`.  
**Llaves e Índices:** PK: `id` | Unique: `uuid`.

| Nombre                  | Tipo            | Descripción                                                            | Atributos        |
| :---------------------- | :-------------- | :--------------------------------------------------------------------- | :--------------- |
| **id**                  | BIGINT UNSIGNED | Identificador único transaccional de la factura.                       | PK, AI, NOT NULL |
| **uuid**                | VARCHAR(36)     | Folio Fiscal Único Universal de la factura del SAT.                    | UNIQUE, NOT NULL |
| **emisor_rfc**          | VARCHAR(13)     | RFC del proveedor emisor extraído automáticamente del XML.             | NOT NULL         |
| **emisor_razon_social** | VARCHAR(250)    | Nombre comercial o razón social histórica del emisor en el XML.        | NOT NULL         |
| **receptor_rfc**        | VARCHAR(13)     | RFC de la empresa organización que recibe el gasto.                    | NOT NULL         |
| **monto_subtotal**      | DECIMAL(12,4)   | Subtotal financiero antes de impuestos desglosados del XML.            | NOT NULL         |
| **monto_iva**           | DECIMAL(12,4)   | Impuesto al Valor Agregado parseado del CFDI.                          | NOT NULL         |
| **monto_total**         | DECIMAL(12,4)   | Suma neta final del comprobante fiscal XML.                            | NOT NULL         |
| **fecha_emision**       | DATE            | Fecha de expedición formal de la factura por el SAT.                   | NOT NULL         |
| **xml_file_path**       | VARCHAR(510)    | Ruta absoluta local o índice seguro de almacenamiento del archivo XML. | NOT NULL         |
| **created_at**          | DATETIME        | Sello temporal exacto del procesamiento en el servidor.                | NOT NULL         |

---

### Tabla: gastos

**Descripción general:** Entidad transaccional central. Modela cada erogación individual, asociándola a un responsable, un centro de costos, una subcuenta y, opcionalmente, a su comprobante fiscal CFDI.  
**Relaciones:** Conectada con `usuarios`, `centro_costos`, `catalogo_cuentas`, `estatus_gastos` y `facturas_cfdi` (1 a 1, nullable si es gasto menor manual).  
**Llaves e Índices:** PK: `id` | FK: `usuario_capturista_id`, `centro_costos_id`, `cuenta_contable_id`, `estatus_gasto_id`, `factura_cfdi_id` (UNIQUE), `usuario_aprobador_jefe_id`, `usuario_aprobador_cxp_id`.

| Nombre                        | Tipo            | Descripción                                                                | Atributos        |
| :---------------------------- | :-------------- | :------------------------------------------------------------------------- | :--------------- |
| **id**                        | BIGINT UNSIGNED | Código único autoincremental de la transacción de gasto.                   | PK, AI, NOT NULL |
| **usuario_capturista_id**     | BIGINT UNSIGNED | ID del colaborador que originó y capturó el egreso.                        | FK, NOT NULL     |
| **centro_costos_id**          | BIGINT UNSIGNED | Unidad contable a la que impacta financieramente el gasto.                 | FK, NOT NULL     |
| **cuenta_contable_id**        | BIGINT UNSIGNED | Clasificación en la subcuenta contable del catálogo (Grupo 600).           | FK, NOT NULL     |
| **estatus_gasto_id**          | BIGINT UNSIGNED | Estado corriente del ciclo de autorización del egreso.                     | FK, NOT NULL     |
| **factura_cfdi_id**           | BIGINT UNSIGNED | ID del CFDI asociado. Nullable si es gasto manual de caja chica.           | FK, UNIQUE, NULL |
| **monto_total**               | DECIMAL(12,4)   | Importe total ejercido en la transacción (extraído de XML o manual).       | NOT NULL         |
| **fecha_gasto**               | DATE            | Fecha en que se efectuó físicamente la erogación.                          | NOT NULL         |
| **concepto_descripcion**      | VARCHAR(255)    | Justificación u objeto operativo detallado del gasto.                      | NOT NULL         |
| **comentarios_rechazo**       | VARCHAR(500)    | Retroalimentación obligatoria ingresada por el jefe en caso de rechazo.    | NULL             |
| **folio_contable_interno**    | VARCHAR(50)     | Identificador asignado por Cuentas por Pagar antes de la dispersión final. | NULL             |
| **usuario_aprobador_jefe_id** | BIGINT UNSIGNED | ID del jefe de área que emitió la primera validación administrativa.       | FK, NULL         |
| **usuario_aprobador_cxp_id**  | BIGINT UNSIGNED | ID del analista de Cuentas por Pagar que validó la póliza final.           | FK, NULL         |
| **created_at**                | DATETIME        | Sello temporal automático de auditoría de creación.                        | NOT NULL         |
| **updated_at**                | DATETIME        | Sello temporal de última mutación del registro.                            | NULL             |

---

### Tabla: bitacora_auditoria

**Descripción general:** Historial inalterable de marcas de tiempo, direcciones IP y acciones realizadas sobre los gastos para mitigar riesgos de fraude. Restringida a operaciones exclusivas `INSERT`.  
**Llaves e Índices:** PK: `id` | FK: `gasto_id`, `usuario_id`.

| Nombre                      | Tipo            | Descripción                                                         | Atributos        |
| :-------------------------- | :-------------- | :------------------------------------------------------------------ | :--------------- |
| **id**                      | BIGINT UNSIGNED | Identificador único autoincremental del log de eventos.             | PK, AI, NOT NULL |
| **gasto_id**                | BIGINT UNSIGNED | ID del registro de gasto sobre el cual se ejecutó la acción.        | FK, NOT NULL     |
| **usuario_id**              | BIGINT UNSIGNED | ID del usuario que accionó el sistema.                              | FK, NOT NULL     |
| **accion_realizada**        | VARCHAR(100)    | Descripción de la operación (ej. 'CREAR_BORRADOR', 'APROBAR_JEFE'). | NOT NULL         |
| **valores_anteriores_json** | TEXT            | Estado previo de los datos modificados en formato JSON.             | NULL             |
| **valores_nuevos_json**     | TEXT            | Estado posterior de los datos tras el cambio en formato JSON.       | NOT NULL         |
| **ip_address**              | VARCHAR(45)     | Dirección IP de origen de la petición (IPv4 o IPv6).                | NOT NULL         |
| **created_at**              | DATETIME        | Sello de tiempo inalterable del momento exacto del evento.          | NOT NULL         |

---

## 3. Reglas de Integridad y Validaciones del Modelo

1.  **Prevención de Duplicidad de Facturas (UUID):** El campo `uuid` en la tabla `facturas_cfdi` es estrictamente `UNIQUE`. Si se intenta duplicar la carga, el motor transaccional rechazará la inserción automáticamente.
2.  **Validación de Presupuesto en Tiempo Real:** Al cambiar una solicitud de gasto a 'Pendiente de Aprobación', el servicio del backend validará en tiempo real que el Presupuesto Asignado del mes sea mayor o igual a la suma de los gastos ya aprobados más el gasto propuesto. En caso contrario, se bloqueo la transición.
3.  **Encadenamiento de Estados:** Un gasto no puede registrar un `folio_contable_interno` ni un revisor final de Cuentas por Pagar a menos que su `estatus_gasto_id` corresponda al código 'APROBADO'.
4.  **Vinculación Rígida de Captura:** El sistema inyectará por sesión activa el `centro_costos_id` relacionado al usuario capturista, impidiendo que a nivel frontend se altere o afecte el presupuesto de departamentos ajenos.

---

## 4. Índices Recomendados

1.  **`idx_gastos_presupuesto (centro_costos_id, estatus_gasto_id, fecha_gasto, monto_total)`:** Optimiza críticamente la consulta agregada `SUM()` ejecutada periódicamente en tiempo real para evaluar el techo presupuestal sin lecturas completas de tabla.
2.  **`idx_gastos_busqueda (fecha_gasto, estatus_gasto_id)`:** Acelera el motor de filtros avanzados y la exportación de sábanas operativas a formatos CSV o Excel.

## </p>

# Estructura Backend Empresarial - Gestión y Comprobación de Gastos

```text
empresa-gastos-platform/                          ← carpeta root principal desplegada en dominio/subdominio
├── .env                                          ← variables de entorno del sistema
├── .env.example                                  ← plantilla de variables de entorno
├── .gitignore                                    ← exclusión de archivos sensibles y temporales
├── README.md                                     ← documentación general del proyecto
├── composer.json                                 ← dependencias y autoload PSR-4
├── composer.lock                                 ← bloqueo de versiones de dependencias
├── phpunit.xml                                   ← configuración de pruebas unitarias
├── docker-compose.yml                            ← orquestación local opcional de servicios
├── Makefile                                      ← automatización de tareas comunes
├── LICENSE                                       ← licencia del proyecto
├── logs/                                         ← logs globales fuera del runtime público
│   ├── api/                                      ← logs del backend API
│   ├── jobs/                                     ← logs de procesos programados
│   └── security/                                 ← logs de eventos de seguridad
├── storage/                                      ← almacenamiento persistente no público
│   ├── uploads/                                  ← archivos cargados por usuarios
│   │   ├── xml/                                  ← XML CFDI almacenados
│   │   ├── invoices/                             ← comprobantes PDF e imágenes
│   │   ├── temp/                                 ← archivos temporales
│   │   └── rejected/                             ← archivos inválidos o rechazados
│   ├── exports/                                  ← reportes exportados
│   ├── cache/                                    ← cache de aplicación
│   ├── sessions/                                 ← sesiones locales si aplica
│   └── backups/                                  ← respaldos locales
├── scripts/                                      ← scripts utilitarios y automatización
│   ├── deploy/                                   ← scripts de despliegue
│   ├── maintenance/                              ← tareas de mantenimiento
│   ├── migration/                                ← scripts auxiliares de migración
│   └── seeders/                                  ← scripts de carga inicial
├── docs/                                         ← documentación técnica global
│   ├── architecture/                             ← diagramas y decisiones arquitectónicas
│   ├── business-rules/                           ← reglas funcionales documentadas
│   ├── api-contracts/                            ← contratos OpenAPI exportados
│   ├── security/                                 ← políticas y lineamientos de seguridad
│   └── deployment/                               ← guías de instalación y despliegue
├── web/                                          ← frontend separado consumiendo la API
│   └── ...                                       ← frontend no detallado en esta fase
└── api/                                          ← backend API empresarial
    ├── public/                                   ← único punto de acceso público
    │   ├── index.php                             ← front controller principal
    │   ├── .htaccess                             ← reglas Apache/Nginx rewrite
    │   └── health.php                            ← endpoint simple de healthcheck
    ├── bootstrap/                                ← inicialización de la aplicación
    │   ├── app.php                               ← arranque principal de la app
    │   ├── container.php                         ← contenedor de dependencias
    │   ├── environment.php                       ← carga y validación de entorno
    │   ├── routes.php                            ← registro global de rutas
    │   ├── middleware.php                        ← registro global de middlewares
    │   ├── exceptions.php                        ← manejo global de excepciones
    │   └── providers.php                         ← carga de proveedores internos
    ├── config/                                   ← configuraciones centralizadas
    │   ├── app.php                               ← configuración general
    │   ├── auth.php                              ← autenticación y sesiones
    │   ├── cache.php                             ← configuración de cache
    │   ├── cors.php                              ← políticas CORS
    │   ├── database.php                          ← conexiones a BD
    │   ├── filesystems.php                       ← manejo de archivos
    │   ├── logging.php                           ← configuración de logs
    │   ├── mail.php                              ← configuración de correo
    │   ├── queue.php                             ← configuración de colas
    │   ├── security.php                          ← políticas de seguridad
    │   ├── swagger.php                           ← configuración OpenAPI
    │   └── services.php                          ← configuración de servicios externos
    ├── routes/                                   ← definición segmentada de rutas API
    │   ├── api.php                               ← agregador principal de rutas
    │   ├── auth.routes.php                       ← rutas autenticación
    │   ├── users.routes.php                      ← rutas usuarios
    │   ├── roles.routes.php                      ← rutas roles
    │   ├── areas.routes.php                      ← rutas áreas
    │   ├── cost-centers.routes.php               ← rutas centros de costo
    │   ├── budgets.routes.php                    ← rutas presupuestos
    │   ├── expenses.routes.php                   ← rutas gastos
    │   ├── approvals.routes.php                  ← rutas flujo de autorización
    │   ├── reports.routes.php                    ← rutas reportes
    │   ├── catalogs.routes.php                   ← rutas catálogos
    │   └── audit.routes.php                      ← rutas auditoría
    ├── app/                                      ← núcleo funcional de la API
    │   ├── Core/                                 ← clases base y componentes internos
    │   ├── Middleware/                           ← middlewares HTTP globales
    │   ├── Exceptions/                           ← excepciones personalizadas
    │   ├── Shared/                               ← componentes reutilizables transversales
    │   ├── Modules/                              ← módulos organizados por feature
    │   ├── Infrastructure/                       ← infraestructura técnica
    │   └── Console/                              ← comandos CLI internos
    ├── database/                                 ← capa base de datos
    │   ├── migrations/                           ← migraciones versionadas
    │   ├── seeders/                              ← datos iniciales
    │   ├── factories/                            ← factories testing
    │   ├── views/                                ← vistas SQL
    │   ├── procedures/                           ← stored procedures opcionales
    │   └── dumps/                                ← snapshots controlados
    ├── docs/                                     ← documentación exclusiva API
    │   ├── openapi/                              ← especificaciones OpenAPI
    │   ├── postman/                              ← colecciones Postman
    │   ├── sequence-diagrams/                    ← diagramas secuencia
    │   └── changelog/                            ← historial cambios API
    ├── tests/                                    ← pruebas automatizadas
    │   ├── Unit/                                 ← pruebas unitarias
    │   ├── Feature/                              ← pruebas funcionales API
    │   ├── Integration/                          ← pruebas integración
    │   ├── Security/                             ← pruebas seguridad
    │   ├── Performance/                          ← pruebas rendimiento
    │   ├── Fixtures/                             ← datos pruebas
    │   └── TestCase.php                          ← clase base testing
    ├── resources/                                ← recursos internos API
    │   ├── lang/                                 ← traducciones mensajes
    │   ├── templates/                            ← plantillas correo/exportes
    │   └── stubs/                                ← plantillas generación código
    ├── tmp/                                      ← archivos temporales runtime
    │   ├── cache/                                ← cache temporal
    │   ├── sessions/                             ← sesiones temporales
    │   └── uploads/                              ← uploads temporales
    └── vendor/                                   ← dependencias Composer
```

---

# Estructura Frontend Empresarial - Gestión y Comprobación de Gastos

```text
empresa-gastos-platform/                              ← carpeta root principal desplegada en dominio/subdominio
├── .env                                              ← variables frontend y endpoints base
├── .env.example                                      ← plantilla variables frontend
├── .gitignore                                        ← exclusión archivos temporales
├── README.md                                         ← documentación general frontend
├── package.json                                      ← dependencias frontend opcionales
├── package-lock.json                                 ← bloqueo versiones dependencias
├── webpack.config.js                                 ← configuración bundling opcional
├── vite.config.js                                    ← configuración build opcional
├── docs/                                             ← documentación técnica frontend
│   ├── ui-guidelines/                                ← lineamientos visuales UI
│   ├── api-integration/                              ← documentación integración API
│   ├── conventions/                                  ← convenciones de desarrollo
│   ├── components/                                   ← catálogo componentes reutilizables
│   └── deployment/                                   ← documentación despliegue frontend
├── scripts/                                          ← scripts automatización frontend
│   ├── build/                                        ← scripts compilación assets
│   ├── deploy/                                       ← scripts despliegue
│   └── maintenance/                                  ← scripts mantenimiento
├── storage/                                          ← almacenamiento temporal frontend
│   ├── cache/                                        ← cache frontend
│   ├── exports/                                      ← exportaciones temporales
│   └── temp/                                         ← archivos temporales
├── api/                                              ← backend API separado
│   └── ...                                           ← backend no detallado en esta fase
└── web/                                              ← frontend empresarial web
    ├── public/                                       ← archivos públicos accesibles
    │   ├── index.html                                ← punto de entrada principal
    │   ├── favicon.ico                               ← icono aplicación
    │   ├── robots.txt                                ← reglas indexación
    │   ├── manifest.json                             ← configuración PWA opcional
    │   └── assets/                                   ← assets compilados públicos
    ├── src/                                          ← código fuente frontend
    │   ├── assets/                                   ← recursos estáticos organizados
    │   │   ├── images/                               ← imágenes del sistema
    │   │   │   ├── logos/                            ← logos corporativos
    │   │   │   ├── backgrounds/                      ← imágenes de fondo
    │   │   │   ├── icons/                            ← iconografía personalizada
    │   │   │   └── illustrations/                    ← ilustraciones UI
    │   │   ├── fonts/                                ← tipografías locales
    │   │   ├── vendors/                              ← librerías frontend externas
    │   │   │   ├── bootstrap/                        ← Bootstrap local
    │   │   │   ├── jquery/                           ← jQuery local
    │   │   │   ├── datatables/                       ← DataTables local
    │   │   │   ├── sweetalert/                       ← alertas visuales
    │   │   │   └── toastr/                           ← notificaciones toast
    │   │   └── mock-data/                            ← datos mock frontend
    │   ├── styles/                                   ← estilos y presentación
    │   │   ├── base/                                 ← estilos globales
    │   │   │   ├── reset.css                         ← normalización estilos
    │   │   │   ├── typography.css                    ← tipografía global
    │   │   │   ├── variables.css                     ← variables visuales
    │   │   │   ├── animations.css                    ← animaciones reutilizables
    │   │   │   └── utilities.css                     ← utilidades CSS
    │   │   ├── layout/                               ← layouts generales
    │   │   │   ├── header.css                        ← estilos encabezado
    │   │   │   ├── sidebar.css                       ← estilos menú lateral
    │   │   │   ├── footer.css                        ← estilos footer
    │   │   │   ├── navbar.css                        ← estilos navbar
    │   │   │   └── grid.css                          ← estilos estructura responsive
    │   │   ├── components/                           ← estilos componentes reutilizables
    │   │   │   ├── buttons.css                       ← estilos botones
    │   │   │   ├── forms.css                         ← estilos formularios
    │   │   │   ├── tables.css                        ← estilos tablas
    │   │   │   ├── modals.css                        ← estilos modales
    │   │   │   ├── cards.css                         ← estilos cards
    │   │   │   ├── alerts.css                        ← estilos alertas
    │   │   │   └── datatables.css                    ← personalización DataTables
    │   │   ├── pages/                                ← estilos por pantalla
    │   │   │   ├── login.css                         ← estilos login
    │   │   │   ├── dashboard.css                     ← estilos dashboard
    │   │   │   ├── expenses.css                      ← estilos gastos
    │   │   │   ├── approvals.css                     ← estilos aprobaciones
    │   │   │   ├── reports.css                       ← estilos reportes
    │   │   │   ├── catalogs.css                      ← estilos catálogos
    │   │   │   └── administration.css                ← estilos administración
    │   │   └── app.css                               ← archivo maestro de estilos
    │   ├── scripts/                                  ← lógica JavaScript frontend
    │   │   ├── core/                                 ← núcleo frontend
    │   │   │   ├── app.js                            ← inicialización aplicación
    │   │   │   ├── bootstrap.js                      ← configuración Bootstrap
    │   │   │   ├── router.js                         ← manejo navegación frontend
    │   │   │   ├── ajax.js                           ← configuración AJAX global
    │   │   │   ├── events.js                         ← eventos globales
    │   │   │   ├── state.js                          ← estado global simple
    │   │   │   ├── auth.js                           ← manejo autenticación frontend
    │   │   │   ├── permissions.js                    ← control permisos UI
    │   │   │   ├── datatables.js                     ← configuración global DataTables
    │   │   │   └── storage.js                        ← local/session storage
    │   │   ├── services/                             ← consumo API vía AJAX
    │   │   │   ├── api.client.js                     ← cliente AJAX centralizado
    │   │   │   ├── auth.service.js                   ← servicios autenticación
    │   │   │   ├── users.service.js                  ← servicios usuarios
    │   │   │   ├── roles.service.js                  ← servicios roles
    │   │   │   ├── areas.service.js                  ← servicios áreas
    │   │   │   ├── budgets.service.js                ← servicios presupuestos
    │   │   │   ├── expenses.service.js               ← servicios gastos
    │   │   │   ├── approvals.service.js              ← servicios aprobaciones
    │   │   │   ├── reports.service.js                ← servicios reportes
    │   │   │   ├── catalogs.service.js               ← servicios catálogos
    │   │   │   ├── documents.service.js              ← servicios documentos CFDI
    │   │   │   └── audit.service.js                  ← servicios auditoría
    │   │   ├── components/                           ← lógica componentes reutilizables
    │   │   │   ├── layout/                           ← componentes layout
    │   │   │   │   ├── header.component.js           ← lógica encabezado
    │   │   │   │   ├── sidebar.component.js          ← lógica menú lateral
    │   │   │   │   ├── footer.component.js           ← lógica footer
    │   │   │   │   └── navbar.component.js           ← lógica navbar
    │   │   │   ├── tables/                           ← componentes tablas
    │   │   │   │   ├── datatable.component.js        ← wrapper DataTables
    │   │   │   │   └── pagination.component.js       ← paginación tablas
    │   │   │   ├── forms/                            ← componentes formularios
    │   │   │   │   ├── validator.component.js        ← validaciones frontend
    │   │   │   │   ├── uploader.component.js         ← carga archivos/XML
    │   │   │   │   ├── select.component.js           ← selects dinámicos
    │   │   │   │   └── datepicker.component.js       ← calendarios
    │   │   │   ├── modals/                           ← componentes modales
    │   │   │   │   ├── confirm.modal.js              ← modal confirmación
    │   │   │   │   ├── alert.modal.js                ← modal alertas
    │   │   │   │   └── loading.modal.js              ← modal carga
    │   │   │   └── feedback/                         ← feedback visual
    │   │   │       ├── toast.component.js            ← notificaciones toast
    │   │   │       ├── loader.component.js           ← loaders globales
    │   │   │       └── empty-state.component.js      ← estados vacíos
    │   │   ├── pages/                                ← lógica por pantalla
    │   │   │   ├── auth/                             ← lógica autenticación
    │   │   │   │   ├── login.page.js                 ← lógica login
    │   │   │   │   ├── forgot-password.page.js       ← recuperación contraseña
    │   │   │   │   └── reset-password.page.js        ← restablecer contraseña
    │   │   │   ├── dashboard/                        ← lógica dashboard
    │   │   │   │   └── dashboard.page.js             ← lógica panel principal
    │   │   │   ├── expenses/                         ← lógica gastos
    │   │   │   │   ├── expenses-list.page.js         ← listado gastos
    │   │   │   │   ├── expense-create.page.js        ← captura gastos
    │   │   │   │   ├── expense-detail.page.js        ← detalle gasto
    │   │   │   │   ├── expense-edit.page.js          ← edición gasto
    │   │   │   │   └── expense-approval.page.js      ← aprobación/rechazo
    │   │   │   ├── approvals/                        ← lógica bandeja aprobación
    │   │   │   │   └── approvals.page.js             ← lógica aprobaciones
    │   │   │   ├── reports/                          ← lógica reportes
    │   │   │   │   ├── reports.page.js               ← reportes generales
    │   │   │   │   ├── budget-report.page.js         ← reporte presupuestos
    │   │   │   │   └── audit-report.page.js          ← reporte auditoría
    │   │   │   ├── administration/                   ← lógica administración
    │   │   │   │   ├── users.page.js                 ← administración usuarios
    │   │   │   │   ├── roles.page.js                 ← administración roles
    │   │   │   │   ├── areas.page.js                 ← administración áreas
    │   │   │   │   └── budgets.page.js               ← administración presupuestos
    │   │   │   └── catalogs/                         ← lógica catálogos
    │   │   │       ├── catalogs.page.js              ← listado catálogos
    │   │   │       ├── fiscal-catalogs.page.js       ← catálogos fiscales
    │   │   │       └── operational-catalogs.page.js  ← catálogos operativos
    │   │   ├── helpers/                              ← utilitarios frontend
    │   │   │   ├── date.helper.js                    ← utilidades fechas
    │   │   │   ├── currency.helper.js                ← utilidades moneda
    │   │   │   ├── format.helper.js                  ← formatos reutilizables
    │   │   │   ├── validation.helper.js              ← validaciones generales
    │   │   │   ├── file.helper.js                    ← manejo archivos
    │   │   │   ├── xml.helper.js                     ← procesamiento XML frontend
    │   │   │   └── datatable.helper.js               ← helpers DataTables
    │   │   ├── config/                               ← configuraciones frontend
    │   │   │   ├── environment.js                    ← variables entorno
    │   │   │   ├── api.config.js                     ← configuración endpoints API
    │   │   │   ├── routes.config.js                  ← configuración rutas
    │   │   │   ├── datatables.config.js              ← configuración DataTables
    │   │   │   ├── permissions.config.js             ← configuración permisos
    │   │   │   └── ui.config.js                      ← configuración UI
    │   │   ├── interfaces/                           ← contratos datos frontend
    │   │   │   ├── expense.interface.js              ← estructura gasto
    │   │   │   ├── user.interface.js                 ← estructura usuario
    │   │   │   ├── approval.interface.js             ← estructura aprobación
    │   │   │   ├── report.interface.js               ← estructura reportes
    │   │   │   └── api-response.interface.js         ← respuestas API
    │   │   ├── types/                                ← tipos y constantes
    │   │   │   ├── roles.types.js                    ← tipos roles
    │   │   │   ├── expense-status.types.js           ← estados gasto
    │   │   │   ├── approval.types.js                 ← tipos aprobación
    │   │   │   ├── alerts.types.js                   ← tipos alertas
    │   │   │   └── datatable.types.js                ← tipos DataTables
    │   │   ├── context/                              ← contexto frontend compartido
    │   │   │   ├── auth.context.js                   ← contexto autenticación
    │   │   │   ├── user.context.js                   ← usuario actual
    │   │   │   ├── permissions.context.js            ← permisos sesión
    │   │   │   └── layout.context.js                 ← estado layout
    │   │   └── router/                               ← navegación frontend
    │   │       ├── routes.js                         ← definición rutas
    │   │       ├── guards.js                         ← validaciones acceso
    │   │       └── navigation.js                     ← helpers navegación
    │   ├── components/                               ← componentes HTML reutilizables
    │   │   ├── layout/                               ← layout compartido
    │   │   │   ├── header.html                       ← encabezado global
    │   │   │   ├── sidebar.html                      ← menú lateral
    │   │   │   ├── footer.html                       ← pie página
    │   │   │   └── navbar.html                       ← barra navegación
    │   │   ├── tables/                               ← tablas reutilizables
    │   │   │   ├── expenses-table.html               ← tabla gastos
    │   │   │   ├── approvals-table.html              ← tabla aprobaciones
    │   │   │   └── users-table.html                  ← tabla usuarios
    │   │   ├── forms/                                ← formularios reutilizables
    │   │   │   ├── expense-form.html                 ← formulario gasto
    │   │   │   ├── budget-form.html                  ← formulario presupuesto
    │   │   │   └── user-form.html                    ← formulario usuario
    │   │   ├── modals/                               ← modales reutilizables
    │   │   │   ├── confirm-modal.html                ← modal confirmación
    │   │   │   ├── loading-modal.html                ← modal carga
    │   │   │   └── alert-modal.html                  ← modal alertas
    │   │   └── feedback/                             ← componentes visuales feedback
    │   │       ├── loader.html                       ← loader global
    │   │       ├── empty-state.html                  ← estado vacío
    │   │       └── no-results.html                   ← sin resultados
    │   ├── pages/                                    ← pantallas HTML completas
    │   │   ├── auth/
    │   │   │   ├── login.html                        ← pantalla login
    │   │   │   ├── forgot-password.html              ← recuperación contraseña
    │   │   │   └── reset-password.html               ← restablecer contraseña
    │   │   ├── dashboard/
    │   │   │   └── dashboard.html                    ← panel principal
    │   │   ├── expenses/
    │   │   │   ├── expenses-list.html                ← listado gastos
    │   │   │   ├── expense-create.html               ← captura gastos
    │   │   │   ├── expense-detail.html               ← detalle gasto
    │   │   │   ├── expense-edit.html                 ← edición gasto
    │   │   │   └── expense-approval.html             ← aprobación gasto
    │   │   ├── approvals/
    │   │   │   └── approvals.html                    ← bandeja aprobaciones
    │   │   ├── reports/
    │   │   │   ├── reports.html                      ← reportes generales
    │   │   │   ├── budget-report.html                ← reporte presupuestos
    │   │   │   └── audit-report.html                 ← reporte auditoría
    │   │   ├── administration/
    │   │   │   ├── users.html                        ← administración usuarios
    │   │   │   ├── roles.html                        ← administración roles
    │   │   │   ├── areas.html                        ← administración áreas
    │   │   │   └── budgets.html                      ← administración presupuestos
    │   │   └── catalogs/
    │   │       ├── catalogs.html                     ← listado catálogos
    │   │       ├── fiscal-catalogs.html              ← catálogos fiscales
    │   │       └── operational-catalogs.html         ← catálogos operativos
    │   └── app.html                                  ← plantilla base aplicación
    ├── tests/                                        ← pruebas frontend
    │   ├── unit/                                     ← pruebas unitarias JS
    │   ├── integration/                              ← pruebas integración frontend
    │   ├── e2e/                                      ← pruebas end-to-end
    │   ├── mocks/                                    ← mocks API
    │   └── fixtures/                                 ← fixtures frontend
    ├── build/                                        ← archivos compilados
    │   ├── css/                                      ← CSS compilado
    │   ├── js/                                       ← JS compilado
    │   ├── images/                                   ← imágenes optimizadas
    │   └── vendors/                                  ← vendors compilados
    └── logs/                                         ← logs frontend
        ├── errors/                                   ← errores JS capturados
        └── performance/                              ← métricas rendimiento
```
