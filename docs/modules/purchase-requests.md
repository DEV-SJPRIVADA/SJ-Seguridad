# Modulo: Solicitudes de compra

## Objetivo

Gestionar solicitudes de compra libres (multi-linea, fotos por item), con autorizacion por director seleccionado, y el trabajo diario de Compras en tres tableros ya existentes, solo reetiquetados: **Pedidos**, **Órdenes** y **Catálogos**. El estado del pedido lo escribe `PurchaseFlowStateService`. La orden impresa es el FO-AD-07 oficial (una hoja carta).

## Alcance actual

- Creacion multi-linea con foto opcional por articulo (disco `public`; sin cambio en FEAT-030)
- Adjuntos **opcionales** multiples a **nivel solicitud** (no por linea): cotizaciones, ordenes, evidencias. Solo visibles en el detalle autenticado
- Solicitud **Interno** o **Cliente**. Solo Cliente muestra y guarda Proyecto nuevo, Asume el cliente, Reinversión, **Comodato** y **Activo** (radios Sí/No, default No). Interna deja esos campos en `null`. Al imprimir la OC, `PurchaseOrder::banderaImpresa()` lee la solicitud: Sí solo si el campo es verdadero; si no, imprime No.
- Director aprobador seleccionado en el formulario (usuarios con rol `director` + `purchase.tab.approval`)
- Autorizacion **in-app**: pestaña Pendientes + formulario Aprobar/Rechazar en detalle (`show`)
- Autorizacion **por correo**: enlace firmado (sin login) a `aprobacion-correo/{id}` con formulario guest Aprobar/Rechazar; vigencia configurable (`PURCHASE_EMAIL_APPROVAL_LINK_DAYS`, default 7)
- Correo al director incluye boton **Autorizar por correo** (enlace firmado) y alternativa **Ver en la plataforma**
- Tablero **Pedidos** (clave `bandeja_compras`, etiqueta Pedidos): tres vistas del mismo listado (`vista=por_comprar|con_orden|historial`). Por comprar + Con orden son la bandeja; Historial es Cerrado. `estado_compras=rechazado` no entra en ninguna
- Export FO-AD-44: PDF y Excel por solicitud de compra; PDF/Excel por suministro desde el detalle
- Emision de **Orden de Compra FO-AD-07** (compra y suministro): el precio se captura solo ahí (`valor_unidad` e IVA importe). PDF y Excel oficiales, una hoja carta, texto legal de `config/purchase-orders.php` (`condiciones`) copiado tal cual
- **Cerrar saldo** (sección del formulario de procesar, no un modal): motivo obligatorio en el hilo y paso a Cerrado aunque quede saldo o falte fecha de recibido
- Evaluación post-entrega en pantalla (`purchase.providers.edit`). **No se imprime** en el FO-AD-07. La fecha de recibido puede guardarse sola; el cierre automático exige saldo cero y fecha en todas las OC
- Import legacy desde BD gestion-compras (`purchase-requests:import-legacy`)

## Flujo de estados

La etiqueta de pantalla no renombra la columna. La escribe un solo servicio, `PurchaseFlowStateService::sync`, **después** de aprobar, rechazar, reenviar, emitir una OC o guardar `fecha_recibido`. No corre al abrir Procesar (GET). Un pedido ya `completado` / `completada` no se degrada.

**Etapa común (`App\Enums\PedidoEtapa`, 2026-10-21):** compras y suministros muestran y filtran la misma etapa. No se guarda en BD: `PurchaseRequest::etapa()` / `SupplyRequest::etapa()` traducen las columnas y los scopes `whereEtapa()` hacen la consulta inversa. Pastilla `<x-pedido-etapa>` con clases `status-pill--etapa-*`. `SupplyRequest::statusLabel()`, `estadosComprasLabels()` y `etiquetaFlujo()` devuelven el nombre de la etapa; las claves guardadas y la auditoría no cambian.

| Etapa (`value`) | Compra (columnas actuales) | Suministro |
| --- | --- | --- |
| Por autorizar (`por_autorizar`) | `estado=pendiente` | `pendiente_calidad` |
| Devuelta (`devuelta`) | `estado=rechazado` | `rechazada_calidad` |
| Por comprar (`por_comprar`) | `estado=aprobado`, `estado_compras=pendiente`, sin cantidades en OC | `aprobada_calidad`, sin cantidades en OC |
| Con orden (`con_orden`) | `estado_compras=en_curso` | `en_compras` |
| Cerrado (`cerrado`) | `estado_compras=completado` | `completada` |
| Cerrado sin compra (`cerrado_sin_compra`) | `estado_compras=rechazado` (dato antiguo) | — |

Usan la etapa: Mis solicitudes (compra y suministro), detalle, Pendientes del director, Pedidos (`ComprasQueueFilterBag::etapas()`), Procesar, Por aprobar de Calidad, PDF FO-AD-44 y dashboard. Quién autoriza (Director o Calidad) es un dato aparte. `aprobado` con `estado_compras` vacío se muestra Por comprar, pero el scope de Por comprar solo cuenta `estado_compras=pendiente` (mismos conteos que antes).

`sync` de una compra solo actúa si `estado=aprobado` y `estado_compras` no es `rechazado` (si es `rechazado`, retorna sin escribir). Predicado:

1. Si ya está Cerrado, no se toca.
2. Sin cantidades en ninguna OC → Por comprar.
3. Hay OC y queda saldo → Con orden.
4. Saldo de todas las líneas en cero **y** cada OC del origen tiene `fecha_recibido` → Cerrado. Si el saldo ya es cero y falta una fecha, queda Con orden. El puntaje de la evaluación no cuenta.

**Devolver al solicitante (2026-10-21):** `PurchaseFlowStateService::returnToRequester` lo usa Compras (`purchase.tab.processing`, sin permiso nuevo) cuando el pedido está mal diligenciado. Solo aplica en Por comprar y sin ninguna OC del origen (`puedeDevolverAlSolicitante()`). Motivo obligatorio (`ReturnToRequesterRequest`, campo `motivo_devolucion`, 3–5000) que va al hilo como `DEVUELTA POR COMPRAS: …` en mayúsculas. Compra: `estado=rechazado`, `estado_compras`, `fecha_aprobacion` y `procesado_compras_*` en null (etapa Devuelta); el solicitante la reabre y al reenviarla vuelve a `pendiente` con el director (correo de asignación). Suministro: `status=rechazada_calidad`; al reenviarlo vuelve a `pendiente_calidad` con Calidad. Rutas `POST purchase-requests.processing.purchase.return` / `.supply.return`; audit `compras_processing` / `supply_compras` acción `return_to_requester`; correo `PurchaseRequestReturnedMail` (log `requester_returned`) o `SupplyRequestReturnedMail`.

`closeBalance` es la excepción: exige motivo (mínimo 3 caracteres; se guarda en el hilo en mayúsculas) y pone Cerrado aunque quede saldo o falte una fecha. Después `PurchaseOrderService` rechaza otra OC (`puedeEmitirOrden` es falso en Cerrado).

Compras ya no elige ni rechaza el estado. El PATCH de procesar compra no persiste `estado_compras`. `comentarios_director` y `comentarios_compras` se siguen mostrando si el pedido viejo los tiene y dejan de escribirse: el motivo nuevo del director (pantalla y correo firmado) es una fila del hilo.

Al aprobar el director: `estado_compras` pasa a `pendiente` y se notifica a Compras. Pendiente de autorización y Devuelta no entran a Pedidos; siguen en Mis solicitudes y en la cola de quien autoriza.

## Rutas

Prefijo autenticado: `/purchase-requests/{module}/`

