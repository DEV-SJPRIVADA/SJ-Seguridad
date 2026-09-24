# Feature Brief — FEAT-037

> Brief final del Arquitecto (2026-09-24). Consolida `docs/briefs/FEAT-037-analyst.md` + respuestas usuario 1–8 (2026-09-24). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-037 |
| Modulo / area | Gestion humana — tablero **Acreditaciones** (`acreditaciones`) — pestaña **Reporte Diario** |
| Titulo | Reporte Diario APO (carga diaria 2 Excel + histórico) |
| Solicitante | Usuario / AgentSj (chat 2026-09-24) |
| Fecha | 2026-09-24 |

## Objetivo

Operativizar la pestaña **Reporte Diario** (hoy placeholder FEAT-036) para que Gestión Humana cargue diariamente los Excel que entrega la APO (**En proceso** y **Acreditados APO**), los asocie a una **fecha de reporte** elegible (hoy o hacia atrás), consulte el **histórico**, filtre/busque y exporte.

Es un **repositorio histórico de snapshots APO**, independiente del listado operativo **Acreditados** (`acreditacion_acreditados`). No cruza Ficha ni recalcula estados del módulo; el cruce queda para **Validaciones** (fuera de alcance).

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Carga parcial | Puede subir **1 o 2** Excel en la misma operación. Si la fecha ya tiene el otro origen → **conservarlo** y reemplazar solo el origen del archivo subido. Día nuevo + un archivo → el otro origen queda vacío. |
| 2 | Fecha reporte | Elegible; default **hoy**; solo **hoy y hacia atrás**; **prohibido futuro**. Es la fecha del reporte APO (no el timestamp de subida). |
| 3 | Histórico v1 | Filtro por fecha de reporte (default hoy) + metadata quién/cuándo + **botón ver listado de cargas**. Sin comparación día vs día. |
| 4 | Cruce Ficha / Acreditados | **Sin cruce** en esta pestaña. Carga permitida sin Ficha. Cruce = Validaciones (fuera FEAT-037). |
| 5 | Permisos | Reutilizar `acreditaciones.view` (ver/filtrar/export/listado cargas) + `acreditaciones.edit` (cargar/reemplazar). **Sin permiso nuevo.** |
| 6 | UI | **Una tabla** con columna **Origen** + filtro origen (Todos / En proceso / Acreditado APO). |
| 7 | Replace | **Duro** del origen(es) afectado(s) ese día; sin versionado del mismo día. |
| 8 | Filas malas | Skip + reporte de fallos; no fallar toda la carga por filas sueltas. Headers incorrectos → falla **ese archivo** (mensaje claro). Persistencia **tal cual** Estado (proceso) y Vigen.Acr (acreditados); **sin** `AcreditacionEstadoCalculator`. |
| 9 | Fuente | Solo personal que sale de la APO; no inventar filas desde Ficha ni desde Acreditados del sistema. |
| 10 | Datos | Solo migraciones aditivas. **Prohibido** `migrate:fresh` / wipe / TRUNCATE masivo sin OK del usuario. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modelo de datos | Cabecera `acreditacion_reporte_diario_cargas` (1 fila por `fecha_reporte`, **unique**) + filas `acreditacion_reporte_diario_filas` con `origen` `PROCESO` \| `ACREDITADO` | Snapshot diario; carga parcial por origen sin segunda cabecera. |
| Metadata por origen | En cabecera: columnas `proceso_*` y `acreditado_*` (archivo, filas ok/fail, loaded_at, loaded_by) | Listado de cargas y UI muestran quién/cuándo por origen; partial load no pisa metadata del origen no tocado. |
| Replace parcial | En transacción: borrar filas del `carga_id` con `origen` ∈ orígenes subidos; insertar nuevas; actualizar solo metadata de esos orígenes | Cumple 1+7 sin soft-delete ni versiones. |
| Confirm UI | Si `fecha_reporte` ya tiene cabecera **y** al menos uno de los orígenes a subir ya tiene filas (o metadata de carga previa) → exigir `confirm_replace=1` | Evita sobrescritura accidental. |
| Validación de archivos | Validar **todos** los archivos subidos (headers/formato) **antes** de mutar BD. Si alguno falla formato → **422 / abort** de toda la request (ningún origen se reemplaza). Filas inválidas dentro de un archivo OK → skip + reporte. | Evita “creo que subí dos y solo aplicó uno por headers rotos”. |
| Archivo 0 filas válidas | Si headers OK y 0 filas válidas → igual se **reemplaza** ese origen (queda vacío) + reporte de fallos | Replace duro coherente. |
| Excel layout | Fila 1 = título (ignorar); fila 2 = headers; datos desde fila 3 | Estructura real APO confirmada. |
| Headers PROCESO | Apellido1, Apellido2, Nombre1, Nombre2, IdNum, Cargo, Estado | Case/espacios normalizados al mapear (aliases). |
| Headers ACREDITADO | Apellido1, Apellido2, Nombre1, Nombre2, IdNum, Cargo, Vigen.Acr | Idem; `Vigen.Acr` / variantes. |
| Identidad / nombre | `document_number` = IdNum; `full_name` = composición Apellido1+Apellido2+Nombre1+Nombre2 (trim, espacios simples) | Búsqueda/listado sin join Ficha. |
| Cargo | Texto libre del Excel; **no** validar contra `acreditacion_cargos` | Snapshot APO ≠ catálogo operativo. |
| Estado / vigencia | `estado_apo` string nullable (solo PROCESO); `vigencia_acr` date nullable (solo ACREDITADO). Texto Estado tal cual; Vigen.Acr parseable a date o fila falla | Sin calculadora de Acreditados. |
| Fila inválida | Sin IdNum (vacío) → skip; Vigen.Acr no parseable (si no vacío) → skip; fila totalmente vacía → empty_rows (no failure) | Igual espíritu import Acreditados. |
| Listado | DataTables `serverSide: true`; tope length 100; sin `-1` | Regla proyecto + ~2000 filas/día. |
| Export | `BaseExport` + `<x-export-excel>`; respeta filtros activos (fecha, origen, búsqueda) | AGENTS.md. |
| Import UX | Modal (fecha + 1–2 files) + `<x-import-result-modal>` + reporte fallos por token cache (~1 h) | Patrón Acreditados. |
| Controlador | Extender `AcreditacionesController` (vertical slice); no controller nuevo salvo que el Feature lo justifique por tamaño — preferir métodos `reporteDiario*` en el mismo controller | Consistencia FEAT-036. |
| Services | `AcreditacionReporteDiarioImportService`, `AcreditacionReporteDiarioDatatableService`, `AcreditacionReporteDiarioListService` (export/reuso query) | Espejo Acreditados; sin Repository. |
| Form Requests | `ImportAcreditacionReporteDiarioRequest`; query request o reglas inline para datatable/export/cargas (mínimo viable) | Validación fecha ≤ hoy; ≥1 archivo. |
| Permisos | Sin cambios en `config/access.php` (no permiso nuevo). Actualizar **solo** textos de ayuda en docs; labels existentes pueden seguir genéricos | Usuario eligió 5.A. |
| Access service | `AcreditacionesAccessService` ya cubre view/edit; botones mutación solo con edit | Sin código de permiso nuevo. |
| Audit | Extender `AcreditacionesAuditLogService` con entity/event de carga/reemplazo (resumen ok/fail por origen); no auditar GET | FEAT-036 + audit central. |
| Selectores | Filtro origen: nativo o `<x-searchable-select>` según densidad; fecha: input date. **Prohibido Select2.** | AGENTS.md. |
| CSS | Reusar clases chrome/filtros/icon-btn existentes; `app.css` solo si faltan estilos mínimos de la pestaña | shared-files posible. |
| Repository | **No.** | Convención. |
| Migrate | Solo `php artisan migrate` incremental. | Protección de datos. |

