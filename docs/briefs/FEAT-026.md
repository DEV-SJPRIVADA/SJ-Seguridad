# Feature Brief — FEAT-026

> Versión final (Arquitecto). Continuación FEAT-025 — instrumentación audit central fase 2.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-026 |
| Modulo / area | **Audit log central** (escritura cross-modulo) + lectura existente `/admin/auditoria` |
| Titulo | Audit log fase 2 — Comercial, Suministros, Compras, Documentos calidad, Ficha empleados |
| Solicitante | Manuel-E (via `@agent-sj`) |
| Fecha | 2026-08-12 |

## Objetivo

Extender la instrumentacion de `audit_logs` (FEAT-021 + FEAT-025) a cinco modulos operativos para que el **super-administrador** pueda investigar cambios en matriz comercial, solicitudes de suministros, compras, documentos de calidad y ficha de empleados desde la UI global **Auditoria del sistema**, sin depender de historiales dispersos.

**Para quien:** rol `super-admin` con permiso `system.view.audit` (sin cambios).

**Politica heredada:** escritura **sincrona** (`AUDIT_QUEUE=false`) en todos los entornos.

## Alcance

### Incluye

1. **Wrappers delgados** por dominio (patron `RequisitionAuditLogService`) delegando a `SystemAuditService`.
2. **Extension** incremental de `config/audit.php` y `AuditEventCatalog` por modulo.
3. **Instrumentacion** en puntos de mutacion identificados abajo (eventos resumen).
4. **Tests** feature por modulo (T1–T5).
5. **Documentacion** incremental en `docs/modules/audit-log.md` (seccion por modulo al cerrar cada Task Card).

### Fuera de alcance

- Migraciones BD, cambios de esquema `audit_logs`.
- Login/logout, cambio de contrasena propio, GET listados, descargas de plantilla vacia.
- Bucles por fila en imports (un evento resumen por peticion).
- Operaciones → Ajustes → Auditoria (`AuditLog::forModule('indicadores')`).
- Archivo GH: `ArchivoController`, `exportArchiveTemplate`, consultas archivo.
- Requisiciones (ya instrumentadas FEAT-025); no dual-write en `change_logs`/`status_logs`.
- Catalogos payroll ficha (`FichaEmpleadosCatalogController`) — fuera de alcance fase 2.
- Cola async (`WriteAuditLogJob` permanece sin uso).
- Export Excel del listado global de auditoria.
- Cambios en permisos o rutas de lectura audit.

## Reglas de negocio

1. **Solo forward:** eventos desde el despliegue; sin backfill.
2. **Sync obligatorio:** `AUDIT_QUEUE=false`; persistencia inline via `SystemAuditService`.
3. **Kill switch:** `AUDIT_ENABLED=false` = no-op global.
4. **Eventos resumen:** create/update/delete, cambios de estado, activate/deactivate; imports/exports masivos = **1 evento** con metadata de conteos/filtros.
5. **Post-commit:** invocar audit **despues** de `DB::transaction` exitoso o al final del metodo tras persistencia confirmada.
6. **Sin datos sensibles:** no paths de archivos completos con tokens; no contenido de observaciones largas (truncar en metadata si aplica).
7. **Area dinamica suministros:** `area` = `area_key` de la solicitud (parametro `{module}` de ruta / campo `SupplyRequest.area_key`).
8. **Area fija** resto: `comercial`, `compras`, `calidad`, `gestion_humana` segun modulo.
9. **Aprobacion director compras:** un solo punto en `PurchaseApprovalService::resolve()` (web + email); no duplicar en `PurchaseEmailApprovalController`.
10. **Ficha empleados:** auditar alta/promocion desde lista espera y cambios de perfil/estado laboral; **no** duplicar flujos requisicion ni archivo.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `system.view.audit` | `super-admin` | Lectura `/admin/auditoria` — **sin cambios** |

La escritura corre en contexto del usuario que ejecuta la accion; no exige permiso adicional.

## Rutas

| Metodo | URI | Nombre | Archivo | Notas |
| --- | --- | --- | --- | --- |
| GET | `/admin/auditoria` | `admin.audit.index` | `routes/web.php` | **Sin cambios** — nuevos modulos aparecen en filtro modulo |

Rutas donde se **instrumenta escritura** (sin cambio de contrato HTTP): ver seccion **Puntos de instrumentacion** por Task Card.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `audit_logs` | **Sin cambio** | Reutilizar tabla FEAT-021 |

