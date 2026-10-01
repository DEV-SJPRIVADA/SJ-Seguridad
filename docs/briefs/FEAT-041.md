# Feature Brief — FEAT-041

> Brief final del Arquitecto (2026-10-01). Consolida decisiones cerradas en chat Ask / AgentSj (tablero Formación GH, permisos estilo Cursos, import replace-all, parse fecha ES, DT server-side, Dashboard KPIs v1). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-041 |
| Modulo / area | Gestion humana — tablero **Formación** (`formacion`) |
| Titulo | Tablero Formación (Dashboard + Formaciones: import replace-all) |
| Solicitante | Usuario / AgentSj (chat 2026-10-01 formacion GH) |
| Fecha | 2026-10-01 |

## Objetivo

Dar a Gestión Humana un **tablero dedicado** para consultar y recargar el dataset operativo de **formaciones** (~81k filas desde `FORMACION.xlsx`), con pestañas **Dashboard** (KPIs) y **Formaciones** (listado, filtros, export, plantilla e import que **reemplaza todo** el dataset).

Hoy el proceso vive en Excel. Este módulo lo materializa en la plataforma: operadores con `formacion.view` consultan/filtran/exportan; con `formacion.edit` descargan plantilla e importan un archivo que sustituye por completo los registros previos (con confirmación UI explícita).

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Nombre UI tablero | **Formación** (label exacto). |
| 2 | Board key / hogar | `formacion`; hogar sidebar `gestion_humana`. |
| 3 | Pestañas | Exactas: **Dashboard**, **Formaciones**. |
| 4 | Permisos | Estilo Cursos: `view.board.gestion_humana.formacion` + `formacion.view` + `formacion.edit`. Ver § Permisos. |
| 5 | Dashboard / shell | Sin permiso aparte de dashboard; acceso con board + `formacion.view` (edit implica view). |
| 6 | Implicación | `edit` ⇒ `view` en `FormacionAccessService` (no depender de herencia Spatie). |
| 7 | Catálogos | **Sin** pestaña parámetros / `parameters.edit` / tablas de catálogo. Categoría y curso son texto libre del Excel. |
| 8 | Migración legacy | **Sin** migración automática de permisos viejos. |
| 9 | Formaciones | Listado + filtros + plantilla Excel + import **replace-all** (confirmación UI obligatoria). |
| 10 | Parse fecha | Texto ES `jueves, 4 de junio de 2026, 00:00` → `fecha_inicio`, `mes`, `anio`; aceptar también serial Excel y `Y-m-d`. |
| 11 | Volumen | ~81k filas → DataTables **`serverSide: true`** obligatorio. |
| 12 | Dashboard v1 | KPIs básicos: total registros, distribución por mes, distribución por categoría. |
| 13 | Filtros listado | Año, mes, categoría, curso, número ID, nombre. |
| 14 | Export | Con `formacion.view` vía `BaseExport` + `<x-export-excel>` (no `excelHtml5`). |
| 15 | Selectores | `<x-searchable-select>` (no Select2). |
| 16 | Chrome | `.module-tab`; icon buttons estándar (`.req-manage-filters__icon-btn` / fila si aplica). |
| 17 | Auditoría | `SystemAuditService` vía wrapper módulo. |
| 18 | Migraciones | Multi-driver MySQL + sqlite tests. **Prohibido** `migrate:fresh` / `db:wipe`. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Area GH, board `formacion`, tabs `dashboard` / `formaciones` | Mismo patrón Cursos / Selección / Acreditaciones. |
| Permisos storage | Keys en `system_permissions` + `boards` + `board_canonical_areas` + `formacion_tabs` + Admin subgroup **Formación** | Consistente con tableros GH de área única; labels legibles Admin. |
| Access | `FormacionAccessService`: `canViewBoard`, `canView` (`view`∨`edit`), `canEdit`, `visibleTabsFor`; bypass `manage.users` | Espejo de `CursosAccessService` (sin filtrar tabs por catálogo). |
| Sidebar board | Visible con `view.board.gestion_humana.formacion` (o bypass). Entrar a pestañas: `formacion.view`/`edit` | Decisión cerrada: board separado de funcional. |
| Persistencia | Tabla única **`formacion_registros`** (sin FK a ficha; dataset Excel autónomo) | Fuente es Excel de formaciones, no Ficha; no mezclar con `employee_cursos`. |
| Índices | `anio`, `mes`, `categoria`, `nombre_curso`, `numero_id`; índice compuesto `(anio, mes)` | Soporte filtros DT y KPIs sobre 81k filas. |
| Replace-all | `DB::transaction` → `FormacionRegistro::query()->delete()` → **chunk insert** (p. ej. 500–1000 filas). **Prohibido** `TRUNCATE` como camino principal (compat sqlite/tests + seguridad). | Transacción atómica; delete all + insert es portable MySQL/sqlite. |
| Import UX | Confirmación modal (texto claro: se borrarán todos los registros actuales) antes del POST; respuesta con conteo importados / errores fila | Evita wipe accidental. |
| Parse fechas | Servicio dedicado (método en `FormacionImportService`): (1) serial Excel `Date::excelToDateTimeObject`; (2) `Y-m-d` / Carbon parseable; (3) regex/locale ES quitando día-semana y hora (`jueves, 4 de junio de 2026, 00:00` → date). Derivar `mes`/`anio` de `fecha_inicio`. | Cumple formato real del Excel de referencia. |
| Plantilla | `FormacionImportTemplateExport` (headers exactos Excel) + ruta GET descarga; alineado a `CursosImportTemplateExport` | Misma DX que Cursos. |
| Datatable | `FormacionDatatableService` (query + format row + order/search) estilo Cursos registros | Listado operativo grande. |
| Export datos | `FormacionExport` extends `BaseExport`; columnas = mapeo Excel; botón `<x-export-excel>` | Estándar proyecto. |
| Dashboard | `FormacionDashboardService::metrics()` + endpoint JSON; KPIs: `total`, `por_mes` (1–12 del año filtrado o global), `por_categoria` (top N + resto opcional) | V1 básico; filtros dashboard mínimos: año (recomendado). |
| Controllers | `FormacionController` (shell + dashboard + formaciones + datatable + export + plantilla + import) | Un board, dos pestañas; sin god-split innecesario en V1. |
| Form Requests | `ImportFormacionRequest` (archivo xlsx/xls/csv según soporte PhpSpreadsheet del proyecto); filtros vía Request simple en controller/service | Mutación única = import. |
| Nav | Extender `NavigationResolver`, `SidebarVisibilityService`, `User::defaultFormacionBoardUrl()` + `formacionBoardTabsFor()` | Igual Cursos. |
| Trait tabs | `HasFormacionTabs` (o equivalente) para `.module-tab` Dashboard \| Formaciones | Patrón `HasCursosTabs`. |
| Audit | Entrada `formacion` en `config/audit.php`; wrapper `FormacionAuditLogService` (`module=formacion`, `area=gestion_humana`) | AGENTS.md. |
| Config import | `config/formacion.php` con mapa columnas Excel → keys internas (headers exactos) | Evita hardcode disperso. |
| Rutas | Grupo nuevo en `routes/areas/gestion_humana.php` (ya `require` en `web.php`; **no** hace falta tocar `web.php` salvo que el Feature descubra lo contrario) | Ownership GH. |
| Sync permisos | `app:sync-permissions` / seeder catalog tras keys en `access.php` | Sin migración legacy de asignación a usuarios. |
| Repository | **No**. | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe / TRUNCATE operativo. | Protección de datos. |
| Slice | Task Cards verticales **T1–T4** (ver § Slice de tareas) | Shared-files en T1. |

