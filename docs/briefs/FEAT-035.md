# Feature Brief — FEAT-035

> Brief final del Arquitecto (2026-09-22). Consolida `docs/briefs/FEAT-035-analyst.md` + run log + respuestas usuario 1–5. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-035 |
| Modulo / area | Gestion humana — tablero **Selección** (`seleccion`) |
| Titulo | Tablero Selección GH (Dashboard, Ingreso, Examen ocupacional, Catálogos) |
| Solicitante | Usuario / AgentSj (chat 2026-09-22) |
| Fecha | 2026-09-22 |

## Objetivo

Dar a Gestión Humana un **tablero operativo Selección** para gestionar dos flujos independientes — **Ingreso** y **Examen ocupacional** — con Dashboard de KPIs/gráficos, listados CRUD (filtros, DataTables server-side, export Excel) y administración de catálogos compartidos/propios.

Hoy no existe board, permisos, tablas ni docs de Selección. El módulo sigue el patrón de área única GH (Cursos / Ficha / Desvinculaciones): vertical slice, permisos board+view+edit, audit via `SystemAuditService`.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | UI | Tablero GH `seleccion` label **Selección**; pestañas **Dashboard**, **Ingreso**, **Examen ocupacional**, **Catálogos** (esta última solo con `seleccion.edit`). |
| 2 | Permisos | `view.board.gestion_humana.seleccion`, `seleccion.view`, `seleccion.edit`. Sin asignación automática al rol `usuario`. Bypass `manage.users` / `super-admin`. |
| 3 | Listados | CRUD + filtros + DataTables **server-side** + export Excel (`BaseExport` + `<x-export-excel>`). |
| 4 | Dashboard | KPIs: totales Ingreso/Examen; conteo por estado SOLICITUD; ingresos del mes; exámenes en proceso. Gráficos: estado SOLICITUD / tendencia mensual / distribución. Filtros: rango fechas, cliente, responsable. |
| 5 | Datos | 2 tablas nuevas independientes de Ficha/requisiciones; varios registros por cédula OK; borrado **duro**. |
| 6 | Duplicado cédula | Opción **B**: aviso + confirmación obligatoria antes de guardar. Solo **misma tabla** (Ingreso↔Ingreso; Examen↔Examen); no cruzado. |
| 7 | Campos | **Todos obligatorios** en create/update (Ingreso y Examen). |
| 8 | Selects | city/position/eps/afp → `payroll_catalog_items`; cliente → `commercial_clients`; tipodotación → `requisition_uniforms`; responsable → reclutadores activos (`requisitions.selection_officer`). |
| 9 | Catálogos nuevos | `blood_type`, `marital_status`, `seleccion_solicitud_status` en `payroll_catalog_items`; editables desde Selección Catálogos junto con city/position/eps/afp. |
| 10 | Texto libre | Tallas; servicio/sector; jefe OPE; reemplaza a; correos/teléfonos/dirección. |
| 11 | Audit | Wrapper `SeleccionAuditLogService` → `SystemAuditService` (`module=seleccion`, `area=gestion_humana`). |
| 12 | Fuera V1 | Bridge/sync Ficha o Requisiciones; import masivo Excel; soft-delete; notificaciones correo; catálogo de servicio/sector o jefe OPE. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Board key `seleccion`, label **Selección**; hogar `gestion_humana`, `base_area_tab => false` | Patrón Cursos / Desvinculaciones. |
| Tablas | `seleccion_ingresos` + `seleccion_examenes_ocupacionales` | snake_case; prefijo `seleccion_` acota dominio; independientes entre sí y de Ficha. |
| Catalog codes (payroll) | Tipos técnicos: `blood_type`, `marital_status`, `seleccion_solicitud_status`. Filas referenciadas por **`code`** (string) + snapshot `*_name` opcional en registro | Alineado a ficha (`position_code`/`eps_code`); evita drift si se renombra el ítem vía sync nombre. |
| Cliente / uniform / responsable | FK `commercial_client_id`, `requisition_uniform_id`, `responsable_user_id` | Entidades con PK propia; `restrictOnDelete` o `nullOnDelete` según tabla (ver BD). |
| Duplicado UX | Endpoint lookup + flag `confirm_duplicate=1` en store/update; sin flag → 422 con lista de matches | Cumple B sin inventar soft-lock. Scope: misma tabla únicamente. |
| «En proceso» (KPI) | Estado SOLICITUD con code `EN_PROCESO` | Label seed «EN PROCESO». |
| Ingresos del mes | `fecha_ingreso` en mes calendario actual (timezone app), respetando filtros dashboard | Claridad operativa. |
| Rango fechas dashboard | Ingreso: filtra por `fecha_ingreso`. Examen: filtra por `fecha_arl`. Ambos aplican cliente + responsable | Un solo panel con dos series. |
| Catalogos UI Selección | Tablero de tarjetas (patrón Ficha/Cursos); whitelist 7 tipos; CRUD reutiliza Form Requests/servicio payroll o wrapper delgado | Pedido reusar patrón; no editar `commercial_clients` ni `requisition_uniforms` desde aquí. |
| Ficha Catalogos | Los 3 tipos nuevos **no** aparecen en UI Ficha V1 (`ficha_admin_excluded_catalog_types`); sí viven en `catalog_type_labels` para validación central | Evita ruido en Ficha; city/position/eps/afp siguen dual-editables. |
| Seed | Migración aditiva + seeder/upsert no destructivo de RH, estado civil y SOLICITUD | Sin `migrate:fresh`. |
| Access | `SeleccionAccessService` (board/view/edit + bypass `manage.users`) + `HasSeleccionTabs` | Espejo `CursosAccessService`. |
| Charts | ApexCharts + entry Vite `seleccion-dashboard-charts.js` + `apex-defaults.js` | Estándar ARCHITECTURE.md. |
| Selectores | `<x-searchable-select>` en formularios, filtros y dashboard. **Prohibido Select2.** | AGENTS.md. |
| Listados | DataTables `serverSide: true` + services dedicados; **no** `@foreach` masivo | Regla datatables-server-side. |
| Roles | Solo `super-admin` recibe permisos vía sync; `administrador` / `usuario` **sin** paquete por defecto | Respuesta P5 + patrón Cursos. |
| Repository | **No.** | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe. | Proteccion de datos. |

