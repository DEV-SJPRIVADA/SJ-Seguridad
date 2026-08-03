# Feature Brief — FEAT-022

> Version final (Arquitecto). Decisiones de negocio cerradas 2026-08-03: el usuario confirmo **opcion A — flujo completo** sobre el contexto ambiguo detectado por el Analista (ver [`FEAT-022-analyst.md`](FEAT-022-analyst.md)). Puntos sin respuesta explicita (preguntas 2, 6, 7, 8, 9, 10 del Analista) se cierran aqui como decisiones de arquitectura, marcadas explicitamente.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-022 |
| Modulo / area | `ficha-empleados` (area unica Gestion Humana) — controlador, form requests, vistas y rutas existentes |
| Titulo | Pendientes ficha: boton "Gestionar Empleado" abre formulario precargado desde requisicion antes de mover a ficha |
| Solicitante | Manuel-E (via AgentSj) |
| Fecha | 2026-08-03 |

## Objetivo

Hoy, mover un registro **pendiente** (persona contratada via requisicion) a la ficha de empleados es una accion de un clic (**"Agregar a ficha empleados"**) que dispara un `PATCH` directo sin formulario: crea el perfil autogenerado (`EmployeeFichaProfilePrefill`) sin que Gestion Humana pueda revisar o corregir nada antes de que el registro salga de Pendientes.

Esta feature cambia el flujo para que Gestion Humana **revise y complete los datos antes de mover el registro**: el boton pasa a llamarse **"Gestionar Empleado"**, abre el formulario de ficha (mismo formulario que ya existe para alta manual, `/nuevo`) **precargado** con los datos de la requisicion, y solo al presionar **"Crear empleado"** el registro se marca como movido a ficha (`moved_to_ficha_at`) y aparece en el listado principal. Si el usuario cancela o cierra sin guardar, el pendiente queda intacto, sin cambios parciales ni perfiles huerfanos.

**Para quien:** Gestion Humana (`ficha_empleados.manage`) que gestiona el paso de "contratado en requisicion" a "ficha de empleados operativa".

## Alcance

### Incluye

1. Boton en el listado de Pendientes: **"Gestionar Empleado"** (reemplaza texto y comportamiento de **"Agregar a ficha empleados"**). Deja de ser un formulario `PATCH` con SweetAlert; pasa a ser un enlace `GET` a la pantalla de ficha.
2. Enlace resultante: `GET gestion-humana/ficha-empleados/empleados/nuevo?desde={fichaEntryId}` — reutiliza la ruta/vista existente de alta manual (`create`/`store`), en un nuevo "modo completar pendiente".
3. En modo `desde`, el formulario se precarga con los datos que hoy genera `EmployeeFichaProfilePrefill` (documento, nombre parseado, sexo, salario, fecha de ingreso, centro de costo, cargo, tipo de contrato, ciudad, cliente) **sin persistir el perfil ni tocar `moved_to_ficha_at`** hasta que el usuario guarde. Si el pendiente ya tiene un perfil persistido (por ejemplo, porque antes se visito la pantalla `/{id}/ficha`), se reutiliza ese perfil tal cual esta (no se sobrescribe con el prefill).
4. Cedula (`hired_document`) y nombre completo (`hired_full_name`) quedan **editables** en el formulario (igual que en alta manual); si el usuario los corrige, el cambio se guarda **solo** en `personal_requisition_ficha_entries` (no se propaga a `personal_requisitions.hired_document`/`hired_full_name`).
5. Al presionar **"Crear empleado"**: el `POST` actualiza la fila **pendiente existente** (no crea una fila duplicada), setea `moved_to_ficha_at = now()` y `moved_to_ficha_by = auth user`, crea o actualiza su `EmployeeFichaProfile` con los datos del formulario, y redirige al **listado principal en ficha** (`gestion-humana.ficha-empleados.employees.index`, estado por defecto `en_ficha`) con mensaje de exito.
6. Alta manual sin requisicion (`/nuevo` sin `?desde`) **sigue igual**: crea una fila nueva (`personal_requisition_id = null`), `moved_to_ficha_at` al guardar, redirige a `/{id}/ficha` (comportamiento actual, sin cambios). Solo cambia el flujo iniciado **desde** un pendiente.
7. Se **elimina** el flujo directo `PATCH .../{fichaEntry}/agregar` (accion `promote`), su Form Request (`PromoteFichaEntryRequest`) y el SweetAlert de confirmacion asociado en el listado — quedan reemplazados integramente por el flujo de formulario. Ya no existe una forma de mover un pendiente a ficha "de un clic sin revisar datos".
8. Permiso: se reutiliza `ficha_empleados.manage` (sin permiso nuevo) para `create`/`store` en ambos modos (manual y `desde`), igual que hoy.
9. Cancelar o volver desde la pantalla sin enviar el formulario: el pendiente permanece exactamente igual (Pendientes, sin `moved_to_ficha_at`, sin perfil creado si no existia antes).
10. Ajuste de titulo/contexto en el formulario cuando viene de un pendiente: encabezado **"Gestionar empleado — {hired_full_name}"** con referencia de solo lectura al codigo de requisicion (en vez de "Nuevo empleado" / "Registro manual sin requisicion").
11. Tests feature: cobertura de `create()` en modo `desde` (prefill sin persistir, 404 si no es pendiente), `store()` en modo `desde` (actualiza fila existente, no duplica, valida cedula duplicada contra otra fila, redirige a `index`), eliminacion de tests de `promote`, y regresion de alta manual sin requisicion (sin cambios de comportamiento).

