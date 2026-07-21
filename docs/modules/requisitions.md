# Modulo Requisiciones

## Objetivo

Gestionar el flujo de requisicion de personal por area, desde la solicitud inicial del usuario hasta la gestion operativa y el cambio de estados por parte de gestion humana.

## Alcance actual

- Tablero `Requisiciones` visible por `view.board.{area}.requisiciones` o por funcionalidades de **area base** en el area del usuario
- Subtableros internos:
  - `Dashboard`
  - `Solicitar`
  - `Mis requisiciones` (permiso `requisitions.tab.seguimiento`)
  - `Gestion`
  - `Parametros`
- Solicitar y Mis requisiciones operan siempre en `users.area_key`
- Gestión y Dashboard requieren tablero visible en alcance + permiso funcional. **Gestión** muestra solicitudes de todas las areas.
- Historial de cambios de estado
- Historial de cambios de campos en edicion de gestion (fecha, usuario, valor anterior y nuevo)
- Catalogos administrables: cargos, motivos, ciudades, tipos de cliente, tipos de programacion, uniformes, tipos de contrato, encargados de seleccion y **correos de notificacion** (los clientes se gestionan en Comercial → Clientes)
- Notificacion por correo al **crear** una solicitud (`PersonalRequisitionNotification`, cola `ShouldQueue`)
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
- Los estados permitidos en V1 son:
  - `solicitada`
  - `en_gestion`
  - `contratado`
  - `cancelada`
- Al cerrar como `contratado`, es obligatorio `hiring_date` y los campos de compensacion marcados como requeridos en la validacion de update
- Despues de creada, la requisicion ya no se modifica desde el flujo del solicitante; solo gestion humana puede hacerlo
- Usuarios con `manage.users` o `manage.area.gestion_humana` pueden crear solicitudes en cualquier modulo sin necesidad de tener `area_key` coincidente

## Notificaciones por correo

### Al crear (Gestion Humana / catalogo)
- Disparo: `RequisitionController::store` tras crear el lote
- Clase: `App\Mail\PersonalRequisitionNotification`
- Vista: `resources/views/emails/requisitions/requested.blade.php`
- Destinatarios: filas activas de `requisition_notification_emails` (Parametros → Correos de notificacion; el valor se guarda en `name`)
- Fallback si no hay activos: `desarrollo.tic@sjsp.com.co`
- CTA: Gestion Humana con filtro `q` = codigo

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
- UI: panel **Historial de cambios** en `resources/views/modules/requisitions/edit.blade.php`
- El **Historial de estados** sigue siendo independiente y solo registra transiciones de estado

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
15. Centro de costo
16. Observaciones del solicitante

### Campos exclusivos para Gestión Humana (GH)
17. **Compensación**: Tipo de contrato, Duración, Salario Base, Auxilios (Transporte, Movilidad), Bonificaciones, Contrato de Arrendamiento.
18. **Seguimiento**: Encargado de selección (`recruiter_id`).
19. **Cierre**: Fecha de contratación, Observaciones de GH.

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
- **Dashboard Compacto**: indicadores KPI en una sola fila.
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
- `POST /requisitions/{module}/parametros/{type}`
- `PATCH /requisitions/{module}/parametros/{type}/{parameterId}`
- `DELETE /requisitions/{module}/parametros/{type}/{parameterId}`

## Permisos relacionados

- `view.board.{area}.requisiciones`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`
- `manage.requisition.parameters`
- `manage.requisitions` (legacy; no asignar en Admin; equivalente practico a `requisitions.tab.gestion` + tablero visible)
- `manage.area.gestion_humana` (Otorga visibilidad completa de campos y acceso a tablero GH)
- `manage.users`

## Tablas implicadas

- `personal_requisitions` (compensacion, `recruiter_id`, cierre con `hiring_date`)
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
- `requisition_recruiters`
- `requisition_notification_emails`

## Riesgos

- **Visibilidad Sensible**: campos de compensacion criticos; cambios en `showHumanResourcesFields` pueden exponer salarios a solicitantes.
- **Validacion de Cierre**: `hiring_date` y compensacion requerida cuando el estado es `contratado`.
- **Cola de correo**: con `QUEUE_CONNECTION=database` hace falta `queue:work` o los mails no salen (Mailpit / SMTP).

## Deuda / pendientes (fuera del corte Mailpit)

- Motivo “Reemplazo” acoplado a `request_reason_id = 2` (frágil si cambia el seeder).
- `PersonalRequisitionPolicy` registrada pero no usada en el controller.
- Cobertura de tests acotada (sin factory dedicada; sin print/dashboard/parametros ampliados).
- Campo legacy `recruiter_name` convive con `recruiter_id`.

## Correcciones aplicadas (Sprint final 2026-04 y mantenimiento 2026-07)

- Ampliacion de modelo y matriz de visibilidad.
- Sistema de colores y navegacion fija.
- Notificaciones toast en UI.
- Mailpit documentado; CTA del correo con filtro `q`; validacion `email` en parametros tipo `emails`.
- Persistencia de `recruiter_id` en mass assignment.
- Correo al solicitante cuando GH cambia el estado (`PersonalRequisitionStatusChangedMail`).
- Campo **Cliente** en Solicitar/Gestion: buscador sobre `commercial_clients` (`commercial-client-picker.blade.php`, `comercial-client-picker.js`); puente `CommercialClientBridge` resuelve `client_id` en `requisition_clients` por nombre al validar (`ResolvesCommercialClient`).
- Eliminado el tablero **Clientes** en Parametros de requisiciones; la fuente maestra es Comercial → Clientes.
