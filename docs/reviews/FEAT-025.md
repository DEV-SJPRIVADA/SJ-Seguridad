# Review Report — FEAT-025

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-025 |
| Fecha | 2026-08-11 |
| Alcance revisado | Rama de trabajo / archivos FEAT-025: wrappers admin/requisitions, `UserManagementAuditService`, hooks en `UserController`, `RequisitionController`, `NotificationConfigService`, `RequisitionManagementApprovalService`, `SystemAuditService` (`userId`), `SystemAuditController` + vista, `config/audit.php`, `AuditEventCatalog`, `.env.example`, `AppServiceProvider` (Gate), tests feature |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `docs/modules/audit-log.md`, `docs/user/audit-log.md` | T6 (Documentador) pendiente: catalogo v1, politica sync permanente, defaults UI 30 dias y guia usuario no actualizados en esta entrega de codigo | Completar en fase Documentador antes del cierre de feature |
| 2 | `RequisitionController` (`exportExcel`, `trackingExport`) | El evento de export se registra antes de generar/descargar el Excel; si falla la exportacion, quedaria un audit sin archivo entregado | Aceptable en v1; mover el log despues de `download()` solo si se reportan falsos positivos |
| 3 | `UserManagementAuditService` / brief | `user_management/create` persiste `document_number`, que en este modulo coincide con la contrasena temporal inicial | Alineado al brief; documentar en doc usuario que no es hash pero correlaciona con credencial inicial |
| 4 | `SystemAuditService` | Escritura sync no usa `afterCommit()` (solo la ruta en cola lo hace); seguro hoy porque los hooks estan post-transaction | Mantener convencion: no invocar audit dentro de `DB::transaction()` |
| 5 | `RequisitionAuditTest` | No hay test espejo de `AUDIT_ENABLED=false` para requisiciones (admin usuarios y notificaciones si lo tienen) | Opcional: anadir un caso para simetria del kill switch |
| 6 | `AppServiceProvider` | Nuevo bypass explicito: `system.view.audit` ya no hereda el `Gate::before` de super-admin | Correcto y testeado; incluir en doc tecnica la razon (permiso debe estar asignado explicitamente) |

## Checklist de revision

- [x] Auth y permisos correctos (`permission:system.view.audit`, `password.changed`; super-admin sin permiso explicito recibe 403)
- [x] Sin registro publico ni bypass de middleware en rutas de escritura
- [x] Validacion de entradas existente (Form Requests de usuarios/notificaciones/requisiciones sin cambio de contrato)
- [x] Sin duplicacion innecesaria (`AdminAuditLogService` / `RequisitionAuditLogService` delgados; logica de diff de usuario en `UserManagementAuditService`)
- [x] Rutas sin cambios nuevos; instrumentacion en controladores/servicios existentes
- [x] Sin migraciones (reutiliza `audit_logs`)
- [x] Export Excel N/A para auditoria global v1
- [x] Tests relevantes presentes (40 tests FEAT-025, todos verdes)

## Seguridad

- **Sin secretos en logs:** tests verifican ausencia de `password`, hashes `$2y$` y permisos acotados (max 50 nombres en diff). `password_reset` solo lleva `admin_initiated: true`.
- **Politica sync:** `AUDIT_QUEUE=false` por defecto en `config/audit.php` y `.env.example`; `SystemAuditTest` confirma persistencia sync y dispatch condicional en cola.
- **Kill switch:** `AUDIT_ENABLED=false` suprime escrituras (admin usuarios y notificaciones testeados).
- **Acceso global:** `/admin/auditoria` protegido; `AppServiceProvider` evita que super-admin acceda sin permiso `system.view.audit` asignado.
- **Email approval sin sesion:** `RequisitionManagementApprovalService::resolve()` pasa `userId` resuelto (`resolveEmailApprovalLogUserId()`), no deja actor null cuando hay usuario resoluble.

## Consistencia con AGENTS.md y docs

- Patron wrapper espejo de `Indicadores\AuditLogService`; delegacion a `SystemAuditService` con `module`/`area` fijos.
- **Operaciones sin regresion:** `IndicadorController` sigue usando `AuditLog::forModule('indicadores')`; test `test_operaciones_ajustes_auditoria_only_shows_indicadores_logs` verde.
- **GH / requisiciones:** `change_logs` y `status_logs` de dominio siguen escribiendose; no hay dual-write campo a campo en central ni hooks en `changeLogger`.
- **Performance:** un evento por batch create, por cambio de estado, por export y por approve/reject; sin logging en bucles.
- **UI defaults:** GET `/admin/auditoria` sin query aplica ultimos 30 dias; inputs precargados; `show_info` oculta eventos info de Indicadores (tests de exclusion/inclusion verdes).
- **Documentacion viva:** pendiente T6 (observacion #1).

## Validacion ejecutada

```text
php artisan test --compact \
  tests/Feature/SystemAuditTest.php \
  tests/Feature/Admin/AdminUserAuditTest.php \
  tests/Feature/Admin/NotificationConfigAuditTest.php \
  tests/Feature/Admin/SystemAuditDefaultDateRangeTest.php \
  tests/Feature/Requisitions/RequisitionAuditTest.php

Tests: 40 passed (162 assertions)
```

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones)
- [ ] Devolver a Agente Feature (si bloqueado)