### Fuera de alcance

- Cambios a la pantalla de edicion de ficha existente para pendientes (`GET/PATCH .../{fichaEntry}/ficha`, `editFicha()`/`updateFicha()`, boton **"Guardar ficha"**) — sigue existiendo tal cual (clic en la **fila** de un pendiente sigue abriendo esa pantalla, que auto-genera perfil en el `GET` y **no** mueve a ficha al guardar). No se unifican ambas pantallas de edicion en esta feature.
- Propagar correcciones de cedula/nombre hechas en el formulario de "Gestionar Empleado" hacia `personal_requisitions.hired_document`/`hired_full_name` — quedan desincronizadas intencionalmente (decision confirmada por el usuario).
- Migraciones nuevas o cambios de esquema — no se requieren; se reutilizan `personal_requisition_ficha_entries` y `employee_ficha_profiles` tal como estan.
- Permiso nuevo — se reutiliza `ficha_empleados.manage`.
- Patron de confirmacion de cedula duplicada tipo `HiredDocumentNotDuplicated`/SweetAlert (FEAT-020) para este formulario — la validacion de cedula duplicada usa el mismo bloqueo simple (`Rule::unique`) que ya tiene el alta manual (ver decision AD-3 abajo).
- Notificaciones por correo, exportacion o reportes relacionados con este cambio de flujo.
- Renombrar la descripcion del permiso `ficha_empleados.manage` en `config/access.php` (`'Ficha empleados: Agregar a ficha empleados'`) — cosmetico, no bloquea el criterio de aceptacion; puede ajustarse en un fix pequeno posterior si se desea.

## Reglas de negocio

1. **Dos modos en la misma pantalla `/nuevo`:** el `GET`/`POST` de `create`/`store` distinguen el modo por la presencia de `desde` (query, GET) / `ficha_entry_id` (input oculto, POST):
   - **Modo manual** (sin `desde`/`ficha_entry_id`): comportamiento actual sin cambios — crea fila nueva con `personal_requisition_id = null`.
   - **Modo completar pendiente** (`desde`/`ficha_entry_id` presente): opera sobre una fila **existente** de `personal_requisition_ficha_entries` que debe estar **pendiente** (`moved_to_ficha_at IS NULL`); nunca crea una fila nueva.
