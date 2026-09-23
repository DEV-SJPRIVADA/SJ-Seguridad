# Modulo Acreditaciones

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/acreditaciones.md`.
> Feature: FEAT-036 (revisado 2026-09-23, aprobado con observaciones).

## Objetivo

Tablero de area **Gestion Humana** para controlar personal acreditado (vigencia, solicitud en tramite, estados automaticos) y administrar el **catalogo de cargos** (Manager / APO / Informe / Acreditacion).

## Alcance actual (fase 1)

- Tablero sidebar **Acreditaciones** (`board` key `acreditaciones`, hogar `gestion_humana`, `base_area_tab => false`).
- Pestanas: **Dashboard**, **Acreditados**, **Reporte Diario**, **Validaciones**, **Export Apo**, **Catálogo** (esta ultima solo con `acreditaciones.edit`).
- **Funcional en fase 1:** Acreditados (CRUD, filtros, DataTables server-side, export Excel, plantilla/import upsert, estados calculados, sync diario) + Catálogo cargos (seed 17 + CRUD).
- **Placeholders** («Próximamente»): Dashboard, Reporte Diario, Validaciones, Export Apo — sin endpoints de negocio ni KPIs.
- Permisos: `view.board.gestion_humana.acreditaciones`, `acreditaciones.view`, `acreditaciones.edit`. Bypass runtime: `manage.users`. Roles `administrador` / `usuario` **sin** paquete por defecto; `super-admin` via `app:sync-permissions`.
- Tipo de acreditacion = valor **CARGO APO** (texto); unicidad `(document_number, cargo_apo)`; re-import → upsert.
- Cedula obligatoria en `employee_ficha_profiles`; `full_name` siempre desde Ficha (snapshot al persistir).
- ESTADO solo calculado (`AcreditacionEstadoCalculator`); no editable en UI/request/import.
- Audit: `AcreditacionesAuditLogService` → `SystemAuditService` (`module=acreditaciones`, `area=gestion_humana`). Solo mutaciones (+ resumen import).
- Selectores: `<x-searchable-select>` (prohibido Select2). Export: `BaseExport` + `<x-export-excel>` (prohibido `excelHtml5`).
- Borrado **duro** (sin soft-delete).

### Fuera de alcance (fase 1 / fase 2)

- Logica de negocio de Dashboard, Reporte Diario, Validaciones, Export Apo.
- Notificaciones por correo.
- Soft-delete / historial versionado.
- Bridge editable Ficha ↔ Acreditados; sync automatico de nombres tras cambio en Ficha.
- Derivar campo CARGO desde el catalogo.
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Redirect a `acreditados`. `acreditaciones.view` |
| GET | `/dashboard` | `dashboard` | Placeholder. `acreditaciones.view` |
| GET | `/acreditados` | `acreditados` | Shell listado. `acreditaciones.view` |
| GET | `/acreditados/datatable` | `acreditados.datatable` | JSON DataTables. `acreditaciones.view` |
| GET | `/acreditados/lookup` | `acreditados.lookup` | Lookup Ficha por cedula. `acreditaciones.edit` |
| GET | `/acreditados/exportar` | `acreditados.export` | Excel filtrado. `acreditaciones.view` |
| GET | `/acreditados/plantilla-importacion` | `acreditados.import-template` | Plantilla vacia. `acreditaciones.edit` |
| POST | `/acreditados/importar` | `acreditados.import` | Upsert masivo. `acreditaciones.edit` |
| GET | `/acreditados/importar/reporte/{token}` | `acreditados.import-report` | Fallos (~1 h cache). `acreditaciones.edit` |
| POST | `/acreditados` | `acreditados.store` | Crear. `acreditaciones.edit` |
| PATCH | `/acreditados/{acreditacionAcreditado}` | `acreditados.update` | Editar. `acreditaciones.edit` |
| DELETE | `/acreditados/{acreditacionAcreditado}` | `acreditados.destroy` | Borrado duro. `acreditaciones.edit` |
| GET | `/reporte-diario` | `reporte-diario` | Placeholder. `acreditaciones.view` |
| GET | `/validaciones` | `validaciones` | Placeholder. `acreditaciones.view` |
| GET | `/export-apo` | `export-apo` | Placeholder (≠ export listado Acreditados). `acreditaciones.view` |
| GET | `/catalogo` | `catalogo` | Listado catalogo. `acreditaciones.edit` |
| POST | `/catalogo` | `catalogo.store` | Crear. `acreditaciones.edit` |
| PATCH | `/catalogo/{acreditacionCargo}` | `catalogo.update` | Editar. `acreditaciones.edit` |
| DELETE | `/catalogo/{acreditacionCargo}` | `catalogo.destroy` | Eliminar si reglas OK. `acreditaciones.edit` |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.acreditaciones` | Ver tablero **Acreditaciones** en sidebar GH |
| `acreditaciones.view` | Shell, Acreditados (lectura), export, placeholders (tambien implica view si tiene `acreditaciones.edit`) |
| `acreditaciones.edit` | CRUD Acreditados, import, lookup Ficha, Catálogo |

