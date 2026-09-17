# Modulo Solicitudes de desarrollo (FO-TIC-23)

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/development-requests.md`.
> Feature: FEAT-033.

## Objetivo

Modulo compartido para solicitar, priorizar y seguir desarrollos internos (StatFlow): formulario FO-TIC-23 digital, aprobacion de lider, bandeja TIC con estados del procedimiento, chat con correo y KPIs/export.

## Alcance actual

- Prefijo de rutas: `/development-requests/{module}` · nombres `development-requests.*`.
- Area hogar del tablero: `tic` (`config/access.php` → `board_canonical_areas.solicitudes_desarrollo.home`).
- Areas base: pestañas **Nueva** / **Mis solicitudes** con `view.board.{area}.solicitudes_desarrollo` o area asignada + permisos create/my_requests.
- TIC: **Bandeja TIC** (KPIs + listado + export), transiciones, bloque interno, chat.
- Lideres: pestana **Aprobacion lider**.
- Codigo de radicacion: `DEV-YYYY-#####`.
- Chat: `development_request_messages`; correo Mailable `DevelopmentRequestMessageMail` (ShouldQueue) + destinatarios de `NotificationConfigService` (`development_requests` / `development_request_message`).
- UAT: el solicitante registra aceptacion en `en_pruebas`; TIC tambien puede transicionar a `entregado` / `en_desarrollo` desde el panel (regla negocio: respaldo operativo).
- Hilo cerrado en estados `cerrado` / `rechazado`.
- Anexos: disco `local`, config `development-requests.attachments`.
- SLA analisis (calendario, desde `radicated_at`): urgente 2d, importante/mejora 5d, soporte 3d (`config/development-requests.sla_analysis_days`). Prioridad efectiva = `tic_confirmed_priority` ?? `suggested_priority`.

## Estados

`borrador` → `pendiente_aprobacion_lider` → `radicado` → `en_analisis` → `en_desarrollo` → `en_pruebas` → `entregado` → `cerrado`  
Alternos: `devuelto`, `rechazado`. Si el creador es el lider seleccionado, el envio puede radicar en un paso.

## Permisos

| Permiso | Uso |
| --- | --- |
| `devreq.tab.create` | Nueva solicitud |
| `devreq.tab.my_requests` | Mis solicitudes |
| `devreq.tab.leader_approval` | Aprobacion lider + elegible como lider |
| `devreq.tab.tic_queue` | Bandeja, export, transiciones TIC |
| `devreq.tab.view` | Consulta (board TIC) |
| `view.board.{area}.solicitudes_desarrollo` | Visibilidad del tablero en el area |

Bypass: `super-admin` / `manage.users`. Middleware de pestana: `devreq.tab:{tab}`.

## Rutas clave

| Metodo | URI | Nombre |
| --- | --- | --- |
| GET | `/{module}` | `index` |
| GET/POST | `/{module}/nueva` | `create` / `store` |
| GET | `/{module}/mis-solicitudes` | `my-requests` |
| GET/PATCH | `/{module}/mis-solicitudes/{id}/editar` | `edit` / `update` |
| GET | `/{module}/aprobacion-lider` | `leader-approval` |
| PATCH | `/{module}/solicitud/{id}/lider` | `leader.update` |
| GET | `/{module}/bandeja-tic` | `tic-queue` |
| GET | `/{module}/bandeja-tic/exportar` | `tic-queue.export` |
| PATCH | `/{module}/solicitud/{id}/tic` | `tic.transition` |
| POST | `/{module}/solicitud/{id}/mensajes` | `messages.store` |
| PATCH | `/{module}/solicitud/{id}/uat` | `uat.update` |
| GET | `/{module}/solicitud/{id}` | `show` |

## Archivos clave

- Controllers: `app/Http/Controllers/DevelopmentRequests/`
- Services: `Workflow`, `Attachment`, `Notification`, `Dashboard`, `AuditLog`
- Policy: `app/Policies/DevelopmentRequestPolicy.php`
- Access: `app/Services/Access/DevelopmentRequestAccessService.php`
- Vistas: `resources/views/modules/development-requests/`
- Tests: `tests/Feature/DevelopmentRequests/`

## Auditoria / notificaciones

- Modulo audit: `development_requests` (area `tic`).
- Config notificaciones: `config/notifications.php` → `development_requests.development_request_message`.

## Fuera de V1

FO-GE-12 / FO-GI-38 como modulos, WebSockets, import Word automatico, integracion Git/CI.
