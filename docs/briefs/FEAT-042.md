# Feature Brief — FEAT-042

> Brief final del Arquitecto (2026-10-02). Consolida `docs/briefs/FEAT-042-analyst.md` + run log + decisiones cerradas del usuario (Propuesta A, masivo B, catálogos ESTADO+SOLICITUD, días hábiles, Dashboard KPIs). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-042 |
| Modulo / area | Gestion humana — tablero **Cliente interno** (`cliente_interno`) |
| Titulo | Tablero Cliente interno GH (Dashboard + Solicitudes + Catálogos) |
| Solicitante | Usuario / AgentSj (chat 2026-10-02 cliente interno GH) |
| Fecha | 2026-10-02 |

## Objetivo

Dar a Gestión Humana un **tablero dedicado Cliente interno** para registrar, consultar y analizar **solicitudes** de personas (cédula, nombre, correo, tipo de solicitud, fechas, estado, novedad y días hábiles de respuesta), con:

1. **Dashboard** de seguimiento (total, por estado, días de respuesta, tendencia mensual).
2. **Solicitudes:** filtros, DataTables server-side, alta/edición/borrado duro, import masivo **replace por año+mes**, export Excel.
3. **Catálogos:** administración de **ESTADO** y **SOLICITUD** (tipos).

Hoy el proceso no existe en plataforma. El módulo sigue el patrón de área única GH (Formación / Selección) con permisos **Propuesta A** (view/edit por pestaña + `parameters.edit`), audit vía `SystemAuditService`, y migraciones solo aditivas.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Área / UI | Tablero GH `cliente_interno`, label exacto **Cliente interno**. |
| 2 | Pestañas | Exactas: **Dashboard**, **Solicitudes**, **Catálogos**. |
| 3 | Permisos | Propuesta A: `view.board.gestion_humana.cliente_interno`; `cliente_interno.solicitudes.view` / `.edit` (`edit` ⇒ `view`); `cliente_interno.parameters.edit` (Catálogos). Dashboard: `solicitudes.view` **OR** `parameters.edit` (sin permiso KPI aparte). Bypass `manage.users`. |
| 4 | Campos solicitud | fecha_solicitud, nombre_apellidos, cedula, correo_electronico, solicitud (FK catálogo), fecha_respuesta, estado (FK catálogo), novedad (texto libre), dias_respuesta (editable), anio/mes derivados de fecha_solicitud. **No inventar campos extra.** |
| 5 | Validación v1 | Obligatorios: fecha_solicitud, nombre_apellidos, cedula, solicitud. Opcionales (pueden vacíos): correo, fecha_respuesta, novedad, dias_respuesta, estado. |
| 6 | Catálogos | **ESTADO** + **SOLICITUD**. NOVEDAD = texto libre. |
| 7 | Seed ESTADO | Pendiente, En proceso, Respondida, Cerrada. |
| 8 | Seed SOLICITUD | **Vacío** (usuario no dio lista). Alta de tipos solo vía UI Catálogos. |
| 9 | Masivo | Usuario elige año+mes → **replace solo ese periodo**. Filas Excel fuera del periodo se **aceptan** con anio/mes reales de `fecha_solicitud` (**opción B**). Confirmación UI antes de borrar el periodo. |
| 10 | Días de respuesta | Días **hábiles** (lun–vie) entre fecha_solicitud y fecha_respuesta; `null` si falta fecha_respuesta; usuario puede override; conservar override al re-guardar (ver § Override). |
| 11 | Dashboard v1 | Total; conteo por ESTADO; promedio/distribución días respuesta; tendencia por mes; filtros año/mes. |
| 12 | Solicitudes UI | Filtros, DT `serverSide: true`, form alta, editar, eliminar definitivo, masivo, export `BaseExport` + `<x-export-excel>`, icon-only, `<x-searchable-select>`. |
| 13 | Duplicados cédula | Varios registros por misma cédula **permitidos** (sin bloqueo ni aviso obligatorio). |
| 14 | Fuera V1 | Bridge Ficha, notificaciones correo, soft-delete, jobs async. |
| 15 | Estándares | Audit central; multi-driver; **prohibido** `migrate:fresh` / wipe / TRUNCATE operativo. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Módulo | Board key `cliente_interno`; hogar `gestion_humana`; `base_area_tab => false` | Patrón Formación / Selección. |
| Access | `ClienteInternoAccessService`: board, `canViewSolicitudes` (view∨edit), `canEditSolicitudes`, `canEditParameters`, `canViewDashboard` (solicitudes.view∨parameters.edit∨bypass), `visibleTabsFor`; bypass `manage.users` | Propuesta A cerrada; espejo Comercial (view/edit por pestaña + parameters). |
| Persistencia catálogos | Tablas propias `cliente_interno_estados` y `cliente_interno_tipos_solicitud` (no `payroll_catalog_items`) | Dominio exclusivo del módulo; evita exclusión Ficha y dual-edit. |
| Persistencia datos | Tabla `cliente_interno_solicitudes` con FK a ambos catálogos (`nullOnDelete` en estado; `restrictOnDelete` en tipo solicitud) | Estado opcional; solicitud obligatoria. |
| anio / mes | `unsignedSmallInteger` / `unsignedTinyInteger` derivados siempre de `fecha_solicitud` (manual + masivo); no inputs libres | Decisión cerrada. |
| Override días | Columna boolean `dias_respuesta_manual` (default `false`) | Detección explícita; ver algoritmo abajo. |
| Cálculo hábiles | Contar lun–vie estrictos entre fechas (excluye sáb/dom). **Sin** calendario festivos en V1 | Alcance acotado; documentar en docs módulo. |
| Masivo B | Validar archivo → parsear → `DELETE WHERE anio=? AND mes=?` → insert **todas** las filas válidas (incl. fuera de periodo) en transacción | Replace solo del filtro; acepta spillover. |
| Controllers | `ClienteInternoController` (shell + dashboard + solicitudes) + opcional `ClienteInternoCatalogController` si el slice lo pide | Un board; sin Repository. |
| Charts | ApexCharts + entry Vite `cliente-interno-dashboard-charts.js` + `apex-defaults.js` | Estándar ARCHITECTURE.md. |
| Selectores / export / chrome | `<x-searchable-select>`; `BaseExport`; `.module-tab`; `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn` | AGENTS.md. |
| Roles | Solo `super-admin` recibe permisos vía sync; `administrador` / `usuario` **sin** paquete por defecto | Igual Formación/Selección. |
| Repository | **No.** | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental. | Protección de datos. |
| Slice | Task Cards verticales **T1–T5** | Shared-files en T1. |

