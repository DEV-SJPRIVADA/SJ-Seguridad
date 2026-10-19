# Modulo: Solicitudes de compra

## Objetivo

Gestionar solicitudes de compra libres (multi-linea, fotos por item), con autorizacion por director seleccionado y procesamiento unificado en **Bandeja compras** junto con suministros aprobados por Calidad.

## Alcance actual

- Creacion multi-linea con foto opcional por articulo (disco `public`; sin cambio en FEAT-030)
- Adjuntos **opcionales** multiples a **nivel solicitud** (no por linea): cotizaciones, ordenes, evidencias. Solo visibles en el detalle autenticado
- Solicitud **Interno** o **Cliente** (campos adicionales de cliente)
- Director aprobador seleccionado en el formulario (usuarios con rol `director` + `purchase.tab.approval`)
- Autorizacion **in-app**: pestaña Pendientes + formulario Aprobar/Rechazar en detalle (`show`)
- Autorizacion **por correo**: enlace firmado (sin login) a `aprobacion-correo/{id}` con formulario guest Aprobar/Rechazar; vigencia configurable (`PURCHASE_EMAIL_APPROVAL_LINK_DAYS`, default 7)
- Correo al director incluye boton **Autorizar por correo** (enlace firmado) y alternativa **Ver en la plataforma**
- Bandeja Compras unificada: solicitudes de compra aprobadas + insumos `aprobada_calidad`, `en_compras` o `completada` (historico visible)
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
| Nueva solicitud | `purchase.tab.create` | `GET/POST purchase-requests.create`, `store`; plantilla ítems `items.import-template`; precarga `items.import` |
| Mis solicitudes | `purchase.tab.my_requests` | `purchase-requests.index`, `show` |
| Pendientes autorizacion | `purchase.tab.approval` | `purchase-requests.approval.index`, `approval.update` |
| Bandeja compras | `purchase.tab.processing` | `purchase-requests.processing.*` |
| Proveedores (tablero Compras) | `purchase.providers.view` (edit implica view) | `purchase-requests.providers.index`, `providers.datatable`, `providers.export`, `providers.template` |
| Proveedores (mutaciones) | `purchase.providers.edit` | `providers.create`, `providers.store`, `providers.edit`, `providers.update`, `providers.import`, `providers.import-report` |
| Detalle / export / adjuntos / comentarios (transversal) | Policy `view` / `comment` | `purchase-requests.show`, `export.pdf`, `export.excel`, `attachments.download`, `comments.store` |
| Dashboard Compras | `view.board.compras.dashboard`, `purchase.tab.processing`, o acceso al area Compras | `compras.dashboard` (ver `routes/areas/compras.php`). **No** incluye solo `purchase.tab.approval` (director). |

Rutas publicas (URLs firmadas, sin login):

- `GET purchase-requests/aprobacion-correo/{id}?director={userId}` — formulario guest de autorizacion (vista `email-approval.blade.php`)
- `POST purchase-requests/aprobacion-correo/{id}` — registra aprobacion/rechazo via `PurchaseApprovalService`
- `GET purchase-requests/aprobacion-correo/{id}/pdf` — PDF FO-AD-44 con firma valida

Servicio: `PurchaseEmailApprovalUrlBuilder` genera URLs firmadas para mail y controlador.

Descarga de adjuntos: `GET purchase-requests.attachments.download` (`/solicitud/{purchase_request}/adjuntos/{attachment}`), mismo grupo `auth` + `active` + `password.changed` que `show` (fuera de tabs). `scopeBindings()`: el `{attachment}` debe pertenecer a la solicitud (404 si no). `Gate::authorize('view', $purchaseRequest)`. Disco `local`; no URL publica.

Middleware: `purchase.tab:{tab}` via `EnsurePurchaseTabAccess` + `PurchaseAccessService`.

**Fuera de adjuntos de pedido:** correo al director (`PurchaseRequestCreatedMail`), FO-AD-44 (PDF/Excel) y vista guest `email-approval`. No hay rutas de adjuntos en `routes/modules/purchase-requests-email.php`.

## Permisos y policy

| Permiso | Uso |
| --- | --- |
| `purchase.tab.create` | Crear solicitudes |
| `purchase.tab.my_requests` | Listar propias + ver detalle (policy `view` como dueno) |
| `purchase.tab.approval` | Pendientes + autorizar (policy `approve`) |
| `purchase.tab.processing` | Bandeja y procesamiento (policy `process`); tambien FO-AD-07 (T4) |
| `purchase.providers.view` | Ver, filtrar y exportar el catalogo Proveedores |
| `purchase.providers.edit` | Crear/editar proveedores; **implica view** en `PurchaseAccessService` |
| `view.board.{area}.solicitudes_compra` | Alcance sidebar (hogar canonico: area base o Compras) |
| `view.board.compras.bandeja_compras` | Legacy sidebar; la bandeja se accede como pestaña en Solicitudes de compra |

