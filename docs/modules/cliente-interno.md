# Modulo Cliente interno

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/cliente-interno.md`.
> Feature: FEAT-042. Review: `docs/reviews/FEAT-042.md` (Aprobado con observaciones).
> Clave interna: `cliente_interno`. Label UI / sidebar: **Cliente interno**.

## Objetivo

Tablero de area **Gestion Humana** para registrar, consultar y analizar **solicitudes de cliente interno** (persona + tipo de solicitud + fechas + estado + novedad + dias hábiles de respuesta), con Dashboard de KPIs, listado CRUD/export, import masivo replace-por-periodo (opción B) y administracion de catálogos **ESTADO** y **SOLICITUD**.

## Alcance actual

- Tablero sidebar **Cliente interno** (`board` key `cliente_interno`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestañas exactas: **Dashboard**, **Solicitudes**, **Catálogos**.
- Permisos Propuesta A: board + `solicitudes.view` / `solicitudes.edit` (`edit` ⇒ `view`) + `parameters.edit` (Catálogos). Dashboard = `solicitudes.view` **OR** `parameters.edit` (sin permiso KPI aparte). Bypass `manage.users`.
- Tablas propias de catálogo (`cliente_interno_estados`, `cliente_interno_tipos_solicitud`) — no `payroll_catalog_items`.
- Solicitudes: DataTables `serverSide: true`, filtros, alta/edición/borrado duro, export `BaseExport` + `<x-export-excel>`, import replace solo del año+mes elegido (filas fuera de periodo se aceptan). Columna **Mes** en mayúsculas derivada de `fecha_solicitud` (p. ej. `DICIEMBRE`).
- Dias de respuesta: hábiles lun–vie (sin festivos V1) + override `dias_respuesta_manual`.
- Seed ESTADO: Pendiente, En proceso, Respondida, Cerrada. Seed SOLICITUD: **vacío** (también se pueden crear al importar Excel).
- Audit: `ClienteInternoAuditLogService` → `SystemAuditService` (`module=cliente_interno`, `area=gestion_humana`).
- Selectores: `<x-searchable-select>`. Charts: ApexCharts + entry Vite `cliente-interno-dashboard-charts.js`.
- Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` vía `app:sync-permissions`.

