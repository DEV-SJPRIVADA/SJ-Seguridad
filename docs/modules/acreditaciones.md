# Modulo Acreditaciones

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/acreditaciones.md`.
> Features: FEAT-036 (fase 1) + FEAT-037 (Reporte Diario APO) + FEAT-038 (Validaciones) + FEAT-039 (Export Apo SuperVigilancia + Dashboard, 2026-09-28, aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para controlar personal acreditado (vigencia, solicitud en tramite, estados automaticos), administrar el **catalogo de cargos** (Manager / APO / Informe / Acreditacion), conservar el **historico diario de snapshots APO** (Reporte Diario), **cruzar** Ficha activa / Acreditados / Reporte Diario en **Validaciones**, generar el archivo **APO SuperVigilancia** (`.xls`) desde candidatos a nueva acreditacion/renovacion (**Export Apo**) y consultar KPIs de lectura en **Dashboard**.

## Alcance actual

- Tablero sidebar **Acreditaciones** (`board` key `acreditaciones`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestanas: **Dashboard**, **Acreditados**, **Reporte Diario**, **Validaciones**, **Export Apo**, **Catálogo**. **Catálogo**, **Validaciones** y **Export Apo** solo visibles/operativas con `acreditaciones.edit`. **Dashboard** con `acreditaciones.view`.
- **Funcional:**
  - **Acreditados** — CRUD, filtros, DataTables server-side, export Excel, plantilla/import upsert, estados calculados, sync diario.
  - **Catálogo** cargos — seed 17 + CRUD; **sección parámetros Export Apo** (fila única editable, FEAT-039).
  - **Reporte Diario** (FEAT-037) — carga 1–2 Excel APO (Enproceso / Acreditado APO), fecha de reporte ≤ hoy, replace parcial por origen, DT server-side, export filtrado, listado de cargas (metadata). Snapshot historico **independiente** de `acreditacion_acreditados`; **sin** cruce Ficha ni `AcreditacionEstadoCalculator` en esa pestana.
  - **Validaciones** (FEAT-038) — gate (ambos origenes APO del dia), **Ejecutar validaciones**, 4 colas operativas (DT server-side desde cache efimera), acciones (Abrir Ficha / Editar / Nuevo con cedula precargada), export por cola + consolidado (4 hojas). **Sin** historico de corridas en BD; **sin** migracion.
  - **Export Apo** (FEAT-039) — candidatos (universo estados + Ficha activa); **Validar** carga preview con columnas SuperVigilancia A–X + Valida/motivo (sin DT ni filtros de listado); selección manual; **quitar fila** solo en front (no sale al `.xls`); política vigencia; modal incluir novedades blandas; generación `.xls` (Writer Xls; **sin** Valida/motivo); seq diario persistido; audit generate. Desde **Acreditados** / **Validaciones**: selección (todas las páginas del filtro) + botón icono **Cargar en Export Apo** abre preview solo de esos IDs (fuera de universo → bloqueo duro/`motivo`).
  - **Dashboard** — KPIs por estado (Total + EN PROCESO / ACREDITADO / POR VENCER / DESACREDITADO), filtros (fecha solicitud, cargo APO, `ficha_estado` activo por defecto, año tendencia) y gráficos ApexCharts (estado, cargo APO, tendencia mensual). Los filtros afectan KPIs y gráficos. Sin corridas Export Apo ni KPIs de candidatos/novedad.
- Permisos: `view.board.gestion_humana.acreditaciones`, `acreditaciones.view`, `acreditaciones.edit`. **Sin permiso nuevo** para Reporte Diario, Validaciones ni Export Apo/Dashboard. Bypass runtime: `manage.users`. Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` via `app:sync-permissions`.
- Tipo de acreditacion (pestana Acreditados) = valor **CARGO APO** (texto); unicidad `(document_number, cargo_apo)`; re-import → upsert.
- Cedula obligatoria en `employee_ficha_profiles` **solo** en Acreditados; en Reporte Diario la cedula ausente en Ficha **no** bloquea. Export Apo exige Ficha **activa** + campos identidad completos (bloqueo duro si incompleta).
- ESTADO (Acreditados) solo calculado (`AcreditacionEstadoCalculator`); no editable. Reporte Diario: Estado APO del Excel Enproceso tal cual; filas con Vigen.Acr muestran/persisten `ACREDITADO` (sin calculadora del módulo Acreditados). Validaciones **lee** `estado` ya persistido (no recalcula).
- Audit: `AcreditacionesAuditLogService` → `SystemAuditService` (`module=acreditaciones`, `area=gestion_humana`). Solo mutaciones (+ resumen import Acreditados y Reporte Diario + `export_apo_generate` + update settings Export Apo). **No** auditar GET/preview/DT/metrics ni la corrida de Validaciones.
- Selectores: `<x-searchable-select>` (prohibido Select2). Export listados: `BaseExport` + `<x-export-excel>` (prohibido `excelHtml5`); consolidado Validaciones = clase dedicada multi-hoja; Export Apo SuperVigilancia = `AcreditacionExportApoXlsExport` (PhpSpreadsheet Writer Xls, **no** BaseExport).
- Borrado **duro** en Acreditados (sin soft-delete). Replace duro por origen en Reporte Diario (sin versionado del mismo dia). Corridas Export Apo: metadata en BD (sin versionado de archivos en disco).

### Fuera de alcance

