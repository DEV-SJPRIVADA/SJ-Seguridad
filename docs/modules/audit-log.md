# Modulo Audit log central

Fuente de verdad cross-modulo para eventos de auditoria de usuario. Complementa (no reemplaza) historiales de dominio embebidos.

**Feature base:** FEAT-021 (tabla, servicio, UI global). **Extension v1:** FEAT-025 (instrumentacion admin, requisiciones, notificaciones; politica sync permanente; default UI 30 dias). **Extension fase 2:** FEAT-026 (comercial, suministros, compras, documentos calidad, ficha empleados).

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

## Modulos instrumentados

Registro en `config/audit.php` → clave `modules`.

### v1 (FEAT-021 / FEAT-025)

| Modulo (`module`) | Etiqueta UI | Area | Wrapper escritura | Lectura dedicada |
| --- | --- | --- | --- | --- |
| `indicadores` | Indicadores | `operaciones` | `App\Services\Indicadores\AuditLogService` | Operaciones → Ajustes → Auditoria (`forModule('indicadores')`) |
| `admin` | Administracion | `null` | `App\Services\Admin\AdminAuditLogService` | Solo UI global |
| `requisitions` | Requisiciones | `gestion_humana` | `App\Services\Requisitions\RequisitionAuditLogService` | Historial dominio en pantalla Editar requisicion |

### Fase 2 (FEAT-026)

| Modulo (`module`) | Etiqueta UI | Area | Wrapper escritura | Lectura dedicada |
| --- | --- | --- | --- | --- |
| `commercial` | Comercial | `comercial` (fijo) | `App\Services\Comercial\CommercialAuditLogService` | Solo UI global |
| `supplies` | Suministros | **dinamica** (`SupplyRequest.area_key` / param `{module}`) | `App\Services\Supplies\SupplyAuditLogService` | Solo UI global |
| `purchase_requests` | Compras | `compras` (fijo) | `App\Services\PurchaseRequests\PurchaseRequestAuditLogService` | Solo UI global |
| `quality_documents` | Documentos calidad | `calidad` (fijo) | `App\Services\QualityDocuments\QualityDocumentAuditLogService` | Solo UI global |
| `ficha_empleados` | Ficha empleados | `gestion_humana` (fijo) | `App\Services\GestionHumana\EmployeeFichaAuditLogService` | Solo UI global |

**Nota suministros:** `config/audit.php` declara `area => null` para el slug; la columna `area` en cada fila refleja el area solicitante de la operacion.

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
| `App\Services\Comercial\CommercialAuditLogService` | `commercial` / `comercial` | Matriz comercial: clientes, servicios, checklist, parametros, import/export |
| `App\Services\Supplies\SupplyAuditLogService` | `supplies` / **dinamico** | Solicitudes suministro + catalogo productos; param `?string $area` obligatorio en hooks |
| `App\Services\PurchaseRequests\PurchaseRequestAuditLogService` | `purchase_requests` / `compras` | Solicitudes compra, aprobacion director, bandeja procesamiento |
| `App\Services\QualityDocuments\QualityDocumentAuditLogService` | `quality_documents` / `calidad` | CRUD documentos calidad + exports |
| `App\Services\GestionHumana\EmployeeFichaAuditLogService` | `ficha_empleados` / `gestion_humana` | Alta/promocion ficha, perfil laboral, import/export masivos |

Inyeccion por constructor; no hay facades nuevas.

**`SupplyAuditLogService`:** `logModelChange(...)` y `logEvent(...)` aceptan parametro adicional `?string $area = null`; cada hook debe pasar `$module` o `area_key` de la solicitud.

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

## Catalogo de eventos

Severidad en `App\Support\Audit\AuditEventCatalog`. Eventos v1 admin, requisitions y fase 2: todos `audit`. Indicadores mantiene eventos `info` y `audit` existentes.

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

