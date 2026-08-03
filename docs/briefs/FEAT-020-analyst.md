# Analista — FEAT-020

> Salida del Agente Analista antes del Feature Brief final (`docs/briefs/FEAT-020.md`). Sin implementacion de codigo.

## Contexto recibido

| Campo | Valor |
| --- | --- |
| **Feature ID** | FEAT-020 |
| **Origen** | `@agent-sj` (2026-07-30) |
| **Pantalla** | `requisitions/gestion_humana/gestion/{requisition}/editar` — `resources/views/modules/requisitions/edit.blade.php` |
| **Modulo(s)** | `requisitions` (existente) + **Ficha empleados** (tablero nuevo, area Gestion humana) |
| **Run log** | [`docs/runs/FEAT-020-run-log.md`](../runs/FEAT-020-run-log.md) |

**Solicitud del usuario (resumen literal):**

1. Al cambiar el estado de una requisicion a **Contratado** (Gestion → Editar), exigir dos campos nuevos: **cedula** y **nombre completo** de la persona a contratar.
2. Esos datos se guardan en la requisicion **y** en una **lista de espera** (tabla aparte) de empleados pendientes por ingreso de datos al sistema.
3. Un rol/usuario **contratador** podra revisar quienes estan pendientes en esa lista.
4. Crear un tablero modular nuevo **Ficha empleados** en Gestion Humana, con pestaña inicial **Empleados** mostrando la tabla de la lista de espera.

## Hallazgos tecnicos relevantes (para enmarcar las preguntas)

| Hallazgo | Detalle | Por que importa |
| --- | --- | --- |
| Cantidad ya es 1 por fila | Desde FEAT-011, `Solicitar` genera **N filas** con `quantity = 1` cada una (`RequisitionController::store`, comentario `// Ahora cada registro es individual`). Sin embargo `UpdatePersonalRequisitionRequest` sigue permitiendo `quantity` `min:1|max:999` **editable en Gestion**. | Si GH deja `quantity > 1` en una fila y la marca **Contratado**, ¿la fila representa 1 o varias personas? Afecta si se piden cedula/nombre unicos o una lista. |
| Ya existen `replacement_document` / `replacement_name` | Campos existentes en `personal_requisitions`, obligatorios hoy solo cuando el **motivo** es *Reemplazo* o *Movimiento interno* (`ResolvesReplacementPersonFields`). Semanticamente son "cedula/nombre de la persona a quien se reemplaza", **no** la persona contratada. | Riesgo de confusion: la nueva "cedula/nombre completo" del contratado **no puede reutilizar** estos campos sin ambiguedad; probablemente se necesitan columnas nuevas (`hired_document`, `hired_name` o similar). Se documenta como supuesto si no se aclara. |
| `UpdatePersonalRequisitionRequest` ya condiciona campos por `STATUS_CONTRATADO` | Patron existente: `$isHired = $this->input('status') === PersonalRequisition::STATUS_CONTRATADO` controla `required`/`nullable` de `contract_type_id`, `hiring_date`, salarios. | La nueva regla de cedula/nombre debe seguir el mismo patron condicional; no rompe si no se documenta distinto. |
| No existe el concepto "contratador" en el codigo | Sin permisos, roles ni menciones a "contratador" en `config/access.php` ni en el codigo. Patron mas cercano: **Encargados de seleccion** (`requisitions.selection_officer`), permiso otorgado por **toggle** a usuarios reales de GH activos (no por rol). | Hay que decidir si "contratador" es: (a) cualquiera con `requisitions.tab.gestion` (ya gestionan Contratado), (b) un permiso nuevo tipo toggle igual a Encargados de seleccion, o (c) un permiso de solo lectura distinto de quien edita la requisicion. |
| Tableros son configuracion central | `config/access.php` define `boards` (lista global de tableros: `dashboard`, `indicadores`, `requisiciones`, `gestion_clientes`, `suministros`, `documentos`) y por area en `other_areas.gestion_humana.subgroups.boards.permissions` (hoy `view.board.gestion_humana.requisiciones`, `view.board.gestion_humana.dashboard`). Tableros con varias pestañas tienen su propio `*_tabs` (ej. `gestion_clientes_tabs`, `requisition_tabs`). | Crear **Ficha empleados** implica: nueva entrada en `boards`, nuevo permiso `view.board.gestion_humana.ficha_empleados`, posible `ficha_empleados_tabs` con `empleados` como unica pestaña inicial. Es un archivo compartido (`config/access.php`) — requiere flag `shared-files` en `docs/TASKS.md` (ya anotado en la fila FEAT-020). |
| Exportacion Excel es convencion obligatoria | `AGENTS.md`: toda exportacion usa `BaseExport` + `<x-export-excel>`. Gestion/Seguimiento de requisiciones ya exportan con `PersonalRequisitionFullExport`. | Si Ficha empleados necesita exportar, debe seguir el mismo patron; se pregunta si aplica en v1. |