## Taxonomia de eventos (fase 2)

Convencion: `module` + `event_type` + `action`. Severidad: todas `audit` (sin eventos `info` nuevos).

### Modulo `commercial` (`area = comercial`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `client` | `create` | `CommercialClientController::store` | `CommercialClient` | `new_values`: nit, name, city; `metadata`: legal_rep_name si presente |
| `client` | `update` | `CommercialClientController::update` | `CommercialClient` | Diff acotado: nit, name, city, legal_rep_name, contact fields |
| `service` | `create` | `CommercialServiceController::store` | `CommercialService` | `metadata`: commercial_client_id, contract_number, portfolio |
| `service` | `update` | `CommercialServiceController::update` | `CommercialService` | Diff acotado: contract_number, portfolio, contract_start/end, advisor_name, is_active |
| `service` | `activate` | `CommercialServiceController::activate` | `CommercialService` | `metadata`: previous_is_active |
| `service` | `deactivate` | `CommercialServiceController::inactivate` | `CommercialService` | idem |
| `checklist` | `update` | `CommercialClientChecklistController::update` | `CommercialClient` | **1 evento por guardado**; `metadata`: documents_updated_count, documentation_expires_on |
| `parameter` | `create` | `CommercialParameterController::store` | modelo parametro | `metadata`: parameter_type (sectors/client-types/…), name, slug si portfolios |
| `parameter` | `update` | `CommercialParameterController::update` | modelo parametro | Diff: name, is_active, sort_order |
| `parameter` | `delete` | `CommercialParameterController::destroy` | modelo parametro | `metadata`: parameter_type, name |
| `import` | `matrix` | `CommercialClientController::import` exitoso | — | `metadata`: clients_created, clients_updated, services_created, services_updated, skipped, empty_rows |
| `export` | `clients_excel` | `CommercialClientController::exportExcel` | — | `metadata`: row_count, filters resumidos (q, city, status) |
| `export` | `services_excel` | `CommercialServiceController::exportExcel` | — | `metadata`: row_count, filters (q, portfolio, vigencia, status) |
| `export` | `checklist_excel` | `CommercialClientChecklistController::exportExcel` | — | `metadata`: row_count, filters |
| `export` | `import_template_data` | `CommercialClientController::exportImportTemplate` | — | `metadata`: row_count (servicios exportados para reimportacion) |

**NO registrar:** `index`, `search`, `create`/`edit` GET, `importTemplate` (plantilla vacia), `downloadImportReport`.

### Modulo `supplies` (`area` = `SupplyRequest.area_key` / param `{module}`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `supply_request` | `create` | `SupplyRequestController::store` post-transaction | `SupplyRequest` | `metadata`: area_key, items_count, sede_id |
| `supply_request` | `quality_approve` | `SupplyRequestController::approvalUpdate` action=approve | `SupplyRequest` | `old_values`/`new_values`: status; `metadata`: items_approved_count |
| `supply_request` | `quality_reject` | `approvalUpdate` action=reject | `SupplyRequest` | idem |
| `supply_product` | `create` | `SupplyProductController::store` | `SupplyProduct` | `new_values`: name, category |
| `supply_product` | `update` | `SupplyProductController::update` | `SupplyProduct` | Diff acotado; si cambia is_active emitir sub-accion activate/deactivate |
| `supply_product` | `activate` | `update` con is_active false→true | `SupplyProduct` | `metadata`: previous_is_active |
| `supply_product` | `deactivate` | `update` con is_active true→false | `SupplyProduct` | idem |
| `export` | `my_requests_excel` | `SupplyRequestController::exportExcel` | — | `metadata`: row_count, area_key |
| `export` | `approval_queue_excel` | `SupplyRequestController::approvalExport` | — | idem |
| `export` | `approved_list_excel` | `SupplyRequestController::approvedExportAll` | — | `metadata`: row_count, filters |
| `export` | `approved_request_excel` | `SupplyRequestController::approvedExport` | `SupplyRequest` | `metadata`: rows_exported |
| `export` | `request_detail_excel` | `SupplyRequestController::exportExcelRequest` | `SupplyRequest` | idem |
| `export` | `catalog_excel` | `SupplyProductController::exportExcel` | — | `metadata`: row_count |