**Paquetes recomendados:** consulta = board + `acreditaciones.view`; operativo = board + view + edit.

Config: `config/access.php` (`system_permissions`, `boards`, `board_canonical_areas`, `acreditaciones_tabs`, `admin_permission_groups`).  
`PermissionCatalog` no genera `view.board.*.acreditaciones` fuera de `gestion_humana`.  
Sync: `php artisan app:sync-permissions` + re-login.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\AcreditacionesController` | Shell, placeholders, Acreditados, import/export, Catálogo (vertical slice) |
| `StoreAcreditacionAcreditadoRequest` / `UpdateAcreditacionAcreditadoRequest` | Ficha obligatoria, al menos una fecha, `cargo_apo` activo en catalogo, unique cedula+APO |
| `ImportAcreditacionAcreditadoRequest` | Archivo Excel import |
| `StoreAcreditacionCargoRequest` / `UpdateAcreditacionCargoRequest` | CRUD catalogo + reglas rename APO |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/acreditaciones/acreditados.blade.php` | Shell DT server-side + filtros + export |
| `areas/gestion_humana/acreditaciones/catalogo.blade.php` | Listado + CRUD catalogo |
| `areas/gestion_humana/acreditaciones/placeholder.blade.php` | Dashboard / Reporte Diario / Validaciones / Export Apo |
| `areas/gestion_humana/acreditaciones/partials/subnav.blade.php` | Pestanas `.module-tab` |
| `areas/gestion_humana/acreditaciones/partials/nuevo-modal.blade.php` | Modal create/edit acreditado |
| `areas/gestion_humana/acreditaciones/partials/masivos-modal.blade.php` | Modal import masivo |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `AcreditacionAcreditado` | `acreditacion_acreditados` | Unique `(document_number, cargo_apo)`; sin FK formal a Ficha ni catalogo |
| `AcreditacionCargo` | `acreditacion_cargos` | Unique `(cargo_manager, cargo_apo)`; `is_active`, `sort_order` |

### `acreditacion_acreditados`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `document_number` | string(50) | CEDULA; index |
| `full_name` | string(255) | Snapshot desde Ficha |
| `cargo` | string(255) | Texto formulario/Excel (no derivado catalogo) |
| `cargo_apo` | string(255) | Tipo (= CARGO APO); parte del unique |
| `vigencia_acr` | date nullable | VIGEN.ACR (vencimiento) |
| `fecha_solicitud` | date nullable | FECHA SOLICITUD |
| `estado` | string(30) | Calculado; index |
| `observaciones` | text nullable | |
| `created_by` / `updated_by` | FK users nullable | `nullOnDelete` |

Indexes: `estado`, `vigencia_acr`, `fecha_solicitud`, `cargo`, `document_number`.

### `acreditacion_cargos`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `cargo_manager` | string(255) | CARGO MANAGER |
| `cargo_apo` | string(255) | CARGO APO; index |
| `cargo_informe` | string(255) | CARGO INFORME |
| `cargo_acreditacion` | string(20) | Codigos 1,2,4,5,6… |
| `is_active` | boolean default true | |
| `sort_order` | unsigned int default 0 | |

### Referencia Ficha (logica)

- Create/update/import: `document_number` debe existir en `employee_ficha_profiles`.
- `full_name` = `EmployeeFichaProfile.full_name` (Excel se ignora).
- Sin FK formal (patron Cursos).

## Servicios / commands

