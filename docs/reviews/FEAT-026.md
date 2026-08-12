# Review Report — FEAT-026

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-026 |
| Fecha | 2026-08-12 |
| Alcance revisado | Audit log fase 2: 5 wrappers (`CommercialAuditLogService`, `SupplyAuditLogService`, `PurchaseRequestAuditLogService`, `QualityDocumentAuditLogService`, `EmployeeFichaAuditLogService`), hooks en controladores Comercial/Suministros/Compras/Documentos calidad/Ficha empleados, `PurchaseApprovalService::resolve`, `config/audit.php`, `AuditEventCatalog`, tests feature T1–T5 |
| Veredicto | **Aprobado con observaciones** |
| **Blockers** | **0** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `config/audit.php` | Falta la entrada `quality_documents` exigida en el brief (`label` + `area = calidad`). Los eventos se persisten con `module=quality_documents`, pero el modulo no aparece en el filtro/labels de `/admin/auditoria`. | Agregar la entrada antes del cierre de feature (shared-files T4). |
| 2 | Varios controladores (exports Excel/PDF) | El evento de export se registra antes de generar/descargar el archivo; si falla la respuesta, quedaria audit sin entrega real. | Patron heredado de FEAT-025; aceptable en v1 salvo reportes de falsos positivos. |
| 3 | `PurchaseApprovalService::resolve()` | `comentarios_director` se persiste en metadata (truncado a 500 chars). Alineado al brief; puede contener contexto operativo sensible. | Documentar en doc usuario que solo super-admin con `system.view.audit` ve estos comentarios. |
| 4 | `FichaEmpleadosController`, `CommercialClientController` | PII acotada en audit (`hired_document`, `document_number`, `phone`, `address`) segun brief; visible para super-admin en auditoria global. | Correcto por diseno; incluir en doc usuario limites vs historiales de dominio. |
| 5 | `QualityDocumentController` | Snapshots de audit excluyen `file_path` y `external_url` (test de create lo confirma). | Mantener esta restriccion en futuros campos de almacenamiento. |
| 6 | `SupplyAuditLogService` | `area` es nullable; todos los hooks actuales pasan `$module` explicitamente. | Opcional: assert/fail-closed si `area === null` en modulo supplies para evitar regresiones. |
| 7 | Tests kill switch | `AUDIT_ENABLED=false` testeado en Comercial y Quality Documents; no hay caso espejo en Supplies, Compras ni Ficha empleados. | Opcional: un test por modulo para simetria del kill switch. |
| 8 | `PurchaseProcessingController::editSupply` | GET muta `status` a `en_compras` sin audit (fuera de politica explicita del brief). | Sin accion; documentado en brief como exclusion intencional. |
| 9 | `docs/modules/audit-log.md` | Seccion fase 2 puede estar incompleta respecto al cierre Documentador. | Completar en fase Documentador (T6). |

## Checklist de revision

- [x] Auth y permisos sin regresion: escritura audit en contexto del usuario autenticado; lectura global sigue en `system.view.audit` (sin cambios FEAT-026)
- [x] Email approval compras: `PurchaseApprovalService::resolve()` atribuye `userId = director` cuando `channel=email`; sin duplicacion en `PurchaseEmailApprovalController`
- [x] Sin rutas publicas nuevas ni bypass de middleware por hooks de audit
- [x] Hooks post-persistencia / post-transaction en puntos criticos (create solicitudes, approval, CRUD documentos, imports)
- [x] Sin paths de archivo, tokens de import ni URLs externas en metadata audit
- [x] Imports masivos = 1 evento resumen (matriz comercial, ficha empleados)
- [x] Checklist comercial = 1 evento por guardado con conteo de documentos
- [x] Area dinamica suministros: `area` = `$module` / `area_key` en todos los hooks revisados
- [x] `AuditEventCatalog` extendido para los 5 modulos fase 2 (severidad `audit`)
- [x] Sin migraciones ni cambios de permisos/rutas de lectura audit
- [x] Tests feature T1–T5 presentes y verdes

## Seguridad

- **Sin secretos en logs:** no se registran `archivo_pedido_path`, `foto_path`, `file_path`, `external_url`, passwords ni tokens de reporte de importacion. Comentarios de director truncados con `Str::limit(..., 500)`.
- **Observaciones de dominio no auditadas:** `quality_observations`, `purchasing_observations`, `observations` de solicitudes suministro quedan fuera de audit central (correcto vs regla 6 del brief).
- **Politica sync:** hooks delegan a `SystemAuditService`; `AUDIT_QUEUE=false` por defecto; tests confirman persistencia inline.
- **Kill switch:** `AUDIT_ENABLED=false` suprime escrituras (testeado en Comercial y Quality Documents).
- **Atribucion de actor:** web usa `auth()->id()`; email approval compras pasa `userId` explicito al director asignado.
- **Acceso a audit logs:** sin cambios; solo super-admin con permiso explicito `system.view.audit` (politica FEAT-025).

## Consistencia con brief FEAT-026

| Modulo | Wrapper | Area | Hooks | Catalogo | Config |
| --- | --- | --- | --- | --- | --- |
| Comercial | OK | `comercial` fijo | 4 controladores | OK | OK |
| Suministros | OK | dinamico | 2 controladores | OK | OK |
| Compras | OK | `compras` fijo | 3 controladores + `resolve` | OK | OK |
| Documentos calidad | OK | `calidad` fijo | 1 controlador | OK | **Falta config** |
| Ficha empleados | OK | `gestion_humana` fijo | 1 controlador | OK | OK |

Exclusiones del brief respetadas: sin hooks en `FichaEmpleadosCatalogController`, `ArchivoController`, `exportArchiveTemplate`, `importTemplate` vacios, descargas de lectura (PDF individual compras, download/openLink documentos), dual-write requisiciones.

## Validacion ejecutada

```text
php artisan test --compact \
  tests/Feature/Comercial/CommercialAuditTest.php \
  tests/Feature/Supplies/SupplyAuditTest.php \
  tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php \
  tests/Feature/QualityDocuments/QualityDocumentAuditTest.php \
  tests/Feature/GestionHumana/EmployeeFichaAuditTest.php \
  tests/Feature/SystemAuditTest.php

Tests: 44 passed (179 assertions)
```

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones — 0 blockers)
- [ ] Devolver a Agente Feature solo si se eleva observacion #1 a requisito de cierre inmediato
