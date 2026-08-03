# FEAT-021 — Audit log central del sistema

## Objetivo

Introducir una fuente de verdad central (`audit_logs`) para eventos de auditoría cross-módulo, con servicio compartido, salvaguardas de rendimiento (cola, retención, índices), migración segura del módulo Indicadores sin romper su UI, y pantalla global de auditoría para super-admin.

## Alcance v1

- Tabla `audit_logs` con `module`, `area`, morph auditable, JSON old/new/metadata, `change_batch` UUID.
- `SystemAuditService` + `WriteAuditLogJob` (cola opcional con `afterCommit`).
- Wrapper `App\Services\Indicadores\AuditLogService` delegando con `module=indicadores`, `area=operaciones`.
- Comando `audit:migrate-indicator-logs` (idempotente, no borra tabla legacy).
- Comando `audit:purge` + schedule mensual.
- UI global `/admin/auditoria` con permiso `system.view.audit` (solo super-admin por seeder).
- Indicadores Ajustes → auditoría lee `AuditLog::forModule('indicadores')` con mismos filtros.

## Fuera de alcance

- `personal_requisition_change_logs`, `personal_requisition_status_logs`, logs de notificaciones/correo.
- Dual-write de Requisiciones en audit central.

## Criterios de aceptación

1. Indicadores muestra auditoría con mismos filtros; datos migrables con comando.
2. Super-admin accede a auditoría global; otros roles 403.
3. Tests Requisiciones sin regresión.
4. `AUDIT_QUEUE=true` encola job; `AUDIT_ENABLED=false` no-op.
5. Documentación técnica + usuario + checklist anti-fallas.