| Pestana | Permiso | Rutas |
| --- | --- | --- |
| Nueva solicitud | `purchase.tab.create` | `GET/POST purchase-requests.create`, `store`; plantilla ítems `items.import-template`; precarga `items.import` |
| Mis solicitudes | `purchase.tab.my_requests` | `purchase-requests.index`, `show` |
| Autorizaciones compras (clave `pendientes_aprobacion`) | `purchase.tab.approval` | `purchase-requests.approval.index` con subpestañas `vista=solicitudes` (default, Pendientes de autorización) y `vista=anulaciones` (Anulaciones de órdenes, con contador); `approval.update` |
| Pedidos (clave `bandeja_compras`) | `purchase.tab.processing` | `purchase-requests.processing.index` con `vista=por_comprar\|con_orden\|historial`. Ya no es pestaña de Solicitudes de compra |
| Cerrar saldo | `purchase.tab.processing` | `POST purchase-requests.processing.purchase.close` y `.supply.close` |
| Solicitar / retirar anulación de OC | `purchase.tab.processing` | `POST|DELETE purchase-requests.orders.cancellation.request|withdraw` |
| Aprobar / rechazar anulación de OC | `purchase.tab.approval` + ser el director elegido | `GET|PATCH purchase-requests.approval.order-cancellation.show|update`; correo firmado `purchase-requests.order-cancellation-email.*` |
| Devolver al solicitante | `purchase.tab.processing` | `POST purchase-requests.processing.purchase.return` y `.supply.return` (solo Por comprar sin OC) |
| Emitir OC (FO-AD-07) | `purchase.tab.processing` (`canEmitOrder`) | `purchase-requests.orders.purchase.create` / `.store`, `orders.supply.create` / `.store`, `orders.show`, `orders.pdf`, `orders.excel` |
| Órdenes (clave `ordenes_compra`) | `purchase.tab.processing` (`canViewOrdersBoard`) | `purchase-requests.orders.index` (`GET /ordenes-compra`), `purchase-requests.orders.datatable`, `purchase-requests.orders.export` (Excel por producto con filtros) |
| Evaluación y fecha de recibido | `purchase.providers.edit` (fuera del grupo `processing`) | `GET/PUT purchase-requests.orders.evaluation.edit` / `.update` (`/ordenes-compra/{purchase_order}/evaluacion`). OC debe estar `emitida`. Quien solo tiene `purchase.tab.processing` recibe 403 |
| Catálogos → Proveedores (clave `proveedores`) | `purchase.providers.view` (edit implica view) | `purchase-requests.providers.index`, `providers.datatable`, `providers.export`, `providers.template` |
| Catálogos → IVA | `purchase.providers.view` (ver) / `purchase.providers.edit` (crear, editar, activar) | `purchase-requests.tax-rates.index`, `tax-rates.store`, `tax-rates.update` |
| Proveedores (mutaciones) | `purchase.providers.edit` | `providers.create`, `providers.store`, `providers.edit`, `providers.update`, `providers.import`, `providers.import-report` |
| Detalle / export / adjuntos / comentarios (transversal) | Policy `view` / `comment` | `purchase-requests.show`, `export.pdf`, `export.excel`, `attachments.download`, `comments.store`, `source-orders.pdf` (PDF de las OC de esa solicitud) |
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
| `purchase.tab.processing` | Pedidos, emitir OC, Órdenes, Cerrar saldo (policy `process` en la compra) |
| `purchase.providers.view` | Pestaña Proveedores dentro de Catálogos |
| `purchase.providers.edit` | CRUD de proveedores y la pantalla de evaluación / fecha de recibido; **implica view** |
| `view.board.{area}.solicitudes_compra` | Tablero Solicitudes de compra (Nueva, Mis solicitudes, Pendientes). Sin pestaña Bandeja |
| `view.board.compras.bandeja_compras` | La clave sigue. El sidebar dice **Pedidos** por la etiqueta de `boards`, no por una clave nueva |

FEAT-048 no agregó claves en `boards` ni en `system_permissions`. `purchase_tabs` aún tiene la etiqueta muerta «Bandeja compras»; `visibleTabsFor` ya no la devuelve.

`PurchaseRequestPolicy`:

- `view`: super-admin, compras processing, solicitante, director asignado — tambien autoriza **ver y descargar** adjuntos (sin permiso nuevo)
- `approve`: director asignado + pendiente
- `process`: compras + solicitud aprobada
- `resubmit`: dueno + `estado=rechazado` + `purchase.tab.my_requests` o `purchase.tab.create` — conservar / agregar / quitar adjuntos al reenviar

Directores: rol `director` incluye `view.board.compras.solicitudes_compra` (compras). No incluye autorizacion de requisiciones cargo nuevo (rol `administrador`).

## Navegacion (sidebar)

| Perfil | Entrada en menu |
| --- | --- |
| Solicitante | Su `area_key` → Solicitudes de compra (Nueva / Mis solicitudes) |
| Director | Compras → Solicitudes de compra → Pendientes |
| Compras (`purchase.tab.processing`) | Compras → **Pedidos** y **Órdenes** |
| Catálogos | Compras → **Catálogos** si `purchase.providers.view` o `supply.tab.catalog`. Pestaña Proveedores o Insumos según el permiso |

`purchase-requests.processing.*` marca Pedidos, no Solicitudes de compra. `supplies.products.*` marca Catálogos, no Suministros. El tablero Dashboard se conserva (`view.board.compras.dashboard`).

Config: etiquetas en `boards` (`bandeja_compras` = Pedidos, `ordenes_compra` = Órdenes, `proveedores` = Catálogos). Visibilidad: `SidebarVisibilityService`, `NavigationResolver`, `PurchaseAccessService::visibleTabsFor` (ya no devuelve bandeja).

## Base de datos

### `purchase_requests`

Folio de pantalla: `folioVisible()` = `SC-` + `folio()` (el correlativo se rellena a 4; por encima de 9999 no se recorta, p. ej. `SC-10000`). El número guardado en `numero_solicitud` no cambia. Campos clave: `area_key`, `solicitud_para`, `urgente`, `aprobador_id`, `estado`, `estado_compras`, `fecha_aprobacion`, `comentarios_director`, `comentarios_compras`, `procesado_compras_at`, `procesado_compras_por`. Si `solicitud_para=Cliente`: `razon_social`, `proyecto_nuevo`, `asume_cliente`, `reinversion`, `comodato`, `activo` (boolean nullable, sin default; el radio default No persiste `false`). Interna persiste `comodato` y `activo` en `null` aunque se posteen. En el detalle de una Cliente ya guardada, `null` se muestra como No y, si se reenvía, se persiste `false`; eso no rellena el snapshot de una OC ya emitida. Sin descripcion/justificacion de cabecera.

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
| `contacto`, `direccion`, `ciudad`, `forma_pago` | strings nullable | Datos FO-AD-07; `contacto`/`direccion`/`forma_pago` en MAYUSCULAS; `ciudad` conserva el nombre del catalogo. Import VINE01 cubre `CONTACTO`, `CIUDAD` y `FORMA_PAGO` (opcional; ciudad contra `requisition_cities` activas) |
| `created_by` | FK users nullable | `nullOnDelete()` |

Indices: `estado`. Modelo `App\Models\PurchaseProvider` (`dias_pago` int, `estadoLabels()`). La tabla **solo contiene proveedores**; no existen columnas de clasificacion (`es_cliente` / `es_proveedor`).

### `purchase_orders` (FEAT-047 T4)

Ordenes de compra FO-AD-07. Migracion `2026_10_19_145000_create_purchase_orders_table.php` (multi-driver: `estado` string, sin `enum()`).

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | PK | **Es el numero de OC visible** (`numeroOc()`), sin prefijo ni pad |
| `source_type` | string(255) | Morph: `purchase_request` / `supply_request` (`Relation::morphMap`) |
| `source_id` | unsignedBigInteger | PK del origen (solicitud de compra o de suministro) |
| `provider_id` | FK purchase_providers nullable | `nullOnDelete()`; el snapshot de la cabecera sobrevive al borrado del proveedor |
| `provider_identificacion`, `provider_nombre` | string | **Snapshot** congelado al emitir |
| `provider_telefono`, `provider_correo`, `provider_contacto`, `provider_direccion`, `provider_ciudad`, `provider_forma_pago` | string nullable | Snapshot del proveedor al emitir |
| `provider_dias_pago` | unsignedSmallInteger nullable | Snapshot |
| `fecha` | string(10) | Fecha de emision (YYYY-MM-DD) |
| `ciudad` | string(120) nullable | Ciudad de entrega de la OC |
| `asume_cliente` | string(20) nullable | Snapshot `SI` / `NO` o `null`. La impresión usa la solicitud: Sí o No |
| `proyecto_nuevo`, `reinversion`, `comodato`, `activo` | string(20) nullable | Igual que `asume_cliente`. Migración aditiva `2026_10_20_144714_add_client_flag_snapshots_to_purchase_orders_table`. Sin backfill |
| `observaciones` | text nullable | |
| `estado` | string(20) default `borrador` | `borrador` / `emitida`, indexado |
| `total` | decimal(15,2) default 0 | Suma de `valor_total` de las lineas |
| `created_by` | FK users nullable | `nullOnDelete()` |