### Estrategia replace-all (obligatoria)

```text
1. Validar archivo + headers (fallar antes de borrar si faltan columnas).
2. Parsear todas las filas a memoria/colección validada (o staging en chunks con validación previa).
3. Si hay filas inválidas críticas (fecha obligatoria rota, ID vacío): política Feature:
   - Default Arquitecto: **rechazar import completo** si % errores > 0 en columnas obligatorias
     (numero_id, nombre_completo, fecha_inicio, nombre_curso, categoria).
   - Filas vacías: skip sin contar como error.
4. DB::transaction:
     FormacionRegistro::query()->delete();
     insert en chunks (no N+1 Eloquent create por fila; preferir insert() masivo).
5. Audit: action `import_replace` con metadata {deleted_before, imported, skipped_empty, errors_count}.
6. Nunca usar TRUNCATE ni migrate:fresh.
```

**Nota performance:** para ~81k filas, preferir lectura PhpSpreadsheet + `insert` por chunks dentro de la transacción; si el timeout HTTP es riesgo en Hostinger, documentar en T3 un límite práctico (p. ej. `set_time_limit` acotado / mensaje si supera umbral) sin jobs queue en V1 salvo que el Feature encuentre bloqueo duro — default V1 síncrono como Cursos.

### Diagrama de pestañas

