# Modulo MT-ST-04

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/mt_st_04.md`.  
> Feature: **FEAT-045** (2026-10-09). Brief: [`docs/briefs/FEAT-045.md`](../briefs/FEAT-045.md). Review: [`docs/reviews/FEAT-045.md`](../reviews/FEAT-045.md).

## Objetivo

Tablero de área **Gestión Humana** que digitaliza la matriz Excel **MT-ST-04** (control de exámenes psicofísicos para armas y psicosensométricos viales): una fila por cédula, identidad operativa en vivo desde Ficha empleados, vencimientos/estados calculados y persistidos, listado DataTables server-side, import upsert, export Excel, Dashboard KPIs/gráficos y sync diario de estados.

## Alcance actual

- Tablero sidebar **MT-ST-04** (`board` key `mt_st_04`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestañas: **Dashboard** | **Matriz** | **Validaciones**. **Sin** Catálogos / `*.parameters.edit`.
- Permisos: `view.board.gestion_humana.mt_st_04` + `mt_st_04.view` + `mt_st_04.edit`. Dashboard con `view` (edit ⇒ view). Asignación Spatie **manual** (sin migración legacy).
- Tabla `mt_st_04_registros` (1 fila / `document_number` unique); enlace lógico a Ficha por cédula (**sin FK**).
- Matriz: CRUD modal, lookup Ficha, filtros (ficha activo/desvinculado/todos + operativos), DT `serverSide: true`, export filtrado, plantilla + import upsert.
- Calculadora única `MtSt04EstadoCalculator` (+364 días; VENCERA ≤ 30 d inclusivo; ESTADO2 NO APLICA cargos exactos `GUARDA` / `OPERADOR`).
- Comando `mt_st_04:sync-estados` + schedule diario **06:25** `America/Bogota`.
- Audit central módulo `mt_st_04` / área `gestion_humana`.
- UI chrome Formación / Acreditaciones / Cursos (`module-tab`, `req-manage-*`, searchable-select, icon-btn canónicos).

### Fuera de alcance (V1)

- Columna RETIRADOS del Excel.
- Pestaña Catálogos / `mt_st_04.parameters.edit`.
- Acciones bulk sobre filas.
- Migración automática de permisos Spatie a usuarios existentes.
- Notificaciones correo/push por vencimiento.
- Soft-delete / historial versionado / multi-fila por cédula.
- Edición manual de vencimientos o estados.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/mt-st-04` · nombre `gestion-humana.mt-st-04.`  
Middleware grupo: `auth`, `active` (vía `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a default tab (Dashboard). `view` |
| GET | `/dashboard` | `dashboard` | Vista KPIs + charts. `view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON KPIs. `view` |
| GET | `/matriz` | `matriz` | Shell listado. `view` |
| GET | `/matriz/datatable` | `matriz.datatable` | DT JSON. `view` |
| GET | `/matriz/exportar` | `matriz.export` | Excel filtrado. `view` |
| GET | `/matriz/plantilla-importacion` | `matriz.import-template` | Plantilla. `edit` |
| POST | `/matriz/importar` | `matriz.import` | Upsert. `edit` |
| GET | `/matriz/lookup` | `matriz.lookup` | Lookup Ficha por cédula (JSON). `edit` |
| POST | `/matriz` | `matriz.store` | Crear. `edit` |
| PATCH | `/matriz/{registro}` | `matriz.update` | Editar. `edit` |
| DELETE | `/matriz/{registro}` | `matriz.destroy` | Borrado duro. `edit` |

Authorize en controller / Form Requests vía `MtSt04AccessService` (bypass `manage.users`).

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.mt_st_04` | Ver tablero **MT-ST-04** en sidebar GH |
| `mt_st_04.view` | Dashboard, Matriz, filtros, export Excel |
| `mt_st_04.edit` | CRUD, lookup, plantilla e import (implica `view` en AccessService) |

**Sin** `mt_st_04.parameters.edit`. **Sin** `view.board.gestion_humana.mt_st_04_dashboard`.

| Permiso otorgado | Efecto runtime |
| --- | --- |
| `mt_st_04.edit` | Puede ver aunque Spatie no tenga `view` marcado |
| `mt_st_04.view` | Dashboard + Matriz + export; **403** en CRUD/plantilla/import/lookup |

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `mt_st_04_tabs`, Admin subgroup **MT-ST-04**).  
Sync: `php artisan app:sync-permissions` + asignación manual en Admin + re-login.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\MtSt04Controller` | Shell, Dashboard metrics, Matriz DT/CRUD/lookup/import/export |
| `StoreMtSt04RegistroRequest` | Alta: cédula unique + exists Ficha; editables; `authorize` = `canEdit` |
| `UpdateMtSt04RegistroRequest` | Edición (cédula no cambia); `authorize` = `canEdit` |
| `ImportMtSt04Request` | Archivo Excel; `authorize` = `canEdit` |

Trait: `HasMtSt04Tabs` → pestañas `.module-tab` Dashboard \| Matriz.

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/mt_st_04/dashboard.blade.php` | KPIs + ApexCharts + filtro ficha |
| `areas/gestion_humana/mt_st_04/matriz.blade.php` | Filtros + DT server-side + toolbar export/import |
| `…/partials/subnav.blade.php` | Tabs Dashboard / Matriz |
| `…/partials/nuevo-modal.blade.php` | Alta + lookup Ficha |
| `…/partials/editar-modal.blade.php` | Edición |
| `…/partials/import-modal.blade.php` | Plantilla + import |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `MtSt04Registro` | `mt_st_04_registros` | Unique `document_number`; factory; `created_by` / `updated_by` → users `nullOnDelete` |
| `EmployeeFichaProfile` | `employee_ficha_profiles` | Lectura live: `full_name`, `position_name`, CIUDAD = `work_city_name` o si vacío `residence_city_name`, `cost_center_name`, `employment_status` |

### Columnas `mt_st_04_registros`

| Columna | Notas |
| --- | --- |
| `document_number` | Cédula; unique |
| `arma`, `apto` | `SI` / `NO` nullable |
| `fecha_examen_1`, `fecha_examen_2` | Editables |
| `fecha_vencimiento_1`, `fecha_vencimiento_2` | Calculadas = examen + 364 |
| `observaciones_1`, `observaciones_2` | Text nullable |
| `estado_1` | `VIGENTE` / `VENCERA` / `VENCIDO` / null |
| `estado_2` | Idem + `NO APLICA` |
| timestamps + audit users | |

Índices: unique cédula; indexes en estados, vencimientos, `apto`, `arma`. Migración multi-driver (MySQL + sqlite tests). **Sin** `migrate:fresh`.

## Servicios / comando / schedule

| Clase | Responsabilidad |
| --- | --- |
| `MtSt04AccessService` | `canViewBoard`, `canView` (view∨edit), `canEdit`, `visibleTabsFor`; bypass `manage.users` |
| `MtSt04EstadoCalculator` | Vencimientos + estados; `applyToModel`; `syncAll` (chunk 200, cargo live Ficha) |
| `MtSt04ListService` | Query filtrada compartida (DT / export / dashboard) |
| `MtSt04DatatableService` | Protocolo DataTables server-side; clase JS `js-mt-st-04-datatable`; tope length 100 |
| `MtSt04ImportService` | Upsert por cédula; última duplicada gana; acepta cédulas sin Ficha |
| `MtSt04DashboardService` | `metrics()` KPIs + series charts |
| `MtSt04AuditLogService` | Wrapper `SystemAuditService` (`module=mt_st_04`, `area=gestion_humana`) |
| `SyncMtSt04EstadosCommand` | `mt_st_04:sync-estados {--date=} {--dry-run}` |

**Schedule** (`bootstrap/app.php`):

```text
mt_st_04:sync-estados → dailyAt('06:25')->timezone('America/Bogota')->withoutOverlapping()
```

Offset vs Cursos 06:15 / Acreditaciones 06:20.

### Config

- `config/mt_st_04.php`: timezone, `vencimiento_dias=364`, `vencera_dias=30`, `no_aplica_cargos`, `si_no`, `estados`, columnas import.
- `config/audit.php`: módulo `mt_st_04` → area `gestion_humana`.

### Nav

`NavigationResolver`, `SidebarVisibilityService`, `User::defaultMtSt04BoardUrl()` (+ tabs).

## Reglas de negocio

### Identidad y Ficha

1. Una fila por cédula.
2. Alta / update / import: cédula **puede no existir** en Ficha; se guarda igual y la matriz marca **Sin Ficha** (filtro activo incluye esas filas; hay filtro «Solo sin Ficha»).
3. NOMBRE / CARGO / CIUDAD / PUESTO solo lectura live (no snapshot como verdad).
4. Lookup (edit): JSON con esos campos + `employment_status`.

### Examen 1 (psicofísico / armas)

Editables: `arma`, `fecha_examen_1`, `apto`, `observaciones_1`.  
`fecha_vencimiento_1` = examen1 + 364.  
`estado_1` = vigencia genérica (sin NO APLICA).

### Examen 2 (psicosensométrico / vial)

Editables: `fecha_examen_2`, `observaciones_2`.  
`fecha_vencimiento_2` = examen2 + 364.  
`estado_2`: primero NO APLICA si cargo exacto; si no, vigencia sobre vencimiento 2.

### Estados (TZ `America/Bogota`, date-only)

Sea `hoy` = inicio del día Bogotá.

1. Sin fecha vencimiento → `null` (UI vacía).
2. `vencimiento < hoy` → `VENCIDO`.
3. `vencimiento <= hoy + 30` (inclusivo) → `VENCERA`.
4. Else → `VIGENTE`.

**NO APLICA (ESTADO2, prioridad máxima):**  
`mb_strtoupper(trim(position_name), 'UTF-8')` exactamente `GUARDA` u `OPERADOR`. Cargos compuestos (`GUARDA SJ`) **no** excluyen.

### Recalculo

- Al guardar (store/update) e import por fila: calculadora.
- Comando diario: recorre todas las filas; persiste solo si cambió.
- Export y Dashboard leen estados **persistidos**.

### Listado

- Default: `employment_status = activo` en Ficha.
- Filtro `ficha_estado`: `activo` | `desvinculado` | `todos`.
- Filtros operativos UI: `q` (cédula/nombre; DT search también cubre cargo), `estado_1`, `estado_2`, `arma`, `apto`, `ciudad` (ciudad efectiva Ficha: trabajo o residencia). (Nit review: no hay control dedicado “cargo”; cubierto por search DT.)
- Sin bulk.

### Import / plantilla / export

Plantilla fila 1 = keys, fila 2 = labels; columnas: `document_number`, `full_name` (ignorado), `arma`, `fecha_examen_1`, `apto`, `observaciones_1`, `fecha_examen_2`, `observaciones_2`.  
**No** aceptar override de ESTADO / FECHA VENCIMIENTO.  
Upsert por cédula; filas vacías skip; duplicados en archivo → última gana + `duplicates_collapsed`.  
Export: cédula + ficha live + editables + vencimientos/estados; respeta filtros del listado. Carga colección filtrada en memoria (mismo patrón otros GH; riesgo volumen alto).

### Dashboard

- Universo default = activos ficha; filtro `ficha_estado` en UI.
- KPIs examen 1: Total, Vigente, Vencera, Vencido.
- KPIs examen 2: mismos **excluyendo** `NO APLICA` del Total2 (conteo `no_aplica` aparte); charts sin segmento NO APLICA.
- Aptos: SI / NO / sin dato.
- ApexCharts patrón Formación/GH. Sin dimensión “año de carga” en V1.

### Auditoría

Eventos: `created`, `updated`, `deleted`, `import_upsert`, `export`, `sync_estados`. Metadata con conteos/ids (sin volcar PII masivo del Excel).

### Borrado

DELETE duro con `edit` (confirmación UI). Sin soft-delete.

## JavaScript / assets

- DataTables server-side en Matriz (`js-mt-st-04-datatable`; no `.js-datatable`; `lengthMenu` sin `-1`).
- Cabecera matriz en dos filas: grupo **Psicofísico (armas)** (azul) y **Psicosensométrico (vial)** (verde); celdas tintadas por grupo. Orden columnas: identidad → psico (arma…obs1) → senso (exam2…estado2) → acciones.
- Modales Nuevo/Editar: secciones visuales Identificación | Psicofísico | Psicosensométrico (`.mt-st-04-form-section`).
- **Validaciones:** activos Ficha con `requires_psicofisicos` y sin fila en matriz; + abre alta; omit/enable muta el flag. Flag también en Ficha (editores de ficha). Permisos: mismos `mt_st_04.view` / `.edit`.
- Alpine / modales CRUD e import (patrón GH).
- Dashboard charts ApexCharts (Vite).
- Selects: solo `<x-searchable-select>`.
- Iconos: `.req-manage-filters__icon-btn` (chrome) / `.cursos-catalogo-page__icon-btn` (fila).

## Export Excel

| Clase | Uso |
| --- | --- |
| `MtSt04Export` extends `BaseExport` | Export matriz filtrada + `<x-export-excel>` |
| `MtSt04ImportTemplateExport` | Plantilla sin columnas ESTADO/VENCIMIENTO |

Prohibido `excelHtml5`. PhpSpreadsheet ya en proyecto.

## Validacion local

1. `php artisan migrate` (incremental).
2. `php artisan app:sync-permissions`.
3. Asignar board + `mt_st_04.view` / `.edit` en Admin; verificar sidebar, tabs y 403s.
4. CRUD con cédula real de Ficha; lookup; filtros activo/desvinculado.
5. Casos estado: vigente, vencera (borde día 30), vencido, sin fecha; ESTADO2 NO APLICA GUARDA/OPERADOR vs `GUARDA SJ`.
6. Plantilla → import upsert → export.
7. `php artisan mt_st_04:sync-estados` / `--dry-run` / `--date=Y-m-d`.
8. Dashboard metrics.
9. Tests Feature:

```text
php artisan test --compact ^
  tests/Feature/GestionHumana/MtSt04BoardAccessTest.php ^
  tests/Feature/GestionHumana/MtSt04MatrizTest.php ^
  tests/Feature/GestionHumana/MtSt04EstadoCalculatorTest.php ^
  tests/Feature/GestionHumana/MtSt04ImportExportTest.php ^
  tests/Feature/GestionHumana/MtSt04DashboardTest.php ^
  tests/Feature/GestionHumana/MtSt04EstadoSyncTest.php
```

10. `vendor/bin/pint --dirty --format agent` tras PHP.
11. `npm run build` si hubo CSS/JS Vite.
12. Smoke UI Chrome DevTools MCP (Dashboard + Matriz).

## Riesgos y pendientes

| Riesgo | Mitigacion / pendiente |
| --- | --- |
| Borde VENCERA inclusivo vs Excel | Formalizado ≤ 30 d; tests Bogotá; ajustar solo con OK usuario |
| CARGO parcial no es NO APLICA | Match exacto; tests; no `str_contains` |
| Drift ficha (nombre/cargo) | Lectura live listado/KPIs/job |
| Cédulas sin ficha en Excel | Rechazo por fila; prerequisito Ficha cargada |
| Duplicados en archivo | Última gana + reporte |
| Job no programado en deploy | Schedule en `bootstrap/app.php` + [`PROCEDURES.md`](../PROCEDURES.md) |
| Permisos sin assign manual | Doc Admin |
| Export en memoria | Documentado; chunk/stream si crece volumen |
| Filtro cargo dedicado | Nit review opcional post-cierre |
| Smoke DevTools | Recomendado en checklist AgentSj |

## Archivos clave

- Controller: `MtSt04Controller`
- Model/factory: `MtSt04Registro`, `MtSt04RegistroFactory`
- Access: `MtSt04AccessService`, `HasMtSt04Tabs`
- Services: calculadora, list, datatable, import, dashboard, audit
- Command: `SyncMtSt04EstadosCommand`
- Exports: `MtSt04Export`, `MtSt04ImportTemplateExport`
- Config: `config/mt_st_04.php`, `config/access.php`, `config/audit.php`
- Vistas: `resources/views/areas/gestion_humana/mt_st_04/`
- Tests: `tests/Feature/GestionHumana/MtSt04*.php`
- Schedule: `bootstrap/app.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-045.md`](../briefs/FEAT-045.md)
- Review: [`docs/reviews/FEAT-045.md`](../reviews/FEAT-045.md)
- Run log: [`docs/runs/FEAT-045-run-log.md`](../runs/FEAT-045-run-log.md)
- Doc usuario: [`docs/user/mt_st_04.md`](../user/mt_st_04.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Procedimientos (comando/schedule): [`docs/PROCEDURES.md`](../PROCEDURES.md)