### Codigos de origen (BD / API)

| Code BD | Label UI | Archivo Excel |
| --- | --- | --- |
| `PROCESO` | En proceso | Informacion de Companias en Proceso… |
| `ACREDITADO` | Acreditado APO | Informacion de Companias acreditados… |

## Alcance

### Incluye

- Sustituir placeholder de **Reporte Diario** por UI operativa.
- Carga de 1–2 Excel APO (proceso / acreditados) con fecha de reporte.
- Confirmación de replace cuando la fecha/origen ya tiene datos.
- Persistencia cabecera + filas; histórico por fecha; listado de cargas (metadata).
- DataTables server-side unificado (columna Origen + filtro origen).
- Búsqueda (cédula / nombre / cargo) sobre el día filtrado.
- Export Excel filtrado (`BaseExport` + `<x-export-excel>`).
- Modal resultado import + descarga reporte de fallos (token).
- Audit de mutaciones de carga/reemplazo.
- Tests PHPUnit mínimos (ver sección).
- Docs: actualizar `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` (+ INDEX / ACCESS_CONTROL si describen placeholders). Documentador al cierre.

### Fuera de alcance

- Pestañas **Dashboard**, **Validaciones**, **Export Apo** (siguen placeholder).
- Cruce / enriquecimiento con Ficha o `acreditacion_acreditados`.
- Bridge, upsert o sync hacia Acreditados del sistema.
- Recalcular estados con `AcreditacionEstadoCalculator`.
- Versionado / soft-delete de cargas del mismo día.
- Comparación día vs día / diff.
- Notificaciones por correo.
- Permiso nuevo / cambios de paquetes en `config/access.php`.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Acceso