| Clase | Rol |
| --- | --- |
| `AcreditacionesAccessService` | Board / view / edit + tabs visibles (oculta Catálogo sin edit) |
| `HasAcreditacionesTabs` | Trait vistas (tab activa / subnav) |
| `AcreditacionesAuditLogService` | Wrapper audit (`acreditacion_acreditado`, `acreditacion_cargo`, `acreditacion_import`) |
| `AcreditacionEstadoCalculator` | Prioridad estados + `syncAll` (chunk 200) |
| `AcreditacionAcreditadoListService` | Query filtrada (export / reuso) |
| `AcreditacionAcreditadoDatatableService` | DT server-side; tope length 100; sin `-1` |
| `AcreditacionImportService` | Upsert Excel; reporte fallos |
| `AcreditacionCargoCatalogService` | Proteccion delete/rename APO referenciado |
| `SyncAcreditacionEstadosCommand` | `acreditaciones:sync-estados` (`--date`, `--dry-run`) |

Nav: `NavigationResolver`, `SidebarVisibilityService`, `User` (default board URL si aplica).  
Schedule: `bootstrap/app.php` → diario **06:20** `America/Bogota`, `withoutOverlapping` (offset vs Cursos 06:15).

## Reglas de negocio

### Acceso

1. Solo `acreditaciones.view` (+ board): ve pestanas operativas/placeholders y export; **sin** botones mutacion ni Catálogo.
2. `acreditaciones.edit`: CRUD Acreditados + import + Catálogo.
3. Bypass: `manage.users`.

### Estados (persistidos)

| Code BD | Label UI |
| --- | --- |
| `EN_PROCESO` | EN PROCESO |
| `DESACREDITADO` | DESACREDITADO |
| `POR_VENCER` | POR VENCER |
| `ACREDITADO` | ACREDITADO |

Prioridad (`AcreditacionEstadoCalculator`):

1. Si hay `fecha_solicitud` → `EN_PROCESO` (gana aunque `vigencia_acr` este vencida).
2. Si `vigencia_acr` ≤ hoy → `DESACREDITADO`.
3. Si `vigencia_acr` ≤ hoy + `config('acreditaciones.por_vencer_days')` (21) → `POR_VENCER`.
4. Else → `ACREDITADO`.

