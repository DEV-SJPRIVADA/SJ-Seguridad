# Modulo Ficha empleados

## Objetivo

Llevar la lista de espera de personas contratadas por Gestion Humana (capturadas en `personal_requisitions` al marcar **Contratado**) hasta su ingreso informativo a una ficha de empleados, mediante un tablero unico de area (`gestion_humana`) con permisos independientes de `requisitions.tab.gestion`.

## Alcance V1

- Funcionalidad de **area unica** de Gestion Humana (no compartida entre areas como `requisitions`); sigue el patron de Comercial → Gestion Clientes.
- Tablero `ficha_empleados` (etiqueta **Ficha empleados**) con una unica pestaña **Empleados** (`ficha_empleados_tabs.empleados`).
- Pestaña Empleados: pills **Pendientes | En ficha** (default Pendientes), busqueda `q` (cedula, nombre, codigo de requisicion), export Excel.
- Accion **Gestionar Empleado** (solo `ficha_empleados.manage`, desde FEAT-022): abre el formulario de ficha precargado con los datos de la requisicion; el registro solo se mueve de Pendientes a En ficha cuando el usuario confirma el formulario con **Crear empleado** (ver seccion "Flujo Gestionar Empleado").
- Accion **Carta de contratación** desde Pendientes (`ficha_empleados.manage`): formulario corto + generación Word **sin** mover a En ficha (ver seccion "Carta rápida de contratación").
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
| `ficha_empleados.terminate` | Registrar desvinculacion formal (causal, fechas, recontratable) — cierra el vinculo activo |

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
| GET | `/gestion-humana/ficha-empleados/empleados/exportar` | `gestion-humana.ficha-empleados.employees.export` | `ficha_empleados.view` — Plantilla masivos (En ficha) |
| GET | `/gestion-humana/ficha-empleados/empleados/exportar-pendientes` | `gestion-humana.ficha-empleados.employees.export-pendientes` | `ficha_empleados.view` — listado Pendientes |
| GET | `/gestion-humana/ficha-empleados/empleados/plantilla-importacion` | `gestion-humana.ficha-empleados.employees.import-template` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/plantilla-importacion/exportar` | `gestion-humana.ficha-empleados.employees.export-import-template` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/empleados/importar` | `gestion-humana.ficha-empleados.employees.import` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/ficha` | `gestion-humana.ficha-empleados.employees.ficha.edit` | `ficha_empleados.manage` |
| PATCH | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/ficha` | `gestion-humana.ficha-empleados.employees.ficha.update` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/desvincular` | `gestion-humana.ficha-empleados.employees.ficha.terminate` | `ficha_empleados.terminate` |
| GET | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/carta-contratacion` | `gestion-humana.ficha-empleados.employees.contratacion.quick` | `ficha_empleados.manage` — carta rápida desde Pendientes |
| POST | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/carta-contratacion` | `gestion-humana.ficha-empleados.employees.contratacion.quick.generate` | `ficha_empleados.manage` |
| GET | `/gestion-humana/ficha-empleados/empleados/periodos/{period}/cartas/plantillas` | `gestion-humana.ficha-empleados.employees.period.letters.templates` | `ficha_empleados.terminate` |
| POST | `/gestion-humana/ficha-empleados/empleados/periodos/{period}/cartas/generar` | `gestion-humana.ficha-empleados.employees.period.letters.generate` | `ficha_empleados.terminate` |
| GET | `/gestion-humana/ficha-empleados/empleados/periodos/{period}/cartas/descargar` | `gestion-humana.ficha-empleados.employees.period.letters.download` | `ficha_empleados.terminate` |
| GET | `/gestion-humana/ficha-empleados/catalogos` | `gestion-humana.ficha-empleados.catalogs.index` | `ficha_empleados.manage` |
| POST | `/gestion-humana/ficha-empleados/catalogos/{type}` | `gestion-humana.ficha-empleados.catalogs.store` | `ficha_empleados.manage` |
| PATCH | `/gestion-humana/ficha-empleados/catalogos/{type}/{item}` | `gestion-humana.ficha-empleados.catalogs.update` | `ficha_empleados.manage` |
| DELETE | `/gestion-humana/ficha-empleados/catalogos/{type}/{item}` | `gestion-humana.ficha-empleados.catalogs.destroy` | `ficha_empleados.manage` |

> **FEAT-022 (2026-08-03):** se elimino la ruta `PATCH .../{fichaEntry}/agregar` (`...employees.promote`) y su `PromoteFichaEntryRequest`. `create`/`store` ahora tienen **dos modos** sobre las mismas URIs (ver seccion "Flujo Gestionar Empleado" abajo): sin `desde`/`ficha_entry_id` (alta manual, sin cambios) y con `desde`/`ficha_entry_id` (completar un pendiente existente).

Middleware: `password.changed` (mismo grupo `auth`/`active` global de `routes/web.php`); autorizacion fina resuelta en el controlador (`authorizeView()` para index/export/`editFicha` consulta, `abort_unless($this->canManage(), 403)` / FormRequest `ficha_empleados.manage` para `create`/`store`/`updateFicha`/import/catalogos).

## Controlador (`App\Http\Controllers\GestionHumana\FichaEmpleadosController`)

- `index(Request $request): View` — filtro `estado=pendientes|en_ficha` (default `en_ficha`), busqueda `q` (cedula, nombre o `requisition.code`), eager load `requisition.position`, `requisition.client`, `requisition.city`, `movedBy`, `profile`.
- `datatable` — server-side via `EmployeeFichaEntryDatatableService`. Con `estado=en_ficha` y `employment_status=desvinculado` incluye columna **Recontratable** (`Si`/`No`/`—`) desde el ultimo periodo cerrado (`is_rehireable`). En `estado=pendientes`, el icono de carta rápida usa `.cursos-catalogo-page__icon-btn--success` si el vínculo activo tiene `termination_letter_path`.
- `create(Request $request): View` — **dos modos** segun query `desde` (ver "Flujo Gestionar Empleado" abajo):
  - Sin `desde`: alta manual sin requisición — `$fichaEntry = null`, perfil vacio con `document_type='C'`, `employment_status=activo` y flags `requires_courses`/`requires_acreditacion` en `true` (editables en el formulario).
  - Con `desde={fichaEntryId}`: resuelve `$fichaEntry` con `PersonalRequisitionFichaEntry::pending()->findOrFail($desde)` (**404** si no existe o ya esta en ficha) y arma el perfil precargado con `EmployeeFichaProfilePrefill::buildForEntry()` (no persiste nada en el `GET`).
  - En ambos modos quien tiene `ficha_empleados.manage` ve y edita la seccion **Cursos y acreditacion** (misma UI que en edicion). Toolbar de cabecera: iconos Volver / Guardar (`.req-manage-filters__icon-btn`).
- `store(StoreManualEmployeeFichaRequest): RedirectResponse` — bifurca por `ficha_entry_id` (input oculto del formulario):
  - Con `ficha_entry_id`: revalida `pending()` (`findOrFail`, 404/422 si ya fue movida por otro proceso — proteccion doble envio), actualiza `hired_document`/`hired_full_name`/`moved_to_ficha_at`/`moved_to_ficha_by` en la fila **existente** (no crea duplicado), crea o actualiza su `EmployeeFichaProfile`, aplica `requires_courses`/`requires_acreditacion` si vienen en el request, y redirige a `employees.index` (estado por defecto `en_ficha`).
  - Sin `ficha_entry_id` (alta manual): crea fila nueva (`personal_requisition_id = null`) + perfil (incluye flags de requisitos si se enviaron), redirige a `.../{id}/ficha`.
- `exportExcel(Request $request): StreamedResponse|RedirectResponse` — export **Plantilla masivos** solo registros **En ficha**; sin rango de fechas exporta solo **activos**; con `fecha_desde`/`fecha_hasta` filtra por fecha de ingreso.
- `exportPendientes(Request $request): StreamedResponse|RedirectResponse` — export Excel del listado **Pendientes** (`PersonalRequisitionFichaEntryExport::downloadPendientes`); respeta búsqueda `q`; vacío → redirect con error.
- `importTemplate(): StreamedResponse` — plantilla vacía importación SJ (`ficha_empleados.manage`).
- `exportImportTemplate(Request $request): StreamedResponse|RedirectResponse` — exporta empleados en ficha con datos actuales en **mismo formato** que la plantilla de import (round-trip editar → reimportar); mismos filtros que export masivos: sin fechas solo activos; con `fecha_desde`/`fecha_hasta` filtra por ingreso; respeta `q`.
- `import(ImportEmployeeFichaRequest): RedirectResponse` — carga masiva xlsx.
- `editFicha` / `updateFicha` — consulta/edicion de ficha ya en ficha (`employee_ficha_profiles`). `editFicha` exige `canView` (lectura o manage); con solo `ficha_empleados.view` la UI queda en **solo lectura** (sin icono «Habilitar edición» / Guardar) y no ejecuta side-effects de heal (`ensureWorkCity` / `ensureOpenPeriod`). `updateFicha` exige `ficha_empleados.manage`. **No** mueve a ficha (`moved_to_ficha_at` no se toca aqui). Toolbar de acciones en cabecera: iconos `.req-manage-filters__icon-btn` (cursos, **acreditación**, historial, desvinculacion, editar/guardar, volver). Modales consulta cursos/acreditaciones (`.ficha-empleados-consult-modal`): paneles anchos (`4xl`/`5xl`), scroll horizontal+vertical y thead sticky; filas por cédula solo lectura (`ficha_empleados.view`). Seccion **Cursos y acreditacion** va primero en el formulario.

> **FEAT-022:** se elimino `promote(PromoteFichaEntryRequest, PersonalRequisitionFichaEntry)` (setear `moved_to_ficha_at` de un clic sin formulario). Toda promocion de un pendiente pasa ahora por `create`/`store` en modo `desde`.

## Flujo "Gestionar Empleado" (FEAT-022 — completar un pendiente via formulario)

Reemplaza el antiguo flujo de un clic (`promote`, `PATCH .../{fichaEntry}/agregar` + SweetAlert). Reutiliza las mismas rutas/vista de alta manual (`create`/`store`, `create-ficha.blade.php`) en un segundo modo, sin URIs nuevas.

1. **Entrada:** en el listado de Pendientes, el icono **Gestionar Empleado** (`.cursos-catalogo-page__icon-btn`, title/aria-label) es un enlace `GET` (no un formulario) a `gestion-humana/ficha-empleados/empleados/nuevo?desde={fichaEntryId}`.
2. **`create()` con `desde`:** resuelve la fila con `PersonalRequisitionFichaEntry::query()->pending()->find($desde)`. Si el `id` no existe o la fila ya esta **en ficha**, redirige al listado de **Pendientes** con mensaje de error (no 404 opaco).
3. **Prefill sin persistir (FEAT-028 — honesto):** `EmployeeFichaProfilePrefill::buildForEntry()` sugiere en el formulario solo campos editables no exportables de forma silenciosa: sexo, salario, fecha ingreso, cargo (mapeo nómina si existe). **No** precarga centro de costo, ciudad residencia, centro de trabajo ni tipo contrato desde requisición — esos datos van al bloque **Referencia de requisición** (`ficha-requisition-reference.blade.php`, solo lectura). Si el pendiente ya tiene perfil persistido, se reutiliza tal cual.
4. **Formulario:** `create-ficha.blade.php` muestra referencia readonly (código requisición, cliente, cargo, salario/fecha sugeridos, texto centro costo, ciudad), titulo dinamico **"Gestionar empleado — {hired_full_name}"**, campo oculto `ficha_entry_id`, seccion **Cursos y acreditacion** (checks editables), cedula y el formulario completo FEAT-028 (`ficha-form-fields.blade.php`). Acciones de cabecera icon-only (Volver / Guardar). Si la validacion falla, muestra alerta en español con la lista de errores, mensajes bajo el campo (incl. cédula) y hace scroll al primer error; la validacion HTML5 incompleta tambien avisa junto al encabezado.
5. **`store()` con `ficha_entry_id`:** dentro de una transaccion, revalida `pending()->findOrFail()` (protege contra doble envio/doble pestaña — si ya fue movida, captura la excepcion y vuelve al formulario con mensaje). Actualiza `hired_document`, `hired_full_name`, `moved_to_ficha_at = now()`, `moved_to_ficha_by = auth user` en la fila **existente**, y crea/actualiza su `EmployeeFichaProfile` con los datos del formulario. Redirige a `gestion-humana.ficha-empleados.employees.index` (estado por defecto `en_ficha`) con mensaje de exito. Excepciones inesperadas vuelven al formulario con mensaje amigable (se reportan en log).
6. **Cedula duplicada:** `StoreManualEmployeeFichaRequest` valida `hired_document` con `Rule::unique('personal_requisition_ficha_entries', 'hired_document')->ignore($fichaEntryId)` — permite guardar sin cambiar la cedula propia, pero bloquea si se cambia a una cedula que ya pertenece a **otra** fila (mensaje en español bajo el campo y en la alerta superior).
7. **No propagacion:** ninguna escritura de este flujo toca `personal_requisitions.hired_document`/`hired_full_name`; esos campos siguen reflejando lo capturado al marcar Contratado en la requisicion.
8. **Alta manual sin requisicion** (`/nuevo` sin `desde`) sigue exactamente igual: crea fila nueva, redirige a `/{id}/ficha`.
9. **Mensajes de validacion:** atributos y plantillas en español viven en `EmployeeFichaProfileFieldRules` (compartidos por create/update); locale de app puede seguir en `en`.

## Controlador catálogos (`App\Http\Controllers\GestionHumana\FichaEmpleadosCatalogController`)

- `index(): View` — tablero de catálogos nómina (`payroll_catalog_items`).
- `store` / `update` / `destroy` — CRUD por `catalog_type` (`config/employee_ficha.catalog_type_labels`).
- Servicio compartido: `EmployeeFichaCatalogService` (opciones formulario + admin).
- Pestaña **Catalogos** visible solo con `ficha_empleados.manage` (`FichaEmpleadosAccessService::visibleTabsFor()`).

## Modelo `employee_ficha_profiles`

Perfil 1:1 con `personal_requisition_ficha_entry` (nullable si import masivo crea entrada sin requisición). Campos alineados a `EMPLEADOS.xlsx` + `employment_status` (`activo`|`desvinculado`) y `termination_date` (snapshot del ultimo cierre).

**Ciudad de trabajo:** columnas `work_city_code` / `work_city_name` (catalogo `city`). Prefill desde la ciudad de la requisicion (`EmployeeFichaProfilePrefill`; upsert al catalogo Ciudad si no existe). Editable en formulario. Incluidas en plantilla de **importacion / exportar datos para actualizar** (`codigo_ciudad_trabajo`, `ciudad_trabajo`). **No** forman parte de `PlantillaMasivosMapper` / export nómina.

### `employee_ficha_employment_periods` (vinculos laborales)

Cada fila = un contrato/vinculo con la empresa (secuencia 1, 2, 3…). Solo un periodo `activo` por empleado.

**Invariante:** perfil `employment_status = activo` (ficha ya movida) **debe** tener periodo abierto. Si falta (legado / import / fecha retiro vaciada), `ensureOpenPeriodIfProfileActive` lo crea al abrir la ficha, al guardar, en **import masivo SJ**, o al lookup de Desvinculaciones masivos. Backfill: `php artisan ficha:backfill-active-periods`.

| Campo clave | Notas |
| --- | --- |
| `personal_requisition_id` | Requisicion origen del vinculo (reingreso siempre por requisicion) |
| `status` | `activo` \| `cerrado` |
| Condiciones variables | cargo, salario, centro de costo, EPS, AFP, cliente, etc. |
| Cierre | `termination_cause_code`, `is_rehireable`, `last_work_day`, `termination_date`, `termination_notes` |
| Cartas generadas | `termination_letter_path` (disco `local` privado); `termination_letter_type` = `docx` \| `zip` |

Servicio periodos: `App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService`.

## Cartas de desvinculacion Word (FEAT-029; evoluciona FEAT-027)

- **Generar:** periodo `cerrado` de **cualquier** causal; permiso `ficha_empleados.terminate`. Abre modal con plantillas de tipo `desvinculacion` (code en `config/employee_ficha.word_document_type_codes`) que tengan archivo en disco. Seleccion minima 1.
- **Salida:** 1 plantilla → descarga y persiste `.docx` (`termination_letter_type = docx`); 2+ → `.zip` (`zip`). Generar **siempre reemplaza** path/tipo previos del periodo (borra archivo anterior si existia).
- **Descargar:** sirve el ultimo archivo persistido; 404 si no hay path o archivo ausente; **no** regenera. No hay boton **Regenerar** aparte (volver a Generar abre el modal y sobrescribe).
- **Admin de plantillas:** tablero sidebar **Plantillas Word** (permisos `plantillas_word.*` + board). **Catalogos → Causal** ya **no** administra plantillas; rutas legacy de upload/download/delete → 404.
- Plantillas: tabla `termination_letter_document_templates` con FK `word_document_type_id` (sin amarre a causal/packs). Contenido legacy RENUNCIA **no** migrado — hay que re-subir. Ver [`plantillas-word.md`](plantillas-word.md).
- Placeholders canónicos: `${NOMBRE_COMPLETO}`, `${DOCUMENTO}`, `${FECHA_TERMINACION_PERFIL}`, etc. — catálogo `letter_placeholders` (UI Plantillas Word). Motor: `LetterVariableBuilder` + `TerminationLetterDocxRenderer` (también fallback temporal `[CLAVE]`). Firmante: `termination_letter_signatory` (env `FICHA_LETTER_SIGNATORY_*`).
- Dependencia: `phpoffice/phpword` (`TemplateProcessor`, macros `${}`).
- Servicios: `App\Services\GestionHumana\TerminationLetter\*` + `App\Services\GestionHumana\Letter\LetterVariableBuilder` (compartido con contratación y futuros tipos).
- Controlador: `TerminationLetterController` — `templates` (JSON), `generate` (body `template_ids`), `download`.
- Rutas: `GET .../periodos/{period}/cartas/plantillas`, `POST .../cartas/generar`, `GET .../cartas/descargar` — `ficha_empleados.terminate`.
- UI: `termination-letter-actions.blade.php` (`iconOnly` en toolbar de ficha junto a Historial; `compact` iconos en modal historial) + modal `termination-letter-generate-modal.blade.php`.
- Audit: `termination_letter_pack` (generate/download, metadata `template_ids` / `output_type`). Mutaciones de plantillas/tipos: audit en modulo Plantillas Word.
- Tests: `tests/Feature/GestionHumana/TerminationLetterPackTest.php`.

### Hooks FEAT-031 (tablero Desvinculaciones)

Tras un `terminate` exitoso, `FichaEmpleadosController` llama a `EmployeeTerminationFollowupService::ensureForClosedPeriod` para crear (si no existe) el registro en `employee_termination_followups` con `letter_generated=false`. Tras un `TerminationLetterController::generate` exitoso, se llama `markLetterGenerated` (`letter_generated=true`; crea el followup si faltaba por datos legacy). Detalle del tablero: [`desvinculaciones.md`](desvinculaciones.md). Regenerar carta sigue en Ficha con `ficha_empleados.terminate` (Seguimientos solo muestra el flag).

Reingreso: requisicion Contratado con cedula desvinculada **recontratable** devuelve el registro a **Pendientes** (badge Reingreso); **Gestionar reingreso** abre nuevo periodo al guardar.

Catálogo **Causal desvinculacion** (`termination_cause`) en pestaña Catalogos.

Catálogos nómina en `payroll_catalog_items` (`catalog_type`, `code`, `name`). Puente cargo: `requisition_position_payroll_maps`. UI admin: pestaña **Catalogos** en Ficha empleados; tras CRUD se permanece en el catalogo activo (`?catalog=`); **Volver al tablero** regresa al grid. Seed alternativo: `php artisan employee-ficha:seed-catalogs`.

Ruta desvinculacion: `POST .../empleados/{fichaEntry}/desvincular` (`ficha.terminate`) — requiere `ficha_empleados.terminate`.

## Carta rápida de contratación (desde Pendientes)

Permite generar cartas Word de tipo `contratacion` **antes** de mover el registro a En ficha, con un formulario corto (sin exigir el formulario completo FEAT-028).

### Flujo

1. En **Pendientes**, icono **Carta de contratación** (contrataciones nuevas y reingresos).
2. Formulario mínimo + plantillas + firmante.
3. Al generar: guarda/actualiza `EmployeeFichaProfile` con esos campos (en reingreso pasa a `activo` y limpia `termination_date`), abre o sincroniza periodo `activo` vía `EmployeeFichaEmploymentPeriodService::openOrSyncPeriodForQuickLetter` (secuencia nueva si solo había vínculos cerrados), genera el pack Word y descarga.
4. **`moved_to_ficha_at` permanece `null`** — el empleado sigue en Pendientes. Luego completa con **Gestionar Empleado** / **Gestionar reingreso**.

### Campos del formulario (variables Word)

`${NOMBRE_COMPLETO}`, `${DOCUMENTO}`, `${LUGAR_NACIMIENTO}`, `${DIRECCION}`, `${CIUDAD_RESIDENCIA}`, `${TELEFONO}`, `${EMAIL}`, `${FECHA_NACIMIENTO}`, `${SALARIO}`, `${FECHA_INGRESO}`, `${CIUDAD_REQUISICION}` (solo lectura desde RQ), `${CARGO}`.

### Componentes

| Pieza | Ubicación |
| --- | --- |
| Servicio | `App\Services\GestionHumana\ContratacionLetter\ContratacionQuickLetterService` |
| FormRequest | `GenerateQuickContratacionLetterRequest` |
| Controller | `ContratacionLetterController::quickForm` / `generateQuick` |
| Vista | `employees/carta-contratacion.blade.php` |
| Permiso | Reutiliza `ficha_empleados.manage` (sin claves Spatie nuevas) |
| Audit | `contratacion_letter_pack` / `generate_quick` (`still_pending: true`) |
| Tests | `tests/Feature/GestionHumana/ContratacionQuickLetterTest.php` |

## Modal Generar Cartas (desde ficha En ficha)

Icono de barra **Generar Cartas** (visible con vínculo **activo** + `ficha_empleados.manage` **o** con vínculo **cerrado** + `ficha_empleados.terminate`). Ambos iconos abren el **mismo modal** (`ficha-generate-cartas`); el de desvinculación preselecciona tipo `desvinculacion` y el de activo preselecciona `contratacion`. El download del último pack de desvinculación se mantiene aparte.

### Comportamiento

1. Lista **todos los tipos activos** de `word_document_types` (Plantillas Word).
2. Tipos con motor: `contratacion` (período activo + manage) y `desvinculacion` (período cerrado + terminate).
3. Tipos no aplicables o sin motor aparecen **deshabilitados** con mensaje (p. ej. «Requiere un vínculo laboral cerrado», «La generación para este tipo aún no está disponible.»).
4. Al elegir un tipo habilitado se cargan plantillas/firmas y el POST usa la URL del tipo (`contratacion.generate` o `period.letters.generate`).
5. UI: cards de tipo, cards compactas de plantilla, pie con iconos Cancelar / Generar y descargar.

### Piezas

| Pieza | Ubicación |
| --- | --- |
| Payload tipos | `FichaEmpleadosController::letterGenerateTypesForFicha` |
| Icono | `partials/contratacion-letter-actions.blade.php` |
| Modal | `partials/contratacion-letter-generate-modal.blade.php` |
| Test | `tests/Feature/GestionHumana/ContratacionLetterGenerateModalTest.php` |

## Formulario ficha alineado a Plantilla masivos (FEAT-028)

**Principio:** captura = exportación. El mismo formulario completo se usa en alta manual, **Gestionar empleado** y editar ficha.

### UI (`ficha-form-fields.blade.php`)

Siete secciones: Identificación, Contacto, Contrato y nómina, Centros, Seguridad social, Pagos, Nómina avanzada. Partial reutilizable `ficha-catalog-select.blade.php` (Select2, formato `codigo — nombre`). Campos con par código/nombre **no** tienen input duplicado para el nombre — el sistema completa el homólogo al guardar.

### Campos obligatorios (store + update)

Cédula, nombre (create), **lugar de nacimiento** (`birth_place`, máx. 255), sexo, fecha ingreso, cargo (`position_code`), salario, centro de costo (`cost_center_code`), EPS, AFP, caja compensación (`payroll_extra.ccf_code`), forma de pago, banco, tipo cuenta, número cuenta. Columna BD nullable (legados); el formulario UI (alta, `?desde=` y edición) lo exige. Fuera de alcance: import/export SJ, plantilla masivos y Selección. Variable Word `${LUGAR_NACIMIENTO}` vía `LetterVariableBuilder` + `config/employee_ficha.php` → `letter_placeholders`.

Opcional en formulario: **fecha desvinculación** (`termination_date`) — visible junto a fecha ingreso; al guardar se sincroniza `employment_status` (misma regla que el import: fecha ≤ hoy → desvinculado). No cierra periodo ni crea seguimiento; la desvinculación formal sigue en el modal **Registrar desvinculación**.

Validación: trait `EmployeeFichaProfileFieldRules` + regla `PayrollCatalogCode`.

### Sync catálogo

`App\Services\GestionHumana\EmployeeFichaProfileCatalogSync` — tras store/update/import sincroniza nombres desde códigos (perfil + pares en `payroll_extra`). Config: `catalog_profile_code_name_pairs`, `catalog_payroll_extra_code_name_pairs` en `config/employee_ficha.php`.

### Prefill honesto

`EmployeeFichaProfilePrefill::requisitionReferenceForEntry()` alimenta el bloque readonly; `attributesForEntry()` ya no escribe valores exportables desde texto de requisición.

### JSON `payroll_extra`

Campos avanzados de plantilla (centro trabajo, CCF, jornada, retención, sucursal, etc.). El controller fusiona claves existentes en update (`mergeProfilePayrollExtra`).

### Catálogos nuevos (FEAT-028)

`work_center`, `linkage_type`, `account_type`, `risk_level`, `workday`, `ccf`, `withholding_type`, `expense_type`, `destination`, `zone`, `severance_admin`. Tipo documento incluye **CE**. Migración/seed: `2026_08_13_162235_seed_fe028_payroll_catalog_defaults.php`.

## Export Excel — Plantilla masivos (nómina externa)

- Clase: `App\Exports\PlantillaMasivosExport` — carga `storage/templates/plantilla-masivos.xlsx`, conserva filas 1–2, datos desde fila 3.
- Mapper: `App\Services\GestionHumana\PlantillaMasivosMapper` — **solo** `employee_ficha_profiles` + `payroll_extra` guardados; sin fallbacks de requisición (excepto cédula/nombre mínimo desde entry); columna **NITCENTROTB** siempre `null`; sin defaults numéricos en jornada/retención/gasto; **EXCLAUXTRA** = `exclude_transport_allowance` (Excluir auxilio de transporte, 0/1; lee legacy `exclude_overtime`).
- Import row mapper: `EmployeeFichaImportRowMapper` — misma regla (solo perfil persistido).
- Config: `config/employee_ficha.php` (`plantilla_masivos_columns`, `plantilla_masivos_excluded_columns`).
- **Sin rango de fechas:** solo empleados activos en ficha.
- **Con `fecha_desde` + `fecha_hasta`:** filtra por `hire_date` del perfil.
- Archivo: `plantilla_masivos_{Y-m-d}.xlsx`.

## Importación masiva SJ

- Plantilla: `EmployeeFichaImportTemplateExport` / ruta `import-template`.
- Export datos actuales: `EmployeeFichaImportTemplateExport::downloadWithData()` + `EmployeeFichaImportRowMapper` / ruta `export-import-template`.
- Servicio: `EmployeeFichaImportService`; comando `php artisan employee-ficha:import {path}`.
- Columnas de nombre en `import_columns`: orden tipo nompr07 (`cedula`, `nombre`, `primer_apellido`…); claves SJ sin renombrar. Campos nuevos opcionales (`edad`, `tipo_cotizante`, `escala`, vacaciones) en `payroll_extra`. Al final: `codigo_ciudad_trabajo`, `ciudad_trabajo`, `codigo_requisicion`.
- Normalización al importar (`EmployeeFichaImportValueNormalizer`): `CEDULA`→`C`, `Masculino`→`M`, `Ahorro`→`1`, riesgo/contrato/salario/forma pago vía catálogo a código corto. La export nómina (`PlantillaMasivosMapper`) reaplica normalización al escribir celdas (archivo binario sin cambio).
- Si vienen partes de nombre, se usan tal cual y se compone `full_name`; si solo viene `nombre`, se parte con `EmployeeFichaNameParser` (compatibilidad plantillas antiguas).
- **Encoding de nombres:** el import normaliza charset (Windows-1252→UTF-8) y repara `?` donde iba `Ñ`/`Ó` (p. ej. `MU?OZ`→`MUÑOZ`, `LE?N`→`LEÓN`). Backfill de datos ya corruptos: `php artisan ficha:fix-name-encoding`.
- **Actualización:** al reimportar una cédula ya en ficha, el servicio sincroniza también la entrada (`hired_full_name` + partes de nombre) con el perfil, para que listado y título coincidan con el formulario.
- `fecha_retiro` → `termination_date` + `employment_status` (`activo`/`desvinculado`). Si queda `activo`, el import **abre periodo** si faltaba (`ensureOpenPeriodIfProfileActive`). No cierra periodo ni crea seguimiento de Desvinculaciones; para desvincular con causal/cartas use el tablero Desvinculaciones.
- Plantilla vacía y **Exportar datos para actualizar** comparten las mismas claves (`import_columns` + `EmployeeFichaImportRowMapper`).
- Fuera de alcance del import SJ hacia columnas de nómina no listadas: otros `payroll_extra` de formulario (jornada, CCF code, etc.) y Archivo (`archive_shelf` / `archive_box`).
- Seed catálogos: `php artisan employee-ficha:seed-catalogs --from=docs/Contratacion`.
- Mapeo técnico: [`docs/Contratacion/MAPEO-PLANTILLA-MASIVOS.md`](../Contratacion/MAPEO-PLANTILLA-MASIVOS.md).
- Columna `linkage_type` (`tipo_vinculacion` en import): `VARCHAR(100)` — valores de nómina como `Contrato Laboral(Dependiente Asociado)` superaban el limite anterior de 30 caracteres.
- **Archivo (2026-08-06):** campos `archive_shelf` / `archive_box` en perfil; ver [`docs/modules/archivo.md`](archivo.md). No forman parte del import masivo.

## Export listado simple (legacy)

- Clase: `App\Exports\PersonalRequisitionFichaEntryExport` — listado Pendientes vía `downloadPendientes()` / ruta `export-pendientes`; columnas: código RQ, cédula, nombre, cargo, cliente, ciudad, fechas, tipo (Nuevo/Reingreso), estado.

## Navegacion

- `App\Services\Navigation\NavigationResolver`: rama `ficha_empleados` (patron identico a `gestion_clientes`) — visible en sidebar de `gestion_humana` solo con `canViewFichaEmpleadosBoard()`; URL resuelta por `User::defaultFichaEmpleadosBoardUrl()`.
- `App\Traits\HasFichaEmpleadosTabs` (patron `HasGestionClientesTabs`): resuelve subnav de pestañas visibles segun `FichaEmpleadosAccessService::visibleTabsFor()`; pestañas `empleados` y `catalogos` (esta ultima solo manage).
- `App\Models\User::fichaEmpleadosBoardTabsFor()` / `defaultFichaEmpleadosBoardUrl()`.

## Vistas

- `resources/views/areas/gestion_humana/ficha-empleados/employees/index.blade.php` — filtros, **Nuevo empleado**, export/import masivos, filas clicables a ficha; columnas: cédula, nombre, cargo, cliente, ciudad, fecha ingreso, fecha retiro, estado (+ agregado por / acciones); en pill **Pendientes**, iconos **Gestionar Empleado** y **Carta de contratación** por fila.
- `resources/views/areas/gestion_humana/ficha-empleados/employees/carta-contratacion.blade.php` — formulario corto carta rápida (Pendientes).
- `resources/views/areas/gestion_humana/ficha-empleados/employees/create-ficha.blade.php` — formulario unico (alta manual / Gestionar empleado / reingreso); toolbar icon-only; seccion requisitos en alta.
- `resources/views/areas/gestion_humana/ficha-empleados/partials/ficha-form-fields.blade.php` — formulario completo FEAT-028 (requisitos + 7 secciones; opcional bloque Documento en create).
- `resources/views/areas/gestion_humana/ficha-empleados/partials/ficha-catalog-select.blade.php` — selector catalogo reutilizable.
- `resources/views/areas/gestion_humana/ficha-empleados/partials/ficha-requisition-reference.blade.php` — referencia readonly requisicion.
- `resources/views/areas/gestion_humana/ficha-empleados/employees/edit-ficha.blade.php` — formulario perfil empleado.
- `resources/views/areas/gestion_humana/ficha-empleados/catalogs/index.blade.php` — admin catalogos nómina (EPS, AFP, cargo, etc.).
- `resources/views/areas/gestion_humana/partials/ficha-empleados-subnav.blade.php` — subnav `.module-tab`.

## Tests

`tests/Feature/FichaEmpleadosTest.php` + `tests/Feature/EmployeeFichaPlantillasTest.php` + suite FEAT-028 + `tests/Feature/GestionHumana/ContratacionQuickLetterTest.php`:

- `tests/Feature/GestionHumana/EmployeeFichaCatalogFe028Test.php`
- `tests/Feature/GestionHumana/EmployeeFichaCatalogSyncFe028Test.php`
- `tests/Feature/GestionHumana/EmployeeFichaFormFe028Test.php`
- `tests/Feature/GestionHumana/EmployeeFichaPrefillFe028Test.php`
- `tests/Feature/GestionHumana/EmployeeFichaMasivosExportFe028Test.php`
- `tests/Feature/GestionHumana/EmployeeFichaSalaryTest.php`

Cobertura FEAT-028: catalogos, sync codigo→nombre, obligatorios, formulario UI, prefill honesto, export sin fallbacks, NIT vacio, round-trip guardar→export.

Cobertura general:
- Columnas de migracion (`personal_requisitions.hired_*`, `personal_requisition_ficha_entries.*`).
- Relaciones/accessors del modelo `PersonalRequisitionFichaEntry`; scopes `pending`/`inFicha`.
- `FichaEmpleadosAccessService`: bypass admin, sin permisos, solo view, manage implica view, board sin tab, independencia de `requisitions.tab.gestion`.
- Controlador: 403 sin `ficha_empleados.view`; listado pendientes por defecto; filtro `en_ficha`; export responde `xlsx` respetando el filtro activo y exige `ficha_empleados.view`.
- **FEAT-022 (modo `desde`):** `create()` precarga desde un pendiente sin persistir perfil; reutiliza el perfil existente si ya habia uno; 404 si el `id` no existe o ya esta en ficha; `store()` con `ficha_entry_id` actualiza la fila existente (no duplica), mueve a ficha, no propaga a la requisicion, respeta cedula duplicada (`unique->ignore`) y revalida `pending()` ante doble envio; vista de pendientes muestra el enlace **Gestionar Empleado** correcto; regresion de alta manual sin `desde` sigue en verde. Tests de `promote` eliminados (ruta retirada).
- Navegacion: tablero visible/oculto segun `view.board.gestion_humana.ficha_empleados`.

Tests de regresion en `tests/Feature/RequisitionModuleTest.php` (marcar Contratado): validacion condicional de `hired_document`/`hired_full_name`, alta/reuso de entrada, duplicado (422 sin confirmar / reasignacion al confirmar), reversion de estado (elimina si pendiente, conserva si ya en ficha).

## Referencias

- Guia de usuario: [`docs/user/ficha-empleados.md`](../user/ficha-empleados.md)
- Desvinculaciones (Masivos / Seguimientos): [`docs/modules/desvinculaciones.md`](desvinculaciones.md)
- Plantillas Word (admin tablero): [`docs/modules/plantillas-word.md`](plantillas-word.md)
- Modulo relacionado: [`docs/modules/requisitions.md`](requisitions.md) (captura de `hired_document`/`hired_full_name` al marcar Contratado)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
