# Modulo Requisiciones

## Objetivo

Gestionar el flujo de requisicion de personal por area, desde la solicitud inicial del usuario hasta la gestion operativa y el cambio de estados por parte de gestion humana.

## Alcance actual

- Tablero `Requisiciones` en sidebar:
  - **Gestion / Dashboard / Parametros / Autorizacion gerencia:** hogar canonico **Gestion humana** (`SidebarVisibilityService`)
  - **Solicitar / Mis requisiciones:** area base del usuario (`users.area_key`)
  - Otras areas: solo si `view.board.{area}.requisiciones` y el usuario **no** tiene alcance GH global
- Subtableros internos:
  - `Dashboard`
  - `Solicitar`
  - `Mis requisiciones` (permiso `requisitions.tab.seguimiento`)
  - `Gestion`
  - `Autorizacion gerencia` (permiso `requisitions.approve.management`)
  - `Parametros`
- Solicitar y Mis requisiciones operan siempre en `users.area_key`
- Gestión y Dashboard requieren tablero visible en alcance + permiso funcional. **Gestión** y **Dashboard** (con `requisitions.tab.dashboard`) muestran solicitudes de **todas las areas**. El dashboard renderiza KPIs y graficos **ApexCharts** via Vite (`resources/js/requisitions-dashboard-charts.js`; datos en `#requisitions-chart-data`).
- Historial de cambios de estado
- Historial de cambios de campos en edicion de gestion (fecha, usuario, valor anterior y nuevo)
- Catalogos administrables: cargos, motivos, ciudades, tipos de cliente, tipos de programacion, uniformes, tipos de contrato y **correos de notificacion** (los clientes se gestionan en Comercial → Clientes)
- **Encargados de seleccion** (solo tablero GH → Parametros): usuarios reales de Gestion humana habilitados con toggles; ya no existe catalogo `requisition_recruiters`
- Notificacion por correo al **crear** una solicitud (`PersonalRequisitionNotification`, cola `ShouldQueue`) segun tipo **Nueva requisicion** en Parametros
- Aviso a gerencia en motivo **Cargo nuevo** (`PersonalRequisitionManagementApprovalMail`, envio sincrono) segun tipo **Autorizacion requisicion cargo nuevo**
- Notificacion por correo al **cambiar de estado** hacia el solicitante (`PersonalRequisitionStatusChangedMail`)

## Reglas de negocio actuales

- El usuario solo puede solicitar en su `area_key` con permiso `requisitions.tab.solicitar`
- **Mis requisiciones** es solo lectura y muestra requisiciones del **area base** del usuario (`users.area_key`).
- **Gestión** lista **todas** las solicitudes de **todas** las areas (vista transversal para validadores GH).
- El filtro `Solo mis solicitudes` permite reducir la vista del area a lo creado por el usuario autenticado
- `leader_name` y `requesting_area_key` se toman del usuario autenticado
- `Cliente` se busca en la matriz comercial (`commercial_clients`) cuando el tipo de cliente **no** es *Interno*; para *Interno* (personal administrativo) se asigna automaticamente `Cliente interno SJ Seguridad` en `requisition_clients`
- `Motivo` se selecciona desde parametros
- `Centro de costo` es texto libre
- Cantidad N en Solicitar genera **N filas** con `quantity = 1` y codigos `REQ-{YEAR}-####`
- Existen dos observaciones:
  - `requester_observation`
  - `human_resources_observation`
- `service_structure` (**Estructura del servicio**): texto obligatorio al crear y al guardar en edicion; captura horarios, descansos y condiciones del puesto. Visible/editable en Solicitar y Gestion; incluido en export Excel; **no** en impresion ni correos
- Los estados permitidos incluyen:
  - `pendiente_autorizacion_gerencia` (solo al crear con motivo **Cargo nuevo**)
  - `solicitada`
  - `en_gestion`
  - `contratado`
  - `cancelada`