`PurchaseRequestPolicy`:

- `view`: super-admin, compras processing, solicitante, director asignado — tambien autoriza **ver y descargar** adjuntos (sin permiso nuevo)
- `approve`: director asignado + pendiente
- `process`: compras + solicitud aprobada
- `resubmit`: dueno + `estado=rechazado` + `purchase.tab.my_requests` o `purchase.tab.create` — conservar / agregar / quitar adjuntos al reenviar

Directores: rol `director` incluye `view.board.compras.solicitudes_compra` (compras). No incluye autorizacion de requisiciones cargo nuevo (rol `administrador`).

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

Folio visible: `numero_solicitud` (4 digitos, unico). Campos clave: `area_key`, `solicitud_para`, `urgente`, `aprobador_id`, `estado`, `estado_compras`, `fecha_aprobacion`, `comentarios_director`, `comentarios_compras`, `procesado_compras_at`, `procesado_compras_por`. Si `solicitud_para=Cliente`: `razon_social`, `proyecto_nuevo`, `asume_cliente`, `reinversion` (Si/No). Sin descripcion/justificacion de cabecera (retirados; el detalle vive en lineas).

### `purchase_request_items`

Lineas: `orden`, `cantidad`, `descripcion`, `referencia`, `utilizacion`, `ubicacion`, `foto_path` (disco `public`).

### `purchase_request_attachments`

Relacion **1:N** con `purchase_requests` (`PurchaseRequest::attachments()`, orden `sort_order` + `id`). Una fila por archivo de soporte de **toda** la solicitud.

| Columna | Tipo | Notas |
| --- | --- | --- |
| `purchase_request_id` | FK | `cascadeOnDelete()` |
| `original_name` | string(255) | Nombre que subio el usuario (`Content-Disposition`) |
| `stored_path` | string(500) | Relativo al disco `local`. Nunca URL publica |
| `mime_type` | string(127) nullable | |
| `size_bytes` | unsignedInteger | |
| `sort_order` | unsignedTinyInteger | 1-based |

Disco: `local` (`storage/app/private`). Path: `purchase-requests/{id}/{uuid}.{ext}` (`config/purchase-requests.php` → `attachments.disk` / `directory`). Topes: `max_files` 5, `max_kilobytes` 10240, `mimes` pdf/doc/docx/xls/xlsx/ppt/pptx/jpg/jpeg/png/webp.

Migracion: `2026_08_28_160752_create_purchase_request_attachments_table.php` (tabla + backfill desde legado + `UPDATE archivo_pedido_path = NULL`). `down()` solo `dropIfExists` de la tabla nueva.

### `purchase_providers`

Catalogo FEAT-047 T2. Migracion `2026_10_19_064916_create_purchase_providers_table.php` (multi-driver: `estado` string, sin `enum()`).

| Columna | Tipo | Notas |
| --- | --- | --- |
| `identificacion` | string(50) unique | NIT / cedula / codigo; no se pasa a mayusculas |
| `nombre` | string(255) | Razon social (se persiste en MAYUSCULAS) |
| `telefono` | string(50) nullable | |
| `correo` | string(255) nullable | Se conserva tal cual (sin uppercase) |
| `estado` | string(20) default `activo` | `activo` / `inactivo`, indexado |
| `dias_pago` | unsignedSmallInteger nullable | Editable en alta/edicion; import VINE01 (`DIAS_PAGO`, opcional) |
| `contacto`, `direccion`, `ciudad`, `forma_pago` | strings nullable | Datos FO-AD-07; texto en MAYUSCULAS |
| `created_by` | FK users nullable | `nullOnDelete()` |

Indices: `estado`. Modelo `App\Models\PurchaseProvider` (`dias_pago` int, `estadoLabels()`). La tabla **solo contiene proveedores**; no existen columnas de clasificacion (`es_cliente` / `es_proveedor`).

### `purchase_requests.archivo_pedido_path` (deprecada)

Columna **permanece** nullable. **No DROP.** Ya no esta en `$fillable`. Store, resubmit, import y UI **no** la leen ni escriben. Fuente de verdad: `purchase_request_attachments`. Valores previos se copiaron a 1:N (disco `public` → `local` si el fichero existia) y la columna quedo en `NULL`.

Migraciones: `2026_07_31_140100_create_purchase_requests_tables.php`, `2026_08_20_082215_drop_descripcion_justificacion_from_purchase_requests_table.php`, `2026_08_28_160752_create_purchase_request_attachments_table.php`.

## Servicios y controladores