```text
[Sidebar GH: Formación] --board--> shell
        │
        ├── Dashboard     (formacion.view)  KPIs + metrics JSON
        └── Formaciones   (formacion.view)  DT + filtros + export
                              │
                              └── edit: plantilla + import replace-all
```

## Alcance

### Incluye

- Tablero sidebar **Formación** en Gestión humana con pestañas **Dashboard** y **Formaciones**.
- Permisos nuevos (board + `formacion.view` + `formacion.edit`) + Admin UI subgroup + sync seeder/`PermissionCatalog`.
- Migración `formacion_registros` multi-driver + modelo `FormacionRegistro` (+ factory para tests).
- `FormacionAccessService` + cableado nav (`NavigationResolver`, `SidebarVisibilityService`, `User`).
- Pestaña Formaciones: DataTables server-side, filtros (año, mes, categoría, curso, número ID, nombre), export Excel.
- Plantilla de importación + import replace-all con confirmación UI + parse fechas ES / Excel / Y-m-d.
- Dashboard v1: total, distribución por mes, por categoría (+ endpoint metrics).
- Auditoría import / export (y opcionalmente acceso shell si el patrón del módulo lo exige).
- Tests PHPUnit Feature (acceso, DT, export gate, import replace-all, parse fechas, KPIs).
- Docs: `docs/modules/formacion.md` + `docs/user/formacion.md` (+ ACCESS / INDEX). Documentador al cierre.

### Fuera de alcance

- CRUD fila a fila (alta/edición/borrado individual) en UI.
- Catálogos / `parameters.edit` / pestaña parámetros.
- Migración legacy de permisos Spatie existentes.
- Vínculo obligatorio a Ficha empleados / `employee_cursos` / Cursos.
- Jobs/colas asíncronas de import (salvo bloqueo duro documentado).
- Dashboard avanzado (tendencias multi-año complejas, drill-down, gráficos Chart.js obligatorios más allá de lo mínimo viable).
- Soft-delete / historial de versiones del dataset (solo replace-all + audit del evento).
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Tablero y acceso

1. Labels exactos: tablero **Formación**; pestañas **Dashboard** / **Formaciones**.
2. Visibilidad sidebar: `view.board.gestion_humana.formacion` (o bypass `manage.users` / super-admin según patrón Cursos).
3. Ver Dashboard o Formaciones: `formacion.view` **o** `formacion.edit` (implicación en AccessService).
4. Plantilla + import: solo `formacion.edit` (o bypass).
5. Export: `formacion.view` (o edit vía implicación).
6. Botones plantilla/import: solo UI con `canEdit`.

### Dataset e import

7. La fuente de verdad operativa es la tabla `formacion_registros` tras el último import exitoso.
8. Un import exitoso **borra todos** los registros previos y carga el archivo nuevo (replace-all).
9. Confirmación UI obligatoria antes de enviar el archivo (mensaje: se eliminarán todos los registros actuales).
10. Columnas obligatorias por fila no vacía: `numero_id`, `nombre_completo`, `fecha_inicio` (parseable), `nombre_curso`, `categoria`. `calificacion` nullable.
11. Filas totalmente vacías: ignorar.
12. Si falla validación de headers o hay errores en filas obligatorias: **no** borrar dataset existente (validar antes del delete).
13. Tras parsear `fecha_inicio`, persistir `mes` (1–12) y `anio` (entero) derivados; no confiar en columnas separadas del Excel (el Excel no las trae).

