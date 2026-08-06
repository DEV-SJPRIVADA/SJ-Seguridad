# Modulo Ficha empleados

## Objetivo

Llevar la lista de espera de personas contratadas por Gestion Humana (capturadas en `personal_requisitions` al marcar **Contratado**) hasta su ingreso informativo a una ficha de empleados, mediante un tablero unico de area (`gestion_humana`) con permisos independientes de `requisitions.tab.gestion`.

## Alcance V1

- Funcionalidad de **area unica** de Gestion Humana (no compartida entre areas como `requisitions`); sigue el patron de Comercial → Gestion Clientes.
- Tablero `ficha_empleados` (etiqueta **Ficha empleados**) con una unica pestaña **Empleados** (`ficha_empleados_tabs.empleados`).
- Pestaña Empleados: pills **Pendientes | En ficha** (default Pendientes), busqueda `q` (cedula, nombre, codigo de requisicion), export Excel.
- Accion **Gestionar Empleado** (solo `ficha_empleados.manage`, desde FEAT-022): abre el formulario de ficha precargado con los datos de la requisicion; el registro solo se mueve de Pendientes a En ficha cuando el usuario confirma el formulario con **Crear empleado** (ver seccion "Flujo Gestionar Empleado").
- **Fuera de V1:** modulo real de alta/ingreso de empleados (usuario, nomina, expediente); notificaciones por correo; edicion/eliminacion de registros desde la UI de Ficha empleados (las correcciones se hacen reabriendo la requisicion en Gestion).

## Modelo de datos

### `personal_requisitions` (columnas nuevas, ver `docs/modules/requisitions.md`)

- `hired_document` `string(50)` nullable — cedula de la persona contratada, independiente de `replacement_document`.
- `hired_full_name` `string(255)` nullable — nombre completo, independiente de `replacement_name`.

### `personal_requisition_ficha_entries`

Relacion **1:1** con `personal_requisitions`. No duplica columnas de contexto (cargo, cliente, ciudad, codigo): se leen via `requisition` para evitar desincronizacion.

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `personal_requisition_id` | bigint FK → `personal_requisitions.id`, **unique**, `cascadeOnDelete` | Relacion 1:1 |
| `hired_document` | `string(50)` | Copia operativa; permite reasignar sin tocar la requisicion original en caso de duplicado |
| `hired_full_name` | `string(255)` | Idem |
| `moved_to_ficha_at` | `timestamp` nullable | `null` = pendiente (lista de espera); no nulo = en ficha |
| `moved_to_ficha_by` | bigint FK nullable → `users.id`, `nullOnDelete` | Quien guardo el formulario que movio el registro a ficha (**Gestionar Empleado** o alta manual) |
| `created_by` | bigint FK nullable → `users.id`, `nullOnDelete` | Quien marco Contratado (normalmente `managed_by` en ese momento) |
| `timestamps` | | |

Indice adicional: `index('hired_document')` (no unico — la unicidad practica la gobierna la regla de negocio de duplicado, no un constraint de BD).

### Modelo `App\Models\PersonalRequisitionFichaEntry`

- `requisition(): BelongsTo` → `PersonalRequisition`
- `movedBy(): BelongsTo` → `User` (`moved_to_ficha_by`)
- `creator(): BelongsTo` → `User` (`created_by`)
- Scopes: `scopePending()` → `whereNull('moved_to_ficha_at')`; `scopeInFicha()` → `whereNotNull('moved_to_ficha_at')`
- Accessors delegados a `requisition` (evitan N+1 con `loadMissing`/`with` en el controlador): `requisitionCode()`, `positionName()`, `clientName()`, `cityName()`

### `App\Models\PersonalRequisition` (relacion nueva)

- `fichaEntry(): HasOne` → `PersonalRequisitionFichaEntry`
- `$fillable` incluye `hired_document`, `hired_full_name`

## Sincronizacion con la requisicion (`App\Services\Requisitions\PersonalRequisitionFichaSync`)