**NO registrar:** `index`, `show`, `create` GET, `exportPdf`, `approvalIndex`/`approvedIndex` GET.

**Nota compras bandeja:** procesamiento de suministros en bandeja compras → modulo `purchase_requests` (T3).

### Modulo `purchase_requests` (`area = compras`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `purchase_request` | `create` | `PurchaseRequestController::store` post-transaction | `PurchaseRequest` | `metadata`: numero_solicitud, area_key, items_count, urgente, aprobador_id |
| `purchase_request` | `resubmit` | `PurchaseRequestController::update` via resubmit | `PurchaseRequest` | `metadata`: numero_solicitud, items_count, previous_estado |
| `director_approval` | `approve` | `PurchaseApprovalService::resolve(..., ESTADO_APROBADO, ...)` | `PurchaseRequest` | `metadata`: numero_solicitud/folio, channel (`web`\|`email`), comentarios truncados |
| `director_approval` | `reject` | `resolve(..., ESTADO_RECHAZADO, ...)` | `PurchaseRequest` | idem |
| `compras_processing` | `status_change` | `PurchaseProcessingController::updatePurchase` si cambia estado_compras | `PurchaseRequest` | `old_values`/`new_values`: estado_compras; `metadata`: folio |
| `supply_compras` | `status_change` | `PurchaseProcessingController::updateSupply` si cambia status | `SupplyRequest` | `old_values`/`new_values`: status; `metadata`: action (`save`\|`complete`), total_cost si complete |
| `export` | `supply_pdf` | `PurchaseProcessingController::exportSupplyPdf` | `SupplyRequest` | `metadata`: supply_request_id |
| `export` | `supply_excel` | `PurchaseProcessingController::exportSupplyExcel` | `SupplyRequest` | `metadata`: rows_exported |

**NO registrar:** `PurchaseRequestController::exportPdf`/`exportExcel` (descarga individual solicitud — lectura); `editPurchase`/`editSupply` GET; side-effect status en `editSupply` GET (mutacion implicita al abrir — fuera de politica explicita); `PurchaseEmailApprovalController` (punto unico en `resolve`).

**Email approval:** pasar `userId` = director en `resolve()` cuando channel=email.

### Modulo `quality_documents` (`area = calidad`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `document` | `create` | `QualityDocumentController::store` post-transaction | `QualityDocument` | `new_values`: code, title, type; `metadata`: areas_count, users_count |
| `document` | `update` | `QualityDocumentController::update` post-transaction | `QualityDocument` | Diff acotado: code, title, process_key, document_type, current_version |
| `document` | `activate` | `toggleStatus` false→true | `QualityDocument` | `metadata`: code |
| `document` | `deactivate` | `toggleStatus` true→false | `QualityDocument` | idem |
| `document` | `delete` | `QualityDocumentController::destroy` | `QualityDocument` | `metadata`: code, title |
| `export` | `admin_excel` | `QualityDocumentController::adminExport` | — | `metadata`: row_count |
| `export` | `library_excel` | `QualityDocumentController::libraryExport` | — | `metadata`: row_count, area module param |
| `export` | `mine_excel` | `QualityDocumentController::mineExport` | — | `metadata`: row_count |

**NO registrar:** `myDocuments`, `libraryIndex`, `adminIndex` GET; `download`/`openLink`/`downloadMine`/`openMine` (lectura archivo).

### Modulo `ficha_empleados` (`area = gestion_humana`)

| event_type | action | Cuando | auditable | old/new / metadata |
| --- | --- | --- | --- | --- |
| `ficha_entry` | `promote` | `FichaEmpleadosController::store` con `ficha_entry_id` | `PersonalRequisitionFichaEntry` | `metadata`: hired_document, requisition_id si existe, source=waiting_list |
| `ficha_entry` | `create` | `store` sin `ficha_entry_id` (alta manual) | `PersonalRequisitionFichaEntry` | `metadata`: hired_document, source=manual |
| `ficha_profile` | `update` | `FichaEmpleadosController::updateFicha` sin cambio employment_status | `EmployeeFichaProfile` | Diff acotado (sin PII excesiva: document_number, employment_status, hire_date, termination_date) |
| `ficha_profile` | `status_change` | `updateFicha` si cambia `employment_status` | `EmployeeFichaProfile` | `old_values`/`new_values`: employment_status; `metadata`: document_number |
| `import` | `profiles` | `FichaEmpleadosController::import` exitoso | — | `metadata`: imported, updated, skipped, empty_rows |
| `export` | `masivos_excel` | `FichaEmpleadosController::exportExcel` | — | `metadata`: row_count, date_range si aplica |
| `export` | `import_template_data` | `FichaEmpleadosController::exportImportTemplate` | — | `metadata`: row_count |