### Listado y filtros

14. Filtros: `anio`, `mes` (1–12), `categoria` (exacto o searchable-select de valores distintos), `nombre_curso` (select/search), `numero_id` (texto), `nombre` / `nombre_completo` (like).
15. Opciones de categoría/curso/año para selects: derivadas de DISTINCT en BD (o endpoint ligero); searchable-select.
16. DataTables `serverSide: true`; no `@foreach` masivo en Blade.

### Dashboard

17. KPI total = `COUNT(*)` (respetando filtro año si aplica).
18. Distribución por mes = conteos agrupados por `mes` (del año filtrado; default año actual o último año con datos — Feature documenta default).
19. Distribución por categoría = conteos agrupados por `categoria` (ordenar desc; top razonable en UI).

### Auditoría

20. Eventos mínimos: `import_replace`, `export`; metadata con conteos (sin volcar 81k PII al log).
21. Módulo audit `formacion`, area `gestion_humana`.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.formacion` | `super-admin` (todos); asignar Admin | Ver tablero **Formación** en sidebar GH |
| `formacion.view` | Paquete GH / consulta | Formación: Ver Dashboard, listado, filtros y export Excel |
| `formacion.edit` | Paquete GH operativo | Formación: Descargar plantilla e importar (replace-all) |

**Admin UI:** grupo **Gestion humana** → *Ver tableros* incluir `view.board.gestion_humana.formacion`; subgroup **Formación** con `formacion.view`, `formacion.edit`.

**Labels legibles:** `Formación: Ver…`, `Formación: Plantilla e import…` (no exponer keys crudas como label principal).

### Implicaciones en AccessService (obligatorio)

| Permiso otorgado | Efecto runtime |
| --- | --- |
| `formacion.edit` | Puede `formacion.view` aunque Spatie no tenga view marcado |
| `formacion.view` | Dashboard + Formaciones + export; **no** plantilla/import |

### Cambios en `config/access.php`

- `system_permissions`: `formacion.view`, `formacion.edit`.
- `boards`: `'formacion' => 'Formación'`.
- `board_canonical_areas.formacion`: `home => gestion_humana`, `base_area_tab => false`.
- `formacion_tabs`: `dashboard => Dashboard`, `formaciones => Formaciones`.
- Admin `permission_groups` → `gestion_humana` boards + subgroup `formacion`.
- **Sin** migración de datos Spatie legacy.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (ya cargado desde `routes/web.php` dentro de `auth`+`active`). Prefijo propuesto:

| Metodo | URI | Nombre | Notas |
| --- | --- | --- | --- |
| GET | `/gestion-humana/formacion` | `gestion-humana.formacion.index` | Redirect a default tab |
| GET | `/gestion-humana/formacion/dashboard` | `gestion-humana.formacion.dashboard` | Vista KPIs |
| GET | `/gestion-humana/formacion/dashboard/metrics` | `gestion-humana.formacion.dashboard.metrics` | JSON KPIs |
| GET | `/gestion-humana/formacion/formaciones` | `gestion-humana.formacion.formaciones` | Listado |
| GET | `/gestion-humana/formacion/formaciones/datatable` | `gestion-humana.formacion.formaciones.datatable` | DT JSON |
| GET | `/gestion-humana/formacion/formaciones/exportar` | `gestion-humana.formacion.formaciones.export` | Excel datos |
| GET | `/gestion-humana/formacion/formaciones/plantilla-importacion` | `gestion-humana.formacion.formaciones.import-template` | Plantilla |
| POST | `/gestion-humana/formacion/formaciones/importar` | `gestion-humana.formacion.formaciones.import` | Replace-all |
| GET | `/gestion-humana/formacion/formaciones/opciones` | `gestion-humana.formacion.formaciones.options` | (opcional) distincts filtros |

Middleware grupo: `password.changed` (como resto GH). Authorize en controller vía AccessService.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `formacion_registros` | migracion create | Multi-driver; sin FK ficha |

### Columnas `formacion_registros`

| Campo | Tipo sugerido | Notas |
| --- | --- | --- |
| `id` | bigIncrements | PK |
| `numero_id` | string(50) | Excel «Número de ID»; index |
| `nombre_completo` | string(255) | Excel «Nombre completo» |
| `fecha_inicio` | date | Excel «Fecha de inicio del curso» |
| `mes` | unsignedTinyInteger | 1–12 derivado |
| `anio` | unsignedSmallInteger | derivado |
| `nombre_curso` | string(255) | Excel «Nombre completo del curso»; index |
| `calificacion` | string(50) nullable | Excel «Calificación»; texto/número libre |
| `categoria` | string(255) | Excel «Nombre de la categoría»; index |
| `created_at` / `updated_at` | timestamps | |

Índices adicionales: `(anio, mes)`, opcional index en `nombre_completo` si LIKE prefix es frecuente.

**Mapeo Excel → BD**

| Excel | Campo |
| --- | --- |
| Número de ID | `numero_id` |
| Nombre completo | `nombre_completo` |
| Fecha de inicio del curso | `fecha_inicio` (+ `mes`, `anio`) |
| Nombre completo del curso | `nombre_curso` |
| Calificación | `calificacion` (nullable) |
| Nombre de la categoría | `categoria` |

## Capas a implementar

- [ ] Migracion(es) `formacion_registros`
- [ ] Modelo `FormacionRegistro` (+ factory)
- [ ] `FormacionAccessService`
- [ ] `FormacionController`
- [ ] Form Request(s) import
- [ ] Services: `FormacionDatatableService`, `FormacionImportService`, `FormacionDashboardService`, `FormacionAuditLogService`
- [ ] Exports: `FormacionExport`, `FormacionImportTemplateExport`
- [ ] Config: `config/formacion.php` + entradas `access.php` / `audit.php`
- [ ] Vistas Blade bajo `resources/views/areas/gestion_humana/formacion/`
- [ ] JS DataTables server-side + confirmación import (Alpine / patrón módulo)
- [ ] Nav: `NavigationResolver`, `SidebarVisibilityService`, `User`
- [ ] Trait tabs chrome
- [ ] Tests Feature
- [ ] Docs modulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` para filtros año/mes/categoría/curso.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- Chrome `.module-tab` + icon buttons estándar.
- PhpSpreadsheet (ya en proyecto) para plantilla/import.
- `SystemAuditService` (wrapper fino).
- Patrón referencia: `CursosAccessService`, `EmployeeCursoImportService`, `CursosImportTemplateExport`, `CursosController`, `EmployeeCursoDatatableService`.