- Historico de corridas de Validaciones; «marcar revisado» / motivo / estados de cola.
- Bridge, upsert o sync masivo hacia Acreditados desde Reporte Diario, Validaciones o Export Apo.
- Match fuzzy / «contiene» de cargos en Validaciones.
- Recalcular estados del snapshot APO con `AcreditacionEstadoCalculator`.
- Versionado / soft-delete de cargas del mismo dia; comparacion dia vs dia / diff.
- Notificaciones por correo.
- Soft-delete / historial versionado de Acreditados.
- Bridge editable Ficha ↔ Acreditados; sync automatico de nombres tras cambio en Ficha.
- Derivar campo CARGO desde el catalogo de acreditaciones (sí se deriva desde Ficha `position_name`).
- Permiso nuevo / cambios de paquetes en `config/access.php` (FEAT-037/038/039).
- Auto-ejecucion de Validaciones al entrar o al cambiar fecha.
- Auto-export Export Apo sin seleccion; poblar masivamente `curso_tipos.cursos` (CodigoCurso APO); ApexCharts obligatorios en Dashboard Acreditaciones.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `acreditados`. `acreditaciones.view` |
| GET | `/dashboard` | `dashboard` | KPIs + ultimas corridas Export Apo. `acreditaciones.view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON KPIs (mismo payload que shell). `acreditaciones.view` |
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
| GET | `/validaciones` | `validaciones` | Shell fecha + gate + botón Ejecutar + resultados. `acreditaciones.edit` |
| POST | `/validaciones/ejecutar` | `validaciones.run` | Body: `fecha_reporte` ≤ hoy; responde conteos + `run_token`. `acreditaciones.edit` |
| GET | `/validaciones/datatable` | `validaciones.datatable` | JSON DT; params `run_token`, `cola`, protocolo DT. `acreditaciones.edit` |
| GET | `/validaciones/bulk-selectable` | `validaciones.bulk-selectable` | JSON ids del filtro (todas las páginas) con `acreditado_id` para Export Apo. `acreditaciones.edit` |
| GET | `/validaciones/exportar` | `validaciones.export` | Excel por cola (`run_token`, `cola`). `acreditaciones.edit` |
| GET | `/validaciones/exportar-consolidado` | `validaciones.export-consolidated` | Excel 4 hojas (`run_token`). `acreditaciones.edit` |
| GET | `/export-apo` | `export-apo` | Shell Validar + preview columnas SuperVigilancia. Query opcional `ids[]` → auto-Validar esos IDs. **`acreditaciones.edit`** |
| POST | `/export-apo/preview` | `export-apo.preview` | Body: `vigencia_policy` (+ `ids` opcional) → filas APO + Valida/motivo. Sin `ids` = universo completo; con `ids` incluye fuera de universo como bloqueo duro. `acreditaciones.edit` |
| POST | `/export-apo/generar` | `export-apo.generate` | Body: ids + policy + `include_novedades` → `.xls` + run. `acreditaciones.edit` |

> **Nota:** no existe ruta `validaciones.gate` JSON; el gate se evalúa en el GET shell (y al POST ejecutar).
| GET | `/catalogo` | `catalogo` | Listado catalogo + sección params Export Apo. `acreditaciones.edit` |
| POST | `/catalogo` | `catalogo.store` | Crear. `acreditaciones.edit` |
| PATCH | `/catalogo/{acreditacionCargo}` | `catalogo.update` | Editar. `acreditaciones.edit` |
| DELETE | `/catalogo/{acreditacionCargo}` | `catalogo.destroy` | Eliminar si reglas OK. `acreditaciones.edit` |
| PATCH | `/catalogo/export-apo-params` | `catalogo.export-apo-params.update` | Actualiza fila unica settings Export Apo. `acreditaciones.edit` |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.acreditaciones` | Ver tablero **Acreditaciones** en sidebar GH |
| `acreditaciones.view` | Shell, **Dashboard** (lectura KPIs/metrics), Acreditados (lectura), Reporte Diario (ver/filtrar/export/listado cargas), export listados. **No** ve ni opera Validaciones, Export Apo ni edita parámetros Catálogo |
| `acreditaciones.edit` | CRUD Acreditados, import Acreditados, Catálogo (cargos + params Export Apo), **cargar/reemplazar** Excel Reporte Diario, **Validaciones**, **Export Apo** (tab, DT, preview, generar, modal novedades) |
| `ficha_empleados.manage` | Solo para habilitar acción **Abrir Ficha** en Validaciones (ruta destino `editFicha`); no abre la pestaña Validaciones |

