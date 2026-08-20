# Modulo Requisiciones

## Objetivo

Gestionar el flujo de requisicion de personal por area: solicitud del colaborador, autorizacion de gerencia cuando aplica (Cargo nuevo), gestion operativa por Gestion humana (GH), cierre como contratado y puente a **Ficha empleados**.

## Alcance actual

- Prefijo autenticado `/requisitions/{module}` + CTA de correo `GET /requisitions/abrir/{requisition}` (`requisitions.open`).
- Subtabs (label UI en `config/access.php` → `requisition_tabs`):
  - **Dashboard** — KPIs ApexCharts; alcance global si el usuario puede abrir Dashboard/Gestion
  - **Solicitar** / **Mis requisiciones** — solo en `users.area_key` (area base)
  - **Gestion** — listado transversal de todas las areas
  - **Autorizacion gerencia** — motivo Cargo nuevo (`requisitions.approve.management`)
  - **Catalogos** (ruta `parametros`) — cargos, motivos, ciudades, tipos, uniformes, contratos + toggles de encargados de seleccion
- Hogares canonicos del sidebar (`SidebarVisibilityService` + `board_canonical_areas.requisiciones`):
  - Gestion / Dashboard / Catalogos / Autorizacion gerencia → **Gestion humana**
  - Solicitar / Mis requisiciones → area base del usuario
- Destinatarios de correo: capa global `NotificationConfigService` (Admin → Configuracion de notificaciones). **No** se administran correos en Catalogos de requisiciones.
- Integracion Ficha empleados al cerrar `contratado` (`PersonalRequisitionFichaSync` → `personal_requisition_ficha_entries`).
- Auditoria de dominio via `RequisitionAuditLogService` (create, autorizacion gerencia, exports).

## Acceso (`RequisitionAccessService`)

| Concepto | Regla |
| --- | --- |
| Bypass | `manage.users` (o rol super-admin via ese permiso) abre todos los tabs |
| Tabs de area base | `solicitar`, `seguimiento`: permiso del tab **y** `users.area_key === {module}` (sin exigir `view.board`) |
| Tabs con alcance | `dashboard`, `gestion`, `autorizacion_gerencia`, `parametros`: `view.board.{module}.requisiciones` + permiso del tab |
| Excepcion board GH | En `gestion_humana`, `requisitions.approve.management` basta para ver el tablero (sin `view.board`) |
| Gestion global | `usesGlobalManagementScope`: lista/export de **todas** las areas |
| Dashboard global | `usesGlobalDashboardScope`: KPIs de **todas** las areas; si no, filtra `requesting_area_key = {module}` |
| Registro | Admin/Gestion/HR operator: cualquier area; si no, solo `requesting_area_key === {module}` |
| CTA correo `requisitions.open` | Preferir Gestion GH con `?q={code}`; si no, Seguimiento del `area_key` del usuario; si ninguno, 403 |

Middleware: `requisition.tab:{tab}` → `EnsureRequisitionTabAccess`.

En Admin usuarios: Solicitar / Mis requisiciones viven en **Solicitar en su area**; Gestion / Dashboard / Parametros / Autorizacion / selection_officer en **Funcionalidades transversales** (grupo Requisiciones — Gestion humana). Detalle: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md).

## Rutas

### Autenticadas — `routes/modules/requisitions.php`

Middleware comun: `auth`, `active`, `password.changed`.