| Componente | Responsabilidad |
| --- | --- |
| `PurchaseRequestController` | CRUD solicitante, show, export PDF/Excel, `downloadAttachment` |
| `PurchaseApprovalController` | Lista pendientes del director, PATCH autorizar |
| `PurchaseProcessingController` | Bandeja unificada, procesar compra/suministro |
| `PurchaseApprovalService` | Resolver aprobacion/rechazo director |
| `PurchaseRequestNotificationService` | Correos director, solicitante, compras; persiste log en `purchase_request_mail_logs` |
| `ComprasQueueService` | Mezcla purchase + supply en bandeja con filtros |
| `ComprasQueueFilterBag` | Parametros de filtro bandeja (fechas, area, tipo, estado compras) |
| `ComprasDashboardService` | KPIs y graficos del dashboard Compras; bandeja usa la misma logica que `ComprasQueueService` |
| `ComprasDashboardController` | Vista `compras/dashboard` (ruta `compras.dashboard`) |
| `PurchaseAccessService` | Tabs visibles de Solicitudes de compra, `canViewProviders` / `canEditProviders`, `canViewProvidersBoard` (tablero Proveedores), directores (`approversQuery`) |
| `PurchaseProviderController` | CRUD proveedores (tablero Compras), datatable server-side, export Excel, plantilla/import VINE01, audit `provider_created` / `provider_updated` / `provider_export` / `providers_imported` |
| `PurchaseProviderDatatableService` | Filtros (`q`, `estado`), query, respuesta DataTables y retorno de filtros |
| `PurchaseProviderImportService` | Import masivo VINE01 (solo `PROVEEDOR=SI`, upsert por `identificacion`, `DIAS_PAGO` opcional, soporte `dry-run`/`limit`) |
| `PurchaseProviderImportTemplateExport` | Plantilla Excel `VINE01` (hoja `VINE01` con encabezados + hoja `Instrucciones`) |
| `Store/UpdatePurchaseProviderRequest` | Autorizacion `providers.edit`, validacion unique/`dias_pago`, telefono numerico, ciudad contra `requisition_cities`, uppercase de textos (excepto correo/identificacion/telefono/estado/ciudad) |
| `ImportPurchaseProvidersRequest` | Autorizacion `providers.edit`, validacion de `import_file` (xlsx/xls/csv, tope `providers_import_max_kilobytes`) |
| `PurchaseEmailApprovalController` | Autorizacion guest por enlace firmado (show/update/pdf). **No** lista adjuntos de pedido |
| `PurchaseRequestAttachmentService` | Guardar en disco `local`, sincronizar en resubmit (keep/quitar/agregar), mapear legado |
| `PurchaseRequestResubmitService` | Reenvio de rechazada (items + adjuntos via attachment service). No toca `archivo_pedido_path` |

Vistas: `resources/views/modules/purchase-requests/` (create, index, show, edit, approval/, processing/, partials/approval-form.blade.php). Dashboard: `resources/views/areas/compras/dashboard.blade.php`.

UI create/show/edit/procesar (compra y suministro): layout `pur-req-form-layout` / `pur-req-detail-layout` (main + aside), secciones FO-AD-44, estilos `pur-req-*` en `resources/css/app.css`. Detalle y procesamiento: volver contextual e iconos chrome (`.req-manage-filters__icon-btn`). Reabrir (edit) alineado a Nueva solicitud. `process-supply`: costeo por linea + completar. **Productos (create/edit):** tarjetas `.purchase-item-card` (Descripcion a ancho completo + grilla Referencia/Utilizacion/Ubicacion/Cantidad+Foto); JS `purchase-request-form.js`.
Listados Mis solicitudes / Bandeja: `purchase-requests-page--list` con intro, resumen de conteo (bandeja) y panel de listado operativo; filtros bandeja conservan IDs y auto-submit.

Control **Adjuntos** en cabecera de `create.blade.php` y `edit.blade.php` (despues de la tabla de productos, antes de Enviar / Reenviar): `attachments[]` multiple, opcional. En edit: `keep_attachment_ids[]` para conservar; quitar en UI elimina el hidden. Detalle `show.blade.php`: bloque Adjuntos (nombre, tamano, enlace `attachments.download`) solo si hay filas. JS: `resources/js/purchase-request-form.js`. **No** en mail, PDF FO-AD-44 ni `email-approval`.

### Carga masiva de ítems (Nueva solicitud)