**Sin permiso nuevo** para Reporte Diario, Validaciones ni Export Apo/Dashboard (reutilizan view/edit). **No** tocar `config/access.php` en FEAT-039.  
`AcreditacionesAccessService::visibleTabsFor`: oculta `catalogo`, `validaciones` y **`export_apo`** si `!canEdit`. Dashboard permanece visible con view.  
**Cambio vs placeholder FEAT-036:** Export Apo pasó de gate `view` a **`edit`**.  
**Paquetes recomendados:** consulta = board + `acreditaciones.view`; operativo = board + view + edit.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `acreditaciones_tabs`, `admin_permission_groups`).  
`PermissionCatalog` no genera `view.board.*.acreditaciones` fuera de `gestion_humana`.  
Sync: `php artisan app:sync-permissions` + re-login.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\AcreditacionesController` | Shell Dashboard/Export Apo/Acreditados/Reporte Diario/Validaciones/Catálogo (vertical slice) |
| `StoreAcreditacionAcreditadoRequest` / `UpdateAcreditacionAcreditadoRequest` | Ficha obligatoria, al menos una fecha, `cargo_apo` activo en catalogo, unique cedula+APO |
| `ImportAcreditacionAcreditadoRequest` | Archivo Excel import Acreditados |
| `ImportAcreditacionReporteDiarioRequest` | Fecha ≤ hoy; ≥1 archivo (`file_proceso` / `file_acreditado`); mimes xlsx/xls/csv; `confirm_replace` boolean |
| `RunAcreditacionValidacionesRequest` | POST ejecutar: `fecha_reporte` ≤ hoy; `authorize` = `canEdit` |
| `AcreditacionValidacionesDatatableRequest` | Query DT: `run_token` (uuid), `cola` ∈ COLAS; `authorize` = `canEdit` |
| `AcreditacionValidacionesExportRequest` | Query export cola/consolidado: `run_token`, `cola` (solo export por cola); `authorize` = `canEdit` |
| `PreviewAcreditacionExportApoRequest` | POST preview: ids + `vigencia_policy`; `authorize` = `canEdit` |
| `GenerateAcreditacionExportApoRequest` | POST generar: ids + policy + `include_novedades`; `authorize` = `canEdit` |
| `UpdateAcreditacionExportApoParamsRequest` | PATCH params fila unica; `authorize` = `canEdit` |
| `StoreAcreditacionCargoRequest` / `UpdateAcreditacionCargoRequest` | CRUD catalogo + reglas rename APO |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/acreditaciones/acreditados.blade.php` | Shell DT server-side + filtros + export |
| `areas/gestion_humana/acreditaciones/reporte-diario.blade.php` | Listado DT + filtros fecha/origen/busqueda + modal carga + modal cargas + export |
| `areas/gestion_humana/acreditaciones/validaciones.blade.php` | Shell fecha + gate + Ejecutar + 4 colas DT + exports; reusa `nuevo-modal` / `edit-modal` |
| `areas/gestion_humana/acreditaciones/catalogo.blade.php` | Listado + CRUD catalogo + sección params Export Apo (fila unica) |
| `areas/gestion_humana/acreditaciones/dashboard.blade.php` | Filtros + KPIs por estado + gráficos ApexCharts |
| `areas/gestion_humana/acreditaciones/export-apo.blade.php` | Vacío hasta **Validar**; preview columnas SuperVigilancia A–X + Sel/Valida/Motivo; modal novedades + generar `.xls` |
| `areas/gestion_humana/acreditaciones/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/acreditaciones/partials/nuevo-modal.blade.php` | Modal crear acreditado (tambien desde Validaciones cola `sin_acreditacion`) |
| `areas/gestion_humana/acreditaciones/partials/edit-modal.blade.php` | Modal editar (identidad bloqueable, CARGO APO searchable-select; tambien desde Validaciones) |
| `areas/gestion_humana/acreditaciones/partials/bulk-update-modal.blade.php` | Modal actualizar seleccionados (fecha solicitud / observaciones) |
| `areas/gestion_humana/acreditaciones/partials/masivos-modal.blade.php` | Modal import masivo Acreditados |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `AcreditacionAcreditado` | `acreditacion_acreditados` | Unique `(document_number, cargo_apo)`; sin FK formal a Ficha ni catalogo |
| `AcreditacionCargo` | `acreditacion_cargos` | Unique `(cargo_manager, cargo_apo)`; `is_active`, `sort_order` |
| `AcreditacionReporteDiarioCarga` | `acreditacion_reporte_diario_cargas` | 1 cabecera por `fecha_reporte` (**unique**); metadata por origen |
| `AcreditacionReporteDiarioFila` | `acreditacion_reporte_diario_filas` | Snapshot; `origen` `PROCESO` \| `ACREDITADO`; FK `carga_id` cascade |
| `AcreditacionExportApoSetting` | `acreditacion_export_apo_settings` | Fila unica (singleton); params empresa SuperVigilancia |
| `AcreditacionExportApoRun` | `acreditacion_export_apo_runs` | Corridas diarias; unique `(export_date, seq)` |

### `acreditacion_acreditados`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | Snapshot desde Ficha |
| `cargo` | string(255) | Derivado de Ficha (`position_name`); no editable libre |
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

### `acreditacion_export_apo_settings` (FEAT-039)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `nit` | string(20) | Seed `9005767186`; prefijo del filename |
| `razon_social` | string(255) | |
| `tipo_documento` | string(10) | Seed `1` |
| `tipo_establecimiento` | string(50) | |
| `telefono_r` | string(30) | |
| `direccion_r` / `direccion_p` | string(255) | |
| `departamento` / `ciudad` | string | |
| `educacion_bm` / `educacion_s` / `discapacidad` | string | |
| `updated_by` | FK users nullable | `nullOnDelete` |
| `timestamps` | | |

Singleton: seeder idempotente y/o `AcreditacionExportApoSetting::singleton()` (crea defaults al primer uso si no hay fila).

### `acreditacion_export_apo_runs` (FEAT-039)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `export_date` | date | Día calendario `America/Bogota` |
| `seq` | unsignedSmallInteger | 1…999; reinicia por día |
| `file_name` | string(80) | Ej. `APO900576718620260928001.xls` |
| `user_id` | FK users nullable | `nullOnDelete` |
| `vigencia_policy` | string(30) | `VIGENTE` \| `VIGENTE_ACTUALIZAR` |
| `include_novedades` | boolean | Modal Sí/No |
| `rows_selected` / `rows_ok` / `rows_novedad` / `rows_blocked` / `rows_exported` | unsigned int | Conteos |
| `created_at` | timestamp | |