Indices: `estado`, `(source_type, source_id)`.

### `purchase_order_items`

Lineas de la OC. Migracion `2026_10_19_145100_create_purchase_order_items_table.php`.

| Columna | Tipo | Notas |
| --- | --- | --- |
| `purchase_order_id` | FK | `cascadeOnDelete()` |
| `orden` | unsignedSmallInteger default 1 | Numeracion visible |
| `cantidad` | decimal(10,2) | |
| `articulo` | string nullable | Referencia del origen |
| `descripcion` | text nullable | Descripcion del origen |
| `valor_unidad` | decimal(15,2) | Precio capturado por Compras |
| `iva` | decimal(15,2) default 0 | **Importe** del IVA de la linea (no porcentaje) |
| `valor_total` | decimal(15,2) | `cantidad * valor_unidad + iva` |
| `source_item_type`, `source_item_id` | nullable | Vinculo con la linea de origen (`purchase_request_item` / `supply_request_item`); base del saldo pendiente por linea |

### `purchase_order_evaluations`

Evaluacion post-entrega **1:1** por OC. Migracion `2026_10_19_145200_create_purchase_order_evaluations_table.php` (unique en `purchase_order_id`).

| Columna | Tipo | Notas |
| --- | --- | --- |
| `purchase_order_id` | FK unique | `cascadeOnDelete()` |
| `fecha_recibido` | date nullable | |
| `calif_referencias_cantidades` | unsignedTinyInteger nullable | 1–5 (peso 25%) |
| `calif_oportunidad_entrega` | unsignedTinyInteger nullable | 1–5 (peso 25%) |
| `calif_calidad_producto` | unsignedTinyInteger nullable | 1–5 (peso 50%) |
| `resultado_referencias`, `resultado_oportunidad`, `resultado_calidad` | decimal(5,2) nullable | Cache del calculo |
| `puntaje_porcentaje` | decimal(5,2) nullable | 0–100 |
| `umbral_codigo` | string(20) nullable | `satisfactoria` / `seguimiento` / `insatisfactoria` |
| `observaciones` | text nullable | |
| `evaluated_by` | FK users nullable | `nullOnDelete()` |

Pesos y umbrales en `config/purchase-orders.php` (`evaluation.weights` 25/25/50, `evaluation.thresholds` ≥ 80 `satisfactoria`, ≥ 60 `seguimiento`, si no `insatisfactoria`). No hardcodear los números en Blade. Resultado del criterio = Calif × (peso/100). Puntaje % = (suma de resultados / 5) × 100. Comprobación: 5/5/5 → 100; 4/4/4 → 80; 3/3/3 → 60; 1/1/1 → 20.

FEAT-048 pone la UI (`PurchaseOrderEvaluationService`, `updateOrCreate`, una fila por OC). La fecha puede guardarse sola (las tres calificaciones quedan null). Si se envía una calificación, las tres son obligatorias (enteros 1–5). Observaciones de la evaluación pasan por mayúsculas. El cierre automático mira fecha + saldo en cero, no el puntaje. La evaluación **no** se imprime en el PDF ni en el Excel.

### `purchase_requests.archivo_pedido_path` (deprecada)
Columna **permanece** nullable. **No DROP.** Ya no esta en `$fillable`. Store, resubmit, import y UI **no** la leen ni escriben. Fuente de verdad: `purchase_request_attachments`. Valores previos se copiaron a 1:N (disco `public` → `local` si el fichero existia) y la columna quedo en `NULL`.

Migraciones: `2026_07_31_140100_create_purchase_requests_tables.php`, `2026_08_20_082215_drop_descripcion_justificacion_from_purchase_requests_table.php`, `2026_08_28_160752_create_purchase_request_attachments_table.php`.

## Servicios y controladores

| Componente | Responsabilidad |
| --- | --- |
| `PurchaseRequestController` | CRUD solicitante, show, export PDF/Excel, `downloadAttachment`. `index` aplica `PurchaseMyRequestsFilterBag` |
| `PurchaseMyRequestsFilterBag` | Filtros GET de Mis solicitudes (fechas, estado, estado compras, urgencia, área) |
| `PurchaseApprovalController` | Lista pendientes del director, PATCH autorizar |
| `PurchaseProcessingController` | Pedidos (tres vistas), procesar compra/suministro, Cerrar saldo. El GET no llama `sync`. El PATCH de compra no escribe estado |
| `PurchaseFlowStateService` | Máquina única: `sync` y `closeBalance` |
| `PurchaseOrderEvaluationController` / `PurchaseOrderEvaluationService` | Fecha de recibido y calificaciones; tras guardar llama `sync` |
| `PurchaseApprovalService` | Resolver aprobacion/rechazo director. El motivo nuevo va al hilo (`UppercaseFormText`); `comentarios_director` solo queda en metadata de auditoría, acotado |
| `PurchaseRequestNotificationService` | Correos director, solicitante, compras; persiste log en `purchase_request_mail_logs` |
| `ComprasQueueService` | Mezcla purchase + supply en bandeja con filtros |
| `ComprasQueueFilterBag` | Parametros de filtro bandeja (fechas, area, tipo, estado compras) |
| `ComprasDashboardService` | KPIs y graficos del dashboard Compras; bandeja usa la misma logica que `ComprasQueueService` |
| `ComprasDashboardController` | Vista `compras/dashboard` (ruta `compras.dashboard`) |
| `PurchaseAccessService` | Tabs de Solicitudes de compra (sin bandeja), `canViewProviders` / `canEditProviders`, tablero Catálogos (proveedores), Pedidos y Órdenes, directores (`approversQuery`) |
| `PurchaseProviderController` | CRUD proveedores (tablero Compras), datatable server-side, export Excel, plantilla/import VINE01, audit `provider_created` / `provider_updated` / `provider_export` / `providers_imported` |
| `PurchaseProviderDatatableService` | Filtros (`q`, `estado`), query, respuesta DataTables y retorno de filtros |
| `PurchaseProviderImportService` | Import masivo VINE01 (solo `PROVEEDOR=SI`, upsert por `identificacion`, `CONTACTO`/`CIUDAD`/`FORMA_PAGO`/`DIAS_PAGO` opcionales, soporte `dry-run`/`limit`) |
| `PurchaseProviderImportTemplateExport` | Plantilla Excel `VINE01` (hoja `VINE01` con encabezados + hoja `Instrucciones`) |
| `PurchaseOrderController` | Emision de OC desde compra/suministro (create + store), detalle de la OC, export PDF/Excel FO-AD-07, audit `purchase_order`/`created` y `export`/`order_pdf`/`order_excel` |
| `PurchaseOrderService` | Construye la OC: snapshot del proveedor y de los cinco flags (`SI`/`NO` o `null`; suministro: los cinco `null`), lineas desde el origen, `valor_total = cantidad * valor_unidad + iva`, estado `emitida`. `supplyLines()` precarga `valor_unidad` desde `unit_cost` histórico y no lo borra. Rechaza emitir si el origen ya está Cerrado. Saldo por línea en `sourceLines()` |
| `PurchaseOrderDatatableService` | Listado server-side del tablero de OC: filtros (estado, origen, proveedor, fechas), busqueda DataTables, orden y filas con acciones |
| `PurchaseOrderPdfExporter` | FO-AD-07 en PDF (`pdf/purchase-order.blade.php`, DomPDF, carta, una página). Título **ORDEN DE COMPRA O SERVICIO**, periodo **Enero de 2025**, versión 04, logo `public/images/logoSj.png`, condiciones desde config. No es un listado `BaseExport` |
| `PurchaseOrderExcelExporter` | Mismo contenido del formulario, incluidas las condiciones. No pasa a `BaseExport` ni a `excelHtml5` |
| `ClosePurchaseBalanceRequest` | Motivo obligatorio (mín. 3), mayúsculas (`UppercasesValidatedText`) |
| `UpdatePurchaseOrderEvaluationRequest` | Fecha obligatoria; calificaciones 1–5 las tres o ninguna |
| `StorePurchaseOrderRequest` | Autorizacion `canEmitOrder`, proveedor **activo** (`Rule::exists`), fecha, ciudad, observaciones y `items.*` (cantidad, articulo, descripcion, valor_unidad, iva) |
| `Store/UpdatePurchaseProviderRequest` | Autorizacion `providers.edit`, validacion unique/`dias_pago`, telefono numerico, ciudad contra `requisition_cities`, uppercase de textos (excepto correo/identificacion/telefono/estado/ciudad) |
| `ImportPurchaseProvidersRequest` | Autorizacion `providers.edit`, validacion de `import_file` (xlsx/xls/csv, tope `providers_import_max_kilobytes`) |
| `PurchaseEmailApprovalController` | Autorizacion guest por enlace firmado (show/update/pdf). **No** lista adjuntos de pedido |
| `PurchaseRequestAttachmentService` | Guardar en disco `local`, sincronizar en resubmit (keep/quitar/agregar), mapear legado |
| `PurchaseRequestResubmitService` | Reenvio de rechazada (items + adjuntos via attachment service). No toca `archivo_pedido_path` |