2. **Prefill sin persistir en el `GET`:** cuando `desde` resuelve a un pendiente **sin perfil propio**, el controlador construye un `EmployeeFichaProfile` **en memoria** (no persistido) con la misma logica que hoy usa `EmployeeFichaProfilePrefill::prefillForEntry()`, solo para rellenar el formulario. Si el pendiente **ya tiene** perfil persistido (creado antes via `/{id}/ficha`), se usa ese perfil real sin crear uno nuevo.
3. **`desde` invalido:** si el `id` no existe o la fila ya esta **en ficha** (`moved_to_ficha_at` no nulo), el `GET` responde **404** (mismo comportamiento por defecto de Route Model Binding + `firstOrFail`/`findOrFail` con scope `pending()`). No hay mensaje especial; evita reprocesar un registro ya movido.
4. **`store()` en modo completar pendiente:**
   - Revalida que la fila siga **pendiente** al momento de guardar (proteccion ante doble envio/doble pestana): si ya fue movida por otro proceso, la validacion falla (422) en vez de sobrescribir silenciosamente.
   - Actualiza `hired_document`, `hired_full_name`, `moved_to_ficha_at = now()`, `moved_to_ficha_by = auth user` sobre la fila existente (no crea fila).
   - Crea el `EmployeeFichaProfile` si no existe, o actualiza el existente, con los mismos campos del formulario (identico a `ficha-form-fields.blade.php` usado en alta manual y en `/{id}/ficha`).
   - Redirige a `gestion-humana.ficha-empleados.employees.index` (sin parametros → estado por defecto `en_ficha`) con `status` de exito.
5. **`store()` en modo manual (sin cambios):** crea fila + perfil, redirige a `.../{id}/ficha`.
6. **Cedula duplicada:** la regla `Rule::unique('personal_requisition_ficha_entries', 'hired_document')` se mantiene, ignorando la propia fila (`->ignore($fichaEntryId)`) cuando se esta completando un pendiente — para no bloquear el guardado cuando el usuario no cambia la cedula. Si el usuario cambia la cedula a una que ya pertenece a **otra** fila, el guardado se bloquea con el mismo mensaje de validacion que hoy tiene el alta manual (sin flujo de confirmacion tipo SweetAlert — ver AD-3).
7. **No propagacion a la requisicion:** ninguna escritura de este flujo toca `personal_requisitions.hired_document`/`hired_full_name`; esos campos siguen reflejando lo capturado al marcar Contratado.
8. **Cancelar/volver:** el enlace "Volver" del formulario en modo `desde` regresa a `employees.index?estado=pendientes` sin ejecutar ninguna escritura; como el `GET` no persiste nada quando no hay perfil previo, no queda ningun rastro en BD de una visita sin guardar.
9. **Eliminacion de `promote`:** se retira la ruta `PATCH .../{fichaEntry}/agregar`, el metodo `FichaEmpleadosController::promote()`, el `PromoteFichaEntryRequest` y el bloque JS `.js-promote-ficha-entry` (incluye el `<script src="sweetalert2">` si no lo usa ningun otro elemento de la pagina — verificar antes de removerlo, ya el modal de importacion masiva no depende de SweetAlert).

## Decisiones de arquitectura (preguntas del Analista sin respuesta explicita)

| # | Pregunta original (Analista) | Decision |
| --- | --- | --- |
| AD-1 | #2 — URL exacta del enlace | `gestion-humana/ficha-empleados/empleados/nuevo?desde={fichaEntryId}` (propuesta original del Analista), sin ruta nueva. |
| AD-2 | #6 — Permiso | Se reutiliza `ficha_empleados.manage`, sin permiso nuevo. |
| AD-3 | #8 — Cedula duplicada al gestionar | Se bloquea con `Rule::unique(...)->ignore($fichaEntryId)` (mismo patron simple del alta manual actual). **No** se reutiliza el patron `HiredDocumentNotDuplicated`/SweetAlert de FEAT-020 en esta iteracion — mantiene el alcance minimo; si el negocio necesita permitir duplicados confirmados aqui, es una mejora futura acotada. |
| AD-4 | #9 — Titulo de la pantalla | Cuando `$fichaEntry` esta presente: **"Gestionar empleado — {hired_full_name}"** + referencia de solo lectura al codigo de requisicion. Sin `$fichaEntry`: se mantiene "Nuevo empleado" / "Registro manual sin requisicion". |
| AD-5 | #10 — Campos adicionales | Mismos campos que hoy (`ficha-form-fields.blade.php`, sin cambios); se agrega unicamente un bloque de referencia de solo lectura (codigo de requisicion, cliente, cargo) tomado de `$fichaEntry->requisition` cuando existe, para dar contexto sin duplicar logica de negocio. |
| AD-6 | #7 — Confirmacion de "pendiente intacto" | Confirmado por diseño: el `GET` en modo `desde` nunca escribe en BD cuando el pendiente no tiene perfil previo (usa un modelo en memoria); si ya tenia perfil persistido de una visita anterior a `/{id}/ficha`, ese perfil ya existia antes de esta feature y no se modifica hasta el `POST`. |

