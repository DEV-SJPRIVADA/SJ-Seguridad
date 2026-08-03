# Modulo: Solicitudes de compra

## Objetivo

Gestionar solicitudes de compra libres (multi-linea, fotos por item), con autorizacion por director seleccionado y procesamiento unificado en **Bandeja compras** junto con suministros aprobados por Calidad.

## Alcance actual

- Creacion multi-linea con foto opcional por articulo
- Solicitud **Interno** o **Cliente** (campos adicionales de cliente)
- Director aprobador seleccionado en el formulario (usuarios con rol `director` + `purchase.tab.approval`)
- Autorizacion **in-app**: pestaña Pendientes + formulario Aprobar/Rechazar en detalle (`show`)
- Correo al director solo **notifica** y enlaza al detalle autenticado (no aprueba por correo)
- Bandeja Compras unificada: solicitudes de compra aprobadas + insumos `aprobada_calidad` / `en_compras`
- Export FO-AD-44: PDF y Excel por solicitud de compra; PDF/Excel por suministro en bandeja
- Import legacy desde BD gestion-compras (`purchase-requests:import-legacy`)

## Flujo de estados

```mermaid
stateDiagram-v2
    direction LR
    state Director {
        pendiente --> aprobado: Director aprueba
        pendiente --> rechazado: Director rechaza
    }
    state Compras {
        aprobado --> compras_pendiente: Ingreso bandeja
        compras_pendiente --> en_curso
        en_curso --> completado
        compras_pendiente --> rechazado_compras: Compras rechaza
    }
```

| Campo | Valores | Quien cambia |
| --- | --- | --- |
| `estado` | `pendiente`, `aprobado`, `rechazado` | Director asignado |
| `estado_compras` | `pendiente`, `en_curso`, `completado`, `rechazado` | Equipo Compras (solo si `estado=aprobado`) |

Al aprobar el director: `estado_compras` pasa a `pendiente` y se notifica a Compras (destinatarios configurables en tipos de notificacion).

## Rutas

Prefijo autenticado: `/purchase-requests/{module}/`

| Pestana | Permiso | Rutas |
| --- | --- | --- |
| Nueva solicitud | `purchase.tab.create` | `GET/POST purchase-requests.create`, `store` |
| Mis solicitudes | `purchase.tab.my_requests` | `purchase-requests.index`, `show` |
| Pendientes autorizacion | `purchase.tab.approval` | `purchase-requests.approval.index`, `approval.update` |
| Bandeja compras | `purchase.tab.processing` | `purchase-requests.processing.*` |
| Detalle / export (transversal) | Policy `view` | `purchase-requests.show`, `export.pdf`, `export.excel` |

Rutas publicas legacy (URLs firmadas, sin login en GET redirect):

- `GET/POST purchase-requests/aprobacion-correo/{id}` → redirigen al detalle en plataforma; POST ya no resuelve la solicitud.

Middleware: `purchase.tab:{tab}` via `EnsurePurchaseTabAccess` + `PurchaseAccessService`.

## Permisos y policy

| Permiso | Uso |
| --- | --- |
| `purchase.tab.create` | Crear solicitudes |
| `purchase.tab.my_requests` | Listar propias + ver detalle (policy `view` como dueno) |
| `purchase.tab.approval` | Pendientes + autorizar (policy `approve`) |
| `purchase.tab.processing` | Bandeja y procesamiento (policy `process`) |
| `view.board.{area}.solicitudes_compra` | Alcance sidebar (hogar canonico: area base o Compras) |
| `view.board.compras.bandeja_compras` | Legacy sidebar; la bandeja se accede como pestaña en Solicitudes de compra |

`PurchaseRequestPolicy`:

- `view`: super-admin, compras processing, solicitante, director asignado
- `approve`: director asignado + pendiente
- `process`: compras + solicitud aprobada

Directores: rol `director` incluye `view.board.gestion_humana.requisiciones` (GH) y `view.board.compras.solicitudes_compra` (compras).

## Navegacion (sidebar)

| Perfil | Entrada en menu |
| --- | --- |
| Solicitante | Su `area_key` → Solicitudes de compra |
| Director | Compras → Solicitudes de compra → Pendientes |
| Compras (analista) | Compras → Solicitudes de compra → pestaña Bandeja compras (landing por defecto); Nueva solicitud / Mis solicitudes segun permisos |

La bandeja **no** aparece como tablero separado en sidebar cuando ya es pestaña del modulo Solicitudes de compra.

Config: `board_canonical_areas.solicitudes_compra` en `config/access.php`. Servicio: `SidebarVisibilityService`.

## Base de datos

### `purchase_requests`

Folio visible: `numero_solicitud` (4 digitos, unico). Campos clave: `area_key`, `solicitud_para`, `urgente`, `aprobador_id`, `estado`, `estado_compras`, `fecha_aprobacion`, `comentarios_director`, `comentarios_compras`, `procesado_compras_at`, `procesado_compras_por`.

### `purchase_request_items`

