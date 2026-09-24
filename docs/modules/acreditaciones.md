# Modulo Acreditaciones

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/acreditaciones.md`.
> Features: FEAT-036 (fase 1) + FEAT-037 (Reporte Diario APO, 2026-09-24, aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para controlar personal acreditado (vigencia, solicitud en tramite, estados automaticos), administrar el **catalogo de cargos** (Manager / APO / Informe / Acreditacion) y conservar el **historico diario de snapshots APO** (Reporte Diario).

## Alcance actual

- Tablero sidebar **Acreditaciones** (`board` key `acreditaciones`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestanas: **Dashboard**, **Acreditados**, **Reporte Diario**, **Validaciones**, **Export Apo**, **Catálogo** (esta ultima solo con `acreditaciones.edit`).
- **Funcional:**
  - **Acreditados** — CRUD, filtros, DataTables server-side, export Excel, plantilla/import upsert, estados calculados, sync diario.
  - **Catálogo** cargos — seed 17 + CRUD.
  - **Reporte Diario** (FEAT-037) — carga 1–2 Excel APO (En proceso / Acreditado APO), fecha de reporte ≤ hoy, replace parcial por origen, DT server-side, export filtrado, listado de cargas (metadata). Snapshot historico **independiente** de `acreditacion_acreditados`; **sin** cruce Ficha ni `AcreditacionEstadoCalculator`.
- **Placeholders** («Próximamente»): Dashboard, Validaciones, Export Apo — sin endpoints de negocio ni KPIs.
- Permisos: `view.board.gestion_humana.acreditaciones`, `acreditaciones.view`, `acreditaciones.edit`. **Sin permiso nuevo** para Reporte Diario. Bypass runtime: `manage.users`. Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` via `app:sync-permissions`.
- Tipo de acreditacion (pestana Acreditados) = valor **CARGO APO** (texto); unicidad `(document_number, cargo_apo)`; re-import → upsert.
- Cedula obligatoria en `employee_ficha_profiles` **solo** en Acreditados; en Reporte Diario la cedula ausente en Ficha **no** bloquea.
- ESTADO (Acreditados) solo calculado (`AcreditacionEstadoCalculator`); no editable. Reporte Diario: Estado APO del Excel En proceso tal cual; filas con Vigen.Acr muestran/persisten `ACREDITADO` (sin calculadora del módulo Acreditados).
- Audit: `AcreditacionesAuditLogService` → `SystemAuditService` (`module=acreditaciones`, `area=gestion_humana`). Solo mutaciones (+ resumen import Acreditados y Reporte Diario).
- Selectores: `<x-searchable-select>` (prohibido Select2). Export: `BaseExport` + `<x-export-excel>` (prohibido `excelHtml5`).
- Borrado **duro** en Acreditados (sin soft-delete). Replace duro por origen en Reporte Diario (sin versionado del mismo dia).

### Fuera de alcance