## Permisos (`config/access.php`)

Sin cambios. Se reutiliza el permiso existente:

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `ficha_empleados.manage` | GH con edicion de Ficha empleados | Ya cubre `create`/`store` (ambos modos) y cubria `promote` (que se elimina). |

## Rutas

`routes/areas/gestion_humana.php` — sin URIs nuevas; se elimina una ruta y se documenta el nuevo comportamiento de las dos existentes:

| Metodo | URI | Nombre | Cambio |
| --- | --- | --- | --- |
| GET | `/gestion-humana/ficha-empleados/empleados/nuevo` | `gestion-humana.ficha-empleados.employees.create` | **Variante nueva:** acepta `?desde={fichaEntryId}` → precarga formulario desde un pendiente (sin persistir). Sin `desde`, comportamiento identico al actual. |
| POST | `/gestion-humana/ficha-empleados/empleados/nuevo` | `gestion-humana.ficha-empleados.employees.store` | **Variante nueva:** acepta input oculto `ficha_entry_id` → actualiza la fila pendiente existente (mueve a ficha) en vez de crear una nueva. Sin `ficha_entry_id`, comportamiento identico al actual. |
| ~~PATCH~~ | ~~`/gestion-humana/ficha-empleados/empleados/{fichaEntry}/agregar`~~ | ~~`gestion-humana.ficha-empleados.employees.promote`~~ | **Eliminada.** Reemplazada por el flujo `create`/`store` en modo `desde`. |

Sin cambios en: `index`, `export`, `import-template`, `export-import-template`, `import`, `ficha.edit`, `ficha.update` (pantalla `/{id}/ficha`, fuera de alcance).

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| — | — | Sin migraciones. Se reutilizan `personal_requisition_ficha_entries` y `employee_ficha_profiles` sin alteraciones de esquema. |

## Capas a implementar

- [ ] Migracion(es) — **N/A**, sin cambios de esquema.
- [ ] Modelo(s) — sin cambios en `PersonalRequisitionFichaEntry`/`EmployeeFichaProfile` (se reutilizan scopes `pending()`/`inFicha()` existentes).
- [ ] Servicio: refactor de `App\Services\GestionHumana\EmployeeFichaProfilePrefill` — extraer el array de atributos a un metodo privado reutilizable y agregar un metodo publico que retorne un `EmployeeFichaProfile` **sin persistir** (`new EmployeeFichaProfile([...])`), manteniendo `prefillForEntry()` intacto (sigue persistiendo, lo sigue usando `editFicha()` sin cambios).
- [ ] Controlador(es): `FichaEmpleadosController::create()` (acepta `Request`, lee `desde`, resuelve `$fichaEntry` pendiente o 404, arma `$profile` prefilled sin persistir), `store()` (bifurca por `ficha_entry_id`: actualiza fila existente + upsert de perfil, vs. flujo manual actual sin cambios); eliminar `promote()`.
- [ ] Form Request(s): `StoreManualEmployeeFichaRequest` — agregar regla `ficha_entry_id` (`nullable|integer`, `exists` con scope pendiente) y ajustar `hired_document` a `Rule::unique(...)->ignore($fichaEntryId)`; eliminar `PromoteFichaEntryRequest`.
- [ ] Vista(s) Blade: `create-ficha.blade.php` (titulo dinamico, input oculto `ficha_entry_id`, prefill de `hired_document`/`hired_full_name`, bloque de referencia solo lectura, destino de "Volver" dinamico); `index.blade.php` (boton pendientes → enlace `create` con `desde`, texto "Gestionar Empleado", eliminar formulario `PATCH`/SweetAlert de `promote`).
- [ ] JavaScript (si aplica) — eliminar bloque `.js-promote-ficha-entry` de `index.blade.php`; verificar si el `<script src="sweetalert2">` sigue siendo necesario para otro elemento de la pagina antes de removerlo (revisar tambien `masivos-modal` y sus scripts).
- [ ] Export Excel (si aplica) — N/A, sin cambios.
- [ ] Tests — ver seccion "Tests (minimos)".

## Componentes reutilizables