**Fuera de alcance V1:** bridge Ficha, notificaciones correo, soft-delete, jobs async, calendario de festivos, permiso KPI de dashboard propio, migración automatica Spatie legacy, Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/cliente-interno` · nombre `gestion-humana.cliente-interno.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a primera pestaña visible. Board + (solicitudes view∨parameters) |
| GET | `/dashboard` | `dashboard` | Vista KPIs. `canViewDashboard` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON filtros → KPIs/charts. Dashboard |
| GET | `/solicitudes` | `solicitudes` | Shell listado. `canViewSolicitudes` |
| GET | `/solicitudes/datatable` | `solicitudes.datatable` | JSON DataTables. view |
| GET | `/solicitudes/exportar` | `solicitudes.export` | Excel filtrado. view |
| GET | `/solicitudes/plantilla-importacion` | `solicitudes.import-template` | Plantilla headers. edit |
| GET | `/solicitudes/periodo-conteo` | `solicitudes.period-count` | Conteo filas a borrar año+mes. edit |
| POST | `/solicitudes/importar` | `solicitudes.import` | Replace periodo (opción B). edit + `confirm_replace` |
| POST | `/solicitudes` | `solicitudes.store` | Crear. edit |
| PATCH | `/solicitudes/{clienteInternoSolicitud}` | `solicitudes.update` | Editar. edit |
| DELETE | `/solicitudes/{clienteInternoSolicitud}` | `solicitudes.destroy` | Borrado duro. edit |
| GET | `/catalogos` | `catalogos` | UI catálogos (`?catalog=`). `parameters.edit` |
| POST | `/catalogos/{type}` | `catalogos.store` | `type` ∈ `estados`\|`tipos-solicitud`. parameters.edit |
| PATCH | `/catalogos/{type}/{item}` | `catalogos.update` | parameters.edit |
| DELETE | `/catalogos/{type}/{item}` | `catalogos.destroy` | Bloqueado si hay refs. parameters.edit |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.cliente_interno` | Ver tablero **Cliente interno** en sidebar GH |
| `cliente_interno.solicitudes.view` | Listado Solicitudes, filtros, export; contribuye a Dashboard |
| `cliente_interno.solicitudes.edit` | Alta, editar, eliminar, plantilla e import masivo (implica view en AccessService) |
| `cliente_interno.parameters.edit` | Pestaña Catálogos (CRUD ESTADO + SOLICITUD); contribuye a Dashboard |

### Implicaciones (`ClienteInternoAccessService`)

| Otorgado | Efecto runtime |
| --- | --- |
| `solicitudes.edit` | Puede `solicitudes.view` aunque Spatie no tenga view |
| `solicitudes.view` | Listado + export + (con board) Dashboard |
| `parameters.edit` | Catálogos + Dashboard; **sin** listado Solicitudes si no hay view/edit |
| `manage.users` | Bypass board / view / edit / parameters |

### Pestañas visibles (`visibleTabsFor`)

| Pestaña | Condición |
| --- | --- |
| Dashboard | `canViewDashboard` = solicitudes view∨edit **OR** parameters.edit **OR** bypass |
| Solicitudes | `canViewSolicitudes` |
| Catálogos | `canEditParameters` |

### Paquetes recomendados (asignación manual Admin)

| Perfil | Permisos |
| --- | --- |
| Solo consulta | board + `solicitudes.view` |
| Operativo GH | board + `solicitudes.view` + `solicitudes.edit` |
| Solo catálogos | board + `parameters.edit` |
| Completo | board + view + edit + parameters.edit |

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas.cliente_interno`, `cliente_interno_tabs`, Admin subgroup **Cliente interno** bajo Gestion humana). Sync: `php artisan app:sync-permissions` + re-login. **Sin** migración automatica de permisos legacy.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\ClienteInternoController` | Shell, dashboard, solicitudes (DT/CRUD/export/import), catálogos |
| `StoreClienteInternoSolicitudRequest` / `UpdateClienteInternoSolicitudRequest` | Validación solicitud; authorize edit |
| `ImportClienteInternoSolicitudesRequest` | Archivo + año/mes + `confirm_replace` accepted; authorize edit |
| `StoreClienteInternoCatalogItemRequest` / `UpdateClienteInternoCatalogItemRequest` | CRUD catálogo whitelist; authorize parameters.edit |

Trait UI: `App\Traits\HasClienteInternoTabs` (subnav / pestañas visibles).

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/cliente_interno/dashboard.blade.php` | KPIs + ApexCharts + filtros año/mes |
| `areas/gestion_humana/cliente_interno/solicitudes.blade.php` | Shell DT server-side + CRUD + export + import |
| `areas/gestion_humana/cliente_interno/catalogos.blade.php` | Tablero de tarjetas ESTADO / TIPOS (`?catalog=` abre gestión; sin query muestra selector) |
| `.../partials/subnav.blade.php` | Pestañas `.module-tab` |
| `.../partials/solicitud-form-fields.blade.php` | Campos formulario alta/edición |
| `.../partials/import-modal.blade.php` | Confirmación replace periodo + conteo |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `ClienteInternoEstado` | `cliente_interno_estados` | code unique, name, is_active, sort_order. Seed 4 estados |
| `ClienteInternoTipoSolicitud` | `cliente_interno_tipos_solicitud` | Igual estructura. Seed vacío |
| `ClienteInternoSolicitud` | `cliente_interno_solicitudes` | FK tipo (`restrictOnDelete`), estado nullable (`nullOnDelete`); `anio`/`mes` derivados de `fecha_solicitud` |

### Columnas `cliente_interno_solicitudes`

| Columna | Notas |
| --- | --- |
| `fecha_solicitud` | Obligatoria; índice; fuente de anio/mes |
| `anio` / `mes` | Derivados siempre al persistir (no inputs libres) |
| `nombre_apellidos`, `cedula` | Obligatorios; varias filas misma cédula OK |
| `correo_electronico` | Opcional; en CRUD valida email si presente |
| `tipo_solicitud_id` | Obligatorio; FK activa en alta manual |
| `fecha_respuesta`, `estado_id`, `novedad`, `dias_respuesta` | Opcionales |
| `dias_respuesta_manual` | Boolean; conserva override |
| `created_by` / `updated_by` | FK users nullable |

Índices: `(anio, mes)`, `cedula`, `estado_id`, `tipo_solicitud_id`, `fecha_solicitud`.

