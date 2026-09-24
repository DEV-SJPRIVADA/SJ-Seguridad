# Preguntas del Analista — FEAT-037

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** negocio base entendido desde conversación Ask + propuesta AgentSj; **8 preguntas abiertas** (prioridad alta). **Pendiente respuesta usuario** → AgentSj debe **PAUSAR** antes de Arquitecto.

---

## Contexto recibido

**Feature ID:** FEAT-037  
**Origen:** `@agent-sj` Reporte Diario APO (carga diaria 2 Excel + histórico) — 2026-09-24  
**Módulo:** Acreditaciones (`acreditaciones`) — área Gestión Humana  
**Pestaña:** **Reporte Diario** (hoy placeholder FEAT-036; ruta `GET /gestion-humana/acreditaciones/reporte-diario`)  
**Run log:** [`docs/runs/FEAT-037-run-log.md`](../runs/FEAT-037-run-log.md)  
**Doc técnica base:** [`docs/modules/acreditaciones.md`](../modules/acreditaciones.md)  
**Doc usuario base:** [`docs/user/acreditaciones.md`](../user/acreditaciones.md)

### Archivos APO reales revisados (conversación previa)

| Archivo | Rol | Estructura observada |
| --- | --- | --- |
| `Informacion de Companias en Proceso …xlsx` | En proceso | Fila 1 título; fila 2 headers: Apellido1, Apellido2, Nombre1, Nombre2, IdNum, Cargo, **Estado**; datos desde fila 3 (~60 filas). Estado ejemplo: `VALIDACION Y SOLICITUD` |
| `Informacion de Companias acreditados …xlsx` | Acreditados APO | Misma estructura de nombres + IdNum + Cargo + **Vigen.Acr** (fecha); ~2000 filas |

### Lo que el usuario ya dijo (cerrado / casi cerrado)

| Aspecto | Entendido |
| --- | --- |
| Fuente | Solo personal que sale de la APO (no inventar filas desde Ficha ni desde Acreditados del sistema) |
| Carga | En **Reporte Diario** se suben **2 Excel** diariamente (uno acreditados APO, uno en proceso) |
| Fecha | El usuario elige **fecha de carga**; si esa fecha ya existe → aviso con botón para aceptar y **reemplazar** |
| Histórico | Se guarda histórico; la vista por defecto muestra solo datos del **día actual** |
| Listado | Filtros, búsqueda y export Excel |
| Separación v1 (propuesta) | **No** mezclar con tabla `acreditacion_acreditados` ni lógica de estados del módulo Acreditados en esta pestaña |

### Propuesta técnica esbozada (NO cerrada — validar)

- Tablas tentativas: cabecera de cargas por `fecha_reporte` (única) + filas con origen `PROCESO` \| `ACREDITADO`
- Reemplazo por fecha (cascade)
- DataTables server-side + `BaseExport` / `<x-export-excel>`
- Permisos: ¿reutilizar `acreditaciones.view` / `acreditaciones.edit` o permiso nuevo?
- Sin bridge a Ficha / Acreditados del sistema en v1

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Shell + pestaña placeholder | FEAT-036 — `reporte-diario` → `placeholder.blade.php` |
| Import Excel + reporte fallos | Acreditados (`AcreditacionImportService`) |
| DT server-side + export filtrado | Acreditados / Cursos |
| Selectores / Excel | `<x-searchable-select>`; `BaseExport` + `<x-export-excel>` |
| Datos | Solo migraciones aditivas; sin `migrate:fresh` / wipe |
| Shared-files | Solo si hay permiso nuevo (`config/access.php`); rutas en `routes/areas/gestion_humana.php` |

---

## Resumen de lo entendido

Gestión Humana necesita **operativizar la pestaña Reporte Diario** del tablero Acreditaciones: cada día el operador carga los **dos Excel** que entrega la APO (personas en proceso y personas acreditadas), asocia esa carga a una **fecha de reporte** elegida, puede **reemplazar** si esa fecha ya fue cargada, consulta el **histórico** (por defecto solo el día de hoy), filtra/busca y exporta.

Es un **repositorio histórico de snapshots APO**, distinto del listado operativo **Acreditados** (que vive en `acreditacion_acreditados` con estados calculados, Ficha obligatoria y catálogo CARGO APO).

### Alcance tentativo (pendiente confirmación)

- **Incluye (candidato v1):** UI Reporte Diario; carga de 2 Excel; fecha de reporte; replace con confirmación; histórico; listado DT server-side; filtros/búsqueda; export; audit de mutaciones de carga.
- **Fuera tentativo:** pestañas Dashboard / Validaciones / Export Apo; sincronizar o upsert hacia `acreditacion_acreditados`; notificaciones por correo; soft-delete versionado de cargas (salvo que el usuario lo pida en P7).

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (máx. ~8 — vacíos reales no cubiertos arriba). Lenguaje de negocio.