### Nombres tecnicos de catalog_type (documentar en docs modulo)

| `catalog_type` | Label UI | Uso |
| --- | --- | --- |
| `blood_type` | RH / Grupo sanguíneo | Ingreso |
| `marital_status` | Estado civil | Examen |
| `seleccion_solicitud_status` | Estado solicitud | Examen (campo SOLICITUD) |

Tipos ya existentes reutilizados: `city`, `position`, `eps`, `afp`.

### Seed — `blood_type`

| code | name | sort_order |
| --- | --- | --- |
| `O+` | O+ | 1 |
| `O-` | O- | 2 |
| `A+` | A+ | 3 |
| `A-` | A- | 4 |
| `B+` | B+ | 5 |
| `B-` | B- | 6 |
| `AB+` | AB+ | 7 |
| `AB-` | AB- | 8 |

> Rutas de catálogo usan model binding por **id** (`PayrollCatalogItem`), no por code; `+`/`-` en code es seguro.

### Seed — `marital_status`

| code | name | sort_order |
| --- | --- | --- |
| `SOLTERO` | Soltero/a | 1 |
| `CASADO` | Casado/a | 2 |
| `UNION_LIBRE` | Unión libre | 3 |
| `DIVORCIADO` | Divorciado/a | 4 |
| `VIUDO` | Viudo/a | 5 |

### Seed — `seleccion_solicitud_status` (un solo DXEMO)

| code | name (label UI) | sort_order |
| --- | --- | --- |
| `CONTRATADO` | CONTRATADO | 1 |
| `DXENT_OPERACIONES` | DXENT OPERACIONES | 2 |
| `DXENT_SELECCION` | DXENT SELECCIÓN | 3 |
| `DESISTE_DEL_PROCESO` | DESISTE DEL PROCESO | 4 |
| `DXPSICOFISICO` | DXPSICOFÍSICO | 5 |
| `DXANT` | DxANT | 6 |
| `DXEMO_Y_PSICOFISICO` | DxEMO Y PSICOFÍSICO | 7 |
| `EN_PROCESO` | EN PROCESO | 8 |
| `DXPOLIGRAFIA` | DXPOLIGRAFÍA | 9 |
| `DXEMO` | DXEMO | 10 |
| `EN_RESERVA` | EN RESERVA | 11 |

## Alcance

### Incluye