- Motivo **Cargo nuevo**: la requisicion queda pendiente hasta que gerencia autorice en la pestaña **Autorizacion gerencia**; GH no edita hasta pasar a `solicitada`
- Al cerrar como `contratado`, es obligatorio `hiring_date` y los campos de compensacion marcados como requeridos en la validacion de update
- Al cerrar como `contratado`, tambien son obligatorios `hired_document` (cedula, max 50) y `hired_full_name` (nombre completo, max 255) de la persona contratada — una persona por fila, sin importar `quantity` (alineado a FEAT-011); en cualquier otro estado son `nullable`. Son independientes de `replacement_document`/`replacement_name` (persona a quien se reemplaza, seccion Motivo)
- Guardar una requisicion `contratado` crea/actualiza una entrada 1:1 en `personal_requisition_ficha_entries` (lista de espera de **Ficha empleados**, ver [`docs/modules/ficha-empleados.md`](ficha-empleados.md)); si `hired_document` ya existe en **otra** requisicion, se pide confirmacion (SweetAlert2) antes de reasignar el registro existente
- Despues de creada, la requisicion ya no se modifica desde el flujo del solicitante; solo gestion humana puede hacerlo
- Usuarios con `manage.users` o `manage.area.gestion_humana` pueden crear solicitudes en cualquier modulo sin necesidad de tener `area_key` coincidente

## Encargados de seleccion (Reclutador)

Patron alineado con **Capturadores** en Indicadores (`IndicatorCaptureAccessService`).

| Capa | Detalle |
| --- | --- |
| Servicio | `App\Services\Requisitions\RequisitionSelectionOfficerAccessService` |
| Permiso Spatie | `requisitions.selection_officer` — otorgado/revocado solo por toggle en Parametros GH (no por roles base) |
| Configuracion UI | `GET /requisitions/gestion_humana/parametros` + permiso `manage.requisition.parameters`; partial `resources/views/modules/requisitions/partials/selection-officers.blade.php` |
| Toggle | `PATCH /requisitions/gestion_humana/parametros/encargados-seleccion/{user}` (`requisitions.selection-officers.update`); body `enabled` boolean; 404 si `{module} !== gestion_humana` |
| Select en Gestion | `recruitersForSelect(?currentRecruiterId)` — usuarios GH activos con permiso; si la requisicion ya tiene `recruiter_id`, ese usuario sigue en la lista aunque el toggle este apagado |
| Persistencia | `personal_requisitions.recruiter_id` → FK nullable `users.id`; el formulario **no** escribe `recruiter_name` |
| Presentacion | `PersonalRequisition::displayRecruiterName()` — `recruiter.name`, si no texto legacy `recruiter_name`, si no «—» (vista, export, impresion, change logger con `User::class`) |
| Validacion | `ValidRequisitionRecruiterUser` en Store/Update; `UpdateRequisitionSelectionOfficerRequest` en toggle |

Listado de toggles: usuarios con `is_active = true` y `area_key = gestion_humana`, orden por `name`.

El CRUD generico de parametros con `type=recruiters` fue retirado de `PARAMETER_TYPES` (store/update/destroy → 404).

### Migracion `2026_07_28_112704_requisition_recruiter_id_references_users_drop_catalog`

1. Elimina FK de `recruiter_id` hacia `requisition_recruiters`.
2. Pone `recruiter_id = NULL` en todas las filas de `personal_requisitions` (sin emparejar catalogo antiguo).
3. Crea FK nullable hacia `users.id` (`nullOnDelete`).
4. Elimina tabla `requisition_recruiters` y modelo `RequisitionRecruiter`.

Post-despliegue: GH debe reactivar encargados en toggles; trazabilidad previa solo via `recruiter_name` legacy en fila si existia.

## Notificaciones por correo

### Al crear (Gestion Humana / catalogo)
- Disparo: `RequisitionController::store` tras crear el lote
- Clase: `App\Mail\PersonalRequisitionNotification` (cola)
- Vista: `resources/views/emails/requisitions/requested.blade.php`
- Destinatarios: `RequisitionNotificationRecipientService::emailsForType('new_requisition')` — pivot `req_notif_type_email` + Parametros → **Tipos de notificacion**
- Fallback si el tipo no tiene correos: `desarrollo.tic@sjsp.com.co`

### Autorizacion gerencia (cargo nuevo)
- Disparo: mismo `store` si motivo normalizado es **cargo nuevo** (`RequisitionRequestReasonCatalog`)
- Estado inicial: `pendiente_autorizacion_gerencia`
- Clase: `App\Mail\PersonalRequisitionManagementApprovalMail` (sincrono)
- Destinatarios: `emailsForType('management_approval_cargo_nuevo')`
- CTA: `requisitions.management-approval.show` (login → detalle)

### Bandeja gerencia (enfoque A)
- Sin tabla auxiliar: listado = `personal_requisitions` con `status = pendiente_autorizacion_gerencia`
- Rutas: `RequisitionManagementApprovalController` — index, show, decide
- Permiso: `requisitions.approve.management`
- Aprobar → `solicitada`; rechazar → `cancelada` + correo al solicitante si aplica