Invocado desde `RequisitionController::update` dentro de la misma transaccion, cuando GH guarda una requisicion en Gestion:

1. **`status` distinto de `contratado`:** si la entrada propia (`personal_requisition_id` = requisicion actual) esta **pendiente**, se **elimina**. Si ya esta **en ficha**, se **conserva** (caso raro documentado abajo).
2. **`status = contratado` sin duplicado:** upsert normal — crea la entrada si no existe, o actualiza `hired_document`/`hired_full_name` de la entrada propia si ya existe.
3. **`status = contratado` con cedula duplicada y `confirm_duplicate_hired=1`:** la entrada de la **otra** requisicion se **reasigna** (`personal_requisition_id` = requisicion actual, `hired_full_name` actualizado); si la requisicion actual ya tenia su propia entrada, se descarta para respetar la relacion 1:1. Si la entrada reasignada ya estaba **en ficha**, conserva ese estado.

### Deteccion de duplicado (`App\Rules\Requisitions\HiredDocumentNotDuplicated`)

- Aplica solo cuando `status = contratado` y `confirm_duplicate_hired` no viene en `1`.
- Busca una entrada de `personal_requisition_ficha_entries` con el mismo `hired_document` y `personal_requisition_id` distinto al de la requisicion actual.
- Si existe, falla con mensaje reconocible por el frontend: `DUPLICATE_HIRED_DOCUMENT: Esta cedula ya esta registrada en otra requisicion ({code}). Confirme para continuar.`
- El frontend (`edit.blade.php`) intercepta ese prefijo y muestra un SweetAlert2 de confirmacion; al confirmar reenvia el formulario con `confirm_duplicate_hired=1` (input hidden).
- Duplicado **dentro de la misma requisicion** (guardar de nuevo sin cambiar cedula) no dispara la alerta: la regla excluye `personal_requisition_id` de la propia requisicion.

**Riesgo conocido:** al reasignar una entrada duplicada, la requisicion "perdedora" (la que tenia la cedula antes) queda sin `fichaEntry` hasta que se vuelva a guardar con datos de contratado; es infrecuente (misma cedula en dos requisiciones activas) y reversible reabriendo esa requisicion.

## Permisos (`config/access.php`)

| Permiso | Descripcion |
| --- | --- |
| `view.board.gestion_humana.ficha_empleados` | Ver el tablero **Ficha empleados** en el sidebar de Gestion Humana |
| `ficha_empleados.view` | Ver pestaña Empleados (Pendientes + En ficha), usar filtros, exportar Excel |
| `ficha_empleados.manage` | Todo lo de `ficha_empleados.view` + `create`/`store` de empleados (alta manual y **Gestionar Empleado** desde un pendiente) |

- `ficha_empleados.manage` **implica** `ficha_empleados.view` en `FichaEmpleadosAccessService` (no via herencia Spatie).
- Ambos son **independientes** de `requisitions.tab.gestion`: un usuario puede tener cualquier combinacion (Gestion sin Ficha empleados, Ficha empleados sin Gestion, o ambos).
- `manage.users` hace bypass total (ver/gestionar/tablero) — patron identico a `CommercialAccessService`.
- Grupo Admin UI: `admin_ui.other_areas.gestion_humana.subgroups.ficha_empleados` (label "Ficha empleados") junto al subgrupo `boards` existente (que incluye `view.board.gestion_humana.ficha_empleados`).

Servicio: `App\Services\Access\FichaEmpleadosAccessService` — `isAdminBypass()`, `canViewFichaEmpleadosBoard()`, `canView()`, `canManage()`, `visibleTabsFor()`.

## Rutas

`routes/areas/gestion_humana.php` (primera area de Gestion Humana en usar el patron `routes/areas/*.php`; registrado explicitamente en `routes/web.php`):