Indexes: **unique** `(export_date, seq)`; index `export_date`; index `user_id`.

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
| `AcreditacionesAccessService` | Board / view / edit + tabs visibles (oculta Catálogo, Validaciones y **Export Apo** sin edit) |
| `HasAcreditacionesTabs` | Trait vistas (tab activa / subnav) |
| `AcreditacionesAuditLogService` | Wrapper audit (`acreditacion_acreditado`, `acreditacion_cargo`, `acreditacion_import`, `acreditacion_reporte_diario_carga`) |
| `AcreditacionEstadoCalculator` | Prioridad estados Acreditados + `syncAll` (chunk 200); **no** usado en Reporte Diario ni en el cruce de Validaciones |
| `AcreditacionAcreditadoListService` | Query filtrada Acreditados (export / reuso) |
| `AcreditacionAcreditadoDatatableService` | DT server-side Acreditados; tope length 100; sin `-1` |
| `AcreditacionImportService` | Upsert Excel Acreditados; reporte fallos |
| `AcreditacionReporteDiarioImportService` | Parse 1–2 Excel APO; validar headers de todos antes de mutar; replace parcial; skip filas malas |
| `AcreditacionReporteDiarioListService` | Query filtrada + listado cargas; export reusa `all(filters)` |
| `AcreditacionReporteDiarioDatatableService` | DT server-side Reporte Diario; tope length 100 |
| `AcreditacionCargoCatalogService` | Proteccion delete/rename APO referenciado |
| `AcreditacionCargoMatchNormalizer` | Normaliza cedula/cargo (trim, espacios, uppercase MB, strip diacriticos) para match exacto |
| `AcreditacionValidacionesGateService` | Gate: cabecera del dia + `origenHasPriorData(PROCESO)` + `origenHasPriorData(ACREDITADO)` |
| `AcreditacionValidacionesRunnerService` | Calcula las 4 colas; `sin_acreditacion` incluye cargo Ficha + personal_tipo; arma `ficha_entry_id` cuando aplica |
| `AcreditacionValidacionesResultStore` | Cache get/put keyed `user_id` + fecha + `run_token`; TTL `config('acreditaciones.validaciones.cache_ttl_seconds')` (5400 s); `get` valida `user_id` del payload |
| `AcreditacionValidacionesRowFilter` | Filtros AJAX por cola sobre filas en cache (cédula, nombre, cargo, cargo_apo, estado, personal_tipo) |
| `AcreditacionValidacionesDatatableService` | DT server-side por cola desde cache + RowFilter; tope length 100; sin `-1` |
| `AcreditacionValidacionesExportService` | Export por cola (`BaseExport`) + consolidado (`AcreditacionValidacionesConsolidatedExport`, 4 hojas) |
| `AcreditacionExportApoCandidateService` | Universo candidatos Export Apo (sin DT) |
| `AcreditacionExportApoRowResolver` | Ficha/cargo/curso/novedades; Genero 1/2; match F\|R |
| `AcreditacionExportApoPreviewService` | Preview Valida/motivo por ids seleccionados |
| `AcreditacionExportApoGenerateService` | Seq diario + writer `.xls` + persist run + audit |
| `AcreditacionDashboardService` | KPIs + charts filtrados (fecha solicitud, cargo APO, ficha_estado, año) |
| `SyncAcreditacionEstadosCommand` | `acreditaciones:sync-estados` (`--date`, `--dry-run`) |

Config: `config/acreditaciones.php` → `reporte_diario`, `validaciones`, **`export_apo`** (headers A–X, politicas vigencia, seq_pad, dashboard_last_n / dashboard_novedad_*).  
Nav: `NavigationResolver`, `SidebarVisibilityService`, `User`.  
Schedule: `bootstrap/app.php` → diario **06:20** `America/Bogota`, `withoutOverlapping` (solo sync estados Acreditados; no aplica a Reporte Diario ni Validaciones).

## Reglas de negocio

### Acceso

1. Solo `acreditaciones.view` (+ board): ve **Dashboard** operativo, Acreditados lectura, Reporte Diario (filtrar/export/cargas); **sin** botones mutacion, **sin** Catálogo, **sin** Validaciones, **sin** Export Apo, **sin** carga de reporte ni editar params.
2. `acreditaciones.edit`: CRUD Acreditados + import + Catálogo (cargos + params Export Apo) + cargar/reemplazar Reporte Diario + **Validaciones** + **Export Apo** (shell, DT, preview, generar, modal).
3. Bypass: `manage.users`.
4. Abrir Ficha (Validaciones): además requiere `ficha_empleados.manage` (via `FichaEmpleadosAccessService::canManage`) y `ficha_entry_id` resuelto; si no → ocultar acción. No filtra filas de cola.

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
7. `cargo` (texto) se toma siempre de `employee_ficha_profiles.position_name` (lookup/alta/edicion/import); si la ficha no tiene cargo → error.
8. Filtros listado/export: `estado` (acreditacion), `ficha_estado` (`activo` por defecto | `desvinculado` | `todos` via `employee_ficha_profiles.employment_status` por cedula), `document_number` (parcial), `cargo` (select cargos Ficha activos / parcial), `cargo_apo`, rango `vigencia_desde` / `vigencia_hasta` sobre `vigencia_acr`.
9. Acción masiva (edit): checkboxes + seleccionar todos del filtro (`bulk-selectable`); modal para sobrescribir `observaciones` y/o `fecha_solicitud` (al menos un campo); con fecha → recalcula estado (EN PROCESO).
10. Eliminar: confirmacion UI; DELETE fisico.