Vistas: `resources/views/modules/purchase-requests/` (create, index, show, edit, approval/, processing/, partials/approval-form.blade.php). Dashboard: `resources/views/areas/compras/dashboard.blade.php`.

UI create/show/edit/procesar (compra y suministro): layout `pur-req-form-layout` / `pur-req-detail-layout` (main + aside), secciones FO-AD-44, estilos `pur-req-*`. La fila Comodato/Activo solo se muestra si `solicitud_para=Cliente`. Procesar ya no ofrece selector de `estado_compras` ni captura `unit_cost`. **Cerrar saldo** es una sección `pur-req-form` (`processing/partials/close-balance.blade.php`), no un modal. **Productos (create/edit):** tarjetas `.purchase-item-card`. Evaluación: `orders/evaluation.blade.php` sobre `pur-req-form`, calificaciones con `<x-searchable-select>`, pesos desde config.
Listado Pedidos: `processing/index` con pastillas Por comprar / Con orden / Historial (`.module-tab`), filtros y tope 200. Órdenes sigue en DataTables server-side. Catálogos usa `partials/catalogos-subnav.blade.php`.

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
## Pedidos — tres vistas y filtros

El listado sigue siendo `purchase-requests.processing.index` (`ComprasQueueFilterBag` + `ComprasQueueService`). Las pastillas son la vista, no un segundo escritorio:

| Vista (`vista`) | Compra | Suministro |
| --- | --- | --- |
| `por_comprar` | `estado_compras=pendiente` | `aprobada_calidad` |
| `con_orden` | `en_curso` | `en_compras` |
| `historial` | `completado` | `completada` |
| (sin vista) | pendiente + en curso | `aprobada_calidad` + `en_compras` |

`estado_compras=rechazado` no lista filas (`whereRaw('1 = 0')` si alguien pide ese filtro). Los cerrados no entran en la bandeja vacía.

| Filtro | Campo / efecto |
| --- | --- |
| Fecha inicio / fin | Compras y suministros: `created_at` (fecha de solicitud) |
| Area solicitante | `area_key` |
| Tipo | `purchase` \| `supply` |
| Vista | `por_comprar`, `con_orden`, `historial` (el dashboard manda `vista` y quita `estado_compras`) |

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
| En bandeja | Por comprar + Con orden (`bandeja_total`). Los cerrados no entran | Si: convierte ano/mes a `date_from`/`date_to` |
| Bandeja pendiente | Por comprar (`pendiente` / `aprobada_calidad`) | Igual que bandeja |
| En curso | Con orden (`en_curso` / `en_compras`) | Igual que bandeja |
| Completadas | Compras: `procesado_compras_at`; suministros: `updated_at` con `status=completada` | Ano/mes del dashboard |

Los KPIs de bandeja enlazan a Pedidos (`purchase-requests.processing.index`) con `vista` (`por_comprar` o `con_orden`), no a un segundo listado. El KPI «En bandeja» abre Por comprar + Con orden. Graficos ApexCharts: tendencia anual, estado solicitudes (periodo), bandeja por estado (periodo), top areas, bandeja por tipo. JS: `resources/js/compras-dashboard-charts.js`.

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

Filtros GET (`PurchaseMyRequestsFilterBag`): `date_from` / `date_to` sobre `fecha_solicitud`, `etapa` (`PedidoEtapa`, una sola lista: Por autorizar … Cerrado), `urgente` (`1`/`0`), `area_key` y `numero_cotizacion` (coincidencia parcial `LIKE`). El listado muestra una sola columna **Estado** con la etapa y la columna **N.º cotización**. El panel usa el chrome de Pedidos (`req-manage-filters`) y se aplica al cambiar un campo. Sin filtros se listan todas las del usuario.

## Integracion Suministros

`ComprasQueueService` mezcla compras aprobadas y suministros según la vista de Pedidos. Sin filtro de fechas muestra hasta 200 registros mas recientes; con rango de fechas trae todos del periodo. Historial (`completado` / `completada`) solo entra con esa vista.

- **Ver detalle** suministro: `supplies.show`. El folio de pantalla es `SU-`.
- **Procesar** suministro: `purchase-requests.processing.supply`. No pide ni guarda `unit_cost`. Abrir la pantalla no pasa `aprobada_calidad` a `en_compras`. El precio se captura al emitir la OC; si el ítem ya tenía `unit_cost`, el formulario de la OC precarga `valor_unidad` y el costo histórico sigue en la línea.
- El cierre del suministro lo escribe la máquina (saldo cero + fecha de recibido en todas las OC) o **Cerrar saldo**. `completada` es terminal.
- Export FO-AD-44 suministro: `supplies.export.pdf` / `supplies.export.excel` (detalle) o `purchase-requests.processing.supply.pdf`.

## Proveedores (FEAT-047 T2)

Pestaña **Proveedores** del tablero **Catálogos** (clave de board que ya existía: `proveedores`; etiqueta nueva en `boards`). No es pestaña de Solicitudes de compra. El tablero se muestra si hay `purchase.providers.view` **o** `supply.tab.catalog` (`SidebarVisibilityService::shouldShowProvidersBoard`). La pestaña Proveedores solo con view (edit implica view). La pestaña Insumos vive en el mismo subnav (`catalogos-subnav`) y se documenta en [`suministros.md`](suministros.md).

