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
- Catalogos: whitelist 9 tipos en `payroll_catalog_items`; tipos nuevos `blood_type`, `marital_status`, `seleccion_solicitud_status`, `ingreso_estado`, `jefe_operativo` excluidos de UI Ficha (`ficha_admin_excluded_catalog_types`).
- Ingreso (v1.1): FECHA ASISTENCIA, ESTADO, JEFE OPERATIVO, OBSERVACIONES en tabla + filtros asistencia (rango) y estado; FECHA ASISTENCIA, OBSERVACIONES y REFERIDO opcionales (marcadas «(opcional)» en el form; `referido` vacío se guarda como `''`, columna NOT NULL default `''`), resto required.
- Modal Nuevo/Editar ingreso (partial `seleccion/partials/ingreso-form-fields.blade.php`): 3 secciones en tarjeta (`seleccion-ingresos-form__section`, patrón Nuevo acreditado) — **Datos del aspirante** (cédula, nombre, correo, teléfono, RH, ciudad, cargo + aviso de duplicado), **Cliente y dotación** (cliente, tipo dotación, tallas), **Proceso de selección** (fechas, estado, responsable, jefe ope, referido, reemplaza a, observaciones); grid 3 columnas (1 en móvil); pie con **Cancelar** + **Guardar/Actualizar ingreso**. Nombres de campos sin cambios.
- `x-on:submit` de Ingreso/Examen usa `guardSubmit($event, …)` **sin** `return` (Alpine no admite `return` en expresiones; `guardSubmit` ya llama `preventDefault()`).
- Audit: `SeleccionAuditLogService` → `SystemAuditService` (`module=seleccion`, `area=gestion_humana`). Solo mutaciones.
- Selectores: `<x-searchable-select>` (prohibido Select2). Export: `BaseExport` + `<x-export-excel>` (prohibido `excelHtml5`).
- "Fuera V1: sync Ficha/Requisiciones, import Excel, soft-delete, notificaciones, CRUD clientes/uniforms, catalogo servicio/sector. (v1.1 incorporo el catalogo jefe OPE; servicio/sector sigue fuera)."

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
| GET | `/examenes/historial` | `examenes.historial` | JSON auditoría Examen: global (`q`, `user_id`, `action`, `date_from`, `date_to`) o `?id=` por registro. `seleccion.edit` |
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
| `areas/gestion_humana/seleccion/ingresos.blade.php` | Shell DT server-side + CRUD modal (modales Nuevo/Editar con secciones y botones icono; filtros asistencia/estado) |
| `areas/gestion_humana/seleccion/examenes.blade.php` | Shell DT server-side + CRUD modal |
| `areas/gestion_humana/seleccion/catalogos.blade.php` | Tablero tarjetas (`?catalog=`) |
| `areas/gestion_humana/seleccion/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/seleccion/partials/ingreso-form-fields.blade.php` | Campos formulario Ingreso |
| `areas/gestion_humana/seleccion/partials/examen-form-fields.blade.php` | Campos formulario Examen |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `SeleccionIngreso` | `seleccion_ingresos` | Sin unique en cedula; FK cliente, uniform, responsable (`restrictOnDelete`). Columnas v1.1: `fecha_asistencia`, `estado_code`/`estado_name`, `jefe_ope_code` (snapshot `jefe_ope`), `observaciones` |
| `SeleccionExamenOcupacional` | `seleccion_examenes_ocupacionales` | Sin unique en cedula; FK cliente, responsable |
| `PayrollCatalogItem` | `payroll_catalog_items` | Catalogos; binding por **id** (codes RH con `+`/`-` OK) |

### Catalog types (`catalog_type`)

