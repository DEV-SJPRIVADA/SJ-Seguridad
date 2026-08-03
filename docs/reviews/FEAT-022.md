# Review Report — FEAT-022

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-022 |
| Fecha | 2026-08-03 |
| Revisor | Agente Revisor (AgentSj) |
| Brief | [`docs/briefs/FEAT-022.md`](../briefs/FEAT-022.md) |
| Alcance revisado | Backend (`create()`/`store()` modo `desde`, prefill no persistente, eliminacion de `promote`) + vistas (`index.blade.php`, `create-ficha.blade.php`) + tests |
| Veredicto | **Aprobado** — apto para pasar a Documentador |

## Verificacion de criterios de aceptacion (brief, seccion "Criterios de aceptacion")

| # | Criterio | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Boton "Gestionar Empleado" es enlace `GET` sin SweetAlert | OK | `index.blade.php:244-247`; test `test_pending_index_shows_gestionar_empleado_button_linking_to_create_with_desde` |
| 2 | Formulario precargado (cedula, nombre, demas campos) editable | OK | `FichaEmpleadosController::create()` usa `profilePrefill->buildForEntry()`; `create-ficha.blade.php` renderiza `ficha-form-fields` + campos editables |
| 3 | `GET ?desde=` no crea perfil ni toca `moved_to_ficha_at` si no habia perfil previo | OK | `EmployeeFichaProfilePrefill::buildForEntry()` retorna `new EmployeeFichaProfile(...)` sin `save()`; test `test_create_form_prefills_from_pending_ficha_entry_without_persisting` verifica `assertDatabaseMissing` + `moved_to_ficha_at` nulo |
| 4 | `store()` mueve a ficha, setea `moved_to_ficha_by`, upsert de perfil, redirige a `employees.index` | OK | `FichaEmpleadosController::store()` rama `$fichaEntryId !== null`; tests `test_store_with_ficha_entry_id_updates_existing_entry_and_moves_to_ficha` y `..._redirects_to_index_en_ficha` |
| 5 | No se duplica la fila | OK | `update()` sobre `$entry` existente, nunca `create()`; test verifica `assertDatabaseCount('personal_requisition_ficha_entries', 1)` |
| 6 | No se propaga a `personal_requisitions.hired_document/hired_full_name` | OK | Ninguna escritura sobre `PersonalRequisition` en el flujo `desde`; test `test_store_with_ficha_entry_id_does_not_update_requisition_hired_fields` |
| 7 | "Volver" sin enviar deja el pendiente intacto | OK | El `GET` no persiste (ver #3); "Volver" es un `<a>` simple sin side effects |
| 8 | `?desde=` de fila ya en ficha o id inexistente → 404 | OK | `PersonalRequisitionFichaEntry::query()->pending()->findOrFail($desde)`; tests `..._returns_404_when_desde_entry_already_in_ficha` y `..._does_not_exist` |
| 9 | Cedula duplicada contra otra fila bloquea con error de validacion | OK | `Rule::unique(...)->ignore($fichaEntryId)`; test `test_store_with_ficha_entry_id_rejects_document_duplicated_in_other_entry` (ademas verifica que `moved_to_ficha_at` sigue nulo tras el rechazo) |
| 10 | Ruta/boton/SweetAlert de `promote` eliminados | OK | `routes/areas/gestion_humana.php` sin ruta `agregar`/`promote`; `PromoteFichaEntryRequest.php` eliminado; `index.blade.php` sin JS `.js-promote-ficha-entry` ni `<script sweetalert2>`; test `assertDontSee('Agregar a ficha empleados')` |
| 11 | Alta manual sin `desde` sin cambios | OK | Rama `$fichaEntryId === null` de `store()` idéntica al flujo previo; tests de regresion en verde (`test_manual_employee_create_stores_entry_without_requisition`, `..._rejects_duplicate_document`) |
| 12 | Tests en verde + `pint` sin diffs | OK con nota | Ver seccion "Tests" abajo |

## Seguridad y permisos

- `create()` (GET, ambos modos) valida `abort_unless($this->canManage(), 403)` antes de resolver `$fichaEntry` — un usuario con solo `ficha_empleados.view` recibe 403 (cubierto por `test_manual_employee_create_form_requires_manage_permission`).
- `StoreManualEmployeeFichaRequest::authorize()` exige `ficha_empleados.manage` para `store()` en ambos modos — sin permiso nuevo, consistente con AD-2 del brief.
- `config/access.php` **no** fue tocado — correcto, la feature reutiliza el permiso existente y no está en la lista de `shared-files` globales.
- Revalidacion de `pending()` en el `POST`: la regla `Rule::exists('personal_requisition_ficha_entries', 'id')->where(fn ($q) => $q->whereNull('moved_to_ficha_at'))` bloquea el guardado (422, error en `ficha_entry_id`) si la fila ya fue movida por otro proceso antes de tocar la BD; el controlador repite el filtro `pending()` dentro de la transaccion como segunda capa. Cubierto por `test_store_with_ficha_entry_id_rejects_when_entry_already_moved`.
- No hay IDOR relevante nuevo: el permiso `ficha_empleados.manage` ya era global (no por registro) antes de esta feature; el comportamiento no cambia ese modelo de autorizacion.

## No propagacion a la requisicion

Confirmado en codigo (`store()` rama `desde` no escribe en el modelo `PersonalRequisition`) y en test (`test_store_with_ficha_entry_id_does_not_update_requisition_hired_fields`). Cumple regla de negocio #7 y el "fuera de alcance" del brief.

## Prefill sin persistir en el `GET`

`EmployeeFichaProfilePrefill` quedo refactorizado correctamente:

- `attributesForEntry()` (privado) concentra el mapeo requisicion → atributos, reutilizado por ambos metodos publicos.
- `prefillForEntry()` (persistente) intacto, sigue siendo el unico usado por `editFicha()` — sin cambios de comportamiento fuera de alcance.
- `buildForEntry()` (nuevo, no persistente) es el unico que usa `create()` en modo `desde`; retorna `new EmployeeFichaProfile(...)` sin `save()`, o el perfil real si ya existia.

Tests `test_create_form_prefills_from_pending_ficha_entry_without_persisting` y `test_create_form_reuses_existing_profile_when_already_present` verifican ambos caminos con `assertDatabaseMissing`/`assertDatabaseCount`.

## Limpieza de `promote`

- Ruta `PATCH .../{fichaEntry}/agregar` eliminada de `routes/areas/gestion_humana.php`.
- `FichaEmpleadosController::promote()` eliminado (no queda en el archivo).
- `PromoteFichaEntryRequest.php` eliminado del filesystem.
- Sin referencias sueltas en codigo de aplicacion o vistas (`grep` de `promote`/`sweetalert`/`js-promote-ficha-entry` solo devuelve archivos de documentacion, que reflejan intencionalmente el cambio como historial).
- El `<script src="sweetalert2">` fue removido por completo de `index.blade.php` sin afectar otros elementos de la pagina (el modal de importacion masiva no dependia de el, confirmado leyendo el archivo completo).

## Tests

- `php artisan test --compact tests/Feature/FichaEmpleadosTest.php`: **37 passed, 1 failed**.
- El unico fallo (`test_admin_bypass_can_view_and_manage_ficha_empleados`, espera `['empleados']` pero obtiene `['empleados', 'catalogos']` en `visibleTabsFor`) es **preexistente y no relacionado**: se reprodujo identico haciendo `git stash` de todos los cambios de FEAT-022 y corriendo el mismo test contra el codigo base. No bloquea esta feature; queda como deuda tecnica separada para quien la introdujo.
- `vendor/bin/pint --test --format agent` sobre los archivos PHP tocados: `passed`, sin diffs pendientes.
- Cobertura nueva: los 11 tests de la tabla "Tests (minimos)" del brief estan presentes y en verde (prefill sin persistir, reutilizo de perfil existente, 404 por entrada inexistente/ya movida, store con `ficha_entry_id` en sus variantes, redireccion, no propagacion, cedula duplicada, doble envio). Los 2 tests de `promote` fueron eliminados segun lo planeado.

## Observaciones (no bloqueantes)

| # | Descripcion | Sugerencia |
| --- | --- | --- |
| 1 | No hay test que golpee explicitamente la ruta nombrada `gestion-humana.ficha-empleados.employees.promote` para confirmar que ya no existe (AC10 solo se valida indirectamente via ausencia de boton/JS en la vista). | Opcional: agregar un test que verifique `Route::has('gestion-humana.ficha-empleados.employees.promote')` es `false`, o que un `PATCH` directo a la URI vieja responde 404/405. Bajo riesgo, la ruta ya no esta registrada. |
| 2 | En el escenario de doble pestana/doble envio real (dos `POST` casi simultaneos que pasan la validacion de `ficha_entry_id` antes de que cualquiera confirme), el segundo request podria terminar en un `redirect()->back()` hacia `GET .../nuevo?desde=X`, que ahora devuelve 404 (porque la fila ya esta en ficha) en vez de mostrar el mensaje de validacion en el formulario. Esto es consistente con la regla de negocio #3 del brief ("404 sin mensaje especial" al revisitar una fila ya movida), por lo que no se considera un defecto, solo una experiencia de usuario abrupta en un caso extremo. | Si Gestion Humana reporta friccion real con este caso raro, evaluar en una mejora futura interceptar el 404 de `create()` cuando viene de un `back()` tras validacion fallida y mostrar un mensaje mas amable. |
| 3 | `docs/modules/ficha-empleados.md` y `docs/user/ficha-empleados.md` ya aparecen modificados en el working tree con contenido que documenta FEAT-022 correctamente (secciones "Flujo Gestionar Empleado", cambios de ruta, cambio de historial), aunque no estaban listados en el alcance de archivos a revisar. No se identifico contenido incorrecto o desalineado con la implementacion real. | Confirmar con el Documentador si este trabajo ya fue realizado por el mismo o si requiere una pasada de revision adicional antes del cierre. |
| 4 | `docs/TASKS.md` conserva la fase "Arquitecto — brief final, pendiente plan de orquestacion" para FEAT-022 pese a que el Agente Feature ya completo T1+T2 (segun el run log). | Actualizar la fase en `docs/TASKS.md` a "Feature implementado — Revisor OK" antes de mover la fila a Completadas, para mantener el tablero alineado con el estado real. |

## Siguiente paso

- [x] Revisor — aprobado, sin bloqueantes.
- [ ] Documentador — validar/cerrar `docs/modules/ficha-empleados.md` y `docs/user/ficha-empleados.md` (ver observacion #3) y `docs/INDEX.md` si aplica.
- [ ] AgentSj — actualizar fase en `docs/TASKS.md` (ver observacion #4) y mover FEAT-022 a "Completadas" tras el cierre documental.