### Catalogo

11. Seed 17 filas idempotente (`AcreditacionCargoSeeder`, upsert por manager+APO).
12. DELETE bloqueado si es la **ultima** fila activa con ese `cargo_apo` y hay acreditados con ese tipo.
13. UPDATE de `cargo_apo` bloqueado si el valor anterior esta referenciado por acreditados (editar Manager/Informe/Acreditacion libremente).
13b. **Parámetros Export Apo** (misma pestaña Catálogo): una sola fila editable (`PATCH catalogo.export-apo-params`); valores se copian a todas las filas del `.xls`. Seed cerrado (Nit `9005767186`, etc.).

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

### Validaciones (FEAT-038)

Codigos de cola (`AcreditacionValidacionesResultStore::COLAS` / `config('acreditaciones.validaciones.colas')`):

| Code | Label UI | Fuente |
| --- | --- | --- |
| `sin_acreditacion` | Ficha activa sin acreditación | `employee_ficha_profiles` activos sin ninguna fila en `acreditacion_acreditados`. Filas incluyen `cargo` (`position_name`) y `personal_tipo` (`OPERATIVO` si `operating_area_key=operaciones` en la requisición vinculada; `ADMINISTRATIVO` si hay otra área; vacío sin requisición) |
| `ausente_reporte` | Acreditado ausente del reporte del día | Acreditados cuyo par `(norm(doc), norm(cargo_apo))` ∉ unión de pares del reporte (cualquier origen). Columna `cargo` = `position_name` de Ficha (no el texto histórico del acreditado) |
| `en_proceso_ya_acreditado` | EN PROCESO en sistema / ACREDITADO en APO | Acreditados `EN_PROCESO` cuyo par ∈ filas origen `ACREDITADO` del día. Incluye `vigencia_apo` = `vigencia_acr` de la fila APO del match cédula+cargo |
| `vencidas` | Vencidas / por vencer | Acreditados `estado IN (DESACREDITADO, POR_VENCER)`; **independiente** del reporte |

25. Pestaña / todos los endpoints `validaciones*`: `canEdit`. Usuario solo view → 403 y sin tab.
26. Fecha default UI = hoy (`America/Bogota`); futuro → 422 al ejecutar. **No** auto-ejecutar al entrar ni al cambiar fecha (cambiar fecha limpia resultados en UI).
27. Gate OK solo si existe cabecera del día **y** `origenHasPriorData(PROCESO)` **y** `origenHasPriorData(ACREDITADO)`. Sin cabecera / falta origen → mensaje + link a Reporte Diario; no ejecuta.
28. Al Ejecutar (gate OK): Runner arma 4 colas → ResultStore cache (`acreditaciones:validaciones:{userId}:{fecha}:{token}`, TTL 5400 s) → responde conteos + `run_token`.
29. DT / export leen solo la corrida del `run_token` del usuario; cache miss / token ajeno → vacío o 422 («Ejecute validaciones primero»).
30. Match cédula: trim + case-insensitive via normalizer. Match cargo: **solo** `cargo_apo` (Acreditados) vs `cargo` (fila reporte); igualdad exacta del string normalizado (trim, colapsar espacios, `mb_strtoupper`, strip diacríticos NFD). **Prohibido** `str_contains` / fuzzy / usar campo `cargo` texto libre del acreditado.
31. Granularidad: una fila por par cédula+cargo en colas 2–4; cola 1 una fila por cédula Ficha (con cargo Ficha + tipo personal).
32. Acciones: Abrir Ficha (si manage Ficha + entry); Editar → modal existente por `acreditado_id`; Nuevo (solo `sin_acreditacion`) → modal crear con cédula precargada + lookup. Tras mutación: UX pide re-ejecutar (sin «marcar revisado»).
33. Export por cola: `BaseExport` + `<x-export-excel>`. Consolidado: `AcreditacionValidacionesConsolidatedExport` (4 hojas). Sin histórico en BD; **sin migración**.
33b. Filtros por cola (AJAX en DT, `AcreditacionValidacionesRowFilter`): cédula/nombre en todas; `sin_acreditacion` + cargo Ficha + personal_tipo; restantes + cargo/cargo_apo/estado según columnas.

### Export Apo SuperVigilancia (FEAT-039)

