# Modulo Formación

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/formacion.md`.
> Feature: FEAT-041 (revisado 2026-10-01, aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para consultar y recargar el dataset operativo de **formaciones** (~81k filas tipicamente desde Excel), con pestanas **Dashboard** (KPIs) y **Formaciones** (listado server-side, filtros, export, plantilla e import **replace-all**).

**Distincion con Cursos:** este modulo no usa Ficha empleados ni `employee_cursos`. Es un dataset autonomo (`formacion_registros`) alimentado por Excel; categoria y curso son texto libre.

## Alcance actual

- Tablero sidebar **Formación** (`board` key `formacion`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestanas exactas: **Dashboard**, **Formaciones** (`config/access.php` → `formacion_tabs`).
- Permisos: `view.board.gestion_humana.formacion`, `formacion.view`, `formacion.edit`. Bypass runtime: `manage.users`. Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` via `app:sync-permissions`.
- **Sin** pestana parametros / `parameters.edit` / tablas de catalogo.
- **Sin** CRUD fila a fila; unica mutacion = import replace-all.
- Dashboard v1: total, KPIs por estado (aprobado/reprobado/no realizada), distribucion por mes (1–12), top categorias (`config('formacion.dashboard.categoria_top')`, default 10), chart donut por estado; filtros ano, mes, estado, curso.
- Formaciones: DataTables `serverSide: true`; filtros ano, mes, categoria, curso, numero ID, nombre; export Excel; plantilla + import.
- Audit: `FormacionAuditLogService` → `SystemAuditService` (`module=formacion`, `area=gestion_humana`). Eventos: `import_replace`, `export`.
- Selectores: `<x-searchable-select>`. Export: `BaseExport` + `<x-export-excel>` (prohibido Select2 / `excelHtml5`).
- Fuera V1: jobs/colas async de import, soft-delete / historial de versiones del dataset, vinculo Ficha/Cursos, CRUD individual, dashboard avanzado multi-ano.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/formacion` · nombre `gestion-humana.formacion.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `dashboard`. `formacion.view` |
| GET | `/dashboard` | `dashboard` | Vista KPIs/graficos. `formacion.view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON filtros → KPIs/charts. `formacion.view` |
| GET | `/formaciones` | `formaciones` | Shell listado + filtros. `formacion.view` |
| GET | `/formaciones/datatable` | `formaciones.datatable` | JSON DataTables. `formacion.view` |
| GET | `/formaciones/exportar` | `formaciones.export` | Excel filtrado. `formacion.view` |
| GET | `/formaciones/plantilla-importacion` | `formaciones.import-template` | Plantilla vacia. `formacion.edit` |
| POST | `/formaciones/importar` | `formaciones.import` | Replace-all. `formacion.edit` + `confirm_replace` |
| GET | `/formaciones/opciones` | `formaciones.options` | Distincts para filtros. `formacion.view` |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.formacion` | Ver tablero **Formación** en sidebar GH |
| `formacion.view` | Dashboard, listado, filtros, export Excel |
| `formacion.edit` | Plantilla e import replace-all (implica view en `FormacionAccessService`) |

**Implicaciones runtime** (`FormacionAccessService`):

| Otorgado | Efecto |
| --- | --- |
| `formacion.edit` | Puede `formacion.view` aunque Spatie no tenga view |
| `formacion.view` | Dashboard + Formaciones + export; **no** plantilla/import |

**Paquetes recomendados:** consulta = board + `formacion.view`; operativo = board + view + edit.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `formacion_tabs`, Admin subgroup **Formación**). Sync: `php artisan app:sync-permissions` + re-login. **Sin** migracion automatica de permisos legacy.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\FormacionController` | Shell, dashboard, formaciones, DT, export, plantilla, import, opciones |
| `App\Http\Requests\GestionHumana\ImportFormacionRequest` | `import_file` (`extensions:xlsx,xls,csv`, max 50 MB; no `mimes` estricto) + `confirm_replace` accepted; authorize via `canEdit` |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/formacion/dashboard.blade.php` | KPIs + ApexCharts + filtro ano |
| `areas/gestion_humana/formacion/formaciones.blade.php` | Shell DT server-side + filtros + export + import |
| `areas/gestion_humana/formacion/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/formacion/partials/import-modal.blade.php` | Modal plantilla/import + confirmacion replace-all |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `FormacionRegistro` | `formacion_registros` | Sin FK a Ficha; dataset Excel autonomo; factory para tests |

### Columnas `formacion_registros`

| Campo | Tipo | Notas |
| --- | --- | --- |
| `id` | bigIncrements | PK |
| `numero_id` | string(50) | Excel «Número de ID»; index |
| `nombre_completo` | string(255) | Excel «Nombre completo» |
| `fecha_inicio` | date | Excel «Fecha de inicio del curso» |
| `mes` | unsignedTinyInteger | 1–12 derivado de `fecha_inicio` |
| `anio` | unsignedSmallInteger | derivado de `fecha_inicio` |
| `nombre_curso` | string(255) | Excel «Nombre completo del curso»; index |
| `calificacion` | string(50) nullable | Excel «Calificación» |
| `categoria` | string(255) | Excel «Nombre de la categoría»; index |
| `created_at` / `updated_at` | timestamps | |