**NO registrar:** `index` GET; `importTemplate`; `exportArchiveTemplate` (archivo GH); `downloadImportReport`; `FichaEmpleadosCatalogController`; flujos requisicion/archivo.

## Wrappers a crear

| Clase | MODULE | AREA | Ubicacion |
| --- | --- | --- | --- |
| `App\Services\Comercial\CommercialAuditLogService` | `commercial` | `comercial` (fijo) | `app/Services/Comercial/CommercialAuditLogService.php` |
| `App\Services\Supplies\SupplyAuditLogService` | `supplies` | **dinamico** por invocacion | `app/Services/Supplies/SupplyAuditLogService.php` |
| `App\Services\PurchaseRequests\PurchaseRequestAuditLogService` | `purchase_requests` | `compras` (fijo) | `app/Services/PurchaseRequests/PurchaseRequestAuditLogService.php` |
| `App\Services\QualityDocuments\QualityDocumentAuditLogService` | `quality_documents` | `calidad` (fijo) | `app/Services/QualityDocuments/QualityDocumentAuditLogService.php` |
| `App\Services\GestionHumana\EmployeeFichaAuditLogService` | `ficha_empleados` | `gestion_humana` (fijo) | `app/Services/GestionHumana/EmployeeFichaAuditLogService.php` |

API espejo de `RequisitionAuditLogService`:

- `logModelChange(...)` y `logEvent(...)` delegando a `SystemAuditService`.
- `SupplyAuditLogService`: parametro adicional `?string $area = null` en ambos metodos (default `null`; obligatorio pasar `area_key` en hooks).

Inyeccion por constructor DI; no facades nuevas.

## Puntos de instrumentacion

### T1 — Comercial

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/Comercial/CommercialClientController.php` | `store` | `CommercialAuditLogService` | `client/create` |
| `app/Http/Controllers/Comercial/CommercialClientController.php` | `update` | `CommercialAuditLogService` | `client/update` |
| `app/Http/Controllers/Comercial/CommercialClientController.php` | `import` | `CommercialAuditLogService` | `import/matrix` (stats del servicio) |
| `app/Http/Controllers/Comercial/CommercialClientController.php` | `exportExcel` | `CommercialAuditLogService` | `export/clients_excel` |
| `app/Http/Controllers/Comercial/CommercialClientController.php` | `exportImportTemplate` | `CommercialAuditLogService` | `export/import_template_data` |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | `store` | `CommercialAuditLogService` | `service/create` |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | `update` | `CommercialAuditLogService` | `service/update` |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | `activate` | `CommercialAuditLogService` | `service/activate` |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | `inactivate` | `CommercialAuditLogService` | `service/deactivate` |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | `exportExcel` | `CommercialAuditLogService` | `export/services_excel` |
| `app/Http/Controllers/Comercial/CommercialClientChecklistController.php` | `update` | `CommercialAuditLogService` | `checklist/update` (1 evento; contar docs tocados) |
| `app/Http/Controllers/Comercial/CommercialClientChecklistController.php` | `exportExcel` | `CommercialAuditLogService` | `export/checklist_excel` |
| `app/Http/Controllers/Comercial/CommercialParameterController.php` | `store` | `CommercialAuditLogService` | `parameter/create` |
| `app/Http/Controllers/Comercial/CommercialParameterController.php` | `update` | `CommercialAuditLogService` | `parameter/update` |
| `app/Http/Controllers/Comercial/CommercialParameterController.php` | `destroy` | `CommercialAuditLogService` | `parameter/delete` |

### T2 — Suministros

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `store` | `SupplyAuditLogService` | `supply_request/create` (area=`$module`) |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `approvalUpdate` | `SupplyAuditLogService` | `quality_approve` o `quality_reject` |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `exportExcel` | `SupplyAuditLogService` | `export/my_requests_excel` |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `approvalExport` | `SupplyAuditLogService` | `export/approval_queue_excel` |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `approvedExportAll` | `SupplyAuditLogService` | `export/approved_list_excel` |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `approvedExport` | `SupplyAuditLogService` | `export/approved_request_excel` |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | `exportExcelRequest` | `SupplyAuditLogService` | `export/request_detail_excel` |
| `app/Http/Controllers/Supplies/SupplyProductController.php` | `store` | `SupplyAuditLogService` | `supply_product/create` (area=`$module`) |
| `app/Http/Controllers/Supplies/SupplyProductController.php` | `update` | `SupplyAuditLogService` | `supply_product/update` y/o activate/deactivate |
| `app/Http/Controllers/Supplies/SupplyProductController.php` | `exportExcel` | `SupplyAuditLogService` | `export/catalog_excel` |

### T3 — Compras

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/PurchaseRequests/PurchaseRequestController.php` | `store` | `PurchaseRequestAuditLogService` | `purchase_request/create` |
| `app/Http/Controllers/PurchaseRequests/PurchaseRequestController.php` | `update` | `PurchaseRequestAuditLogService` | `purchase_request/resubmit` |
| `app/Services/PurchaseRequests/PurchaseApprovalService.php` | `resolve` | `PurchaseRequestAuditLogService` | `director_approval/approve` o `reject` (+ `userId`, channel) |
| `app/Http/Controllers/PurchaseRequests/PurchaseProcessingController.php` | `updatePurchase` | `PurchaseRequestAuditLogService` | `compras_processing/status_change` solo si cambia |
| `app/Http/Controllers/PurchaseRequests/PurchaseProcessingController.php` | `updateSupply` | `PurchaseRequestAuditLogService` | `supply_compras/status_change` |
| `app/Http/Controllers/PurchaseRequests/PurchaseProcessingController.php` | `exportSupplyPdf` | `PurchaseRequestAuditLogService` | `export/supply_pdf` |
| `app/Http/Controllers/PurchaseRequests/PurchaseProcessingController.php` | `exportSupplyExcel` | `PurchaseRequestAuditLogService` | `export/supply_excel` |