- Tablero sidebar **Selección** en Gestión humana.
- Permisos + Admin UI (`admin_permission_groups`) + sync `PermissionCatalog` / `app:sync-permissions`.
- Pestaña **Dashboard**: KPIs + gráficos ApexCharts + filtros AJAX (fechas / cliente / responsable).
- Pestaña **Ingreso**: shell + DataTables server-side + CRUD modal/form + filtros + export Excel + aviso duplicado cédula.
- Pestaña **Examen ocupacional**: mismo patrón sobre tabla propia.
- Pestaña **Catálogos** (`seleccion.edit`): CRUD de `city`, `position`, `eps`, `afp`, `blood_type`, `marital_status`, `seleccion_solicitud_status`.
- Seed no destructivo de los 3 catálogos nuevos.
- Auditoría create/update/delete (registros + ítems de catálogo gestionados desde Selección).
- Tests PHPUnit (permisos, CRUD, duplicado B, dashboard metrics, export auth, catálogos whitelist).
- Docs: `docs/modules/seleccion.md` + `docs/user/seleccion.md` (+ INDEX / ACCESS_CONTROL / ARCHITECTURE ownership). Documentador al cierre.

### Fuera de alcance

- Bridge / sync con Ficha empleados o Requisiciones.
- Import masivo Excel (solo export).
- Soft-delete / historial versionado.
- Notificaciones por correo.
- CRUD de `commercial_clients` o `requisition_uniforms` desde Selección.
- Catálogo administrable de Servicio/sector o Jefe OPE.
- Aviso cruzado de cédula entre Ingreso y Examen.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Tablero y acceso

1. Etiquetas: tablero **Selección**; pestañas **Dashboard**, **Ingreso**, **Examen ocupacional**, **Catálogos**.
2. Sidebar: `view.board.gestion_humana.seleccion` (hogar `gestion_humana`). Bypass: `manage.users`.
3. Consultar Dashboard / listados / export: `seleccion.view` (o `seleccion.edit` o bypass).
4. Crear / editar / eliminar registros y CRUD Catálogos: `seleccion.edit` (o bypass).
5. Usuario solo con `seleccion.view` (+ board): ve Dashboard/Ingreso/Examen y export; **sin** mutaciones ni pestaña Catálogos.
6. Catálogos visible en subnav solo si `seleccion.edit`.

### Ingreso (`seleccion_ingresos`)

7. Campos UI (todos **required**): CEDULA, APELLIDOS Y NOMBRE, CORREO, TELEFONO, CIUDAD, CARGO, CLIENTE, TALLA CAMISA, TALLA PANTALÓN, TALLA ZAPATOS, TIPO DOTACION, FECHA DE INGRESO, RH, REEMPLAZA A, RESPONSABLE, JEFE OPE ASIGNADO.
8. Varios registros por misma cédula permitidos.
9. Al create/update, si existe ≥1 fila con la misma cédula (normalizada trim) **en esta tabla** (excluyendo el propio id en update): mostrar aviso con resumen (ids/fechas) y exigir confirmación (`confirm_duplicate`); sin confirmación → no persistir.
10. Eliminar: confirmación UI; **DELETE** físico.
11. CIUDAD / CARGO / RH: codes activos en `payroll_catalog_items` del tipo correspondiente.
12. CLIENTE: `commercial_clients.id` existente.
13. TIPO DOTACION: `requisition_uniforms.id` activo.
14. RESPONSABLE: `users.id` activo con permiso `requisitions.selection_officer` (vía `RequisitionSelectionOfficerAccessService::selectableSelectionOfficers`).
15. Tallas, REEMPLAZA A, JEFE OPE, CORREO, TELEFONO, NOMBRE: texto libre (validar email/teléfono razonables).

### Examen ocupacional (`seleccion_examenes_ocupacionales`)

16. Campos UI (todos **required**): CEDULA, APELLIDOS Y NOMBRES, CARGO, SERVICIO/SECTOR, CLIENTE, EPS, PENSION (AFP), FECHA NACIMIENTO, CIUDAD, DIRECCION, CORREO, CELULAR, ESTADO CIVIL, FECHA DE ARL, SOLICITUD, RESPONSABLE.
17. Misma regla de duplicado cédula **solo dentro de esta tabla** (B).
18. FECHA DE ARL = `date`. SOLICITUD = code de `seleccion_solicitud_status`.
19. EPS/AFP/CIUDAD/CARGO/ESTADO CIVIL: payroll codes activos. CLIENTE y RESPONSABLE igual que Ingreso.
20. SERVICIO/SECTOR, DIRECCION, CORREO, CELULAR, NOMBRE: texto libre.