- `App\Services\GestionHumana\EmployeeFichaProfilePrefill` (refactor, no reemplazo) — misma logica de mapeo requisicion → perfil, ahora con variante no persistente.
- `resources/views/areas/gestion_humana/ficha-empleados/partials/ficha-form-fields.blade.php` y `ficha-form-scripts.blade.php` — sin cambios, se siguen incluyendo igual en `create-ficha.blade.php`.
- `App\Services\GestionHumana\EmployeeFichaNameParser` — sin cambios, se sigue usando en `store()`.
- Patron de scopes `pending()`/`inFicha()` de `PersonalRequisitionFichaEntry` — se reutiliza para resolver `$fichaEntry` en `create()` y para revalidar en `store()`.

## Documentacion a actualizar

- [ ] `docs/modules/ficha-empleados.md` — actualizar seccion Rutas (quitar `promote`), seccion Controlador (`create`/`store` con modo `desde`), seccion Vistas, y agregar nota sobre el reemplazo del flujo de un clic por el flujo de formulario.
- [ ] `docs/user/ficha-empleados.md` — actualizar paso a paso de "mover un pendiente a ficha" con el nuevo boton y formulario.
- [ ] `docs/INDEX.md` — sin cambios de navegacion (no hay tablero/pestaña nueva); verificar que no referencie el texto viejo del boton.
- [ ] `README.md` — sin cambios (no afecta stack ni modulos base).

## Archivos compartidos (`shared-files`)

No se tocan archivos de la lista global (`config/access.php`, `routes/web.php`, layouts, seeders globales). Se marca como **coordinacion de modulo** (no shared-files global) porque toca archivos ya existentes y activos de Ficha empleados:

| Archivo | Motivo |
| --- | --- |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `create()`/`store()` bifurcados por modo; eliminacion de `promote()`. |
| `app/Services/GestionHumana/EmployeeFichaProfilePrefill.php` | Refactor para exponer variante sin persistir. |
| `app/Http/Requests/GestionHumana/StoreManualEmployeeFichaRequest.php` | Regla `ficha_entry_id` + ajuste `unique`. |
| `app/Http/Requests/GestionHumana/PromoteFichaEntryRequest.php` | **Eliminar archivo.** |
| `routes/areas/gestion_humana.php` | Eliminar ruta `promote`. |
| `resources/views/areas/gestion_humana/ficha-empleados/employees/index.blade.php` | Boton + JS de pendientes. |
| `resources/views/areas/gestion_humana/ficha-empleados/employees/create-ficha.blade.php` | Modo `desde`, titulo dinamico, campo oculto. |
| `tests/Feature/FichaEmpleadosTest.php` | Eliminar tests de `promote`; agregar tests de modo `desde`. |

**Ownership:** un unico Agente Feature de `ficha-empleados` para toda la tarea (no requiere paralelismo; todos los archivos son del mismo modulo/area).

## Criterios de aceptacion

1. En el listado de Pendientes, el boton dice **"Gestionar Empleado"** y al hacer clic navega (GET, sin confirmacion SweetAlert) a `gestion-humana/ficha-empleados/empleados/nuevo?desde={id}` de esa fila.
2. Esa pantalla muestra el formulario de ficha con **cedula, nombre y demas campos precargados** desde la requisicion (misma informacion que hoy genera `EmployeeFichaProfilePrefill`), editables.
3. Visitar la pantalla en modo `desde` **no** crea ningun `EmployeeFichaProfile` ni modifica `moved_to_ficha_at` si el pendiente no tenia perfil previo — verificable en BD antes de enviar el formulario.
4. Al presionar **"Crear empleado"**: la fila pendiente pasa a `moved_to_ficha_at` no nulo con `moved_to_ficha_by` = usuario actual, su perfil queda creado/actualizado con los datos enviados, y el usuario es redirigido al listado principal en ficha (`employees.index`, sin `estado=pendientes`).
5. La fila **no se duplica**: sigue existiendo un unico registro en `personal_requisition_ficha_entries` para esa requisicion, ahora con estado "en ficha".
6. Editar cedula o nombre en el formulario **no modifica** `personal_requisitions.hired_document`/`hired_full_name` de la requisicion original.
7. Si el usuario da clic en "Volver" sin enviar el formulario, la fila permanece en Pendientes exactamente como estaba.
8. Visitar `?desde={id}` de una fila que **ya esta en ficha** (o un `id` inexistente) responde **404**.
9. Guardar con una cedula que ya pertenece a **otra** fila de ficha distinta bloquea el envio con error de validacion en `hired_document` (no se permite duplicado).
10. La ruta/accion `promote` (`PATCH .../agregar`) **ya no existe** (404/405 si se invoca) y no queda ningun boton ni SweetAlert de "Agregar a ficha empleados" en la UI.
11. El alta manual sin requisicion (`/nuevo` sin `desde`) sigue funcionando exactamente igual que antes (crea fila nueva, redirige a `/{id}/ficha`).
12. `php artisan test --compact tests/Feature/FichaEmpleadosTest.php` en verde; `vendor/bin/pint --dirty --format agent` sin diffs pendientes.