### Algoritmo — días hábiles + override

```text
function businessDays(fecha_solicitud, fecha_respuesta):
  si falta cualquiera → null
  contar días lun–vie en (fecha_solicitud, fecha_respuesta]  // o [start, end] inclusivo documentado
  (Feature: contar días estrictamente posteriores a solicitud hasta e incl. respuesta,
   o diferencia hábil estándar del proyecto; documentar en tests con casos fijos)

Al CREATE:
  si request trae dias_respuesta explícito (no vacío) Y distinto del calculado (o sin fecha_respuesta):
      dias_respuesta = valor request; dias_respuesta_manual = true
  si no:
      dias_respuesta = businessDays(...); dias_respuesta_manual = false

Al UPDATE:
  si dias_respuesta_manual ya era true Y el usuario NO envió cambio de dias_respuesta
      y solo cambió otras fechas/campos:
      → conservar dias_respuesta (no recalcular)
  si el usuario editó el campo dias_respuesta (valor distinto al auto o flag UI):
      → guardar valor; dias_respuesta_manual = true
  si dias_respuesta_manual == false (o usuario pide “recalcular” / limpia override):
      → dias_respuesta = businessDays(...); manual = false
  si fecha_respuesta pasa a null y manual == false:
      → dias_respuesta = null

UI: checkbox o implícito — al editar el input “Días de respuesta”, marcar override.
Import masivo: si columna días vacía → auto (manual=false); si trae número → persistir y manual=true.
```

### Estrategia masivo replace-por-periodo (opción B)

```text
1. Usuario elige anio + mes + archivo; confirma UI (conteo filas a borrar del periodo).
2. Validar headers; parsear todas las filas.
3. Filas vacías → skip. Errores en obligatorias → rechazar import completo (dataset intacto).
4. Solicitud: resolver por nombre/code del catálogo; si no existe → error de fila (rechazo completo V1).
5. Estado: vacío OK; si trae valor, debe existir en catálogo.
6. DB::transaction:
     DELETE FROM cliente_interno_solicitudes WHERE anio = :anio AND mes = :mes;
     INSERT todas las filas válidas (también las con anio/mes ≠ filtro).
7. Audit import_replace_period {anio, mes, deleted_in_period, imported, accepted_outside_period, skipped_empty}.
8. Nunca TRUNCATE / migrate:fresh.
```