### Modulo `commercial` (`area = comercial`) — FEAT-026

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `client` | `create` | `CommercialClientController::store` | `CommercialClient` | `new_values`: nit, name, city; `metadata`: legal_rep_name si presente |
| `client` | `update` | `CommercialClientController::update` | `CommercialClient` | Diff: nit, name, city, legal_rep_name, contact fields |
| `service` | `create` | `CommercialServiceController::store` | `CommercialService` | `metadata`: commercial_client_id, contract_number, portfolio |
| `service` | `update` | `CommercialServiceController::update` | `CommercialService` | Diff: contract_number, portfolio, contract_start/end, advisor_name, is_active |
| `service` | `activate` | `CommercialServiceController::activate` | `CommercialService` | `metadata`: previous_is_active |
| `service` | `deactivate` | `CommercialServiceController::inactivate` | `CommercialService` | idem |
| `checklist` | `update` | `CommercialClientChecklistController::update` | `CommercialClient` | **1 evento por guardado**; `metadata`: documents_updated_count, documentation_expires_on |
| `parameter` | `create` | `CommercialParameterController::store` | modelo parametro | `metadata`: parameter_type, name, slug si portfolios |
| `parameter` | `update` | `CommercialParameterController::update` | modelo parametro | Diff: name, is_active, sort_order |
| `parameter` | `delete` | `CommercialParameterController::destroy` | modelo parametro | `metadata`: parameter_type, name |
| `import` | `matrix` | `CommercialClientController::import` exitoso | — | `metadata`: clients_created, clients_updated, services_created, services_updated, skipped, empty_rows |
| `export` | `clients_excel` | `CommercialClientController::exportExcel` | — | `metadata`: row_count, filters (q, city, status) |
| `export` | `services_excel` | `CommercialServiceController::exportExcel` | — | `metadata`: row_count, filters (q, portfolio, vigencia, status) |
| `export` | `checklist_excel` | `CommercialClientChecklistController::exportExcel` | — | `metadata`: row_count, filters |
| `export` | `import_template_data` | `CommercialClientController::exportImportTemplate` | — | `metadata`: row_count |

**No registrar:** `index`, `search`, GET create/edit, `importTemplate` (plantilla vacia), `downloadImportReport`.

### Modulo `supplies` (`area` = `area_key` dinamico) — FEAT-026

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `supply_request` | `create` | `SupplyRequestController::store` post-transaction | `SupplyRequest` | `metadata`: area_key, items_count, sede_id |
| `supply_request` | `quality_approve` | `SupplyRequestController::approvalUpdate` action=approve | `SupplyRequest` | old/new status; `metadata`: items_approved_count |
| `supply_request` | `quality_reject` | `approvalUpdate` action=reject | `SupplyRequest` | idem |
| `supply_product` | `create` | `SupplyProductController::store` | `SupplyProduct` | `new_values`: name, category |
| `supply_product` | `update` | `SupplyProductController::update` | `SupplyProduct` | Diff acotado; sub-accion activate/deactivate si cambia is_active |
| `supply_product` | `activate` | `update` con is_active false→true | `SupplyProduct` | `metadata`: previous_is_active |
| `supply_product` | `deactivate` | `update` con is_active true→false | `SupplyProduct` | idem |
| `export` | `my_requests_excel` | `SupplyRequestController::exportExcel` | — | `metadata`: row_count, area_key |
| `export` | `approval_queue_excel` | `SupplyRequestController::approvalExport` | — | idem |
| `export` | `approved_list_excel` | `SupplyRequestController::approvedExportAll` | — | `metadata`: row_count, filters |
| `export` | `approved_request_excel` | `SupplyRequestController::approvedExport` | `SupplyRequest` | `metadata`: rows_exported |
| `export` | `request_detail_excel` | `SupplyRequestController::exportExcelRequest` | `SupplyRequest` | idem |
| `export` | `catalog_excel` | `SupplyProductController::exportExcel` | — | `metadata`: row_count |

**No registrar:** `index`, `show`, GET create, `exportPdf`, GET approvalIndex/approvedIndex. Procesamiento bandeja compras → modulo `purchase_requests`.