- Logica de negocio de Dashboard, **Validaciones**, Export Apo (siguen placeholder).
- Cruce / enriquecimiento Reporte Diario ↔ Ficha o `acreditacion_acreditados` (queda para Validaciones).
- Bridge, upsert o sync hacia Acreditados del sistema desde Reporte Diario.
- Recalcular estados del snapshot APO con `AcreditacionEstadoCalculator`.
- Versionado / soft-delete de cargas del mismo dia; comparacion dia vs dia / diff.
- Notificaciones por correo.
- Soft-delete / historial versionado de Acreditados.
- Bridge editable Ficha ↔ Acreditados; sync automatico de nombres tras cambio en Ficha.
- Derivar campo CARGO desde el catalogo (Acreditados).
- Permiso nuevo / cambios de paquetes en `config/access.php` (FEAT-037).
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `acreditados`. `acreditaciones.view` |
| GET | `/dashboard` | `dashboard` | Placeholder. `acreditaciones.view` |
| GET | `/acreditados` | `acreditados` | Shell listado. `acreditaciones.view` |
| GET | `/acreditados/datatable` | `acreditados.datatable` | JSON DataTables. `acreditaciones.view` |
| GET | `/acreditados/bulk-selectable` | `acreditados.bulk-selectable` | JSON ids del filtro para selección masiva. `acreditaciones.edit` |
| POST | `/acreditados/bulk-update` | `acreditados.bulk-update` | Actualiza obs. y/o fecha solicitud de ids. `acreditaciones.edit` |
| GET | `/acreditados/lookup` | `acreditados.lookup` | Lookup Ficha por cedula. `acreditaciones.edit` |
| GET | `/acreditados/exportar` | `acreditados.export` | Excel filtrado. `acreditaciones.view` |
| GET | `/acreditados/plantilla-importacion` | `acreditados.import-template` | Plantilla vacia. `acreditaciones.edit` |
| POST | `/acreditados/importar` | `acreditados.import` | Upsert masivo. `acreditaciones.edit` |
| GET | `/acreditados/importar/reporte/{token}` | `acreditados.import-report` | Fallos (~1 h cache). `acreditaciones.edit` |
| POST | `/acreditados` | `acreditados.store` | Crear. `acreditaciones.edit` |
| PATCH | `/acreditados/{acreditacionAcreditado}` | `acreditados.update` | Editar. `acreditaciones.edit` |
| DELETE | `/acreditados/{acreditacionAcreditado}` | `acreditados.destroy` | Borrado duro. `acreditaciones.edit` |
| GET | `/reporte-diario` | `reporte-diario` | Shell listado + filtros + modal carga. `acreditaciones.view` |
| GET | `/reporte-diario/datatable` | `reporte-diario.datatable` | JSON DataTables server-side. `acreditaciones.view` |
| GET | `/reporte-diario/exportar` | `reporte-diario.export` | Excel filtrado (`BaseExport`). `acreditaciones.view` |
| POST | `/reporte-diario/importar` | `reporte-diario.import` | Multipart: fecha, 1–2 files, `confirm_replace` opcional. `acreditaciones.edit` |
| GET | `/reporte-diario/importar/reporte/{token}` | `reporte-diario.import-report` | Fallos cache ~1 h. `acreditaciones.edit` |
| GET | `/reporte-diario/cargas` | `reporte-diario.cargas` | JSON listado cabeceras (metadata). `acreditaciones.view` |
| GET | `/validaciones` | `validaciones` | Placeholder. `acreditaciones.view` |
| GET | `/export-apo` | `export-apo` | Placeholder (≠ export listado Acreditados ni Reporte Diario). `acreditaciones.view` |
| GET | `/catalogo` | `catalogo` | Listado catalogo. `acreditaciones.edit` |
| POST | `/catalogo` | `catalogo.store` | Crear. `acreditaciones.edit` |
| PATCH | `/catalogo/{acreditacionCargo}` | `catalogo.update` | Editar. `acreditaciones.edit` |
| DELETE | `/catalogo/{acreditacionCargo}` | `catalogo.destroy` | Eliminar si reglas OK. `acreditaciones.edit` |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.acreditaciones` | Ver tablero **Acreditaciones** en sidebar GH |
| `acreditaciones.view` | Shell, Acreditados (lectura), Reporte Diario (ver/filtrar/export/listado cargas), export; placeholders Dashboard/Validaciones/Export Apo (tambien implica view si tiene `acreditaciones.edit`) |
| `acreditaciones.edit` | CRUD Acreditados, import Acreditados, Catálogo, **cargar/reemplazar** Excel Reporte Diario |

**Sin permiso nuevo** para Reporte Diario (reutiliza view/edit).  
**Paquetes recomendados:** consulta = board + `acreditaciones.view`; operativo = board + view + edit.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `acreditaciones_tabs`, `admin_permission_groups`).  
`PermissionCatalog` no genera `view.board.*.acreditaciones` fuera de `gestion_humana`.  
Sync: `php artisan app:sync-permissions` + re-login.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\AcreditacionesController` | Shell, placeholders, Acreditados, Reporte Diario (`reporteDiario*`), import/export, Catálogo (vertical slice) |
| `StoreAcreditacionAcreditadoRequest` / `UpdateAcreditacionAcreditadoRequest` | Ficha obligatoria, al menos una fecha, `cargo_apo` activo en catalogo, unique cedula+APO |
| `ImportAcreditacionAcreditadoRequest` | Archivo Excel import Acreditados |
| `ImportAcreditacionReporteDiarioRequest` | Fecha ≤ hoy; ≥1 archivo (`file_proceso` / `file_acreditado`); mimes xlsx/xls/csv; `confirm_replace` boolean |
| `StoreAcreditacionCargoRequest` / `UpdateAcreditacionCargoRequest` | CRUD catalogo + reglas rename APO |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/acreditaciones/acreditados.blade.php` | Shell DT server-side + filtros + export |
| `areas/gestion_humana/acreditaciones/reporte-diario.blade.php` | Listado DT + filtros fecha/origen/busqueda + modal carga + modal cargas + export |
| `areas/gestion_humana/acreditaciones/catalogo.blade.php` | Listado + CRUD catalogo |
| `areas/gestion_humana/acreditaciones/placeholder.blade.php` | Dashboard / Validaciones / Export Apo |
| `areas/gestion_humana/acreditaciones/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/acreditaciones/partials/nuevo-modal.blade.php` | Modal crear acreditado |
| `areas/gestion_humana/acreditaciones/partials/edit-modal.blade.php` | Modal editar (identidad bloqueable, CARGO APO searchable-select) |
| `areas/gestion_humana/acreditaciones/partials/bulk-update-modal.blade.php` | Modal actualizar seleccionados (fecha solicitud / observaciones) |
| `areas/gestion_humana/acreditaciones/partials/masivos-modal.blade.php` | Modal import masivo Acreditados |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `AcreditacionAcreditado` | `acreditacion_acreditados` | Unique `(document_number, cargo_apo)`; sin FK formal a Ficha ni catalogo |
| `AcreditacionCargo` | `acreditacion_cargos` | Unique `(cargo_manager, cargo_apo)`; `is_active`, `sort_order` |
| `AcreditacionReporteDiarioCarga` | `acreditacion_reporte_diario_cargas` | 1 cabecera por `fecha_reporte` (**unique**); metadata por origen |
| `AcreditacionReporteDiarioFila` | `acreditacion_reporte_diario_filas` | Snapshot; `origen` `PROCESO` \| `ACREDITADO`; FK `carga_id` cascade |

### `acreditacion_acreditados`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | Snapshot desde Ficha |
| `cargo` | string(255) | Texto formulario/Excel (no derivado catalogo) |
| `cargo_apo` | string(255) | Tipo (= CARGO APO); parte del unique |
| `vigencia_acr` | date nullable | VIGEN.ACR (vencimiento) |
| `fecha_solicitud` | date nullable | FECHA SOLICITUD |
| `estado` | string(30) | Calculado; index |
| `renovacion` | string(30) nullable | Manual: `SOLICITADO` / `RENOVADO`; index |
| `observaciones` | text nullable | |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |

Indexes: `estado`, `renovacion`, `vigencia_acr`, `fecha_solicitud`, `cargo`, `document_number`.

### `acreditacion_cargos`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `cargo_manager` | string(255) | CARGO MANAGER |
| `cargo_apo` | string(255) | CARGO APO; index |
| `cargo_informe` | string(255) | CARGO INFORME |
| `cargo_acreditacion` | string(20) | Codigos 1,2,4,5,6… |
| `is_active` | boolean default true | |
| `sort_order` | unsigned int default 0 | |

### `acreditacion_reporte_diario_cargas`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `fecha_reporte` | date | **unique** (1 cabecera por dia) |
| `proceso_file_name` | string(255) nullable | Ultimo archivo En proceso |
| `proceso_rows_ok` / `proceso_rows_fail` | unsigned int | Conteos ultima carga PROCESO |
| `proceso_loaded_at` | timestamp nullable | |
| `proceso_loaded_by` | FK `users.id` nullable | `nullOnDelete` |
| `acreditado_file_name` | string(255) nullable | Ultimo archivo Acreditados APO |
| `acreditado_rows_ok` / `acreditado_rows_fail` | unsigned int | Conteos ultima carga ACREDITADO |
| `acreditado_loaded_at` | timestamp nullable | |
| `acreditado_loaded_by` | FK `users.id` nullable | `nullOnDelete` |

### `acreditacion_reporte_diario_filas`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `carga_id` | FK → cargas | `cascadeOnDelete` |
| `origen` | string(20) | `PROCESO` \| `ACREDITADO`; index compuesto con carga |
| `apellido1`…`nombre2` | string(100) nullable | Columnas Excel |
| `full_name` | string(255) | Compuesto al persistir; busqueda |
| `document_number` | string(50) | IdNum; index |
| `cargo` | string(255) nullable | Texto APO (no validar catalogo) |
| `estado_apo` | string(100) nullable | PROCESO: Estado Excel tal cual. ACREDITADO: `ACREDITADO` cuando hay Vigen.Acr |
| `vigencia_acr` | date nullable | Solo ACREDITADO (Vigen.Acr parseable) |
| `source_row` | unsigned int nullable | Nº fila Excel (reporte fallos) |

**Sin** unique de negocio en filas (duplicados IdNum del Excel se persisten).  
Modelo carga parcial: subir un origen solo reemplaza filas/metadata de ese origen; el otro se conserva.

### Referencia Ficha (logica Acreditados)

- Create/update/import Acreditados: `document_number` debe existir en `employee_ficha_profiles`.
- `full_name` = `EmployeeFichaProfile.full_name` (Excel se ignora).
- Sin FK formal (patron Cursos).
- **No aplica** a Reporte Diario.

## Servicios / commands

| Clase | Rol |
| --- | --- |
| `AcreditacionesAccessService` | Board / view / edit + tabs visibles (oculta Catálogo sin edit) |
| `HasAcreditacionesTabs` | Trait vistas (tab activa / subnav) |
| `AcreditacionesAuditLogService` | Wrapper audit (`acreditacion_acreditado`, `acreditacion_cargo`, `acreditacion_import`, `acreditacion_reporte_diario_carga`) |
| `AcreditacionEstadoCalculator` | Prioridad estados Acreditados + `syncAll` (chunk 200); **no** usado en Reporte Diario |
| `AcreditacionAcreditadoListService` | Query filtrada Acreditados (export / reuso) |
| `AcreditacionAcreditadoDatatableService` | DT server-side Acreditados; tope length 100; sin `-1` |
| `AcreditacionImportService` | Upsert Excel Acreditados; reporte fallos |
| `AcreditacionReporteDiarioImportService` | Parse 1–2 Excel APO; validar headers de todos antes de mutar; replace parcial; skip filas malas |
| `AcreditacionReporteDiarioListService` | Query filtrada + listado cargas; export reusa `all(filters)` |
| `AcreditacionReporteDiarioDatatableService` | DT server-side Reporte Diario; tope length 100 |
| `AcreditacionCargoCatalogService` | Proteccion delete/rename APO referenciado |
| `SyncAcreditacionEstadosCommand` | `acreditaciones:sync-estados` (`--date`, `--dry-run`) |

Config headers/origenes: `config/acreditaciones.php` → `reporte_diario`.  
Nav: `NavigationResolver`, `SidebarVisibilityService`, `User`.  
Schedule: `bootstrap/app.php` → diario **06:20** `America/Bogota`, `withoutOverlapping` (solo sync estados Acreditados; no aplica a Reporte Diario).

## Reglas de negocio

### Acceso

1. Solo `acreditaciones.view` (+ board): ve pestanas operativas/placeholders, Acreditados lectura, Reporte Diario (filtrar/export/cargas); **sin** botones mutacion ni Catálogo ni carga de reporte.
2. `acreditaciones.edit`: CRUD Acreditados + import + Catálogo + cargar/reemplazar Reporte Diario.
3. Bypass: `manage.users`.

### Estados Acreditados (persistidos)

| Code BD | Label UI |
| --- | --- |
| `EN_PROCESO` | EN PROCESO |
| `DESACREDITADO` | DESACREDITADO |
| `POR_VENCER` | POR VENCER |
| `ACREDITADO` | ACREDITADO |

Prioridad (`AcreditacionEstadoCalculator`):

1. Si hay `fecha_solicitud` → `EN_PROCESO` (gana aunque `vigencia_acr` este vencida).
2. Si no hay `vigencia_acr` → `EN_PROCESO` (trámite; import con celda vacía o «en proceso»).
3. Si `vigencia_acr` ≤ hoy → `DESACREDITADO`.
4. Si `vigencia_acr` ≤ hoy + `config('acreditaciones.por_vencer_days')` (21) → `POR_VENCER`.
5. Else → `ACREDITADO`.

- Formulario create/update: ambas fechas vacías → rechazar (422).
- Import `VIGEN.ACR`: fecha válida, vacío o texto «en proceso» (variantes) → vigencia null + `EN_PROCESO`. Otro texto → falla la fila.
- Recalc inmediato en store/update/import y en sync diario.
- Ventana timezone app (`America/Bogota`).

### Acreditados

5. Unicidad: un registro por `(document_number, cargo_apo)`.
6. `cargo_apo` debe existir como valor **activo** en `acreditacion_cargos` (comparacion trim + case-insensitive via `forCargoApo`).
7. Filtros listado/export: `estado` (acreditacion), `ficha_estado` (`activo` por defecto | `desvinculado` | `todos` via `employee_ficha_profiles.employment_status` por cedula), `document_number` (parcial), `cargo` (parcial), `cargo_apo`, rango `vigencia_desde` / `vigencia_hasta` sobre `vigencia_acr`.
8. Acción masiva (edit): checkboxes + seleccionar todos del filtro (`bulk-selectable`); modal para sobrescribir `observaciones` y/o `fecha_solicitud` (al menos un campo); con fecha → recalcula estado (EN PROCESO).
9. Eliminar: confirmacion UI; DELETE fisico.

### Catalogo

10. Seed 17 filas idempotente (`AcreditacionCargoSeeder`, upsert por manager+APO).
11. DELETE bloqueado si es la **ultima** fila activa con ese `cargo_apo` y hay acreditados con ese tipo.
12. UPDATE de `cargo_apo` bloqueado si el valor anterior esta referenciado por acreditados (editar Manager/Informe/Acreditacion libremente).

### Reporte Diario APO (FEAT-037)

Origenes:

| Code BD | Label UI | Campo form |
| --- | --- | --- |
| `PROCESO` | En proceso | `file_proceso` |
| `ACREDITADO` | Acreditado APO | `file_acreditado` |

13. Fecha de reporte obligatoria; default hoy; `fecha_reporte <= hoy` (futuro → 422). Sin limite inferior en v1.
14. Al menos un archivo; como maximo dos (uno por origen). Origen = campo del formulario, no el nombre del archivo.
15. Dia nuevo + un archivo → solo ese origen tiene filas; metadata del otro null/0.
16. Si la fecha ya tiene cabecera **y** el origen a subir ya tiene datos (filas > 0 o `*_loaded_at` no null) → exigir `confirm_replace=1`; sin ella no muta.
17. Replace: delete filas del `carga_id` con `origen` ∈ orígenes subidos; insert nuevas; actualizar **solo** metadata de esos orígenes. Sin versionado del mismo dia.
18. Validar **todos** los Excel subidos (headers fila 2) **antes** de mutar BD. Headers invalidos → abort total de la request (ningun origen se reemplaza). Acepta xlsx/xls/csv.
19. Layout Excel APO: fila 1 titulo (ignorar); fila 2 headers; datos desde fila 3. Aliases en `config/acreditaciones.php` → `reporte_diario.headers`.
20. Fila sin IdNum → skip + failure; fila vacia → `empty_rows`; Vigen.Acr no parseable (si no vacio) → skip. Headers OK + 0 filas validas → igual se reemplaza el origen (queda vacio).
21. No exigir Ficha ni catalogo CARGO APO. No escribir en `acreditacion_acreditados`. No llamar `AcreditacionEstadoCalculator`.
22. Listado default: `fecha_reporte` = hoy; filtros origen (Todos / PROCESO / ACREDITADO) + busqueda (cedula / nombre / cargo). DT server-side; length max 100.
23. **Ver cargas**: JSON de cabeceras (fecha, conteos, quien/cuando, nombres archivo); elegir una aplica filtro fecha al listado.
24. Export: columnas Origen, Apellido1–2, Nombre1–2, Nombre completo, IdNum, Cargo, Estado (APO), Vigen.Acr; respeta filtros. `filteredQuery()->get()` sin tope — volumen tipico ~2000 filas/dia (aceptable v1).

### Placeholders

25. Dashboard / Validaciones / Export Apo: vista «Próximamente»; sin JSON de metricas ni mutaciones.

### Auditoria

26. Eventos: `acreditacion_acreditado` create/update/delete; `acreditacion_cargo` create/update/delete; `acreditacion_import` (resumen ok/fail Acreditados); `acreditacion_reporte_diario_carga` action `imported` (fecha, origins, by_origen ok/fail, replaced, carga_id).
27. No auditar GET/list/datatable/export ni sync diario fila a fila.

## Import / export Excel

### Plantilla / import Acreditados

Clase plantilla: `App\Exports\AcreditacionesImportTemplateExport`.  
Fila 1 claves tecnicas (`config/acreditaciones.php` → `import.columns`), fila 2 labels, datos desde fila 3.

| Clave | Label | Persistencia |
| --- | --- | --- |
| `document_number` | CEDULA | Si; required; debe existir en Ficha |
| `full_name` | NOMBRE COMPLETO | Ignorado (ayuda humana) |
| `cargo` | CARGO | Si; texto |
| `cargo_apo` | CARGO APO | Si; tipo / unique key |
| `vigencia_acr` | VIGEN.ACR | Si; date, vacío o «en proceso» → vigencia null + estado EN PROCESO; opcional si hay fecha_solicitud |
| `fecha_solicitud` | FECHA SOLICITUD | Si; date; opcional si VIGEN.ACR es fecha, vacío o «en proceso» |
| `observaciones` | OBSERVACIONES | Si; nullable |

**No** incluir columna ESTADO. Misma cedula + mismo `cargo_apo` → update in-place (upsert). Fallos: token temporal + descarga reporte.

### Export listado Acreditados

`BaseExport` inline en controlador (no clase dedicada). Columnas: CEDULA, NOMBRE COMPLETO, CARGO, CARGO APO, VIGEN.ACR, ESTADO, RENOVACIONES, FECHA SOLICITUD, OBSERVACIONES. Respeta filtros activos. Auth: `acreditaciones.view`. Boton `<x-export-excel>`.

### Import / export Reporte Diario

- Import: modal (fecha + `file_proceso` / `file_acreditado`) + `<x-import-result-modal>` + reporte fallos por token cache (~1 h).
- Headers esperados PROCESO: Apellido1, Apellido2, Nombre1, Nombre2, IdNum, Cargo, Estado.
- Headers esperados ACREDITADO: Apellido1, Apellido2, Nombre1, Nombre2, IdNum, Cargo, Vigen.Acr.
- Export: `BaseExport` + `<x-export-excel>`; auth `acreditaciones.view`.

> Observacion review FEAT-036 #1 / FEAT-037 #3: exports hacen `filteredQuery()->get()` sin tope; volumen alto puede saturar memoria. Follow-up: chunk/stream si crece el filtro.

## JavaScript / assets

- DataTables `serverSide: true` en Acreditados y Reporte Diario (clase distinta de `.js-datatable`; tope `datatable_max_length` = 100).
- Filtros y forms con `<x-searchable-select>` + Alpine (filtro origen Reporte Diario).
- Confirmacion UI borrado duro Acreditados; modal import masivo; confirm replace Reporte Diario.
- Sin entry Vite dedicada de charts (placeholders Dashboard).

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan db:seed --class=AcreditacionCargoSeeder` + `php artisan app:sync-permissions`.
2. Asignar manualmente board + view (+ edit) a usuario GH; verificar sidebar y pestanas.
3. Seed catalogo visible; CRUD una fila de prueba; probar bloqueo delete/rename APO con acreditados.
4. CRUD Acreditados con cedula real de Ficha; filtros, export, plantilla e import upsert.
5. Casos estado: solicitud, vencido, por vencer (≤21), acreditado; ambas fechas vacias rechazado.
6. Reporte Diario: carga 1 y 2 orígenes; replace parcial; fecha futura rechazada; headers invalidos sin mutar; ver cargas; export.
7. `php artisan acreditaciones:sync-estados` (y `--dry-run` si aplica).
8. `php artisan test --compact --filter=Acreditacion` (incluye `AcreditacionesReporteDiarioTest`).

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Export sin tope de memoria | FEAT-036 obs. #1 / FEAT-037 obs. #3: chunk/stream o tope documentado |
| Unique Form Request vs `forCargoApo` | Review FEAT-036 obs. #2: `Rule::unique` exacto vs LOWER/TRIM en import |
| Tests sin asercion `audit_logs` | FEAT-036 obs. #3; FEAT-037 obs. #2 (import reporte diario) |
| Calculator sin fechas → `ACREDITADO` | FEAT-036 obs. #4: path defensivo; Form Request ya rechaza |
| Cedulas no cargadas en Ficha | Import Acreditados fallara hasta tener Ficha; Reporte Diario no bloquea |
| Drift `full_name` vs Ficha | Snapshot al guardar/import Acreditados; sync diario no refresca nombres |
| Metadata audit con `document_number` | Aceptable; endurecer PII si politica lo exige |
| Pestaña Export Apo vs export Excel | Nombres distintos; placeholder no implementa export APO |
| Test dual-file headers invalidos | FEAT-037 obs. #1: cobertura 2 archivos (uno OK + uno bad) pendiente |
| Confirm replace UX | FEAT-037 obs. #4: checkbox puede venir pre-marcado tras error |
| Perdida datos por replace duro | Confirm UI; sin versionado (ley negocio); documentado en doc usuario |
| Listado cargas sin paginar | FEAT-037 nit N2: `cargasList()->get()` OK v1 |