### Diagrama de pestañas

```text
[Sidebar GH: Cliente interno] --board--> shell
        │
        ├── Dashboard     (solicitudes.view OR parameters.edit)
        ├── Solicitudes   (solicitudes.view)  DT + filtros + export
        │                     └── edit: alta / editar / eliminar / masivo
        └── Catálogos     (parameters.edit)  ESTADO + SOLICITUD
```

## Alcance

### Incluye

- Tablero sidebar **Cliente interno** en Gestión humana con pestañas **Dashboard**, **Solicitudes**, **Catálogos**.
- Permisos nuevos (board + solicitudes view/edit + parameters.edit) + Admin UI subgroup + sync.
- Migraciones: catálogos ESTADO/SOLICITUD + `cliente_interno_solicitudes` (multi-driver) + seed ESTADO + factories.
- `ClienteInternoAccessService` + nav (`NavigationResolver`, `SidebarVisibilityService`, `User`).
- Catálogos: CRUD ESTADO y tipos SOLICITUD; seed ESTADO; seed SOLICITUD vacío.
- Solicitudes: DT server-side, filtros, CRUD (alta/editar/delete duro), export, form con searchable-select.
- Import masivo replace-por-periodo (opción B) + plantilla + confirmación UI.
- Cálculo días hábiles + override (`dias_respuesta_manual`).
- Dashboard v1: total, por estado, promedio/distribución días, tendencia mensual; filtros año/mes; ApexCharts.
- Auditoría create/update/delete/import/export (wrapper módulo).
- Tests PHPUnit Feature (acceso, CRUD, catálogos, masivo B, días hábiles/override, KPIs).
- Docs: `docs/modules/cliente-interno.md` + `docs/user/cliente-interno.md` (+ ACCESS / INDEX / ARCHITECTURE). Documentador al cierre.

### Fuera de alcance

- Bridge / sync con Ficha empleados u otros módulos GH.
- Notificaciones por correo.
- Soft-delete / versionado histórico del dataset.
- Jobs / colas asíncronas de import.
- Calendario de festivos nacionales en el cálculo de días hábiles.
- Permiso KPI de dashboard aparte (`view.board…dashboard` propio del módulo).
- Migración automática de permisos Spatie legacy.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Tablero y acceso

1. Labels: tablero **Cliente interno**; pestañas **Dashboard** / **Solicitudes** / **Catálogos**.
2. Sidebar: `view.board.gestion_humana.cliente_interno` (o bypass `manage.users`).
3. Dashboard: `cliente_interno.solicitudes.view` **OR** `cliente_interno.parameters.edit` (o edit solicitudes vía implicación, o bypass).
4. Ver listado / filtros / export Solicitudes: `solicitudes.view` (o `solicitudes.edit` o bypass).
5. Alta / editar / eliminar / plantilla / import: `solicitudes.edit` (o bypass).
6. Catálogos (subnav + CRUD): solo `parameters.edit` (o bypass).
7. `solicitudes.edit` ⇒ `solicitudes.view` en AccessService (no depender de Spatie).
8. Usuario solo con `parameters.edit` (+ board): ve Dashboard + Catálogos; **no** muta solicitudes ni ve listado completo salvo que también tenga view (Feature: sin view, pestaña Solicitudes oculta; dashboard sí).

### Solicitud (`cliente_interno_solicitudes`)

9. Obligatorios: `fecha_solicitud`, `nombre_apellidos`, `cedula`, `tipo_solicitud_id` (catálogo SOLICITUD activo).
10. Opcionales: `correo_electronico`, `fecha_respuesta`, `estado_id`, `novedad`, `dias_respuesta`.
11. `anio` / `mes` siempre derivados de `fecha_solicitud` al persistir.
12. Varios registros por misma cédula permitidos.
13. Eliminar: confirmación UI; **DELETE** físico.
14. Correo: si presente, validar formato email; si vacío, null.
15. Tipo solicitud debe existir y estar activo; estado si presente, activo.

### Días de respuesta

16. Auto = días hábiles lun–vie entre `fecha_solicitud` y `fecha_respuesta`; null si falta `fecha_respuesta`.
17. Override manual conservado según `dias_respuesta_manual` (algoritmo § Decisiones técnicas).
18. Sin festivos en V1.