### Modulo `purchase_requests` (`area = compras`) — FEAT-026

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `purchase_request` | `create` | `PurchaseRequestController::store` post-transaction | `PurchaseRequest` | `metadata`: numero_solicitud, area_key, items_count, urgente, aprobador_id |
| `purchase_request` | `resubmit` | `PurchaseRequestController::update` via resubmit | `PurchaseRequest` | `metadata`: numero_solicitud, items_count, previous_estado |
| `director_approval` | `approve` | `PurchaseApprovalService::resolve(..., ESTADO_APROBADO, ...)` | `PurchaseRequest` | `metadata`: numero_solicitud/folio, channel (web/email), comentarios truncados |
| `director_approval` | `reject` | `resolve(..., ESTADO_RECHAZADO, ...)` | `PurchaseRequest` | idem |
| `compras_processing` | `status_change` | `PurchaseProcessingController::updatePurchase` si cambia estado_compras | `PurchaseRequest` | old/new estado_compras; `metadata`: folio |
| `supply_compras` | `status_change` | `PurchaseProcessingController::updateSupply` si cambia status | `SupplyRequest` | old/new status; `metadata`: action (save/complete), total_cost si complete |
| `export` | `supply_pdf` | `PurchaseProcessingController::exportSupplyPdf` | `SupplyRequest` | `metadata`: supply_request_id |
| `export` | `supply_excel` | `PurchaseProcessingController::exportSupplyExcel` | `SupplyRequest` | `metadata`: rows_exported |

**No registrar:** `exportPdf`/`exportExcel` solicitud individual; GET editPurchase/editSupply; side-effect status en `editSupply` GET. Email approval: un solo punto en `resolve()` con `userId` = director.

### Modulo `quality_documents` (`area = calidad`) — FEAT-026

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `document` | `create` | `QualityDocumentController::store` post-transaction | `QualityDocument` | `new_values`: code, title, type; `metadata`: areas_count, users_count |
| `document` | `update` | `QualityDocumentController::update` post-transaction | `QualityDocument` | Diff: code, title, process_key, document_type, current_version |
| `document` | `activate` | `toggleStatus` false→true | `QualityDocument` | `metadata`: code |
| `document` | `deactivate` | `toggleStatus` true→false | `QualityDocument` | idem |
| `document` | `delete` | `QualityDocumentController::destroy` | `QualityDocument` | `metadata`: code, title |
| `export` | `admin_excel` | `QualityDocumentController::adminExport` | — | `metadata`: row_count |
| `export` | `library_excel` | `QualityDocumentController::libraryExport` | — | `metadata`: row_count, area module param |
| `export` | `mine_excel` | `QualityDocumentController::mineExport` | — | `metadata`: row_count |

**No registrar:** GET listados/biblioteca; `download`, `openLink`, `downloadMine`, `openMine`.

### Modulo `ficha_empleados` (`area = gestion_humana`) — FEAT-026

| event_type | action | Cuando | auditable | Payload resumido |
| --- | --- | --- | --- | --- |
| `ficha_entry` | `promote` | `FichaEmpleadosController::store` con `ficha_entry_id` | `PersonalRequisitionFichaEntry` | `metadata`: hired_document, requisition_id si existe, source=waiting_list |
| `ficha_entry` | `create` | `store` sin `ficha_entry_id` (alta manual) | `PersonalRequisitionFichaEntry` | `metadata`: hired_document, source=manual |
| `ficha_profile` | `update` | `FichaEmpleadosController::updateFicha` sin cambio employment_status | `EmployeeFichaProfile` | Diff acotado (document_number, employment_status, hire_date, termination_date) |
| `ficha_profile` | `status_change` | `updateFicha` si cambia `employment_status` | `EmployeeFichaProfile` | old/new employment_status; `metadata`: document_number |
| `import` | `profiles` | `FichaEmpleadosController::import` exitoso | — | `metadata`: imported, updated, skipped, empty_rows |
| `export` | `masivos_excel` | `FichaEmpleadosController::exportExcel` | — | `metadata`: row_count, date_range si aplica |
| `export` | `import_template_data` | `FichaEmpleadosController::exportImportTemplate` | — | `metadata`: row_count |

**No registrar:** `index` GET; `importTemplate`; `exportArchiveTemplate` (archivo GH); `downloadImportReport`; `FichaEmpleadosCatalogController`; flujos requisicion/archivo.

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

## Puntos de instrumentacion (fase 2 — FEAT-026)

