# Feature Brief — FEAT-025

> Versión final (Arquitecto). Decisiones de negocio cerradas 2026-08-11 (ver [`FEAT-025-analyst.md`](FEAT-025-analyst.md)).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-025 |
| Modulo / area | **Administracion** (lectura global `/admin/auditoria`) + instrumentacion **cross-modulo** v1: `admin`, `requisitions`, notificaciones admin |
| Titulo | Log general admin cross-modulo (sync, sin cola async) |
| Solicitante | Manuel-E (via `@agent-sj`) |
| Fecha | 2026-08-11 |

## Objetivo

Poblar la tabla central `audit_logs` (FEAT-021) desde los modulos clave de la plataforma para que la pantalla **Auditoria del sistema** deje de percibirse como copia del log de Operaciones/Indicadores y permita al **super-administrador** investigar incidentes, cambios de permisos, requisiciones y configuracion de notificaciones.

**Para quien:** rol `super-admin` con permiso `system.view.audit` (soporte e investigacion operativa).

**Politica operativa:** escritura **sincrona** (`AUDIT_QUEUE=false`) en todos los entornos; sin dependencia de `queue:work` en Hostinger compartido.

## Alcance

### Incluye (v1 — Fase 1)

1. **Wrappers delgados** por dominio delegando a `SystemAuditService` (patron `Indicadores\AuditLogService`).
2. **Instrumentacion** en puntos de mutacion de Admin usuarios, Requisiciones (eventos resumen) y Config notificaciones.
3. **Extension** de `config/audit.php` y `AuditEventCatalog` con taxonomia v1.
4. **UI global Admin:** rango de fechas por defecto **ultimos 30 dias** al abrir; eventos `info` de Indicadores ocultos (comportamiento actual); **sin** excluir modulo Indicadores por defecto.
5. **Politica sync** documentada en `docs/modules/audit-log.md`, `.env.example` y checklist anti-fallas (sustituir recomendacion FEAT-021 de cola en produccion).
6. **Tests** feature por modulo + regresion Operaciones Ajustes → Auditoria + regresion historiales dominio requisiciones.
7. **Documentacion** tecnica y usuario al cierre (`docs/modules/audit-log.md`, `docs/user/audit-log.md`).

### Fuera de alcance

- Fases 2–3 (comercial, suministros, compras, documentos calidad, ficha empleados, archivo GH).
- Migracion retroactiva de `personal_requisition_change_logs`, `personal_requisition_status_logs`, `employee_archive_consultations` ni logs de correo.
- Dual-write permanente en tablas legacy.
- Login/logout, cambio de contrasena propio del usuario, bloqueo por inactivo.
- Export Excel del listado global de auditoria.
- Cambios en permisos (`system.view.audit` sigue solo `super-admin`).
- Filtro por area para roles no super-admin.
- Modificacion de query/UI **Operaciones → Ajustes → Auditoría** (`AuditLog::forModule('indicadores')`).
- Activar cola async en produccion (el codigo de `WriteAuditLogJob` permanece; no se usa en despliegue actual).
- Instrumentar parametros CRUD de requisiciones (`storeParameter`, etc.) ni dashboard/listados GET.

## Reglas de negocio

1. **Solo forward:** eventos desde el despliegue; sin backfill de historiales legacy.
2. **Retencion:** 24 meses via `audit:purge` programado (sin cambio).
3. **Requisiciones — resumen, no detalle:** registrar create, cambio de estado, aprobacion/rechazo gerencia y export Excel; **no** duplicar cambios campo a campo (siguen en `PersonalRequisitionChangeLogger` / pantalla Editar).
4. **Admin usuarios:** registrar alta, actualizacion de perfil, activacion/inactivacion, cambio de rol, sync de permisos y reset de contrasena por admin; **nunca** persistir contrasenas ni hashes en JSON.
5. **Notificaciones:** registrar attach/detach de correo a tipo configurable admin; metadata con modulo, slug del tipo y direccion.
6. **Batch create requisiciones:** un **unico** evento resumen por peticion `store` (metadata `batch_size`, `codes[]`, `requesting_area_key`), no N filas por bucle.
7. **Aprobacion gerencia por correo:** auditar en `RequisitionManagementApprovalService::resolve()` con `user_id` del actor cuando exista; si es flujo email sin sesion, usar el mismo criterio que `status_logs` (`resolveEmailApprovalLogUserId()`) — **no** dejar `user_id` null si hay usuario resoluble.
8. **Sync obligatorio:** `AUDIT_QUEUE=false` en dev, pruebas y produccion; latencia aceptada porque v1 no loguea bucles masivos ni GET.
9. **Kill switch:** `AUDIT_ENABLED=false` sigue siendo no-op global.
10. **Indicadores:** sin cambios de escritura ni de UI Operaciones; visible mezclado en Admin global.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `system.view.audit` | `super-admin` (sin cambios en seeder) | Ver auditoria global del sistema en `/admin/auditoria`. |