### T4 — Documentos calidad

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `store` | `QualityDocumentAuditLogService` | `document/create` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `update` | `QualityDocumentAuditLogService` | `document/update` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `toggleStatus` | `QualityDocumentAuditLogService` | `document/activate` o `deactivate` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `destroy` | `QualityDocumentAuditLogService` | `document/delete` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `adminExport` | `QualityDocumentAuditLogService` | `export/admin_excel` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `libraryExport` | `QualityDocumentAuditLogService` | `export/library_excel` |
| `app/Http/Controllers/QualityDocuments/QualityDocumentController.php` | `mineExport` | `QualityDocumentAuditLogService` | `export/mine_excel` |

### T5 — Ficha empleados

| Archivo | Metodo | Wrapper | Eventos |
| --- | --- | --- | --- |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `store` | `EmployeeFichaAuditLogService` | `ficha_entry/promote` o `create` segun `ficha_entry_id` |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `updateFicha` | `EmployeeFichaAuditLogService` | `ficha_profile/update` y/o `status_change` |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `import` | `EmployeeFichaAuditLogService` | `import/profiles` |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `exportExcel` | `EmployeeFichaAuditLogService` | `export/masivos_excel` |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | `exportImportTemplate` | `EmployeeFichaAuditLogService` | `export/import_template_data` |

## Cambios `config/audit.php`

Anadir entradas en `modules` (incremental por Task Card):

```php
'commercial' => ['label' => 'Comercial', 'area' => 'comercial'],
'supplies' => ['label' => 'Suministros', 'area' => null], // area real en columna area por solicitud
'purchase_requests' => ['label' => 'Compras', 'area' => 'compras'],
'quality_documents' => ['label' => 'Documentos calidad', 'area' => 'calidad'],
'ficha_empleados' => ['label' => 'Ficha empleados', 'area' => 'gestion_humana'],
```

Sin cambio en `queue`, `default_date_range_days`, politica sync.

## Extension `AuditEventCatalog`

Archivo: `app/Support/Audit/AuditEventCatalog.php`.

Por cada Task Card:

1. Anadir constante privada `COMMERCIAL_EVENTS`, `SUPPLIES_EVENTS`, `PURCHASE_REQUESTS_EVENTS`, `QUALITY_DOCUMENTS_EVENTS`, `FICHA_EMPLEADOS_EVENTS`.
2. Extender `severityFor()` match para modulos fase 2.
3. **No** anadir eventos `info` → `globalUiExcludedEventTypes()` sin cambio semantico.