### Comercial

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `CommercialClientController` | `store` | `CommercialAuditLogService` | `client/create` |
| `CommercialClientController` | `update` | `CommercialAuditLogService` | `client/update` |
| `CommercialClientController` | `import` | `CommercialAuditLogService` | `import/matrix` |
| `CommercialClientController` | `exportExcel` | `CommercialAuditLogService` | `export/clients_excel` |
| `CommercialClientController` | `exportImportTemplate` | `CommercialAuditLogService` | `export/import_template_data` |
| `CommercialServiceController` | `store` | `CommercialAuditLogService` | `service/create` |
| `CommercialServiceController` | `update` | `CommercialAuditLogService` | `service/update` |
| `CommercialServiceController` | `activate` | `CommercialAuditLogService` | `service/activate` |
| `CommercialServiceController` | `inactivate` | `CommercialAuditLogService` | `service/deactivate` |
| `CommercialServiceController` | `exportExcel` | `CommercialAuditLogService` | `export/services_excel` |
| `CommercialClientChecklistController` | `update` | `CommercialAuditLogService` | `checklist/update` |
| `CommercialClientChecklistController` | `exportExcel` | `CommercialAuditLogService` | `export/checklist_excel` |
| `CommercialParameterController` | `store` | `CommercialAuditLogService` | `parameter/create` |
| `CommercialParameterController` | `update` | `CommercialAuditLogService` | `parameter/update` |
| `CommercialParameterController` | `destroy` | `CommercialAuditLogService` | `parameter/delete` |

### Suministros

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `SupplyRequestController` | `store` | `SupplyAuditLogService` | `supply_request/create` (area=`$module`) |
| `SupplyRequestController` | `approvalUpdate` | `SupplyAuditLogService` | `quality_approve` o `quality_reject` |
| `SupplyRequestController` | `exportExcel` | `SupplyAuditLogService` | `export/my_requests_excel` |
| `SupplyRequestController` | `approvalExport` | `SupplyAuditLogService` | `export/approval_queue_excel` |
| `SupplyRequestController` | `approvedExportAll` | `SupplyAuditLogService` | `export/approved_list_excel` |
| `SupplyRequestController` | `approvedExport` | `SupplyAuditLogService` | `export/approved_request_excel` |
| `SupplyRequestController` | `exportExcelRequest` | `SupplyAuditLogService` | `export/request_detail_excel` |
| `SupplyProductController` | `store` | `SupplyAuditLogService` | `supply_product/create` (area=`$module`) |
| `SupplyProductController` | `update` | `SupplyAuditLogService` | `supply_product/update` y/o activate/deactivate |
| `SupplyProductController` | `exportExcel` | `SupplyAuditLogService` | `export/catalog_excel` |

### Compras

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `PurchaseRequestController` | `store` | `PurchaseRequestAuditLogService` | `purchase_request/create` |
| `PurchaseRequestController` | `update` | `PurchaseRequestAuditLogService` | `purchase_request/resubmit` |
| `PurchaseApprovalService` | `resolve` | `PurchaseRequestAuditLogService` | `director_approval/approve` o `reject` (+ `userId`, channel) |
| `PurchaseProcessingController` | `updatePurchase` | `PurchaseRequestAuditLogService` | `compras_processing/status_change` solo si cambia |
| `PurchaseProcessingController` | `updateSupply` | `PurchaseRequestAuditLogService` | `supply_compras/status_change` |
| `PurchaseProcessingController` | `exportSupplyPdf` | `PurchaseRequestAuditLogService` | `export/supply_pdf` |
| `PurchaseProcessingController` | `exportSupplyExcel` | `PurchaseRequestAuditLogService` | `export/supply_excel` |

### Documentos calidad

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `QualityDocumentController` | `store` | `QualityDocumentAuditLogService` | `document/create` |
| `QualityDocumentController` | `update` | `QualityDocumentAuditLogService` | `document/update` |
| `QualityDocumentController` | `toggleStatus` | `QualityDocumentAuditLogService` | `document/activate` o `deactivate` |
| `QualityDocumentController` | `destroy` | `QualityDocumentAuditLogService` | `document/delete` |
| `QualityDocumentController` | `adminExport` | `QualityDocumentAuditLogService` | `export/admin_excel` |
| `QualityDocumentController` | `libraryExport` | `QualityDocumentAuditLogService` | `export/library_excel` |
| `QualityDocumentController` | `mineExport` | `QualityDocumentAuditLogService` | `export/mine_excel` |

### Ficha empleados