**Sin cambios** en `config/access.php`, seeders ni roles. La instrumentacion de escritura no exige permiso adicional (corre en contexto del usuario que ejecuta la accion).

## Rutas

| Metodo | URI | Nombre | Archivo de rutas | Notas |
| --- | --- | --- | --- | --- |
| GET | `/admin/auditoria` | `admin.audit.index` | `routes/web.php` | Sin rutas nuevas. Ajuste de **defaults de filtro** en controlador/vista. |

Rutas donde se **instrumenta escritura** (sin cambio de contrato HTTP):

| Modulo | Rutas / acciones |
| --- | --- |
| Admin usuarios | `admin.users.store`, `admin.users.update` |
| Notificaciones | `admin.notifications.types.emails.attach`, `admin.notifications.types.emails.detach` |
| Requisiciones | `requisitions.store`, `requisitions.update` (solo si cambia estado), `requisitions.export-excel`, `requisitions.tracking-export`, `requisitions.management-approval.decide`, flujo email approval via `RequisitionManagementApprovalService::resolve()` |

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `audit_logs` | **Sin cambio de esquema** | Reutilizar tabla FEAT-021. |

## Taxonomia de eventos (v1)

Convencion: `module` + `event_type` + `action`. Severidad en catalogo: todos `audit` salvo Indicadores existentes.

### Modulo `admin` (`area = null`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `user_management` | `create` | `UserController::store` exitoso | `User` | `new_values`: name, email, document_number, area_key, sede_id, is_active, role; `metadata`: permissions_count |
| `user_management` | `update` | `UserController::update` — cambios de perfil sin rol/permisos/is_active | `User` | Diff acotado: name, email, document_number, area_key, sede_id, must_change_password |
| `user_management` | `activate` | `update` con `is_active` false→true | `User` | `metadata`: previous_is_active |
| `user_management` | `deactivate` | `update` con `is_active` true→false | `User` | idem |
| `user_management` | `role_sync` | `update` con cambio de rol | `User` | `old_values`/`new_values`: role; sin listar permisos aqui |
| `user_management` | `permissions_sync` | `update` con cambio en permisos directos | `User` | `metadata`: added[], removed[] (nombres permiso, max 50 total combinados); counts |
| `user_management` | `password_reset` | `update` con `password` filled | `User` | sin valores secretos; `metadata`: admin_initiated=true |
| `notification_config` | `email_attach` | `NotificationConfigService::addEmailToType` | `NotificationType` | `metadata`: notification_module, type_slug, type_label, email |
| `notification_config` | `email_detach` | `NotificationConfigService::removeEmailFromType` | `NotificationType` | idem |

**Nota implementacion:** capturar `$user->getOriginal()` / estado previo de rol y permisos **antes** del transaction en `update`; emitir solo los sub-eventos que aplicaron (puede coexistir `update` + `permissions_sync` en un mismo guardado).

### Modulo `requisitions` (`area = gestion_humana`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `requisition` | `create` | Fin exitoso `RequisitionController::store` | `PersonalRequisition` (primera del lote) | `metadata`: batch_size, codes[], requesting_area_key, initial_status |
| `requisition` | `status_change` | `RequisitionController::update` si `$oldStatus !== $newStatus` | `PersonalRequisition` | `old_values`: status; `new_values`: status; `metadata`: code, from_label, to_label |
| `management_approval` | `approve` | `RequisitionManagementApprovalService::resolve(..., 'approve', ...)` | `PersonalRequisition` | `metadata`: code, channel (`web`\|`email`), comment (truncado) |
| `management_approval` | `reject` | `resolve(..., 'reject', ...)` | `PersonalRequisition` | idem |
| `export` | `manage_excel` | `RequisitionController::exportExcel` | — | `metadata`: module, filter_hash o filtros aplicados resumidos, row_count |
| `export` | `tracking_excel` | `RequisitionController::trackingExport` | — | idem + mine_only si aplica |

