# Preguntas del Analista — FEAT-023

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-023  
**Origen:** `@agent-sj` (2026-08-04) — captura delegada de indicadores (suplencia vacaciones).

En Operaciones, los jefes capturadores (`operations.capture`) registran indicadores FT-OP mensualmente. Cuando salen de vacaciones, otra persona del área debe ingresar datos **a nombre del jefe titular**, no del suplente.

**Estado actual del código (referencia):**

| Aspecto | Comportamiento hoy |
| --- | --- |
| Captura | `IndicadorController::show` / `storeCapture` usa siempre `$request->user()` como dueño (`indicator_captures.user_id`). |
| Modelo | `IndicatorCapture` tiene `user_id`, `created_by_user_id`, `updated_by_user_id`; los tres apuntan al mismo usuario al guardar. |
| Listado capturadores | `IndicatorCaptureAccessService::capturableUsers()` — usuarios activos del área Operaciones con `operations.capture` o `operations.manage`. |
| Consolidado | Filtro por capturador (solo lectura, `operations.manage`). |
| Doc técnica | [`docs/modules/indicadores.md`](../modules/indicadores.md) |

**Decisiones ya confirmadas por el usuario (AgentSj / negocio — no re-preguntar):**

| # | Tema | Decisión |
| --- | --- | --- |
| D-1 | Quién recibe el permiso | Solo personal del área Operaciones (usuarios activos con `area_key=operaciones`). |
| D-2 | Permiso del suplente | Solo el permiso nuevo; **no** necesita `operations.capture`. |
| D-3 | Alcance del selector | Puede capturar por **cualquier capturador activo** (lista `capturableUsers()`), sin asignaciones temporales por persona. |
| D-4 | `operations.manage` | **No** incluye automáticamente esta capacidad; permiso **aparte y más acotado**. |
| D-5 | Modelo de datos | `user_id` = titular/capturador; `created_by_user_id` / `updated_by_user_id` = quien digitó (suplente). |

---

## Preguntas abiertas

Responde cada punto para cerrar el brief:

### 1. Objetivo y usuarios

**¿Quién usará esta funcionalidad y qué resultado espera?**

**Respuesta (cerrada):** Personal de Operaciones designado como suplente durante vacaciones u otras ausencias de los jefes capturadores. Debe poder abrir la pantalla de captura mensual FT-OP, elegir al **titular** en un selector y guardar la captura de forma que el registro quede asociado al jefe ausente (ranking, consolidado por capturador, dashboard y exportaciones por usuario siguen reflejando al titular). El suplente no necesita ser capturador titular (`operations.capture`).

### 2. Alcance

**¿Qué queda explícitamente fuera?**

**Respuesta (cerrada):**

- Asignaciones temporales por persona (fechas de suplencia, calendario, workflow de aprobación).
- Que `operations.manage` otorgue automáticamente captura delegada (sigue siendo permiso independiente).
- Exigir `operations.capture` al suplente.
- Restringir el selector a un subconjunto de capturadores (solo titulares pre-asignados al suplente).
- Cambiar la lógica de consolidado más allá de lo que ya hace con filtro por capturador (solo lectura para `operations.manage`).

### 3. Permisos

**¿Qué roles o permisos existentes aplican? ¿Hay permisos nuevos?**

**Respuesta (cerrada):**

| Permiso | Rol en la feature |
| --- | --- |
| `operations.capture` | Capturador titular: captura **propia** (comportamiento actual, sin selector obligatorio). |
| `operations.manage` | Administración y consolidado; **no** incluye suplencia salvo que se otorgue el permiso nuevo explícitamente. |
| **`operations.capture.delegate`** *(nuevo, propuesto)* | Suplente: captura **a nombre de** cualquier capturador activo de `capturableUsers()`. Solo usuarios activos con `area_key=operaciones`. |

**Etiqueta legible sugerida en `config/access.php`:** `Indicadores: Capturar por suplencia`.

**Asignación:** misma superficie que hoy usa Ajustes → Capturadores (toggle por usuario del área), siguiendo el patrón de `operations.capture`.

### 4. Reglas de negocio

**¿Validaciones, estados, flujos de aprobación?**

**Respuesta (cerrada):**

1. **Titular vs digitador:** al guardar en modo delegado, `user_id` = capturador seleccionado; `created_by_user_id` / `updated_by_user_id` = usuario autenticado (suplente).
2. **Captura propia:** si el usuario tiene `operations.capture` y captura sin delegación (o se selecciona a sí mismo estando en `capturableUsers()`), los tres campos pueden coincidir como hoy.
3. **Selector:** lista = `IndicatorCaptureAccessService::capturableUsers()` (activos, área Operaciones, con `operations.capture` o `operations.manage`).
4. **Validación:** el capturador elegido debe pertenecer a esa lista; de lo contrario, rechazar (404/403).
5. **Periodo cerrado:** misma regla que hoy — no guardar si el periodo está cerrado.
6. **Sin flujo de aprobación** adicional para suplencia (no hay confirmación de titular ni ventana de fechas).
7. **Mejoras / plan de mejora:** heredan la captura del titular (`user_id`); trazabilidad de digitador vía `created_by`/`updated_by` en captura y auditoría.

