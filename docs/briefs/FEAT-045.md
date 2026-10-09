# Feature Brief — FEAT-045

> Brief final del Arquitecto (2026-10-09). Consolida [`FEAT-045-analyst.md`](FEAT-045-analyst.md) + decisiones usuario (chat MT-ST-04 / run log). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-045 |
| Modulo / area | Gestión humana — tablero **MT-ST-04** (`mt_st_04`) |
| Titulo | Matriz de control de exámenes psicofísicos (armas) y psicosensométricos (vial) |
| Solicitante | Usuario / AgentSj (chat 2026-10-09 MT-ST-04) |
| Fecha | 2026-10-09 |
| Analista | [`docs/briefs/FEAT-045-analyst.md`](FEAT-045-analyst.md) |
| Run log | [`docs/runs/FEAT-045-run-log.md`](../runs/FEAT-045-run-log.md) |
| Referencia Excel | `MT-ST-04 CONTROL EXAMENES PSICOFISICOS Y PSICOSENSOMETRICO` — hoja **MATRIZ** |

## Objetivo

Digitalizar en SJ StatFlow la matriz Excel **MT-ST-04** para que Gestión Humana controle la vigencia de:

1. **Examen psicofísico** (manejo de armas).
2. **Examen psicosensométrico** (seguridad vial), con exclusión **NO APLICA** para cargos exactos `GUARDA` / `OPERADOR`.

