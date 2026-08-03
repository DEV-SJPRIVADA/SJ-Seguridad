# Feature Brief — FEAT-019

## Objetivo

Notificar por correo cuando un **servicio comercial** (`commercial_services`) entre en ventana de **30 dias calendario** antes de `contract_end`, usando destinatarios de **Admin → Configuracion de notificaciones** (modulo `comercial`).

## Reglas

- Candidatos: `is_active = true`, `contract_end` not null, `contract_end` entre **hoy** y **hoy + 30 dias** (misma logica que filtro «Por vencer» en [`CommercialService::scopeFilterByContractEstado`](../../app/Models/CommercialService.php)).
- **Un aviso por servicio y fecha fin** (dedupe en log `commercial_service_id` + `contract_end`); si cambian `contract_end`, puede volver a notificarse.
- Tipo notificacion: `module=comercial`, `slug=service_contract_expiring`, label legible en admin.
- Patron FEAT-015: servicio dominio + Mailable digest + comando Artisan + schedule 06:00 Bogota.
- Destinatarios: `NotificationConfigService::recipientEmails('comercial', 'service_contract_expiring')`.

## Artefactos

- Migracion: log `commercial_service_contract_notification_logs` + seed `NotificationType`
- `CommercialServiceContractNotificationService`
- `CommercialServiceContractExpiringDigestMail` + vista email
- `comercial:send-service-contract-notification-digest` (`--date`, `--dry-run`)
- `config/notifications.php` admin_configurable
- `NotificationType::SLUG_SERVICE_CONTRACT_EXPIRING`
- Tests dedicados (mirror `CommercialDocumentationNotificationTest`)

## Fuera de alcance

- Notificar contratos ya vencidos (solo ventana 30 dias).
- Cambiar UI listado servicios.
