# Estado T4 — PurchaseOrder / OC morph

Fecha: 2026-10-19 (actualizado al cierre de T4)
Feature: FEAT-047 T4 (OC morfismo + emisión + evaluación no impresa)

## Creado / actualizado

### Base de datos (aplicadas en MySQL local, batch 102)

- `database/migrations/2026_10_19_145000_create_purchase_orders_table.php` (morph `source_type`/`source_id`; snapshot de proveedor; `id` = número OC; `provider_id` y `created_by` **nullable** para los FK `ON DELETE SET NULL`).
- `database/migrations/2026_10_19_145100_create_purchase_order_items_table.php`.
- `database/migrations/2026_10_19_145200_create_purchase_order_evaluations_table.php` (1:1 con `purchase_order_id`; pesos 25/25/50; calif 1–5).

### Modelos / infra

- `app/Models/PurchaseOrder.php` (morphTo + `numeroOc()` + `estadoLabel()` + `sourceLabel()`/`sourceFolio()` + items ordenados).
- `app/Models/PurchaseOrderItem.php`, `app/Models/PurchaseOrderEvaluation.php` (con `HasFactory`).
- `app/Providers/AppServiceProvider.php` (`Relation::morphMap`: `purchase_request` / `supply_request`; `Route::bind('supply_request')` reconoce `purchase-requests.orders.` como ruta cross-area).
- `app/Services/Access/PurchaseAccessService.php` (`canEmitOrder`: `purchase.tab.processing` o bypass admin).
- Factories: `PurchaseOrderFactory`, `PurchaseOrderItemFactory`, `PurchaseOrderEvaluationFactory`.

### Emisión y documentos

- `app/Services/PurchaseRequests/PurchaseOrderService.php` (snapshot, líneas desde el origen, `valor_total = cantidad × valor_unidad + iva`, total, estado `emitida`).
- `app/Http/Controllers/PurchaseRequests/PurchaseOrderController.php` (create/store desde compra y suministro, show, PDF, Excel).
- `app/Http/Requests/PurchaseRequests/StorePurchaseOrderRequest.php` (proveedor activo, `items.*`, uppercase).
- `app/Services/PurchaseRequests/PurchaseOrderPdfExporter.php` + `resources/views/pdf/purchase-order.blade.php` (FO-AD-07, sin bloque de evaluación).
- `app/Services/PurchaseRequests/PurchaseOrderExcelExporter.php`.
- `config/purchase-orders.php` (FO-AD-07 · Enero 2025 · versión 04; estados; pesos/umbrales de evaluación para T5).
- Rutas `purchase-requests.orders.*` en `routes/modules/purchase-requests.php` (grupo `purchase.tab:processing`).
- Vistas `resources/views/modules/purchase-requests/orders/{create,show}.blade.php`.
- Botón icon-only **Emitir OC** (`x-lucide-file-plus`) en la fila de la bandeja y en las cabeceras de procesar compra/suministro.

## Cierre T4 (2026-10-19, segunda tanda)

### Reparto de una solicitud entre varios proveedores

- `PurchaseOrderService::sourceLines()` / `orderedQuantities()`: saldo pendiente por linea = `cantidad solicitada - cantidad ya ordenada` (suma de todas las OC del origen).
- `purchase_order_items.source_item_type` / `source_item_id` = vinculo con la linea de origen (`purchase_request_item` / `supply_request_item`). Backfill por posicion en la migracion `2026_10_19_180000_backfill_purchase_order_item_sources.php` (aplicada).
- `StorePurchaseOrderRequest`: regla closure sobre `items.*.cantidad` que **rechaza** cantidad mayor al pendiente; exige al menos una linea con cantidad > 0. El servicio revalida dentro de la transaccion (`InvalidArgumentException` -> redirect con error).
- Formulario de emision: columna **Pendiente** (`solicitadas / ordenadas / pendiente`), cantidad precargada con el pendiente, `max` = pendiente, banner con las OC ya emitidas del origen y filas en gris sin saldo.
- Detalle de la OC: columna **Saldo origen** y panel **Otras órdenes de esta solicitud**.