| Metodo | URI | Nombre | Tab / permiso |
| --- | --- | --- | --- |
| GET | `/requisitions/abrir/{requisition}` | `requisitions.open` | auth; redirige via `notificationOpenUrl` |
| GET | `/requisitions/{module}/dashboard` | `requisitions.dashboard` | `dashboard` → `requisitions.tab.dashboard` |
| GET | `/requisitions/{module}/solicitar` | `requisitions.create` | `solicitar` |
| POST | `/requisitions/{module}/solicitar` | `requisitions.store` | `solicitar` |
| GET | `/requisitions/{module}/clientes/buscar` | `requisitions.clients.search` | `solicitar` (JSON, `q` min. 2) |
| GET | `/requisitions/{module}/seguimiento` | `requisitions.tracking` | `seguimiento` |
| GET | `/requisitions/{module}/seguimiento/exportar` | `requisitions.tracking.export` | `seguimiento` |
| GET | `/requisitions/{module}/seguimiento/{requisition}` | `requisitions.tracking.show` | `seguimiento` |
| GET | `/requisitions/{module}/gestion` | `requisitions.manage` | `gestion` |
| GET | `/requisitions/{module}/gestion/exportar` | `requisitions.export` | `gestion` |
| GET | `/requisitions/{module}/gestion/{requisition}/editar` | `requisitions.edit` | `gestion` |
| GET | `/requisitions/{module}/gestion/{requisition}/imprimir` | `requisitions.print` | `gestion` |
| PATCH | `/requisitions/{module}/gestion/{requisition}` | `requisitions.update` | `gestion` |
| GET | `/requisitions/{module}/autorizacion-gerencia` | `requisitions.management-approval.index` | `autorizacion_gerencia` |
| GET | `/requisitions/{module}/autorizacion-gerencia/{requisition}` | `requisitions.management-approval.show` | `autorizacion_gerencia` |
| POST | `/requisitions/{module}/autorizacion-gerencia/{requisition}` | `requisitions.management-approval.decide` | `autorizacion_gerencia` |
| GET | `/requisitions/{module}/parametros` | `requisitions.parameters` | `parametros` → `manage.requisition.parameters` |
| PATCH | `/requisitions/{module}/parametros/encargados-seleccion/{user}` | `requisitions.selection-officers.update` | `parametros`; **404 si module ≠ `gestion_humana`** |
| POST | `/requisitions/{module}/parametros/{type}` | `requisitions.parameters.store` | `parametros` |
| PATCH | `/requisitions/{module}/parametros/{type}/{parameterId}` | `requisitions.parameters.update` | `parametros` |
| DELETE | `/requisitions/{module}/parametros/{type}/{parameterId}` | `requisitions.parameters.destroy` | `parametros`; `type=recruiters` / `emails` / `clients` → 404 |

### Guest (firmadas) — `routes/modules/requisitions-email.php`

| Metodo | URI | Nombre | Notas |
| --- | --- | --- | --- |
| GET | `/requisitions/aprobacion-correo/{requisition}` | `requisitions.email-approval.show` | middleware `signed` |
| POST | `/requisitions/aprobacion-correo/{requisition}` | `requisitions.email-approval.update` | aprobar/rechazar sin login |

## Permisos

| Permiso | Uso |
| --- | --- |
| `requisitions.tab.solicitar` | Solicitar (area base) |
| `requisitions.tab.seguimiento` | Mis requisiciones (area base) |
| `requisitions.tab.gestion` | Gestion (transversal GH) |
| `requisitions.tab.dashboard` | Dashboard |
| `manage.requisition.parameters` | Catalogos + toggles encargados |
| `requisitions.approve.management` | Autorizacion gerencia (rol `administrador` por seeder; **no** rol `director`) |
| `requisitions.selection_officer` | Aparecer en select Reclutador (toggle en Catalogos GH) |
| `view.board.{area}.requisiciones` | Visibilidad de tablero para tabs con alcance |
| `manage.users` | Bypass de tabs |
| `manage.requisitions` | Legacy; oculto en Admin — no asignar |

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `RequisitionController` | Dashboard, solicitar/store, seguimiento, gestion/edit/print/update, catalogos, selection officers, openFromNotification, exports |
| `RequisitionManagementApprovalController` | Bandeja gerencia in-app |
| `RequisitionEmailApprovalController` | Decision guest por enlace firmado |
| `StorePersonalRequisitionRequest` / `UpdatePersonalRequisitionRequest` | Validacion crear/editar; `service_structure` required; `recruiter_id` + `ValidRequisitionRecruiterUser` |
| `UpdateRequisitionSelectionOfficerRequest` | Toggle `enabled` boolean |
| `DecideRequisitionManagementApprovalRequest` | Decision gerencia (si aplica en el flujo) |

Trait UI: `HasRequisitionTabs`. Policy `PersonalRequisitionPolicy` existe pero el enforcement operativo usa `RequisitionAccessService` en controllers.

## Servicios clave

| Servicio | Rol |
| --- | --- |
| `App\Services\Access\RequisitionAccessService` | Tabs, scopes, `notificationOpenUrl` |
| `RequisitionManagementApprovalService` | Listados gerencia + `resolve()` aprobar/rechazar |
| `RequisitionSelectionOfficerAccessService` | Toggles / `recruitersForSelect` |
| `PersonalRequisitionFichaSync` | `contratado` ↔ lista de espera ficha |
| `CommercialClientBridge` + `ResolvesCommercialClient` | Matriz comercial → `requisition_clients` |
| `NotificationConfigService` | `recipientEmails('requisitions', $slug)` |
| `PersonalRequisitionChangeLogger` | Historial de campos en Gestion |
| `RequisitionAuditLogService` | Eventos de auditoria del modulo |
| `RequisitionEmailApprovalUrlBuilder` | URLs firmadas de correo |
| `RequisitionRequestReasonCatalog` | Normalizacion motivo Cargo nuevo |
| `PersonalRequisitionFilterBag` | Filtros listados |

