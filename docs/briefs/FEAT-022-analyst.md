# Preguntas del Analista — FEAT-022

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

Cuando en Gestion Humana → Requisiciones se marca una requisicion como **Contratado**, hoy se crea una fila **pendiente** en `personal_requisition_ficha_entries` (`moved_to_ficha_at = null`), visible en `gestion-humana/ficha-empleados/empleados?estado=pendientes`. Cada fila pendiente ya tiene `hired_document`/`hired_full_name` capturados en la requisicion, y esta 1:1 con una `personal_requisition_id` (nunca es null en pendientes — solo el alta manual vía "Nuevo empleado" crea entradas con `personal_requisition_id = null`, y esas nacen directamente **en ficha**, nunca pendientes).

Hoy, para pasar un pendiente a ficha, el usuario da clic en **"Agregar a ficha empleados"** (fila de la tabla), que dispara un SweetAlert de confirmacion y un `PATCH .../{fichaEntry}/agregar` **sin formulario**: solo marca `moved_to_ficha_at` + `moved_to_ficha_by` y llama a `EmployeeFichaProfilePrefill::prefillForEntry()` (autogenera el perfil con lo que trae la requisicion, sin intervencion humana).

El usuario pide:

1. Renombrar el boton a **"Gestionar Empleado"**.
2. Que el clic **no** ejecute el PATCH directo, sino que abra un formulario editable en `gestion-humana/ficha-empleados/empleados/nuevo` con los datos de la requisicion **precargados**.
3. Que el paso a ficha (`moved_to_ficha_at`) ocurra recien cuando el usuario complete/corrija datos y presione **"Crear empleado"**, y que el registro resultante aparezca en el listado principal `empleados` (en ficha), no en pendientes.

## Hallazgo relevante (no es pregunta, es contexto para el Arquitecto)

Ya existe una pantalla de edicion de ficha para pendientes: al hacer clic en la **fila** (no en el boton) de un pendiente en el listado, se abre `GET .../{fichaEntry}/ficha` (`editFicha()`), que **ya autogenera el perfil prefilled** si no existe (misma logica de `EmployeeFichaProfilePrefill`) y muestra un formulario editable completo (`edit-ficha.blade.php`, boton **"Guardar ficha"**). Ese guardado (`updateFicha()`) **no** mueve el registro a ficha (no toca `moved_to_ficha_at`); el "Volver" de esa pantalla ya distingue si el origen es pendiente o en-ficha.

Es decir, hoy conviven dos caminos hacia "editar antes de mover": (a) clic en fila → `/{id}/ficha` (edicion completa, no mueve), y (b) boton → `/agregar` (mueve, sin edicion). La solicitud pide fusionarlos usando la URL `/nuevo` (hoy exclusiva para alta manual **sin** requisicion, donde `store()` siempre crea una fila nueva con `personal_requisition_id = null`). Esto implica que `/nuevo` tendria que soportar dos modos: alta manual pura (como hoy) y "completar" una fila pendiente **existente** (nueva funcionalidad), sin duplicar la fila ni el `personal_requisition_id`.

## Preguntas abiertas

Responde cada punto para cerrar el brief:

1. **Reutilizar `/nuevo` vs. extender `/{id}/ficha`:** Dado que ya existe una pantalla de edicion prefilled para pendientes (`/{id}/ficha`, sin mover a ficha), ¿prefieres que "Gestionar Empleado" (a) abra realmente `/nuevo` como pediste literalmente (mismo formulario de alta manual, pero en "modo completar" para esa fila), o (b) que reutilicemos `/{id}/ficha` agregando ahi un boton "Crear empleado" que sí mueva el registro? La opcion (a) implica mantener dos pantallas de edicion de perfil muy similares; la (b) evita duplicar formulario pero no usa la URL `/nuevo` que pediste. ¿Cual prefieres?
2. **Identidad de la fila al guardar en `/nuevo`:** si se usa `/nuevo`, necesitamos saber que fila pendiente se esta completando (para no crear una fila duplicada). ¿Cual es la URL exacta que debe abrir el boton? Propuesta: `gestion-humana/ficha-empleados/empleados/nuevo?desde={fichaEntryId}`. ¿Te sirve ese formato o prefieres otro (ej. ruta separada `/nuevo/{fichaEntry}`)?
3. **Cedula y nombre — ¿editables o de solo lectura?** Cuando el formulario viene precargado desde una requisicion, ¿la cedula (`hired_document`) y el nombre completo (`hired_full_name`) deben quedar **bloqueados** (solo lectura, ya que vienen de un dato oficial de la requisicion) o el usuario puede **corregirlos** ahi mismo (ej. error de tipeo al marcar Contratado)? Si se permiten editar, ¿esa correccion debe reflejarse tambien en la requisicion original (`personal_requisitions.hired_document/hired_full_name`) o solo en la ficha del empleado?
4. **Redireccion tras "Crear empleado":** confirmas que el guardado exitoso debe llevar al **listado principal en ficha** (`gestion-humana/ficha-empleados/empleados`, estado en_ficha) y no al detalle de ficha del empleado recien creado (`/{id}/ficha`)? Hoy el alta manual (`store()` sin requisicion) redirige al detalle de ficha (`/{id}/ficha`) con mensaje de exito, no al listado; ¿quieres el mismo comportamiento aqui o un mensaje/destino distinto?
5. **Boton y endpoint `promote` (`PATCH .../{id}/agregar`) y su SweetAlert:** ¿lo eliminamos por completo (ya no habria forma de mover a ficha "de un clic sin editar") o lo dejamos como atajo alternativo para casos donde no se necesita tocar nada? Si se elimina, ¿confirmas que perder esa via directa es aceptable para el flujo diario de Gestion Humana?
6. **Permiso:** ¿se mantiene `ficha_empleados.manage` como unico permiso requerido para "Gestionar Empleado" (igual que hoy para `promote` y para `/nuevo`), sin crear un permiso nuevo?
7. **Cancelar / volver sin guardar:** si el usuario entra a "Gestionar Empleado" y da clic en "Volver" (o cierra sin enviar el formulario), ¿la fila debe permanecer en **Pendientes** tal cual estaba (sin ningun cambio), incluyendo si ya habia datos autogenerados por el prefill anterior? Confirmamos que no debe quedar a medio mover (nunca `moved_to_ficha_at` parcial).
8. **Cedula duplicada al gestionar:** si al guardar el usuario deja o cambia la cedula a un valor que ya existe en OTRA fila de ficha, ¿debe bloquear con error (como el `Rule::unique` actual en alta manual) o debe permitir confirmar el duplicado igual que la requisicion original (SweetAlert de confirmacion, patron `HiredDocumentNotDuplicated` de FEAT-020)?
9. **Titulo/mensaje de la pantalla:** el formulario en `/nuevo` hoy se llama "Nuevo empleado" con texto "Registro manual sin requisición…". Cuando se abre para completar un pendiente que **sí** viene de requisicion, ¿el titulo debe decir algo distinto (ej. "Gestionar empleado — {nombre}") para no confundir al usuario, o dejamos el mismo titulo generico?
10. **Alcance de "editar datos faltantes":** ¿el formulario debe mostrar los mismos campos que hoy tiene `/nuevo` (identico a `ficha-form-fields.blade.php`), o hay campos adicionales/distintos que quieras exponer especificamente para el caso "viene de requisicion" (ej. mostrar codigo/cliente/cargo de la requisicion como referencia de solo lectura, ya que hoy esos datos solo se ven en el listado y en `/{id}/ficha`)?

## Supuestos temporales (si el usuario no responde aun)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Se reutiliza la URL `/nuevo` literalmente (opcion (a) de la pregunta 1) via `?desde={fichaEntryId}`, y el `create()`/`store()` actuales se extienden en vez de crear rutas nuevas. | Si el usuario en realidad prefiere extender `/{id}/ficha` (opcion (b)), habria que rehacer la implementacion y posiblemente dejar una pantalla duplicada sin uso. |
| 2 | Cedula y nombre quedan **editables** (no readonly) en el formulario precargado, igual que en alta manual, y la correccion solo se guarda en la ficha (no se propaga a `personal_requisitions`). | Si el negocio esperaba bloquear esos campos por ser "dato oficial" de la requisicion, se perderia esa garantia de integridad; y si esperaba propagar la correccion a la requisicion, quedaria informacion desincronizada entre requisicion y ficha. |
| 3 | El endpoint `promote` (`PATCH .../agregar`) y su SweetAlert se **eliminan** (ya no hay boton de "mover sin editar"); toda promocion pasa por el formulario. | Si el negocio queria mantenerlo como atajo para casos triviales, se removeria una funcionalidad usada activamente, afectando el flujo diario. |
| 4 | Tras "Crear empleado" exitoso, se redirige al **listado principal en ficha** (`estado=en_ficha`) con mensaje de exito, distinto al comportamiento actual de alta manual (que redirige a `/{id}/ficha`). | Si el usuario prefiere ver el detalle recien creado (como en alta manual), se generaria una navegacion inconsistente entre los dos flujos de creacion de empleado. |

## Estado

- [ ] Todas las preguntas respondidas — listo para Arquitecto
- [x] Pendiente confirmacion comportamiento (texto vs flujo completo)

## Respuestas del usuario

| # | Pregunta | Respuesta |
| --- | --- | --- |
| 1 | Pantalla (`/nuevo` vs `/{id}/ficha`) | *Ambigua:* "solo es reemplazar el texto del boton" |
| 4 | Redirect tras guardar | **Listado principal en ficha** |
| 5 | Endpoint `promote` | *Ambigua:* "solo es cambiarle el texto" |
| 3 | Cedula/nombre | **Editables; correccion solo en ficha** |

Preguntas 2, 6, 7, 8, 9, 10 sin respuesta explicita.

### Interpretacion AgentSj (pendiente confirmacion)

La solicitud original pide cambio de **comportamiento**: clic abre formulario precargado, guardar con **Crear empleado** mueve a ficha y redirige al listado. Las respuestas "solo texto" pueden ser malentendido del cuestionario.