34. Universo candidatos: `acreditacion_acreditados` con `estado IN ('EN_PROCESO','POR_VENCER','DESACREDITADO')` y Ficha `employment_status = activo`. Excluye `ACREDITADO` «fresco». Sin Ficha activa → fuera del universo.
35. Una fila por par cédula + `cargo_apo` (multicargo = N filas). Export solo de IDs **seleccionados** (mínimo 1); IDs revalidados contra universo.
36. Parámetros empresa (Nit, RazonSocial, TipoDocumento, …) desde settings; iguales en todas las filas.
37. Identidad desde Ficha: `NoDocumento`, nombres/apellidos, `FechaNacimiento`, `Fechavinculacion`. Obligatorios sin bloqueo duro: `document_number`, `first_name`, `first_surname`, `birth_date`, `sex`, `hire_date`. `second_name` / `second_surname` pueden vacíos.
38. **Genero** (columna J): implementación mapea Ficha `sex` a códigos SuperVigilancia **`1`** (masculino: M/1/MASCULINO/…) y **`2`** (femenino: F/2/FEMENINO/…). Si no reconoce el valor, emite el raw. *(Review FEAT-039 obs. #1: brief S3 decía M/F tal cual; documentar como está implementado.)*
39. `Cargo` Excel = `cargo_acreditacion` resuelto desde catálogo activo por `cargo_apo` (desempate `sort_order`, `id`). Sin activa → novedad blanda; activas con valores distintos → novedad blanda «cargo ambiguo».
40. **Match curso F|R** (mismo cargo; no mezclar):
    1. Cursos de la cédula con `cursoTipo`.
    2. Familia F|R: `tipo_curso` normalizado (sin espacios) match `/^[FR]\./` o igual a `F.{apo}` / `R.{apo}`.
    3. Mismo cargo si **cualquiera**: `tipo_curso` ∈ {`F.{apo}`, `R.{apo}`}; o `cargo_acredit` ≡ `cargo_apo`; o `cargo_acredit` ≡ `cargo_acreditacion` del catálogo APO (el catálogo Cursos real usa códigos `1,2,4,5,6`, no el nombre ESCOLTA).
    4. Política vigencia: `VIGENTE` solo vigencia VIGENTE; `VIGENTE_ACTUALIZAR` = VIGENTE ∪ ACTUALIZAR (`EmployeeCurso::computeVigencia()`).
    5. Elegir max `fecha_expedicion` (empate max `id`).
    6. `CodigoCurso` = `trim(curso_tipos.cursos)`; vacío → novedad blanda.
    7. `NitEscuela`: primero snapshot `employee_cursos.escuela_nit` (columna NIT de Registros); si vacío, FK a escuela activa; si aún vacío y hay `numero_curso`, extraer código (`ECSP0015-M…` → `15` vía `CursoEscuela::extractCodigoFromNumeroCurso`) y buscar NIT en catálogo Escuelas activo. Fallo → novedad blanda. `Nro` = `numero_curso`.
41. Fechas celdas: string `dd/mm/yyyy`. Headers A–X exactos (`config('acreditaciones.export_apo.headers')`); 1 hoja titulada **`ApoDatos`**; extensión `.xls`.
42. Preview: columnas negocio + `valida` + `motivo` (**no** van al xls). Novedades:

| Codigo | Tipo | Modal Sí incluye? |
| --- | --- | --- |
| `ficha_incompleta` | **Dura** | **No** |
| `sin_curso_match` / `curso_vencido_sin_alterna` / `codigo_curso_vacio` / `escuela_no_catalogo` / `cargo_acreditacion_no_resoluble` / `cargo_acreditacion_ambiguo` | Blanda | Sí |

43. Default export: solo `valida=true`. Modal Sí: limpia + blandas; **nunca** duras aunque `include_novedades=true`.
44. Filename: `APO` + `settings.nit` + `Ymd` (Bogotá) + `str_pad(seq, 3, '0')` + `.xls`. Seq diario persistido; unique `(export_date, seq)` + reintento. Clave config `filename_prefix` es legacy no usada (el prefijo real usa `settings.nit`).
45. Writer: `App\Exports\AcreditacionExportApoXlsExport` (PhpSpreadsheet `Writer\Xls`); **no** BaseExport / excelHtml5.
46. Tab + rutas Export Apo: **`acreditaciones.edit`**. GET sin edit → 403.

### Dashboard

47. Lectura con `acreditaciones.view`: KPIs (Total + conteos por `estado`) y gráficos (estado, cargo APO, tendencia solicitudes por `fecha_solicitud`, tendencia vencimientos por `vigencia_acr`).
48. Filtros compartidos KPIs/gráficos: `fecha_desde`/`fecha_hasta` (solicitud), `cargo_apo`, `ficha_estado` (`activo` por defecto | `desvinculado` | `todos`, mismo criterio que listado/export Acreditados vía `employee_ficha_profiles`), `anio` (ambas tendencias mensuales). Endpoint `dashboard.metrics` JSON. Sin corridas Export Apo ni KPIs candidatos/novedad.
49. Endpoint `dashboard.metrics` JSON; sin mutaciones; sin ApexCharts obligatorio.

### Auditoria

50. Eventos: `acreditacion_acreditado` create/update/delete; `acreditacion_cargo` create/update/delete; `acreditacion_import` (resumen ok/fail Acreditados); `acreditacion_reporte_diario_carga` action `imported`; **`export_apo_generate`** (file_name, conteos, opciones); **`export_apo_settings`** update.
51. No auditar GET/list/datatable/export/preview/metrics, sync diario fila a fila, ni la corrida de Validaciones.

## Import / export Excel

### Plantilla / import Acreditados

Clase plantilla: `App\Exports\AcreditacionesImportTemplateExport`.  
Fila 1 claves tecnicas (`config/acreditaciones.php` → `import.columns`), fila 2 labels, datos desde fila 3.

| Clave | Label | Persistencia |
| --- | --- | --- |
| `document_number` | CEDULA | Si; required; debe existir en Ficha |
| `full_name` | NOMBRE COMPLETO | Ignorado (ayuda humana) |
| `cargo` | CARGO | Se ignora el valor Excel; se toma `position_name` de Ficha (obligatorio en ficha) |
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

### Export Validaciones

- Por cola: `AcreditacionValidacionesExportService` + `BaseExport`; query `run_token` + `cola`; auth `acreditaciones.edit`.
- Consolidado: `App\Exports\AcreditacionValidacionesConsolidatedExport` (PhpSpreadsheet, 4 hojas = 4 colas); query `run_token`.
- UI: `<x-export-excel>` en la vista Validaciones.
- Sin token / cache expirada → error claro (422 o redirect).

> Observacion review FEAT-036 #1 / FEAT-037 #3: exports Acreditados/Reporte Diario hacen `filteredQuery()->get()` sin tope; volumen alto puede saturar memoria. Follow-up: chunk/stream si crece el filtro. Validaciones exporta desde cache de la corrida (volumen acotado a las colas). Export Apo genera solo filas seleccionadas (no todo el universo).

### Export Apo SuperVigilancia (.xls)

- Clase: `App\Exports\AcreditacionExportApoXlsExport` + PhpSpreadsheet `Writer\Xls` (streaming/download).
- Headers A–X exactos; 1 hoja **`ApoDatos`**; fechas `dd/mm/yyyy`; Genero `1`/`2`; sin columnas Valida/motivo.
- Auth: `acreditaciones.edit`. Nombre `APO{nit}{yyyymmdd}{seq3}.xls`.

## JavaScript / assets

- DataTables `serverSide: true` en Acreditados, Reporte Diario, Validaciones (4 colas) y **Export Apo candidatos** (tope length 100; sin `-1`).
- Filtros y forms con `<x-searchable-select>` + Alpine (origen Reporte Diario; política vigencia Export Apo). Fecha Validaciones: `input type="date"`.
- Confirmacion UI borrado duro Acreditados; modal import masivo; confirm replace Reporte Diario; **modal Sí/No novedades blandas** antes de generar Export Apo.
- Validaciones: botón Ejecutar (no auto); acciones delegadas Abrir Ficha / Editar / Nuevo; reuso modales Acreditados.
- Dashboard Acreditaciones: filtros + KPIs + ApexCharts (`resources/js/acreditaciones-dashboard-charts.js`).

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan db:seed --class=AcreditacionCargoSeeder` (+ opcional `AcreditacionExportApoSettingSeeder`) + `php artisan app:sync-permissions`.
2. Asignar manualmente board + view (+ edit) a usuario GH; verificar sidebar y pestanas (Validaciones / Export Apo / Catálogo solo con edit; Dashboard con view).
3. Seed catalogo visible; CRUD una fila de prueba; probar bloqueo delete/rename APO con acreditados; editar params Export Apo en Catálogo.
4. CRUD Acreditados con cedula real de Ficha; filtros, export, plantilla e import upsert.
5. Casos estado: solicitud, vencido, por vencer (≤21), acreditado; ambas fechas vacias rechazado.
6. Reporte Diario: carga 1 y 2 orígenes; replace parcial; fecha futura rechazada; headers invalidos sin mutar; ver cargas; export.
7. Validaciones: día con ambos orígenes → Ejecutar → 4 colas; día parcial → gate falla; acciones Ficha/editar/nuevo; export cola + consolidado; usuario solo view sin tab.
8. Export Apo: candidatos EN_PROCESO/POR_VENCER/DESACREDITADO; **Validar** carga preview A–X + Valida/motivo (sin DT/filtros); modal Sí/No; ambas políticas vigencia; ficha incompleta no sale; filename seq 001/002 mismo día; tipos curso con `cursos` (CodigoCurso) y `cargo_acredit`.
9. Dashboard: KPIs + metrics + ultimas corridas; usuario solo view OK; GET export-apo → 403.
10. `php artisan acreditaciones:sync-estados` (y `--dry-run` si aplica).
11. `php artisan test --compact` filtros Export Apo / Dashboard / BoardAccess (o `--filter=Acreditacion`).

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Export sin tope de memoria | FEAT-036 obs. #1 / FEAT-037 obs. #3: chunk/stream o tope documentado (Acreditados/Reporte Diario) |
| Unique Form Request vs `forCargoApo` | Review FEAT-036 obs. #2: `Rule::unique` exacto vs LOWER/TRIM en import |
| Tests sin asercion `audit_logs` | FEAT-036 obs. #3; FEAT-037 obs. #2 (import reporte diario) |
| Calculator sin fechas → `ACREDITADO` | FEAT-036 obs. #4: path defensivo; Form Request ya rechaza |
| Cedulas no cargadas en Ficha | Import Acreditados fallara hasta tener Ficha; Reporte Diario no bloquea |
| Drift `full_name` vs Ficha | Snapshot al guardar/import Acreditados; sync diario no refresca nombres |
| Metadata audit con `document_number` | Aceptable; endurecer PII si politica lo exige |
| Pestaña Export Apo vs export Excel listados | Nombres distintos; Export Apo = SuperVigilancia `.xls` (edit); exports listados = BaseExport |
| Genero Excel 1/2 vs M/F | Implementado 1/2; contrastar con archivo APO real (review obs. #1) |
| `curso_tipos.cursos` vacío | Novedad blanda; GH debe cargar CodigoCurso APO en Catálogo Cursos |
| Seeder settings no en DatabaseSeeder | Runtime `singleton()` mitiga; checklist deploy seed class (review obs. #3) |
| KPI novedad `VIGENTE_ACTUALIZAR` | Config actual; brief sugería VIGENTE (review obs. #2) |
| Test dual-file headers invalidos | FEAT-037 obs. #1: cobertura 2 archivos (uno OK + uno bad) pendiente |
| Confirm replace UX | FEAT-037 obs. #4: checkbox puede venir pre-marcado tras error |
| Perdida datos por replace duro | Confirm UI; sin versionado (ley negocio); documentado en doc usuario |
| Listado cargas sin paginar | FEAT-037 nit N2: `cargasList()->get()` OK v1 |
| `cargo_apo` ≠ texto Cargo Excel APO | FEAT-038: normalización estricta; falsos «ausentes» si redacción distinta — sin fuzzy v1 |
| Cache Validaciones expirada / multi-servidor | TTL 5400 s; mensaje «vuelva a ejecutar»; store default del entorno |
| Test aislamiento token entre editores | FEAT-038 obs. #1: follow-up opcional (key ya aísla por user_id) |
| Mapa Ficha Abrir Ficha vs case cédula | FEAT-038 obs. #2: riesgo bajo (cédulas numéricas) |

## Archivos clave

- Config: `config/acreditaciones.php` (`export_apo`), `config/access.php` (**sin** cambio FEAT-039), `config/audit.php`
- Migrations: cargos, acreditados, reporte_diario_*, **`acreditacion_export_apo_settings`**, **`acreditacion_export_apo_runs`** (aditivas multi-driver; **sin** migración FEAT-038)
- Seeders: `AcreditacionCargoSeeder`, `AcreditacionExportApoSettingSeeder`
- Factories: Acreditado, Cargo, ReporteDiario*, `AcreditacionExportApoSettingFactory`, `AcreditacionExportApoRunFactory`
- Tests: `AcreditacionesExportApo*Test`, `AcreditacionesDashboardTest`, `AcreditacionesBoardAccessTest`, ReporteDiario, Validaciones, …
- Exports: `AcreditacionValidacionesConsolidatedExport`, **`AcreditacionExportApoXlsExport`**
- Vistas: `resources/views/areas/gestion_humana/acreditaciones/` (`dashboard`, `export-apo`, `catalogo`, …)
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
Shared-files FEAT-037/038/039: `routes/areas/gestion_humana.php` (sin `access.php`).

## Referencias

- Feature Brief: [`docs/briefs/FEAT-036.md`](../briefs/FEAT-036.md), [`docs/briefs/FEAT-037.md`](../briefs/FEAT-037.md), [`docs/briefs/FEAT-038.md`](../briefs/FEAT-038.md), [`docs/briefs/FEAT-039.md`](../briefs/FEAT-039.md)
- Review: [`docs/reviews/FEAT-036.md`](../reviews/FEAT-036.md), [`docs/reviews/FEAT-037.md`](../reviews/FEAT-037.md), [`docs/reviews/FEAT-038.md`](../reviews/FEAT-038.md), [`docs/reviews/FEAT-039.md`](../reviews/FEAT-039.md)
- Doc usuario: [`docs/user/acreditaciones.md`](../user/acreditaciones.md)
- Access: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Ownership: [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md)
- Cursos (CodigoCurso / cargo_acredit): [`docs/modules/cursos.md`](cursos.md)

## Control de cambios (tecnico)

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.13 | 2026-09-29 | Dashboard: filtro `ficha_estado` (activo por defecto, alineado con listado/export Acreditados). |
| 1.12 | 2026-09-29 | Dashboard: filtros + gráficos ApexCharts; se quitan KPIs candidatos/novedad y tabla de corridas Export Apo. |
| 1.11 | 2026-09-28 | Export Apo: editar acreditado por fila + Actualizar masivo de seleccionados (vuelve y revalida). |
| 1.10 | 2026-09-28 | Modal novedades Export Apo: diseño chrome; solo si selección tiene Valida=No; si todas válidas genera directo. |
| 1.9 | 2026-09-28 | Acreditados/Validaciones: checks + seleccionar filtro completo → Cargar en Export Apo (auto-Validar IDs); preview fuera de universo = bloqueo; quitar fila en front. |
| 1.8 | 2026-09-28 | Export Apo NitEscuela: snapshot NIT del registro; si vacío, código de No.CURSO → catálogo Escuelas. |
| 1.7 | 2026-09-28 | Export Apo: solo preview SuperVigilancia (sin DT/filtros); Validar = universo completo; columnas A–X + Valida/Motivo. |
| 1.6 | 2026-09-28 | Export Apo: Validar bajo demanda + Preview restaurado; match F\|R acepta `cargo_acredit` como código de catálogo (p. ej. `2` = ESCOLTA). |
| 1.5 | 2026-09-28 | FEAT-039: Export Apo SuperVigilancia (`.xls`) + Dashboard; settings/runs; Genero 1/2; match F\|R; gate Export Apo = edit. |
| 1.4 | 2026-09-25 | CARGO en Acreditados/masivos derivado de Ficha `position_name` (lookup, store/update, import). |
| 1.3 | 2026-09-25 | Validaciones: filtros por cola; `sin_acreditacion` con Cargo Ficha + Tipo admin/operativo (área requisición). |
| 1.2 | 2026-09-25 | FEAT-038: Validaciones operativa (gate ambos orígenes, Ejecutar, 4 colas, cache efímera, acciones, export cola+consolidado; permiso solo edit). |
| 1.1 | 2026-09-24 | FEAT-037: Reporte Diario APO operativo (tablas, rutas, carga/replace, DT, export, cargas). |
| 1.0 | 2026-09-23 | FEAT-036: tablero Acreditaciones fase 1 (shell, Acreditados, Catálogo, import, sync). |