### 1. Carga: ¿los dos archivos siempre juntos?

Al cargar un día, ¿**siempre** debe subir **los dos** Excel (en proceso + acreditados APO) en la misma operación?

- **A)** Sí: obligatorios los dos; si falta uno, no se guarda nada de ese día.
- **B)** No: puede cargar solo uno (el otro queda vacío o se mantiene el anterior de ese día — aclare cuál).
- **C)** Otro (describa el flujo).

### 2. ¿Qué significa la “fecha de carga”?

Cuando el usuario elige la fecha:

- **A)** Es la **fecha del reporte APO** (día al que corresponden los archivos). Por defecto **hoy**; el usuario puede elegir otra (ej. cargar el viernes el reporte del jueves).
- **B)** Es la **fecha/hora en que se subió** al sistema (automática; el usuario no la elige).
- **C)** Otra regla (describa).

Si es **A**: ¿se permite cargar una fecha **futura**? ¿Y fechas muy antiguas sin límite?

### 3. Histórico: ¿bastan filtros por fecha?

Para consultar días pasados, ¿alcanza con:

- filtro / selector de **fecha de reporte** (y por defecto = hoy),

o necesita además:

- listado de **cargas realizadas** (quién cargó, cuándo, cuántas filas),
- comparar dos días,
- u otra vista?

Responda qué es **imprescindible en v1**.

### 4. ¿Cruzar con Ficha o con Acreditados del sistema?

En esta pestaña, al ver o cargar filas APO:

- **A)** **No cruzar** en v1: se muestran tal cual vienen del Excel (recomendación previa).
- **B)** Sí: marcar o enriquecer si la cédula (`IdNum`) existe en **Ficha** y/o en **Acreditados**.
- **C)** Solo avisar si **no** está en Ficha (sin bloquear la carga).

### 5. Permisos: ¿quién carga y quién solo consulta?

Hoy el módulo usa `acreditaciones.view` (ver/export) y `acreditaciones.edit` (crear/editar/importar).

Para Reporte Diario:

- **A)** Igual que Acreditados: **ver/filtrar/exportar** con consulta; **cargar y reemplazar** solo con edición.
- **B)** Hace falta un **permiso nuevo** solo para cargar el reporte diario (aparte de editar Acreditados/Catálogo).
- **C)** Otro (describa quiénes).

### 6. Pantalla: ¿una tabla o dos?

Tras cargar, ¿cómo prefiere ver los datos del día?

- **A)** **Una sola tabla** con columna **Origen** (En proceso / Acreditado APO), filtros y búsqueda sobre todo.
- **B)** **Dos pestañas/secciones** internas (una por archivo), cada una con su tabla.
- **C)** Ambos (tabla unificada + posibilidad de filtrar solo un origen).

### 7. Al reemplazar un día ya cargado

Si la fecha ya existe y el usuario acepta reemplazar:

- **A)** Se **borra** todo lo de ese día y se guarda solo la nueva carga (no queda versión anterior).
- **B)** Se guarda una **versión anterior** consultable (historial de reemplazos del mismo día).
- **C)** Otro.

### 8. Si el Excel viene mal formado o con filas incompletas

Cuando falte un encabezado esperado, o una fila no tenga cédula / fechas inválidas:

- **A)** **Falla toda la carga** si faltan columnas obligatorias o el formato no coincide (mensaje claro; no se guarda nada).
- **B)** Se cargan las filas **válidas** y se reportan las fallidas (como el import de Acreditados).
- **C)** Mezcla: sin headers correctos → falla todo; filas malas → se saltan con reporte.