Operadores con `mt_st_04.view` consultan Dashboard, filtran Matriz y exportan; con `mt_st_04.edit` hacen CRUD e import upsert. Una fila por cédula; identidad operativa desde Ficha (solo lectura); vencimientos y estados calculados/persistidos con recalculo al guardar y job diario.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Nombre UI tablero | **MT-ST-04** |
| 2 | Board key / hogar | `mt_st_04`; hogar sidebar `gestion_humana` |
| 3 | Pestañas | Exactas: **Dashboard**, **Matriz**. **Sin** Catálogos |
| 4 | Permisos | `view.board.gestion_humana.mt_st_04` + `mt_st_04.view` + `mt_st_04.edit`. **Sin** `parameters.edit` |
| 5 | Dashboard | Acceso con `mt_st_04.view` (edit ⇒ view). **Sin** permiso board dashboard aparte |
| 6 | Implicación | `edit` ⇒ `view` en `MtSt04AccessService` (no herencia Spatie) |
| 7 | Spatie | Asignación **manual**; sin migración automática legacy |
| 8 | Unicidad | 1 fila por cédula (`unique` en `document_number`) |
| 9 | Lookup ficha | NOMBRE=`full_name`, CARGO=`position_name`, CIUDAD=`work_city_name`, PUESTO=`cost_center_name` — solo lectura |
| 10 | Editables | ARMA, FECHA DE EXAMEN, APTO, OBSERVACIONES, FECHA EXAMEN (2), OBSERVACIONES2 |
| 11 | ARMA / APTO | Selects fijos **SI/NO** (sin catálogo BD) |
| 12 | Vencimientos | fecha examen correspondiente + **364** días; null si no hay fecha examen |
| 13 | Estados | Persistidos; recalculo al guardar (CRUD/import) + comando schedule diario |
| 14 | ESTADO2 NO APLICA | `mb_strtoupper(trim(CARGO), 'UTF-8')` exactamente `GUARDA` u `OPERADOR` |
| 15 | VENCERA | Ventana **≤ 30** días calendario hasta vencimiento; TZ **America/Bogota** |
| 16 | Listado default | Solo empleados **activos** en ficha; filtro UI para incluir/ver **desvinculados** |
| 17 | Import | Upsert por cédula; plantilla + export con estados/vencimientos |
| 18 | Bulk filas | No |
| 19 | RETIRADOS Excel | Fuera V1 |
| 20 | Dashboard KPIs | Total/Vigente/Vencera/Vencido (ex.1); mismos ex.2 excl. NO APLICA; Aptos vs no aptos + gráficos |
| 21 | UI | Calidad módulo desde T1 (referencia Formación / Acreditaciones / Cliente interno) |
| 22 | Alta / import | Cédula **debe existir** en Ficha; rechazar fila huérfana |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Area GH, board `mt_st_04`, tabs `dashboard` / `matriz` | Patrón Formación / Acreditaciones / Cursos |
| Permisos storage | `system_permissions` + `boards` + `board_canonical_areas` + `mt_st_04_tabs` + Admin subgroup **MT-ST-04** | Labels legibles; sin packs multi-pestaña |
| Access | `MtSt04AccessService`: `canViewBoard`, `canView` (`view`∨`edit`), `canEdit`, `visibleTabsFor`; bypass `manage.users` | Espejo `FormacionAccessService` / `CommercialAccessService` |
| Persistencia | Tabla **`mt_st_04_registros`** — 1 fila / cédula | Dataset operativo matriz |
| Enlace Ficha | Lógico por `document_number` (**sin FK** formal); join live en listado/KPIs/recalculo ESTADO2 | Patrón Acreditaciones/Cursos; evita huérfanos; cargo live para NO APLICA |
| Snapshot opcional | No persistir nombre/cargo/ciudad/puesto como fuente de verdad; UI/export leen Ficha live (fallback vacío si desaparece ficha) | Mitiga drift ESTADO2; listado siempre refleja ficha actual |
| Calculadora | `MtSt04EstadoCalculator` (vencimientos + ESTADO / ESTADO2) | Un solo lugar; CRUD, import, job y tests |
| VENCERA (formal) | Ver § Reglas — comparación **inclusiva ≤ 30 días** | Alineado a decisión usuario en run log |
| NO APLICA | Exact match con `mb_strtoupper(..., 'UTF-8')` | Cargos compuestos (`GUARDA SJ`) **no** excluyen |
| Datatable | `MtSt04DatatableService` + `serverSide: true` | Listado operativo; regla DT del repo |
| Import | `MtSt04ImportService` upsert por cédula; duplicados en archivo: **última fila gana** + warning conteo | Excel real suele repetir; reportar sin abortar todo |
| Export | `MtSt04Export` extends `BaseExport` + `<x-export-excel>` | Estándar proyecto |
| Plantilla | `MtSt04ImportTemplateExport` (headers operativos; **sin** columnas ESTADO/VENCIMIENTO editables) | Evita override manual de derivados |
| Dashboard | `MtSt04DashboardService::metrics()` + JSON; ApexCharts patrón Formación/GH | Universo default = activos ficha |
| Sync | Comando `mt_st_04:sync-estados` (`--date`, `--dry-run`) + schedule **06:25** Bogotá | Offset vs Cursos 06:15 / Acreditaciones 06:20 |
| Controllers | `MtSt04Controller` (shell + dashboard + matriz + DT + CRUD + import/export + lookup) | Un board; split opcional solo si crece |
| Form Requests | Store/Update matriz; Import; filtros DT | Authorize vía AccessService |
| Nav | `NavigationResolver`, `SidebarVisibilityService`, `User::defaultMtSt04BoardUrl()` + tabs | Igual Formación |
| Trait tabs | `HasMtSt04Tabs` → `.module-tab` Dashboard \| Matriz | Patrón GH |
| Audit | `config/audit.php` módulo `mt_st_04`; wrapper `MtSt04AuditLogService` | AGENTS.md |
| Config | `config/mt_st_04.php` (columnas import, estados, ventana 30, +364, cargos NO APLICA) | Evita hardcode |
| Rutas | Grupo en `routes/areas/gestion_humana.php` | Ownership GH; **no** tocar `web.php` salvo require ya existente |
| Schedule | Registro en `bootstrap/app.php` `withSchedule` | Shared-file puntual T4 |
| Repository | **No** | Convención proyecto |
| Migrate | Solo incremental. **Prohibido** `migrate:fresh` / wipe / TRUNCATE operativo | Protección de datos |
| Slice | Task Cards **T1–T4** (ver § Slice) | Shared-files en T1 (+ schedule T4) |

### Diagrama de pestañas