## Documentacion a actualizar

- [ ] `docs/modules/formacion.md` (nuevo)
- [ ] `docs/user/formacion.md` (nuevo)
- [ ] `docs/ACCESS_CONTROL.md`
- [ ] `docs/INDEX.md`
- [ ] `README.md` (si lista tableros GH)
- [ ] Documentador al cierre del flujo

## Archivos compartidos (`shared-files`)

**`shared-files: si`**

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, permisos, tabs, Admin UI |
| `config/audit.php` | Módulo `formacion` |
| `routes/areas/gestion_humana.php` | Grupo rutas Formación |
| `routes/web.php` | Solo si faltara require (hoy **ya** incluye GH; no esperado) |
| `app/Services/Navigation/NavigationResolver.php` | Entrada sidebar |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad board |
| `app/Models/User.php` | `defaultFormacionBoardUrl` / tabs |
| Seeders / PermissionCatalog / sync | Registrar permisos Spatie |

T1 es la única Task Card autorizada a tocar shared-files de permisos/nav. T2–T4 no editan `config/access.php` salvo hotfix autorizado por AgentSj.

## Criterios de aceptacion

1. Sidebar GH muestra **Formación** solo con `view.board.gestion_humana.formacion` (o bypass).
2. Usuario con solo `formacion.view` ve Dashboard + Formaciones + export; **403** en plantilla/import.
3. Usuario con `formacion.edit` (sin view Spatie) puede ver e importar (implicación view).
4. Formaciones usa DataTables `serverSide: true` y responde en tiempo razonable con decenas de miles de filas (paginado).
5. Filtros año/mes/categoría/curso/número ID/nombre aplican en DT y export respeta filtros activos.
6. Plantilla descarga headers exactos del mapeo Excel.
7. Import con confirmación: tras éxito, el conteo en BD = filas válidas del archivo; dataset previo eliminado.
8. Import con headers inválidos o filas obligatorias rotas: dataset previo **intacta**.
9. Fecha texto ES `jueves, 4 de junio de 2026, 00:00` produce `fecha_inicio=2026-06-04`, `mes=6`, `anio=2026`; serial Excel y `Y-m-d` también.
10. Dashboard muestra total + distribución por mes + por categoría.
11. Selectores con `<x-searchable-select>`; sin Select2; sin `excelHtml5`.
12. Auditoría registra import replace y export.
13. Migración pasa en MySQL local y en tests sqlite (`RefreshDatabase`).
14. **No** se ejecuta `migrate:fresh` / `db:wipe` / TRUNCATE operativo.

