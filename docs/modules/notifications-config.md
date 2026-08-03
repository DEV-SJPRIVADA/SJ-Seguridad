# Modulo — Configuracion de notificaciones (admin)

## Objetivo

Centralizar correos destinatarios y asignacion por **tipo de aviso** del sistema (FEAT-013). Comercial FEAT-015 agrega un tipo configurable.

## Acceso

- Ruta: `GET /admin/notificaciones` (`admin.notifications.index`)
- Permiso: `manage.notifications`
- Controlador: `App\Http\Controllers\Admin\NotificationConfigController`
- Servicio: `App\Services\Notifications\NotificationConfigService`

## Interfaz (v2 UX)

Pantalla unica con Alpine.js (`resources/views/admin/notifications/index.blade.php`):

1. **Resumen (KPIs):** modulos, avisos, configurados, sin destinatarios.
2. **Correo de respaldo:** visible en cabecera (`config('notifications.fallback_recipient')`); se usa cuando un aviso no tiene correos activos.
3. **Busqueda y filtro:** texto libre (aviso/modulo) + estado (todos / con destinatarios / sin destinatarios).
4. **Grid de modulos:** tarjetas por area de negocio; badge indica avisos sin correo.
5. **Vista detalle (maestro-detalle):** lista lateral de avisos del modulo + panel de destinatarios del aviso seleccionado.
6. **Chips destinatarios:** cada correo asignado con boton × para quitar (confirmacion).
7. **Agregar correo:** un formulario por aviso; datalist con correos ya usados en otros avisos.

Tras agregar o quitar un correo, el redirect conserva contexto (`?module=&type=#notification-type-{id}`).

## Tipos configurables en admin (`config/notifications.php`)

| Modulo | Slug | Uso |
| --- | --- | --- |
| `requisitions` | `new_requisition` | Nueva requisicion de personal |
| `comercial` | `documentation_expiring` | Digest diario documentacion comercial por vencer o vencida (checklist) |

Resolucion de destinatarios: `NotificationConfigService::recipientEmails($module, $slug)`. Sin correos activos asignados → fallback `config('notifications.fallback_recipient')`.

## Rutas de mutacion

| Metodo | Ruta | Nombre |
| --- | --- | --- |
| POST | `/admin/notificaciones/tipos/{notification_type}/correos` | `admin.notifications.types.emails.attach` |
| DELETE | `/admin/notificaciones/tipos/{notification_type}/correos/{notification_email}` | `admin.notifications.types.emails.detach` |

Solo tipos listados en `config/notifications.php` → clave `admin_configurable`.

## Comercial — documentacion

- Job: `php artisan comercial:send-documentation-notification-digest` (programado 06:00 `America/Bogota`).
- Reglas alineadas a checklist: `CommercialClient::scopeDocumentationExpiring` / `scopeDocumentationExpired`.
- Dedupe: tabla `commercial_client_documentation_notification_logs`.

Ver [`matriz-clientes.md`](matriz-clientes.md).

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-08-03 | Rediseño UX: KPIs, grid modulos, maestro-detalle, chips, busqueda/filtros y redirect con contexto. |
