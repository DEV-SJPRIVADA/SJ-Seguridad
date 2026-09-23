# Feature Brief — FEAT-036

> Brief final del Arquitecto (2026-09-23). Consolida `docs/briefs/FEAT-036-analyst.md` + respuestas usuario 1–5. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-036 |
| Modulo / area | Gestion humana — tablero **Acreditaciones** (`acreditaciones`) |
| Titulo | Tablero Acreditaciones GH — Fase 1: shell + Acreditados + Catálogo cargos |
| Solicitante | Usuario / AgentSj (chat 2026-09-23) |
| Fecha | 2026-09-23 |

## Objetivo

Dar a Gestión Humana un **tablero operativo Acreditaciones** para controlar personal acreditado (vigencia, solicitud en trámite, estados automáticos) y administrar el **catálogo de cargos** (Manager / APO / Informe / Acreditación).

Hoy no existe board, permisos, tablas ni docs de Acreditaciones. El módulo sigue el patrón de área única GH (Cursos / Selección): vertical slice, permisos board+view+edit, DataTables server-side, import/export Excel, audit via `SystemAuditService`.

**Fase 1** entrega shell del tablero + pestaña **Acreditados** funcional + **Catálogo** (seed + CRUD). Dashboard, Reporte Diario, Validaciones y Export Apo quedan como placeholders.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | UI shell | Tablero GH `acreditaciones` label **Acreditaciones**; pestañas **Dashboard**, **Acreditados**, **Reporte Diario**, **Validaciones**, **Export Apo**, **Catálogo**. |
| 2 | Fase 1 | Shell + Acreditados (CRUD/import/export/filtros/estados) + Catálogo cargos (seed 17 + CRUD). Resto = placeholder («Próximamente» / vacío controlado). |
| 3 | Campos Acreditados | CEDULA, NOMBRE COMPLETO, CARGO, VIGEN.ACR, ESTADO, OBSERVACIONES, FECHA SOLICITUD; **tipo** = **CARGO APO**. |
| 4 | VIGEN.ACR | Fecha de **vencimiento** de la acreditación (`date`). |
| 5 | CARGO | Texto del **Excel / formulario**; **no** se deriva del catálogo en fase 1. |
| 6 | Tipo / unicidad | Tipo = valor **CARGO APO** (ej. VIGILANTE, ESCOLTA). Unique **cédula + CARGO APO**. Re-import → **upsert**. |
| 7 | Estados (solo calculados) | `ACREDITADO`, `EN_PROCESO`, `POR_VENCER`, `DESACREDITADO`. Labels UI: ACREDITADO, EN PROCESO, POR VENCER, DESACREDITADO. |
| 8 | Prioridad estados | 1) Si hay FECHA SOLICITUD → EN PROCESO; 2) Si VIGEN.ACR ≤ hoy → DESACREDITADO; 3) Si VIGEN.ACR ≤ hoy+21 → POR VENCER; 4) Else → ACREDITADO. |
| 9 | Ficha | Cédula **obligatoria** en `employee_ficha_profiles`; si no existe → falla. NOMBRE COMPLETO **siempre** desde Ficha (ignorar Excel). |
| 10 | Ops Acreditados | CRUD manual; plantilla vacía + re-import; export filtrado; filtros estado/cédula/cargo/fechas; DataTables server-side; `<x-searchable-select>`. |
| 11 | Catálogo | Columnas CARGO MANAGER \| CARGO APO \| CARGO INFORME \| CARGO ACREDITACION; seed 17 filas; **CRUD** completo en fase 1. |
| 12 | Permisos | `view.board.gestion_humana.acreditaciones`, `acreditaciones.view`, `acreditaciones.edit`. Sin paquete default a `administrador`/`usuario`. Bypass `manage.users` / super-admin. |
| 13 | Audit | Wrapper → `SystemAuditService` (`module=acreditaciones`, `area=gestion_humana`), solo mutaciones. |
| 14 | Datos | Solo migraciones aditivas. **Prohibido** `migrate:fresh` / wipe / TRUNCATE. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modulo | Board key `acreditaciones`, label **Acreditaciones**; hogar `gestion_humana`, `base_area_tab => false` | Patrón Cursos / Selección. |
| Tabla operativa | `acreditacion_acreditados` | Prefijo de dominio; evita colisión con nombre genérico. |
| Tabla catálogo | `acreditacion_cargos` | Tabla propia (no `payroll_catalog_items`): 4 columnas de negocio distintas al patrón code/name. |
| Persistencia del **tipo** | Columna `cargo_apo` **string** (texto normalizado), **sin FK** al id del catálogo | Varias filas del catálogo comparten el mismo CARGO APO; la unicidad de negocio es cédula + valor APO, no id de fila Manager. |
| Validación tipo | Al create/update/import: existe ≥1 fila activa en `acreditacion_cargos` con el mismo `cargo_apo` (comparación trim + case-insensitive según normalización). | Evita tipos inventados; no acopla a un id Manager concreto. |
| Unique BD | `unique(document_number, cargo_apo)` | Upsert determinista. |
| Nombre | Snapshot `full_name` desde Ficha al persistir (como Cursos); Excel se ignora | Listados rápidos sin join obligatorio; re-sync al guardar/import. |
| Fechas ambas vacías | **Rechazar** (422 / fila falla en import): exigir **al menos una** de `vigencia_acr` o `fecha_solicitud` | Sin fechas no hay regla de estado aplicable; no inventar estado. |
| Solo `fecha_solicitud` | Permitido → estado `EN_PROCESO`; `vigencia_acr` nullable | Prioridad 1 cubre el caso. |
| Solo `vigencia_acr` | Permitido → calcular DESACREDITADO / POR_VENCER / ACREDITADO | Prioridad 2–4. |
| Ambas presentes | Prioridad 1 gana (`EN_PROCESO`) aunque vigencia esté vencida | Ley de negocio confirmada. |
| Ventana POR VENCER | `vigencia_acr <= hoy + 21` días calendario (inclusive), timezone app (`America/Bogota`) | Analyst + Cursos. |
| ESTADO editable | **No.** Solo service calculator + persistencia en columna `estado` | Filtros/export indexados; no input UI. |
| Sync diario | **Incluir** comando `acreditaciones:sync-estados` + schedule diario (~06:20 Bogota, offset vs Cursos 06:15) | POR_VENCER / DESACREDITADO cambian con el calendario sin mutación de usuario (patrón `cursos:sync-estados`). |
| Recalc inmediato | Al store/update/import (y al sync diario) | Consistencia operativa. |
| Access | `AcreditacionesAccessService` + trait `HasAcreditacionesTabs` | Espejo Cursos/Selección. |
| Catálogo en subnav | Visible solo con `acreditaciones.edit` (o bypass) | Igual Selección Catálogos. |
| Placeholders | GET shell por pestaña; sin endpoints de negocio ni KPIs | Fase 1 acotada. |
| Selectores | `<x-searchable-select>` en filtros/forms. **Prohibido Select2.** | AGENTS.md. |
| Listados | DataTables `serverSide: true` + service dedicado | Regla datatables-server-side. |
| Export | `BaseExport` + `<x-export-excel>`; export respeta filtros activos | AGENTS.md. |
| Import | Plantilla vacía (headers) + servicio upsert; reporte de fallos con token temporal (patrón Cursos/Ficha) | Pedido fase 1. |
| Borrado | **Duro** (DELETE físico) con confirmación UI | Sin soft-delete en alcance. |
| Repository | **No.** | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental. | Protección de datos. |
| Delete/rename APO en catálogo | DELETE de fila: bloquear si es la **última** fila activa con ese `cargo_apo` y existen acreditados con ese tipo. UPDATE de `cargo_apo`: bloquear si el valor **anterior** está referenciado y el nuevo no es un simple no-op; preferir editar Manager/Informe/Acreditación libremente. | Evita huérfanos en unique key textual. |