- Ambas fechas vacias → rechazar (422 / fila import falla). Form Request/import lo validan; el calculator, si llega sin fechas, retorna `ACREDITADO` de forma defensiva (review obs. #4).
- Recalc inmediato en store/update/import y en sync diario.
- Ventana timezone app (`America/Bogota`).

### Acreditados

5. Unicidad: un registro por `(document_number, cargo_apo)`.
6. `cargo_apo` debe existir como valor **activo** en `acreditacion_cargos` (comparacion trim + case-insensitive via `forCargoApo`).
7. Filtros listado/export: `estado`, `document_number` (parcial), `cargo` (parcial), `cargo_apo`, rango `vigencia_desde` / `vigencia_hasta` sobre `vigencia_acr`.
8. Eliminar: confirmacion UI; DELETE fisico.

### Catalogo

9. Seed 17 filas idempotente (`AcreditacionCargoSeeder`, upsert por manager+APO).
10. DELETE bloqueado si es la **ultima** fila activa con ese `cargo_apo` y hay acreditados con ese tipo.
11. UPDATE de `cargo_apo` bloqueado si el valor anterior esta referenciado por acreditados (editar Manager/Informe/Acreditacion libremente).

### Placeholders

12. Dashboard / Reporte Diario / Validaciones / Export Apo: vista «Próximamente»; sin JSON de metricas ni mutaciones.

### Auditoria

13. Eventos: `acreditacion_acreditado` create/update/delete; `acreditacion_cargo` create/update/delete; `acreditacion_import` (resumen ok/fail; sin volcado PII completo).
14. No auditar GET/list/datatable/export ni sync diario fila a fila.

## Import / export Excel

### Plantilla / import

Clase plantilla: `App\Exports\AcreditacionesImportTemplateExport`.  
Fila 1 claves tecnicas (`config/acreditaciones.php` → `import.columns`), fila 2 labels, datos desde fila 3.

| Clave | Label | Persistencia |
| --- | --- | --- |
| `document_number` | CEDULA | Si; required; debe existir en Ficha |
| `full_name` | NOMBRE COMPLETO | Ignorado (ayuda humana) |
| `cargo` | CARGO | Si; texto |
| `cargo_apo` | CARGO APO | Si; tipo / unique key |
| `vigencia_acr` | VIGEN.ACR | Si; date; opcional si hay fecha_solicitud |
| `fecha_solicitud` | FECHA SOLICITUD | Si; date; opcional si hay vigencia_acr |
| `observaciones` | OBSERVACIONES | Si; nullable |

**No** incluir columna ESTADO. Misma cedula + mismo `cargo_apo` → update in-place (upsert). Fallos: token temporal + descarga reporte.

### Export listado

`BaseExport` inline en controlador (no clase dedicada). Columnas: CEDULA, NOMBRE COMPLETO, CARGO, CARGO APO, VIGEN.ACR, ESTADO, OBSERVACIONES, FECHA SOLICITUD. Respeta filtros activos. Auth: `acreditaciones.view`. Boton `<x-export-excel>`.

> Observacion review #1: export hace `filteredQuery()->get()` sin tope; volumen alto puede saturar memoria (mismo patron Seleccion). Follow-up: chunk/stream.

## JavaScript / assets

- DataTables `serverSide: true` en Acreditados (clase distinta de `.js-datatable`; tope `datatable_max_length` = 100).
- Filtros y forms con `<x-searchable-select>` + Alpine.
- Confirmacion UI borrado duro; modal import masivo.
- Sin entry Vite dedicada de charts en fase 1 (placeholders).

## Validacion local

1. `php artisan migrate` (aditivo) + `php artisan db:seed --class=AcreditacionCargoSeeder` + `php artisan app:sync-permissions`.
2. Asignar manualmente board + view (+ edit) a usuario GH; verificar sidebar y pestanas.
3. Seed catalogo visible; CRUD una fila de prueba; probar bloqueo delete/rename APO con acreditados.
4. CRUD Acreditados con cedula real de Ficha; filtros, export, plantilla e import upsert.
5. Casos estado: solicitud, vencido, por vencer (≤21), acreditado; ambas fechas vacias rechazado.
6. `php artisan acreditaciones:sync-estados` (y `--dry-run` si aplica).
7. `php artisan test --compact --filter=Acreditacion` (o `tests/Feature/GestionHumana/Acreditacion*`).

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Export sin tope de memoria | Review obs. #1: chunk/stream o tope documentado |
| Unique Form Request vs `forCargoApo` | Review obs. #2: `Rule::unique` exacto vs LOWER/TRIM en import; posible divergencia collation |
| Tests sin asercion `audit_logs` | Review obs. #3: follow-up espejo Cursos/Ficha |
| Calculator sin fechas → `ACREDITADO` | Review obs. #4: path defensivo; Form Request ya rechaza |
| Cedulas no cargadas en Ficha | Import fallara hasta tener Ficha |
| Drift `full_name` vs Ficha | Snapshot al guardar/import; sync diario no refresca nombres |
| Metadata audit con `document_number` | Aceptable; endurecer PII si politica lo exige |
| Pestaña Export Apo vs export Excel | Nombres distintos; placeholder no implementa export APO |

## Archivos clave

- Config: `config/acreditaciones.php`, `config/access.php`, `config/audit.php`
- Migrations: `*_create_acreditacion_cargos_table`, `*_create_acreditacion_acreditados_table`
- Seeder: `AcreditacionCargoSeeder`
- Factories: `AcreditacionAcreditadoFactory`, `AcreditacionCargoFactory`
- Tests: `tests/Feature/GestionHumana/Acreditacion*.php`, `Acreditaciones*Test.php`
- Vistas: `resources/views/areas/gestion_humana/acreditaciones/`
- Schedule: `bootstrap/app.php`

## Ownership

| Capa | Ubicacion |
| --- | --- |
| Rutas | `routes/areas/gestion_humana.php` (grupo Acreditaciones) |
| Controlador | `App\Http\Controllers\GestionHumana\AcreditacionesController` |
| Vistas | `resources/views/areas/gestion_humana/acreditaciones/` |
| Doc tecnica | `docs/modules/acreditaciones.md` |
| Doc usuario | `docs/user/acreditaciones.md` |

Shared-files tocados en FEAT-036: `config/access.php`, rutas GH, nav (`NavigationResolver`, `SidebarVisibilityService`, `User`), `config/audit.php`, schedule (`bootstrap/app.php`), `PermissionCatalog`.

## Referencias

- Feature Brief: [`docs/briefs/FEAT-036.md`](../briefs/FEAT-036.md)
- Review: [`docs/reviews/FEAT-036.md`](../reviews/FEAT-036.md)
- Doc usuario: [`docs/user/acreditaciones.md`](../user/acreditaciones.md)
- Access: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Ownership: [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md)

## Control de cambios (tecnico)

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-09-23 | FEAT-036: tablero Acreditaciones fase 1 (shell, Acreditados, Catálogo, import, sync). |