Migraciones (aditivas, multi-driver):

- `2026_10_05_101108_create_cliente_interno_estados_table.php`
- `2026_10_05_101108_create_cliente_interno_tipos_solicitud_table.php`
- `2026_10_05_101109_create_cliente_interno_solicitudes_table.php`

Factories: `ClienteInternoEstadoFactory`, `ClienteInternoTipoSolicitudFactory`, `ClienteInternoSolicitudFactory`.

## Servicios / jobs / mail

| Clase | Responsabilidad |
| --- | --- |
| `ClienteInternoAccessService` | Board, view/edit solicitudes, parameters, dashboard, `visibleTabsFor` |
| `ClienteInternoDatatableService` | Query filtrada + payload DataTables server-side (tope length 100) |
| `ClienteInternoBusinessDaysService` | Conteo hábiles + `resolveForCreate` / `resolveForUpdate` |
| `ClienteInternoImportService` | Replace-por-periodo opción B; plantilla parse; audit metadata |
| `ClienteInternoDashboardService` | KPIs + charts payload; default año; bins distribución |
| `ClienteInternoCatalogService` | CRUD catálogos; bloqueo DELETE con refs; desactivar OK |
| `ClienteInternoAuditLogService` | Wrapper audit (`cliente_interno` / `gestion_humana`) |

Sin jobs ni mail en V1.

### Config

- `config/cliente_interno.php`: whitelist `catalog_types`, headers import, memory/time/chunk/max_rows, bins dashboard.
- `config/audit.php`: módulo `cliente_interno` → area `gestion_humana`.

## Reglas de negocio

### Solicitud

1. Obligatorios: `fecha_solicitud`, `nombre_apellidos`, `cedula`, `tipo_solicitud_id` (activo en CRUD).
2. Opcionales vacíos OK: correo, fecha_respuesta, estado, novedad, dias_respuesta.
3. `anio` / `mes` siempre derivados de `fecha_solicitud`.
4. Varios registros por misma cédula permitidos (sin aviso obligatorio).
5. Eliminar = DELETE físico tras confirmación UI.
6. Tipo solicitud activo al crear/editar manual; estado si presente, activo.

### Días hábiles + override

7. Auto = días lun–vie **estrictamente posteriores** a `fecha_solicitud` hasta e incluida `fecha_respuesta`. Mismo día = 0; sin fecha_respuesta = null; respuesta &lt; solicitud = null. **Sin festivos** en V1.
8. CREATE: si el request trae `dias_respuesta` explícito y distinto del calculado (o sin fecha_respuesta) → valor + `dias_respuesta_manual=true`; si no → auto / `manual=false`.
9. UPDATE: si `dias_respuesta_manual` y el usuario no tocó el input de días → conservar; si tocó / limpia override / recalcular → según `resolveForUpdate`.
10. Import: columna días vacía → auto (`manual=false`); número presente → persistir + `manual=true`.

### Catálogos

11. ESTADO seed: `PENDIENTE`/`Pendiente`, `EN_PROCESO`/`En proceso`, `RESPONDIDA`/`Respondida`, `CERRADA`/`Cerrada` (sort 1–4).
12. SOLICITUD seed vacío; el import masivo **crea** tipos/estados del Excel si no existen (código slug del nombre).
13. CRUD: code + name + is_active + sort_order; unique `code`.
14. DELETE bloqueado si hay referencias en solicitudes; desactivar (`is_active=false`) permitido.

### Masivo (opción B)

15. Usuario elige año + mes + archivo; UI confirma con conteo de filas del periodo a borrar; `confirm_replace` requerido.
16. Validar headers y **todas** las filas antes de cualquier DELETE. Errores → rechazo completo, dataset intacto.
17. Match Solicitud/Estado por name o code (case-insensitive). Solicitud inexistente → error de fila (rechazo completo). Estado vacío OK.
18. Transacción: `DELETE WHERE anio=? AND mes=?` → INSERT **todas** las filas válidas (también fuera de periodo → spillover).
19. Audit `import_replace_period`: `{anio, mes, deleted_in_period, imported, accepted_outside_period, skipped_empty, tipos_created, estados_created}`.
20. Nunca TRUNCATE / `migrate:fresh` / wipe.

### Dashboard