1. Ver pestaña, filtrar, listado de cargas, datatable, export: `acreditaciones.view` (o `acreditaciones.edit` / bypass `manage.users`).
2. Abrir modal de carga, POST importar, confirmar replace: `acreditaciones.edit` (o bypass).
3. Usuario solo view: ve datos y export; **sin** botón cargar/reemplazar.

### Fecha de reporte

4. Campo obligatorio en el modal; default = hoy (timezone app `America/Bogota`).
5. `fecha_reporte <= hoy`; fechas futuras → 422 + mensaje claro. Sin límite inferior en v1 (histórico antiguo permitido).

### Carga y replace

6. Al menos un archivo en la request; como máximo dos (uno por origen). El origen se determina por el **campo del formulario** (`file_proceso` / `file_acreditado`), no por el nombre del archivo.
7. Si la fecha no existe → crear cabecera; insertar filas de los orígenes enviados; metadata del otro origen null/0.
8. Si la fecha existe y se envía un origen que **ya tiene** datos (filas > 0 o `*_loaded_at` no null) → exigir confirmación explícita (`confirm_replace`); sin ella → 422/redirect con aviso.
9. Al confirmar (o si el origen estaba vacío): delete filas de ese origen + insert nuevas + actualizar solo metadata de ese origen.
10. Si se envían ambos orígenes y ambos ya tenían datos → un solo confirm replace cubre ambos; se reemplaza el día completo de ambos orígenes.
11. No crear segunda cabecera para la misma fecha (unique `fecha_reporte`).

### Validación Excel

12. Por archivo: headers esperados en **fila 2**; si falta columna obligatoria del origen → **falla ese archivo** (y, con la regla de validación previa, **toda la request**).
13. Filas desde 3: sin `IdNum` → failure/skip; fila vacía → empty_rows; resto de campos opcionales salvo lógica de vigencia (si celda Vigen.Acr tiene valor no parseable → failure).
14. No exigir existencia en Ficha ni catálogo CARGO APO.
15. No escribir en `acreditacion_acreditados`.