### Tablero "Órdenes de compra" (listado)

- `config/access.php`: board `ordenes_compra` (`boards` + `board_canonical_areas`, home `compras`).
- `PurchaseAccessService::canViewOrdersBoard()` (`purchase.tab.processing`), `SidebarVisibilityService::shouldShowOrdersBoard()`, rama propia en `NavigationResolver` (activa el board en rutas `purchase-requests.orders.*` y deja de marcar `solicitudes_compra` en esas rutas).
- `PurchaseOrderDatatableService` (server-side: busqueda + filtros estado/origen/proveedor/fechas) y `PurchaseOrderController::index()/datatable()`.
- Rutas `purchase-requests.orders.index` (`GET /ordenes-compra`) y `purchase-requests.orders.datatable` (`GET /ordenes-compra/datatable`).
- Vista `resources/views/modules/purchase-requests/orders/index.blade.php` con el chrome canonico (panel, filtros plegables con badge, toolbar con conteo, DataTables server-side, encabezados en mayusculas, alto completo).

### Datos de prueba del usuario (NO se tocan)

En la BD local de desarrollo hay 4 OC: N.º 1 (compra 0012, 2 de 2 uds), N.º 2 y N.º 3 (suministro 0012, **mismas cantidades** en ambas) y N.º 4 (compra 0022). OC 2 y 3 duplican el pedido: con el saldo pendiente ya en 0, no se puede emitir una nueva OC para esas lineas. **Decision del usuario (2026-10-20): dejarlas quietas, son datos de prueba** → no se borra ni se modifica esa data.

## Ejecutado / verificado

- `php artisan migrate` — 3 tablas nuevas aplicadas (sin `migrate:fresh`; la tabla parcial del intento fallido se eliminó **con autorización del usuario**).
- `vendor/bin/pint --dirty --format agent` — OK.
- `php artisan test tests/Feature/PurchaseOrderMigrationTest.php tests/Feature/PurchaseRequests/PurchaseOrderEmissionTest.php` — 12 tests verdes (+ 10 del catálogo de proveedores).
- Smoke en navegador: OC N.º 1 emitida desde la bandeja, detalle con snapshot y totales, PDF (`%PDF-`) y Excel (`.xlsx`) 200, 0 errores de consola.

## Pendiente (T5)

- Evaluación post-entrega (tabla `purchase_order_evaluations` ya creada): captura de calificaciones 1–5 con pesos 25/25/50, umbrales 80/60, pantalla y reporte. **No** se imprime en FO-AD-07.
- Revisar si la OC debe enlazarse al suministro (`supply_request` ya se guarda como `source_type`).
- Decidir qué se hace con las OC duplicadas de prueba del usuario (N.º 2 y 3 del suministro 0012) en la BD local. **Resuelto 2026-10-20 por el usuario: dejarlas quietas, son datos de prueba** (no se modifica ni se borra esa data).

## Regla observada

- No usar `migrate:fresh` / `db:wipe` / `TRUNCATE` ni reemplazar datos de BD sin autorización explícita (`database-safety.mdc`). Para la tabla vacía `purchase_orders` del intento fallido se pidió y se obtuvo autorización del usuario.

## Historial de correcciones

- `app/Providers/AppServiceProvider.php` restaurado desde HEAD conservando solo el `Relation::morphMap` aditivo de T4 (la sobrescritura previa había roto la navegación global con `Undefined variable $appNavigation`).
- `database/migrations/2026_10_19_145300_t4_migration_note.php` (no era una migración válida) movido a `docs/briefs/FEAT-047-t4-migration-note.php.txt`.
- Las vistas de proveedores **no** están revertidas: `providers/index.blade.php` conserva el rediseño (icon-only, panel plegable, toolbar, alto completo).