## Preguntas abiertas

Responde cada punto para cerrar el brief:

1. **Cantidad > 1 en la requisicion:** cuando una fila de `personal_requisitions` tiene `quantity > 1` (GH la aumento en Gestion) y se marca **Contratado**, ¿se debe pedir **una sola** cedula/nombre (asumiendo que en la practica cada fila = 1 persona, como ya ocurre desde FEAT-011), o el formulario debe permitir **capturar varias personas** (cedula + nombre por cada unidad de `quantity`)? Si la respuesta es "una sola", ¿bloqueamos `quantity > 1` al marcar Contratado o simplemente se ignora el exceso?
2. **Quien es "contratador":** ¿es (a) cualquier usuario con `requisitions.tab.gestion` (los mismos que ya gestionan y marcan Contratado), (b) un permiso/rol nuevo — con el mismo patron de **toggle** que Encargados de seleccion (`requisitions.selection_officer`) — para un subconjunto de GH, o (c) un permiso de **solo lectura** distinto (revisa la lista de espera pero no edita requisiciones)? Esto define si Ficha empleados necesita permiso propio o reutiliza uno existente.
3. **Columnas de la lista de espera (pestaña Empleados):** ademas de cedula y nombre completo, ¿que otras columnas necesita el contratador para identificar y priorizar? Candidatas segun contexto de la requisicion: cargo, cliente, ciudad, fecha de contratacion (`hiring_date`), area solicitante, codigo de requisicion (`code`), fecha en que entro a la lista, estado (pendiente/ingresado), quien la marco.
4. **Transicion pendiente → ingresado:** ¿como se marca que un registro de la lista de espera **ya fue ingresado** al sistema (por ejemplo, cuando se crea su usuario/ficha en otro modulo)? ¿Es un boton "Marcar como ingresado" dentro de Ficha empleados, un cambio de estado con fecha y usuario responsable, o una integracion automatica con otro modulo (por ejemplo, alta de usuarios) que aun no existe? Si no hay modulo de ingreso real todavia, ¿el "ingresado" es solo un marcador manual sin efecto en otras tablas?
5. **Duplicados de cedula:** si la misma cedula aparece en **otra fila** de la lista de espera (por ejemplo, la persona ya fue registrada por otra requisicion, o se corrige y se vuelve a guardar), ¿debe bloquearse como error, permitirse con aviso, o simplemente actualizar/vincular el registro existente en vez de crear uno nuevo? ¿La validacion de duplicados es solo dentro de la lista de espera o tambien contra empleados ya "ingresados"?
6. **Campos en `personal_requisitions` ademas de la tabla de espera:** ¿los campos cedula/nombre completo del contratado deben ser **columnas nuevas** en `personal_requisitions` (distintas de las ya existentes `replacement_document`/`replacement_name`, que hoy significan "persona reemplazada", no "persona contratada"), o el requisito de "guardarlos tambien en la requisicion" se cumple reutilizando algun campo existente? Se recomienda **columnas nuevas** para evitar ambiguedad semantica; confirmar nombre esperado (ej. `hired_document`, `hired_full_name`).
7. **Notificaciones:** ¿debe enviarse correo (siguiendo el patron `ShouldQueue` ya usado en el modulo) cuando entra un nuevo registro a la lista de espera, o cuando se marca como ingresado? ¿A quien (contratador, correos de Parametros, el mismo GH que gestiono)? Si no se especifica, se asume **sin notificacion** en v1.
8. **Export Excel:** ¿la pestaña Empleados de Ficha empleados necesita boton de exportacion (`<x-export-excel>` / `BaseExport`) desde el inicio, o queda para una iteracion posterior?
9. **Edicion posterior de una requisicion ya Contratada:** si GH reabre una requisicion ya marcada Contratado y **cambia** la cedula/nombre (correccion), ¿se debe **actualizar** el registro correspondiente en la lista de espera (1:1 con la requisicion) o crear uno nuevo? ¿Que pasa si GH cambia el estado de Contratado a otro estado (por error) — se elimina o inactiva el registro de la lista de espera?
10. **Alcance de "empleados" en Ficha empleados v1:** ¿la pestaña Empleados solo debe mostrar **pendientes** (lista de espera activa) o tambien un historico de ya **ingresados**? Si es solo pendientes, ¿donde se consulta el historico despues?

## Fuera de alcance (propuesta analista, confirmar)