- **Rutas:** `GET /purchase-requests/{module}/proveedores` (index), `/proveedores/datatable`, `/proveedores/exportar`, `/proveedores/plantilla` (descarga VINE01); mutaciones `/proveedores/crear` (GET), `/proveedores` (POST), `/proveedores/importar` (POST), `/proveedores/importar/reporte/{token}` (GET, reporte de fallos de la carga), `/proveedores/{purchase_provider}/editar` (GET), `/proveedores/{purchase_provider}` (PATCH). El grupo de mutaciones usa middleware `purchase.tab:providers.edit`; el listado/plantilla, `purchase.tab:providers`. El prefijo `purchase-requests` se conserva; el tablero se activa por el módulo `compras` de la ruta.
- **Listado server-side** (`PurchaseProviderDatatableService`): la tabla solo contiene proveedores. Default `estado = activo`; el filtro `estado` (`activo` / `inactivo` / `todos`) es el único filtro y se aplica con **chips** (enlaces GET; Activos = URL sin parámetro). El **buscador** es el de DataTables (`search[value]`), que consulta identificacion, nombre, correo, contacto y ciudad. No hay clasificacion cliente/proveedor en pantalla (las filas que no son proveedor se omiten en la carga masiva). Pagina 10/25/50/100.
- **Chrome del listado (rediseño 2026-10-20):** `panel` + `panel__body panel__body--compact req-manage-shell`, sin titulo hero (el tablero ya se nombra en la franja "Tableros del área"). Una **franja única** `purchase-providers-page__toolbar`: a la izquierda chips de estado (`module-tab`: Activos / Inactivos / Todos) + conteo; a la derecha icon-only **Exportar**, **Importar VINE01** (solo con permiso de edición) y **Nuevo proveedor**. Ya no hay panel `<details>` de filtros. Tabla: identificación sin cortes (`__col-id`), nombre en negrita (`__col-name`), correo como enlace `mailto:` (render JS; el JSON del DT sigue siendo texto plano), días de pago / estado / acciones centrados (`__col-center`, `__col-actions`). La **plantilla VINE01** se descarga desde el modal de importación. Encabezados de tabla en mayúsculas. El listado **ocupa todo el alto del área principal** (`.app-main:has(.purchase-providers-page.req-manage-page)` + `req-manage-shell`, patrón de Cursos/Seguimientos), de modo que la tabla scrollea internamente y no queda espacio vacío al pie.
- **Modal de carga VINE01:** mismas acciones icon-only del chrome (`.req-manage-filters__icon-btn`): `--ghost` para descargar plantilla, elegir archivo y cerrar; `--primary` para importar. Todos con `title`/`aria-label`. La fila `.purchase-providers-page__import-toolbar` alinea plantilla + nombre del archivo + acciones (referencia: modal de import de Formación).
- **Alta/edicion:** identificacion (unica), nombre, telefono, correo, contacto, direccion, ciudad, forma de pago, `dias_pago` (0–3650) y estado. **Sin** clasificacion cliente/proveedor. Botones de la cabecera y del formulario son **icon-only** con `title`/`aria-label` (`.req-manage-filters__icon-btn`); la accion de fila usa `.cursos-catalogo-page__icon-btn`. Shell visual `purchase-requests-page--form` + `pur-req-form` (meta + secciones); vista compartida `providers/partials/form.blade.php`.
- **Validaciones:** `telefono` solo numerico (`digits_between:7,15`; input `type="tel"` `inputmode="numeric"` `pattern="[0-9]{7,15}"`); `correo` email; `ciudad` debe existir en el catálogo de Requisiciones (`requisition_cities` activas) y se elige con `<x-searchable-select>`.
- **Mayusculas:** `Store/UpdatePurchaseProviderRequest` usa `UppercasesValidatedText`; se guardan en MAYUSCULAS nombre, contacto, direccion y forma de pago. Se conservan sin cambios correo, identificacion, telefono, estado y **ciudad** (valor del catálogo, ej. `Cali`).
- **Export Excel:** `BaseExport` (identificacion, nombre, telefono, correo, contacto, direccion, ciudad, forma de pago, dias de pago, estado) con los filtros activos; el término del buscador se sincroniza a `q` en el enlace de export.
- **Filtros conservados:** el enlace de editar y el redirect tras guardar llevan `q` y `estado` del query string, sin mezclarlos con los campos del formulario.
- **Auditoria:** eventos `provider_created`, `provider_updated`, `provider_export` y `providers_imported` en modulo `purchase_requests` (area `compras`).

### Carga masiva VINE01 (FEAT-047 T3)