## Validacion local

1. `php artisan migrate` (incremental).
2. `php artisan app:sync-permissions` (o flujo seeder del proyecto).
3. Asignar board + view/edit a usuario de prueba; verificar sidebar y 403s.
4. Cargar plantilla → rellenar pocas filas → import → verificar replace.
5. Probar parse con fila fecha ES y con fecha Excel nativa.
6. DT + filtros + export.
7. Dashboard metrics con/sin datos.
8. `php artisan test --compact` (suite Feature Formación + regresión permisos GH si aplica).
9. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Import 81k filas: timeout / memoria PHP en Hostinger | Chunks + validar antes de delete; elevar límites solo en request import; medir en T3; si falla, documentar límite o diferir a job (fuera V1 salvo bloqueo). |
| Replace-all destructivo accidental | Confirmación UI + validación previa a delete + audit. |
| Parse fechas ES frágil (acentos, mayúsculas, variantes) | Tests unitarios/feature con corpus de ejemplos; normalizar `mb_strtolower` + mapa meses ES. |
| DISTINCT 81k para selects de filtro | Cachear opciones en request o endpoint con query indexada; no cargar 81k options al cliente. |
| Colisión conceptual con tablero **Cursos** | Naming UI **Formación**; tabla/permisos `formacion_*`; docs aclaran dataset distinto. |
| Shared-files race con otras features GH | Solo T1 toca `access.php`/nav; AgentSj coordina. |
| Transacción larga bloquea tabla | Aceptable V1 (import poco frecuente); comunicar a usuarios no usar listado durante import. |

## Slice de tareas (Task Cards)

| ID | Titulo | Alcance vertical | shared-files |
| --- | --- | --- | --- |
| **T1** | Shell + permisos + migración + modelo + nav | `access.php`, audit, rutas shell/redirect, `FormacionAccessService`, migración/modelo/factory, nav User/Resolver/Sidebar, vistas shell + tabs vacíos/placeholders, tests acceso board/tabs | **si** |
| **T2** | Formaciones listado / filtros / DT / export | Datatable service, vista formaciones, filtros searchable-select, export `BaseExport` + botón, tests DT/filtros/export/403 | no |
| **T3** | Import replace-all + plantilla + parse fechas | `FormacionImportService`, template export, Form Request, UI confirmación, config columnas, tests parse ES/Excel/Y-m-d + replace-all + no-wipe-on-error | no |
| **T4** | Dashboard KPIs | `FormacionDashboardService`, vista dashboard, metrics JSON, tests KPIs | no |

**Orden:** T1 → T2 → T3 → T4 (T4 puede paralelizarse tras T1 si AgentSj lo permite; recomendado tras T2 por datos reales de filtros año).

No combinar T2+T3: import replace-all merece tarjeta y tests propios. No absorber Dashboard en T1 (KPIs dependen de datos/filtros).

## Aprobacion

- [x] Analista — vacíos cerrados (skip: decisiones usuario en chat Ask)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion (implícita vía «implementa» / decisiones cerradas; AgentSj puede pedir OK explícito antes de Feature)