```text
[Sidebar GH: MT-ST-04] --board--> shell
        │
        ├── Dashboard   (mt_st_04.view)  KPIs + charts + metrics JSON
        └── Matriz      (mt_st_04.view)  DT server-side + filtros + export
                          │
                          └── edit: CRUD + lookup ficha + plantilla + import upsert
```

## Alcance

### Incluye

- Tablero sidebar **MT-ST-04** en Gestión humana (`view.board.gestion_humana.mt_st_04`).
- Permisos `mt_st_04.view` / `mt_st_04.edit` + Admin UI subgroup + sync catálogo Spatie (`app:sync-permissions`).
- Pestañas **Dashboard** | **Matriz** (sin Catálogos).
- Migración multi-driver `mt_st_04_registros` + modelo/factory.
- `MtSt04AccessService` + nav (Resolver / Sidebar / User) + trait tabs.
- Matriz: DataTables **server-side**; filtros (activo/desvinculado + operativos); CRUD modal/form; lookup ficha readonly por cédula.
- Vencimientos + estados persistidos; calculadora única; comando + schedule diario.
- Plantilla Excel + import **upsert por cédula**; export con estados/vencimientos.
- Dashboard: KPIs + gráficos ApexCharts; default universo activos.
- Audit central del módulo.
- Tests Feature: acceso, unicidad, reglas estado (bordes TZ Bogotá), NO APLICA, import upsert, default activos.
- Docs técnica + usuario (Documentador al cierre).
- UI pulida desde el primer slice (chrome Formación / Acreditaciones).

### Fuera de alcance

- Columna **RETIRADOS** del Excel.
- Pestaña Catálogos / `*.parameters.edit` / tablas de parámetros.
- Acciones **bulk** sobre filas seleccionadas.
- Migración automática de permisos Spatie a usuarios existentes.
- Notificaciones correo / push por vencimiento.
- Soft-delete / versionado histórico del dataset.
- Sync bidireccional editable Ficha ↔ matriz.
- Bridge con Cursos, Acreditaciones u otros tableros más allá del lookup por cédula.
- Edición manual de FECHA VENCIMIENTO / ESTADO / ESTADO2.
- Multi-fila por cédula o historial de exámenes por persona.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Tablero y acceso

1. Labels UI: tablero **MT-ST-04**; pestañas **Dashboard** / **Matriz**.
2. Sidebar: `view.board.gestion_humana.mt_st_04` (o bypass `manage.users`).
3. Ver Dashboard o Matriz: `mt_st_04.view` **o** `mt_st_04.edit`.
4. CRUD, plantilla, import: solo `mt_st_04.edit` (o bypass).
5. Export: `mt_st_04.view` (o edit vía implicación).
6. Botones mutación: UI solo con `canEdit`.

### Identidad y ficha

7. Clave de negocio: `document_number` (cédula), única.
8. Alta / update / import: la cédula **debe existir** en `employee_ficha_profiles`; si no → error de fila/validación; **no** crear huérfanos.
9. Campos solo lectura (live Ficha): `full_name`, `position_name` (CARGO), `work_city_name` (CIUDAD), `cost_center_name` (PUESTO).
10. Endpoint lookup por cédula (edit): devolver esos campos + `employment_status` para el formulario.

### Examen 1 — psicofísico (armas)

11. Editables: `arma` (SI/NO), `fecha_examen_1`, `apto` (SI/NO), `observaciones_1`.
12. `fecha_vencimiento_1` = `fecha_examen_1` + **364** días; null si no hay `fecha_examen_1`.
13. `estado_1` persistido según § Estados (sin rama NO APLICA).

### Examen 2 — psicosensométrico (vial)

14. Editables: `fecha_examen_2`, `observaciones_2`.
15. `fecha_vencimiento_2` = `fecha_examen_2` + **364** días; null si no hay fecha.
16. `estado_2` persistido: primero evaluar NO APLICA; si no, misma lógica de vigencia sobre `fecha_vencimiento_2`.

### Estados (calculadora — TZ America/Bogota, fechas calendario date-only)

Sea `hoy` = inicio del día en `America/Bogota`.

**Vigencia genérica** (ESTADO1 y ESTADO2 cuando aplica):