### Nombres tecnicos de estado (almacenados)

| Code BD | Label UI |
| --- | --- |
| `ACREDITADO` | ACREDITADO |
| `EN_PROCESO` | EN PROCESO |
| `POR_VENCER` | POR VENCER |
| `DESACREDITADO` | DESACREDITADO |

### Seed catálogo cargos (17 filas, upsert no destructivo)

| cargo_manager | cargo_apo | cargo_informe | cargo_acreditacion |
| --- | --- | --- | --- |
| GUARDA | VIGILANTE | GUARDAS | 1 |
| GUARDA MENSUAL | VIGILANTE | GUARDAS | 1 |
| GUARDA SJ | VIGILANTE | GUARDAS | 1 |
| COORDINADOR CARTAGENA | ESCOLTA | COORDINADORES | 2 |
| COORDINADOR DE OPERACIONES | ESCOLTA | COORDINADORES | 2 |
| COORDINADORES | ESCOLTA | COORDINADORES | 2 |
| COORDINADORES MENSUAL | ESCOLTA | COORDINADORES | 2 |
| DIRECTOR NACIONAL OPERACIONES Y GESTION DE RIESGOS | ESCOLTA | DIRECTOR NACIONAL | 2 |
| ESCOLTA | ESCOLTA | ESCOLTA | 2 |
| ESCOLTA MENSUAL | ESCOLTA | ESCOLTA | 2 |
| JEFE DE SEGURIDAD | ESCOLTA | JEFES DE SEGURIDAD | 2 |
| SUPERVISOR SJ | SUPERVISOR | SUPERVISORES | 4 |
| SUPERVISORES | SUPERVISOR | SUPERVISORES | 4 |
| SUPERVISORES MENSUAL | SUPERVISOR | SUPERVISORES | 4 |
| OPERADOR | OPERADOR DE MEDIOS TECNOLOGICOS | OMT(OPERADOR) | 5 |
| OPERADOR SJ | OPERADOR DE MEDIOS TECNOLOGICOS | OMT(OPERADOR) | 5 |
| GUARDA MANEJADOR CANINO | MANEJADOR CANINO | MANEJADOR CANINO | 6 |

