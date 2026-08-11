# Modulo Audit log central

Fuente de verdad cross-modulo para eventos de auditoria de usuario. Complementa (no reemplaza) historiales de dominio embebidos.

**Feature base:** FEAT-021 (tabla, servicio, UI global). **Extension v1:** FEAT-025 (instrumentacion admin, requisiciones, notificaciones; politica sync permanente; default UI 30 dias).

## Arquitectura hibrida

| Componente | Rol |
| --- | --- |
| `audit_logs` | Eventos centralizados por modulo/area |
| `personal_requisition_change_logs` | Historial campo a campo en edicion de requisicion (sin migrar) |
| `personal_requisition_status_logs` | Historial de estados de requisicion (sin migrar) |
| `employee_archive_consultations` | Consultas al archivo GH (fuera de alcance v1 central) |
| `*_notification_logs`, mail logs | Entrega/dedup de correo, no auditoria de usuario |

```text
Modulo / Controller / Servicio dominio
        |
        v
Wrapper delgado (module + area fijos)
        |
        v
SystemAuditService (sync — politica SJ)
        |
        v
audit_logs
```

## Modulos instrumentados (v1)

| Modulo (`module`) | Etiqueta UI | Area | Wrapper escritura | Lectura dedicada |
| --- | --- | --- | --- | --- |
| `indicadores` | Indicadores | `operaciones` | `App\Services\Indicadores\AuditLogService` | Operaciones → Ajustes → Auditoria (`forModule('indicadores')`) |
| `admin` | Administracion | `null` | `App\Services\Admin\AdminAuditLogService` | Solo UI global |
| `requisitions` | Requisiciones | `gestion_humana` | `App\Services\Requisitions\RequisitionAuditLogService` | Historial dominio en pantalla Editar requisicion |

Registro en `config/audit.php` → clave `modules`.

## Wrappers y servicios de dominio

### Patron wrapper

Cada modulo fija `module` y `area` y delega a `SystemAuditService`:

- `logModelChange($eventType, $action, $model, $before, $after, $reason, $metadata)` (+ `$userId` opcional en requisiciones)
- `logEvent($eventType, $action, $reason, $metadata, $model)` (+ `$userId` opcional en requisiciones)

| Clase | Constantes | Uso |
| --- | --- | --- |
| `App\Services\Indicadores\AuditLogService` | `indicadores` / `operaciones` | Indicadores (FEAT-021) |
| `App\Services\Admin\AdminAuditLogService` | `admin` / `null` | Notificaciones admin (attach/detach) |
| `App\Services\Requisitions\RequisitionAuditLogService` | `requisitions` / `gestion_humana` | Requisiciones resumen + aprobacion gerencia |
| `App\Services\Admin\UserManagementAuditService` | — (usa `AdminAuditLogService`) | Diff de usuarios admin: perfil, activacion, rol, permisos, reset contrasena |

Inyeccion por constructor; no hay facades nuevas.

### `UserManagementAuditService`

Encapsula la logica de sub-eventos en `UserController::update`:

- Captura estado previo (`captureProfileState`, `captureRole`, `captureDirectPermissions`) **antes** de la transaccion.
- Emite solo los sub-eventos que aplicaron (puede coexistir `update` + `permissions_sync` en un mismo guardado).
- Diff de permisos en metadata acotada: max 50 nombres combinados (`added[]`, `removed[]`, counts).
- **Nunca** persiste contrasenas ni hashes en JSON; `password_reset` solo lleva `metadata.admin_initiated=true`.

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
| `user_id` | Nullable, `nullOnDelete`; resoluble en aprobacion gerencia por correo |

Indices: `(module, created_at)`, `(module, area, created_at)`, `(auditable_type, auditable_id, created_at)`, `(user_id, created_at)`, `(event_type, created_at)`.

## Configuracion (`config/audit.php`)