### Catálogos

19. ESTADO seed: code/name `PENDIENTE`/`Pendiente`, `EN_PROCESO`/`En proceso`, `RESPONDIDA`/`Respondida`, `CERRADA`/`Cerrada` (sort 1–4).
20. SOLICITUD: seed **vacío**; operador crea tipos en UI antes de alta/masivo (o falla validación si no hay match).
21. CRUD: code + name + is_active + sort_order. Unique `(code)` por tabla.
22. DELETE bloqueado si hay referencias en `cliente_interno_solicitudes`; permitir desactivar (`is_active=false`).

### Masivo

23. Replace **solo** filas del `anio`+`mes` seleccionados en el filtro del import.
24. Filas con `fecha_solicitud` fuera del periodo: **aceptar** con anio/mes reales (B).
25. Confirmación UI obligatoria con conteo de filas a borrar del periodo.
26. Validar completo antes de DELETE; errores obligatorios → abortar sin borrar.
27. Headers plantilla alineados a columnas UI/Excel acordadas.

### Dashboard

28. Filtros: `anio` (requerido o default año actual / último con datos), `mes` opcional.
29. KPIs: `total`; `por_estado` (conteos; incluir “Sin estado”); `dias_respuesta` promedio + distribución (bins o histograma razonable); `tendencia_mensual` (conteos por mes del año filtrado).
30. Sin mutaciones en Dashboard.
31. Solo filas con `dias_respuesta` no null entran al promedio/distribución.

### Auditoría

32. Eventos: create/update/delete solicitud; create/update/delete catálogo; `import_replace_period`; `export`. Metadata con conteos; sin volcar PII masiva.
33. Módulo audit `cliente_interno`, area `gestion_humana`.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.cliente_interno` | `super-admin` (todos); resto manual Admin | Ver tablero **Cliente interno** en sidebar GH |
| `cliente_interno.solicitudes.view` | Paquete consulta / completo (manual) | Cliente interno: Ver solicitudes, filtros y export |
| `cliente_interno.solicitudes.edit` | Paquete operativo (manual) | Cliente interno: Alta, editar, eliminar e import masivo |
| `cliente_interno.parameters.edit` | Paquete catálogos (manual) | Cliente interno: Catálogos ESTADO y SOLICITUD |

**Labels Admin legibles:** `Cliente interno: Ver solicitudes`, `Cliente interno: Editar solicitudes`, `Cliente interno: Catálogos` (no keys crudas como label principal).

**Paquetes recomendados:**

| Perfil | Permisos |
| --- | --- |
| Solo consulta | board + `solicitudes.view` |
| Operativo GH | board + `solicitudes.view` + `solicitudes.edit` |
| Solo catálogos | board + `parameters.edit` |
| Completo | board + view + edit + parameters.edit |

### Implicaciones en AccessService (obligatorio)

| Permiso otorgado | Efecto runtime |
| --- | --- |
| `cliente_interno.solicitudes.edit` | Puede `solicitudes.view` aunque Spatie no tenga view |
| `cliente_interno.solicitudes.view` | Listado + export + (con board) Dashboard |
| `cliente_interno.parameters.edit` | Catálogos + Dashboard (sin listado solicitudes) |
| `manage.users` | Bypass board / view / edit / parameters |

### Cambios en `config/access.php`

- `system_permissions`: las 3 keys funcionales.
- `boards`: `'cliente_interno' => 'Cliente interno'`.
- `board_canonical_areas.cliente_interno`: `home => gestion_humana`, `base_area_tab => false`.
- `cliente_interno_tabs`: `dashboard => Dashboard`, `solicitudes => Solicitudes`, `catalogos => Catálogos`.
- Admin `permission_groups` → `gestion_humana`: board + subgroup **Cliente interno**.
- **Sin** migración de datos Spatie legacy.

### Seeders / sync

- `app:sync-permissions`: crea permisos; `super-admin` recibe todos.
- `administrador` / `usuario`: **no** paquete Cliente interno por defecto.
- Tras deploy: sync + re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (ya `require` en `web.php`; **no** tocar `web.php` salvo descubrimiento contrario).

Prefijo: `/gestion-humana/cliente-interno` · nombre `gestion-humana.cliente-interno.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect default tab. Board + (view∨parameters) |
| GET | `/dashboard` | `dashboard` | KPIs. Dashboard access |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON. Dashboard access |
| GET | `/solicitudes` | `solicitudes` | Shell listado. `solicitudes.view` |
| GET | `/solicitudes/datatable` | `solicitudes.datatable` | DT JSON. view |
| GET | `/solicitudes/exportar` | `solicitudes.export` | Excel filtrado. view |
| GET | `/solicitudes/plantilla-importacion` | `solicitudes.import-template` | Plantilla. edit |
| GET | `/solicitudes/periodo-conteo` | `solicitudes.period-count` | (opcional) filas a borrar año+mes. edit |
| POST | `/solicitudes/importar` | `solicitudes.import` | Replace periodo. edit + confirm |
| POST | `/solicitudes` | `solicitudes.store` | Crear. edit |
| PATCH | `/solicitudes/{clienteInternoSolicitud}` | `solicitudes.update` | Editar. edit |
| DELETE | `/solicitudes/{clienteInternoSolicitud}` | `solicitudes.destroy` | Borrado duro. edit |
| GET | `/catalogos` | `catalogos` | UI catálogos (`?catalog=`). parameters.edit |
| POST | `/catalogos/{type}` | `catalogos.store` | type ∈ `estados`\|`tipos-solicitud`. parameters.edit |
| PATCH | `/catalogos/{type}/{item}` | `catalogos.update` | parameters.edit |
| DELETE | `/catalogos/{type}/{item}` | `catalogos.destroy` | Bloquear si refs. parameters.edit |