21. Filtros: año (default: año actual con datos → MAX(anio) con datos → año calendario); mes opcional.
22. KPIs: `total`; `por_estado` (incluye **Sin estado**); promedio + distribución días (bins `config/cliente_interno.dashboard.dias_bins`); `tendencia_mensual` del **año completo** (el filtro mes no la reduce).
23. Solo filas con `dias_respuesta` no null entran a promedio/distribución.
24. Sin mutaciones en Dashboard.

### Auditoría

25. Eventos: create/update/delete solicitud; create/update/delete catálogo; `import_replace_period`; `export`. Metadata con conteos; sin volcar PII masiva.

## JavaScript / assets

- Entry Vite: `resources/js/cliente-interno-dashboard-charts.js` (+ `apex-defaults.js`).
- DataTables server-side en pestaña Solicitudes (Alpine/jQuery según patrón GH).
- Modal import: pide conteo periodo + checkbox/confirmación antes de POST.
- Chrome: `.module-tab`; iconos `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn`.

## Export Excel

| Clase | Uso |
| --- | --- |
| `App\Exports\ClienteInternoExport` | Export filtrado (`BaseExport`); headers alineados a UI |
| `App\Exports\ClienteInternoImportTemplateExport` | Plantilla headers-only para import |

Botón UI: `<x-export-excel>`. Prohibido `excelHtml5`.

### Mapeo Excel (plantilla / export / import)

| Header Excel | Campo |
| --- | --- |
| Fecha de solicitud | `fecha_solicitud` (+ anio, mes) |
| Nombre y apellidos | `nombre_apellidos` |
| Cédula | `cedula` |
| Correo electrónico | `correo_electronico` |
| Solicitud | name/code → `tipo_solicitud_id` (crea tipo si no existe) |
| Fecha de respuesta | `fecha_respuesta` |
| Estado | name/code → `estado_id` (opcional; crea estado si no existe) |
| Novedad | `novedad` |
| Días de respuesta | `dias_respuesta` (+ manual si presente en import) |

Fechas texto `d/m/Y` (Colombia). Años mal tipados tipo `22026` se normalizan a `2026`.

## Validacion local

1. `php artisan migrate` (incremental; no fresh).
2. `php artisan app:sync-permissions` + asignar board/view/edit/parameters a usuario de prueba + re-login.
3. Catálogos: verificar 4 estados; crear ≥1 tipo SOLICITUD; probar desactivar / bloqueo delete con refs.
4. Alta/editar/eliminar solicitud; override días; export.
5. Plantilla → filas in/out periodo → import con confirmación → verificar replace B + spillover.
6. Dashboard metrics con/sin mes.
7. `php artisan test --compact tests/Feature/GestionHumana/ClienteInternoBoardAccessTest.php` (y resto `ClienteInterno*.php`).
8. `npm run build` si se tocó entry charts.
9. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y pendientes

| Riesgo / observación review | Nota operativa |
| --- | --- |
| Seed SOLICITUD vacío | Alta manual requiere tipo; el **import** crea tipos (y estados) faltantes desde la columna Solicitud/Estado. |
| Import no valida formato email (obs. review #1) | CRUD sí valida email; en masivo un correo mal formado puede persistir. Hotfix recomendado. |
| Import hace match de catálogos **incl. inactivos** (obs. #2) | Alta manual exige activos; import puede resolver code/name de ítems desactivados. |
| Export materializa colección completa (obs. #3) | Filtro muy amplio puede tensionar memoria/timeout en Hostinger; preferir filtros año/mes acotados. |
| Controller monolítico ~580 líneas (obs. #4) | Split opcional post-V1; no bloquea. |
| Festivos no restados | V1 solo lun–vie. |
| Replace periodo destructivo | Mitigado con confirmación + conteo + validación previa + audit. |
| Spillover opción B | Mensaje post-import: importados vs fuera de periodo. |

## Referencias

- Feature Brief: `docs/briefs/FEAT-042.md`
- Plan / Task Cards: `docs/briefs/FEAT-042-plan.md`, `FEAT-042-T1`…`T5`
- Review: `docs/reviews/FEAT-042.md`
- Doc usuario: `docs/user/cliente-interno.md`
- Access: `docs/ACCESS_CONTROL.md`
- Patrones: Formación (import + anio/mes), Selección (CRUD + Catálogos + Dashboard), Comercial (view/edit + parameters.edit)
