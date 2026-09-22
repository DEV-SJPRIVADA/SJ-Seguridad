# Modulo Selección

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/seleccion.md`.
> Feature: FEAT-035 (revisado 2026-09-22, aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para operar dos flujos independientes — **Ingreso** y **Examen ocupacional** — con Dashboard de KPIs/graficos, listados CRUD (filtros, DataTables server-side, export Excel) y administracion de catalogos compartidos/propios.

## Alcance actual

- Tablero sidebar **Selección** (`board` key `seleccion`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestanas: **Dashboard**, **Ingreso**, **Examen ocupacional**, **Catálogos** (esta ultima solo con `seleccion.edit`).
- Permisos: `view.board.gestion_humana.seleccion`, `seleccion.view`, `seleccion.edit`. Bypass runtime: `manage.users`. Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` via `app:sync-permissions`.
- Dos tablas independientes (`seleccion_ingresos`, `seleccion_examenes_ocupacionales`): varios registros por cedula OK; borrado **duro**; sin bridge a Ficha ni Requisiciones.
- Duplicado cedula opcion **B**: aviso + `confirm_duplicate=1` obligatorio; scope **misma tabla** (no cruzado Ingreso↔Examen).
- Todos los campos create/update **required**.
- Catalogos: whitelist 7 tipos en `payroll_catalog_items`; tipos nuevos `blood_type`, `marital_status`, `seleccion_solicitud_status` excluidos de UI Ficha (`ficha_admin_excluded_catalog_types`).
- Audit: `SeleccionAuditLogService` → `SystemAuditService` (`module=seleccion`, `area=gestion_humana`). Solo mutaciones.
- Selectores: `<x-searchable-select>` (prohibido Select2). Export: `BaseExport` + `<x-export-excel>` (prohibido `excelHtml5`).
- Fuera V1: sync Ficha/Requisiciones, import Excel, soft-delete, notificaciones, CRUD clientes/uniforms, catalogo servicio/sector o jefe OPE.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/seleccion` · nombre `gestion-humana.seleccion.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `dashboard`. `seleccion.view` |
| GET | `/dashboard` | `dashboard` | Vista KPIs/graficos. `seleccion.view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON filtros → KPIs/charts. `seleccion.view` |
| GET | `/ingresos` | `ingresos` | Shell listado. `seleccion.view` |
| GET | `/ingresos/datatable` | `ingresos.datatable` | JSON DataTables. `seleccion.view` |
| GET | `/ingresos/lookup-cedula` | `ingresos.lookup-cedula` | Matches misma tabla. `seleccion.edit` |
| GET | `/ingresos/exportar` | `ingresos.export` | Excel filtrado. `seleccion.view` |
| POST | `/ingresos` | `ingresos.store` | Crear (+ `confirm_duplicate`). `seleccion.edit` |
| PATCH | `/ingresos/{seleccionIngreso}` | `ingresos.update` | Editar. `seleccion.edit` |
| DELETE | `/ingresos/{seleccionIngreso}` | `ingresos.destroy` | Borrado duro. `seleccion.edit` |
| GET | `/examenes` | `examenes` | Shell listado. `seleccion.view` |
| GET | `/examenes/datatable` | `examenes.datatable` | JSON. `seleccion.view` |
| GET | `/examenes/lookup-cedula` | `examenes.lookup-cedula` | Matches misma tabla. `seleccion.edit` |
| GET | `/examenes/exportar` | `examenes.export` | Excel. `seleccion.view` |
| POST | `/examenes` | `examenes.store` | Crear. `seleccion.edit` |
| PATCH | `/examenes/{seleccionExamenOcupacional}` | `examenes.update` | Editar. `seleccion.edit` |
| DELETE | `/examenes/{seleccionExamenOcupacional}` | `examenes.destroy` | Borrado duro. `seleccion.edit` |
| GET | `/catalogos` | `catalogos` | Tablero catalogos (`?catalog=`). `seleccion.edit` |
| POST | `/catalogos/{type}` | `catalogos.store` | Crear item whitelist. `seleccion.edit` |
| PATCH | `/catalogos/{type}/{item}` | `catalogos.update` | Editar. `seleccion.edit` |
| DELETE | `/catalogos/{type}/{item}` | `catalogos.destroy` | Eliminar si sin refs Seleccion. `seleccion.edit` |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.seleccion` | Ver tablero **Selección** en sidebar GH |
| `seleccion.view` | Dashboard, listados, filtros, export (tambien implica view si tiene `seleccion.edit`) |
| `seleccion.edit` | CRUD Ingreso/Examen + Catálogos + lookup cedula |