Upsert key sugerida: `(cargo_manager, cargo_apo)` (unique compuesto en catálogo) para re-seed idempotente.

## Alcance

### Incluye

- Tablero sidebar **Acreditaciones** en Gestión humana.
- Permisos + Admin UI (`admin_permission_groups`) + sync `PermissionCatalog` / `app:sync-permissions`.
- Shell de pestañas `.module-tab` + subnav.
- Placeholders: Dashboard, Reporte Diario, Validaciones, Export Apo.
- Pestaña **Acreditados**: DataTables server-side, CRUD, filtros, plantilla/import upsert, export Excel, estados calculados, sync diario.
- Pestaña **Catálogo**: listado + CRUD de `acreditacion_cargos` + seed 17.
- Auditoría de mutaciones (acreditados + catálogo + import resumen).
- Tests PHPUnit (permisos, CRUD, unicidad, estados, import, export, catálogo, sync).
- Docs: `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` (+ INDEX / ACCESS_CONTROL / ARCHITECTURE). Documentador al cierre.

### Fuera de alcance (fase 1)

- Lógica de negocio de Dashboard, Reporte Diario, Validaciones, Export Apo (solo shell).
- Notificaciones por correo.
- Soft-delete / historial versionado.
- Bridge editable desde Ficha / sync automático Ficha→Acreditados (más allá de resolver nombre por cédula).
- Derivar CARGO del catálogo.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Tablero y acceso

1. Etiquetas: tablero **Acreditaciones**; pestañas según tabla de decisión.
2. Sidebar: `view.board.gestion_humana.acreditaciones`. Bypass: `manage.users`.
3. Consultar listados / export / placeholders: `acreditaciones.view` (o `acreditaciones.edit` o bypass).
4. Mutaciones Acreditados (CRUD, import) y Catálogo: `acreditaciones.edit` (o bypass).
5. Usuario solo `acreditaciones.view` (+ board): ve pestañas operativas/placeholders y export; **sin** botones mutación ni pestaña Catálogo.
6. Catálogo visible en subnav solo si `acreditaciones.edit`.

### Acreditados (`acreditacion_acreditados`)

7. Campos UI: CEDULA (`document_number`), NOMBRE COMPLETO (solo lectura desde Ficha), CARGO (`cargo` texto), CARGO APO / tipo (`cargo_apo`), VIGEN.ACR (`vigencia_acr`), ESTADO (solo lectura calculado), OBSERVACIONES, FECHA SOLICITUD (`fecha_solicitud`).
8. Unicidad: un registro por `(document_number, cargo_apo)`.
9. Create/update/import: cédula debe existir en `employee_ficha_profiles`; si no → error. `full_name` = `EmployeeFichaProfile.full_name`.
10. `cargo_apo` debe existir como valor activo en catálogo (ver decisión técnica).
11. Al menos una fecha: `vigencia_acr` **o** `fecha_solicitud` (ambas vacías → rechazar).
12. ESTADO: recalcular con `AcreditacionEstadoCalculator` (o nombre equivalente) y persistir; **no** aceptar override del cliente.
13. Import: misma cédula+`cargo_apo` → **update** in-place (upsert); columnas de plantilla sin ESTADO.
14. Eliminar: confirmación UI; DELETE físico.
15. Filtros: estado, cédula (parcial), cargo (texto), rango fechas (por `vigencia_acr` y/o `fecha_solicitud` — documentar en UI: default filtrar por vigencia; permitir toggle o dos rangos si el Form Request lo soporta; mínimo viable: rango sobre `vigencia_acr` + filtro opcional «con fecha solicitud»).