**Explicitamente NO registrar en central:** `changeLogger->logUpdate` (campos), parametros CRUD, `dashboard`, `manage` index, impresion PDF.

## Wrappers a crear

| Clase | Constantes | Ubicacion |
| --- | --- | --- |
| `App\Services\Admin\AdminAuditLogService` | `MODULE = admin`, `AREA = null` | `app/Services/Admin/AdminAuditLogService.php` |
| `App\Services\Requisitions\RequisitionAuditLogService` | `MODULE = requisitions`, `AREA = gestion_humana` | `app/Services/Requisitions/RequisitionAuditLogService.php` |

API espejo del wrapper Indicadores: `logModelChange(...)`, `logEvent(...)` delegando a `SystemAuditService` con module/area fijos.

**Inyeccion:** constructor DI en controladores/servicios; no facades nuevas.

## Puntos de instrumentacion

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/Admin/UserController.php` | `store` | `AdminAuditLogService` | `user_management/create` |
| `app/Http/Controllers/Admin/UserController.php` | `update` | `AdminAuditLogService` | sub-eventos segun diff (ver tabla) |
| `app/Services/Notifications/NotificationConfigService.php` | `addEmailToType` | `AdminAuditLogService` | `notification_config/email_attach` |
| `app/Services/Notifications/NotificationConfigService.php` | `removeEmailFromType` | `AdminAuditLogService` | `notification_config/email_detach` |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | `store` | `RequisitionAuditLogService` | `requisition/create` (1 por request) |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | `update` | `RequisitionAuditLogService` | `requisition/status_change` solo si cambia estado |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | `exportExcel` | `RequisitionAuditLogService` | `export/manage_excel` |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | `trackingExport` | `RequisitionAuditLogService` | `export/tracking_excel` |
| `app/Services/Requisitions/RequisitionManagementApprovalService.php` | `resolve` | `RequisitionAuditLogService` | `management_approval/approve` o `reject` |

**Orden:** invocar audit **despues** de commit exitoso (dentro de transaction OK si sync; evitar audit si transaction rollback). Para `store` requisiciones: despues del `DB::transaction` que crea registros.

**Email approval:** no duplicar en `RequisitionEmailApprovalController`; un solo punto en `resolve()`.

## Cambios `SystemAuditController` y vista

Archivos: `app/Http/Controllers/Admin/SystemAuditController.php`, `resources/views/admin/audit/index.blade.php`.

1. Nueva clave config `audit.default_date_range_days` = **30**.
2. Si la peticion **no** trae `date_from` ni `date_to`, aplicar filtro implicito:
   - `date_from` = hoy − 30 dias (startOfDay)
   - `date_to` = hoy (endOfDay)
3. Pasar a la vista valores efectivos en inputs Desde/Hasta (no dejar campos vacios en carga inicial).
4. Enlace **Limpiar filtros** → `route('admin.audit.index')` sin query string; el controlador reaplica defaults 30 dias.
5. Mantener `filter_lookback_days` = 90 para poblar selects de modulo/area/evento/accion/usuario (solo datos recientes en combos).
6. Mantener exclusion `show_info` via `AuditEventCatalog::globalUiExcludedEventTypes()` sin cambio de semantica.
7. Paginacion 30 sin cambio.

## Cambios `config/audit.php`

```php
'default_date_range_days' => 30,
'filter_lookback_days' => 90, // sin cambio
'queue' => env('AUDIT_QUEUE', false), // politica: mantener false
'modules' => [
    'indicadores' => ['label' => 'Indicadores', 'area' => 'operaciones'],
    'admin' => ['label' => 'Administracion', 'area' => null],
    'requisitions' => ['label' => 'Requisiciones', 'area' => 'gestion_humana'],
],
```

Comentario en archivo: **politica de proyecto — sync permanente; no activar cola en Hostinger compartido.**

Actualizar `.env.example`:

```env
AUDIT_QUEUE=false
# Politica SJ Seguridad: sync en todos los entornos (ver docs/modules/audit-log.md)
```

## Extension `AuditEventCatalog`

Archivo: `app/Support/Audit/AuditEventCatalog.php`.

1. Anadir constantes privadas `ADMIN_EVENTS` y `REQUISITIONS_EVENTS` con severidad `audit` para todas las parejas v1 (documentacion + futuro `log_by_default`).
2. Actualizar `severityFor()` para resolver modulos `admin` y `requisitions`.
3. **No** anadir eventos `info` nuevos en v1 → `globalUiExcludedEventTypes()` sigue derivando solo de Indicadores.

## Capas a implementar

- [ ] Migracion(es) — **no**
- [ ] Modelo(s) — **no** (reutilizar `AuditLog`)
- [ ] Servicio(s) — `AdminAuditLogService`, `RequisitionAuditLogService`
- [ ] Controlador(es) — ajuste `SystemAuditController`; hooks en `UserController`, `RequisitionController`
- [ ] Servicio dominio — hooks en `NotificationConfigService`, `RequisitionManagementApprovalService`
- [ ] Form Request(s) — **no**
- [ ] Vista(s) Blade — ajuste defaults fechas en `admin/audit/index.blade.php`
- [ ] JavaScript — **no**
- [ ] Export Excel — **no** (v1)
- [ ] Tests — ver Task Cards

## Componentes reutilizables

- `App\Services\Audit\SystemAuditService` — API canonica (sin cambios de firma).
- Patron wrapper: `App\Services\Indicadores\AuditLogService`.
- `AuditEventCatalog` — severidad y exclusion UI info.
- Permiso y ruta existentes FEAT-021.

## Documentacion a actualizar

- [ ] `docs/modules/audit-log.md` — eventos v1, wrappers, politica sync, defaults UI 30 dias, puntos de instrumentacion.
- [ ] `docs/user/audit-log.md` — guia super-admin: filtros, default 30 dias, modulos v1, que no incluye historial detallado requisiciones.
- [ ] `docs/INDEX.md` — solo si falta enlace o descripcion desactualizada.
- [ ] **No** crear modulos nuevos en `docs/modules/`.

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/audit.php` | defaults, modulos, politica |
| `.env.example` | comentario AUDIT_QUEUE |
| `app/Support/Audit/AuditEventCatalog.php` | catalogo cross-modulo |
| `app/Http/Controllers/Admin/SystemAuditController.php` | defaults 30 dias |
| `resources/views/admin/audit/index.blade.php` | inputs fecha default |
| `docs/modules/audit-log.md` | doc tecnica |
| `docs/user/audit-log.md` | doc usuario |