## Archivos clave

- Config: `config/acreditaciones.php`, `config/access.php`, `config/audit.php`
- Migrations: `*_create_acreditacion_cargos_table`, `*_create_acreditacion_acreditados_table`, `*_create_acreditacion_reporte_diario_cargas_table`, `*_create_acreditacion_reporte_diario_filas_table`
- Seeder: `AcreditacionCargoSeeder`
- Factories: `AcreditacionAcreditadoFactory`, `AcreditacionCargoFactory`, `AcreditacionReporteDiarioCargaFactory`, `AcreditacionReporteDiarioFilaFactory`
- Tests: `tests/Feature/GestionHumana/Acreditacion*.php`, `Acreditaciones*Test.php`, `AcreditacionesReporteDiarioTest.php`
- Vistas: `resources/views/areas/gestion_humana/acreditaciones/`
- Schedule: `bootstrap/app.php`

## Ownership

| Capa | Ubicacion |
| --- | --- |
| Rutas | `routes/areas/gestion_humana.php` (grupo Acreditaciones) |
| Controlador | `App\Http\Controllers\GestionHumana\AcreditacionesController` |
| Vistas | `resources/views/areas/gestion_humana/acreditaciones/` |
| Doc tecnica | `docs/modules/acreditaciones.md` |
| Doc usuario | `docs/user/acreditaciones.md` |

Shared-files FEAT-036: `config/access.php`, rutas GH, nav, `config/audit.php`, schedule, `PermissionCatalog`.  
Shared-files FEAT-037: `routes/areas/gestion_humana.php` (sin `access.php`).

## Referencias

- Feature Brief: [`docs/briefs/FEAT-036.md`](../briefs/FEAT-036.md), [`docs/briefs/FEAT-037.md`](../briefs/FEAT-037.md)
- Review: [`docs/reviews/FEAT-036.md`](../reviews/FEAT-036.md), [`docs/reviews/FEAT-037.md`](../reviews/FEAT-037.md)
- Doc usuario: [`docs/user/acreditaciones.md`](../user/acreditaciones.md)
- Access: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Ownership: [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md)

## Control de cambios (tecnico)

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.1 | 2026-09-24 | FEAT-037: Reporte Diario APO operativo (tablas, rutas, carga/replace, DT, export, cargas). |
| 1.0 | 2026-09-23 | FEAT-036: tablero Acreditaciones fase 1 (shell, Acreditados, Catálogo, import, sync). |