- Plantilla vacía: `GET purchase-requests.items.import-template` → `PurchaseRequestItemsImportTemplateExport` (columnas `config('purchase-requests.items_import_columns')`: cantidad, descripcion, referencia, utilizacion, ubicacion; fila 1 claves, fila 2 etiquetas, datos desde fila 3).
- Precarga: `POST purchase-requests.items.import` (JSON) → `PurchaseRequestItemsImportService`; el JS reemplaza las filas de la tabla. La foto no va en Excel (se agrega en pantalla).
- Máximo: `items_import_max_rows` (default 200). Mismo permiso `purchase.tab.create`.

### Comentarios del hilo (post-envio)

- Tabla `purchase_request_comments` (`body`, `user_id`, FK a solicitud).
- Modelo `PurchaseRequestComment`; relación `PurchaseRequest::comments()`.
- Policy `comment` = misma regla que `view`, salvo si `estado_compras = completado` (hilo cerrado). `Gate::before` de super-admin **no** corta esta ability (igual que `system.view.audit`).
- FormRequest y vista también exigen `puedeComentar()` por defensa en profundidad.
- Ruta `POST purchase-requests.comments.store` → detalle `#purchase-request-comments`.
- UI en `show.blade.php` (lista cronológica + formulario). Independiente de `comentarios_director` / `comentarios_compras`.
- Audit: `purchase_request` / `comment` con `comment_id` y `body_length` (sin texto completo).
- Subnav en `show`: query `from=mis_solicitudes|approval|processing`; si falta, dueño → Mis solicitudes; director asignado pendiente → Pendientes; resto con processing → Bandeja. No usar `Gate::can('approve')` (super-admin lo pasa siempre).
- Mis solicitudes lista también `estado_compras` cuando existe.
## Bandeja compras — filtros y listado

`ComprasQueueFilterBag` + vista `processing/index` (estilo filtros GH):

| Filtro | Campo / efecto |
| --- | --- |
| Fecha inicio / fin | Compras y suministros: `created_at` (fecha de solicitud) |
| Area solicitante | `area_key` |
| Tipo | `purchase` \| `supply` |
| Estado (pills) | `estado_compras`: pendiente, en_curso, completado, rechazado |

Reglas de volumen (`ComprasQueueService`):

- Sin rango de fechas: maximo **200** registros mas recientes (`truncated` + aviso en UI).
- Con rango de fechas: todos los que coincidan (sin tope).

La tabla DataTables ordena por **fecha descendente** (`data-order` en la columna Fecha) para no empujar los suministros detras de todas las solicitudes de compra (el default `[[0, asc]]` por Tipo ocultaba "Suministro" en paginas siguientes). Los filtros Area/Tipo (searchable-select) autoenvian el GET al cambiar, igual que las fechas.

Acciones por fila (icon-only): **Ver detalle** (`eye`) — compra → `purchase-requests.show`; suministro → `supplies.show` con `module=area_key` del pedido. **Procesar** (`cog`) → rutas `processing.purchase` / `processing.supply`. Limpiar filtros: icono `x` en toolbar.

## Dashboard Compras (`compras.dashboard`)

Ruta: `GET /compras/dashboard`. Acceso: permiso `view.board.compras.dashboard`, `purchase.tab.processing`, `view.area.compras` o `manage.area.compras`. El rol **director** (`purchase.tab.approval` solo) **no** accede al dashboard; usa Solicitudes de compra → Pendientes autorizacion.

Filtros globales: **ano**, **mes** (opcional), **area solicitante**, **tipo**.

### KPIs visibles (2026-08-03)

| KPI | Logica | Filtro fecha |
| --- | --- | --- |
| Pendientes director | `estado=pendiente` (solo area) | No usa ano/mes |
| En bandeja | Total en `ComprasQueueService` | Si: convierte ano/mes a `date_from`/`date_to` |
| Bandeja pendiente | `estado_compras=pendiente` (+ suministro `aprobada_calidad`) | Igual que bandeja |
| En curso | `estado_compras=en_curso` (+ suministro `en_compras`) | Igual que bandeja |
| Completadas | Compras: `procesado_compras_at`; suministros: `updated_at` con `status=completada` | Ano/mes del dashboard |

Los KPIs de bandeja enlazan a `purchase-requests.processing.index` con los mismos parametros de fecha/area/tipo. Graficos ApexCharts: tendencia anual, estado solicitudes (periodo), bandeja por estado (periodo), top areas, bandeja por tipo. JS: `resources/js/compras-dashboard-charts.js`.

**Retirados:** KPIs "Solicitudes en periodo" y "Urgentes en bandeja" (2026-08-03).

Conversión dashboard → bandeja: mes seleccionado → primer/ultimo dia del mes; mes "Todos" + ano → `YYYY-01-01` … `YYYY-12-31` (`ComprasQueueFilterBag::dateRangeFromDashboardFilters`).

## Notificaciones

Tipos en `notification_types` (modulo `purchase_requests`):