| Clave / variable `.env` | Default | Descripcion |
| --- | --- | --- |
| `AUDIT_ENABLED` / `enabled` | `true` | Kill switch; `false` = no-op global |
| `AUDIT_QUEUE` / `queue` | **`false`** | Politica SJ: **sync permanente** en dev, pruebas y produccion |
| `AUDIT_QUEUE_CONNECTION` / `connection` | `QUEUE_CONNECTION` | Solo relevante si se activara cola (no desplegado) |
| `AUDIT_RETENTION_MONTHS` / `retention_months` | `24` | Retencion para `audit:purge` |
| `default_date_range_days` | **30** | Rango implicito al abrir `/admin/auditoria` sin fechas en query |
| `filter_lookback_days` | `90` | Ventana para poblar selects de filtro (modulo, area, evento, accion, usuario) |
| `max_json_bytes` | `65536` | Truncado de payloads JSON |

Comentario en archivo: **politica de proyecto — sync permanente; no activar cola en Hostinger compartido.**

`.env.example`:

```env
AUDIT_QUEUE=false
# Politica SJ Seguridad: sync en todos los entornos (ver docs/modules/audit-log.md)
```

`WriteAuditLogJob` permanece en codigo para uso futuro opcional; **no** se usa en el despliegue actual.

## Politica de escritura sync (FEAT-025)

| Entorno | `AUDIT_QUEUE` | Requisito |
| --- | --- | --- |
| Desarrollo local | `false` | Ninguno |
| PHPUnit (`RefreshDatabase`) | `false` | Ninguno |
| Produccion (Hostinger) | **`false`** | **No** exige `queue:work` |

Razon: hosting compartido sin worker de colas confiable; v1 no loguea bucles masivos ni GET.

Convencion de invocacion:

- Registrar **despues** de commit exitoso (hooks post-`DB::transaction` o al final del metodo tras persistencia).
- No invocar audit dentro de transacciones abiertas si un rollback dejaria filas huerfanas (sync no usa `afterCommit()`).
- Un evento resumen por operacion masiva (batch create requisiciones, export Excel).

## API del servicio canonico

`App\Services\Audit\SystemAuditService`:

- `logModelChange($module, $eventType, $action, $model, $before, $after, $reason, $metadata, $area, $changeBatch, $userId)`
- `logEvent($module, $eventType, $action, $reason, $metadata, $model, $area, $changeBatch, $userId)`

Modulos deben usar wrapper delgado; no llamar con `module`/`area` sueltos desde controladores.

## Catalogo de eventos v1

Severidad en `App\Support\Audit\AuditEventCatalog`. Eventos v1 admin y requisitions: todos `audit`. Indicadores mantiene eventos `info` y `audit` existentes.

Eventos `info` de Indicadores se excluyen de la UI global salvo `show_info=1` via `AuditEventCatalog::globalUiExcludedEventTypes()`.

### Modulo `admin` (`area = null`)

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `user_management` | `create` | `UserController::store` | `User` | `new_values`: perfil + rol; `metadata.permissions_count` |
| `user_management` | `update` | `UserController::update` — cambio perfil | `User` | Diff: name, email, document_number, area_key, sede_id, must_change_password |
| `user_management` | `activate` | `is_active` false→true | `User` | `metadata.previous_is_active` |
| `user_management` | `deactivate` | `is_active` true→false | `User` | idem |
| `user_management` | `role_sync` | cambio de rol | `User` | `old_values`/`new_values`: role |
| `user_management` | `permissions_sync` | cambio permisos directos | `User` | `metadata`: added[], removed[] (max 50), counts |
| `user_management` | `password_reset` | admin resetea contrasena | `User` | sin secretos; `metadata.admin_initiated=true` |
| `notification_config` | `email_attach` | `NotificationConfigService::addEmailToType` | `NotificationType` | modulo, type_slug, type_label, email |
| `notification_config` | `email_detach` | `NotificationConfigService::removeEmailFromType` | `NotificationType` | idem |

### Modulo `requisitions` (`area = gestion_humana`)

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `requisition` | `create` | Fin exitoso `RequisitionController::store` | `PersonalRequisition` (primera del lote) | `metadata`: batch_size, codes[], requesting_area_key, initial_status |
| `requisition` | `status_change` | `update` solo si cambia estado | `PersonalRequisition` | old/new status; metadata code, labels |
| `management_approval` | `approve` | `RequisitionManagementApprovalService::resolve(..., 'approve')` | `PersonalRequisition` | code, channel (web/email), comment truncado |
| `management_approval` | `reject` | `resolve(..., 'reject')` | `PersonalRequisition` | idem |
| `export` | `manage_excel` | `RequisitionController::exportExcel` | — | filtros resumidos, row_count |
| `export` | `tracking_excel` | `RequisitionController::trackingExport` | — | idem + mine_only si aplica |