**No** tocar `config/access.php` ni `routes/web.php` (salvo que implementacion requiera minimo ajuste de comentarios — no esperado).

## Criterios de aceptacion

1. Tras crear/editar usuario, cambiar permisos o resetear contrasena admin, `/admin/auditoria` muestra eventos `module=admin` con `event_type`/`action` correctos y sin datos sensibles.
2. Tras crear requisicion (simple o lote), cambiar estado, aprobar/rechazar gerencia (web y email) o export Excel, aparecen eventos `module=requisitions`, `area=gestion_humana`.
3. Tras attach/detach correo en notificaciones admin, aparece `notification_config` en `module=admin`.
4. Al abrir `/admin/auditoria` sin parametros, el listado muestra solo **ultimos 30 dias**; inputs Desde/Hasta precargados.
5. Eventos `info` Indicadores (`dashboard_view`, `consolidado_view`) ocultos salvo checkbox Info; modulo Indicadores **visible** por defecto en mezcla global.
6. Operaciones → Ajustes → Auditoria sigue filtrando **solo** `forModule('indicadores')` — test regresion verde.
7. Pantalla Editar requisicion sigue mostrando `change_logs`/`status_logs` completos; sin dual-write campo a campo en central.
8. Con `AUDIT_QUEUE=false`, todos los eventos v1 persisten **sin** `queue:work`.
9. `AUDIT_ENABLED=false` suprime escrituras v1 (no-op).
10. Documentacion tecnica y usuario actualizada; checklist anti-fallas refleja sync permanente.