- Modulo real de **alta/ingreso de empleados** al sistema (creacion de usuario, expediente completo, nomina, etc.) — Ficha empleados en v1 es solo **lista de espera + marcador de seguimiento**, no un modulo de gestion de personal completo.
- Cambios a los campos existentes `replacement_document` / `replacement_name` (motivo Reemplazo / Movimiento interno) — se mantienen intactos; los campos nuevos de "contratado" son independientes.
- Pestañas adicionales en Ficha empleados mas alla de **Empleados** (ej. Parametros, Dashboard) — quedan para iteraciones futuras salvo que el usuario las pida ahora.
- Integracion automatica con otros modulos (nomina, usuarios del sistema, indicadores) a partir del "ingreso" — hasta no exista ese modulo, el marcador es informativo.
- Notificaciones por correo, salvo confirmacion explicita (pregunta 7).
- Export Excel, salvo confirmacion explicita (pregunta 8) — si se confirma, sigue el patron `BaseExport`/`<x-export-excel>` obligatorio del proyecto.

## Supuestos temporales (si el usuario no responde aun)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Cada fila de `personal_requisitions` representa 1 persona contratada (alineado con el comportamiento real desde FEAT-011); se captura **una** cedula y **un** nombre por fila, sin importar el valor de `quantity`. | Si el negocio realmente permite `quantity > 1` con varias personas por fila, el diseño de formulario y de la tabla de espera (1:1 vs 1:N) cambia por completo. |
| 2 | "Contratador" = usuarios con `requisitions.tab.gestion` (los mismos que ya gestionan requisiciones GH); no se crea permiso nuevo tipo toggle. | Si el negocio quiere un subconjunto especifico de GH (como Encargados de seleccion), habria que agregar un permiso/toggle nuevo y una pantalla de configuracion en Parametros. |
| 3 | Los campos cedula/nombre completo del contratado son **columnas nuevas** e independientes de `replacement_document`/`replacement_name`. | Si el usuario esperaba reutilizar los campos de reemplazo, se duplicaria informacion o se confundiria el dato mostrado en Gestion. |
| 4 | La transicion "pendiente → ingresado" es un **marcador manual** (boton + fecha + usuario) dentro de Ficha empleados, sin automatizacion con otro modulo. | Si se espera integracion automatica con altas de usuario u otro sistema, el alcance de la feature crece significativamente (fuera de una entrega simple). |
| 5 | Duplicados de cedula en la lista de espera generan **aviso** (no bloqueo duro), permitiendo que GH continue si es un caso legitimo (ej. re-contratacion). | Si el negocio exige unicidad estricta de cedula, se necesita regla de validacion distinta y decidir que pasa con historicos. |
| 6 | Ficha empleados v1 **no** incluye notificaciones por correo ni export Excel; solo tabla en pantalla con filtros basicos. | Si el negocio los espera desde el dia uno, quedarian como deuda inmediata detectada en la primera revision. |

## Estado

- [x] Preguntas criticas respondidas por usuario (2026-07-30) — listo para Arquitecto
- [ ] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario (2026-07-30)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Cantidad | **Una cedula/nombre por fila** (cada fila = 1 persona, alineado FEAT-011). |
| 2 | Contratador | **Dos permisos nuevos:** uno de **lectura** y uno de **edicion** sobre Ficha empleados, **distintos** de quien edita requisiciones (`requisitions.tab.gestion`). |
| 3 | Ingresado al sistema | **v1 sin estado "ingresado"** al sistema externo. |
| 4 | Duplicados cedula | **Alerta de decision:** cada registro amarrado a una requisicion; si cedula existe, usuario confirma y **actualiza** el registro existente. |
| 5 | Alcance lista / ficha | Tabla **solo pendientes** (lista de espera). Accion **Agregar a ficha empleados** mueve el registro a ficha y **desaparece** de la lista de espera. |
| 6 | Export | **Si**, export Excel v1 (`BaseExport` + `<x-export-excel>`). |

### Pendiente de confirmar en brief (interpretacion AgentSj)

- Tras **Agregar a ficha**, los empleados ya no estan en espera: el tablero **Ficha empleados → Empleados** debe permitir ver **pendientes** y, con permiso edicion, **empleados ya en ficha** (p. ej. pills/filtro *Pendientes* \| *En ficha*), salvo que el usuario indique otro layout.

### Sin respuesta explicita (supuesto Arquitecto)

- Columnas lista de espera: codigo requisicion, cedula, nombre, cargo, cliente, ciudad, fecha contratacion, quien registro.
- Campos nuevos en `personal_requisitions`: columnas dedicadas (`hired_document`, `hired_full_name`), no reutilizar `replacement_*`.
- Edicion requisicion ya Contratada: actualizar registro 1:1 en lista de espera; revertir estado Contratado → inactivar/retirar de espera (Arquitecto define).
- Notificaciones correo: **no** en v1.