## Capas a implementar

- [ ] Migracion(es) — **no**
- [ ] Modelo(s) — **no**
- [ ] Servicio(s) — 5 wrappers audit
- [ ] Controlador(es) — hooks en controladores listados
- [ ] Servicio dominio — hook en `PurchaseApprovalService::resolve`
- [ ] Form Request(s) — **no**
- [ ] Vista(s) Blade — **no**
- [ ] JavaScript — **no**
- [ ] Export Excel — **no** (solo auditar exports existentes)
- [ ] Tests — 5 archivos feature (ver Task Cards)

## Componentes reutilizables

- `App\Services\Audit\SystemAuditService` — API canonica (sin cambios de firma).
- Patron wrapper: `RequisitionAuditLogService` / `Indicadores\AuditLogService`.
- `AuditEventCatalog` — severidad.
- UI global y permiso FEAT-021/025 sin cambios.

## Documentacion a actualizar

- [ ] `docs/modules/audit-log.md` — secciones modulos fase 2, taxonomia, puntos de instrumentacion
- [ ] `docs/user/audit-log.md` — modulos visibles en filtro, limites vs historiales dominio
- [ ] `docs/INDEX.md` — solo si descripcion desactualizada

## Archivos compartidos (`shared-files`)

| Archivo | Motivo | Task Cards |
| --- | --- | --- |
| `config/audit.php` | registro modulos | T1–T5 (incremental) |
| `app/Support/Audit/AuditEventCatalog.php` | catalogo eventos | T1–T5 (incremental) |
| `docs/modules/audit-log.md` | doc tecnica | T1–T5 + cierre |

**No** tocar `config/access.php`, `routes/web.php`, `SystemAuditController`, Operaciones auditoria.

**Orden recomendado:** T1 → T2 → T3 → T4 → T5 (minimiza conflictos en shared-files).

## Criterios de aceptacion

1. Tras CRUD comercial (cliente, servicio, checklist, parametro), import matriz o exports Excel, `/admin/auditoria` muestra eventos `module=commercial`, `area=comercial`.
2. Tras crear/aprobar/rechazar solicitud suministro o CRUD catalogo, eventos `module=supplies` con `area` = area solicitante.
3. Tras crear/re-enviar solicitud compra, aprobar/rechazar director (web y email) o procesar en bandeja, eventos `module=purchase_requests`, `area=compras`.
4. Tras CRUD documento calidad o exports biblioteca/admin/mis docs, eventos `module=quality_documents`, `area=calidad`.
5. Tras promover lista espera, alta manual, editar ficha, import o export masivos, eventos `module=ficha_empleados`, `area=gestion_humana`.
6. Imports masivos generan **un** evento resumen con conteos; sin filas individuales en audit.
7. `AUDIT_QUEUE=false`: todos los eventos persisten sin `queue:work`.
8. `AUDIT_ENABLED=false` suprime escrituras fase 2.
9. Operaciones → Ajustes → Auditoria sin regresion.
10. Sin hooks en Archivo GH ni requisiciones duplicados.

## Validacion local

Por Task Card al cerrar:

1. `vendor/bin/pint --dirty --format agent`
2. Test del modulo correspondiente (ver DoD)
3. Regresion minima: `php artisan test --compact tests/Feature/SystemAuditTest.php`

Al cierre feature:

4. `php artisan test --compact tests/Feature/**/*Audit*.php`
5. Smoke manual super-admin: accion por modulo + verificar `/admin/auditoria`

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Conflictos merge en `AuditEventCatalog` / `config/audit.php` | Task Cards secuenciales; un modulo a la vez |
| `SupplyAuditLogService` area dinamica | Pasar `$module` explicito en cada hook |
| Email approval compras sin sesion | `userId` = director en `resolve()` |
| Checklist update loop documentos | 1 evento resumen por request |
| `editSupply` GET muta status | No auditar (mutacion implicita); solo `updateSupply` |
| Latencia sync imports grandes | Un evento; metadata conteos only |

**Dependencia:** FEAT-025 desplegado (infra, politica sync, wrappers patron).

## Task Cards (implementacion)

### T1 — Comercial (vertical slice)

