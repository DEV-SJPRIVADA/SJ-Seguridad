# Modulo Audit log central

Fuente de verdad cross-modulo para eventos de auditoria de usuario. Complementa (no reemplaza) historiales de dominio embebidos.

## Arquitectura hibrida

| Componente | Rol |
| --- | --- |
| `audit_logs` | Eventos centralizados por modulo/area |
| `personal_requisition_change_logs` | Historial campo a campo en edicion de requisicion (sin migrar) |
| `personal_requisition_status_logs` | Historial de estados de requisicion (sin migrar) |
| `*_notification_logs`, mail logs | Entrega/dedup de correo, no auditoria de usuario |

```text
Modulo / Controller
        |
        v
SystemAuditService (sync o cola)
        |
        v
audit_logs
```

## Esquema `audit_logs`

| Columna | Notas |
| --- | --- |
| `module` | Slug estable: `indicadores`, `admin`, `requisitions`, … |
| `area` | Nullable; alinea con `config/access.php` → `areas` |
| `event_type`, `action` | Taxonomia por modulo |
| `auditable_type`, `auditable_id` | Morph nullable |
| `change_batch` | UUID nullable (lotes futuros) |
| `old_values`, `new_values`, `metadata` | JSON nullable, truncado si excede 64 KB |
| `reason`, `ip_address`, `user_agent` | Contexto |
| `user_id` | Nullable, `nullOnDelete` |

Indices: `(module, created_at)`, `(module, area, created_at)`, `(auditable_type, auditable_id, created_at)`, `(user_id, created_at)`, `(event_type, created_at)`.

## Configuracion (`config/audit.php`)

| Variable `.env` | Default | Descripcion |
| --- | --- | --- |
| `AUDIT_ENABLED` | `true` | Kill switch; `false` = no-op |
| `AUDIT_QUEUE` | `false` | `true` encola `WriteAuditLogJob` con `afterCommit()` |
| `AUDIT_QUEUE_CONNECTION` | `QUEUE_CONNECTION` | Conexion de cola |
| `AUDIT_RETENTION_MONTHS` | `24` | Retencion para `audit:purge` |

## API del servicio

`App\Services\Audit\SystemAuditService`:

- `logModelChange($module, $eventType, $action, $model, $before, $after, $reason, $metadata, $area, $changeBatch)`
- `logEvent($module, $eventType, $action, $reason, $metadata, $model, $area, $changeBatch)`

Modulos existentes deben usar un wrapper delgado (ej. `App\Services\Indicadores\AuditLogService`) que fije `module` y `area`.

## Catálogo de eventos

`App\Support\Audit\AuditEventCatalog` define severidad (`info`, `audit`, `security`) para Indicadores. Eventos `info` (`dashboard_view`, `consolidado_view`) se excluyen de la UI global salvo `show_info=1`.

## Indicadores

- Escritura: wrapper `AuditLogService` → `module=indicadores`, `area=operaciones`.
- Lectura Ajustes: `AuditLog::forModule('indicadores')` con filtros `event_type` y `action`.
- Migracion historica: `php artisan audit:migrate-indicator-logs --dry-run` luego `--force`. Idempotente via `metadata.migrated_from_indicator_id`. **No borra** `indicator_audit_logs`.

## UI global super-admin

- Ruta: `GET /admin/auditoria` (`admin.audit.index`)
- Permiso: `system.view.audit` (solo `super-admin` por seeder)
- Filtros: modulo, area, event_type, action, user_id, fechas; paginacion 30
- UI compacta en grid (4 columnas desktop); estilos en `public/css/user-admin.css` (`.audit-filter-grid`)
- Opciones de filtro acotadas a `filter_lookback_days` (90)

## Operaciones

### Cola

- Desarrollo / PHPUnit: `AUDIT_QUEUE=false`
- Produccion: `AUDIT_QUEUE=true` + `php artisan queue:work` activo

### Retencion

```bash
php artisan audit:purge --dry-run
php artisan audit:purge --force
php artisan audit:purge --months=12 --force
```

Programado mensualmente en `bootstrap/app.php` (`audit:purge --force`).

## Que registrar / que no

**Registrar:** create/update/delete de entidades, cambios de permisos, exportaciones sensibles, cierres de periodo.

**No registrar:** GET de listados, health checks, payloads con adjuntos completos, duplicar historiales de dominio (requisiciones campo a campo).

## Checklist anti-fallas

1. Nunca consultar audit sin paginacion + filtro de fecha en produccion.
2. No loguear en bucles de import masivo (usar resumen de 1 fila).
3. Verificar `queue:work` si `AUDIT_QUEUE=true`.
4. Ejecutar / monitorear `audit:purge` programado.
5. No migrar `personal_requisition_change_logs` al central.
6. Tests usan `RefreshDatabase`; no escribir audit en seeders salvo fixtures.
7. Transacciones: job con `afterCommit()`.
8. Migracion de datos: siempre `--dry-run` primero.

## Archivos clave

- `app/Services/Audit/SystemAuditService.php`
- `app/Jobs/WriteAuditLogJob.php`
- `app/Models/AuditLog.php`
- `app/Http/Controllers/Admin/SystemAuditController.php`
- `app/Console/Commands/MigrateIndicatorAuditLogsCommand.php`
- `app/Console/Commands/PurgeAuditLogsCommand.php`
- `config/audit.php`
- `tests/Feature/SystemAuditTest.php`

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-08-03 | FEAT-021: tabla central, servicio, UI global, wrapper Indicadores |
| 2026-08-03 | Filtros compactos en `/admin/auditoria` |
