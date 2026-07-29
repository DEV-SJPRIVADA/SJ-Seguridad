# Modulo — Configuracion de notificaciones (admin)

## Objetivo

Centralizar correos destinatarios y asignacion por **tipo de aviso** del sistema (FEAT-013). Comercial FEAT-015 agrega un tipo configurable.

## Tipos configurables en admin (`config/notifications.php`)

| Modulo | Slug | Uso |
| --- | --- | --- |
| `requisitions` | `new_requisition` | Nueva requisicion de personal |
| `comercial` | `documentation_expiring` | Digest diario documentacion comercial por vencer o vencida (checklist) |

Resolucion de destinatarios: `App\Services\Notifications\NotificationConfigService::recipientEmails($module, $slug)`. Sin correos activos asignados → fallback `config('notifications.fallback_recipient')`.

## Comercial — documentacion

- Job: `php artisan comercial:send-documentation-notification-digest` (programado 06:00 `America/Bogota`).
- Reglas alineadas a checklist: `CommercialClient::scopeDocumentationExpiring` / `scopeDocumentationExpired`.
- Dedupe: tabla `commercial_client_documentation_notification_logs`.

Ver [`matriz-clientes.md`](matriz-clientes.md).