### Catálogo (`acreditacion_cargos`)

16. CRUD de las 4 columnas; `is_active` + `sort_order` recomendados (patrón catálogos GH).
17. Seed 17 filas idempotente.
18. Reglas de borrado/rename APO según decisión técnica (proteger referencias de acreditados).

### Placeholders

19. Dashboard / Reporte Diario / Validaciones / Export Apo: vista con mensaje «Próximamente» (o vacío controlado); sin JSON de métricas ni mutaciones.

### Auditoria

20. Eventos: `acreditacion_acreditado` create/update/delete; `acreditacion_cargo` create/update/delete; `acreditacion_import` (resumen: ok/fail counts; sin volcado PII completo).
21. No auditar GET/list/datatable/export/sync-diario masivo fila a fila (el comando puede loggear un evento agregado opcional; no obligatorio en V1).

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.acreditaciones` | `super-admin` (todos); resto Manual en Admin | Ver tablero **Acreditaciones** en sidebar GH |
| `acreditaciones.view` | Paquete consulta / completo (manual) | Shell, Acreditados (lectura), export, placeholders |
| `acreditaciones.edit` | Paquete completo (manual) | CRUD Acreditados, import, Catálogo |

**Paquetes recomendados:**

| Perfil | Permisos |
| --- | --- |
| Solo consulta | board + `acreditaciones.view` |
| Operativo GH | board + `acreditaciones.view` + `acreditaciones.edit` |

No crear roles nuevos. Bypass runtime: `manage.users` en `AcreditacionesAccessService`.

### Cambios en `config/access.php`

- `system_permissions`: `acreditaciones.view`, `acreditaciones.edit`.
- `boards`: `'acreditaciones' => 'Acreditaciones'`.
- `board_canonical_areas`: `acreditaciones` → `home => gestion_humana`, `base_area_tab => false`.
- `acreditaciones_tabs`:
  - `dashboard` => Dashboard
  - `acreditados` => Acreditados
  - `reporte_diario` => Reporte Diario
  - `validaciones` => Validaciones
  - `export_apo` => Export Apo
  - `catalogo` => Catálogo
- `admin_permission_groups` → `other_areas.gestion_humana`:
  - boards: agregar `view.board.gestion_humana.acreditaciones`
  - subgroup `acreditaciones`: `acreditaciones.view`, `acreditaciones.edit`
- Generación `view.board.*` vía `PermissionCatalog`.

### Seeders / sync

- `app:sync-permissions`: crea permisos; `super-admin` recibe todos.
- Rol `administrador` y `usuario`: **no** incluir paquete Acreditaciones por defecto.
- Tras deploy: `php artisan app:sync-permissions` + re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo nuevo en el mismo archivo; **no** incluir dedicado salvo que el Feature agent encuentre el archivo ya demasiado largo — preferencia Arquitecto: **mismo archivo**, patrón Cursos/Selección). **No** editar `routes/web.php` salvo el require ya existente del área.

Prefijo: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `acreditados` (o dashboard placeholder). `acreditaciones.view` |
| GET | `/dashboard` | `dashboard` | Placeholder. `acreditaciones.view` |
| GET | `/acreditados` | `acreditados` | Shell listado. `acreditaciones.view` |
| GET | `/acreditados/datatable` | `acreditados.datatable` | JSON DataTables. `acreditaciones.view` |
| GET | `/acreditados/exportar` | `acreditados.export` | Excel filtrado. `acreditaciones.view` |
| GET | `/acreditados/plantilla-importacion` | `acreditados.import-template` | Plantilla vacía. `acreditaciones.edit` |
| POST | `/acreditados/importar` | `acreditados.import` | Upsert masivo. `acreditaciones.edit` |
| GET | `/acreditados/importar/reporte/{token}` | `acreditados.import-report` | Fallos (~1 h). `acreditaciones.edit` |
| POST | `/acreditados` | `acreditados.store` | Crear. `acreditaciones.edit` |
| PATCH | `/acreditados/{acreditacionAcreditado}` | `acreditados.update` | Editar. `acreditaciones.edit` |
| DELETE | `/acreditados/{acreditacionAcreditado}` | `acreditados.destroy` | Borrado duro. `acreditaciones.edit` |
| GET | `/reporte-diario` | `reporte-diario` | Placeholder. `acreditaciones.view` |
| GET | `/validaciones` | `validaciones` | Placeholder. `acreditaciones.view` |
| GET | `/export-apo` | `export-apo` | Placeholder (≠ export listado). `acreditaciones.view` |
| GET | `/catalogo` | `catalogo` | Listado catálogo. `acreditaciones.edit` |
| POST | `/catalogo` | `catalogo.store` | Crear. `acreditaciones.edit` |
| PATCH | `/catalogo/{acreditacionCargo}` | `catalogo.update` | Editar. `acreditaciones.edit` |
| DELETE | `/catalogo/{acreditacionCargo}` | `catalogo.destroy` | Eliminar si reglas OK. `acreditaciones.edit` |

Middleware grupo: `auth`, `active`, `password.changed` (igual resto GH).

### Columnas plantilla / import Excel

Fila 1 claves técnicas (config), fila 2 labels, datos desde fila 3 (patrón Cursos):

| Clave | Label | Persistencia |
| --- | --- | --- |
| `document_number` | CEDULA | Sí; required; debe existir en Ficha |
| `full_name` | NOMBRE COMPLETO | Ignorado (solo ayuda humana en plantilla) |
| `cargo` | CARGO | Sí; texto Excel |
| `cargo_apo` | CARGO APO | Sí; tipo / unique key |
| `vigencia_acr` | VIGEN.ACR | Sí; date; opcional si hay fecha_solicitud |
| `fecha_solicitud` | FECHA SOLICITUD | Sí; date; opcional si hay vigencia_acr |
| `observaciones` | OBSERVACIONES | Sí; nullable |

**No** incluir columna ESTADO en plantilla.

## Base de datos

Solo migración(es) **nuevas** + seed upsert. Prohibido `migrate:fresh`.

### Config

- `config/acreditaciones.php` (nuevo): tabs labels, `import.columns`, ventana 21 días, estados, límites validación.
- `config/audit.php`: módulo `acreditaciones` → area `gestion_humana`.
- `config/access.php`: ver sección permisos.

### Tabla `acreditacion_cargos`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `cargo_manager` | string(255) | CARGO MANAGER |
| `cargo_apo` | string(255) | CARGO APO; index |
| `cargo_informe` | string(255) | CARGO INFORME |
| `cargo_acreditacion` | string(20) | CARGO ACREDITACION (códigos 1,2,4,5,6…) |
| `is_active` | boolean default true | |
| `sort_order` | unsigned int default 0 | |
| `created_at` / `updated_at` | timestamps | |

Unique: `(cargo_manager, cargo_apo)` — `acreditacion_cargos_manager_apo_unique`.  
Index: `(cargo_apo)`, `(is_active)`.

### Tabla `acreditacion_acreditados`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | Snapshot desde Ficha |
| `cargo` | string(255) | Texto Excel/form (no derivado catálogo) |
| `cargo_apo` | string(255) | Tipo (= CARGO APO); parte del unique |
| `vigencia_acr` | date nullable | VIGEN.ACR (vencimiento) |
| `fecha_solicitud` | date nullable | FECHA SOLICITUD |
| `estado` | string(30) | Calculado; index |
| `observaciones` | text nullable | |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |
| `created_at` / `updated_at` | timestamps | |

Unique: `(document_number, cargo_apo)` — `acreditacion_acreditados_cedula_apo_unique`.  
Indexes: `(estado)`, `(vigencia_acr)`, `(fecha_solicitud)`, `(cargo)`.  
Check lógico app-level: al menos una de `vigencia_acr` / `fecha_solicitud` (MySQL check opcional; no obligatorio).

**Sin FK formal** a `employee_ficha_profiles` ni a `acreditacion_cargos.id` (enlace lógico por `document_number` / valor `cargo_apo`, patrón Cursos).

## Capas a implementar

- [ ] Migracion(es) — `acreditacion_cargos`, `acreditacion_acreditados`
- [ ] Seeder — 17 cargos (upsert)
- [ ] Modelo(s) — `AcreditacionCargo`, `AcreditacionAcreditado` (+ factories)
- [ ] Config — `config/acreditaciones.php`; `access.php`; `audit.php`
- [ ] Access — `AcreditacionesAccessService` + `HasAcreditacionesTabs`
- [ ] Audit — `AcreditacionesAuditLogService`
- [ ] Services — `AcreditacionEstadoCalculator`; `AcreditacionAcreditadoDatatableService`; `AcreditacionImportService`; sync estados
- [ ] Command — `acreditaciones:sync-estados` + registro en schedule
- [ ] Controlador(es) — `AcreditacionesController` (+ opcional split `AcreditacionesCatalogController`)
- [ ] Form Request(s) — store/update acreditado; import; catalog store/update; filtros datatable
- [ ] Export Excel — clase `BaseExport` acreditados + `<x-export-excel>`
- [ ] Vista(s) Blade — shell tabs + acreditados + catalogo + placeholders
- [ ] JavaScript — DataTables server-side; filtros Alpine/`<x-searchable-select>`
- [ ] Nav — `NavigationResolver`, `SidebarVisibilityService`, `User` default board URL si aplica
- [ ] Tests — Feature suite Acreditaciones
- [ ] Docs modulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` — filtros (estado, cargo_apo) y forms.
- `<x-export-excel>` + `App\Exports\BaseExport`.
- DataTables server-side (referencia `EmployeeCursoDatatableService` / Selección).
- Patrón import plantilla + reporte token (Cursos/Ficha).
- `SystemAuditService` vía wrapper módulo.
- Chrome `.module-tab` / subnav (nav-chrome-ui).

