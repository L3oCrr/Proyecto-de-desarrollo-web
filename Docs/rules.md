# Project Rules — App Gastos

# Project Rules — Cursor
## App Gastos

## 1. Propósito
Estas reglas son obligatorias para cualquier implementación dentro del proyecto.

Los prompts futuros deben ser breves y solo referirse a:
- backlog item a desarrollar,
- alcance incluido,
- alcance excluido,
- criterios de aceptación específicos,
- aclaraciones especiales del momento.

Si un prompt indica “respeta las reglas del proyecto y revisa `docs`”, Cursor debe aplicar automáticamente este documento.

---

## 2. Fuentes obligatorias
Antes de implementar cualquier tarea, Cursor debe revisar el directorio `docs`.

Orden de prioridad:
1. Prompt actual
2. Este documento
3. Documentos en `docs`
4. Código ya existente

Si hay contradicción:
- no resolver en silencio,
- aplicar el supuesto mínimo más seguro,
- explicarlo brevemente,
- no ampliar alcance.

---

## 3. Contexto fijo del proyecto
- Aplicación web empresarial on-premise.
- Desarrollo incremental por slices pequeños.
- Backend: PHP nativo.
- Arquitectura backend: MVC clásico.
- Frontend: HTML, CSS, Bootstrap, jQuery, DataTables.
- Base de datos: MySQL / MariaDB.
- Sin frameworks full-stack pesados.
- Sin ORM.

Objetivo permanente:
Construir incrementos pequeños, verificables, integrables y mantenibles.

---

## 4. Reglas obligatorias de implementación
Cursor debe siempre:

1. Implementar solo el backlog item solicitado.
2. No adelantar fases posteriores.
3. No inventar reglas de negocio no documentadas.
4. No rediseñar arquitectura sin necesidad real.
5. No refactorizar partes estables salvo que sea indispensable.
6. Mantener el incremento pequeño y verificable.
7. Preferir simplicidad sobre sofisticación.
8. Mantener bajo acoplamiento.
9. Respetar la estructura de carpetas existente.
10. Entregar cambios naturalmente integrables.

---

## 5. Qué no debe hacer Cursor
Cursor no debe:

- implementar módulos no solicitados,
- “aprovechar” para adelantar features futuras,
- meter librerías innecesarias,
- introducir frameworks pesados,
- inventar nombres si ya existen convenciones,
- poner SQL en controladores,
- poner lógica de negocio en repositorios,
- mezclar responsabilidades sin necesidad,
- generar código innecesariamente enterprise,
- responder con cambios incompletos sin explicar qué hizo.

---

## 6. Arquitectura obligatoria
### Backend
- PHP nativo
- MVC clásico
- controladores solo orquestan
- lógica de negocio en servicios
- acceso a datos en repositorios
- consultas parametrizadas
- sin ORM

### Frontend
- presentación separada de lógica
- consumo de API vía AJAX con jQuery
- páginas, components, services, helpers, context y router separados
- no mezclar frontend con lógica de backend

### Persistencia
- MySQL / MariaDB
- consultas parametrizadas obligatorias
- sin concatenar variables en SQL
- integridad básica desde base de datos cuando aplique

---

## 7. Convenciones obligatorias
### Base de datos
- tablas: `snake_case` plural
- columnas: `snake_case`
- PK: `id`
- FK: `tabla_id`

### PHP
- clases en PascalCase
- archivos coherentes con carpetas
- una responsabilidad clara por clase siempre que sea posible

### JavaScript
- variables y funciones en camelCase
- separación clara entre pages, services, helpers y context

### General
- nombres descriptivos
- evitar abreviaturas ambiguas
- respetar nombres definidos en `docs`

---

## 8. Seguridad mínima obligatoria
Aunque el incremento no implemente toda la seguridad, Cursor no debe romper estas bases:

- consultas parametrizadas siempre,
- hashing seguro cuando toque autenticación,
- RBAC en backend, no en frontend,
- escape de salida cuando aplique,
- manejo seguro de XML cuando toque esa fase,
- no exponer información sensible innecesaria.

---

## 9. Manejo de ambigüedad
Si falta definición:

1. no bloquear el avance innecesariamente,
2. tomar el supuesto mínimo más seguro,
3. no inventar complejidad,
4. explicarlo brevemente,
5. no expandir el alcance para resolver otras áreas.

Si una ambigüedad afecta directamente el incremento, mencionarla en la entrega.

---

## 10. Regla de tamaño
Cada tarea debe resolverse como incremento pequeño.

Cursor no debe agrupar por cuenta propia varios bloques grandes de trabajo.

Si un pedido parece amplio, debe resolver solo la parte mínima necesaria para cumplir el objetivo solicitado.

---

## 11. Formato de entrega obligatorio
Salvo que el prompt indique otra cosa, Cursor debe responder en este orden:

### A. Resumen breve de lo implementado
### B. Supuestos mínimos aplicados
### C. Archivos creados o modificados
### D. Código completo
### E. Explicación breve de integración
### F. Pasos de prueba manual
### G. Límites del incremento

---

## 12. Criterio de calidad
Un incremento solo se considera correcto si:
- cumple el alcance pedido,
- no invade backlog items siguientes,
- respeta la arquitectura,
- se integra con lo existente,
- puede probarse manualmente,
- deja base limpia para el siguiente paso.

---

## 13. Texto corto para reutilizar en prompts
Respeta las Project Rules del proyecto y revisa `docs` antes de implementar. Aplica arquitectura, convenciones, restricciones, formato de entrega y criterio de incrementalidad definidos allí. Implementa solo el backlog item solicitado, sin adelantar fases posteriores.