### Dashboard

21. KPIs V1:
    - Total registros Ingreso (con filtros).
    - Total registros Examen (con filtros).
    - Conteo por estado SOLICITUD (examen; barras/tarjeta).
    - Ingresos del mes (`fecha_ingreso` en mes actual ∩ filtros).
    - Exámenes en proceso (`solicitud_status_code = EN_PROCESO` ∩ filtros).
22. Gráficos V1 (ApexCharts):
    - Distribución / barras por estado SOLICITUD.
    - Tendencia mensual de ingresos (`fecha_ingreso` agrupada por mes; rango filtros).
    - Distribución (cliente y/o responsable — al menos una serie; preferir cliente + responsable como dos charts o tabs si el espacio lo permite).
23. Filtros: `date_from` / `date_to`, `commercial_client_id`, `responsable_user_id`. Refresh AJAX a `/dashboard/metrics` (sin botón obligatorio; patrón Cursos).
24. Sin mutaciones en Dashboard.

### Catálogos

25. Whitelist administrable desde Selección: `city`, `position`, `eps`, `afp`, `blood_type`, `marital_status`, `seleccion_solicitud_status`.
26. CRUD: code + name + is_active + sort_order (patrón Ficha). Unique `(catalog_type, code)`.
27. No eliminar ítem referenciado por registros de Selección (bloquear con mensaje) **o**, si se prefiere V1 más simple: soft-desactivar (`is_active=false`) y bloquear solo delete duro cuando hay hijos — **preferencia Arquitecto: bloquear DELETE si hay referencias; permitir desactivar**.
28. No administrar clientes comerciales ni uniforms aquí.

### Auditoria