Middleware grupo: `password.changed` (como resto GH). Authorize en controller vía AccessService.

## Base de datos

Solo migraciones **nuevas** + seed upsert ESTADO. Prohibido `migrate:fresh`.

### Tabla `cliente_interno_estados`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `code` | string(50) | Unique; seed PENDIENTE, EN_PROCESO, RESPONDIDA, CERRADA |
| `name` | string(100) | Label UI |
| `is_active` | boolean default true | |
| `sort_order` | unsignedInteger default 0 | |
| `created_at` / `updated_at` | timestamps | |

### Tabla `cliente_interno_tipos_solicitud`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `code` | string(50) | Unique |
| `name` | string(150) | Label UI / match Excel |
| `is_active` | boolean default true | |
| `sort_order` | unsignedInteger default 0 | |
| `created_at` / `updated_at` | timestamps | |

**Seed inicial: 0 filas.** Documentar en docs módulo: crear al menos un tipo en Catálogos antes de operar.

### Tabla `cliente_interno_solicitudes`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `fecha_solicitud` | date | index; fuente anio/mes |
| `anio` | unsignedSmallInteger | derivado; index |
| `mes` | unsignedTinyInteger | 1–12 derivado; index |
| `nombre_apellidos` | string(255) | |
| `cedula` | string(50) | index |
| `correo_electronico` | string(150) nullable | |
| `tipo_solicitud_id` | FK → `cliente_interno_tipos_solicitud.id` | `restrictOnDelete` |
| `fecha_respuesta` | date nullable | |
| `estado_id` | FK → `cliente_interno_estados.id` nullable | `nullOnDelete` |
| `novedad` | text nullable | texto libre |
| `dias_respuesta` | unsignedInteger nullable | |
| `dias_respuesta_manual` | boolean default false | override |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Índices adicionales: compuesto `(anio, mes)`; `(estado_id)`; `(tipo_solicitud_id)`.

### Seed ESTADO

| code | name | sort_order |
| --- | --- | --- |
| `PENDIENTE` | Pendiente | 1 |
| `EN_PROCESO` | En proceso | 2 |
| `RESPONDIDA` | Respondida | 3 |
| `CERRADA` | Cerrada | 4 |

### Config

- `config/cliente_interno.php`: whitelist catalog types, headers import, chunk_size, bins distribución días.
- `config/audit.php`: módulo `cliente_interno` → `gestion_humana`.

### Mapeo Excel (plantilla / export / import)

| Excel (header sugerido) | Campo |
| --- | --- |
| Fecha de solicitud | `fecha_solicitud` (+ anio, mes) |
| Nombre y apellidos | `nombre_apellidos` |
| Cédula | `cedula` |
| Correo electrónico | `correo_electronico` |
| Solicitud | nombre o code → `tipo_solicitud_id` |
| Fecha de respuesta | `fecha_respuesta` |
| Estado | nombre o code → `estado_id` (opcional) |
| Novedad | `novedad` |
| Días de respuesta | `dias_respuesta` (+ manual si presente) |

