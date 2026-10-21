# Módulo Compartido: Gestión de Suministros

## 1. Objetivo del Módulo
Permitir a los usuarios autorizados solicitar insumos (aseo, cafetería, papelería, etc.), con revisión de Calidad y compra en el tablero **Pedidos**. El precio se captura al emitir la orden de compra. El catálogo de productos vive en **Catálogos**, no en el tablero Suministros.

## 2. Flujo del Proceso (Estados de Solicitud)
En pantalla, `status` se muestra con la **etapa común** (`App\Enums\PedidoEtapa`), igual que compras: `pendiente_calidad` = Por autorizar, `rechazada_calidad` = Devuelta, `aprobada_calidad` = Por comprar, `en_compras` = Con orden, `completada` = Cerrado. Los valores guardados no cambian. Detalle en [`purchase-requests.md`](purchase-requests.md#flujo-de-estados).

El ciclo de vida de una solicitud de suministros será el siguiente:

1. **`borrador` (Opcional):** El solicitante está armando la lista.
2. **`pendiente_calidad`:** Solicitud enviada. Esperando revisión de la Jefe de Calidad.
3. **`aprobada_calidad`:** Calidad revisó, (opcionalmente) ajustó cantidades y aprobó el pedido. En Pedidos se ve como **Por comprar**. Abrir Procesar no cambia este estado.
4. **`rechazada_calidad`:** Calidad denegó la solicitud. El solicitante dueño puede **reenviarla**: vuelve a `pendiente_calidad` y no borra `quality_observations`.
5. **`en_compras`:** Hay al menos una orden y queda saldo, o el saldo ya es cero pero falta la fecha de recibido en alguna OC. En Pedidos: **Con orden**.
6. **`completada`:** Cerrado. Lo escribe la orden (saldo en cero y fecha de recibido en todas las OC) o **Cerrar saldo** con motivo. No lo cierra el costo unitario. Es terminal: no se emite otra OC y el hilo deja de aceptar notas.

## 3. Estructura de Base de Datos

### Tabla: `supply_products` (Catálogo Maestro)
Catálogo administrable de productos disponibles para pedir.
* `id` (PK)
* `name` (string) - Ej: "ALCOHOL GALON"
* `description` (string, nullable) - Ej: "LIMPIADOR SUPERFICIES"
* `category` (string, nullable) - Ej: "Aseo", "Cafetería" (Para organizar el catálogo)
* `is_active` (boolean) - Default true. Si es false, no aparece para nuevas solicitudes.
* `timestamps`

### Tabla: `supply_requests` (Cabecera del Pedido)
* `id` (PK)
* `user_id` (FK -> users) - Quién solicita
* `area_key` (string) - Área a la que pertenece el solicitante (para centros de costo)
* `status` (enum/string) - Estado actual (ver flujo)
* `observations` (text, nullable) - Notas del solicitante
* `quality_reviewer_id` (FK -> users, nullable) - Quién aprobó/rechazó en Calidad
* `quality_observations` (text, nullable) - Notas de Calidad al aprobar/rechazar
* `purchasing_manager_id` (FK -> users, nullable) - Quién gestionó la compra
* `total_cost` (decimal, nullable) - Costo total de la solicitud (calculado al completar)
* `timestamps`

### Tabla: `supply_request_items` (Detalle del Pedido)
* `id` (PK)
* `supply_request_id` (FK -> supply_requests)
* `supply_product_id` (FK -> supply_products)
* `requested_quantity` (integer) - Cantidad pedida originalmente
* `approved_quantity` (integer, nullable) - Cantidad autorizada por Calidad
* `unit_cost` (decimal, nullable) - Costo ingresado por Compras
* `timestamps`

## 4. Matriz de Permisos (`config/access.php`)

Siguiendo la arquitectura actual, los accesos se controlan con permisos funcionales (`supply.tab.*`) y tablero por area (`view.board.{area}.suministros`). **Hogares canonicos del sidebar:**

| Funcion | Donde aparece en el menu |
| --- | --- |
| Mis solicitudes (solicitante) | Area base del usuario → Suministros |
| Por aprobar (`supply.tab.quality`) | **Calidad** → Suministros. Solo esa pestaña. La URL vieja `supplies.approved.index` redirige a Por aprobar |
| Catálogo de insumos (`supply.tab.catalog`) | **Compras** → **Catálogos** → pestaña Insumos |
| Compra del pedido | **Compras** → **Pedidos** (clave `bandeja_compras`) |

Ver [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md) y `SidebarVisibilityService`.

## 5. Precio y cierre (FEAT-048)

* Procesar un suministro **no** pide ni guarda `unit_cost`. Un costo ya guardado se conserva.
* Al emitir la OC, `PurchaseOrderService::supplyLines()` precarga `valor_unidad` desde ese `unit_cost`. El precio vigente es el de la orden.
* El cierre no depende del costo. `PurchaseFlowStateService` pasa a `completada` solo si el saldo de todas las líneas es cero y cada OC tiene `fecha_recibido`. **Cerrar saldo** cierra igual, con motivo en el hilo, aunque falte una fecha o quede cantidad.
* `total_cost` y `unit_cost` siguen en el esquema. No se usan para cerrar el pedido.

## 6. Plan de Ejecución (Sprints/Pasos)

1. **Paso 1: Migraciones y Modelos.** Crear las 3 tablas y sus relaciones Eloquent.
2. **Paso 2: Seeder Inicial.** Cargar los 49 artículos enviados en la imagen al catálogo inicial `supply_products`.
3. **Paso 3: Rutas, Controladores y Permisos.** Registrar el módulo en la navegación y establecer permisos.
4. **Paso 4: Vistas - Catálogo.** CRUD básico para el administrador del catálogo.
5. **Paso 5: Vistas - Solicitante.** Interfaz para crear un pedido seleccionando productos.
6. **Paso 6: Vistas - Calidad.** Bandeja de entrada para revisar, modificar cantidades y aprobar.
7. **Paso 7: Vistas - Compras.** El pedido se compra en Pedidos. El precio va en la orden, no en un costo unitario de cierre.

---

## 7. Estado real implementado (actualizado)

### Permisos vigentes (`config/access.php`)
El tablero Suministros muestra solo las pestañas que `SupplyAccessService::visibleTabsFor` devuelve. FEAT-048 no creó claves nuevas. `supply_tabs` todavía nombra «Insumos aprobados» y «Catalogo»; esas entradas ya no salen en el menú.

- `supply.tab.my_requests`: ver, crear y **reenviar** solicitudes propias (area base del usuario).
- `supply.tab.quality`: solo **Por aprobar** (`aprobacion_insumos`). Sidebar en **Calidad**. Ya no abre Insumos aprobados.
- `supply.tab.catalog` (y `manage.supply.catalog`): pestaña **Insumos** dentro del tablero **Catálogos** (`canViewInsumosCatalog`). Quien solo administra el catálogo entra por Catálogos, no por un Suministros vacío.
- `approve.supply.quality`: variante de revisión de Calidad.
- `view.board.{area}.suministros`: solo visualiza el tablero cuando aplica la regla canónica.

`User::defaultSupplyBoardUrl()` abre la primera pestaña que quede: Mis solicitudes o Por aprobar. Ya no prioriza `insumos_aprobados` ni `catalogo`.

### Flujo de estados
`pendiente_calidad` → `aprobada_calidad` | `rechazada_calidad`. El rechazo se reenvía a `pendiente_calidad` (policy `resubmit`: dueño, `supply.tab.my_requests`, área de la URL). `aprobada_calidad` → `en_compras` → `completada` lo escribe `PurchaseFlowStateService` al emitir, al registrar la fecha de recibido o al cerrar saldo. Ver [`purchase-requests.md`](purchase-requests.md).

Folio de pantalla: `SupplyRequest::folioVisible()` = `SU-` + `folio()` (el `id` relleno a 4; por encima de 9999 no se recorta). El id guardado no cambia.

### Rutas reales (`routes/modules/supplies.php`)
Prefijo `supplies/{module}`:

- `GET /mis-solicitudes` (`supplies.index`)
- `GET /solicitud/{supply_request}` (`supplies.show`) — detalle alineado visualmente a solicitud de compra
- `GET /solicitud/{supply_request}/pdf` (`supplies.export.pdf`) — FO-AD-44, layout igual que PDF solicitud compra
- `GET /solicitud/{supply_request}/excel` (`supplies.export.excel`) — FO-AD-44 Excel
- `GET|POST /solicitar` (`supplies.create` / `supplies.store`) — UI catalogo + carrito con layout `pur-req-form-layout` (secciones + aside) e iconos chrome; quitar linea con `.cursos-catalogo-page__icon-btn--danger`.
- `GET /mis-solicitudes` (`supplies.index`) — listado `purchase-requests-page--list` con toolbar Excel/Nueva y Ver detalle icon-only.
- `GET /aprobacion-insumos` (`supplies.approval.index`) — listado `purchase-requests-page--list` + Revisar icon-only
- `GET /aprobacion-insumos/{supply_request}/editar` (`supplies.approval.edit`)
- `PATCH /aprobacion-insumos/{supply_request}` (`supplies.approval.update`) — Form Request `ResolveSupplyQualityRequest` (`action` approve/reject, `quality_observations` máx. 5000 en MAYÚSCULAS, `approved_quantity` entero ≥ 0 al aprobar; sin tope frente a lo solicitado)
- `GET /mis-solicitudes/{supply_request}/editar` y `PATCH /mis-solicitudes/{supply_request}` (`supplies.edit` / `supplies.update`) — reenvío si `rechazada_calidad`
- `POST /solicitud/{supply_request}/comentarios` (`supplies.comments.store`) — hilo; policy `comment`
- `GET /insumos-aprobados` (`supplies.approved.index`) — **redirige** a `supplies.approval.index`. Ya no es un escritorio. Las rutas de export de ese listado no tienen entrada de menú
- `GET|POST /catalogo` y `PATCH /catalogo/{product}` (`supplies.products.*`) — pestaña Insumos de **Catálogos**; el nav activo es Catálogos, no Suministros

### Items fuera de catalogo
En `supply_request_items`:

- `supply_product_id` nullable
- `custom_product_name` (string, nullable)
- `is_not_in_catalog` (boolean): marca productos agregados manualmente por el solicitante

### Campos adicionales en base de datos
- `current_inventory` (integer, default 0): inventario actual reportado por el solicitante.
- `purchasing_observations`, `unit_cost`, `total_cost`: el costo histórico se conserva. Procesar el suministro ya no lo pide ni lo usa para cerrar. El precio se captura en la orden de compra.

### Sedes (`supply_sites`) y snapshot en solicitudes
Catálogo de sedes físicas con `name`, `utilization` (columna Utilización del Excel), `city` (columna Ubicación) e `is_active`.

- Seeder inicial: Cali (Sede Principal y central de monitoreo), Cartagena, Manizales.
- Cada usuario puede tener `sede_id` (FK nullable) asignada desde **Admin > Usuarios**.
- Desde el formulario de usuario, botón **Gestionar** abre un modal para crear, editar o eliminar sedes (`admin/supply-sites`).
- Al crear una solicitud (`store`), se copia snapshot: `sede_id`, `site_utilization`, `site_city` desde la sede del usuario. Sin sede asignada no puede solicitar.
- `exported_at` en `supply_requests`: marca solicitudes ya incluidas en un export FO-AD-44.

### Vista detalle (`supplies.show`)

Vista: `resources/views/modules/supplies/show.blade.php`. Misma estructura que `purchase-requests/show`:

- Layout `purchase-requests-page--detail` + `pur-req-detail-layout` (main + aside), meta `pur-req-form__meta`, secciones `pur-req-form__section`.
- Toolbar icon-only (`.req-manage-filters__icon-btn`): volver contextual, PDF/Excel FO-AD-44 (si exportable), Procesar si viene de bandeja.
- Cabecera con folio visible `SU-` (`folioVisible()`), pills de estado.
- Tabla de lineas (#, foto placeholder, cantidades, descripcion, referencia, inventario, cant. autorizada).
- Observaciones de calidad y notas del solicitante; bloque procesamiento compras si aplica.
- Aside: resumen + guia de flujo.

**Subnav:** solicitante y Calidad ven las pestañas que queden en Suministros (Mis solicitudes o Por aprobar). Compras vuelve a Pedidos. El catálogo no está en este subnav.

**Export FO-AD-44 desde detalle:** disponible cuando `status` ∈ `aprobada_calidad`, `en_compras`, `completada` (`SupplyRequest::isExportableForCompras()`). PDF usa filas autorizadas (`approved_quantity > 0`) agrupadas como en Excel.

### Hilo de notas (`supply_request_comments`)

Tabla nueva (FEAT-048), espejo de `purchase_request_comments`: `supply_request_id`, `user_id`, `body`, timestamps, índice `(supply_request_id, created_at)`. Sin backfill. Modelo `SupplyRequestComment`; relación `SupplyRequest::comments()`.

- El motivo de **Cerrar saldo** se guarda aquí, en mayúsculas (`UppercasesValidatedText` / `UppercaseFormText`).
- `observations` y `quality_observations` se siguen mostrando. El rechazo de Calidad puede seguir en `quality_observations`.
- Policy `comment`: cerrado si `status=completada`. Pueden comentar Compras (`purchase.tab.processing`), el dueño y quien tiene `supply.tab.quality` o `approve.supply.quality`. No exige que Calidad vea la solicitud en su área (observación de revisión: puede comentar un suministro de otra área). Ruta dentro de `auth`.
- El alta y el reenvío pasan `observations` y `custom_product_name` por `UppercaseFormText`. El reenvío no borra `quality_observations`.

### Reporte Excel FO-AD-44 (por solicitud)

El listado **Insumos aprobados** ya no está en el menú. El Excel FO-AD-44 se descarga desde el detalle de una solicitud exportable (`aprobada_calidad`, `en_compras`, `completada`). La ruta histórica `supplies.approved.export` sigue en el archivo de rutas, sin entrada de menú. Comportamiento que esa ruta conservaba:

1. Listado cross-área de solicitudes `aprobada_calidad` con filtros (sede, fechas, estado de exportación, solicitante).
2. Cada fila permite **Descargar FO-AD-44** para esa solicitud.
3. El Excel incluye solo los ítems de la solicitud con `approved_quantity > 0`; une filas duplicadas por Descripción + Referencia dentro de la misma solicitud.
4. Genera `.xlsx` con formato FO-AD-44 (`config/supplies.php`, servicio `SupplyPurchaseReportExporter`).
5. Marca `exported_at` en la primera descarga (filtro “Pendientes”); las descargas posteriores siguen disponibles.

| Columna Excel | Fuente |
|---------------|--------|
| Cantidad | Suma `approved_quantity` tras merge |
| Insertar Foto del Artículo | `N/A` |
| Descripción | `product.name` o `custom_product_name` |
| Referencia | `product.description` o `N/A` si fuera de catálogo |
| Utilización | `site_utilization` (snapshot) |
| Ubicación | `site_city` (snapshot) |

### Reglas de autorizacion aplicadas
- `supplies.show`: solicitante dueno; revisores Calidad (`supply.tab.quality`, `approve.supply.quality`); analista Compras con `purchase.tab.processing` si el suministro esta en bandeja (`aprobada_calidad`, `en_compras`, `completada`); `manage.users`.
- `supplies.export.pdf` / `supplies.export.excel`: misma visibilidad que show + estado exportable.
- `supplies.approval.*`: solicitudes `pendiente_calidad` de **todas las áreas** (2026-10-21). El binding `supply_request` (`AppServiceProvider`) trata `supplies.approval.*` como ruta cross-área; el acceso sigue exigiendo la pestaña Por aprobar en el módulo de la URL (`supply.tab:quality`).
- Por aprobar tiene filtro GET `area_key` (área solicitante) y el export respeta ese filtro. La auditoría de aprobar/rechazar guarda el área del suministro y `reviewed_from_module`.
- El correo a Calidad enlaza a Por aprobar en el hogar `calidad` (`board_canonical_areas.suministros.alt_home`). Bandeja Compras es cross-area via `ComprasQueueService`.

### Navegacion
- `User::defaultSupplyBoardUrl()` abre Mis solicitudes o Por aprobar.
- Pestañas visibles del tablero Suministros: `mis_solicitudes`, `aprobacion_insumos`.
- Pestaña Insumos del tablero Catálogos: `User::catalogBoardTabs()` (`insumos`) si `canViewInsumosCatalog`. Subnav `catalogos-subnav`.

### Notificaciones
- Al crear una solicitud (`store`), se envia `SupplyRequestNotification` a usuarios con `supply.tab.quality` o `approve.supply.quality`.

### Pruebas
- `tests/Feature/SupplyModuleTest.php` y los de FEAT-048 en compras (`PurchaseFlowStateTest`, `PurchaseMenuFolioTest`, `PurchaseSupplyPriceTest`): reenvío, hilo, redirección de la URL vieja de insumos aprobados, folio `SU-` y precio solo en la orden.

### Pendientes conocidos
- Estado `borrador` documentado pero no implementado.

### Integración con Pedidos
- **Por comprar:** `aprobada_calidad`. **Con orden:** `en_compras`. **Historial:** `completada`. El listado ordena por fecha más reciente.
- **Ver detalle** abre `supplies.show` (folio `SU-`) y permite PDF/Excel FO-AD-44 si el estado es exportable.
- **Procesar** abre `purchase-requests.processing.supply`. No pide costo unitario y no cambia el estado al abrirlo.
- El paso a `en_compras` o `completada` lo hace la orden (o Cerrar saldo), no un selector.
- Notificación a Compras al aprobar Calidad (`supply_request_approved_for_compras`).
- Ver [`purchase-requests.md`](purchase-requests.md).

### Control de cambios (modulo)

| Fecha | Descripcion |
| --- | --- |
| 2026-10-21 | Etapa común `PedidoEtapa` en listados, detalle y PDF; Por aprobar de Calidad para todas las áreas con filtro por área |
| 2026-10-21 | Alta en MAYÚSCULAS (observaciones y producto fuera de catálogo); aprobación de Calidad con `ResolveSupplyQualityRequest` y observaciones en MAYÚSCULAS |
| 2026-10-20 | FEAT-048: reenvío si Calidad rechaza, hilo `supply_request_comments`, catálogo dentro de Catálogos, sin pestaña Insumos aprobados, folio `SU-`, cierre por la orden (no por costo unitario) |
| 2026-10-02 | UI aprobacion/insumos-aprobados/catalogo: layout pur-req list + filtros chrome e iconos |
| 2026-10-02 | UI mis-solicitudes/solicitar: layout pur-req (list + form) e iconos chrome; carrito de catalogo conservado |
| 2026-10-02 | UI detalle suministro: layout pur-req (secciones + aside) e iconos chrome; mismo patron que solicitud compra |
| 2026-08-03 | Detalle `supplies.show` alineado a solicitud compra; rutas `supplies.export.pdf` / `excel` |
| 2026-08-03 | PDF FO-AD-44 suministro con layout identico a PDF solicitud compra |
| 2026-08-03 | Doc corregida: estados `en_compras` y `completada` activos via bandeja Compras |
| 2026-08-28 | Bandeja compras: orden por fecha descendente para no ocultar insumos aprobados |

---
*Documento vivo. Actualizar si cambian las reglas de negocio durante el desarrollo.*

## Referencias

- Guia de usuario: [`docs/user/suministros.md`](../user/suministros.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