29. Eventos: `seleccion_ingreso` create/update/delete; `seleccion_examen` create/update/delete; `seleccion_catalog` create/update/delete (metadata: type, code; sin PII innecesaria).
30. No auditar GET/list/datatable/export.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.seleccion` | `super-admin` (todos); resto asignación Manual en Admin | Ver tablero **Selección** en sidebar GH |
| `seleccion.view` | Paquete consulta / completo (manual) | Dashboard, listados, filtros, export |
| `seleccion.edit` | Paquete completo (manual) | CRUD Ingreso/Examen + Catálogos |

**Paquetes recomendados:**

| Perfil | Permisos |
| --- | --- |
| Solo consulta | board + `seleccion.view` |
| Operativo GH | board + `seleccion.view` + `seleccion.edit` |

No crear roles nuevos. Bypass runtime: `manage.users` en `SeleccionAccessService` (super-admin ya tiene todos vía Spatie).

### Cambios en `config/access.php`

- `system_permissions`: `seleccion.view`, `seleccion.edit`.
- `boards`: `'seleccion' => 'Selección'`.
- `board_canonical_areas`: `seleccion` → `home => gestion_humana`, `base_area_tab => false`.
- `seleccion_tabs`: `dashboard => Dashboard`, `ingresos => Ingreso`, `examenes => Examen ocupacional`, `catalogos => Catálogos`.
- `admin_permission_groups` → `other_areas.gestion_humana`:
  - boards: agregar `view.board.gestion_humana.seleccion`
  - subgroup `seleccion`: `seleccion.view`, `seleccion.edit`
- Generación `view.board.*` vía `PermissionCatalog`.

### Seeders / sync

- `app:sync-permissions`: crea permisos; `super-admin` recibe todos.
- Rol `administrador` y `usuario`: **no** incluir paquete Selección por defecto.
- Tras deploy: `php artisan app:sync-permissions` + re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo nuevo). **No** editar `routes/web.php` salvo el require ya existente del área.

Prefijo: `/gestion-humana/seleccion` · nombre `gestion-humana.seleccion.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `dashboard`. `seleccion.view` |
| GET | `/dashboard` | `dashboard` | Vista KPIs/gráficos. `seleccion.view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON filtros → KPIs/charts. `seleccion.view` |
| GET | `/ingresos` | `ingresos` | Shell listado Ingreso. `seleccion.view` |
| GET | `/ingresos/datatable` | `ingresos.datatable` | JSON DataTables. `seleccion.view` |
| GET | `/ingresos/lookup-cedula` | `ingresos.lookup-cedula` | Matches misma tabla. `seleccion.edit` |
| GET | `/ingresos/exportar` | `ingresos.export` | Excel filtrado. `seleccion.view` |
| POST | `/ingresos` | `ingresos.store` | Crear (+ `confirm_duplicate`). `seleccion.edit` |
| PATCH | `/ingresos/{seleccionIngreso}` | `ingresos.update` | Editar. `seleccion.edit` |
| DELETE | `/ingresos/{seleccionIngreso}` | `ingresos.destroy` | Borrado duro. `seleccion.edit` |
| GET | `/examenes` | `examenes` | Shell listado Examen. `seleccion.view` |
| GET | `/examenes/datatable` | `examenes.datatable` | JSON. `seleccion.view` |
| GET | `/examenes/lookup-cedula` | `examenes.lookup-cedula` | Matches misma tabla. `seleccion.edit` |
| GET | `/examenes/exportar` | `examenes.export` | Excel. `seleccion.view` |
| POST | `/examenes` | `examenes.store` | Crear. `seleccion.edit` |
| PATCH | `/examenes/{seleccionExamen}` | `examenes.update` | Editar. `seleccion.edit` |
| DELETE | `/examenes/{seleccionExamen}` | `examenes.destroy` | Borrado duro. `seleccion.edit` |
| GET | `/catalogos` | `catalogos` | Tablero catálogos (`?catalog=`). `seleccion.edit` |
| POST | `/catalogos/{type}` | `catalogos.store` | Crear ítem whitelist. `seleccion.edit` |
| PATCH | `/catalogos/{type}/{item}` | `catalogos.update` | Editar. `seleccion.edit` |
| DELETE | `/catalogos/{type}/{item}` | `catalogos.destroy` | Eliminar si sin refs. `seleccion.edit` |

Middleware grupo: `auth`, `active`, `password.changed` (igual resto GH).

## Base de datos

Solo migración(es) **nuevas** + seed upsert. Prohibido `migrate:fresh`.

### Extensión config (sin tabla nueva)

- `config/employee_ficha.php`: agregar `blood_type`, `marital_status`, `seleccion_solicitud_status` a `catalog_types` + `catalog_type_labels`; agregar `ficha_admin_excluded_catalog_types` (los 3 nuevos) y filtrar `EmployeeFichaCatalogService::catalogsForAdmin()` / `typeLabels` de admin UI.
- `config/seleccion.php` (nuevo): whitelist `managed_catalog_types`, labels tabs, límites validación, “en proceso” code.
- `config/audit.php`: módulo `seleccion` → area `gestion_humana`.
- Filas seed en `payroll_catalog_items` (upsert por `catalog_type`+`code`).

### Tabla `seleccion_ingresos`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | APELLIDOS Y NOMBRE |
| `email` | string(150) | CORREO |
| `phone` | string(40) | TELEFONO |
| `city_code` | string(50) | FK lógica → payroll `city` |
| `city_name` | string(150) | snapshot al guardar |
| `position_code` | string(50) | payroll `position` |
| `position_name` | string(150) | snapshot |
| `commercial_client_id` | FK → `commercial_clients.id` | `restrictOnDelete` |
| `shirt_size` | string(40) | TALLA CAMISA |
| `pants_size` | string(40) | TALLA PANTALÓN |
| `shoes_size` | string(40) | TALLA ZAPATOS |
| `requisition_uniform_id` | FK → `requisition_uniforms.id` | TIPO DOTACION; `restrictOnDelete` |
| `fecha_ingreso` | date | FECHA DE INGRESO; index |
| `blood_type_code` | string(20) | payroll `blood_type` |
| `blood_type_name` | string(40) | snapshot |
| `reemplaza_a` | string(255) | REEMPLAZA A |
| `responsable_user_id` | FK → `users.id` | RESPONSABLE; `restrictOnDelete` |
| `jefe_ope` | string(255) | JEFE OPE ASIGNADO |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Indices: `(document_number)`, `(fecha_ingreso)`, `(commercial_client_id)`, `(responsable_user_id)`. **Sin** unique en cédula.

### Tabla `seleccion_examenes_ocupacionales`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | APELLIDOS Y NOMBRES |
| `position_code` / `position_name` | string | CARGO |
| `servicio_sector` | string(255) | SERVICIO/SECTOR (texto libre) |
| `commercial_client_id` | FK → `commercial_clients.id` | `restrictOnDelete` |
| `eps_code` / `eps_name` | string | EPS |
| `afp_code` / `afp_name` | string | PENSION (AFP) |
| `birth_date` | date | FECHA NACIMIENTO |
| `city_code` / `city_name` | string | CIUDAD |
| `address` | string(255) | DIRECCION |
| `email` | string(150) | CORREO |
| `phone` | string(40) | CELULAR |
| `marital_status_code` / `marital_status_name` | string | ESTADO CIVIL |
| `fecha_arl` | date | FECHA DE ARL; index |
| `solicitud_status_code` / `solicitud_status_name` | string | SOLICITUD; index code |
| `responsable_user_id` | FK → `users.id` | `restrictOnDelete` |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Indices: `(document_number)`, `(fecha_arl)`, `(solicitud_status_code)`, `(commercial_client_id)`, `(responsable_user_id)`. **Sin** unique en cédula.

### Validacion de codes payroll

En Form Requests: `Rule::exists('payroll_catalog_items', 'code')->where(fn ($q) => $q->where('catalog_type', $type)->where('is_active', true))`.

## Capas a implementar

- [ ] Migracion(es) — `seleccion_ingresos`, `seleccion_examenes_ocupacionales` + seed catalog items
- [ ] Modelo(s) — `SeleccionIngreso`, `SeleccionExamenOcupacional` (+ factories)
- [ ] Config — `config/seleccion.php`; extender `employee_ficha.php`, `access.php`, `audit.php`
- [ ] Access — `SeleccionAccessService` + trait `HasSeleccionTabs`
- [ ] Audit — `SeleccionAuditLogService`
- [ ] Services — dashboard metrics; datatable ingreso; datatable examen; catalog admin whitelist; duplicate-cedula check
- [ ] Controlador(es) — `SeleccionController` (dashboard + ingresos + examenes) y/o split `SeleccionCatalogController`
- [ ] Form Request(s) — store/update ingreso; store/update examen; catalog store/update; métricas filters
- [ ] Export Excel — clases basadas en `BaseExport` (ingresos + examenes) + `<x-export-excel>`
- [ ] Vista(s) Blade — shell tabs + dashboard + ingresos + examenes + catalogos
- [ ] JavaScript — DataTables server-side; dashboard charts entry Vite; confirmación duplicado
- [ ] Nav — `NavigationResolver`, `SidebarVisibilityService`, `User::defaultSeleccionBoardUrl()`
- [ ] Tests — Feature suite Selección
- [ ] Docs modulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` — todos los selects.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- `RequisitionSelectionOfficerAccessService::selectableSelectionOfficers()` — opciones RESPONSABLE.
- Patrón UI catálogos Ficha (`catalogs/index` tarjetas + `?catalog=`).
- ApexCharts + `resources/js/charts/apex-defaults.js`.
- DataTables server-side (referencia `EmployeeCursoDatatableService`).
- `SystemAuditService` vía wrapper módulo.