### 5. Datos

**¿Tablas nuevas o cambios a existentes? ¿Relaciones?**

**Respuesta (cerrada):**

- **Sin migraciones nuevas** — los campos `created_by_user_id` y `updated_by_user_id` ya existen en `indicator_captures`.
- **Cambio de uso:** dejar de copiar siempre el mismo `user_id` en los tres campos; en delegación, separar titular (`user_id`) de digitador (`created_by_*` / `updated_by_*`).
- **Unicidad:** sigue siendo una captura por `(indicator_id, user_id titular, period_id)`.
- **Mejoras (`improvements`):** revisar si `created_by_user_id` debe reflejar suplente en delegación (recomendación analista: sí, alinear con captura).

### 6. Interfaz

**¿Pantallas, acciones, exportaciones, notificaciones?**

**Respuesta (cerrada):**

1. **Pantalla de captura** (`indicadores.show`): agregar selector **Capturador** (dropdown) cuando el usuario tenga `operations.capture.delegate`.
2. **Usuarios solo con `operations.capture`:** sin selector (o selector fijo en el propio usuario); UX igual que hoy.
3. **Usuarios solo con `operations.capture.delegate`:** deben elegir capturador antes de guardar; no pueden capturar “como ellos mismos” si no están en `capturableUsers()`.
4. **Usuarios con ambos permisos:** selector con todos los capturadores; default = usuario autenticado si está en la lista.
5. **Etiqueta visible:** mostrar nombre del capturador titular en cabecera (reemplaza el texto fijo del usuario logueado cuando hay delegación).
6. **Ajustes → Capturadores:** toggle adicional para otorgar/revocar `operations.capture.delegate` (mismo estilo que captura).
7. **Consolidado, exportaciones, dashboard:** sin cambios de UI en v1; ya agregan/filtran por `user_id` titular.
8. **Notificaciones por correo:** fuera de alcance.

### 7. Integraciones

**¿Correo, otros módulos, áreas del negocio?**

**Respuesta (cerrada):**

- Módulo **Indicadores (Operaciones)** únicamente.
- **Auditoría central** (`audit_logs`, módulo `indicadores`): registrar create/update de captura incluyendo metadata de suplencia (titular vs digitador) cuando aplique.
- Sin integración con RRHH, calendario de vacaciones ni otros módulos.
- `config/access.php` y seeders de permisos: registrar permiso nuevo en bloque `area_indicador_permissions.operaciones`.

### 8. Documentación usuario

**¿Hay procedimiento operativo definido por el negocio?**

**Respuesta (cerrada):** No hay procedimiento formal documentado. El Documentador debe redactar en `docs/user/indicadores.md` (o equivalente): (1) quién recibe el permiso de suplencia, (2) cómo capturar eligiendo al titular, (3) que el ranking/consolidado refleja al titular, (4) que la auditoría registra quién digitó.

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| — | *Ninguno — el usuario cerró vacíos antes del Analista.* | — |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

| # | Pregunta (plantilla) | Respuesta |
| --- | --- | --- |
| 1 | Objetivo y usuarios | Suplente de Operaciones captura FT-OP **a nombre del titular** durante vacaciones; resultado = datos del jefe en ranking/consolidado/dashboard. |
| 2 | Alcance / fuera de alcance | Sin asignaciones temporales; sin auto-incluir en `operations.manage`; suplente sin `operations.capture`. |
| 3 | Permisos | Nuevo `operations.capture.delegate`; solo personal activo área Operaciones; etiqueta sugerida «Indicadores: Capturar por suplencia». |
| 4 | Reglas de negocio | Titular en `user_id`; digitador en `created_by`/`updated_by`; selector = todos `capturableUsers()`; sin aprobación. |
| 5 | Datos | Sin migración; usar columnas existentes con semántica corregida. |
| 6 | Interfaz | Selector capturador en pantalla captura; toggle en Ajustes → Capturadores. |
| 7 | Integraciones | Solo indicadores + auditoría; actualizar `config/access.php`. |
| 8 | Doc usuario | Sin procedimiento previo; Documentador redacta guía operativa básica. |

**Referencias de decisiones:** D-1 a D-5 (contexto AgentSj, 2026-08-04).