### Listado e histórico

16. Vista default: `fecha_reporte` = hoy; si no hay carga ese día → tabla vacía + CTA cargar (si edit).
17. Filtro origen: Todos | En proceso | Acreditado APO.
18. Botón **Ver cargas**: listado (DT client-side o server-side ligero) de cabeceras: fecha, conteos por origen, quién/cuándo por origen, nombres de archivo. Acción “Ver” aplica filtro de fecha al listado principal (cerrar modal).
19. Export: mismas columnas visibles + Origen; respeta filtros.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion (uso en FEAT-037) |
| --- | --- | --- |
| `view.board.gestion_humana.acreditaciones` | Asignación manual (sin paquete default) | Ver tablero Acreditaciones |
| `acreditaciones.view` | Idem | Ver Reporte Diario, filtrar, listado cargas, datatable, export |
| `acreditaciones.edit` | Idem | Cargar / reemplazar Excel del reporte |
| `manage.users` | Admin | Bypass runtime (existente) |

**Sin permiso nuevo.** **No** editar `config/access.php` en esta feature (shared-files: no). Actualizar descripciones en docs de módulo/usuario/ACCESS_CONTROL al documentar (Documentador o Feature al cierre).

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo existente: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware: `auth`, `active`, `password.changed` (igual bloque actual).

| Metodo | URI | Nombre | Permiso | Notas |
| --- | --- | --- | --- | --- |
| GET | `/reporte-diario` | `reporte-diario` | `acreditaciones.view` | **Reemplaza** placeholder; shell listado + filtros + modal carga |
| GET | `/reporte-diario/datatable` | `reporte-diario.datatable` | `acreditaciones.view` | JSON DataTables server-side |
| GET | `/reporte-diario/exportar` | `reporte-diario.export` | `acreditaciones.view` | Excel filtrado (`BaseExport`) |
| POST | `/reporte-diario/importar` | `reporte-diario.import` | `acreditaciones.edit` | Multipart: fecha, files, `confirm_replace` opcional |
| GET | `/reporte-diario/importar/reporte/{token}` | `reporte-diario.import-report` | `acreditaciones.edit` | Fallos cache ~1 h (patrón Acreditados) |
| GET | `/reporte-diario/cargas` | `reporte-diario.cargas` | `acreditaciones.view` | JSON o HTML parcial del listado de cargas (metadata) |

**No** se requiere `GET show` de carga individual en v1: seleccionar una carga en el listado = setear filtro `fecha` en el index/datatable. Si el Feature prefiere `GET /reporte-diario/cargas/{carga}` solo-lectura, es opcional y no bloqueante.

Rutas placeholder **Dashboard / Validaciones / Export Apo** no se tocan (siguen como están).

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `acreditacion_reporte_diario_cargas` | migracion create | Cabecera por día |
| `acreditacion_reporte_diario_filas` | migracion create | Filas snapshot |

### `acreditacion_reporte_diario_cargas`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `fecha_reporte` | date | **unique** |
| `proceso_file_name` | string(255) nullable | Último archivo En proceso |
| `proceso_rows_ok` | unsigned int default 0 | |
| `proceso_rows_fail` | unsigned int default 0 | |
| `proceso_loaded_at` | timestamp nullable | |
| `proceso_loaded_by` | FK `users.id` nullable | `nullOnDelete` |
| `acreditado_file_name` | string(255) nullable | Último archivo Acreditados APO |
| `acreditado_rows_ok` | unsigned int default 0 | |
| `acreditado_rows_fail` | unsigned int default 0 | |
| `acreditado_loaded_at` | timestamp nullable | |
| `acreditado_loaded_by` | FK `users.id` nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Índices: unique(`fecha_reporte`); index(`proceso_loaded_at`); index(`acreditado_loaded_at`).