| Slug | Destinatario | Cuando |
| --- | --- | --- |
| `purchase_request_created` | Director asignado | Al crear (envio sincrono `Mail::send`) |
| `purchase_request_resolved` | Solicitante | Director aprueba/rechaza |
| `purchase_request_approved_for_compras` | Emails configurados | Director aprueba |
| `compras_queue_processed` | Solicitante | Compras actualiza estado |

Correo director: boton principal **Autorizar por correo** (URL firmada a `email-approval.show`) y secundario a `purchase-requests.show` (login requerido en plataforma). Enlaces generados con `PUBLIC_APP_URL` (`App\Support\ApplicationUrls`) para que sean alcanzables desde VPN/red interna. PDF FO-AD-44 adjunto en el correo de nueva solicitud. **Los adjuntos de pedido no viajan en el correo** (ni MIME ni enlaces); el director los consulta en el detalle autenticado.

**Registro de correos:** tabla `purchase_request_mail_logs` (tipo, destinatario, estado `enviado`/`fallido`, detalle, fecha). Visible en detalle de la solicitud. Si falla el envio al crear, aviso amarillo en formulario Nueva solicitud.

## Mis solicitudes

Lista todas las solicitudes creadas por el usuario autenticado (`user_id`), sin filtrar por `area_key` del modulo en la URL (el area elegida al crear puede diferir del tablero desde el que navega).

## Integracion Suministros

`ComprasQueueService` incluye solicitudes de compra aprobadas (todos los `estado_compras`, incluido completado) y suministros con status `aprobada_calidad`, `en_compras` o `completada`. Sin filtro de fechas muestra hasta 200 registros mas recientes; con rango de fechas trae todos los del periodo.

- **Ver detalle** suministro: `supplies.show` — vista alineada a `purchase-requests.show` (metadatos en grid, tabla de lineas, export PDF/Excel FO-AD-44). Si el usuario tiene `purchase.tab.processing`, subnav de Solicitudes de compra y boton "Volver a bandeja".
- **Procesar** suministro: `purchase-requests.processing.supply` (costos, completar).
- Al abrir procesamiento desde bandeja, suministro `aprobada_calidad` pasa a `en_compras`.
- Export FO-AD-44 suministro: `supplies.export.pdf` / `supplies.export.excel` (detalle) o `purchase-requests.processing.supply.pdf` (bandeja).

## Proveedores (FEAT-047 T2)

**Tablero** del área Compras (no es pestaña del subnav de Solicitudes de compra). En `config/access.php` vive en `boards.proveedores` y `board_canonical_areas.proveedores` (home `compras`); ya **no** está en `purchase_tabs`. El sidebar lo resuelve `NavigationResolver` y la visibilidad `SidebarVisibilityService::shouldShowProvidersBoard` -> `PurchaseAccessService::canViewProvidersBoard`. Visible con `purchase.providers.view`; `purchase.providers.edit` implica view (`PurchaseAccessService::canViewProviders`).