1. Si no hay fecha de vencimiento → estado **vacío** (`null` en BD; UI muestra celda vacía).
2. Else si `vencimiento < hoy` → **`VENCIDO`**.
3. Else si `vencimiento <= hoy + 30 días` (inclusivo) → **`VENCERA`**.  
   Equivale a: faltan **0..30** días calendario hasta el vencimiento (incluidos el día de vencimiento y el día exacto +30).
4. Else → **`VIGENTE`**.

**ESTADO2 — NO APLICA (prioridad máxima):**

1. Resolver CARGO live = `EmployeeFichaProfile.position_name` de la cédula (trim).
2. Si `mb_strtoupper($cargo, 'UTF-8')` es exactamente **`GUARDA`** o **`OPERADOR`** → **`NO APLICA`** (ignorar fechas examen/vencimiento2).
3. Else → vigencia genérica sobre `fecha_vencimiento_2`.

> Cargos compuestos (`GUARDA SJ`, `OPERADOR MENSUAL`) **no** son NO APLICA. Si el cargo en Ficha cambia, el próximo guardado y el job diario recalculan ESTADO2.

### Recalculo

17. Al **guardar** (store/update e import por fila): recalcular ambos vencimientos + ambos estados vía calculadora.
18. Comando diario `mt_st_04:sync-estados`: chunk sobre todas las filas; leer cargo live de Ficha; recalcular y persistir solo si cambió (opcional optimización).
19. Export y Dashboard leen estados **persistidos** (no fórmula divergente ad hoc en lectura).
20. Schedule: `dailyAt('06:25')->timezone('America/Bogota')->withoutOverlapping()` en `bootstrap/app.php`.

### Listado y filtros

21. Default: join/filtro `employment_status = activo` en Ficha.
22. Filtro UI `ficha_estado`: `activo` (default) | `desvinculado` | `todos`.
23. Filtros operativos sugeridos V1: cédula, nombre (like), cargo, estado_1, estado_2, apto, arma; searchable-select donde aplique.
24. Sin selección masiva ni acciones bulk.
25. DataTables `serverSide: true`; clase JS distinta de `.js-datatable`; `lengthMenu` sin `-1`.

### Import / plantilla / export

26. Plantilla descargable con headers alineados a campos editables + cédula (nombre/cargo solo ayuda; se ignoran al persistir).
27. **No** aceptar override de ESTADO / FECHA VENCIMIENTO desde Excel (columnas ausentes o ignoradas).
28. Import: upsert por `document_number`; cédula sin ficha → error fila; filas vacías → skip.
29. Duplicados de cédula en el mismo archivo: **última fila gana**; reportar `duplicates_collapsed`.
30. Export: incluye cédula, datos ficha live, editables, vencimientos y estados persistidos; respeta filtros activos del listado.
31. `BaseExport` + `<x-export-excel>`; PhpSpreadsheet; no `excelHtml5`.

### Dashboard

32. Universo default = mismos activos que Matriz (filtro `ficha_estado=activo`); opcional mismo filtro desvinculados/todos en UI dashboard si el Feature lo cablea sin coste (mínimo: activos).
33. KPIs examen 1: Total, Vigente, Vencera, Vencido (sobre filas del universo; estados persistidos).
34. KPIs examen 2: mismos conteos **excluyendo** filas `estado_2 = NO APLICA` (y excluyendo vacíos del denominador de distribución según implementación Feature — documentar: conteos absolutos por estado; Total2 = filas donde ESTADO2 no es NO APLICA).
35. Aptos vs no aptos: conteo de `apto` SI/NO (nullable aparte si aplica).
36. Gráficos: distribución estados examen 1; distribución estados examen 2 (sin NO APLICA o con segmento NO APLICA aparte); aptos vs no aptos — ApexCharts patrón Formación/GH.
37. Sin dimensión “año de carga” en V1: KPIs sobre el universo filtrable actual.

### Auditoría

38. Eventos mínimos: `created`, `updated`, `deleted`, `import_upsert`, `export`, `sync_estados` (comando).
39. Módulo audit `mt_st_04`, area `gestion_humana`; metadata con conteos / ids (evitar volcar PII masivo).