| Metodo | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/ficha-empleados/empleados/nuevo` | `gestion-humana.ficha-empleados.employees.create` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/empleados/nuevo` | `gestion-humana.ficha-empleados.employees.store` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados` | `gestion-humana.ficha-empleados.employees.index` | `ficha_empleados.view` |
| GET | `/gestion-humana/ficha-empleados/empleados/exportar` | `gestion-humana.ficha-empleados.employees.export` | `ficha_empleados.view` |
| GET | `/gestion-humana/ficha-empleados/empleados/plantilla-importacion` | `gestion-humana.ficha-empleados.employees.import-template` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/plantilla-importacion/exportar` | `gestion-humana.ficha-empleados.employees.export-import-template` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/empleados/importar` | `gestion-humana.ficha-empleados.employees.import` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/ficha` | `gestion-humana.ficha-empleados.employees.ficha.edit` | `ficha_empleados.manage` |
| PATCH | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/ficha` | `gestion-humana.ficha-empleados.employees.ficha.update` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/catalogos` | `gestion-humana.ficha-empleados.catalogs.index` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/catalogos/{type}` | `gestion-humana.ficha-empleados.catalogs.store` | `ficha_empleados.manage` |
| PATCH | `/gestion-humana/ficha-empleados/catalogos/{type}/{item}` | `gestion-humana.ficha-empleados.catalogs.update` | `ficha_empleados.manage` |
| DELETE | `/gestion-humana/ficha-empleados/catalogos/{type}/{item}` | `gestion-humana.ficha-empleados.catalogs.destroy` | `ficha_empleados.manage` |

> **FEAT-022 (2026-08-03):** se elimino la ruta `PATCH .../{fichaEntry}/agregar` (`...employees.promote`) y su `PromoteFichaEntryRequest`. `create`/`store` ahora tienen **dos modos** sobre las mismas URIs (ver seccion "Flujo Gestionar Empleado" abajo): sin `desde`/`ficha_entry_id` (alta manual, sin cambios) y con `desde`/`ficha_entry_id` (completar un pendiente existente).

Middleware: `password.changed` (mismo grupo `auth`/`active` global de `routes/web.php`); autorizacion fina resuelta en el controlador (`authorizeView()` para index/export, `abort_unless($this->canManage(), 403)` para `create`/`store`/import/catalogos).

## Controlador (`App\Http\Controllers\GestionHumana\FichaEmpleadosController`)

- `index(Request $request): View` — filtro `estado=pendientes|en_ficha` (default `en_ficha`), busqueda `q` (cedula, nombre o `requisition.code`), eager load `requisition.position`, `requisition.client`, `requisition.city`, `movedBy`, `profile`.
- `create(Request $request): View` — **dos modos** segun query `desde` (ver "Flujo Gestionar Empleado" abajo):
  - Sin `desde`: alta manual sin requisición — `$fichaEntry = null`, perfil vacio con `document_type='C'` y `employment_status=activo`.
  - Con `desde={fichaEntryId}`: resuelve `$fichaEntry` con `PersonalRequisitionFichaEntry::pending()->findOrFail($desde)` (**404** si no existe o ya esta en ficha) y arma el perfil precargado con `EmployeeFichaProfilePrefill::buildForEntry()` (no persiste nada en el `GET`).
- `store(StoreManualEmployeeFichaRequest): RedirectResponse` — bifurca por `ficha_entry_id` (input oculto del formulario):
  - Con `ficha_entry_id`: revalida `pending()` (`findOrFail`, 404/422 si ya fue movida por otro proceso — proteccion doble envio), actualiza `hired_document`/`hired_full_name`/`moved_to_ficha_at`/`moved_to_ficha_by` en la fila **existente** (no crea duplicado), crea o actualiza su `EmployeeFichaProfile`, y redirige a `employees.index` (estado por defecto `en_ficha`).
  - Sin `ficha_entry_id` (alta manual, sin cambios): crea fila nueva (`personal_requisition_id = null`) + perfil, redirige a `.../{id}/ficha`.
- `exportExcel(Request $request): StreamedResponse|RedirectResponse` — export **Plantilla masivos** solo registros **En ficha**; sin rango de fechas exporta solo **activos**; con `fecha_desde`/`fecha_hasta` filtra por fecha de ingreso.
- `importTemplate(): StreamedResponse` — plantilla vacía importación SJ (`ficha_empleados.manage`).
- `exportImportTemplate(Request $request): StreamedResponse|RedirectResponse` — exporta empleados en ficha con datos actuales en **mismo formato** que la plantilla de import (round-trip editar → reimportar); mismos filtros que export masivos: sin fechas solo activos; con `fecha_desde`/`fecha_hasta` filtra por ingreso; respeta `q`.
- `import(ImportEmployeeFichaRequest): RedirectResponse` — carga masiva xlsx.
- `editFicha` / `updateFicha` — formulario ficha empleado para un pendiente o uno ya en ficha (`employee_ficha_profiles`); **no** mueve a ficha (`moved_to_ficha_at` no se toca aqui), fuera del alcance de FEAT-022.

> **FEAT-022:** se elimino `promote(PromoteFichaEntryRequest, PersonalRequisitionFichaEntry)` (setear `moved_to_ficha_at` de un clic sin formulario). Toda promocion de un pendiente pasa ahora por `create`/`store` en modo `desde`.

## Flujo "Gestionar Empleado" (FEAT-022 — completar un pendiente via formulario)

Reemplaza el antiguo flujo de un clic (`promote`, `PATCH .../{fichaEntry}/agregar` + SweetAlert). Reutiliza las mismas rutas/vista de alta manual (`create`/`store`, `create-ficha.blade.php`) en un segundo modo, sin URIs nuevas.

1. **Entrada:** en el listado de Pendientes, el boton **"Gestionar Empleado"** es un enlace `GET` (no un formulario) a `gestion-humana/ficha-empleados/empleados/nuevo?desde={fichaEntryId}`.
2. **`create()` con `desde`:** resuelve la fila con `PersonalRequisitionFichaEntry::query()->pending()->findOrFail($desde)`. Si el `id` no existe o la fila ya esta **en ficha**, responde **404** (protege contra reprocesar un registro ya movido).
3. **Prefill sin persistir:** `App\Services\GestionHumana\EmployeeFichaProfilePrefill::buildForEntry($fichaEntry)` arma un `EmployeeFichaProfile` **en memoria** con la misma logica de mapeo requisicion → perfil que `prefillForEntry()` (documento, nombre parseado, sexo, salario, fecha de ingreso, centro de costo, cargo, tipo de contrato, ciudad, cliente), extraida a un metodo privado comun `attributesForEntry()`. Si el pendiente ya tiene un perfil **persistido** (por ejemplo, porque antes se visito `/{id}/ficha`), se reutiliza ese perfil real tal cual esta, sin sobrescribirlo. Visitar la pantalla en este modo **no** crea ningun `EmployeeFichaProfile` ni toca `moved_to_ficha_at` cuando no habia perfil previo.
4. **Formulario:** `create-ficha.blade.php` muestra un bloque de referencia de solo lectura (codigo de requisicion, cliente, cargo, via `$fichaEntry->requisitionCode()`/`clientName()`/`positionName()`), titulo dinamico **"Gestionar empleado — {hired_full_name}"**, campo oculto `ficha_entry_id`, y los mismos campos de `ficha-form-fields.blade.php` que el alta manual. `hired_document`/`hired_full_name` quedan **editables**; el link "Volver" apunta a `employees.index?estado=pendientes`.
5. **`store()` con `ficha_entry_id`:** dentro de una transaccion, revalida `pending()->findOrFail()` (protege contra doble envio/doble pestaña — si ya fue movida, falla en vez de sobrescribir), actualiza `hired_document`, `hired_full_name`, `moved_to_ficha_at = now()`, `moved_to_ficha_by = auth user` en la fila **existente**, y crea/actualiza su `EmployeeFichaProfile` con los datos del formulario. Redirige a `gestion-humana.ficha-empleados.employees.index` (estado por defecto `en_ficha`) con mensaje de exito.
6. **Cedula duplicada:** `StoreManualEmployeeFichaRequest` valida `hired_document` con `Rule::unique('personal_requisition_ficha_entries', 'hired_document')->ignore($fichaEntryId)` — permite guardar sin cambiar la cedula propia, pero bloquea si se cambia a una cedula que ya pertenece a **otra** fila (mismo mensaje de validacion que el alta manual, sin flujo de confirmacion tipo SweetAlert).
7. **No propagacion:** ninguna escritura de este flujo toca `personal_requisitions.hired_document`/`hired_full_name`; esos campos siguen reflejando lo capturado al marcar Contratado en la requisicion.
8. **Alta manual sin requisicion** (`/nuevo` sin `desde`) sigue exactamente igual: crea fila nueva, redirige a `/{id}/ficha`.

## Controlador catálogos (`App\Http\Controllers\GestionHumana\FichaEmpleadosCatalogController`)

- `index(): View` — tablero de catálogos nómina (`payroll_catalog_items`).
- `store` / `update` / `destroy` — CRUD por `catalog_type` (`config/employee_ficha.catalog_type_labels`).
- Servicio compartido: `EmployeeFichaCatalogService` (opciones formulario + admin).
- Pestaña **Catalogos** visible solo con `ficha_empleados.manage` (`FichaEmpleadosAccessService::visibleTabsFor()`).

## Modelo `employee_ficha_profiles`

Perfil 1:1 con `personal_requisition_ficha_entry` (nullable si import masivo crea entrada sin requisición). Campos alineados a `EMPLEADOS.xlsx` + `employment_status` (`activo`|`desvinculado`) y `termination_date`.

Catálogos nómina en `payroll_catalog_items` (`catalog_type`, `code`, `name`). Puente cargo: `requisition_position_payroll_maps`. UI admin: pestaña **Catalogos** en Ficha empleados; seed alternativo: `php artisan employee-ficha:seed-catalogs`.

## Export Excel — Plantilla masivos (nómina externa)

- Clase: `App\Exports\PlantillaMasivosExport` — carga `storage/templates/plantilla-masivos.xlsx`, conserva filas 1–2, datos desde fila 3.
- Mapper: `App\Services\GestionHumana\PlantillaMasivosMapper`.
- Config: `config/employee_ficha.php`.
- **Sin rango de fechas:** solo empleados activos en ficha.
- **Con `fecha_desde` + `fecha_hasta`:** filtra por `hire_date` (perfil o requisición).
- Archivo: `plantilla_masivos_{Y-m-d}.xlsx`.

## Importación masiva SJ

- Plantilla: `EmployeeFichaImportTemplateExport` / ruta `import-template`.
- Export datos actuales: `EmployeeFichaImportTemplateExport::downloadWithData()` + `EmployeeFichaImportRowMapper` / ruta `export-import-template`.
- Servicio: `EmployeeFichaImportService`; comando `php artisan employee-ficha:import {path}`.
- Seed catálogos: `php artisan employee-ficha:seed-catalogs --from=docs/Contratacion`.
- Mapeo técnico: [`docs/Contratacion/MAPEO-PLANTILLA-MASIVOS.md`](../Contratacion/MAPEO-PLANTILLA-MASIVOS.md).
- Columna `linkage_type` (`tipo_vinculacion` en import): `VARCHAR(100)` — valores de nómina como `Contrato Laboral(Dependiente Asociado)` superaban el limite anterior de 30 caracteres.
- **Archivo (2026-08-06):** campos `archive_shelf` / `archive_box` en perfil; ver [`docs/modules/archivo.md`](archivo.md). No forman parte del import masivo.

## Export listado simple (legacy)

- Clase: `App\Exports\PersonalRequisitionFichaEntryExport` — conservada; ya no expuesta en UI principal.

## Navegacion

- `App\Services\Navigation\NavigationResolver`: rama `ficha_empleados` (patron identico a `gestion_clientes`) — visible en sidebar de `gestion_humana` solo con `canViewFichaEmpleadosBoard()`; URL resuelta por `User::defaultFichaEmpleadosBoardUrl()`.
- `App\Traits\HasFichaEmpleadosTabs` (patron `HasGestionClientesTabs`): resuelve subnav de pestañas visibles segun `FichaEmpleadosAccessService::visibleTabsFor()`; pestañas `empleados` y `catalogos` (esta ultima solo manage).
- `App\Models\User::fichaEmpleadosBoardTabsFor()` / `defaultFichaEmpleadosBoardUrl()`.

## Vistas

- `resources/views/areas/gestion_humana/ficha-empleados/employees/index.blade.php` — filtros, **Nuevo empleado**, export/import masivos, filas clicables a ficha; en pill **Pendientes**, boton **Gestionar Empleado** por fila (enlace `GET` a `create` con `?desde={id}`, sin formulario ni SweetAlert).
- `resources/views/areas/gestion_humana/ficha-empleados/employees/create-ficha.blade.php` — formulario unico con **dos modos**: alta manual (sin requisición, titulo "Nuevo empleado") y **Gestionar Empleado** (`$fichaEntry` presente: titulo dinamico, bloque de referencia de requisicion de solo lectura, campo oculto `ficha_entry_id`).
- `resources/views/areas/gestion_humana/ficha-empleados/employees/edit-ficha.blade.php` — formulario perfil empleado.
- `resources/views/areas/gestion_humana/ficha-empleados/catalogs/index.blade.php` — admin catalogos nómina (EPS, AFP, cargo, etc.).
- `resources/views/areas/gestion_humana/partials/ficha-empleados-subnav.blade.php` — subnav `.module-tab`.

## Tests

`tests/Feature/FichaEmpleadosTest.php` + `tests/Feature/EmployeeFichaPlantillasTest.php`:

- Columnas de migracion (`personal_requisitions.hired_*`, `personal_requisition_ficha_entries.*`).
- Relaciones/accessors del modelo `PersonalRequisitionFichaEntry`; scopes `pending`/`inFicha`.
- `FichaEmpleadosAccessService`: bypass admin, sin permisos, solo view, manage implica view, board sin tab, independencia de `requisitions.tab.gestion`.
- Controlador: 403 sin `ficha_empleados.view`; listado pendientes por defecto; filtro `en_ficha`; export responde `xlsx` respetando el filtro activo y exige `ficha_empleados.view`.
- **FEAT-022 (modo `desde`):** `create()` precarga desde un pendiente sin persistir perfil; reutiliza el perfil existente si ya habia uno; 404 si el `id` no existe o ya esta en ficha; `store()` con `ficha_entry_id` actualiza la fila existente (no duplica), mueve a ficha, no propaga a la requisicion, respeta cedula duplicada (`unique->ignore`) y revalida `pending()` ante doble envio; vista de pendientes muestra el enlace **Gestionar Empleado** correcto; regresion de alta manual sin `desde` sigue en verde. Tests de `promote` eliminados (ruta retirada).
- Navegacion: tablero visible/oculto segun `view.board.gestion_humana.ficha_empleados`.

Tests de regresion en `tests/Feature/RequisitionModuleTest.php` (marcar Contratado): validacion condicional de `hired_document`/`hired_full_name`, alta/reuso de entrada, duplicado (422 sin confirmar / reasignacion al confirmar), reversion de estado (elimina si pendiente, conserva si ya en ficha).

## Referencias

- Guia de usuario: [`docs/user/ficha-empleados.md`](../user/ficha-empleados.md)
- Modulo relacionado: [`docs/modules/requisitions.md`](requisitions.md) (captura de `hired_document`/`hired_full_name` al marcar Contratado)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