- **Rutas:** `GET /purchase-requests/{module}/proveedores` (index), `/proveedores/datatable`, `/proveedores/exportar`, `/proveedores/plantilla` (descarga VINE01); mutaciones `/proveedores/crear` (GET), `/proveedores` (POST), `/proveedores/importar` (POST), `/proveedores/importar/reporte/{token}` (GET, reporte de fallos de la carga), `/proveedores/{purchase_provider}/editar` (GET), `/proveedores/{purchase_provider}` (PATCH). El grupo de mutaciones usa middleware `purchase.tab:providers.edit`; el listado/plantilla, `purchase.tab:providers`. El prefijo `purchase-requests` se conserva; el tablero se activa por el módulo `compras` de la ruta.
- **Listado server-side** (`PurchaseProviderDatatableService`): la tabla solo contiene proveedores. Default `estado = activo`; el filtro `estado` (`activo` / `inactivo` / `todos`) es el único del panel. El **buscador** es el de DataTables (`search[value]`), que consulta identificacion, nombre, correo, contacto y ciudad. No hay clasificacion cliente/proveedor en pantalla (las filas que no son proveedor se omiten en la carga masiva). Pagina 10/25/50/100.
- **Chrome del listado:** `panel` + `panel__body panel__body--compact req-manage-shell`, sin titulo hero (el tablero ya se nombra en la franja "Tableros del área"). Los filtros van en `<details class="req-manage-filters__panel ...">` con toggle plegable y badge **Activos** cuando hay filtros aplicados; el filtro **Estado** y los iconos **Aplicar/Limpiar** comparten la misma fila (`purchase-providers-page__filter-grid` + `__filter-actions`, botones contiguos al filtro). La **barra de acciones** (`cursos-registros-page__table-toolbar` + `purchase-providers-page__table-toolbar`) es **siempre visible**: conteo a la izquierda y, a la derecha, icon-only **Exportar**, **Importar VINE01** (solo con permiso de edición) y **Nuevo proveedor**. La **plantilla VINE01** se descarga desde el modal de importación. Encabezados de tabla en mayúsculas. El listado **ocupa todo el alto del área principal** (`.app-main:has(.purchase-providers-page.req-manage-page)` + `req-manage-shell`, patrón de Cursos/Seguimientos), de modo que la tabla scrollea internamente y no queda espacio vacío al pie.
- **Modal de carga VINE01:** mismas acciones icon-only del chrome (`.req-manage-filters__icon-btn`): `--ghost` para descargar plantilla, elegir archivo y cerrar; `--primary` para importar. Todos con `title`/`aria-label`. La fila `.purchase-providers-page__import-toolbar` alinea plantilla + nombre del archivo + acciones (referencia: modal de import de Formación).
- **Alta/edicion:** identificacion (unica), nombre, telefono, correo, contacto, direccion, ciudad, forma de pago, `dias_pago` (0–3650) y estado. **Sin** clasificacion cliente/proveedor. Botones de la cabecera y del formulario son **icon-only** con `title`/`aria-label` (`.req-manage-filters__icon-btn`); la accion de fila usa `.cursos-catalogo-page__icon-btn`. Shell visual `purchase-requests-page--form` + `pur-req-form` (meta + secciones); vista compartida `providers/partials/form.blade.php`.
- **Validaciones:** `telefono` solo numerico (`digits_between:7,15`; input `type="tel"` `inputmode="numeric"` `pattern="[0-9]{7,15}"`); `correo` email; `ciudad` debe existir en el catálogo de Requisiciones (`requisition_cities` activas) y se elige con `<x-searchable-select>`.
- **Mayusculas:** `Store/UpdatePurchaseProviderRequest` usa `UppercasesValidatedText`; se guardan en MAYUSCULAS nombre, contacto, direccion y forma de pago. Se conservan sin cambios correo, identificacion, telefono, estado y **ciudad** (valor del catálogo, ej. `Cali`).
- **Export Excel:** `BaseExport` (identificacion, nombre, telefono, correo, contacto, direccion, ciudad, forma de pago, dias de pago, estado) con los filtros activos; el término del buscador se sincroniza a `q` en el enlace de export.
- **Filtros conservados:** el enlace de editar y el redirect tras guardar llevan `q` y `estado` del query string, sin mezclarlos con los campos del formulario.
- **Auditoria:** eventos `provider_created`, `provider_updated`, `provider_export` y `providers_imported` en modulo `purchase_requests` (area `compras`).

### Carga masiva VINE01 (FEAT-047 T3)

