# Feature Brief — FEAT-020

> Version final (Arquitecto). Decisiones de negocio cerradas 2026-07-30 (ver [`FEAT-020-analyst.md`](FEAT-020-analyst.md)). Interpretaciones AgentSj marcadas explicitamente donde el usuario no dio layout exacto.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-020 |
| Modulo / area | `requisitions` (existente, Gestion Humana) + **Ficha empleados** (tablero nuevo, area unica Gestion Humana) |
| Titulo | Contratado: cedula/nombre del contratado + lista de espera + tablero Ficha empleados |
| Solicitante | Manuel-E (via AgentSj) |
| Fecha | 2026-07-30 |

## Objetivo

Cuando Gestion Humana marca una requisicion como **Contratado** (`requisitions/gestion_humana/gestion/{requisition}/editar`), capturar **cedula** y **nombre completo** de la persona contratada (una por fila, alineado a FEAT-011). Esos datos quedan en la requisicion **y** alimentan una **lista de espera** ligada 1:1 a la requisicion. Un tablero nuevo **Ficha empleados** (Gestion Humana), pestaña **Empleados**, permite a usuarios con permisos dedicados (distintos de `requisitions.tab.gestion`) revisar la lista de espera (pendientes) y mover registros a la ficha de empleados (accion **Agregar a ficha empleados**), saliendo de la vista de pendientes. Exporta a Excel con el patron `BaseExport`.

**Para quien:** Gestion Humana que gestiona requisiciones (marca Contratado, ya tiene `requisitions.tab.gestion`) y un segundo grupo de usuarios GH con permisos nuevos de **lectura** y **edicion** sobre Ficha empleados (pueden ser los mismos usuarios u otros, segun se les asigne en Admin).

## Alcance

### Incluye

1. Dos columnas nuevas en `personal_requisitions`: `hired_document`, `hired_full_name` — obligatorias solo cuando `status = contratado` (mismo patron condicional que `hiring_date`/compensacion en `UpdatePersonalRequisitionRequest`).
2. Nueva tabla `personal_requisition_ficha_entries`, relacion **1:1** con `personal_requisitions` (FK unica). Al guardar una requisicion como Contratado se crea o actualiza su entrada.
3. Deteccion de **cedula duplicada** contra otra requisicion: alerta de confirmacion (SweetAlert2, patron ya usado en Parametros de requisiciones / servicios comerciales); al confirmar, el registro existente se **reasigna** a la requisicion actual (no se crean dos filas para la misma persona).
4. Nuevo tablero **Ficha empleados** (`view.board.gestion_humana.ficha_empleados`), pestaña unica **Empleados**, con dos permisos nuevos: lectura (`ficha_empleados.view`) y edicion (`ficha_empleados.manage`), **independientes** de `requisitions.tab.gestion`.
5. Pestaña Empleados: filtro **Pendientes | En ficha** (pills, estilo `.module-tab`/status pill existente). **Pendientes** = lista de espera activa (equivalente a "lista de espera" del enunciado). **En ficha** = registros ya movidos.
6. Accion **Agregar a ficha empleados** (solo `ficha_empleados.manage`): marca `moved_to_ficha_at`/`moved_to_ficha_by`; el registro desaparece de **Pendientes** y aparece en **En ficha**.
7. Export Excel de la pestaña Empleados (`BaseExport` + `<x-export-excel>`), respetando el filtro Pendientes/En ficha activo.
8. Documentacion tecnica y de usuario del modulo nuevo + actualizacion de `docs/modules/requisitions.md`.
9. Tests feature (form request, controller Ficha empleados, navegacion, export).

### Fuera de alcance