### Al cambiar de estado (solicitante)
- Disparo: `RequisitionController::update` **solo si** el estado cambio (`old !== new`)
- Clase: `App\Mail\PersonalRequisitionStatusChangedMail`
- Vista: `resources/views/emails/requisitions/status-changed.blade.php`
- Destinatario: email del usuario `requested_by` (si no hay email, no se envia)
- Contenido: codigo, cargo, cliente, estado anterior → nuevo, observacion GH; CTA a Seguimiento del area solicitante con `q`
- No notifica al catalogo de Parametros ni al fallback GH

### Trazabilidad en edicion (gestion)
- Disparo: `RequisitionController::update` en cada guardado con cambios en campos editables
- Servicio: `App\Services\Requisitions\PersonalRequisitionChangeLogger`
- Tabla: `personal_requisition_change_logs` (agrupado por `change_batch` por cada guardado)
- Registra: fecha/hora, campo (etiqueta legible), valor anterior, valor nuevo, usuario (`changed_by`)
- Incluye `service_structure` con etiqueta legible **Estructura del servicio** cuando GH lo modifica
- UI: panel **Historial de cambios** en `resources/views/modules/requisitions/edit.blade.php`
- El **Historial de estados** sigue siendo independiente y solo registra transiciones de estado

### Export Excel
- Gestion y Mis requisiciones / seguimiento usan `App\Exports\PersonalRequisitionFullExport` (sobre `BaseExport`) con **todas las columnas operativas** de `personal_requisitions`, incluyendo compensacion; relaciones (cargo, cliente, motivo, etc.) se exportan como **nombres legibles**
- El export de Gestion/Seguimiento **no** incluye `hired_document`/`hired_full_name` como columnas propias; esos datos se consultan en el export dedicado de **Ficha empleados** (`App\Exports\PersonalRequisitionFichaEntryExport`, ver [`docs/modules/ficha-empleados.md`](ficha-empleados.md))
- Filtros del export = mismos que la vista: busqueda (`q`), estado, **fecha inicio / fecha fin** sobre `request_date` (opcionales; sin fechas exporta todo el universo filtrado por el resto). En seguimiento tambien aplican cliente, ciudad y solo mis solicitudes
- Filtros de fecha en panel de Gestion y Seguimiento; al pulsar Buscar filtran la tabla y el Excel

### Compartido
- Ambos mailables usan cola (`ShouldQueue`)
- Fallos de envio se registran en log; la solicitud HTTP sigue siendo exitosa
- Pruebas locales: Mailpit + `MAIL_MAILER=smtp` puerto `1025` (ver [`LOCAL_SETUP.md`](../LOCAL_SETUP.md))

## Orden de campos y Matriz de Visibilidad (Sprint 2026-04-29)

El formulario incluye matriz de compensacion y seguimiento, con visibilidad restringida segun el rol:

### Campos visibles para Solicitantes (Perfil de área)
1. Lider / solicitante (readonly)
2. Area solicitante (readonly)
3. Cargo solicitado
4. Sexo
5. Cantidad
6. Cedula / Nombre a quien reemplaza (opcional)
7. Area operativa
8. Motivo
9. Cliente (buscador sobre matriz comercial; min. 2 caracteres)
10. Ciudad
11. Tipo de cliente
12. Tipo de programacion
13. Perfil requerido
14. Dotacion requerida
15. **Estructura del servicio** (`service_structure`; obligatorio; seccion 4, debajo de perfil/dotacion)
16. Centro de costo
17. Observaciones del solicitante

### Campos visibles y editables tambien para Gestión Humana (GH)
- Mismos campos del solicitante en el formulario de edicion (incluido **Estructura del servicio**), mas:

### Campos exclusivos para Gestión Humana (GH)
18. **Compensación**: Tipo de contrato, Duración, Salario Base, Auxilios (Transporte, Movilidad), Bonificaciones, Contrato de Arrendamiento.
19. **Seguimiento**: Reclutador / encargado de seleccion (`recruiter_id` → usuario GH habilitado).
20. **Cierre**: Fecha de contratación, Observaciones de GH.

## Identificación Visual y UI

- **Estados por colores**: indicadores visuales (status-pills) en tablas e historial:
  - `solicitada`: Pendiente.
  - `en_gestion`: En proceso por GH.
  - `contratado`: Proceso finalizado con éxito.
  - `cancelada`: Solicitud descartada.