## Validacion local

1. `php artisan test --compact tests/Feature/SystemAuditTest.php`
2. `php artisan test --compact tests/Feature/Admin/AdminUserAuditTest.php` (nuevo)
3. `php artisan test --compact tests/Feature/Requisitions/RequisitionAuditTest.php` (nuevo)
4. `php artisan test --compact tests/Feature/Admin/NotificationConfigAuditTest.php` (nuevo)
5. `php artisan test --compact tests/Feature/Admin/SystemAuditDefaultDateRangeTest.php` (nuevo)
6. Regresion: tests existentes requisiciones + indicadores auditoria
7. `vendor/bin/pint --dirty --format agent`
8. Smoke manual: super-admin realiza acciones v1 y verifica `/admin/auditoria`

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Latencia sync en exports grandes | Metadata resumida; un evento por export; no loguear filas |
| Email approval sin sesion | Reutilizar `resolveEmailApprovalLogUserId()` para `user_id` |
| Multi-evento en un `update` usuario | Emitir sub-eventos acotados; tests cubren combinaciones |
| Doc FEAT-021 contradice sync prod | Actualizar seccion Operaciones en audit-log.md |
| Confusion resumen vs change_logs | Doc usuario explicita dos fuentes complementarias |

**Dependencia:** FEAT-021 desplegado (tabla, servicio, UI, permiso).

## Task Cards (implementacion)

### T1 — Infra transversal y UI defaults

**Scope:** `config/audit.php`, `.env.example`, `AuditEventCatalog`, wrappers `AdminAuditLogService` + `RequisitionAuditLogService`, `SystemAuditController` + vista audit (default 30 dias).

**DoD:**

- Config keys documentadas.
- Wrappers registrables en container y test unitario minimo de delegacion (module/area).
- GET `/admin/auditoria` sin query aplica rango 30 dias.
- Test `SystemAuditDefaultDateRangeTest`.

---

### T2 — Admin usuarios

**Scope:** `UserController::store`, `UserController::update`, tests `AdminUserAuditTest`.

**DoD:**

- Eventos tabla admin implementados.
- Sin passwords en JSON.
- Permisos diff en metadata acotada.
- Tests: create, update perfil, activate/deactivate, role_sync, permissions_sync, password_reset.

---

### T3 — Requisiciones resumen

**Scope:** `RequisitionController` (store, update status, exports), `RequisitionManagementApprovalService::resolve`, tests `RequisitionAuditTest`.

**DoD:**

- Un evento create por batch.
- status_change solo cuando cambia estado.
- approve/reject web y email.
- Exports con row_count metadata.
- **No** hooks en changeLogger.
- Tests regresion: change_logs/status_logs siguen escribiendose.

---

### T4 — Notificaciones config

**Scope:** `NotificationConfigService` attach/detach, tests `NotificationConfigAuditTest`.

**DoD:**

- attach/detach auditan con metadata completa.
- Test con permiso `manage.notifications`.

---

### T5 — Regresion e integracion

**Scope:** `SystemAuditTest` ampliado, test Operaciones auditoria (IndicadorController seccion auditoria), verificacion `show_info`.

**DoD:**

- Indicadores wrapper sin regresion.
- Operaciones Ajustes query intacta.
- Super-admin 403 sin permiso intacto.

---

### T6 — Documentacion

**Scope:** `docs/modules/audit-log.md`, `docs/user/audit-log.md`, checklist anti-fallas, politica sync.

**DoD:**

- Catalogo eventos v1 tabulado.
- Usuario: como filtrar, default 30 dias, limites vs historiales dominio.
- Eliminar/contrastar recomendacion cola prod FEAT-021.

## Aprobacion

- [x] Analista — vacios cerrados (2026-08-11)
- [x] Arquitecto — brief final (2026-08-11)
- [ ] Usuario — confirmacion
- [ ] AgentSj — Task Cards en `docs/TASKS.md` y orquestacion Feature