### Borrado

40. DELETE duro con `edit` (sin soft-delete). Confirmar en UI.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion (label Admin) |
| --- | --- | --- |
| `view.board.gestion_humana.mt_st_04` | `super-admin` (todos); asignar Admin | **MT-ST-04** (Ver tableros) |
| `mt_st_04.view` | Paquete GH / consulta | **MT-ST-04: Ver** — Dashboard, Matriz, filtros y export |
| `mt_st_04.edit` | Paquete GH operativo | **MT-ST-04: Editar** — CRUD, plantilla e import |

**Admin UI:** grupo **Gestion humana** → *Ver tableros* incluir `view.board.gestion_humana.mt_st_04`; subgroup **MT-ST-04** con `mt_st_04.view`, `mt_st_04.edit`.

**Sin** `mt_st_04.parameters.edit`. **Sin** `view.board.gestion_humana.mt_st_04_dashboard`.

### Implicaciones en AccessService (obligatorio)

| Permiso otorgado | Efecto runtime |
| --- | --- |
| `mt_st_04.edit` | Puede `mt_st_04.view` aunque Spatie no tenga view marcado |
| `mt_st_04.view` | Dashboard + Matriz + export; **no** CRUD/plantilla/import |

### Cambios en `config/access.php`

- `system_permissions`: `mt_st_04.view`, `mt_st_04.edit`.
- `boards`: `'mt_st_04' => 'MT-ST-04'`.
- `board_canonical_areas.mt_st_04`: `home => gestion_humana`, `base_area_tab => false`.
- `mt_st_04_tabs`: `dashboard => Dashboard`, `matriz => Matriz`.
- Admin `permission_groups` → `gestion_humana` boards + subgroup `mt_st_04`.
- **Sin** migración de datos Spatie legacy.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (ya cargado desde `routes/web.php` dentro de `auth`+`active`). Prefijo:

`/gestion-humana/mt-st-04` · nombre `gestion-humana.mt-st-04.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a default tab (dashboard). `view` |
| GET | `/dashboard` | `dashboard` | Vista KPIs. `view` |
| GET | `/dashboard/metrics` | `dashboard.metrics` | JSON KPIs. `view` |
| GET | `/matriz` | `matriz` | Listado shell. `view` |
| GET | `/matriz/datatable` | `matriz.datatable` | DT JSON. `view` |
| GET | `/matriz/exportar` | `matriz.export` | Excel filtrado. `view` |
| GET | `/matriz/plantilla-importacion` | `matriz.import-template` | Plantilla. `edit` |
| POST | `/matriz/importar` | `matriz.import` | Upsert. `edit` |
| GET | `/matriz/lookup` | `matriz.lookup` | Lookup ficha por cédula (JSON). `edit` (también usable en view si Feature lo prefiere solo edit) |
| POST | `/matriz` | `matriz.store` | Crear. `edit` |
| PATCH | `/matriz/{registro}` | `matriz.update` | Editar. `edit` |
| DELETE | `/matriz/{registro}` | `matriz.destroy` | Borrado duro. `edit` |

Middleware grupo: `auth`, `active`, `password.changed` (igual resto GH). Authorize en controller vía `MtSt04AccessService`.

### Columnas plantilla / import Excel (propuesta)

| Clave | Label | Persistencia |
| --- | --- | --- |
| `document_number` | CEDULA | Sí; required; debe existir en Ficha |
| `full_name` | NOMBRE COMPLETO | Ignorado (ayuda humana) |
| `arma` | ARMA | Sí; SI/NO |
| `fecha_examen_1` | FECHA DE EXAMEN | Sí; date |
| `apto` | APTO | Sí; SI/NO |
| `observaciones_1` | OBSERVACIONES | Sí; nullable |
| `fecha_examen_2` | FECHA EXAMEN | Sí; date examen 2 |
| `observaciones_2` | OBSERVACIONES2 | Sí; nullable |

**No** incluir en plantilla: ESTADO, ESTADO2, FECHA VENCIMIENTO, FECHA VENCIMIENTO2, CARGO, CIUDAD, PUESTO, RETIRADOS.

## Base de datos

Solo migración(es) **nuevas**. Prohibido `migrate:fresh` / wipe / TRUNCATE operativo. Multi-driver MySQL + sqlite tests.

### Tabla `mt_st_04_registros`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) | CEDULA; **unique** |
| `arma` | string(2) nullable | `SI` / `NO` |
| `fecha_examen_1` | date nullable | FECHA DE EXAMEN (psicofísico) |
| `fecha_vencimiento_1` | date nullable | Calculada = examen1 + 364 |
| `apto` | string(2) nullable | `SI` / `NO` |
| `observaciones_1` | text nullable | |
| `estado_1` | string(20) nullable | `VIGENTE` / `VENCERA` / `VENCIDO` / null |
| `fecha_examen_2` | date nullable | FECHA EXAMEN (psicosensométrico) |
| `fecha_vencimiento_2` | date nullable | Calculada = examen2 + 364 |
| `observaciones_2` | text nullable | |
| `estado_2` | string(20) nullable | `VIGENTE` / `VENCERA` / `VENCIDO` / `NO APLICA` / null |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Índices: `unique(document_number)`; indexes en `estado_1`, `estado_2`, `fecha_vencimiento_1`, `fecha_vencimiento_2`, `apto`, `arma`.

**Sin FK** a `employee_ficha_profiles` (enlace lógico por cédula).

### Config

- `config/mt_st_04.php`: import columns, estados, `vencimiento_dias=364`, `vencera_dias=30`, `no_aplica_cargos=['GUARDA','OPERADOR']`, timezone.
- `config/audit.php`: módulo `mt_st_04` → area `gestion_humana`.
- `config/access.php`: ver § Permisos.

## Capas a implementar

- [ ] Migracion(es) `mt_st_04_registros`
- [ ] Modelo `MtSt04Registro` (+ factory)
- [ ] `MtSt04AccessService` + trait `HasMtSt04Tabs`
- [ ] `MtSt04Controller` (+ Form Requests store/update/import)
- [ ] Services: `MtSt04EstadoCalculator`, `MtSt04DatatableService`, `MtSt04ImportService`, `MtSt04DashboardService`, `MtSt04AuditLogService`, sync service
- [ ] Command `mt_st_04:sync-estados` + schedule `bootstrap/app.php`
- [ ] Exports: `MtSt04Export`, `MtSt04ImportTemplateExport`
- [ ] Config: `config/mt_st_04.php` + entradas `access.php` / `audit.php`
- [ ] Vistas Blade bajo `resources/views/areas/gestion_humana/mt_st_04/`
- [ ] JS DataTables server-side + modal CRUD/import (Alpine / patrón módulo)
- [ ] Nav: `NavigationResolver`, `SidebarVisibilityService`, `User`
- [ ] Tests Feature
- [ ] Docs modulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` para ARMA/APTO (SI/NO) y filtros.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- Chrome: `.module-tab`, `req-manage-*`, `.req-manage-filters__icon-btn`, acciones fila `.cursos-catalogo-page__icon-btn`.
- `<x-date-table />` / `DisplayDate` para fechas en tabla.
- PhpSpreadsheet (ya en proyecto).
- `SystemAuditService` (wrapper fino).
- ApexCharts (patrón Formación / dashboards GH).
- Referencias código: `FormacionAccessService`, `AcreditacionesAccessService` / Acreditados DT+CRUD+import upsert, `cursos:sync-estados` / `acreditaciones:sync-estados`, Ficha `EmployeeFichaProfile`.

## UI (primera entrega pulida)

**Vista referencia a clonar:** shell/tabs de **Formación** (`resources/views/areas/gestion_humana/formacion/`) y listado/CRUD/import de **Acreditaciones** (Acreditados) / **Cliente interno** donde aplique modales.

Chrome esperado desde T1 (no “API + Blade mínimo”):