## Capas a implementar

- [ ] Migracion(es) — estados, tipos_solicitud, solicitudes + seed ESTADO
- [ ] Modelo(s) — `ClienteInternoEstado`, `ClienteInternoTipoSolicitud`, `ClienteInternoSolicitud` (+ factories)
- [ ] Config — `config/cliente_interno.php`; `access.php`; `audit.php`
- [ ] Access — `ClienteInternoAccessService` + trait `HasClienteInternoTabs`
- [ ] Audit — `ClienteInternoAuditLogService`
- [ ] Services — Datatable, Import (replace periodo), Dashboard metrics, Catalog, BusinessDays helper
- [ ] Controlador(es) — `ClienteInternoController` (+ CatalogController opcional)
- [ ] Form Request(s) — store/update solicitud; import; catalog store/update
- [ ] Export — `ClienteInternoSolicitudesExport` + `ClienteInternoImportTemplateExport` (`BaseExport`)
- [ ] Vista(s) Blade — `resources/views/areas/gestion_humana/cliente_interno/`
- [ ] JavaScript — DT server-side; confirm import; entry Vite charts
- [ ] Nav — NavigationResolver, SidebarVisibilityService, User
- [ ] Tests Feature
- [ ] Docs módulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` — filtros, formularios, catálogos.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- Chrome `.module-tab` + icon buttons estándar.
- ApexCharts + `resources/js/charts/apex-defaults.js`.
- DataTables server-side (ref. `EmployeeCursoDatatableService` / Formación).
- Patrón UI catálogos Selección/Ficha (tarjetas + `?catalog=`).
- PhpSpreadsheet (plantilla/import).
- `SystemAuditService` (wrapper fino).
- Referencias: FEAT-041 Formación (import + anio/mes), FEAT-035 Selección (CRUD + Catálogos + Dashboard), Comercial (view/edit + parameters.edit).

## Documentacion a actualizar

- [ ] `docs/modules/cliente-interno.md` (nuevo)
- [ ] `docs/user/cliente-interno.md` (nuevo)
- [ ] `docs/ACCESS_CONTROL.md`
- [ ] `docs/ARCHITECTURE.md` — fila ownership
- [ ] `docs/INDEX.md`
- [ ] `README.md` (si lista tableros GH)
- [ ] Documentador al cierre del flujo

## Archivos compartidos (`shared-files`)

**`shared-files: si`**

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, permisos, tabs, Admin UI |
| `config/audit.php` | Módulo `cliente_interno` |
| `routes/areas/gestion_humana.php` | Grupo rutas Cliente interno |
| `routes/web.php` | Solo si faltara require (hoy **ya** incluye GH; no esperado) |
| `app/Services/Navigation/NavigationResolver.php` | Entrada sidebar |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board |
| `app/Models/User.php` | `defaultClienteInternoBoardUrl` / tabs |
| Vite (`vite.config.js` / entries) | Entry charts dashboard si aplica |
| Seeders / PermissionCatalog / sync | Registrar permisos Spatie |

**Flag en `docs/TASKS.md`:** `shared-files` = sí. **T1** es la única Task Card autorizada a tocar `access.php` / nav / audit entry. T2–T5 no editan shared salvo hotfix autorizado por AgentSj.

## Criterios de aceptacion

1. Sidebar GH muestra **Cliente interno** solo con `view.board.gestion_humana.cliente_interno` (o bypass).
2. Usuario solo `solicitudes.view` (+ board): ve Dashboard + Solicitudes + export; **403** en mutaciones, import y Catálogos.
3. Usuario solo `parameters.edit` (+ board): ve Dashboard + Catálogos; **no** ve/muta Solicitudes.
4. Usuario con `solicitudes.edit` (sin view Spatie) puede ver y mutar solicitudes (implicación view).
5. `manage.users` bypassa board/view/edit/parameters.
6. Alta con obligatorios OK; omitir fecha/nombre/cédula/solicitud → 422; opcionales vacíos OK.
7. anio/mes siempre derivados de fecha_solicitud tras save.
8. Días hábiles: caso fijo (p. ej. lun→vie = 4 o 5 según regla documentada); null sin fecha_respuesta; override se conserva al re-guardar otras fechas.
9. DataTables `serverSide: true`; filtros afectan JSON y export.
10. Eliminar: fila desaparece (delete duro) tras confirmación UI.
11. Catálogos: seed 4 estados; SOLICITUD vacío al día 1; CRUD + bloqueo DELETE con refs.
12. Masivo: confirma → borra solo periodo elegido → inserta filas in-periodo y fuera-periodo; audit con `accepted_outside_period`.
13. Masivo con errores obligatorios: periodo **intacta**.
14. Dashboard: total, por estado, promedio/distribución días, tendencia mensual; filtros año/mes.
15. Selectores `<x-searchable-select>`; sin Select2; sin `excelHtml5`.
16. Auditoría en create/update/delete/import/export.
17. Migración MySQL local + sqlite tests; **no** `migrate:fresh` / wipe / TRUNCATE operativo.
18. Roles `administrador`/`usuario` sin paquete por defecto tras sync.

## Validacion local

1. `php artisan migrate` (incremental).
2. `php artisan app:sync-permissions`.
3. Asignar board + view/edit/parameters a usuario de prueba; verificar sidebar, pestañas y 403s.
4. Catálogos: verificar 4 estados; crear ≥1 tipo SOLICITUD; probar desactivar/bloqueo delete.
5. Alta/editar/eliminar solicitud; override días; export.
6. Plantilla → filas in/out periodo → import con confirmación → verificar replace B.
7. Dashboard metrics con/sin mes.
8. `php artisan test --compact` (suite Feature Cliente interno).
9. `npm run build` si hay entry charts nueva.
10. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Seed SOLICITUD vacío | Alta/masivo fallan hasta crear tipos en Catálogos; AgentSj confirma al usuario en 1 línea; docs + empty-state UI. |
| Match Excel ↔ tipo solicitud ambiguo | Match case-insensitive por `name` luego `code`; documentar; rechazo completo si no match V1. |
| Replace periodo destructivo | Confirmación UI + conteo + validación previa a DELETE + audit. |
| Spillover opción B confunde operadores | Mensaje post-import: importados in-periodo vs fuera-periodo. |
| Override días frágil | Flag `dias_respuesta_manual` + tests Feature; no solo “comparar al vuelo”. |
| Festivos no restados | Documentar V1 lun–vie; festivos = fuera de alcance. |
| Shared-files race | Solo T1 toca access/nav; AgentSj coordina. |
| Charts Vite manifest | Entry + `npm run build` en validación. |
| Volumen listado | DT server-side desde V1; índices (anio, mes). |

## Supuesto documentado (AgentSj → usuario, 1 línea)

> Catálogo **SOLICITUD** arranca **vacío**: ¿OK cargar tipos solo desde Catálogos el día 1, o envía lista seed antes de Feature?

## Slice de tareas (Task Cards)

| ID | Titulo | Alcance vertical | shared-files |
| --- | --- | --- | --- |
| **T1** | Shell + permisos + migración + modelos + nav | `access.php`, audit, rutas shell/redirect/dashboard placeholder, `ClienteInternoAccessService`, migraciones 3 tablas + seed ESTADO, modelos/factories, nav User/Resolver/Sidebar, trait tabs, tests acceso board/tabs/403 | **si** |
| **T2** | Catálogos ESTADO + SOLICITUD | UI Catálogos (`?catalog=`), CRUD Form Requests/service, bloqueo DELETE con refs, empty-state SOLICITUD, tests seed + CRUD | no |
| **T3** | Solicitudes CRUD + DT + filtros + export | Datatable service, vista listado, form alta/editar, delete duro, filtros searchable-select, export BaseExport, business days + override en store/update, tests CRUD/DT/export/días | no |
| **T4** | Masivo replace-por-periodo (opción B) | ImportService, plantilla, Form Request, modal confirmación + conteo periodo, spillover fuera periodo, audit metadata, tests B + no-wipe-on-error | no |
| **T5** | Dashboard KPIs | DashboardService, vista + metrics JSON, ApexCharts entry Vite, filtros año/mes, tests KPIs | no |

**Orden:** T1 → T2 → T3 → T4 → T5.  
T2 antes de T3 (FK solicitud obligatoria y selects dependen de catálogo). T5 tras T3 (datos reales). No combinar T3+T4: masivo B merece tarjeta y tests propios.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas 1–7 en `FEAT-042-analyst.md`)
- [x] Arquitecto — brief final
- [x] Usuario — `OK implementa` (2026-10-05; seed SOLICITUD vacío)