## Notificaciones por correo

Destinatarios: `NotificationConfigService::recipientEmails('requisitions', $slug)`. Sin correos activos → fallback `config('notifications.fallback_recipient')` (`desarrollo.tic@sjsp.com.co`).

| Evento | Clase | Cola | Slug / destinatario | CTA |
| --- | --- | --- | --- | --- |
| Alta (no cargo nuevo pendiente) | `PersonalRequisitionNotification` | `ShouldQueue` | `new_requisition` (editable en Admin → Notificaciones) | `requisitions.open` |
| Cargo nuevo | `PersonalRequisitionManagementApprovalMail` | **Sincrono** | `management_approval_cargo_nuevo` (no en `admin_configurable`; se resuelve igual por servicio) | Enlace firmado `email-approval.*` + link plataforma |
| Cambio de estado / rechazo gerencia | `PersonalRequisitionStatusChangedMail` | `ShouldQueue` | Email del `requested_by` | Seguimiento del area con `q` |

Config enlace gerencia: `config/requisitions.php` (`email_approval_link_days`, opcional `email_approval_log_user_id`).

Documentacion de la capa global: [`docs/modules/notifications-config.md`](notifications-config.md).

## Reglas de negocio

- Solicitar solo en `area_key` con `requisitions.tab.solicitar`.
- Mis requisiciones: solo lectura del **area base** del usuario.
- Gestion: todas las areas; filtro «Solo mis solicitudes» reduce a creadas por el autenticado.
- `leader_name` y `requesting_area_key` del usuario autenticado al crear.
- Cliente: buscador sobre `commercial_clients` si el tipo no es Interno; Interno → `Cliente interno SJ Seguridad` en `requisition_clients`.
- Cantidad N en Solicitar → **N filas** con `quantity = 1` y codigos `REQ-{YEAR}-####`.
- Observaciones: `requester_observation` (solicitante) y `human_resources_observation` (GH).
- `service_structure` (**Estructura del servicio**): obligatorio al crear y al guardar en edicion; en Excel; **no** en impresion ni correos.
- Estados operativos: `pendiente_autorizacion_gerencia`, `solicitada`, `en_gestion`, `contratado`, `cancelada` (legacy `aprobada` posible en import).
- Motivo **Cargo nuevo**: estado inicial pendiente gerencia; GH no edita hasta `solicitada`.
- Aprobar gerencia → `solicitada`; rechazar → `cancelada` + correo al solicitante.
- Cierre `contratado`: obligatorios `hiring_date`, compensacion requerida por validacion, `hired_document` y `hired_full_name` (una persona por fila). Independientes de `replacement_*`.
- Guardar `contratado` upsert 1:1 en `personal_requisition_ficha_entries`; cedula ya usada en otra requisicion pide confirmacion (SweetAlert2).
- Tras crear, el solicitante no edita; solo GH (salvo bloqueo por pendiente gerencia).

## Encargados de seleccion (Reclutador)

| Capa | Detalle |
| --- | --- |
| Servicio | `RequisitionSelectionOfficerAccessService` |
| Permiso | `requisitions.selection_officer` (toggle Parametros GH; no en roles base) |
| UI | Catalogos GH + partial `selection-officers.blade.php` |
| Toggle | `requisitions.selection-officers.update`; solo `module=gestion_humana` |
| Select Gestion | `recruitersForSelect(?currentRecruiterId)` — usuarios GH activos con permiso; el reclutador ya guardado sigue aunque se apague el toggle |
| Persistencia | `personal_requisitions.recruiter_id` → `users.id` nullable; no se escribe `recruiter_name` |
| Presentacion | `PersonalRequisition::displayRecruiterName()` |

Catalogo `requisition_recruiters` eliminado (migracion `2026_07_28_112704_…`).

## Catalogos (`PARAMETER_TYPES`)

`positions`, `reasons`, `cities`, `client-types`, `programming-types`, `uniforms`, `contract-types`.

**Fuera de Catalogos:** clientes (Comercial), reclutadores (usuarios + toggle), correos (Admin notificaciones).

## Formulario — matriz de visibilidad

### Solicitante