### `acreditacion_reporte_diario_filas`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `carga_id` | FK → cargas `cascadeOnDelete` | |
| `origen` | string(20) | `PROCESO` \| `ACREDITADO`; index compuesto con carga |
| `apellido1` | string(100) nullable | |
| `apellido2` | string(100) nullable | |
| `nombre1` | string(100) nullable | |
| `nombre2` | string(100) nullable | |
| `full_name` | string(255) | Compuesto al persistir; búsqueda |
| `document_number` | string(50) | IdNum; index |
| `cargo` | string(255) nullable | Texto APO |
| `estado_apo` | string(100) nullable | Solo PROCESO (Estado Excel) |
| `vigencia_acr` | date nullable | Solo ACREDITADO (Vigen.Acr) |
| `source_row` | unsigned int nullable | Nº fila Excel (reporte fallos) |
| `created_at` / `updated_at` | timestamps | |

Índices recomendados:

- `(carga_id, origen)`
- `document_number`
- `full_name`
- `(carga_id, document_number)` opcional para búsqueda

**Sin** unique de negocio en filas (el Excel puede traer duplicados IdNum; se persisten tal cual).

### Modelado carga parcial

```
carga (fecha_reporte única)
  ├── filas origen=PROCESO     ← replace solo si se sube file_proceso
  └── filas origen=ACREDITADO  ← replace solo si se sube file_acreditado
```

Metadata del origen no enviado **no** se modifica.

## Capas a implementar

- [x] Migracion(es) — 2 tablas (pueden ir en una o dos migraciones)
- [x] Modelo(s) — `AcreditacionReporteDiarioCarga`, `AcreditacionReporteDiarioFila` (+ relations, casts)
- [x] Controlador(es) — métodos en `AcreditacionesController` (o extracción justificada)
- [x] Form Request(s) — `ImportAcreditacionReporteDiarioRequest` (+ filtros datatable/export si aplica)
- [x] Vista(s) Blade — `reporte-diario.blade.php` (+ partials modal carga, modal cargas); dejar de usar placeholder para esta pestaña
- [x] JavaScript — DataTables server-side + confirm replace (Alpine/inline como Acreditados)
- [x] Export Excel — clase dedicada o config columnas vía `BaseExport` + `<x-export-excel>`
- [x] Services — Import + Datatable + List (export)
- [x] Audit — eventos en `AcreditacionesAuditLogService`
- [x] Tests — ver criterios / validación local
- [ ] `config/access.php` — **no** (sin permiso nuevo)

## Componentes reutilizables

| Componente | Uso |
| --- | --- |
| `<x-export-excel>` | Botón export filtrado |
| `<x-import-result-modal>` | Resumen ok/fail + descarga reporte |
| `<x-searchable-select>` | Filtro origen si se usa searchable; si opciones fijas 3 valores, select nativo aceptable |
| `.module-tab` / subnav existente | Sin cambiar estructura de pestañas |
| `.req-manage-filters__icon-btn` | Acciones chrome (cargar, ver cargas, export) |
| `BaseExport` | Export columnas configurables |
| `SpreadsheetCellReader` / PhpSpreadsheet | Lectura Excel (patrón `AcreditacionImportService`) |
| `AcreditacionesAccessService` / `HasAcreditacionesTabs` | Acceso y tab activa `reporte_diario` |
| `SystemAuditService` via wrapper | Audit mutaciones |

### Columnas Export / DataTables (mínimo)

| Columna UI | Campo |
| --- | --- |
| Origen | `origen` (label) |
| Apellido1 | `apellido1` |
| Apellido2 | `apellido2` |
| Nombre1 | `nombre1` |
| Nombre2 | `nombre2` |
| Nombre completo | `full_name` (opcional en export si se prefiere desglose) |
| Cédula / IdNum | `document_number` |
| Cargo | `cargo` |
| Estado (APO) | `estado_apo` |
| Vigen.Acr | `vigencia_acr` |