**No registrar en central (v1):** `changeLogger->logUpdate` (campos), parametros CRUD, dashboard, manage index, PDF, login/logout.

### Modulo `indicadores` (`area = operaciones`)

Ver FEAT-021. Escritura via `Indicadores\AuditLogService`. Eventos `info`: `admin_action/dashboard_view`, `admin_action/consolidado_view`.

## Puntos de instrumentacion (v1)

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `UserController` | `store` | `UserManagementAuditService` | `user_management/create` |
| `UserController` | `update` | `UserManagementAuditService` | sub-eventos segun diff |
| `NotificationConfigService` | `addEmailToType` | `AdminAuditLogService` | `notification_config/email_attach` |
| `NotificationConfigService` | `removeEmailFromType` | `AdminAuditLogService` | `notification_config/email_detach` |
| `RequisitionController` | `store` | `RequisitionAuditLogService` | `requisition/create` (1 por request) |
| `RequisitionController` | `update` | `RequisitionAuditLogService` | `requisition/status_change` si cambia estado |
| `RequisitionController` | `exportExcel` | `RequisitionAuditLogService` | `export/manage_excel` |
| `RequisitionController` | `trackingExport` | `RequisitionAuditLogService` | `export/tracking_excel` |
| `RequisitionManagementApprovalService` | `resolve` | `RequisitionAuditLogService` | `management_approval/approve` o `reject` |

Email approval: un solo punto en `resolve()`; no duplicar en `RequisitionEmailApprovalController`. `user_id` resuelto con `resolveEmailApprovalLogUserId()` cuando no hay sesion web.

## UI global super-admin

- Ruta: `GET /admin/auditoria` (`admin.audit.index`)
- Middleware: `password.changed`, `permission:system.view.audit`
- Paginacion: 30 registros
- UI compacta en grid (4 columnas desktop); estilos en `public/css/user-admin.css` (`.audit-filter-grid`)

### Permiso y Gate `system.view.audit`

| Aspecto | Comportamiento |
| --- | --- |
| Permiso | `system.view.audit` — asignado solo a rol `super-admin` por seeder |
| Ruta | Middleware Spatie `permission:system.view.audit` |
| Gate especial | En `AppServiceProvider`, `system.view.audit` **no** hereda el bypass `Gate::before` de super-admin: el permiso debe estar **explicitamente** asignado al usuario |
| Escritura audit | No exige permiso adicional; corre en contexto del usuario que ejecuta la accion |

### Defaults de filtro (FEAT-025)

1. Si la peticion **no** trae `date_from` ni `date_to`: aplicar ultimos **30 dias** (`default_date_range_days`).
2. Inputs Desde/Hasta precargados en la vista (no vacios en carga inicial).
3. **Limpiar filtros** → `route('admin.audit.index')` sin query; el controlador reaplica defaults 30 dias.
4. Selects de modulo/area/evento/accion/usuario: valores detectados en ultimos **90 dias** (`filter_lookback_days`).
5. Checkbox **Info**: muestra eventos `info` de Indicadores; ocultos por defecto.
6. Modulo Indicadores **visible** en mezcla global (no excluido por defecto).

## Indicadores — lectura Operaciones

- Escritura: wrapper `AuditLogService` → `module=indicadores`, `area=operaciones`.
- Lectura Ajustes: `AuditLog::forModule('indicadores')` con filtros `event_type` y `action` — **sin cambio** FEAT-025.
- Migracion historica: `php artisan audit:migrate-indicator-logs --dry-run` luego `--force`. Idempotente via `metadata.migrated_from_indicator_id`. **No borra** `indicator_audit_logs`.

## Retencion

```bash
php artisan audit:purge --dry-run
php artisan audit:purge --force
php artisan audit:purge --months=12 --force
```