1. Lider / solicitante (readonly)  
2. Area solicitante (readonly)  
3. Cargo, sexo, cantidad  
4. Cedula / nombre a quien reemplaza (opcional)  
5. Area operativa, motivo, cliente, ciudad, tipo cliente, programacion  
6. Perfil requerido, dotacion  
7. **Estructura del servicio**  
8. Centro de costo, observaciones del solicitante  

Cantidad visible solo para motivos *Cargo nuevo* y *Servicio nuevo* (demas envian 1).

### Gestion humana (ademas)

- Compensacion: tipo contrato, duracion, salario, auxilios, bonificaciones, arrendamiento  
- Seguimiento: reclutador (`recruiter_id`)  
- Cierre: fecha contratacion, observaciones GH, cedula/nombre contratado  

## UI operativa

- Status pills por estado; layout con subnav de modulo.
- Gestion: filtros + pills; default **En curso** (excluye contratado/cancelada); `include_closed=1` = Todos; DataTables.
- Seguimiento: mismos filtros + export Excel.
- Dashboard: KPIs Total / Solicitadas / En gestion / Contratadas / Canceladas; charts Vite `resources/js/requisitions-dashboard-charts.js` + `#requisitions-chart-data`.
- Historial de estados y historial de cambios de campos (paneles en edicion Gestion).

## Export Excel

- Gestion y Seguimiento: `App\Exports\PersonalRequisitionFullExport` (sobre `BaseExport`); filtros = vista (`q`, estado, `date_from`/`date_to` sobre `request_date`, etc.).
- No incluye `hired_*` como columnas propias; eso va en export de Ficha empleados (`PersonalRequisitionFichaEntryExport`).

## Modelos y tablas

| Modelo / tabla | Notas |
| --- | --- |
| `personal_requisitions` | Nucleo; compensacion; `recruiter_id`→users; `service_structure`; `hired_*`; `hiring_date` |
| `personal_requisition_status_logs` | Transiciones de estado + comentarios gerencia |
| `personal_requisition_change_logs` | Edicion campo a campo (`change_batch`) |
| `personal_requisition_ficha_entries` | Lista de espera ficha 1:1 |
| `requisition_positions`, `_request_reasons`, `_cities`, `_client_types`, `_programming_types`, `_uniforms`, `_contract_types` | Catalogos |
| `requisition_clients` | Puente interno; no se administra en Catalogos |
| `commercial_clients` | Fuente del buscador |
| `notification_types` / `notification_emails` | Destinatarios globales (modulo `requisitions`) |

Eliminada: `requisition_recruiters`. Legacy rename: `requisition_notification_emails` → `notification_emails` (FEAT-013).

## JavaScript / assets

- `resources/js/requisitions-dashboard-charts.js` (+ `apex-defaults.js`)
- `resources/js/comercial-client-picker.js` + partial `commercial-client-picker.blade.php`
- Vite; no Chart.js en dashboard

## Validacion local

1. `php artisan test --compact tests/Feature/RequisitionModuleTest.php`
2. `php artisan test --compact tests/Feature/Requisitions/RequisitionAuditTest.php`
3. Con cola `database`: `queue:work` para mails `ShouldQueue`; Mailpit en local ([`LOCAL_SETUP.md`](../LOCAL_SETUP.md))

## Riesgos y pendientes

- Campos de compensacion: no exponer a solicitantes (matriz de vistas).
- Cierre `contratado`: validar `hiring_date` + compensacion + `hired_*`.
- `quantity > 1` en Gestion marcado Contratado solo registra una persona en ficha (riesgo conocido v1).
- Cola de correo: sin worker no salen mails encolados.
- `PersonalRequisitionPolicy` poco usada frente a `RequisitionAccessService`.
- Columna legacy `recruiter_name` solo lectura/presentacion.

## Control de cambios (tecnico)

| Fecha | Descripcion |
| --- | --- |
| 2026-08-20 | Doc tecnica alineada al codigo: notificaciones globales (`NotificationConfigService`), rutas export/gerencia/email, acceso Admin «Solicitar en su area», Catalogos sin correos, mail gerencia sincrono |
| 2026-08-04 | Autorizacion cargo nuevo solo rol `administrador` |
| 2026-08-03 | Hogares canonicos sidebar |
| 2026-07 | FEAT-005/006/010/011/012/020: service_structure, Excel, ApexCharts, encargados, gerencia, ficha |

## Referencias

- Guia usuario: [`docs/user/requisitions.md`](../user/requisitions.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Notificaciones: [`docs/modules/notifications-config.md`](notifications-config.md)
- Ficha empleados: [`docs/modules/ficha-empleados.md`](ficha-empleados.md)
- Auditoria: [`docs/modules/audit-log.md`](audit-log.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
