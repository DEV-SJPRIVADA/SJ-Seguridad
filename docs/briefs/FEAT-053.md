# Feature Brief — FEAT-053

> Brief del maestro (2026-11-20). Decisiones del usuario cerradas en chat (Q&A de 7 + 2 + 1 preguntas). No reabrir sin autorización.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-053 |
| Modulo / area | Calidad — tablero **MT-GI-11** (`mt_gi_11`), independiente del módulo dinámico `matrices` |
| Titulo | Tablero MT-GI-11 Matrices (Dashboard + Matriz + Catálogos) |
| Solicitante | Usuario / AgentSj |
| Fecha | 2026-11-20 |
| Triaje | C — Full (tablero nuevo, permisos nuevos, 3 cortes de negocio) |

## Objetivo

Que cualquier usuario solicite la estandarización de un documento del sistema de gestión mediante el formato **MT-GI-11**, con seguimiento propio y visibilidad total para Calidad, en un tablero dedicado dentro del área Calidad con dashboard, listado operativo y catálogos administrables.

## Decisiones de negocio (ley)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Ubicacion | Tablero nuevo en area **Calidad**, `key` = `mt_gi_11`, label sidebar **MT-GI-11**. Convive con el tablero existente `matrices` («Matrices» del módulo dinámico); no se reutiliza ni se modifica. |
| 2 | Pestanas | `dashboard` (MT-GI-11 Dashboard) · `matriz` (MT-GI-11 Matriz) · `catalogos` (Catálogos). |
| 3 | Permisos | `view.board.calidad.mt_gi_11`, `mt_gi_11.matriz.view`, `mt_gi_11.matriz.edit`, `mt_gi_11.view_all`, `mt_gi_11.parameters.edit`. `edit` implica `view`. El dashboard se abre con `matriz.view` (sin clave propia). |
| 4 | Visibilidad | Cada usuario ve **solo lo que solicita** (`created_by = auth()->id()`). `mt_gi_11.view_all` (Calidad / admin) ve todo. Aplica a listado, dashboard, export, import y adjuntos. |
| 5 | Consecutivo | Texto corto **editable y NO unico**: la llave es el `id`. El usuario reutiliza el consecutivo cuando sube una nueva version del mismo documento. |
| 6 | Nueva version | Boton por fila **«Nueva version»** que abre el formulario precargado con el mismo consecutivo y `version_actualizada + 1`. No copia adjuntos ni justificacion. |
| 7 | Justificacion | Campo de texto **largo** (`text`), validacion `required\|string\|max:2000`. |
| 8 | Catalogos | Cinco catalogos en BD administrables desde pestana: procesos, tipos de documento, tipos de solicitud, cargos del responsable y estados (con color). |
| 9 | Estados iniciales | `PENDIENTE` ambar · `EN_REVISION` azul · `APROBADA` verde · `RECHAZADA` rojo. Color como clave de `status-pill`. |
| 10 | Adjuntos | Multiples por solicitud, preview de PDF/imagenes y descarga. 10 archivos, 10 MB c/u, pdf/jpg/png. |
| 11 | Masivos | Plantilla XLSX + importacion (alta masiva, sin validar duplicados de consecutivo) + export filtrado con columna `ID`. |
| 12 | DataTable | Server-side, buscador global por todos los campos, selector de cantidad 10/25/50/100 (sin «Todos»), paginacion, orden por columnas. |
| 13 | Mayusculas | Todo texto libre se persiste en MAYUSCULAS (incluye `consecutivo`, `codigo_actual`, `codigo_estandarizacion`, `version_actual`). |
| 14 | Llave de operacion | Consultar, editar, eliminar y adjuntos operan **siempre por `id`**, nunca por consecutivo. |

## Campos del formulario

| Campo | Tipo | Obligatorio |
| --- | --- | --- |
| `fecha_solicitud` | date | Si |
| `created_by` | Usuario que solicito (autor, no editable) | Si (sistema) |
| `proceso_id` | Selector catalogo | Si |
| `consecutivo` | Texto corto editable (60) | Si |
| `tipo_documento_id` | Selector catalogo | Si |
| `nombre_documento` | Texto corto (255) | Si |
| `tipo_solicitud_id` | Selector catalogo | Si |
| `codigo_actual` | Texto corto (60) | No |
| `version_actual` | Texto corto (30) | No |
| `justificacion` | Texto largo (`text`, max 2000) | Si |
| `responsable_proceso` | Texto corto (255) | Si |
| `cargo_responsable_id` | Selector catalogo | Si |
| `codigo_estandarizacion` | Texto corto (60) | No |
| `fecha_estandarizacion` | date (>= fecha_solicitud) | No |
| `version_actualizada` | Entero >= 0 | No |
| `estado_id` | Selector catalogo con color | Si |
| `datos_adjuntos` | Adjuntos multiples | No |

## Modelo de datos

- `calidad_mt_gi_11_solicitudes` (+ softDeletes, `created_by`, `updated_by`).
- `calidad_mt_gi_11_adjuntos`.
- Catalogos: `calidad_mt_gi_11_procesos`, `_tipos_documento`, `_tipos_solicitud`, `_cargos_responsable`, `_estados` (con `color`).

Reglas: sin `enum()` (string indexado), FKs `restrictOnDelete` en catalogos, `nullOnDelete` en usuarios, compatible sqlite/MySQL.

## Fuera de alcance

- Flujos de aprobacion con multiples pasos.
- Notificaciones por correo.
- Versionado historico automatico (la «nueva version» crea un registro nuevo, sin encadenar).
- Firma digital.

## Criterios de aceptacion

1. Un usuario sin `mt_gi_11.matriz.view` no entra por URL directa.
2. Sin `view_all`, un usuario solo ve sus propias solicitudes en listado, dashboard, export y adjuntos.
3. Con `view_all`, ve todas.
4. El formulario guarda textos en MAYUSCULAS.
5. Dos solicitudes pueden compartir consecutivo; editar/eliminar una no afecta a la otra.
6. «Nueva version» precarga consecutivo y `version_actualizada + 1`.
7. El import acepta consecutivos repetidos y reporta solo errores reales.
8. `php artisan test --compact --filter=MtGi11`, `pint --dirty` y `npm run build` en verde.

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-11-20 | Creacion del brief con las decisiones aprobadas por el usuario. |