Indices adicionales: `anio`, `mes`, compuesto `(anio, mes)`.

### Mapeo Excel → BD (`config/formacion.php`)

| Excel (header exacto) | Campo |
| --- | --- |
| Número de ID | `numero_id` |
| Nombre completo | `nombre_completo` |
| Fecha de inicio del curso | `fecha_inicio` (+ `mes`, `anio`) |
| Nombre completo del curso | `nombre_curso` |
| Calificación | `calificacion` (nullable) |
| Estado (solo UI listado) | Derivado: vacío → `No realizada`; numérico `> 7.5` → `Aprobado`; resto numérico → `Reprobado` (`FormacionRegistro::estadoFromCalificacion`). Columna con `status-pill` (success/danger/muted). Filtro `estado` = `aprobado` \| `reprobado` \| `no_realizada`. |
| Nombre de la categoría | `categoria` |

## Servicios / jobs / mail

| Clase | Rol |
| --- | --- |
| `FormacionAccessService` | Board / view / edit + tabs visibles |
| `HasFormacionTabs` | Trait vistas (tab activa / subnav) |
| `FormacionAuditLogService` | Wrapper audit (`module=formacion`, `area=gestion_humana`) |
| `FormacionDatatableService` | DT server-side + filtros + opciones distinct |
| `FormacionImportService` | Parse fechas + replace-all (validate → delete → chunk insert 500) |
| `FormacionDashboardService` | Metrics KPIs/charts por ano + filtros mes/estado/curso |

Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultFormacionBoardUrl()` / tabs (patron Cursos).

Sin jobs/colas en V1; import sincrono.

## Import replace-all

Servicio: `App\Services\GestionHumana\FormacionImportService`  
Columnas: `config/formacion.php` → `import.columns`.

### Flujo obligatorio

1. Validar archivo + headers (fallar **antes** de borrar si faltan columnas).
2. Parsear filas; filas vacias → skip; errores en obligatorias → **rechazar import completo** (dataset intacto). Archivo sin filas validas → rechazar (no vaciar tabla).
3. `DB::transaction`: `FormacionRegistro::query()->delete()` + `insert` por chunks (`config formacion.import.chunk_size`, default 500). **No** `TRUNCATE` / `migrate:fresh`.
4. Audit `import_replace` con metadata `{deleted_before, imported, skipped_empty, errors_count}` (sin volcar PII masiva).
5. Request síncrono endurecido para ~81k filas: `memory_limit` / `time_limit` / `max_rows` en `config/formacion.php`; reader `setReadDataOnly` + `getHighestDataRow()` (evita filas fantasma); lectura con `rawValue` (sin `getCalculatedValue`); liberar spreadsheet antes del insert.

### Columnas obligatorias (fila no vacia)

`numero_id`, `nombre_completo`, `fecha_inicio` (parseable), `nombre_curso`, `categoria`. `calificacion` nullable.

### Parse `fecha_inicio`

Orden: (1) serial Excel `Date::excelToDateTimeObject`; (2) `Y-m-d`; (3) texto ES tipo `jueves, 4 de junio de 2026, 00:00` (mapa meses ES). Derivar `mes`/`anio` de la fecha; no confiar en columnas separadas del Excel.

### Confirmacion UI

Checkbox `confirm_replace` (accepted) + mensaje claro: se eliminaran todos los registros actuales.

## Reglas de negocio

1. Labels exactos: tablero **Formación**; pestanas **Dashboard** / **Formaciones**.
2. Sidebar: `view.board.gestion_humana.formacion` (o bypass). Pestanas: `formacion.view` o `formacion.edit`.
3. Plantilla + import: solo `formacion.edit`. Export: `formacion.view` (o edit via implicacion).
4. Fuente de verdad operativa = tabla tras el ultimo import exitoso.
5. Import exitoso **borra todos** los registros previos y carga el archivo.
6. Headers invalidos o errores en filas obligatorias → **no** borrar dataset.
7. Filtros listado: `anio`, `mes` (1–12), `categoria`, `nombre_curso`, `numero_id`, `nombre`/`nombre_completo` (like), `estado` (`aprobado`/`reprobado`/`no_realizada`).
8. DataTables `serverSide: true`; `lengthMenu` sin `-1`; tope length en servidor (max 100).
9. Dashboard default ano: (1) query valida 2000–2100; (2) ano calendario con datos; (3) `MAX(anio)` con datos; (4) sin datos → ano actual (metricas en cero).
10. Distinto del tablero **Cursos** (naming / tabla / permisos `formacion_*`).

## JavaScript / assets

- Entry Vite: `resources/js/formacion-dashboard-charts.js` (+ `apex-defaults.js`).
- DataTables `serverSide: true` clase `js-formacion-formaciones-datatable` (no `.js-datatable`).
- Modal import compacto: una card, toolbar icon-only (plantilla Excel / elegir / importar via `.req-manage-filters__icon-btn`), confirmación replace-all.
- Filtros: `<x-searchable-select>` (ano, mes, categoria, curso).

## Export Excel

| Clase | Uso |
| --- | --- |
| `App\Exports\FormacionExport` | Export listado filtrado (`BaseExport`); incluye mes/ano derivados |
| `App\Exports\FormacionImportTemplateExport` | Plantilla con headers exactos del mapeo Excel |

Boton `<x-export-excel>`. Auth: `formacion.view`.

> **Observacion review (FEAT-041 #1):** el export materializa `filteredQuery()->get()` en memoria. Con ~81k filas (o filtro amplio) puede agotar memoria/timeout en Hostinger. Follow-up: chunked/`fromQuery` o tope + aviso.

## Validacion local

1. `php artisan migrate` (aditivo; **no** `migrate:fresh`).
2. `php artisan app:sync-permissions`.
3. Asignar board + view (+ edit) a usuario GH; verificar sidebar, 403s plantilla/import sin edit.
4. Plantilla → pocas filas → import → verificar replace; headers rotos → dataset intacto.
5. Parse fecha ES / serial Excel / `Y-m-d`.
6. DT + filtros + export; Dashboard metrics con/sin datos.
7. `php artisan test --compact tests/Feature/GestionHumana/FormacionBoardAccessTest.php tests/Feature/GestionHumana/FormacionFormacionesTest.php tests/Feature/GestionHumana/FormacionImportTest.php tests/Feature/GestionHumana/FormacionDashboardTest.php` (Revisor: 37 passed).
8. `vendor/bin/pint --dirty --format agent` tras PHP; `npm run build` si falta entry charts en Vite manifest.

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Export ~81k en memoria | Review obs. #1: `get()` completo; riesgo Hostinger. Documentado; follow-up chunked. |
| Import ~81k memoria/tiempo | Review obs. #2: valida todo en memoria; `set_time_limit(300)` + chunks insert. Medir con archivo real. |
| Import 0 filas validas | Rechazado: no se vacía la tabla. |
| Tests sidebar board | Review obs. #4: smoke HTTP sidebar pendiente (cableado nav OK). |
| `recordsTotal` DT | Review obs. #5: cuenta sobre query filtrada; UI usa `recordsFiltered` (OK). |
| DISTINCT filtros 81k | Opciones via endpoint/query indexada; no cargar 81k options al cliente. |
| Transaccion larga | Import poco frecuente; evitar listado concurrente durante import. |
| Colision conceptual con Cursos | Naming UI **Formación**; tabla/permisos `formacion_*`. |

## Archivos clave

- Controllers: `FormacionController`
- Models: `FormacionRegistro` (+ factory)
- Services: `FormacionAccessService`, `FormacionDatatableService`, `FormacionImportService`, `FormacionDashboardService`, `FormacionAuditLogService`
- Exports: `FormacionExport`, `FormacionImportTemplateExport`
- Config: `config/formacion.php`, `config/access.php`, `config/audit.php`
- Trait: `HasFormacionTabs`
- Vistas: `resources/views/areas/gestion_humana/formacion/`
- Migration: `2026_10_01_142408_create_formacion_registros_table.php`
- Tests: `tests/Feature/GestionHumana/Formacion*.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-041.md`](../briefs/FEAT-041.md)
- Review: [`docs/reviews/FEAT-041.md`](../reviews/FEAT-041.md) (Aprobado con observaciones)
- Doc usuario: [`docs/user/formacion.md`](../user/formacion.md)
- Patron acceso: Cursos / `CursosAccessService`
- Regla DT: [`.cursor/rules/datatables-server-side.mdc`](../../.cursor/rules/datatables-server-side.mdc)

## Control de cambios

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.6 | 2026-10-02 | Dashboard: opciones de curso filtradas por ano+mes (`options.cursos` en metrics); si el curso elegido no aplica al mes, se limpia. |
| 1.5 | 2026-10-02 | Dashboard: rediseño KPIs por estado, filtros mes/estado/curso, chart donut de estado. |
| 1.4 | 2026-10-02 | Listado Formaciones: Estado con colores (`status-pill`) y filtro searchable-select por estado. |
| 1.3 | 2026-10-02 | Listado Formaciones: columna **Estado** (después de Calificación) derivada: `> 7.5` Aprobado, numérico ≤ 7.5 Reprobado, vacía/no numérica No realizada. |
| 1.2 | 2026-10-02 | Validación import: `extensions` en lugar de `mimes` (evita rechazo por MIME octet-stream/zip); alerta visible del error real; mensaje `uploaded` por tope PHP. |
| 1.1 | 2026-10-02 | Import: endurecer memoria/tiempo/lectura (`setReadDataOnly`, `getHighestDataRow`, `rawValue`); rechazar archivo sin filas válidas; tope `max_rows`. |
| 1.0 | 2026-10-01 | FEAT-041: tablero Formación (Dashboard + Formaciones, import replace-all, DT server-side, export, audit). Incluye observaciones review (riesgo 81k). |