## Validacion local

1. Marcar una requisicion como Contratado (o usar un pendiente existente en BD local) → confirmar que aparece en `?estado=pendientes` con boton **"Gestionar Empleado"**.
2. Clic en el boton → confirmar URL `?desde={id}` y datos precargados (cedula, nombre, cargo, salario si vienen de la requisicion).
3. Revisar en BD (`employee_ficha_profiles`) que **no** se creo ningun perfil solo por visitar la pantalla (si el pendiente no tenia perfil previo).
4. Editar un campo y presionar "Crear empleado" → confirmar redireccion a `employees.index` (en ficha), fila visible ahi y ausente en `?estado=pendientes`.
5. Repetir con un pendiente distinto y dar clic en "Volver" sin guardar → confirmar que sigue en Pendientes sin cambios.
6. Intentar `?desde={id}` de una fila ya movida a ficha → confirmar 404.
7. Probar alta manual sin requisicion (`/nuevo` sin `desde`) → confirmar comportamiento identico al actual (redirige a `/{id}/ficha`).
8. `php artisan test --compact tests/Feature/FichaEmpleadosTest.php`.
9. `vendor/bin/pint --dirty --format agent`.

## Tests (minimos)

| Test | Intencion |
| --- | --- |
| `test_create_form_prefills_from_pending_ficha_entry_without_persisting` | `GET ?desde=` muestra datos de la requisicion y no crea `EmployeeFichaProfile` si no existia. |
| `test_create_form_reuses_existing_profile_when_already_present` | Si el pendiente ya tiene perfil (de `/{id}/ficha`), el formulario lo usa sin sobrescribirlo. |
| `test_create_form_returns_404_when_desde_entry_already_in_ficha` | Proteccion contra reprocesar un registro ya movido. |
| `test_create_form_returns_404_when_desde_entry_does_not_exist` | `id` invalido → 404. |
| `test_store_with_ficha_entry_id_updates_existing_entry_and_moves_to_ficha` | `POST` con `ficha_entry_id` actualiza la fila (no crea una nueva), setea `moved_to_ficha_at`/`moved_to_ficha_by`, crea/actualiza perfil. |
| `test_store_with_ficha_entry_id_redirects_to_index_en_ficha` | Redireccion al listado principal (no a `/{id}/ficha`). |
| `test_store_with_ficha_entry_id_does_not_update_requisition_hired_fields` | No propagacion a `personal_requisitions`. |
| `test_store_with_ficha_entry_id_allows_keeping_same_hired_document` | La regla `unique` ignora la propia fila. |
| `test_store_with_ficha_entry_id_rejects_document_duplicated_in_other_entry` | Bloqueo si la cedula corregida ya pertenece a otra fila. |
| `test_store_with_ficha_entry_id_rejects_when_entry_already_moved` | Revalidacion en el `POST` (doble envio/doble pestana). |
| `test_manual_employee_create_stores_entry_without_requisition` | **Regresion:** alta manual sin `desde` sigue igual (ya existe, verificar que sigue en verde). |
| `test_manual_employee_create_rejects_duplicate_document` | **Regresion:** ya existe, verificar que sigue en verde. |
| ~~`test_ficha_empleados_promote_requires_manage_permission`~~ | **Eliminar** (ruta removida). |
| ~~`test_ficha_empleados_promote_moves_entry_to_ficha`~~ | **Eliminar** (ruta removida). |
| `test_pending_index_shows_gestionar_empleado_button_linking_to_create_with_desde` | Vista: boton renombrado y enlazado correctamente, sin formulario `PATCH`. |

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| El `GET create()` con `desde` podria persistir sin querer si se reutiliza `prefillForEntry()` tal cual (efecto secundario existente en esa funcion) | El controlador debe usar explicitamente la nueva variante **no persistente** del servicio para el caso `desde`; `prefillForEntry()` (que si persiste) sigue reservado a `editFicha()`. |
| Doble pestana/doble clic: dos solicitudes `POST` casi simultaneas para el mismo `ficha_entry_id` | Revalidacion de `pending()` dentro de la transaccion de `store()`; la segunda solicitud falla en vez de duplicar o sobrescribir silenciosamente. |
| Eliminar `promote`/`PromoteFichaEntryRequest` podria dejar referencias sueltas (tests, rutas nombradas en otras vistas) | Buscar usos de `gestion-humana.ficha-empleados.employees.promote` y `PromoteFichaEntryRequest` en todo el repo antes de eliminar; actualizar/():eliminar tests asociados en el mismo cambio. |
| Bloque `<script src="sweetalert2">` en `index.blade.php` podria ser usado por otro elemento de la pagina (ej. futuros mensajes) | Revisar el archivo completo antes de remover el script tag; si ningun otro bloque lo usa, se puede quitar, si no, dejarlo y solo remover el listener `.js-promote-ficha-entry`. |
| Cedula duplicada sin flujo de confirmacion (AD-3) podria sentirse como regresion frente al patron ya usado en requisiciones (FEAT-020) | Documentado como decision de alcance minimo; si Gestion Humana reporta friccion real, es candidato a mejora futura acotada (reutilizar `HiredDocumentNotDuplicated`). |