- **Layout Fijo**: barras de navegacion (Modulo y Sub-tableros) fijas en la parte superior.
- **Formulario Solicitar**: secciones numeradas (motivo, cargo, servicio, perfil, administrativo); cantidad visible solo para motivos *Cargo nuevo* y *Servicio nuevo* (demas motivos envian 1); barra lateral con checklist y acciones destacadas al pie.
- **Formulario Edicion (Gestion)**: mismo layout de secciones numeradas que Solicitar, mas bloques de compensacion/contrato y gestion humana; panel lateral con historial de estados, historial de cambios de campos y guia operativa.
- **Gestion**: panel de filtros (busqueda servidor + pills de estado a la derecha); tabla con DataTables (busqueda en tabla, selector de registros, orden por fecha desc).
- **Seguimiento**: mismo panel de filtros que Gestion (busqueda, pills de estado, cliente, ciudad, alcance mis/todas); resumen de resultados y exportacion Excel en la cabecera del panel.
- **Dashboard Compacto**: indicadores KPI en una sola fila (Total, Solicitadas, En gestion, Contratadas, Canceladas); alcance consolidado de todas las areas via `usesGlobalDashboardScope`.
- **Toasts**: feedback UI en esquina inferior derecha (aparte del correo).

## Rutas

Definidas en [`routes/modules/requisitions.php`](../../routes/modules/requisitions.php):

- `GET /requisitions/{module}/dashboard`
- `GET /requisitions/{module}/solicitar`
- `POST /requisitions/{module}/solicitar`
- `GET /requisitions/{module}/clientes/buscar` — JSON de clientes comerciales para el formulario (param `q`, min. 2 caracteres)
- `GET /requisitions/{module}/seguimiento`
- `GET /requisitions/{module}/gestion`
- `GET /requisitions/{module}/gestion/{requisition}/editar`
- `GET /requisitions/{module}/gestion/{requisition}/imprimir`
- `PATCH /requisitions/{module}/gestion/{requisition}`
- `GET /requisitions/{module}/parametros`
- `PATCH /requisitions/gestion_humana/parametros/encargados-seleccion/{user}` — toggle encargado (`requisitions.selection-officers.update`)
- `POST /requisitions/{module}/parametros/{type}`
- `PATCH /requisitions/{module}/parametros/{type}/{parameterId}`
- `DELETE /requisitions/{module}/parametros/{type}/{parameterId}` (`type=recruiters` ya no existe → 404)

## Permisos relacionados