**Paquetes recomendados:** consulta = board + `seleccion.view`; operativo = board + view + edit.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `seleccion_tabs`, `admin_permission_groups`). Sync: `php artisan app:sync-permissions` + re-login.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\SeleccionController` | Dashboard, ingresos, examenes, catalogos (vertical slice) |
| `StoreSeleccionIngresoRequest` / `UpdateSeleccionIngresoRequest` | Validacion Ingreso (todos required; codes payroll activos; cliente/uniform/responsable) |
| `StoreSeleccionExamenRequest` / `UpdateSeleccionExamenRequest` | Validacion Examen |
| `StoreSeleccionCatalogItemRequest` / `UpdateSeleccionCatalogItemRequest` | CRUD catalogo whitelist |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/seleccion/dashboard.blade.php` | KPIs + ApexCharts + filtros |
| `areas/gestion_humana/seleccion/ingresos.blade.php` | Shell DT server-side + CRUD modal |
| `areas/gestion_humana/seleccion/examenes.blade.php` | Shell DT server-side + CRUD modal |
| `areas/gestion_humana/seleccion/catalogos.blade.php` | Tablero tarjetas (`?catalog=`) |
| `areas/gestion_humana/seleccion/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/seleccion/partials/ingreso-form-fields.blade.php` | Campos formulario Ingreso |
| `areas/gestion_humana/seleccion/partials/examen-form-fields.blade.php` | Campos formulario Examen |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `SeleccionIngreso` | `seleccion_ingresos` | Sin unique en cedula; FK cliente, uniform, responsable (`restrictOnDelete`) |
| `SeleccionExamenOcupacional` | `seleccion_examenes_ocupacionales` | Sin unique en cedula; FK cliente, responsable |
| `PayrollCatalogItem` | `payroll_catalog_items` | Catalogos; binding por **id** (codes RH con `+`/`-` OK) |

### Catalog types (`catalog_type`)

| `catalog_type` | Label UI | Uso |
| --- | --- | --- |
| `blood_type` | RH / Grupo sanguíneo | Ingreso |
| `marital_status` | Estado civil | Examen |
| `seleccion_solicitud_status` | Estado solicitud | Examen (SOLICITUD) |
| `city`, `position`, `eps`, `afp` | (existentes) | Dual-edit Ficha + Seleccion |

KPI «examenes en proceso»: `solicitud_status_code = EN_PROCESO` (`config('seleccion.solicitud_en_proceso_code')`).

### Referencias externas (no CRUD desde Seleccion)

- Cliente → `commercial_clients`
- Tipo dotacion → `requisition_uniforms` (activos)
- Responsable → usuarios activos con `requisitions.selection_officer` via `RequisitionSelectionOfficerAccessService::selectableSelectionOfficers()`

## Servicios / jobs / mail