## Task Cards sugeridas (vertical slices)

Un unico modulo (`ficha-empleados`), sin necesidad de paralelismo. Recomendado en 2 tareas secuenciales para mantener el diff revisable:

### FEAT-022-T1 — Backend: prefill sin persistir, controlador, form request, limpieza de `promote`

- Refactor `EmployeeFichaProfilePrefill`: extraer atributos a metodo privado; agregar metodo publico no persistente.
- `FichaEmpleadosController::create()` — modo `desde` (resolver pendiente, 404 si no aplica, prefill sin persistir).
- `FichaEmpleadosController::store()` — bifurcacion por `ficha_entry_id` (actualizar fila existente + upsert perfil vs. flujo manual actual).
- Eliminar `promote()`, `PromoteFichaEntryRequest`, ruta `promote` en `routes/areas/gestion_humana.php`.
- `StoreManualEmployeeFichaRequest`: regla `ficha_entry_id` + ajuste `unique`.
- Tests backend de la tabla "Tests (minimos)" (create/store en modo `desde`, regresion manual, eliminar tests de `promote`).

### FEAT-022-T2 — Vistas: boton pendientes, formulario dinamico, limpieza JS

- `index.blade.php`: boton **"Gestionar Empleado"** como enlace a `create` con `desde`; eliminar formulario `PATCH`/SweetAlert de `promote` y su JS.
- `create-ficha.blade.php`: input oculto `ficha_entry_id`, prefill de `hired_document`/`hired_full_name`, titulo dinamico, bloque de referencia de solo lectura (codigo requisicion/cliente/cargo), destino dinamico de "Volver".
- Test de vista (boton/enlace correcto en index).
- `vendor/bin/pint --dirty --format agent` + `php artisan test --compact tests/Feature/FichaEmpleadosTest.php`.

Documentacion (`docs/modules/ficha-empleados.md`, `docs/user/ficha-empleados.md`) la actualiza el Agente Documentador tras el Revisor, no una Task Card de Feature.

## Aprobacion

- [x] Analista — preguntas planteadas; usuario confirmo **opcion A (flujo completo)** cerrando la ambiguedad detectada (2026-08-03).
- [x] Arquitecto — brief final, decisiones AD-1 a AD-6 documentadas para preguntas sin respuesta explicita.
- [ ] Usuario — confirmacion explicita de este brief antes de iniciar Task Cards.
- [ ] AgentSj — plan de orquestacion en `docs/briefs/FEAT-022-plan.md`.