Lineas: `orden`, `cantidad`, `descripcion`, `referencia`, `utilizacion`, `ubicacion`, `foto_path` (disco `public`).

Migracion: `2026_07_31_140100_create_purchase_requests_tables.php`.

## Servicios y controladores

| Componente | Responsabilidad |
| --- | --- |
| `PurchaseRequestController` | CRUD solicitante, show, export PDF/Excel |
| `PurchaseApprovalController` | Lista pendientes del director, PATCH autorizar |
| `PurchaseProcessingController` | Bandeja unificada, procesar compra/suministro |
| `PurchaseApprovalService` | Resolver aprobacion/rechazo director |
| `PurchaseRequestNotificationService` | Correos director, solicitante, compras; persiste log en `purchase_request_mail_logs` |
| `ComprasQueueService` | Mezcla purchase + supply en bandeja con filtros |
| `ComprasDashboardService` | KPIs y graficos del dashboard Compras |
| `ComprasDashboardController` | Vista `compras/dashboard` (ruta `compras.dashboard`) |
| `PurchaseAccessService` | Tabs visibles, directores (`approversQuery`) |
| `PurchaseEmailApprovalController` | Redirect legacy correo → plataforma |

Vistas: `resources/views/modules/purchase-requests/` (create, index, show, approval/, processing/, partials/approval-form.blade.php).

## Notificaciones

Tipos en `notification_types` (modulo `purchase_requests`):

| Slug | Destinatario | Cuando |
| --- | --- | --- |
| `purchase_request_created` | Director asignado | Al crear (cola) |
| `purchase_request_resolved` | Solicitante | Director aprueba/rechaza |
| `purchase_request_approved_for_compras` | Emails configurados | Director aprueba |
| `compras_queue_processed` | Solicitante | Compras actualiza estado |

Correo director: boton enlaza a `purchase-requests.show` (login requerido).

**Registro de correos:** tabla `purchase_request_mail_logs` (tipo, destinatario, estado `enviado`/`fallido`, detalle, fecha). Visible en detalle de la solicitud.

## Mis solicitudes

Lista todas las solicitudes creadas por el usuario autenticado (`user_id`), sin filtrar por `area_key` del modulo en la URL (el area elegida al crear puede diferir del tablero desde el que navega).

## Integracion Suministros

`ComprasQueueService` incluye `SupplyRequest` con status `aprobada_calidad` o `en_compras`. Al abrir un suministro aprobado, pasa automaticamente a `en_compras`. Compras ingresa costos unitarios y puede completar (`completada`). Export FO-AD-44 desde bandeja.

## FO-AD-44

- Compra: `PurchaseRequestPdfService`, `PurchaseRequestExcelExporter` (`App\Exports\BaseExport`)
- Suministro: `SupplyPurchasePdfExporter`, `SupplyPurchaseReportExporter`
- Botones: `<x-export-excel>` en detalle/bandeja

## Import legacy

```bash
php artisan purchase-requests:import-legacy --dry-run
```

Requiere `LEGACY_GESTION_COMPRAS_DB_*` en `.env`. Comando: `ImportLegacyPurchaseRequestsCommand`.

## Reglas de negocio

- Folio correlativo con lock en transaccion al crear
- Director debe estar en `approversQuery()` (rol director, activo, `purchase.tab.approval`)
- Rechazo director: comentarios obligatorios (`UpdatePurchaseApprovalRequest`)
- Solo solicitudes `aprobado` entran en bandeja Compras
- `show` muestra formulario de autorizacion si `@can('approve', $purchaseRequest)`

## Pruebas

- `tests/Feature/PurchaseRequestModuleTest.php`
- `tests/Feature/RoleDirectorMigrationTest.php`
- `tests/Feature/NavigationVisibilityTest.php`

## Control de cambios

| Fecha | Descripcion |
| --- | --- |
| 2026-07-31 | Modulo inicial: CRUD, bandeja, correos, FO-AD-44, import legacy |
| 2026-07-31 | Autorizacion in-app (correo solo notifica); approval-form en show |
| 2026-08-03 | Hogares canonicos sidebar (`SidebarVisibilityService`); permisos director en seeder/migracion |
| 2026-08-03 | Urgente opcional al crear; Mis solicitudes por usuario; registro de correos en detalle |
| 2026-08-03 | PDF solicitud alineado al detalle (metadatos, estilo FO-AD-44); sin registro de correos |
| 2026-08-03 | Dashboard Compras (`compras.dashboard`): KPIs bandeja, tendencias y graficos ApexCharts |
| 2026-08-03 | Bandeja compras: accion Ver detalle; analista compras ve detalle completo y vuelve a bandeja |
| 2026-08-03 | Navegacion unificada: bandeja solo como pestana en Solicitudes de compra; landing analista en bandeja |
| 2026-08-03 | Bandeja compras: filtros estilo Gestion requisiciones (buscar, fechas, pills estado, area/tipo) |

## Referencias

- Guia usuario: [`docs/user/purchase-requests.md`](../user/purchase-requests.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Suministros: [`docs/modules/suministros.md`](suministros.md)