- **Plantilla:** `GET purchase-requests.providers.template` -> `PurchaseProviderImportTemplateExport` (`plantilla_proveedores_VINE01.xlsx`). Hoja `VINE01` (fila 1 = encabezados oficiales, datos desde fila 2) + hoja `Instrucciones`. Columnas (`config('purchase-requests.providers_import_columns')`): `IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `CONTACTO`, `CIUDAD`, `FORMA_PAGO`, `PROVEEDOR`, `ESTADO`, `DIAS_PAGO` (opcional).
- **Import:** `POST purchase-requests.providers.import` (multipart, `import_file` xlsx/xls/csv) -> `PurchaseProviderImportService`.
- **Reglas:** solo se cargan filas con `PROVEEDOR = SI` (normaliza `SI/SÍ/1/X/TRUE`); upsert por `identificacion`; `TELEFONO` se guarda solo con digitos; `NOMBRE`, `CONTACTO` y `FORMA_PAGO` en MAYUSCULAS; `ESTADO` `ACTIVO`/`INACTIVO` (default `activo`); `DIAS_PAGO` entero 0–3650 o vacio; `CIUDAD` vacia o nombre de una ciudad activa de Requisiciones (se guarda con la forma del catalogo, ej. `Cali`). Si el archivo no trae las columnas `CONTACTO`/`CIUDAD`/`FORMA_PAGO`, no se pisan valores ya cargados a mano. `DIRECCION` no viene en VINE01 y se edita en pantalla. Las columnas `CLIENTE` de archivos de origen se ignoran.
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

## Ordenes de compra — FO-AD-07 oficial (FEAT-048)

Papel carta, **una página**. Reimprimir una OC ya emitida usa este layout: las celdas sin snapshot salen vacías y el `id` no cambia. La evaluación no aparece.

Orden visual (`resources/views/pdf/purchase-order.blade.php` y el Excel):

1. Logo `public/images/logoSj.png` | título **ORDEN DE COMPRA O SERVICIO** | recuadro **FO-AD-07** / **Enero de 2025** / **Versión 04** / **Página 1 de 1**. Metadatos en `config/purchase-orders.php` (`report_title`, `form_period`, `form_version`).
2. Fecha `dd/mm/aaaa` (`purchase_orders.fecha`) y **OC No.** = `id` (`numeroOc()`).
3. Ciudad = `purchase_orders.ciudad` (ciudad de entrega), no la ciudad del proveedor.
4. Asociado de negocio, desde el snapshot: razón social, NIT, contacto, teléfono, correo, dirección, ciudad del proveedor, forma de pago. Si la forma de pago está vacía y hay `dias_pago`, se imprime `A {n} DÍAS`. Asume el cliente, Proyecto nuevo, Reinversión, Comodato y Activo salen en la celda de la derecha: Sí o No según la solicitud (`banderaImpresa`). Un suministro, o una OC sin solicitud, imprime No.
5. Debajo, **siempre**, Proyecto nuevo, Reinversión, Comodato y Activo. `imprimirSiNo` pinta Sí o No solo con snapshot `SI`/`NO`. Solicitud interna, suministro y OC anteriores al cambio: celdas vacías.
6. Tabla «Sírvase suministrar…»: Cantidad, Artículo (`articulo`, la referencia del origen), Descripción, Valor unidad, IVA (importe, no %), Valor total. Fila **TOTAL** dentro de la tabla.
7. **OBSERVACIONES** siempre (`observaciones`; vacío si no hay texto).
8. Firmas, con espacio para firma manuscrita: Elaborado por **Analista de Compras** (`created_by`); Revisado y aprobado por **Director(a) área solicitante** (aprobador de la compra, o `quality_reviewer_id` en suministro; si no hay persona, la línea queda en blanco con el cargo); Aprobado 2 por **Director Admon. y Financiero** y **Gerente (cuando aplique)** en blanco.
9. Debajo, **siempre**, el texto de `condiciones`. Si hay muchas líneas, se reduce la letra de la tabla antes de pasar a página 2. Prueba: 1 línea y 12 líneas, ambas en una carta. El texto legal no pasa por `UppercaseFormText` y no se «corrige» (espacios dobles y tildes tal cual).

Emision desde Pedidos, para origen **solicitud de compra** o **solicitud de suministro**.

- **Permiso:** `purchase.tab.processing` (`canEmitOrder`). La compra exige `process` y no estar Cerrada (`puedeEmitirOrden`: Por comprar o Con orden). El suministro exige `aprobada_calidad` o `en_compras`. Un origen Cerrado no admite otra OC.
- **Rutas:** `GET|POST purchase-requests.orders.purchase.create|store` (`/bandeja-compras/solicitud/{purchase_request}/orden-compra[/crear]`), `GET|POST purchase-requests.orders.supply.create|store` (`/bandeja-compras/suministro/{supply_request}/orden-compra[/crear]`), `GET purchase-requests.orders.show|pdf|excel` (`/ordenes-compra/{purchase_order}[/pdf|/excel]`). Todas dentro del grupo `purchase.tab:processing`.
- **Morph:** `source_type` guarda el alias (`purchase_request` / `supply_request`) declarado en `Relation::morphMap` (`AppServiceProvider`). `PurchaseOrder::source()` resuelve la origen; `sourceLabel()` y `sourceFolio()` exponen el texto visible.
- **Numero OC:** el `id` de `purchase_orders` (`numeroOc()`), sin prefijo ni relleno.
- **Snapshot de proveedor:** al emitir se congelan identificacion, nombre, telefono, correo, contacto, direccion, ciudad, forma de pago y dias de pago. El proveedor editable posterior **no** altera la OC emitida.
- **Precio:** solo en la OC. `valor_unidad` e `iva` (importe) los captura Compras al emitir. `valor_total = cantidad * valor_unidad + iva`. Procesar un suministro no pide `unit_cost`. Si el ítem ya tenía costo, `supplyLines()` precarga `valor_unidad` y no borra el histórico.
- **Formulario:** shell `pur-req-form` (`purchase-requests-page--form` + `purchase-orders-page`), dos secciones (proveedor/datos + lineas), selector de proveedor con `<x-searchable-select>` (solo proveedores **activos**), tabla de lineas con total por linea y total general calculados en JS (`js-line-qty` / `js-line-unit` / `js-line-iva`), textos en MAYUSCULAS (`UppercasesValidatedText`).
- **Entrada:** botón icon-only **Emitir orden de compra** en la fila de Pedidos, en Procesar (compra o suministro) y en **Productos solicitados** del detalle. Solo si se puede emitir: Por comprar o Con orden, no Cerrado.
- **Detalle:** `modules/purchase-requests/orders/show.blade.php` — cabecera con estado y origen, bloque de proveedor (snapshot), detalle con subtotal / IVA / TOTAL, observaciones, resumen y botones de descarga (PDF, Excel).
- **Export:** PDF y Excel con el layout de arriba. Nombre `FO-AD-07-OC-{id}.pdf` / `.xlsx`. La evaluación no va en el documento.
- **Auditoria:** `purchase_order` / `created`, `export` / `order_pdf` | `order_excel`, y `purchase_order_evaluation` created/updated. Cerrar saldo también registra evento. Módulo `purchase_requests`, área `compras`.
- **Evaluación:** pantalla `orders/evaluation`, permiso `purchase.providers.edit`, no el de Pedidos. Ver tabla `purchase_order_evaluations`.
- **Reparto entre varios proveedores:** una misma solicitud puede generar **varias OC**. Cada linea del origen tiene un **saldo pendiente** = `cantidad solicitada - cantidad ya ordenada` (suma de todas las OC de ese origen). La linea de OC guarda su vinculo con la linea de origen (`source_item_type` + `source_item_id`), de modo que el saldo se descuenta automaticamente. El formulario de emision muestra, por linea, `solicitadas / ordenadas / pendiente` y precarga la cantidad con el **pendiente**; el input tiene `max` = pendiente. La validacion (`StorePurchaseOrderRequest`, regla closure sobre `items.*.cantidad`) **rechaza** cualquier cantidad mayor al pendiente (mensaje: "La cantidad supera el saldo pendiente (X). Ya se ordenaron Y de Z unidades."), y el servicio vuelve a validarlo dentro de la transaccion (`InvalidArgumentException` -> redirect con error). Ademas se exige **al menos una linea con cantidad > 0**. Con esto una orden duplicada (mismas cantidades que una OC previa) queda bloqueada cuando no hay saldo.
- **Tablero Órdenes** (clave `ordenes_compra`, etiqueta Órdenes; visible con `purchase.tab.processing` vía `canViewOrdersBoard`): listado server-side (`PurchaseOrderDatatableService`). El folio del origen se muestra `SC-` / `SU-`. El número de OC sigue siendo el `id`.
- **Folio de pantalla:** accessor `PurchaseRequest::folioVisible()` / `SupplyRequest::folioVisible()`. Sin columna nueva.
- **Tests:** `PurchaseOrderEmissionTest`, `PurchaseOrderFoAd07Test` (Cliente vs interna vs suministro, texto legal, una página con 1 y con 12 líneas), `PurchaseFlowStateTest`, `PurchaseSupplyPriceTest`, `PurchaseMenuFolioTest`, `PurchaseOrderEvaluationTest`, `PurchaseOrderMigrationTest`.

Vistas: `resources/views/modules/purchase-requests/orders/{create,show}.blade.php` + `resources/views/pdf/purchase-order.blade.php`.

### Anulación de OC con aprobación del director (2026-10-21)

Una OC emitida no se edita ni se borra. Si quedó mal, Compras pide su anulación y un director la aprueba.

| Estado (`purchase_orders.estado`) | Significado |
| --- | --- |
| `emitida` | OC vigente |
| `anulacion_pendiente` | Compras pidió anularla; espera al director elegido. Su cantidad **sigue contando** en el saldo y no se puede evaluar (la evaluación exige `emitida`) |
| `anulada` | El director aprobó. No se borra ni se reutiliza el número; deja de contar en el saldo (`orderedQuantities`), en el cierre automático (`allOrdersHaveReceiptDate`) y en `puedeDevolverAlSolicitante` |

- **Migración aditiva** `2026_10_21_120000_add_cancellation_fields_to_purchase_orders_table.php`: `anulacion_aprobador_id`, `anulacion_solicitada_por` (FK `users` en MySQL; entero sin FK en sqlite), `anulacion_motivo`, `anulacion_solicitada_at`, `anulacion_respuesta`, `anulacion_resuelta_at`.
- **Solicitar** (Compras, `purchase.tab.processing` vía `canEmitOrder`, sin permiso nuevo): `POST purchase-requests.orders.cancellation.request` (`/ordenes-compra/{id}/anulacion`). Solo `emitida` sin `fecha_recibido` (`puedeSolicitarAnulacion`). Compras **elige el director** que aprueba (`anulacion_aprobador_id`, validado con `PurchaseAccessService::isValidApprover`: activo, rol `director`, `purchase.tab.approval`), para compras y suministros. Motivo obligatorio (`motivo_anulacion`, 3–5000, mayúsculas).
- **Retirar** (Compras): `DELETE purchase-requests.orders.cancellation.withdraw`. Vuelve a `emitida` y limpia los datos de la solicitud.
- **Decidir** (solo el director elegido): en la plataforma, subpestaña **Anulaciones de órdenes** de Autorizaciones compras (`approval.index?vista=anulaciones`) → `GET|PATCH purchase-requests.approval.order-cancellation.show|update` (grupo `purchase.tab:approval`); o por correo con enlace firmado `purchase-requests.order-cancellation-email.show|update` (`routes/modules/purchase-requests-email.php`, vigencia `purchase-orders.cancellation_link_days`, default 7). Aprobar: comentario opcional; rechazar: comentario obligatorio. `ResolvePurchaseOrderCancellationRequest` exige que el usuario (o el `director` del enlace firmado) sea `anulacion_aprobador_id`.
- **Al aprobar:** `estado=anulada` y `PurchaseFlowStateService::sync` recalcula el origen (vuelve a Con orden o Por comprar; un Cerrado no se reabre). PDF con marca **ANULADA** en el título; Excel `ORDEN DE COMPRA O SERVICIO — ANULADA`.
- **Trazabilidad:** cada paso deja una nota en el hilo del pedido de origen (`SOLICITUD DE ANULACIÓN OC N.º …`, `… RETIRADA POR COMPRAS.`, `ANULACIÓN OC N.º … APROBADA|RECHAZADA POR …`) y un evento `purchase_order` (`cancellation_requested`, `cancellation_withdrawn`, `cancellation_approved`, `cancellation_rejected`, con `channel` web/email).
- **Correos:** `PurchaseOrderCancellationRequestedMail` al director (botón con el enlace firmado) y `PurchaseOrderCancellationResolvedMail` a quien la pidió. El sistema no envía la OC al proveedor.
- **Servicio:** `PurchaseOrderCancellationService` (`request`, `withdraw`, `resolve`, `emailShowUrl`, `emailUpdateUrl`). Controlador `PurchaseOrderCancellationController`. Vistas `orders/partials/cancellation`, `approval/order-cancellation`, `order-cancellation-email`, parciales `partials/order-cancellation-{summary,decision}`.
- **Listado Órdenes:** filtro de estado con Anulación pendiente y Anuladas; pastilla amarilla / roja.
- **Tests:** `PurchaseOrderCancellationTest`.

## Import legacy

```bash
php artisan purchase-requests:import-legacy --dry-run
```

Requiere `LEGACY_GESTION_COMPRAS_DB_*` en `.env`. Comando: `ImportLegacyPurchaseRequestsCommand`. Escribe filas 1:N via `PurchaseRequestAttachmentService` (no la columna deprecada). No importa binarios del servidor legado; un path sin fichero puede dejar metadata con download 404.

## Reglas de negocio

- Folio correlativo con lock en transaccion al crear
- Director debe estar en `approversQuery()` (rol director, activo, `purchase.tab.approval`)
- Rechazo director: comentarios obligatorios (`UpdatePurchaseApprovalRequest`)
- Solo solicitudes `aprobado` con `estado_compras` pendiente o en curso entran en Por comprar / Con orden. Historial = Cerrado. `estado_compras=rechazado` no se reescribe y no sale en Pedidos
- El estado de Compras no se elige en el formulario. Abrir Procesar no mueve el suministro
- Después de Cerrado no hay otra OC. Cerrar saldo cierra con motivo en el hilo aunque falten fechas o quede saldo
- El precio vive en la OC. El FO-AD-44 sigue siendo el pedido; el FO-AD-07 es la orden
- Alta y reenvío de la solicitud no usan `UppercasesValidatedText` (observación de revisión): razón social y textos de ítem se guardan como llegan. Sí van a mayúsculas el motivo de Cerrar saldo, las observaciones de la OC, las de la evaluación y el hilo
- `show` muestra formulario de autorizacion si `@can('approve', $purchaseRequest)`
- Adjuntos de solicitud **opcionales** (0–5; 10 MB c/u; tipos en config). No alteran estados ni el flujo de autorizacion
- Al **reenviar rechazada**: `keep_attachment_ids[]` + `attachments[]` nuevos; tope combinado 5; quitar todos es valido
- Audit `create` / `resubmit`: metadata `attachments_count` (entero). Sin paths ni nombres de archivo
- Download sin `view`: 403. Adjunto de otra solicitud: 404. Sin auth: redirect login

## Riesgos y pendientes (adjuntos)

- **Tope PHP:** 5 × 10 MB mas fotos de linea puede superar `post_max_size` / `upload_max_filesize` (el POST se descarta antes de Laravel). No se bajo el tope de producto; si falla el envio, revisar php.ini.
- **Huerfanos en disco:** el hook `deleting` borra el fichero si Eloquent elimina el adjunto. Un cascade SQL al borrar la solicitud padre no dispara el evento (aceptable en v1).
- **Tope keep + nuevos en UI:** el servidor rechaza si se supera 5; no hay aviso en cliente (observacion de revision, no bloqueante).
- **Form Requests de procesar (2026-10-21):** se eliminó `ProcessPurchaseComprasRequest` (aceptaba `estado_compras` y nadie lo usaba). El PATCH de procesar compra solo redirige con aviso. `ProcessSupplyComprasRequest` solo acepta `purchasing_observations` (MAYÚSCULAS, máx. 1000 por línea); ya no exige `action` ni `items`. No volver a cablear el cambio manual de estado.
- **Aprobador:** `Store/UpdatePurchaseRequestRequest` validan `aprobador_id` con `PurchaseAccessService::isValidApprover` (activo + rol `director` + `purchase.tab.approval`), el mismo criterio de la lista del formulario.
- **Etiquetas muertas en `purchase_tabs` / `supply_tabs`:** «Bandeja compras», «Insumos aprobados» y «Catalogo» siguen en el diccionario. No son permisos nuevos y `visibleTabsFor` ya no las devuelve.
- **Hilo de suministro:** `SupplyRequestPolicy::comment` deja comentar a Compras, al dueño y a quien tiene `supply.tab.quality`, también si el suministro es de otra área. El hilo se cierra en `completada`.
- **Mayúsculas del alta de compra:** ver reglas de negocio. Al volver a tocar `StorePurchaseRequestRequest` / `UpdatePurchaseRequestRequest`, aplicar el trait y dejar fuera radios, fechas y archivos.
- **Cerrar saldo** es una sección del formulario, no el modal opaco del brief. El chrome es el del módulo.
- `NavigationVisibilityTest` del director puede fallar por causas previas a FEAT-048 (área TIC del rol `director` y `area_key` aleatorio de la factory). No lo introduce este corte.

## Pruebas

- `tests/Feature/PurchaseRequestModuleTest.php`
- `tests/Feature/PurchaseRequests/FoAd44ExportAlignmentTest.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php`
- `tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php`
- `tests/Feature/PurchaseRequests/PurchaseProviderAccessTest.php` (view vs edit, 403, guest)
- `tests/Feature/PurchaseRequests/PurchaseProviderCatalogTest.php` (DT default, filtros, uppercase, unique, update conserva filtros)
- `tests/Feature/PurchaseRequests/PurchaseOrderEmissionTest.php` (emision FO-AD-07 desde compra y suministro, snapshot, totales, 403, show/PDF/Excel)
- `tests/Feature/PurchaseRequests/PurchaseOrderFoAd07Test.php` (layout oficial, texto legal, una página)
- `tests/Feature/PurchaseRequests/PurchaseFlowStateTest.php` (máquina, Cerrar saldo, exclusión de rechazado, dashboard)
- `tests/Feature/PurchaseRequests/PurchaseSupplyPriceTest.php` (precio solo en la OC)
- `tests/Feature/PurchaseRequests/PurchaseMenuFolioTest.php` (Pedidos / Órdenes / Catálogos, folio `SC-`/`SU-`)
- `tests/Feature/PurchaseRequests/PurchaseOrderEvaluationTest.php` (fórmula, fecha sola, 403, PDF sin evaluación, cierre con y sin fechas)
- `tests/Feature/PurchaseOrderMigrationTest.php` (estructura `purchase_orders`, morph, `numeroOc`, evaluacion 1:1)
- `tests/Feature/ComprasDashboardTest.php`
- `tests/Feature/RoleDirectorMigrationTest.php`
- `tests/Feature/NavigationVisibilityTest.php`

## Control de cambios

| Fecha | Descripcion |
| --- | --- |
| 2026-10-23 | Export de Órdenes: hoja **Productos** con columnas nuevas *Fecha solicitud* (compra: `fecha_solicitud`; suministro: `created_at`) y *Fecha aprobación* (compra: `fecha_aprobacion` del director; suministro: última auditoría `quality_approve` de Calidad). Hoja nueva **Evaluación**: una fila por OC filtrada con recibido, las tres calificaciones, resultados, puntaje, resultado (`purchase-orders.evaluation.labels`), observaciones, evaluado por y fecha de evaluación (vacía si no se evaluó). `BaseExport::writeTo(Worksheet)` permite libros con varias hojas; `download()` no cambia |
| 2026-10-23 | **IVA por tasa del catálogo:** tabla `purchase_tax_rates` (migración `2026_10_23_120000`, siembra 0% / 5% / 19%; sin borrado, se inactivan) y `purchase_order_items.iva_porcentaje`. Emitir OC: selector `items[i][iva_porcentaje]` (`<x-searchable-select>`, solo activas, 19% por defecto); `PurchaseOrderService` calcula `iva = round(cantidad × valor_unidad × % / 100, 2)` y `valor_total = base + iva`, ignorando un `iva` enviado si llega tasa (sin tasa se respeta `iva`, compatibilidad). Pestaña **Catálogos → IVA** (`purchase-requests.tax-rates.index|store|update`, `/catalogos/iva`): ver con `purchase.providers.view`, editar con `purchase.providers.edit` (sin claves nuevas). Detalle OC, panel de OC del pedido, PDF FO-AD-07 y Excel muestran "valor (tasa)" vía `PurchaseOrderItem::ivaImpreso()`; el export de Órdenes agrega columna "IVA %" |
| 2026-10-23 | Orden de columnas: en `show` (tabla de productos) Cantidad → Referencia → Descripción; en `orders/create` el encabezado "Artículo" pasa a "Referencia" (el campo sigue siendo `articulo`). Detalle de OC, FO-AD-07 y export conservan "Artículo" |
| 2026-10-22 | **Export Excel del tablero Órdenes:** `GET purchase-requests.orders.export` (`/ordenes-compra/exportar`, grupo `purchase.tab:processing`, declarada antes de `{purchase_order}`). Toma los filtros del panel (estado, origen, proveedor, desde, hasta) y la búsqueda del DataTable como `q` (`PurchaseOrderDatatableService::applySearchTerm`). `App\Exports\PurchaseOrderLinesExport` sobre `BaseExport`: una fila por línea de OC con datos de la orden (N.º OC, fecha, estado, origen/folio/área, cotización, proveedor y contacto, forma de pago, ciudad de entrega, elaborado por, recibido, observaciones, total OC) y de la línea (cantidad, artículo, marca de origen, descripción, valor unidad, IVA, total). Incluye anuladas si el filtro lo permite. Audit `export` / `orders_lines_excel` |
| 2026-10-22 | **OC en el detalle del pedido:** panel `partials/source-orders` en `purchase-requests.show` y `supplies.show` (N.º OC, fecha, proveedor, subtotal/IVA/total, estado con anuladas marcadas, recibido, líneas con valor unitario) + columnas Ordenado / Pendiente en productos. PDF FO-AD-07 por rutas nuevas fuera del grupo de Compras: `purchase-requests.source-orders.pdf` (`/solicitud/{pr}/ordenes/{oc}/pdf`, policy `view`: solicitante, director asignado, Compras) y `supplies.orders.pdf` (`authorizeSupplyView`: solicitante, Calidad, Compras); 404 si la OC no es de ese pedido. Relación `purchaseOrders()` (morphMany `source`). Audit `export` / `order_pdf_from_request` · `order_pdf_from_supply` |
| 2026-10-22 | Cotización, marca y comentarios del solicitante también en Procesar compra, aprobación por correo, PDF FO-AD-44 (fila N.º cotización, columna Marca, comentarios en notas; el Excel FO-AD-44 no cambia), Autorizaciones compras y Pedidos (columnas N.º cotización / Marca / Comentarios vía `partials/list-extra-cells`, marcas únicas con `PurchaseRequest::marcasResumen()`; en Pedidos solo filas SC-) |
| 2026-10-22 | Productos solicitados: `purchase_request_items.marca` (string 255, opcional, pedido de Comercial) al lado de Referencia en Nueva/Reabrir, detalle y plantilla Excel de ítems (columna `marca`, opcional vía `items_import_optional_columns`; plantillas viejas siguen cargando). Migración aditiva `2026_10_22_093000`. No va en el FO-AD-44 ni en la OC |
| 2026-10-22 | Datos generales: `numero_cotizacion` (string 100) y `comentarios_solicitante` (text), opcionales, en Nueva/Reabrir y detalle. Migración aditiva `2026_10_22_090000`. Se guardan como se escriben (sin mayúsculas, igual que el resto de la solicitud); no van en el FO-AD-44 |
| 2026-10-21 | Pestaña del director renombrada **Autorizaciones compras** (`purchase_tabs.pendientes_aprobacion`) con subpestañas Pendientes de autorización / Anulaciones de órdenes (`vista`) |
| 2026-10-21 | **Anulación de OC** con aprobación del director que elige Compras (plataforma o correo firmado); estados `anulacion_pendiente` / `anulada`; la OC anulada libera saldo; migración aditiva |
| 2026-10-21 | Compras puede **devolver al solicitante** (Por comprar sin OC, motivo al hilo); al reenviar, el director o Calidad vuelven a autorizar |
| 2026-10-21 | Etapa común `PedidoEtapa` (fases 1 y 2, sin migraciones): mismas etiquetas y colores en compras y suministros; scopes `whereEtapa`; Pedidos, dashboard y filtro de Mis solicitudes consultan por etapa |
| 2026-10-21 | Ajustes de revisión: `aprobador_id` exige director activo; se borra `ProcessPurchaseComprasRequest`; `ProcessSupplyComprasRequest` solo observaciones en MAYÚSCULAS |
| 2026-10-21 | Mis solicitudes: filtros GET (fechas, estado, estado compras, urgencia, área) con `PurchaseMyRequestsFilterBag` |
| 2026-10-21 | Emitir OC (`orders/create`): en **Líneas y precios** la columna **Pendiente** va antes de **Cantidad** (orden: #, Pendiente, Cantidad, Artículo, Descripción, Valor unidad, IVA, Valor total) |
| 2026-10-21 | VINE01: plantilla e import reciben `CONTACTO`, `CIUDAD` y `FORMA_PAGO` (ciudad contra `requisition_cities`); el alta manual ya tenia los mismos campos |
| 2026-10-20 | FEAT-048: FO-AD-07 oficial (una hoja, condiciones, filas de cliente), máquina `PurchaseFlowStateService`, tableros Pedidos / Órdenes / Catálogos (sin claves nuevas), folio `SC-`, Cerrar saldo, precio solo en la OC, evaluación que no se imprime |
| 2026-10-20 | Proveedores: rediseño del listado — chips de estado + conteo + acciones icon-only en una sola franja (sin panel de filtros), columnas con formato (nombre destacado, correo enlazable, días/estado/acciones centrados) |
| 2026-10-19 | Boton icon-only **Emitir orden de compra** dentro de la seccion **Productos solicitados** del detalle de la solicitud (solo si el usuario puede procesar la solicitud) |
| 2026-10-19 | FEAT-047 T4 (cierre): **reparto de una solicitud entre varios proveedores** — cada linea tiene saldo pendiente (`solicitada - ordenada`), la OC guarda el vinculo con su linea de origen, el formulario muestra/precarga el pendiente y la validacion **bloquea** ordenes duplicadas. Nuevo **tablero "Ordenes de compra"** (area Compras) con listado server-side, busqueda y filtros (estado, origen, proveedor, fechas) |
| 2026-10-19 | FEAT-047 T4: emision de **Orden de Compra FO-AD-07** desde la bandeja (compra y suministro) — proveedor activo con snapshot congelado, precios por linea (`valor_total = cantidad * valor_unidad + iva`), numero OC = `id`, detalle + export PDF/Excel (`Enero 2025` v04) y audit `purchase_order`/`created` + `export` |
| 2026-10-19 | FEAT-047 T4: tablas `purchase_orders`, `purchase_order_items` y `purchase_order_evaluations` (morph `purchase_request`/`supply_request` con `Relation::morphMap`); `provider_id` y `created_by` nullable para los FK `ON DELETE SET NULL` |
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
- Feature Brief: [`docs/briefs/FEAT-048.md`](../briefs/FEAT-048.md), [`docs/briefs/FEAT-030.md`](../briefs/FEAT-030.md)
- Revisión: [`docs/reviews/FEAT-048.md`](../reviews/FEAT-048.md)
- Acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Suministros: [`docs/modules/suministros.md`](suministros.md)