**Scope:** `CommercialAuditLogService`, hooks en 4 controladores Comercial, entradas `commercial` en config/catalogo, tests.

**Archivos:**

- `app/Services/Comercial/CommercialAuditLogService.php` (nuevo)
- Controladores Comercial (4)
- `config/audit.php`, `AuditEventCatalog.php`
- `tests/Feature/Comercial/CommercialAuditTest.php` (nuevo)

**DoD:**

- Taxonomia `commercial` implementada.
- Import matriz = 1 evento con stats.
- Exports con `row_count` en metadata.
- Checklist = 1 evento por guardado.
- Tests: client create/update, service create/activate/deactivate, parameter CRUD, import summary, export clients.

---

### T2 — Suministros (vertical slice)

**Scope:** `SupplyAuditLogService` (area dinamica), hooks `SupplyRequestController` + `SupplyProductController`, config/catalogo, tests.

**Archivos:**

- `app/Services/Supplies/SupplyAuditLogService.php` (nuevo)
- `app/Http/Controllers/Supplies/SupplyRequestController.php`
- `app/Http/Controllers/Supplies/SupplyProductController.php`
- `config/audit.php`, `AuditEventCatalog.php`
- `tests/Feature/Supplies/SupplyAuditTest.php` (nuevo)

**DoD:**

- Create solicitud post-transaction con `area_key`.
- Aprobacion calidad approve/reject.
- Producto create/update/activate/deactivate.
- Exports resumen (minimo: create + quality_approve + catalog export).
- Tests: store request, approvalUpdate approve, product update deactivate, export catalog.

---

### T3 — Compras (vertical slice)

**Scope:** `PurchaseRequestAuditLogService`, hooks compras + `PurchaseApprovalService::resolve`, config/catalogo, tests.

**Archivos:**

- `app/Services/PurchaseRequests/PurchaseRequestAuditLogService.php` (nuevo)
- `app/Http/Controllers/PurchaseRequests/PurchaseRequestController.php`
- `app/Http/Controllers/PurchaseRequests/PurchaseProcessingController.php`
- `app/Services/PurchaseRequests/PurchaseApprovalService.php`
- `config/audit.php`, `AuditEventCatalog.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php` (nuevo)

**DoD:**

- Create + resubmit auditados.
- Director approve/reject web y email (un punto `resolve`).
- Bandeja: `updatePurchase` status_change; `updateSupply` complete/save.
- **No** duplicar en `PurchaseEmailApprovalController`.
- Tests: store, resolve approve, email approval reject, updatePurchase status, updateSupply complete.

---

### T4 — Documentos calidad (vertical slice)

**Scope:** `QualityDocumentAuditLogService`, hooks `QualityDocumentController`, config/catalogo, tests.

**Archivos:**

- `app/Services/QualityDocuments/QualityDocumentAuditLogService.php` (nuevo)
- `app/Http/Controllers/QualityDocuments/QualityDocumentController.php`
- `config/audit.php`, `AuditEventCatalog.php`
- `tests/Feature/QualityDocuments/QualityDocumentAuditTest.php` (nuevo)

**DoD:**

- CRUD admin: create, update, toggleStatus, destroy.
- Exports admin/library/mine auditados.
- **No** hooks en download/openLink.
- Tests: store, update, toggle deactivate, destroy, adminExport.

---

### T5 — Ficha empleados (vertical slice)

**Scope:** `EmployeeFichaAuditLogService`, hooks `FichaEmpleadosController` (sin catalogo/archivo), config/catalogo, tests.

**Archivos:**

- `app/Services/GestionHumana/EmployeeFichaAuditLogService.php` (nuevo)
- `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php`
- `config/audit.php`, `AuditEventCatalog.php`
- `tests/Feature/GestionHumana/EmployeeFichaAuditTest.php` (nuevo)

**DoD:**

- Promote desde lista espera vs create manual distinguidos.
- `updateFicha` emite status_change solo si cambia employment_status.
- Import = 1 evento resumen.
- **No** `exportArchiveTemplate`, **no** `FichaEmpleadosCatalogController`, **no** `ArchivoController`.
- Tests: store promote, store manual create, updateFicha status_change, import summary, exportExcel.

---

## Aprobacion

- [x] Arquitecto — brief final (2026-08-12)
- [ ] Usuario — confirmacion
- [ ] AgentSj — Task Cards en `docs/TASKS.md` y orquestacion Feature