## Documentacion a actualizar

- [ ] `docs/modules/seleccion.md` (nuevo)
- [ ] `docs/user/seleccion.md` (nuevo)
- [ ] `docs/INDEX.md`
- [ ] `docs/ACCESS_CONTROL.md` — permisos nuevos
- [ ] `docs/ARCHITECTURE.md` — fila ownership `seleccion`
- [ ] `README.md` (si lista módulos GH)

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Permisos, board, tabs, admin groups, canonical area |
| `routes/areas/gestion_humana.php` | Grupo rutas Selección |
| `config/employee_ficha.php` | Nuevos `catalog_type` + exclusión UI Ficha |
| `config/audit.php` | Módulo `seleccion` |
| Nav shared | `NavigationResolver`, `SidebarVisibilityService`, `User` (default board URL) |
| Posible CSS | `resources/css/app.css` solo si hacen falta tokens KPI/badge (mínimo) |
| Seeders / sync | `PermissionCatalog` / comando sync permisos; seeder catálogos payroll |

**Flag en `docs/TASKS.md`:** `shared-files` = sí (lista anterior). Un solo Feature agent por slice que toque shared; no paralelizar T1 con otros editores de `access.php`.

## Criterios de aceptacion

1. Con board + `seleccion.view`, el usuario ve **Selección** en sidebar GH y pestañas Dashboard / Ingreso / Examen; **no** ve Catálogos ni botones mutación.
2. Con `seleccion.edit`, ve Catálogos y puede CRUD Ingreso/Examen/catálogos whitelist.
3. Sin permisos (ni bypass): 403 en todas las rutas del grupo.
4. `manage.users` bypassa board/view/edit como Cursos.
5. Rol `usuario` / `administrador` **no** reciben permisos Selección al hacer `app:sync-permissions` (salvo asignación manual previa).
6. Crear Ingreso con todos los campos válidos persiste fila; omitir cualquier campo → 422.
7. Crear segundo Ingreso con misma cédula **sin** `confirm_duplicate` → 422 + aviso; **con** confirmación → 201/redirect OK.
8. Misma cédula en Examen **no** dispara aviso al crear Ingreso (y viceversa).
9. DataTables Ingreso/Examen: server-side (`draw` / `recordsFiltered`); filtros afectan JSON y export.
10. Export Excel usa `BaseExport` / `<x-export-excel>`; respeta filtros; auth `seleccion.view`.
11. Dashboard metrics JSON refleja KPIs/gráficos acordados y filtros fechas/cliente/responsable.
12. Catálogos: solo 7 tipos; seed RH (8), estado civil (5), SOLICITUD (11, un DXEMO); Ficha Catalogos **no** lista los 3 tipos nuevos.
13. Borrado es duro (fila desaparece de BD); confirmación UI.
14. Eventos audit en create/update/delete de registros y catálogo Selección.
15. Selectores usan `<x-searchable-select>`; sin Select2.
16. Tests Feature verdes para permisos, CRUD, duplicado, dashboard, export, catálogos.
17. Solo `php artisan migrate` (sin fresh); datos existentes intactos.

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan app:sync-permissions`.
2. Asignar manualmente board+view(+edit) a un usuario de prueba GH; verificar sidebar y pestañas.
3. CRUD Ingreso y Examen; probar aviso duplicado B; export Excel.
4. Dashboard: cambiar filtros y ver refresh de KPIs/gráficos.
5. Catálogos: CRUD en un tipo nuevo y en `city`; verificar exclusión en Ficha.
6. `php artisan test --compact --filter=Seleccion` (o path `tests/Feature/GestionHumana/Seleccion*`).
7. `npm run build` si hay entry charts nueva (avisar al usuario si Vite manifest falta).

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Shared-files (`access.php`, nav, `employee_ficha.php`) | Un agente a la vez; Task Card T1 primero; flag en TASKS. |
| Reclutadores vacíos | Si no hay `selection_officer`, select RESPONSABLE vacío → no se puede crear; documentar dependencia FEAT-011 / Parámetros GH. |
| Clientes / uniforms inactivos o vacíos | Validar exists; uniforms filtrar `is_active`. |
| Codes RH con `+` | Binding por id en catálogos; encoding OK en forms. |
| Dual edit city/position/eps/afp (Ficha + Selección) | Misma tabla; cambios visibles en ambos; sin sync especial. |
| Exclusión tipos nuevos en Ficha | Olvidar filtro → aparecen en Ficha; cubrir con test. |
| Dashboard “en proceso” | Code canónico `EN_PROCESO`; no matchear por label. |
| Volumen listados | Server-side obligatorio desde V1. |
| Datos | Prohibido fresh/wipe; seed solo upsert. |

## Sugerencia de Task Cards (para plan AgentSj)

| Task | Scope | Notas |
| --- | --- | --- |
| **T1** | Accesos / nav / shell | `access.php`, permisos, `SeleccionAccessService`, tabs, rutas index/dashboard shell, NavigationResolver/Sidebar/User, tests permisos/403. **shared-files.** |
| **T2** | Catálogos + seed | Extender `employee_ficha.php`; seed 3 tipos; UI Catálogos whitelist; Form Requests; exclusión Ficha; tests seed + CRUD + exclusión. |
| **T3** | Ingreso | Migración/modelo `seleccion_ingresos`; datatable; CRUD; lookup duplicado B; export; audit; tests. |
| **T4** | Examen ocupacional | Migración/modelo `seleccion_examenes_ocupacionales`; datatable; CRUD; duplicado B; export; audit; tests. |
| **T5** | Dashboard | Metrics service; KPIs/gráficos ApexCharts; filtros AJAX; entry Vite; tests metrics. |

Orden recomendado: T1 → T2 → T3 ∥ T4 (si no chocan shared) → T5 (consume ambas tablas). Preferir **secuencial** T3 luego T4 si un solo Feature agent.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas 1–5)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion (opcional post-brief; AgentSj puede planificar)