| `catalog_type` | Label UI | Uso |
| --- | --- | --- |
| `blood_type` | RH / Grupo sanguíneo | Ingreso |
| `ingreso_estado` | Estado ingreso | Ingreso (`estado_code`/`estado_name`); semillas: CONTRATADO, EN_PROCESO, EN_RESERVA, DESISTE_DEL_PROCESO, NO_CONTRATADO |
| `jefe_operativo` | Jefe operativo | Ingreso (`jefe_ope_code`); `jefe_ope` queda como snapshot del nombre |
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
| `SeleccionIngresoService` | Persistencia Ingreso + snapshot nombres catalogo (estado/jefe operativo) + filtros asistencia/estado |
| `SeleccionExamenService` | Persistencia Examen |
| `SeleccionIngresoDatatableService` | DT server-side Ingreso |
| `SeleccionExamenDatatableService` | DT server-side Examen |
| `SeleccionDashboardService` | Metrics KPIs/charts (filtros fechas/cliente/responsable) |
| `SeleccionCatalogService` | Whitelist + CRUD + bloqueo DELETE si refs en tablas Seleccion |

Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultSeleccionBoardUrl()` (patron Cursos).

## Reglas de negocio

1. Usuario solo `seleccion.view` (+ board): ve Dashboard/Ingreso/Examen y export; **sin** mutaciones ni pestana Catálogos.
2. Ingreso campos required: CEDULA, NOMBRE, CORREO, TELEFONO, CIUDAD, CARGO, CLIENTE, tallas, TIPO DOTACION, FECHA INGRESO, ESTADO (`ingreso_estado` activo), RH, REEMPLAZA A, RESPONSABLE, REFERIDO, JEFE OPE (`jefe_operativo` activo). Opcionales: FECHA ASISTENCIA (date) y OBSERVACIONES (<=1000 char).
3. Examen campos required: CEDULA, NOMBRE, CARGO, SERVICIO/SECTOR, CLIENTE, EPS, AFP, FECHA NACIMIENTO, CIUDAD, DIRECCION, CORREO (`email:rfc,filter` + `type=email`/pattern en UI), CELULAR, ESTADO CIVIL, FECHA ARL, ESTADO, SOLICITUD, RESPONSABLE.
   - **ESTADO** (columna `estado`, lista fija en `SeleccionExamenOcupacional::ESTADO_LABELS`, no catálogo): `solicitado` (default alta y registros previos), `agendado`, `aprobado`, `no_aprobado`, `cancelado`. Pill `status-pill--examen-{estado}` (guiones) en tabla y pills de filtro (`?estado=`) como Requisiciones.
   - Tabla/export: todas las columnas del formulario; orden … ESTADO CIVIL, FECHA ARL, **ESTADO**, **CORREO**, SOLICITUD, RESPONSABLE (+ Acciones). Fechas con `DisplayDate`.
   - **Historial** (`SeleccionExamenHistorialService`, solo `seleccion.edit`): lee `audit_logs` `module=seleccion`, `event_type=seleccion_examen`; alta guarda snapshot inicial (`logModelChange` before null), edición before/after (`SeleccionExamenService::auditSnapshot`, fechas Y-m-d); cambios campo a campo con labels, nombres de cliente/responsable y estado legible. Toolbar (global, filtro por usuario) + icono por fila.
   - Modales Nuevo/Editar: mismo partial por secciones (Datos del aspirante · Cargo y afiliaciones · Proceso del examen) con clases `seleccion-ingresos-form*` y pie icon-only.
4. Duplicado: misma tabla; sin `confirm_duplicate` → 422 con matches; con flag → persiste.
5. Dashboard filtros: Ingreso por `fecha_ingreso`; Examen por `fecha_arl`; ambos + cliente + responsable. Sin mutaciones.
6. KPIs: totales Ingreso/Examen; conteo por SOLICITUD; ingresos del mes calendario; examenes `EN_PROCESO`.
7. Catalogos: DELETE bloqueado si hay referencias en tablas Seleccion (ingreso_estado via `estado_code`, jefe_operativo via `jefe_ope_code`); se permite desactivar (`is_active=false`). Unique `(catalog_type, code)`.
8. Catalogos **codeless** (`config('seleccion.codeless_catalog_types')`: `blood_type`, `marital_status`, `seleccion_solicitud_status`, `ingreso_estado`, `jefe_operativo`): UI sin Codigo ni Orden. Alta: `SeleccionCatalogService::makeUniqueCode` (ASCII mayúsculas, espacios→`_`, conserva `+`/`-`; sufijo `_2…` si choca) + `nextSortOrder` (max+1). Edicion: conserva `code` y `sort_order` (Ingreso/Examen referencian por code). Nombre unique por tipo. Tabla sin fila vacia con colspan (DataTables `errMode=throw`).
8. **Dual-edit city/position/eps/afp:** cambios desde Seleccion o Ficha afectan la misma tabla; el bloqueo de DELETE en Seleccion **solo** mira refs en tablas Seleccion (no Ficha). Documentado como riesgo aceptado (review obs. #6).

## JavaScript / assets

- Entry Vite: `resources/js/seleccion-dashboard-charts.js` + `apex-defaults.js`.
- DataTables `serverSide: true` en ingresos/examenes (tope length; sin `-1`). 18 col + acciones (v1.1: fecha asistencia, estado badge, jefe operativo, observaciones).
- `searchable-select` transversal: dropdown teleportado a `<body>` con `position: fixed` (JS) + reposicion en scroll/resize; corrige opciones recortadas en modales/overflow. `resources/js/components/searchable-select.js`.
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
- Componente transversal: `resources/views/components/searchable-select.blade.php` + `resources/js/components/searchable-select.js`
- Migrations: `*_create_seleccion_ingresos_table`, `*_create_seleccion_examenes_ocupacionales_table`, `*_seed_seleccion_payroll_catalog_defaults`, `*_add_referido_to_seleccion_ingresos_table`, `2026_10_20_000001_add_asistencia_estado_observaciones_to_seleccion_ingresos_table`, `2026_10_20_000002_seed_seleccion_ingreso_estado_catalog`, `2026_10_20_000003_add_estado_to_seleccion_examenes_ocupacionales_table`
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
| 1.5 | 2026-10-20 | Ingreso: se quita la alerta verde de `session('status')` encima de filtros (queda solo el toast global del layout). |
| 1.4 | 2026-10-20 | Ingreso: REFERIDO pasa a opcional (`nullable` en Store/Update; sin migración). |
| 1.3 | 2026-10-20 | Ingreso: rediseño del modal Nuevo/Editar (3 secciones en tarjeta + pie Cancelar/Guardar). Fix Alpine `x-on:submit` (sin `return`) en Ingreso y Examen. |
| 1.0 | 2026-09-22 | FEAT-035: tablero Seleccion (Dashboard, Ingreso, Examen, Catalogos). |
| 1.5 | 2026-10-20 | Examen: campo ESTADO fijo (SOLICITADO/AGENDADO/APROBADO/NO APROBADO/CANCELADO, default SOLICITADO; migración `2026_10_20_000003`), tabla con todos los campos (ESTADO y CORREO tras FECHA ARL), pills de estado, correo `email:rfc,filter`, historial de auditoría por usuario/registro (`examenes.historial`, `seleccion.edit`), modales Nuevo/Editar rediseñados por secciones, filtros en 2 filas + export en toolbar. |
| 1.4 | 2026-10-20 | Ingreso filtros: `.seleccion-ingresos-filters` en 2 filas (grande/portatil): fila 1 Buscar + 4 fechas + filtrar/limpiar a la derecha; fila 2 los 5 selectores; <1024px auto-fit. Export Excel movido a la toolbar de la tabla, a la izquierda de Nuevo ingreso (mismo `exportUrl` con filtros aplicados; visible tambien para solo consulta). |
| 1.3 | 2026-10-20 | Catalogos: modificador `.seleccion-catalogs-page` (gestion max 58rem, filas densas, columnas fijas centradas, contador en header). Modal Ingreso: pie icon-only (`req-manage-filters__icon-btn` ghost/primary). Fix transversal searchable-select: `.searchable-select__dropdown` base `position: fixed` (antes `absolute` bajo el viewport mostraba un frame de scrollbar y el modal saltaba ~7px). |
| 1.2 | 2026-10-20 | Catalogos codeless (RH, estado civil, estado solicitud, estado ingreso, jefe operativo): sin Codigo/Orden en UI; code autogenerado desde nombre, orden al final, nombre unico; fix fila vacia colspan en DataTables. |
| 1.1 | 2026-10-20 | Ingresos: columnas FECHA ASISTENCIA (editable/nullable), ESTADO (catalogo `ingreso_estado`, badge), JEFE OPERATIVO (catalogo `jefe_operativo`, reemplaza texto libre `jefe_ope` por `jefe_ope_code` + snapshot) y OBSERVACIONES; filtros asistencia/estado; export ampliado; modales Nuevo/Editar rediseñados con secciones y botón Guardar/Actualizar a icono; fix transversal searchable-select (dropdown `position: fixed` teleportado, sin recortes en modales). Migraciones 2026_10_20_000001/000002. |