Programado mensualmente en `bootstrap/app.php` (`audit:purge --force`). Retencion default: 24 meses.

## Que registrar / que no (v1)

**Registrar:** altas/ediciones de usuarios admin, sync rol/permisos, reset contrasena admin, attach/detach correos notificaciones, create/status/approve/export requisiciones, eventos Indicadores existentes.

**No registrar:** GET de listados, health checks, payloads con adjuntos completos, duplicar historiales de dominio (requisiciones campo a campo), consultas archivo GH, login/logout, cambio contrasena propio del usuario.

**Solo forward:** eventos desde el despliegue; sin backfill de historiales legacy.

## Checklist anti-fallas (sync produccion)

1. Mantener **`AUDIT_QUEUE=false`** en `.env` de produccion; no activar cola en Hostinger compartido.
2. **No** depender de `php artisan queue:work` para persistir auditoria v1.
3. Nunca consultar audit sin paginacion + filtro de fecha en produccion (default UI: 30 dias).
4. No loguear en bucles de import masivo (usar resumen de 1 fila).
5. Invocar audit **post-commit** / post-transaction exitosa; no dentro de transacciones abiertas.
6. Ejecutar / monitorear `audit:purge` programado (24 meses default).
7. No migrar `personal_requisition_change_logs` ni `employee_archive_consultations` al central.
8. Tests usan `RefreshDatabase`; no escribir audit en seeders salvo fixtures.
9. Verificar `AUDIT_ENABLED=true` en produccion; kill switch solo para diagnostico puntual.
10. Tras cambios de permisos en codigo: `php artisan app:sync-permissions` (crea permisos y **actualiza rol super-admin** con el catalogo completo).

## Validacion local

```bash
php artisan test --compact tests/Feature/SystemAuditTest.php
php artisan test --compact tests/Feature/Admin/SystemAuditDefaultDateRangeTest.php
php artisan test --compact tests/Feature/Admin/AdminUserAuditTest.php
php artisan test --compact tests/Feature/Admin/NotificationConfigAuditTest.php
php artisan test --compact tests/Feature/Requisitions/RequisitionAuditTest.php
vendor/bin/pint --dirty --format agent
```

## Archivos clave

- `app/Services/Audit/SystemAuditService.php`
- `app/Services/Admin/AdminAuditLogService.php`
- `app/Services/Admin/UserManagementAuditService.php`
- `app/Services/Requisitions/RequisitionAuditLogService.php`
- `app/Services/Indicadores/AuditLogService.php`
- `app/Jobs/WriteAuditLogJob.php`
- `app/Models/AuditLog.php`
- `app/Support/Audit/AuditEventCatalog.php`
- `app/Http/Controllers/Admin/SystemAuditController.php`
- `app/Providers/AppServiceProvider.php` (Gate `system.view.audit`)
- `app/Console/Commands/MigrateIndicatorAuditLogsCommand.php`
- `app/Console/Commands/PurgeAuditLogsCommand.php`
- `config/audit.php`
- `resources/views/admin/audit/index.blade.php`
- `tests/Feature/SystemAuditTest.php`
- `tests/Feature/Admin/SystemAuditDefaultDateRangeTest.php`
- `tests/Feature/Admin/AdminUserAuditTest.php`
- `tests/Feature/Admin/NotificationConfigAuditTest.php`
- `tests/Feature/Requisitions/RequisitionAuditTest.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-021.md`](../briefs/FEAT-021.md), [`docs/briefs/FEAT-025.md`](../briefs/FEAT-025.md)
- Review: [`docs/reviews/FEAT-025.md`](../reviews/FEAT-025.md)
- Doc usuario: [`docs/user/audit-log.md`](../user/audit-log.md)

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-08-03 | FEAT-021: tabla central, servicio, UI global, wrapper Indicadores |
| 2026-08-03 | Filtros compactos en `/admin/auditoria` |
| 2026-08-11 | FEAT-025: instrumentacion admin/requisitions/notificaciones, wrappers, `UserManagementAuditService`, catalogo v1, default UI 30 dias, politica sync permanente, Gate explicito `system.view.audit`, checklist anti-fallas actualizado |