| Clase | Rol |
| --- | --- |
| `SeleccionAccessService` | Board / view / edit + tabs visibles |
| `HasSeleccionTabs` | Trait vistas (tab activa / subnav) |
| `SeleccionAuditLogService` | Wrapper audit (`seleccion_ingreso`, `seleccion_examen`, `seleccion_catalog`) |
| `SeleccionIngresoService` | Persistencia Ingreso + snapshot nombres catalogo |
| `SeleccionExamenService` | Persistencia Examen |
| `SeleccionIngresoDatatableService` | DT server-side Ingreso |
| `SeleccionExamenDatatableService` | DT server-side Examen |
| `SeleccionDashboardService` | Metrics KPIs/charts (filtros fechas/cliente/responsable) |
| `SeleccionCatalogService` | Whitelist + CRUD + bloqueo DELETE si refs en tablas Seleccion |

Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultSeleccionBoardUrl()` (patron Cursos).

## Reglas de negocio

1. Usuario solo `seleccion.view` (+ board): ve Dashboard/Ingreso/Examen y export; **sin** mutaciones ni pestana Catálogos.
2. Ingreso campos required: CEDULA, NOMBRE, CORREO, TELEFONO, CIUDAD, CARGO, CLIENTE, tallas, TIPO DOTACION, FECHA INGRESO, RH, REEMPLAZA A, RESPONSABLE, JEFE OPE.
3. Examen campos required: CEDULA, NOMBRE, CARGO, SERVICIO/SECTOR, CLIENTE, EPS, AFP, FECHA NACIMIENTO, CIUDAD, DIRECCION, CORREO, CELULAR, ESTADO CIVIL, FECHA ARL, SOLICITUD, RESPONSABLE.
4. Duplicado: misma tabla; sin `confirm_duplicate` → 422 con matches; con flag → persiste.
5. Dashboard filtros: Ingreso por `fecha_ingreso`; Examen por `fecha_arl`; ambos + cliente + responsable. Sin mutaciones.
6. KPIs: totales Ingreso/Examen; conteo por SOLICITUD; ingresos del mes calendario; examenes `EN_PROCESO`.
7. Catalogos: DELETE bloqueado si hay referencias en tablas Seleccion; se permite desactivar (`is_active=false`). Unique `(catalog_type, code)`.
8. **Dual-edit city/position/eps/afp:** cambios desde Seleccion o Ficha afectan la misma tabla; el bloqueo de DELETE en Seleccion **solo** mira refs en tablas Seleccion (no Ficha). Documentado como riesgo aceptado (review obs. #6).

## JavaScript / assets

- Entry Vite: `resources/js/seleccion-dashboard-charts.js` + `apex-defaults.js`.
- DataTables `serverSide: true` en ingresos/examenes (tope length; sin `-1`).
- Confirmacion UI borrado duro y duplicado cedula.

## Export Excel

| Clase | Uso |
| --- | --- |
| `App\Exports\SeleccionIngresosExport` | Export Ingreso filtrado |
| `App\Exports\SeleccionExamensExport` | Export Examen (nombre clase con typo `Examens`; follow-up opcional) |

Ambas extienden `BaseExport`. Boton `<x-export-excel>`. Auth: `seleccion.view`.

> Observacion review: export actual puede N+1 en relaciones; follow-up sugerido eager-load.

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan app:sync-permissions`.
2. Asignar manualmente board + view (+ edit) a usuario GH; verificar sidebar y pestanas.
3. CRUD Ingreso/Examen; aviso duplicado B; export Excel.
4. Dashboard: cambiar filtros → refresh AJAX metrics.
5. Catalogos: CRUD tipo nuevo y `city`; verificar exclusion en Ficha Catalogos.
6. `php artisan test --compact --filter=Seleccion` (o `tests/Feature/GestionHumana/Seleccion*`).
7. `npm run build` si falta entry charts en Vite manifest.

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Dashboard metrics en memoria (`->get()`) | Review obs. #1: a escala, preferir agregaciones SQL |
| Export N+1 | Review obs. #2: eager-load en export |
| Test `administrador` sin paquete | Review obs. #3: asercion espejo pendiente |
| Dual-edit catalogos Ficha↔Seleccion | DELETE solo bloquea refs Seleccion |
| RESPONSABLE vacio | Si no hay `selection_officer`, no se puede crear registro |
| Metadata audit con `document_number` | Aceptable; endurecer PII si politica lo exige |
| Sin bridge Ficha | Independiente por diseno V1 |

## Archivos clave

- Config: `config/seleccion.php`, `config/access.php`, `config/employee_ficha.php`, `config/audit.php`
- Migrations: `*_create_seleccion_ingresos_table`, `*_create_seleccion_examenes_ocupacionales_table`, `*_seed_seleccion_payroll_catalog_defaults`
- Factories: `SeleccionIngresoFactory`, `SeleccionExamenOcupacionalFactory`
- Tests: `tests/Feature/GestionHumana/Seleccion*.php`
- Vistas: `resources/views/areas/gestion_humana/seleccion/`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-035.md`](../briefs/FEAT-035.md)
- Review: [`docs/reviews/FEAT-035.md`](../reviews/FEAT-035.md)
- Doc usuario: [`docs/user/seleccion.md`](../user/seleccion.md)
- Access: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Ownership: [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md)

## Control de cambios (tecnico)

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-09-22 | FEAT-035: tablero Seleccion (Dashboard, Ingreso, Examen, Catalogos). |