### Flujo UX (resumen)

1. Usuario abre **Reporte Diario** → ve filas de **hoy** (o vacío).
2. **Cargar reporte** (edit): modal con fecha (max=hoy), input En proceso, input Acreditados APO; al menos uno.
3. Submit → si requiere replace y no hay confirm → flash/modal “Ya existe carga para esta fecha / origen(es). ¿Reemplazar?” → reenvío con `confirm_replace=1`.
4. Éxito → redirect + `<x-import-result-modal>` (conteos + link fallos si hay).
5. Filtros: fecha, origen, búsqueda texto.
6. **Ver cargas**: modal/panel con historial de cabeceras; “Ver” cambia filtro fecha del listado.

## Documentacion a actualizar

- [ ] `docs/modules/acreditaciones.md` — quitar placeholder Reporte Diario; documentar tablas, rutas, reglas, audit
- [ ] `docs/user/acreditaciones.md` — guía operativa de carga / histórico / export
- [ ] `docs/INDEX.md` — si lista estado de pestañas
- [ ] `docs/ACCESS_CONTROL.md` — ampliar descripción view/edit (Reporte Diario) sin permiso nuevo
- [ ] `README.md` — solo si menciona placeholders de Acreditaciones

## Archivos compartidos (`shared-files`)

| Archivo | ¿Toca? | Notas |
| --- | --- | --- |
| `config/access.php` | **No** | Sin permiso nuevo |
| `routes/web.php` | No | Rutas viven en area |
| `routes/areas/gestion_humana.php` | **Sí** | Ampliar bloque acreditaciones (datatable, import, export, cargas) |
| Layouts globales | No | |
| Seeders globales / PermissionSeeder | No | |
| `resources/css/app.css` | **Posible** | Solo estilos mínimos reutilizables de la pestaña; preferir clases existentes |

Flag en `docs/TASKS.md` / plan: `shared-files: routes/areas/gestion_humana.php` (+ `app.css` si aplica).

## Criterios de aceptacion

1. Con `acreditaciones.view`, la pestaña Reporte Diario muestra listado (no “Próximamente”) y permite filtrar por fecha/origen y exportar.
2. Sin `acreditaciones.edit`, no aparece acción de carga; POST import responde 403.
3. Con edit, se puede cargar **solo** En proceso, **solo** Acreditados APO, o **ambos** en una operación.
4. Fecha default = hoy; fecha futura rechazada (UI + server).
5. Día nuevo + un archivo → el otro origen queda sin filas; metadata del no enviado null.
6. Fecha existente + subir un origen que ya tenía datos → bloqueo hasta confirmar replace; tras confirmar, el otro origen **se conserva**.
7. Replace no deja versiones anteriores del mismo origen/día (conteo filas del origen = solo la última carga).
8. Excel con headers incorrectos → no se muta BD; mensaje claro indicando el origen/archivo.
9. Filas sin IdNum (u otras inválidas) → no abortan la carga; aparecen en reporte de fallos; filas válidas se guardan.
10. Filas se persisten con Estado / Vigen.Acr tal cual; **no** se llama a `AcreditacionEstadoCalculator`; **no** se escribe en `acreditacion_acreditados`.
11. Cédula ausente en Ficha **no** bloquea la fila.
12. DataTables server-side responde en JSON paginado; length máximo 100.
13. Export descarga `.xlsx` con columnas acordadas y respeta filtros activos.
14. Botón **Ver cargas** lista cabeceras con fecha, conteos y quién/cuándo por origen; al elegir una fecha se ve su listado.
15. Audit registra evento de carga/reemplazo con resumen (fecha, orígenes, ok/fail); no audita GET.
16. Tests PHPUnit del paquete Reporte Diario pasan con `php artisan test --compact` filtrado.
17. Docs técnica y usuario actualizadas en la misma entrega (Documentador).

## Validacion local