- **Plantilla:** `GET purchase-requests.providers.template` -> `PurchaseProviderImportTemplateExport` (`plantilla_proveedores_VINE01.xlsx`). Hoja `VINE01` (fila 1 = encabezados oficiales, datos desde fila 2) + hoja `Instrucciones`. Columnas (`config('purchase-requests.providers_import_columns')`): `IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `PROVEEDOR`, `ESTADO`, `DIAS_PAGO` (opcional).
- **Import:** `POST purchase-requests.providers.import` (multipart, `import_file` xlsx/xls/csv) -> `PurchaseProviderImportService`.
- **Reglas:** solo se cargan filas con `PROVEEDOR = SI` (normaliza `SI/SÍ/1/X/TRUE`); upsert por `identificacion`; `TELEFONO` se guarda solo con digitos; `NOMBRE` en MAYUSCULAS; `ESTADO` `ACTIVO`/`INACTIVO` (default `activo`); `DIAS_PAGO` entero 0–3650 o vacio. Las columnas `CLIENTE` de archivos de origen se ignoran. Los campos FO-AD-07 extra (contacto, direccion, ciudad, forma de pago) no vienen en la plantilla y se editan en pantalla.
- **Reporte:** el resultado **no** se muestra como alerta superior. El redirect al listado deja el resumen en el modal compartido `<x-import-result-modal>` (`download-route` `purchase-requests.providers.import-report`, `download-params` `['module' => $module]`): conteos (nuevos, actualizados, omitidos por no ser proveedor, vacias, con error) y, si hay filas invalidas, las primeras 50 con numero de fila y motivo. Si hay fallos, el modal ofrece la **descarga del reporte** `.xlsx` (`GET purchase-requests.providers.import-report` -> `/proveedores/importar/reporte/{token}`, token en cache 24 h, propiedad por usuario; otro usuario recibe 403). Un archivo sin encabezados obligatorios o sin filas de proveedor se rechaza con error en `import_file` (reapertura del modal de carga). El servicio devuelve `failures` estructuradas (`row`, `identifier`, `field`, `reason`, `raw`) ademas de `errors`.
- **Soporte CLI/service:** `import(string $path, bool $dryRun = false, ?int $limit = null, ?int $userId = null)` permite simulacion (`dry_run`) y limite de filas para pruebas; tope `providers_import_max_rows` (default 20000).
- **Comando Artisan:** `php artisan purchase-providers:import {path} [--dry-run] [--limit=N]` (mismo servicio; resumen por tabla).

Vistas: `resources/views/modules/purchase-requests/providers/{index,create,edit}.blade.php` + `providers/partials/{form,import-modal}.blade.php`.

## FO-AD-44

Alineado a formato oficial **Marzo 2026 Versión 01** (FEAT-047 Fase A / T1):

- Encabezado: código `FO-AD-44`, periodo `Marzo 2026`, `Versión 01`, `Página 1 de 1` (`config/purchase-requests.php` / `config/supplies.php`)
- Columnas: Cantidad | Foto | Descripción | Referencia | Utilización | Ubicación
- Foto de ítem embebida si existe en disco `public` (`foto_path`); si no, placeholder `S/A` (no `N/A` fijo sin intentar)
- Compra: `PurchaseRequestPdfService` → `pdf/purchase-request-solicitud.blade.php`; Excel `PurchaseRequestExcelExporter`
- Suministro: `SupplyPurchasePdfExporter` → `pdf/supply-request-solicitud.blade.php` (**mismo layout visual** que compra); Excel `SupplyPurchaseReportExporter` (catálogo de insumos aún sin foto por producto → `S/A`)
- Botones: detalle compra, detalle suministro y bandeja compras (suministro)
- **No** embebe adjuntos de pedido (FEAT-030)
- Tests: `tests/Feature/PurchaseRequests/FoAd44ExportAlignmentTest.php`

## Import legacy

```bash
php artisan purchase-requests:import-legacy --dry-run
```

Requiere `LEGACY_GESTION_COMPRAS_DB_*` en `.env`. Comando: `ImportLegacyPurchaseRequestsCommand`. Escribe filas 1:N via `PurchaseRequestAttachmentService` (no la columna deprecada). No importa binarios del servidor legado; un path sin fichero puede dejar metadata con download 404.

## Reglas de negocio

- Folio correlativo con lock en transaccion al crear
- Director debe estar en `approversQuery()` (rol director, activo, `purchase.tab.approval`)
- Rechazo director: comentarios obligatorios (`UpdatePurchaseApprovalRequest`)
- Solo solicitudes `aprobado` entran en bandeja Compras
- `show` muestra formulario de autorizacion si `@can('approve', $purchaseRequest)`
- Adjuntos de solicitud **opcionales** (0–5; 10 MB c/u; tipos en config). No alteran estados ni el flujo de autorizacion
- Al **reenviar rechazada**: `keep_attachment_ids[]` + `attachments[]` nuevos; tope combinado 5; quitar todos es valido
- Audit `create` / `resubmit`: metadata `attachments_count` (entero). Sin paths ni nombres de archivo
- Download sin `view`: 403. Adjunto de otra solicitud: 404. Sin auth: redirect login

## Riesgos y pendientes (adjuntos)

- **Tope PHP:** 5 × 10 MB mas fotos de linea puede superar `post_max_size` / `upload_max_filesize` (el POST se descarta antes de Laravel). No se bajo el tope de producto; si falla el envio, revisar php.ini.
- **Huerfanos en disco:** el hook `deleting` borra el fichero si Eloquent elimina el adjunto. Un cascade SQL al borrar la solicitud padre no dispara el evento (aceptable en v1).
- **Tope keep + nuevos en UI:** el servidor rechaza si se supera 5; no hay aviso en cliente (observacion de revision, no bloqueante).

## Pruebas

- `tests/Feature/PurchaseRequestModuleTest.php`
- `tests/Feature/PurchaseRequests/FoAd44ExportAlignmentTest.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php`
- `tests/Feature/PurchaseRequests/PurchaseProviderAccessTest.php` (view vs edit, 403, guest)
- `tests/Feature/PurchaseRequests/PurchaseProviderCatalogTest.php` (DT default, filtros, uppercase, unique, update conserva filtros)
- `tests/Feature/ComprasDashboardTest.php`
- `tests/Feature/RoleDirectorMigrationTest.php`
- `tests/Feature/NavigationVisibilityTest.php`

## Control de cambios

| Fecha | Descripcion |
| --- | --- |
| 2026-10-19 | FEAT-047 T3: rediseño del listado de proveedores al chrome canónico — sin título hero, panel de filtros plegable (`req-manage-filters__panel` + badge), barra de acciones siempre visible (exportar/plantilla/importar/nuevo) y encabezados en mayúsculas |
| 2026-10-19 | FEAT-047 T3: `purchase_providers` solo almacena proveedores — se eliminan `es_cliente`/`es_proveedor` y sus indices (migracion recreada antes de produccion); plantilla/import VINE01 sin columna `CLIENTE` |
| 2026-10-19 | FEAT-047 T3: carga VINE01 — el resultado sale en `<x-import-result-modal>` (icon-only) con descarga de reporte `.xlsx` por token; `PurchaseProviderImportService` devuelve `failures` estructuradas |
| 2026-10-19 | FEAT-047 T2: Proveedores pasa a **tablero** del área Compras (board `proveedores`, fuera del subnav de Solicitudes); UI icon-only, validación teléfono numérico, correo email y ciudad contra `requisition_cities`; rediseño shell `pur-req-form` |
| 2026-10-19 | FEAT-047 T2: catalogo Proveedores (CRUD, permisos view/edit, pestaña, DT server-side, export Excel, uppercase, filtros conservados al editar) |
| 2026-10-19 | FEAT-047 T1: FO-AD-44 PDF/Excel (compra + suministro) alineado Marzo 2026 Versión 01; foto embebida o S/A |
| 2026-10-02 | UI productos: foto antes de descripción; descripción alta; referencia ancha; utilización/ubicación/cantidad en fila; cliente radios en 3 cols; ortografía |
| 2026-10-02 | UI procesar suministro: layout pur-req e iconos chrome (alineado a procesar compra) |
| 2026-10-02 | UI procesar/reabrir: layout pur-req (secciones + aside) e iconos chrome; mismo patrón que Nueva solicitud |
| 2026-10-02 | Bandeja: acciones Ver detalle / Procesar y Limpiar filtros como icon-only |
| 2026-07-31 | Modulo inicial: CRUD, bandeja, correos, FO-AD-44, import legacy |
| 2026-07-31 | Autorizacion in-app (correo solo notifica); approval-form en show |
| 2026-08-03 | Hogares canonicos sidebar (`SidebarVisibilityService`); permisos director en seeder/migracion |
| 2026-10-02 | Campo cliente `reinversion` (Si/No) junto a `asume_cliente` en create/edit/show/PDF; migracion aditiva |
| 2026-08-03 | Urgente opcional al crear; Mis solicitudes por usuario; registro de correos en detalle |
| 2026-08-03 | PDF solicitud alineado al detalle (metadatos, estilo FO-AD-44); sin registro de correos |
| 2026-08-03 | Dashboard Compras (`compras.dashboard`): KPIs bandeja, tendencias y graficos ApexCharts |
| 2026-08-03 | Bandeja compras: accion Ver detalle; analista compras ve detalle completo y vuelve a bandeja |
| 2026-08-03 | Navegacion unificada: bandeja solo como pestana en Solicitudes de compra; landing analista en bandeja |
| 2026-08-03 | Bandeja compras: filtros estilo Gestion requisiciones (fechas, pills estado, area/tipo) |
| 2026-08-03 | Bandeja historica completa: completados visibles; limite 200 sin filtro fecha; sin limite con rango |
| 2026-08-03 | Dashboard Compras: KPIs bandeja alineados con `ComprasQueueService` y filtros ano/mes (fecha aprobacion/actualizacion) |
| 2026-08-03 | Dashboard Compras: retirados KPIs Solicitudes en periodo y Urgentes en bandeja |
| 2026-08-03 | Detalle suministro alineado a solicitud compra; export PDF/Excel FO-AD-44 en `supplies.show` |
| 2026-08-03 | PDF suministro FO-AD-44: mismo layout visual que PDF solicitud de compra |
| 2026-08-20 | Retirados campos de cabecera `descripcion` y `justificacion` (formulario, detalle, PDF, correo); detalle solo en lineas |
| 2026-08-04 | Dashboard Compras restringido: director (`purchase.tab.approval`) ya no accede; requiere permiso dashboard, processing o area Compras |
| 2026-08-28 | Bandeja: listado por fecha mas reciente (no por Tipo); filtros searchable-select autoenvian GET |
| 2026-08-28 | FEAT-030: adjuntos opcionales 1:N a nivel solicitud (`purchase_request_attachments`, disco `local`, ruta `attachments.download`, policy `view`). `archivo_pedido_path` deprecada (columna permanece). Fuera: mail, FO-AD-44, guest |

## Referencias

- Guia usuario: [`docs/user/purchase-requests.md`](../user/purchase-requests.md)
- Feature Brief: [`docs/briefs/FEAT-030.md`](../briefs/FEAT-030.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Suministros: [`docs/modules/suministros.md`](suministros.md)