- Modulo real de alta/ingreso de empleados al sistema (usuario, nomina, expediente) — v1 es solo lista de espera + ficha informativa.
- Estado "ingresado al sistema externo" (decision usuario #3): no existe en v1; **En ficha** no implica integracion con otro modulo.
- Cambios a `replacement_document` / `replacement_name` (persona reemplazada) — se mantienen intactos y semanticamente separados de `hired_document`/`hired_full_name`.
- Pestañas adicionales en Ficha empleados (Dashboard, Parametros) mas alla de **Empleados**.
- Notificaciones por correo (alta en lista de espera o movimiento a ficha) — **sin correo en v1**.
- Bloqueo de `quantity > 1` en Gestion: no se toca la regla existente (`min:1|max:999`); se documenta como riesgo conocido (ver Riesgos).
- Edicion/eliminacion de registros ya movidos a **En ficha** desde la UI de Ficha empleados (v1 es solo lectura de los datos capturados en la requisicion; correcciones se hacen reabriendo la requisicion en Gestion, ver regla 6 abajo).

## Reglas de negocio

1. **Una persona por fila:** cada requisicion contratada captura exactamente una cedula y un nombre completo, sin importar `quantity` (alineado a FEAT-011; no se agregan campos por unidad de `quantity`).
2. **Campos condicionales:** `hired_document` (string, max 50) y `hired_full_name` (string, max 255) son `required` solo si `status` enviado es `contratado`; `nullable` en cualquier otro estado — mismo patron que `contract_type_id`/`hiring_date` en `UpdatePersonalRequisitionRequest::rules()`.
3. **Persistencia dual:** al guardar Gestion con `status = contratado`, el controlador (dentro de la misma transaccion de `update()`) hace upsert de `personal_requisition_ficha_entries` para esa requisicion con `hired_document`/`hired_full_name` actuales.
4. **Cedula duplicada (contra otra requisicion):**
   - Si `hired_document` coincide con una entrada **de otra requisicion** (`personal_requisition_id` distinto), el guardado normal se detiene con un error de validacion dedicado (`hired_document` → mensaje "Esta cedula ya esta registrada en otra requisicion (REQ-XXXX). Confirme para continuar.") **a menos que** el formulario envie `confirm_duplicate_hired=1`.
   - El frontend intercepta ese error especifico (codigo/mensaje reconocible) y muestra un SweetAlert2 de confirmacion; si el usuario confirma, se reenvia el formulario con `confirm_duplicate_hired=1`.
   - Al confirmar: la entrada existente **se reasigna** (`personal_requisition_id` = requisicion actual, `hired_full_name` actualizado); no se crea una fila nueva. Si esa entrada ya estaba **En ficha** (`moved_to_ficha_at` no nulo), se conserva ese estado (no se revierte a pendiente).
   - Duplicado **dentro de la misma requisicion** (guardar de nuevo sin cambiar cedula) no dispara la alerta: es upsert normal sobre la misma fila.
5. **Reversion de estado Contratado → otro:**
   - Si la entrada de la lista de espera esta **pendiente** (`moved_to_ficha_at` nulo), se **elimina** al guardar el nuevo estado (ya no aplica).
   - Si la entrada ya esta **En ficha** (`moved_to_ficha_at` no nulo), se **conserva** (ya es un registro de ficha, no depende del estado vivo de la requisicion); se documenta como caso raro en `docs/modules/ficha-empleados.md`.
   - `hired_document`/`hired_full_name` en `personal_requisitions` se guardan siempre con lo que envie el formulario (vacios/`null` si el estado ya no es Contratado y los campos no se muestran), igual que `hiring_date`/compensacion.
6. **Correccion de una requisicion ya Contratada:** si GH reabre y cambia cedula/nombre sin cambiar de estado, se actualiza la misma entrada 1:1 (upsert por `personal_requisition_id`), sujeta a la misma regla de duplicado (#4) si la nueva cedula coincide con otra requisicion.
7. **Accion "Agregar a ficha empleados"** (`ficha_empleados.manage`): `PATCH` que setea `moved_to_ficha_at = now()`, `moved_to_ficha_by = auth user`. Idempotente: si ya esta en ficha, no-op (redirect con mensaje informativo).
8. **Permisos Ficha empleados independientes de Gestion:** un usuario con `ficha_empleados.view`/`ficha_empleados.manage` pero sin `requisitions.tab.gestion` puede ver/gestionar la pestaña Empleados sin poder editar requisiciones; viceversa, alguien con `requisitions.tab.gestion` no ve Ficha empleados salvo que se le asignen los permisos nuevos explicitamente.
9. **Export Excel** respeta el filtro activo (Pendientes o En ficha) y busqueda `q` (cedula/nombre/codigo requisicion), usando `App\Exports\BaseExport`.

## Modelo de datos

### `personal_requisitions` (alter)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `hired_document` | `string(50)`, nullable | Cedula de la persona contratada. Independiente de `replacement_document`. |
| `hired_full_name` | `string(255)`, nullable | Nombre completo de la persona contratada. Independiente de `replacement_name`. |

Nombres sugeridos y confirmados por el usuario en la peticion (documentados aqui como definitivos): `hired_document`, `hired_full_name`.

### `personal_requisition_ficha_entries` (tabla nueva)

Relacion **1:1** con `personal_requisitions` — un registro de lista de espera/ficha por requisicion contratada. No se duplican columnas de contexto (cargo, cliente, ciudad, codigo): se leen via la relacion `requisition` para evitar desincronizacion.

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `personal_requisition_id` | bigint FK → `personal_requisitions.id`, **unique**, `cascadeOnDelete` | Relacion 1:1. |
| `hired_document` | `string(50)` | Copia operativa (permite reasignar sin tocar la requisicion original en el caso de duplicado, regla 4). |
| `hired_full_name` | `string(255)` | Idem. |
| `moved_to_ficha_at` | `timestamp` nullable | `null` = pendiente (lista de espera); no nulo = en ficha. |
| `moved_to_ficha_by` | bigint FK nullable → `users.id`, `nullOnDelete` | Quien ejecuto "Agregar a ficha empleados". |
| `created_by` | bigint FK nullable → `users.id`, `nullOnDelete` | Quien marco Contratado (normalmente `managed_by` de la requisicion en ese momento). |
| `timestamps` | | |

Índice adicional: `index('hired_document')` (no unico — permite el flujo de duplicado con confirmacion sin violar constraint de BD; la unicidad practica la gobierna la regla de negocio 4, no la base de datos).

### Modelo `PersonalRequisitionFichaEntry`

- `belongsTo(PersonalRequisition::class)`
- `belongsTo(User::class, 'moved_to_ficha_by')` → `movedBy()`
- `belongsTo(User::class, 'created_by')` → `creator()`
- Scopes: `scopePending($q)` → `whereNull('moved_to_ficha_at')`; `scopeInFicha($q)` → `whereNotNull('moved_to_ficha_at')`
- Accessors de conveniencia para export/vista: `requisitionCode()`, `positionName()`, `clientName()`, `cityName()` delegando a `$this->requisition` (con `loadMissing` en el controlador índice, no N+1).

### `PersonalRequisition` (modelo existente)

- Agregar `'hired_document'`, `'hired_full_name'` a `$fillable`.
- Nueva relacion `fichaEntry(): HasOne` → `PersonalRequisitionFichaEntry`.

## Permisos (`config/access.php`)

| Permiso | Rol(es) tipicos | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.ficha_empleados` | GH con tablero Ficha empleados asignado en Admin | Ver el tablero **Ficha empleados** en el sidebar de Gestion Humana. |
| `ficha_empleados.view` | GH lectura (contratador consulta) | Ver pestaña Empleados (Pendientes + En ficha), usar filtros, exportar Excel. |
| `ficha_empleados.manage` | GH edicion (contratador que gestiona) | Todo lo de `ficha_empleados.view` + ejecutar **Agregar a ficha empleados**. |

Notas:
- `ficha_empleados.manage` **implica** `ficha_empleados.view` en la logica del `AccessService` (igual patron `comercial.matriz.manage` implica `comercial.matriz.view`), no via Spatie herencia sino en el metodo `canAccess*`.
- Ambos permisos son **independientes** de `requisitions.tab.gestion`; un usuario puede tener cualquier combinacion.
- Agregar a `system_permissions` en `config/access.php` (no requieren toggle por-usuario tipo Encargados de seleccion; se asignan en Admin como cualquier permiso de tablero/funcion).
- Agregar `view.board.gestion_humana.ficha_empleados`, `ficha_empleados.view`, `ficha_empleados.manage` al grupo `other_areas.gestion_humana.subgroups` en Admin UI: nuevo subgrupo `ficha_empleados` (label "Ficha empleados") dentro de `other_areas.gestion_humana.subgroups`, junto al subgrupo `boards` existente. Agregar `view.board.gestion_humana.ficha_empleados` al subgrupo `boards` existente (junto a `view.board.gestion_humana.requisiciones` / `dashboard`).
- Nueva entrada en `boards`: `'ficha_empleados' => 'Ficha empleados'`.
- Nueva entrada `ficha_empleados_tabs`: `['empleados' => 'Empleados']` (unica pestaña v1; deja espacio para futuras).

## Rutas propuestas

Ficha empleados es una funcionalidad **unica de area** (solo Gestion Humana, no compartida entre areas como `requisitions`), por lo tanto sigue el patron de **area unica** (como Comercial → Gestion Clientes), no el patron `{module}` multi-area de requisiciones.

| Metodo | URI | Nombre | Archivo de rutas |
| --- | --- | --- | --- |
| GET | `/gestion-humana/ficha-empleados/empleados` | `gestion-humana.ficha-empleados.employees.index` | `routes/areas/gestion_humana.php` (nuevo archivo) |
| GET | `/gestion-humana/ficha-empleados/empleados/exportar` | `gestion-humana.ficha-empleados.employees.export` | idem |
| PATCH | `/gestion-humana/ficha-empleados/empleados/{fichaEntry}/agregar` | `gestion-humana.ficha-empleados.employees.promote` | idem — accion "Agregar a ficha empleados"; requiere `ficha_empleados.manage` |

Middleware: `['auth', 'active', 'password.changed']` + gate de permiso `ficha_empleados.view` (index/export) o `ficha_empleados.manage` (promote) resuelto en el controlador (`authorizeView()`/`authorizeManage()`, mismo patron `CommercialClientController`).

No se agregan rutas a `routes/modules/requisitions.php`; **si** se agrega el nuevo Form Request de `UpdatePersonalRequisitionRequest` (mismo archivo existente, sin ruta nueva) y el flujo de confirmacion de duplicado vive en `RequisitionController::update` (misma ruta `PATCH requisitions/{module}/gestion/{requisition}`).

## Flujos UX

### 1. Editar requisicion → marcar Contratado (`resources/views/modules/requisitions/edit.blade.php`)

- En el bloque **Cierre** (junto a `hiring_date`, ya condicional a `status=contratado`), agregar dos campos nuevos: **Cedula del contratado** y **Nombre completo del contratado**, visibles/obligatorios solo cuando el select Estado = Contratado (mismo `data-status-dependent` / JS ya usado para mostrar compensacion condicional).
- Etiquetas claras para no confundir con "Cedula/Nombre a quien reemplaza" (seccion 6, motivo Reemplazo): usar labels **"Cedula persona contratada"** / **"Nombre completo persona contratada"**.
- Al guardar (`PATCH`):
  - Si no hay duplicado → guarda normal, toast de exito habitual, upsert silencioso de `personal_requisition_ficha_entries`.
  - Si hay duplicado (regla 4) → la respuesta de validacion trae el error en `hired_document`; JS existente de manejo de errores intercepta el mensaje especifico (marcador, ej. prefijo `DUPLICATE_HIRED_DOCUMENT:`) y dispara SweetAlert2 de confirmacion en vez del error inline habitual; al confirmar reenvia el formulario con `confirm_duplicate_hired=1` (input hidden).
  - Historial de cambios (`PersonalRequisitionChangeLogger`) registra `hired_document`/`hired_full_name` con etiquetas legibles **"Cedula contratado"** / **"Nombre contratado"**, igual que `service_structure`.

### 2. Tablero Ficha empleados → pestaña Empleados

- Sidebar Gestion Humana muestra **Ficha empleados** si el usuario tiene `view.board.gestion_humana.ficha_empleados` (o `manage.users`/bypass admin).
- Al entrar, unica pestaña **Empleados** (subnav estilo `.module-tab`, igual a `requisitions/partials/subnav.blade.php`).
- Panel de filtros: pills **Pendientes | En ficha** (default **Pendientes**), busqueda `q` (cedula, nombre, codigo requisicion), boton **Exportar Excel** (`<x-export-excel>`).
- Tabla (DataTables, mismo estilo Gestion): columnas Codigo requisicion, Cedula, Nombre completo, Cargo, Cliente, Ciudad, Fecha contratacion (`hiring_date` de la requisicion), Registrado por (`created_by`), y en **En ficha** columna adicional Fecha agregado a ficha / Agregado por.
- Fila en **Pendientes** con boton **Agregar a ficha empleados** visible solo con `ficha_empleados.manage`; al click, confirmacion simple (SweetAlert2) → `PATCH promote` → toast + la fila desaparece de Pendientes (recarga o remocion optimista) y aparece en **En ficha**.
- Usuarios con solo `ficha_empleados.view` ven ambas pestañas/pills y exportan, sin boton de accion.

### 3. Duplicado de cedula (detalle)

1. GH guarda requisicion B con cedula `123` (ya existe en la entrada de requisicion A, pendiente o en ficha).
2. Backend detecta conflicto, responde 422 con error reconocible en `hired_document`.
3. JS muestra SweetAlert2: *"La cedula 123 ya esta registrada para la requisicion REQ-2026-0050. ¿Continuar y actualizar ese registro para asociarlo a esta requisicion?"* con botones Cancelar / Confirmar.
4. Confirmar → reenvio con `confirm_duplicate_hired=1` → la entrada se reasigna a requisicion B (campo `personal_requisition_id` actualizado, `hired_full_name` actualizado); requisicion A conserva sus columnas `hired_document`/`hired_full_name` propias (no se tocan), pero **ya no tiene** `fichaEntry` asociada hasta que se vuelva a guardar (edge case documentado en Riesgos).

## Capas a implementar

- [ ] Migracion: alter `personal_requisitions` (2 columnas) + create `personal_requisition_ficha_entries`
- [ ] Modelo(s): `PersonalRequisitionFichaEntry`; relacion en `PersonalRequisition`
- [ ] Servicio: `App\Services\Requisitions\PersonalRequisitionFichaSync` (upsert + logica duplicado + reversion)
- [ ] Servicio de acceso: `App\Services\Access\FichaEmpleadosAccessService` (view/manage/board visibility, patron `CommercialAccessService`)
- [ ] Controlador(es): ajuste `RequisitionController::update`; nuevo `App\Http\Controllers\GestionHumana\FichaEmpleadosController` (index, export, promote)
- [ ] Form Request(s): ajuste `UpdatePersonalRequisitionRequest` (reglas `hired_document`/`hired_full_name` + regla duplicado); nuevo `PromoteFichaEntryRequest` (autorizacion `ficha_empleados.manage`)
- [ ] Vista(s) Blade: ajuste `edit.blade.php` (campos nuevos); nuevas `resources/views/areas/gestion_humana/ficha-empleados/employees/index.blade.php` + subnav partial
- [ ] JavaScript: manejo del error especial duplicado + SweetAlert2 confirm en edicion; confirm simple + fetch/patch en Ficha empleados
- [ ] Export Excel: `App\Exports\PersonalRequisitionFichaEntryExport` sobre `BaseExport`
- [ ] Navegacion: `NavigationResolver` (rama `ficha_empleados` como `gestion_clientes`), `config/access.php` (boards, tabs, permisos, admin_ui), helper `User::defaultFichaEmpleadosBoardUrl()`
- [ ] Tests

## Componentes reutilizables

- `App\Exports\BaseExport` + `<x-export-excel>`.
- Patron SweetAlert2 de confirmacion (`resources/views/modules/requisitions/parameters.blade.php`, `resources/views/areas/comercial/matriz-clientes/services/index.blade.php`).
- Subnav `.module-tab` (patron `resources/views/modules/requisitions/partials/subnav.blade.php`, replicado en FEAT-017 Comercial Gestion Clientes).
- `App\Traits\HasGestionClientesTabs` como referencia directa para crear `HasFichaEmpleadosTabs` (aunque v1 solo tiene una pestaña, mantiene el patron para crecer).
- `PersonalRequisitionChangeLogger` (agregar labels nuevos, sin nueva clase).

## Documentacion a actualizar

- [ ] `docs/modules/requisitions.md` — campos `hired_document`/`hired_full_name`, regla condicional, tabla implicada nueva, entrada en Correcciones aplicadas.
- [ ] `docs/modules/ficha-empleados.md` (nuevo) — objetivo, tablero, permisos, rutas, reglas duplicado/reversion, export.
- [ ] `docs/user/ficha-empleados.md` (nuevo) — Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios.
- [ ] `docs/user/requisitions.md` — paso a paso de marcar Contratado con los campos nuevos + alerta de duplicado.
- [ ] `docs/INDEX.md` — enlazar modulo/usuario nuevos.

## Archivos compartidos (`shared-files`)

Marcar **`shared-files: true`** en Task Cards que toquen:

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Nuevo board `ficha_empleados`, `ficha_empleados_tabs`, permisos nuevos, `admin_ui.other_areas.gestion_humana` |
| `app/Services/Navigation/NavigationResolver.php` | Nueva rama de resolucion de tablero (patron `gestion_clientes`) |
| `app/Models/User.php` | Helper `defaultFichaEmpleadosBoardUrl()` / `fichaEmpleadosBoardTabsFor()` |
| `app/Models/PersonalRequisition.php` | Nueva relacion `fichaEntry()`, `$fillable` |
| `app/Http/Requests/Requisitions/UpdatePersonalRequisitionRequest.php` | Reglas `hired_document`/`hired_full_name` + duplicado |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | Hook de upsert/reversion en `update()` |
| `resources/views/modules/requisitions/edit.blade.php` | Campos nuevos + JS confirm duplicado |
| `app/Services/Requisitions/PersonalRequisitionChangeLogger.php` | Labels nuevos |
| `routes/web.php` | Si se requiere registrar `routes/areas/gestion_humana.php` (verificar si ya se incluye automaticamente via `routes/areas/*.php` glob; si no, agregar `require`) |
| `docs/modules/requisitions.md`, `docs/TASKS.md` | Cierre de feature |

**Ownership principal (sin flag):** migracion, modelo `PersonalRequisitionFichaEntry`, servicio `PersonalRequisitionFichaSync`, `FichaEmpleadosAccessService`, controlador/vistas/export nuevos de Ficha empleados, tests nuevos.

> Nota AgentSj: verificar si `routes/web.php` ya hace `require` glob de `routes/areas/*.php`; si Gestion Humana es la primera area en usar `routes/areas/`, puede requerir agregar la linea explicita (ver como se registro `routes/areas/comercial.php` y `routes/areas/operaciones.php`).

## Task Cards sugeridas (vertical slices)

Orden recomendado para AgentSj (un agente feature a la vez; respetar `shared-files`):

### FEAT-020-T1 — Esquema, modelos y permisos base (`shared-files`)

- Migracion: `personal_requisitions` +`hired_document`/`hired_full_name`; create `personal_requisition_ficha_entries`.
- Modelo `PersonalRequisitionFichaEntry` (relaciones, scopes `pending`/`inFicha`, accessors delegados a `requisition`).
- `PersonalRequisition`: `$fillable`, relacion `fichaEntry()`.
- `config/access.php`: `boards.ficha_empleados`, `ficha_empleados_tabs.empleados`, `system_permissions` (`view.board.gestion_humana.ficha_empleados`, `ficha_empleados.view`, `ficha_empleados.manage`), `admin_ui.other_areas.gestion_humana.subgroups` (agregar board + nuevo subgrupo `ficha_empleados`).
- `FichaEmpleadosAccessService` (patron `CommercialAccessService`): `canViewFichaEmpleadosBoard`, `canView`, `canManage`, `visibleTabsFor`.
- Tests: migracion aplica/revierte limpio; modelo relaciones; `FichaEmpleadosAccessService` casos permiso.

### FEAT-020-T2 — Requisicion: campos contratado, validacion y duplicado (`shared-files`)

- `UpdatePersonalRequisitionRequest`: reglas `hired_document`/`hired_full_name` condicionales a `status=contratado`; regla de duplicado (`withValidator` o `ValidationRule` dedicado, ej. `App\Rules\Requisitions\HiredDocumentNotDuplicated`) que ignora `confirm_duplicate_hired=1`.
- `PersonalRequisitionFichaSync` (servicio): `syncOnUpdate(PersonalRequisition $requisition, string $newStatus, string $document, string $fullName, ?int $confirmedDuplicateFor, int $userId): void` — crea/actualiza/reasigna/elimina segun reglas 3–6.
- `RequisitionController::update`: invocar el servicio dentro de la transaccion existente; agregar `hired_document`/`hired_full_name` a `$updateData`; labels en `PersonalRequisitionChangeLogger`.
- Vista `edit.blade.php`: campos nuevos condicionales + JS de confirmacion duplicado (SweetAlert2) + input hidden `confirm_duplicate_hired`.
- Tests: validacion required/nullable por estado; guardado crea entrada; guardado repetido actualiza misma entrada; duplicado sin confirmar → 422; duplicado confirmado → reasigna; reversion Contratado→otro con entrada pendiente → elimina; reversion con entrada en ficha → conserva.

### FEAT-020-T3 — Tablero Ficha empleados: index + accion Agregar (`shared-files` en navegacion)

- `routes/areas/gestion_humana.php` (nuevo): `employees.index`, `employees.export`, `employees.promote`.
- `App\Http\Controllers\GestionHumana\FichaEmpleadosController`: `index` (filtro `estado=pendientes|en_ficha`, `q`, eager load `requisition.position`,`requisition.client`,`requisition.city`), `promote` (Form Request `PromoteFichaEntryRequest`, `ficha_empleados.manage`).
- Vista `resources/views/areas/gestion_humana/ficha-empleados/employees/index.blade.php` + subnav partial (trait `HasFichaEmpleadosTabs`, patron `HasGestionClientesTabs`).
- `NavigationResolver`: rama `ficha_empleados` (patron `gestion_clientes`); `User::fichaEmpleadosBoardTabsFor()` / `defaultFichaEmpleadosBoardUrl()`.
- Tests: 403 sin `ficha_empleados.view`; index lista pendientes por defecto; pill `en_ficha` filtra; `promote` requiere `manage`, mueve registro y desaparece de pendientes; sidebar visible solo con permiso de tablero.

### FEAT-020-T4 — Export Excel, documentacion y cierre (`shared-files` en docs)

- `App\Exports\PersonalRequisitionFichaEntryExport` (extiende `BaseExport`) + ruta `employees.export`; boton `<x-export-excel>` en la vista.
- Documentacion: `docs/modules/ficha-empleados.md`, `docs/user/ficha-empleados.md`, actualizar `docs/modules/requisitions.md` y `docs/user/requisitions.md`, `docs/INDEX.md`.
- Tests: export responde 200/xlsx con columnas esperadas y respeta filtro activo; regresion `RequisitionModuleTest` (marcar Contratado con campos nuevos no rompe flujos existentes).
- `php artisan test --compact` + `vendor/bin/pint --dirty --format agent`.

## Criterios de aceptacion

1. Al marcar una requisicion como **Contratado** en Gestion, el formulario exige **Cedula** y **Nombre completo** del contratado (una persona por fila); en cualquier otro estado esos campos no son obligatorios.
2. Guardar una requisicion Contratada crea/actualiza una entrada 1:1 en `personal_requisition_ficha_entries` sin duplicar filas para la misma requisicion.
3. Si la cedula ya existe en **otra** requisicion, el sistema muestra una alerta de confirmacion; al confirmar, el registro existente se reasigna a la requisicion actual (no quedan dos filas activas para la misma persona).
4. El tablero **Ficha empleados** solo es visible con `view.board.gestion_humana.ficha_empleados`; la pestaña **Empleados** solo con `ficha_empleados.view`; la accion **Agregar a ficha empleados** solo con `ficha_empleados.manage`.
5. La pestaña Empleados muestra por defecto **Pendientes** (lista de espera); el pill **En ficha** muestra los ya movidos; ambos filtros excluyentes.
6. Ejecutar **Agregar a ficha empleados** mueve el registro de Pendientes a En ficha de forma inmediata y persistente (`moved_to_ficha_at`/`moved_to_ficha_by`).
7. Si se revierte el estado de Contratado a otro estado antes de mover a ficha, la entrada de lista de espera desaparece; si ya estaba en ficha, se conserva.
8. Export Excel de la pestaña Empleados usa `BaseExport`/`<x-export-excel>`, respeta el filtro Pendientes/En ficha y la busqueda activa.
9. Usuarios con `requisitions.tab.gestion` pero sin los permisos nuevos **no** ven el tablero Ficha empleados; usuarios con los permisos nuevos pero sin `requisitions.tab.gestion` **no** pueden editar requisiciones.
10. `php artisan test --compact` en verde para los archivos de requisiciones y los nuevos de Ficha empleados; `vendor/bin/pint --dirty --format agent` sin diffs pendientes.

## Validacion local

1. `php artisan migrate` (BD local) — verificar columnas nuevas y tabla `personal_requisition_ficha_entries`.
2. Marcar una requisicion existente como Contratado con cedula/nombre → revisar fila en la tabla nueva.
3. Repetir con la misma cedula en otra requisicion → confirmar alerta y reasignacion.
4. Revertir estado de una requisicion Contratada pendiente de ficha → confirmar que la entrada desaparece.
5. Entrar a Ficha empleados con usuario `ficha_empleados.view` (sin manage) → confirmar solo lectura y export.
6. Ejecutar "Agregar a ficha empleados" con usuario `ficha_empleados.manage` → confirmar movimiento de pill.
7. Exportar Excel en ambos filtros (Pendientes / En ficha) → validar columnas.
8. `php artisan test --compact tests/Feature/RequisitionModuleTest.php tests/Feature/FichaEmpleadosTest.php` (archivo nuevo).
9. `vendor/bin/pint --dirty --format agent`.

## Tests (minimos)

| Test | Intencion |
| --- | --- |
| `test_update_requires_hired_document_and_name_when_status_contratado` | Validacion required condicional. |
| `test_update_hired_fields_nullable_when_status_not_contratado` | Sin exigencia fuera de Contratado. |
| `test_update_creates_ficha_entry_on_first_hire` | Alta de entrada 1:1. |
| `test_update_reuses_same_ficha_entry_on_resave` | No duplica fila propia. |
| `test_update_duplicate_hired_document_requires_confirmation` | 422 sin `confirm_duplicate_hired`. |
| `test_update_duplicate_hired_document_confirmed_reassigns_entry` | Reasignacion correcta, sin duplicar. |
| `test_update_reverting_from_contratado_removes_pending_entry` | Reversion borra si pendiente. |
| `test_update_reverting_from_contratado_keeps_entry_already_in_ficha` | Reversion conserva si ya en ficha. |
| `test_ficha_empleados_index_forbidden_without_view_permission` | 403 sin `ficha_empleados.view`. |
| `test_ficha_empleados_index_lists_pending_by_default` | Filtro default. |
| `test_ficha_empleados_index_en_ficha_filter` | Pill en_ficha. |
| `test_ficha_empleados_promote_requires_manage_permission` | 403 con solo view. |
| `test_ficha_empleados_promote_moves_entry_to_ficha` | Accion principal. |
| `test_ficha_empleados_board_hidden_without_board_permission` | Sidebar/NavigationResolver. |
| `test_ficha_empleados_export_returns_spreadsheet_with_active_filter` | Export Excel. |

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| `quantity > 1` sigue permitido en Gestion; una requisicion asi marcada Contratado solo registra una persona | Fuera de alcance v1 (decision usuario); documentar en `docs/modules/requisitions.md` como limitacion conocida. |
| Reasignacion de entrada duplicada deja a la requisicion "perdedora" sin `fichaEntry` hasta que se vuelva a guardar | Documentar en `docs/modules/ficha-empleados.md`; aceptable porque el escenario (misma cedula en dos requisiciones activas) es infrecuente y reversible reabriendo esa requisicion. |
| Confusion visual entre "Cedula/Nombre a quien reemplaza" (`replacement_*`) y "Cedula/Nombre contratado" (`hired_*`) en el mismo formulario | Labels explicitos + ubicacion separada (seccion Motivo vs seccion Cierre) + doc usuario con captura de pantalla. |
| Primera funcionalidad de area unica para Gestion Humana (`routes/areas/gestion_humana.php` no existe aun) | T3 valida registro del archivo de rutas en `routes/web.php` (o autoload glob) antes de continuar; replicar exactamente patron `routes/areas/comercial.php`. |
| Sin notificacion por correo: el "contratador" debe recordar revisar Ficha empleados manualmente | Aceptado en v1 (decision usuario); candidato a FEAT futura ligada a FEAT-013. |

## Supuestos documentados (interpretacion AgentSj, sin respuesta explicita del usuario)

| # | Supuesto |
| --- | --- |
| 1 | Nombres de columna definitivos: `hired_document`, `hired_full_name` (sugeridos en la peticion, sin objecion). |
| 2 | Tabla `personal_requisition_ficha_entries` 1:1 sin duplicar columnas de contexto (cargo/cliente/ciudad/codigo se leen de la requisicion relacionada). |
| 3 | Filtro pestaña Empleados = pills **Pendientes | En ficha** (interpretacion explicita solicitada en la peticion). |
| 4 | Duplicado de cedula: la "actualizacion del registro existente" se implementa como **reasignacion** de `personal_requisition_id` (no como fusion de dos filas ni bloqueo duro). |
| 5 | Reversion de estado Contratado→otro con entrada ya en ficha: se conserva (no se borra), priorizando no perder informacion ya promovida. |
| 6 | Ficha empleados es funcionalidad de **area unica** (Gestion Humana), no modulo compartido multi-area como `requisitions`; sigue el patron de Comercial → Gestion Clientes. |
| 7 | Sin notificaciones por correo en v1 (confirmado por el usuario). |

## Aprobacion

- [x] Analista — decisiones 1–6 cerradas por el usuario (2026-07-30); interpretacion de layout Pendientes/En ficha documentada como supuesto a confirmar.
- [x] Arquitecto — brief final.
- [x] Usuario — confirmacion explicita del brief (2026-07-30).
- [x] AgentSj — plan de orquestacion en `docs/briefs/FEAT-020-plan.md`.