| Pieza | Patrón |
| --- | --- |
| Página | `page-section` + `mt-st-04-page` + `req-manage-page` (matriz) |
| Contenedor | `app-container` + `panel` / `req-manage-shell` |
| Subnav | `.module-tab` en header (Dashboard \| Matriz); padding franja `0.2rem` |
| Filtros | `<details class="req-manage-filters">` + icon-btn canónicos |
| Tabla | `data-table-wrap` + DT server-side; thead azul corporativo `#003366` |
| Selects | Solo `<x-searchable-select>` |
| Modales | Card opaca `#fff` + overlay oscuro (patrón GH) |
| Tokens | `--brand-*` / alturas chrome; sin Select2 ni familias `*__icon-btn` propias |

Tras CSS/JS Vite: `npm run build`. Cierre Feature: smoke Chrome DevTools MCP.

## Documentacion a actualizar

- [x] `docs/modules/mt_st_04.md` (nuevo)
- [x] `docs/user/mt_st_04.md` (nuevo)
- [x] `docs/ACCESS_CONTROL.md`
- [x] `docs/INDEX.md`
- [x] `docs/PROCEDURES.md` (comando schedule / deploy)
- [x] `README.md` (si lista tableros GH) — N/A: README no enumera tableros GH individuales
- [x] Documentador al cierre del flujo

## Archivos compartidos (`shared-files`)

**`shared-files: si`**

| Archivo | Motivo | Task |
| --- | --- | --- |
| `config/access.php` | Board, permisos, tabs, Admin UI | T1 |
| `config/audit.php` | Módulo `mt_st_04` | T1 |
| `routes/areas/gestion_humana.php` | Grupo rutas MT-ST-04 | T1–T4 (secuencial) |
| `routes/web.php` | Solo si faltara require (hoy **ya** incluye GH; no esperado) | — |
| `app/Services/Navigation/NavigationResolver.php` | Entrada sidebar | T1 |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board | T1 |
| `app/Models/User.php` | Default board URL / tabs | T1 |
| Seeders / PermissionCatalog / sync | Registrar permisos Spatie | T1 |
| `bootstrap/app.php` | Schedule `mt_st_04:sync-estados` | **T4** |

T1 es la única Task Card autorizada a tocar `access.php`/nav/audit. T4 toca `bootstrap/app.php` solo para el schedule. T2–T3 no editan `config/access.php` salvo hotfix autorizado por AgentSj.

## Criterios de aceptacion

1. Sidebar GH muestra **MT-ST-04** solo con `view.board.gestion_humana.mt_st_04` (o bypass).
2. Usuario con solo `mt_st_04.view` ve Dashboard + Matriz + export; **403** en CRUD/plantilla/import.
3. Usuario con `mt_st_04.edit` (sin view Spatie) puede ver y mutar (implicación view).
4. Matriz usa DataTables `serverSide: true`; default solo activos ficha; filtro permite desvinculados/todos.
5. Unicidad: no se puede crear segunda fila con misma cédula.
6. Lookup/alta: cédula sin ficha → error claro; con ficha → nombre/cargo/ciudad/puesto readonly.
7. `fecha_vencimiento_*` = examen + 364; no editables en UI ni import.
8. ESTADO1: null sin vencimiento; VENCIDO / VENCERA (≤30d incl.) / VIGENTE con fechas fijas TZ Bogotá en tests.
9. ESTADO2: `GUARDA` y `OPERADOR` (mb_strtoupper exact) → NO APLICA; `GUARDA SJ` no.
10. Guardar e import recalculan estados; comando `mt_st_04:sync-estados` actualiza filas sin edición manual; schedule registrado 06:25 Bogotá.
11. Import upsert por cédula; última duplicada en archivo gana; export incluye estados persistidos.
12. Dashboard: KPIs examen 1 + examen 2 (excl. NO APLICA) + aptos; gráficos visibles; default activos.
13. Selectores `<x-searchable-select>`; sin Select2; sin `excelHtml5`.
14. Auditoría registra mutaciones relevantes / import / export / sync.
15. Migración OK en MySQL local y sqlite tests. **No** `migrate:fresh` / wipe.
16. UI primera entrega alineada a chrome Formación/Acreditaciones (module-tab, req-manage, icon-btn canónicos).

## Validacion local