También indique: ¿el **Estado** del Excel “en proceso” y la **Vigen.Acr** del Excel “acreditados” se guardan tal cual (texto/fecha) sin recalcular estados del módulo Acreditados?

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Los **2 archivos son obligatorios** en la misma carga (opción 1.A). | Operación diaria bloqueada si un día solo llega un archivo; o se pierde parcialidad que el negocio sí necesita. |
| 2 | La fecha es **fecha del reporte APO**, elegida a mano, default **hoy** (2.A); no se permiten fechas futuras. | Desalineación con cómo nombran los archivos APO; o imposibilidad de cargar atrasados/adelantados. |
| 3 | Histórico v1 = **filtro por fecha de reporte** + metadata mínima de la carga (quién/cuándo); sin comparación día vs día. | Falta de trazabilidad operativa que el negocio esperaba en la primera entrega. |
| 4 | **No cruzar** con Ficha ni `acreditacion_acreditados` en v1 (4.A). | Usuarios esperan validar cédulas o “empujar” a Acreditados desde esta pestaña. |
| 5 | Permisos = **reutilizar** `acreditaciones.view` / `.edit` (5.A); sin permiso nuevo ni tocar paquetes por defecto. | Quien edita Acreditados podrá cargar reportes (o al revés: falta segregación que el negocio pedía). |
| 6 | UI = **una tabla unificada** con columna Origen + filtro por origen (6.A/C). | Pantalla confusa o insuficiente si el negocio piensa en dos listados separados como los Excel. |
| 7 | Replace = **borrado duro del día** y nueva carga (7.A); sin versiones anteriores del mismo día. | Pérdida de evidencia si alguien reemplaza por error y necesitaban recuperar la versión previa. |
| 8 | Headers incorrectos → **falla toda la carga**; filas con datos inválidos → **reporte de fallos** y se guardan solo válidas **solo si** al menos hay un mínimo de filas OK — *o* falla todo si 0 filas válidas (8.C acotado). Columnas APO se persisten **tal cual** (Estado / Vigen.Acr); no se usan calculadoras de Acreditados. | Cargas parciales silenciosas o rechazo excesivo; o contaminación del modelo de estados del módulo Acreditados. |
| 9 | Nombre mostrado = composición Apellido1+Apellido2+Nombre1+Nombre2; identidad = **IdNum**; Cargo = texto libre del Excel. | Si el negocio exige otro orden o campos adicionales, hay que rehacer mapeo/UI. |
| 10 | Pestañas Dashboard / Validaciones / **Export Apo** siguen fuera de FEAT-037. | Confusión si “Export Apo” se interpretaba como este reporte. |
| 11 | Audit: registrar eventos de **carga / reemplazo** (resumen filas ok/fail), no cada GET/listado. | Brecha de auditoría o ruido excesivo en `audit_logs`. |
| 12 | Volumen ~2000+ filas/día → listado **server-side** obligatorio; export respeta filtros activos. | Timeouts o memoria si se asumiera tabla client-side. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

(Completado 2026-09-24 tras pausa AgentSj.)

| # | Respuesta |
| --- | --- |
| 1 | **B** — Puede cargar solo uno de los dos Excel en una operación. |
| 2 | **A** — Fecha del reporte APO, elegible, default hoy. **Límite:** día actual y hacia atrás; **no** permitir fechas futuras. |
| 3 | Filtro por fecha (+ quién/cuándo cargó) **y** botón para **ver listado de cargas**. |
| 4 | Carga **sin bloquear** aunque la cédula no esté en Ficha. **No cruzar** en Reporte Diario v1; el cruce queda para la pestaña **Validaciones** (fuera de FEAT-037). |
| 5 | **A** — Reutilizar `acreditaciones.view` / `acreditaciones.edit`. |
| 6 | **A** + filtro por origen — una sola tabla con columna Origen y filtro En proceso / Acreditado APO / Todos. |
| 7 | **A** — Al reemplazar: borrar lo de ese día (origen(es) afectados) y guardar lo nuevo; sin versiones anteriores del mismo día. |
| 8 | Formato APO estable. Filas con error: **saltar, seguir y reportar**. No recalcular estados del módulo Acreditados; persistir Estado / Vigen.Acr tal cual. |

### Aclaraciones de interpretación (para Arquitecto)

1. **Carga parcial (1.B):** si el usuario sube solo un archivo para una `fecha_reporte` que ya tiene el otro origen, **conservar** el origen no enviado y **reemplazar** solo el origen del archivo subido. Si es día nuevo y solo llega un archivo, el otro origen queda vacío.
2. **Replace (7.A) + parcial:** el aviso “ya existe fecha” aplica cuando ya hay carga para esa fecha; al confirmar, se reemplazan solo los orígenes incluidos en la subida (si vienen ambos, se reemplaza el día completo de ambos).
3. **Pregunta 4:** aunque el usuario marcó B, el texto aclara “permitir carga de todos aunque no esté en ficha” y “no hacer cruce; eso después en Validaciones” → en FEAT-037 = **sin enriquecimiento/cruce**; solo snapshot APO.
4. **Validación (8):** no exigir Ficha; filas inválidas (ej. sin IdNum) → skip + reporte; no fallar toda la carga por filas sueltas. Headers esperados: si el archivo no trae el formato conocido, falla ese archivo con mensaje claro.

---

## Instrucción a AgentSj

1. Respuestas cerradas → lanzar **Arquitecto** para Feature Brief final `docs/briefs/FEAT-037.md`.
2. **No** implementar código desde Analista.