| Archivo | Metodo | Servicio | Eventos |
| --- | --- | --- | --- |
| `FichaEmpleadosController` | `store` | `EmployeeFichaAuditLogService` | `ficha_entry/promote` o `create` segun `ficha_entry_id` |
| `FichaEmpleadosController` | `updateFicha` | `EmployeeFichaAuditLogService` | `ficha_profile/update` y/o `status_change` |
| `FichaEmpleadosController` | `import` | `EmployeeFichaAuditLogService` | `import/profiles` |
| `FichaEmpleadosController` | `exportExcel` | `EmployeeFichaAuditLogService` | `export/masivos_excel` |
| `FichaEmpleadosController` | `exportImportTemplate` | `EmployeeFichaAuditLogService` | `export/import_template_data` |

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

## Que registrar / que no (v1 + fase 2)

**Registrar:** altas/ediciones de usuarios admin, sync rol/permisos, reset contrasena admin, attach/detach correos notificaciones, create/status/approve/export requisiciones, eventos Indicadores existentes, CRUD comercial (clientes, servicios, checklist, parametros), import matriz comercial, solicitudes suministro (create/aprobacion calidad), catalogo productos suministro, solicitudes compra (create/resubmit/aprobacion director/procesamiento bandeja), CRUD documentos calidad, alta/promocion ficha empleados, cambios perfil/estado laboral ficha, imports/exports masivos fase 2 (1 evento resumen por operacion).

**No registrar:** GET de listados, health checks, payloads con adjuntos completos, duplicar historiales de dominio (requisiciones campo a campo), consultas archivo GH, login/logout, cambio contrasena propio del usuario, descargas plantilla vacia, lectura/descarga de archivos documentos calidad, catalogos payroll ficha, mutacion implicita `editSupply` GET.

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
php artisan test --compact tests/Feature/Comercial/CommercialAuditTest.php
php artisan test --compact tests/Feature/Supplies/SupplyAuditTest.php
php artisan test --compact tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php
php artisan test --compact tests/Feature/QualityDocuments/QualityDocumentAuditTest.php
php artisan test --compact tests/Feature/GestionHumana/EmployeeFichaAuditTest.php
vendor/bin/pint --dirty --format agent
```

## Archivos clave

- `app/Services/Audit/SystemAuditService.php`
- `app/Services/Admin/AdminAuditLogService.php`
- `app/Services/Admin/UserManagementAuditService.php`
- `app/Services/Requisitions/RequisitionAuditLogService.php`
- `app/Services/Indicadores/AuditLogService.php`
- `app/Services/Comercial/CommercialAuditLogService.php`
- `app/Services/Supplies/SupplyAuditLogService.php`
- `app/Services/PurchaseRequests/PurchaseRequestAuditLogService.php`
- `app/Services/PurchaseRequests/PurchaseApprovalService.php`
- `app/Services/QualityDocuments/QualityDocumentAuditLogService.php`
- `app/Services/GestionHumana/EmployeeFichaAuditLogService.php`
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
- `tests/Feature/Comercial/CommercialAuditTest.php`
- `tests/Feature/Supplies/SupplyAuditTest.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php`
- `tests/Feature/QualityDocuments/QualityDocumentAuditTest.php`
- `tests/Feature/GestionHumana/EmployeeFichaAuditTest.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-021.md`](../briefs/FEAT-021.md), [`docs/briefs/FEAT-025.md`](../briefs/FEAT-025.md), [`docs/briefs/FEAT-026.md`](../briefs/FEAT-026.md)
- Review: [`docs/reviews/FEAT-025.md`](../reviews/FEAT-025.md)
- Doc usuario: [`docs/user/audit-log.md`](../user/audit-log.md)

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-08-03 | FEAT-021: tabla central, servicio, UI global, wrapper Indicadores |
| 2026-08-03 | Filtros compactos en `/admin/auditoria` |
| 2026-08-11 | FEAT-025: instrumentacion admin/requisitions/notificaciones, wrappers, `UserManagementAuditService`, catalogo v1, default UI 30 dias, politica sync permanente, Gate explicito `system.view.audit`, checklist anti-fallas actualizado |
| 2026-08-12 | FEAT-026 fase 2: wrappers comercial, suministros (area dinamica), compras, documentos calidad, ficha empleados; taxonomia y puntos de instrumentacion por modulo; tests feature T1–T5; nuevos slugs en `config/audit.php` y `AuditEventCatalog` |