1. `php artisan migrate` (incremental).
2. `php artisan app:sync-permissions`.
3. Asignar board + view/edit a usuario de prueba; verificar sidebar, tabs y 403s.
4. CRUD con cédula real de Ficha; probar lookup y filtros activo/desvinculado.
5. Casos estado: vigente, vencera (borde día 30), vencido, sin fecha; ESTADO2 NO APLICA GUARDA/OPERADOR vs cargo compuesto.
6. Plantilla → import upsert → export.
7. `php artisan mt_st_04:sync-estados` (`--dry-run` si se implementa).
8. Dashboard metrics con/sin datos.
9. `php artisan test --compact` (suite Feature MT-ST-04).
10. `vendor/bin/pint --dirty --format agent` tras PHP.
11. `npm run build` si hubo CSS/JS Vite.
12. Smoke UI Chrome DevTools MCP (regla proyecto).

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Borde VENCERA inclusivo vs Excel `hoy+30 > venc` | Formalizado aquí como **≤ 30 días**; tests con fechas fijas Bogotá; si Excel operativo diverge un día, ajustar solo con OK usuario |
| CARGO parcial no es NO APLICA | Documentar match exacto; tests; no ampliar con `str_contains` |
| Drift ficha (nombre/cargo) | Lectura **live** en listado/KPIs/job; no snapshot como verdad |
| Cédulas sin ficha en Excel masivo | Rechazar fila; prerequisito operativo Ficha cargada |
| Duplicados en archivo | Última gana + reporte; test |
| Job no programado en deploy | Schedule en `bootstrap/app.php` + docs PROCEDURES / módulo |
| Permisos sin sync a usuarios | Doc Admin: asignar board + view/edit manualmente |
| Volumen + DT client-side | Server-side obligatorio V1 |
| Confusión RETIRADOS | Fuera V1 explícito |
| Desvinculados en KPIs | Default activos (alineado Matriz) |
| Shared-files race GH | Solo T1 `access.php`/nav; T4 schedule; AgentSj coordina |
| Colisión horario schedule | 06:25 vs Cursos 06:15 / Acreditaciones 06:20 |
| Datos | Prohibido fresh/wipe/TRUNCATE operativo |

## Slice de tareas (Task Cards)

| ID | Titulo | Alcance vertical | shared-files |
| --- | --- | --- | --- |
| **T1** | Shell + permisos + migración + nav | `access.php`, audit, rutas shell/redirect/placeholders Dashboard+Matriz, `MtSt04AccessService`, migración/modelo/factory `mt_st_04_registros`, nav User/Resolver/Sidebar, trait tabs, chrome UI shell, tests acceso board/tabs/403 | **si** |
| **T2** | Matriz CRUD + DT + lookup | Calculadora estados (mínimo para persistir al guardar), Datatable service, vista matriz + filtros (activo/desvinculado + operativos), modal CRUD, lookup ficha, Form Requests, export puede quedar stub o completo si cabe — preferencia: CRUD+DT+lookup+tests estados/unicidad/default activos; export full en T3 si pesa | no |
| **T3** | Import / export | Plantilla, `MtSt04ImportService` upsert, `MtSt04Export` + `<x-export-excel>`, reporte errores/duplicados, tests import/export | no |
| **T4** | Dashboard + job estados | `MtSt04DashboardService` + vista KPIs/charts + metrics JSON; comando `mt_st_04:sync-estados` + schedule `bootstrap/app.php`; tests KPIs + sync | **parcial** (`bootstrap/app.php`) |

**Orden:** T1 → T2 → T3 → T4 (secuencial). No combinar T2+T3 (import merece tarjeta propia). No absorber Dashboard/job en T1.

**Nota T2/T3:** Si el export es trivial extender `BaseExport` en T2, AgentSj puede moverlo a T2 y dejar T3 solo import; default arriba mantiene export en T3 junto a plantilla.

## Aprobacion

- [x] Analista — vacíos cerrados ([`FEAT-045-analyst.md`](FEAT-045-analyst.md))
- [x] Arquitecto — brief final
- [ ] Usuario — **confirmacion del brief** (AgentSj debe pausar antes de Feature / plan de orquestación hasta OK explícito)