- `view.board.{area}.requisiciones`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`
- `manage.requisition.parameters` (Parametros GH, incl. toggles encargados)
- `requisitions.selection_officer` (aparecer en select Reclutador; vía toggle en Parametros GH o asignacion directa por super-admin)
- `manage.requisitions` (legacy; no asignar en Admin; equivalente practico a `requisitions.tab.gestion` + tablero visible)
- `manage.area.gestion_humana` (Otorga visibilidad completa de campos y acceso a tablero GH)
- `manage.users`

## Validacion (Form Requests)

- `StorePersonalRequisitionRequest` / `UpdatePersonalRequisitionRequest`: `service_structure` → `required|string`; `recruiter_id` nullable, `exists:users,id`, regla `ValidRequisitionRecruiterUser` (habilitado o mismo ID ya guardado)
- `UpdateRequisitionSelectionOfficerRequest`: `enabled` required boolean
- HTML `required` en textarea de Solicitar (`form-fields-requester`) y Gestion (`form-fields`)
- Registros legacy con `NULL` en BD: al reabrir en Gestion el campo es obligatorio al guardar

## Tablas implicadas

- `personal_requisitions` (compensacion, `recruiter_id` FK → `users.id` nullable, `recruiter_name` legacy solo lectura, cierre con `hiring_date`, `service_structure` text nullable, `hired_document`/`hired_full_name` nullable — persona contratada, ver Ficha empleados)
- `personal_requisition_ficha_entries` (lista de espera 1:1 con `personal_requisitions`; ver [`docs/modules/ficha-empleados.md`](ficha-empleados.md))
- `personal_requisition_status_logs`
- `personal_requisition_change_logs` (trazabilidad de campos editados en gestion)
- `requisition_positions`
- `requisition_request_reasons`
- `requisition_clients` (tabla interna de enlace; se alimenta automaticamente desde matriz comercial al crear/editar; **no** se administra en Parametros)
- `commercial_clients` (fuente del buscador en Solicitar y Gestion)
- `requisition_cities`
- `requisition_client_types`
- `requisition_programming_types`
- `requisition_uniforms`
- `requisition_contract_types`
- `requisition_notification_emails`

Tabla eliminada (FEAT-011): `requisition_recruiters`.

## Riesgos

- **Visibilidad Sensible**: campos de compensacion criticos; cambios en `showHumanResourcesFields` pueden exponer salarios a solicitantes.
- **Validacion de Cierre**: `hiring_date` y compensacion requerida cuando el estado es `contratado`.
- **Cola de correo**: con `QUEUE_CONNECTION=database` hace falta `queue:work` o los mails no salen (Mailpit / SMTP).

## Deuda / pendientes (fuera del corte Mailpit)

- Motivo “Reemplazo” o “Movimiento interno” exige cedula y nombre (IDs resueltos por nombre de catalogo, no hardcode).
- `PersonalRequisitionPolicy` registrada pero no usada en el controller.
- Cobertura de tests acotada (sin factory dedicada; sin print/dashboard/parametros ampliados).
- Columna legacy `recruiter_name` solo para lectura/presentacion cuando no hay `recruiter_id` (datos anteriores a FEAT-011).

## Correcciones aplicadas (Sprint final 2026-04 y mantenimiento 2026-07)

- Ampliacion de modelo y matriz de visibilidad.
- Sistema de colores y navegacion fija.
- Notificaciones toast en UI.
- Mailpit documentado; CTA del correo con filtro `q`; validacion `email` en parametros tipo `emails`.
- Persistencia de `recruiter_id` en mass assignment.
- Correo al solicitante cuando GH cambia el estado (`PersonalRequisitionStatusChangedMail`).
- Campo **Cliente** en Solicitar/Gestion: buscador sobre `commercial_clients` (`commercial-client-picker.blade.php`, `comercial-client-picker.js`); puente `CommercialClientBridge` resuelve `client_id` en `requisition_clients` por nombre al validar (`ResolvesCommercialClient`).
- Eliminado el tablero **Clientes** en Parametros de requisiciones; la fuente maestra es Comercial → Clientes.
- **FEAT-005 (2026-07-24):** campo `service_structure` / **Estructura del servicio** en Solicitar y Gestion (seccion 4), validacion `required`, label en `PersonalRequisitionChangeLogger`.
- **FEAT-006 (2026-07-24):** export Excel completo (`PersonalRequisitionFullExport`) en Gestion y Seguimiento; filtros `date_from`/`date_to` sobre `request_date` en panel (tabla + export).
- **FEAT-010 (2026-07-27):** dashboard GH migra a **ApexCharts** via Vite (`resources/js/requisitions-dashboard-charts.js` + `apex-defaults.js`); Chart.js retirado.
- **FEAT-012 (2026-07-28):** autorizacion gerencia para motivo cargo nuevo (`pendiente_autorizacion_gerencia`); pestaña Autorizacion gerencia; tipos de notificacion en Parametros (`new_requisition`, `management_approval_cargo_nuevo`).
- **FEAT-011 (2026-07-28):** encargados de seleccion = usuarios GH + permiso `requisitions.selection_officer` (toggles en Parametros); `recruiter_id` referencia `users`; migracion elimina `requisition_recruiters`; export/impresion/historial usan `displayRecruiterName()`.
- **FEAT-020 (2026-07-30):** columnas `hired_document`/`hired_full_name` (persona contratada) obligatorias solo con `status=contratado`, con deteccion de cedula duplicada (`App\Rules\Requisitions\HiredDocumentNotDuplicated`) y confirmacion via SweetAlert2; upsert 1:1 en `personal_requisition_ficha_entries` (`App\Services\Requisitions\PersonalRequisitionFichaSync`) que alimenta el tablero nuevo **Ficha empleados**; labels nuevos "Cedula contratado"/"Nombre contratado" en `PersonalRequisitionChangeLogger`. Riesgo conocido: `quantity > 1` en Gestion sigue permitido y una fila asi marcada Contratado solo registra una persona (fuera de alcance v1).
- **Navegacion canonica (2026-08-03):** tablero Requisiciones GH consolidado en sidebar bajo Gestion humana; ver `SidebarVisibilityService` y `docs/ACCESS_CONTROL.md`.

## Referencias

- Guia de usuario: [`docs/user/requisitions.md`](../user/requisitions.md)
- Modulo relacionado: [`docs/modules/ficha-empleados.md`](ficha-empleados.md) (lista de espera y ficha de personas contratadas)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