## Documentacion a actualizar

- [ ] `docs/modules/acreditaciones.md` (nuevo)
- [ ] `docs/user/acreditaciones.md` (nuevo)
- [ ] `docs/INDEX.md`
- [ ] `docs/ACCESS_CONTROL.md` — permisos nuevos
- [ ] `docs/ARCHITECTURE.md` — fila ownership `acreditaciones`
- [ ] `README.md` (si lista módulos GH)

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Permisos, board, tabs, admin groups, canonical area |
| `routes/areas/gestion_humana.php` | Grupo rutas Acreditaciones |
| `config/audit.php` | Módulo `acreditaciones` |
| Nav shared | `NavigationResolver`, `SidebarVisibilityService`, `User` (default board URL si aplica) |
| Schedule | `routes/console.php` o Kernel schedule — comando sync estados |
| Seeders / sync | `PermissionCatalog` / `app:sync-permissions`; seeder cargos |

**Flag en `docs/TASKS.md`:** `shared-files` = sí (lista anterior). Un solo Feature agent por slice que toque shared; no paralelizar T1 con otros editores de `access.php`.

## Criterios de aceptacion

1. Con board + `acreditaciones.view`, el usuario ve **Acreditaciones** en sidebar GH y pestañas Dashboard / Acreditados / Reporte Diario / Validaciones / Export Apo; **no** ve Catálogo ni botones mutación.
2. Con `acreditaciones.edit`, ve Catálogo y puede CRUD Acreditados + import + CRUD cargos.
3. Sin permisos (ni bypass): 403 en todas las rutas del grupo.
4. `manage.users` bypassa board/view/edit como Cursos.
5. Rol `usuario` / `administrador` **no** reciben permisos Acreditaciones al hacer `app:sync-permissions` (salvo asignación manual previa).
6. Placeholders responden 200 con UI «Próximamente» (sin endpoints de negocio).
7. Crear acreditado con cédula existente en Ficha + `cargo_apo` válido + al menos una fecha → persiste; `full_name` = Ficha; `estado` calculado.
8. Cédula ausente en Ficha → 422 / fila import falla.
9. Ambas fechas vacías → 422 / fila import falla.
10. Misma cédula + mismo `cargo_apo` en import → **actualiza** fila existente (upsert), no duplica.
11. Prioridad estados: con `fecha_solicitud` → `EN_PROCESO` aunque `vigencia_acr` vencida; sin solicitud y vigencia ≤ hoy → `DESACREDITADO`; ≤ hoy+21 → `POR_VENCER`; else `ACREDITADO`.
12. ESTADO no es editable en UI ni aceptado desde request/import.
13. DataTables Acreditados: server-side; filtros estado/cédula/cargo/fechas afectan JSON y export.
14. Export Excel usa `BaseExport` / `<x-export-excel>`; auth `acreditaciones.view`.
15. Plantilla import descargable; import con reporte de fallos.
16. Catálogo: seed 17 filas; CRUD; delete/rename APO protegido si hay acreditados dependientes.
17. Comando `acreditaciones:sync-estados` recalcula estados; schedule diario registrado.
18. Eventos audit en create/update/delete de acreditados y catálogo (+ resumen import).
19. Selectores usan `<x-searchable-select>`; sin Select2.
20. Solo `php artisan migrate` (sin fresh); datos existentes intactos.
21. Tests Feature verdes para permisos, CRUD, unicidad/upsert, estados, fechas vacías, Ficha obligatoria, import, export, catálogo, sync.

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan db:seed --class=…AcreditacionCargoSeeder` (o seeder incluido) + `php artisan app:sync-permissions`.
2. Asignar manualmente board+view(+edit) a usuario de prueba GH; verificar sidebar y pestañas.
3. Seed catálogo visible; CRUD una fila de prueba.
4. CRUD Acreditados con cédula real de Ficha; probar filtros, export, plantilla e import upsert.
5. Casos estado: solicitud, vencido, por vencer (≤21), acreditado; ambas fechas vacías rechazado.
6. `php artisan acreditaciones:sync-estados` (dry-run si se implementa, o ejecución real en local).
7. `php artisan test --compact --filter=Acreditacion` (o path `tests/Feature/GestionHumana/Acreditacion*`).
8. `npm run build` solo si hay entry JS nueva (avisar al usuario si Vite manifest falta).

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Shared-files (`access.php`, nav, rutas GH, schedule) | Un agente a la vez; Task Card T1 primero; flag en TASKS. |
| Cédulas no cargadas en Ficha | Import fallará masivamente hasta tener Ficha; documentar prerequisito operativo. |
| Tipo como texto (sin FK) | Validar existencia en catálogo; proteger rename/delete APO referenciado. |
| Drift `full_name` vs Ficha | Snapshot al guardar/import; sync diario **no** obliga refrescar nombres (opcional futuro); fase 1 aceptable. |
| Ambigüedad filtro fechas | Criterio mínimo: rango sobre `vigencia_acr`; documentar en UI. |
| Colisión schedule con Cursos | Offset horario (06:20 vs 06:15). |
| Volumen listados | Server-side obligatorio desde V1. |
| Datos | Prohibido fresh/wipe; seed solo upsert. |
| Pestaña «Export Apo» vs export Excel Acreditados | Nombres distintos; placeholder no implementa export APO. |

### Riesgos abiertos (ninguno bloqueante)

Ningún vacío de negocio pendiente. Decisiones Arquitecto fijadas arriba (fechas vacías = rechazar; tipo = texto `cargo_apo`; sync diario = incluir).

## Sugerencia de Task Cards (para plan AgentSj)

| Task | Scope | Notas |
| --- | --- | --- |
| **T1** | Accesos / nav / shell | `access.php`, permisos, `AcreditacionesAccessService`, tabs, rutas index + placeholders + shell Acreditados/Catálogo vacío, NavigationResolver/Sidebar/User, `config/audit.php`, tests permisos/403. **shared-files.** |
| **T2** | Catálogo + seed | Migración/modelo `acreditacion_cargos`; seeder 17; UI CRUD; Form Requests; reglas delete/rename; tests seed + CRUD. |
| **T3** | Acreditados core | Migración/modelo `acreditacion_acreditados`; calculator estados; datatable; CRUD; filtros; export; audit; tests estados/Ficha/unicidad. |
| **T4** | Import + sync diario | Plantilla; `AcreditacionImportService` upsert; reporte fallos; comando `acreditaciones:sync-estados` + schedule; tests import/sync. |

Orden recomendado: **T1 → T2 → T3 → T4** (secuencial). T3 depende de catálogo para validar `cargo_apo`; T4 depende de T3.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas 1–5)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion (opcional post-brief; AgentSj puede planificar)