1. `php artisan migrate` (sin fresh) — tablas nuevas creadas.
2. Carga manual con Excel reales APO (proceso ~60, acreditados ~2000) para fecha hoy.
3. Re-carga parcial (solo un origen) y verificar conservación del otro.
4. Intentar fecha futura → rechazo.
5. Usuario solo view vs edit (permisos).
6. `php artisan test --compact` — tests FEAT-037 / filtro `ReporteDiario` o archivo dedicado.
7. Verificar modal import-result + descarga reporte fallos.
8. `npm run build` solo si se tocó Vite/JS de entry (si el JS va inline en Blade como Acreditados, puede no aplicar).

## Tests minimos (PHPUnit)

| Caso | Esperado |
| --- | --- |
| Guest / sin permiso | 403/redirect en index, datatable, export, import, cargas |
| View puede GET index/datatable/export/cargas; no POST import | 403 en import |
| Edit importa 1 origen día nuevo | Cabecera creada; solo filas de ese origen |
| Edit importa 2 orígenes | Ambos orígenes con filas |
| Reimport parcial conserva otro origen | Conteos correctos |
| Replace sin `confirm_replace` cuando ya hay datos | 422 / no muta |
| Replace con confirm | Filas antiguas del origen eliminadas |
| Fecha futura | 422 |
| Headers inválidos | 422; 0 filas nuevas |
| Fila sin IdNum | Contada en fail; resto ok |
| Cédula no en Ficha | Fila válida se guarda |
| Export | 200 + content-type spreadsheet (o stream) |
| Datatable | Estructura JSON DataTables |

Factories: crear factories mínimas para carga/fila si facilitan tests.

## Audit events

| Evento sugerido | Cuándo | Payload mínimo |
| --- | --- | --- |
| `acreditacion_reporte_diario.imported` (o `logEvent` equivalente) | Tras import exitoso (incl. replace) | `fecha_reporte`, orígenes tocados, `rows_ok`/`rows_fail` por origen, `replaced` bool, `carga_id`, `actor_id` |

No auditar vistas ni export. Entity type sugerido: `acreditacion_reporte_diario_carga`.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Volumen ~2000+ filas/import | Transacción + insert batch/chunk; DT server-side; no client-side full dump |
| Headers APO con variaciones de nombre | Mapa de aliases (normalizar: lower, quitar espacios/puntos) documentado en service/config |
| Pérdida de datos por replace duro | Confirm UI obligatoria; sin versionado (ley usuario); mencionar en doc usuario |
| Confusión con pestaña Export Apo / Acreditados | Fuera de alcance explícito; copy UI: “Reporte Diario APO (snapshot)” |
| Timeout PHP en import grande | Alinear con límites import Acreditados; chunk inserts; revisar `max_execution_time` solo si falla en local |
| `shared-files` en `gestion_humana.php` | Un Feature a la vez en ese archivo; AgentSj coordina |
| Descripciones `access.php` desactualizadas | Docs sí; código access no (evitar shared-files innecesario) |

### Dependencias

- FEAT-036 cerrado (shell Acreditaciones + permisos view/edit + patrones import/DT/export/audit).
- PhpSpreadsheet ya en proyecto.
- No nuevas dependencias Composer/npm.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas usuario 2026-09-24)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmación (opcional si AgentSj continúa con plan; negocio ya cerrado)
- [ ] AgentSj — plan de orquestación + Task Cards (`docs/briefs/FEAT-037-plan.md`)

---

## Instruccion a AgentSj

1. Generar `docs/briefs/FEAT-037-plan.md` + Task Cards según este brief.
2. Marcar `shared-files`: `routes/areas/gestion_humana.php` (y `app.css` solo si hace falta).
3. **No** tocar `config/access.php`.
4. Lanzar Agente Feature → Revisor → Documentador.
5. Actualizar `docs/TASKS.md` y `docs/runs/FEAT-037-run-log.md`.
