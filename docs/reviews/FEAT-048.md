# Review Report — FEAT-048

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-048.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-048 |
| Fecha | 2026-10-20 |
| Alcance revisado | Solo el corte FEAT-048 (T1–T5), no el resto del working tree. Migraciones `2026_10_20` de comodato/activo, snapshots de `purchase_orders` y `supply_request_comments`. `PurchaseFlowStateService`, `PurchaseOrderEvaluationService`, `PurchaseOrderService`, exportadores PDF/Excel, controladores de procesamiento, evaluación, solicitud y reenvío de suministro. Form Requests de compra, evaluación, cerrar saldo y suministro. `resources/views/pdf/purchase-order.blade.php` y vistas de `purchase-requests` (alta, reenvío, detalle, pedidos, órdenes, evaluación, catálogos). `config/purchase-orders.php` y etiquetas de `boards` en `config/access.php`. `NavigationResolver`, `SidebarVisibilityService`, `PurchaseAccessService`, `SupplyAccessService`, `User::defaultSupplyBoardUrl`. Tests Feature del corte más `NavigationVisibilityTest::test_director_sees_only_compras_area_for_purchase_approval`. |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno. | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `tests/Feature/NavigationVisibilityTest.php` | El test del director falla, y **no lo causa FEAT-048**. En esta corrida las áreas fueron `comercial`, `compras` y `tic` (esperado: solo `compras`). TIC sale porque el rol `director` en `RoleAndPermissionSeeder` (igual que en HEAD) ya tiene `view.board.tic.solicitudes_desarrollo` y `devreq.tab.leader_approval`, y `shouldShowDevelopmentRequestBoard` / `DevelopmentRequestAccessService` no están en el diff de este corte. Comercial salió en esta ejecución porque `UserFactory` asigna `area_key` al azar y el tablero de documentos sigue el área asignada (`BoardAccessService`, tampoco tocado). El diff de nav de FEAT-048 solo reetiqueta Pedidos / Órdenes / Catálogos y cambia qué ruta queda activa. | No devolver el corte por este fallo. Corregirlo en un fix aparte: fijar `area_key` en el test y decidir si el director de compras debe seguir viendo Solicitudes de desarrollo. |
| 2 | `app/Http/Requests/PurchaseRequests/StorePurchaseRequestRequest.php`, `UpdatePurchaseRequestRequest.php` | El alta y el reenvío de la solicitud no usan `UppercasesValidatedText`. Razón social y textos de ítem (descripción, referencia, utilización, ubicación) se persisten como llegan. El criterio de mayúsculas del brief sí está cubierto en motivo de Cerrar saldo, observaciones de la OC, observaciones de la evaluación y el hilo (compra y suministro). | Al volver a tocar esos Form Requests, aplicar el trait y dejar fuera radios, fechas y archivos. |
| 3 | `app/Http/Requests/PurchaseRequests/ProcessPurchaseComprasRequest.php` | Quedó huérfano. Sigue aceptando `estado_compras` (incluido `rechazado`) y `comentarios_compras`, pero ningún controlador lo usa. El PATCH vivo ya no escribe estado. | Borrar el Form Request en un corte posterior para que nadie vuelva a cablear el rechazo manual. |
| 4 | `app/Http/Requests/PurchaseRequests/ProcessSupplyComprasRequest.php` | Sigue exigiendo `action` e `items` y acepta `purchasing_observations` sin el trait de mayúsculas. La vista de procesar suministro ya no envía ese formulario: el precio se captura en la OC y el estado lo escribe la máquina. | Alinear el Form Request con la vista o retirar la ruta PATCH si ya no tiene uso. |
| 5 | `resources/views/modules/purchase-requests/processing/partials/close-balance.blade.php` | Cerrar saldo es una sección del formulario (`pur-req-form`), no el modal opaco que nombra el brief. El chrome es el del módulo (no un modal transparente ni un prototipo suelto). | Dejarlo así, o pasarlo al modal GH si se quiere cumplir el brief al pie de la letra. |
| 6 | `resources/views/modules/purchase-requests/orders/evaluation.blade.php` | La pantalla usa `pur-req-form`, `module-tab` vía subnav, `<x-searchable-select>` en las calificaciones e icon-btn de volver. Hay dos `style` de margen. Los pesos y umbrales salen del config. | Quitar el estilo inline cuando se toque la vista. |
| 7 | `config/access.php` (`purchase_tabs`, `supply_tabs`) | Las claves de `boards` no se crearon ni se borraron: solo cambiaron las etiquetas `bandeja_compras` → Pedidos, `ordenes_compra` → Órdenes, `proveedores` → Catálogos. Los diccionarios de pestañas siguen con «Bandeja compras», «Insumos aprobados» y «Catalogo», que `visibleTabsFor` ya no devuelve. No son permisos nuevos. | Limpiar esas etiquetas muertas en documentación o en un fix menor, sin borrar claves de `boards`. |
| 8 | `app/Policies/SupplyRequestPolicy.php` | `comment` cierra el hilo si el suministro está `completada` y deja comentar a Compras, al dueño y a quien tiene `supply.tab.quality`. No reutiliza un `view()` acotado al área, así que Calidad puede comentar un suministro de otra área. La ruta sigue dentro de `auth`. | Si el criterio es el mismo que la compra, exigir también que la persona pueda ver esa solicitud en su área. |
| 9 | Run log | T1–T5 dejaron el smoke de browser pendiente (app local apagada). Esta revisión contrastó las Blade con el chrome del módulo y no abrió el navegador. | Smoke breve al documentar: alta Cliente vs interna, Pedidos sin selector de estado, Cerrar saldo, folio `SC-`/`SU-`, evaluación. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — cero claves nuevas en `config/access.php` (diff: solo las tres etiquetas). Evaluación en `purchase.tab:providers.edit` (`purchase.providers.edit`; edit implica view). Cerrar saldo y Pedidos/Órdenes con `purchase.tab.processing`.
- [x] Sin registro publico ni bypass de middleware — rutas nuevas dentro del grupo `auth` + `active` + `password.changed` de `routes/modules/purchase-requests.php` y `routes/modules/supplies.php`, a su vez requeridos desde el grupo autenticado de `routes/web.php`. No se tocó el registro público. La evaluación no está dentro del grupo `processing`.
- [x] Validacion de entradas (Form Requests) — Cerrar saldo exige motivo (mín. 3). Evaluación: fecha obligatoria; si hay una calificación, las tres; enteros 1–5. Observaciones de OC y de evaluación, motivo e hilo pasan por `UppercasesValidatedText` o `UppercaseFormText`. Ver observaciones 2 y 4 para textos que este corte no normalizó.
- [x] Sin duplicacion innecesaria — un solo `PurchaseFlowStateService` escribe Por comprar / Con orden / Cerrado. `sync` no corre al abrir Procesar.
- [x] Rutas en archivo de modulo/area correcto — `routes/modules/purchase-requests.php` y `routes/modules/supplies.php`. `routes/web.php` no se modificó para este corte.
- [x] Migraciones compatibles con hosting compartido — aditivas, nullable, sin default, sin `enum()`, sin `change()`, sin backfill. `down()` solo quita lo que esta feature agregó. `supply_request_comments` espeja el hilo de compra.
- [x] Export Excel usa `BaseExport` si aplica — el FO-AD-07 sigue en `PurchaseOrderExcelExporter` (formulario, como pide el brief). El texto legal sale de `config('purchase-orders.condiciones')`. `<x-export-excel>` se mantiene en el Excel del suministro.
- [x] Tests relevantes presentes o justificados — 33 tests del corte en verde (329 aserciones), reejecutados por el Revisor. El fallo de `NavigationVisibilityTest` es previo (observación 1).
- [x] UI nueva (si aplica): `module-ui-quality` — Pedidos (`req-manage-page`, pastillas `.module-tab`, filtros searchable-select, icon-btn). Evaluación y procesar sobre `pur-req-form`. Catálogos con subnav `.module-tab`. No es un prototipo crudo. Órdenes conserva el listado server-side ya existente.
- [x] UI: DataTables server-side si el listado puede crecer — Órdenes ya tiene endpoint datatable. Pedidos reutiliza la cola con tope 200; el brief pedía clonar ese listado, no migrarlo a DataTables en este corte.

## Seguridad

- Evaluación: middleware `purchase.tab:providers.edit` y `UpdatePurchaseOrderEvaluationRequest` exige OC `emitida` más `canEditProviders`. Quien solo tiene `purchase.tab.processing` recibe 403 (test en verde).
- Cerrar saldo: `purchase.tab.processing` y, en suministro, `puedeCerrarSaldo()`.
- Reenvío de suministro: policy `resubmit` (dueño, `rechazada_calidad`, `supply.tab.my_requests`) y el área de la URL. No borra `quality_observations`. Vuelve a `pendiente_calidad`.
- `estado_compras=rechazado`: `PurchaseFlowStateService::syncPurchase` retorna sin escribir. La cola de Pedidos no lo lista (`whereRaw('1 = 0')` si alguien pide ese filtro; el vacío solo incluye pendiente y en curso).
- Comentario nuevo del director va al hilo en mayúsculas. `comentarios_director` ya no se escribe en la columna (sigue en la metadata de auditoría como texto acotado).
- Hilo de suministro: ruta autenticada; `puedeComentar()` niega `completada`.
- Sin claves Spatie nuevas. No se borraron claves de `boards`.

## Consistencia con AGENTS.md y docs

Contraste con el brief (los 11 puntos pedidos):

| # | Criterio | Resultado |
| --- | --- | --- |
| 1 | PDF FO-AD-07: texto legal tal cual, una hoja, filas Proyecto nuevo / Reinversión / Comodato / Activo, firmas, sin evaluación | OK. `condiciones` coincide con el bloque del brief (espacios dobles, `SERÁ`, `RELACIÓN`, sin «corregir» RADICACION/NUMERO). Título **ORDEN DE COMPRA O SERVICIO**, periodo **Enero de 2025**. Firmas: Analista de Compras (`created_by`), Director(a) área solicitante (aprobador o revisor de Calidad), Director Admon. y Financiero y Gerente en blanco. El HTML no menciona evaluación. Test de 1 y de 12 líneas en una carta, en verde. |
| 2 | Comodato/Activo nullable, sin default. Interna y suministro no imprimen Sí | OK. Migraciones nullable sin default. Interna persiste `null` aunque se posteen radios. Suministro y OC sin snapshot dejan la celda vacía (`imprimirSiNo` solo pinta Sí/No si el snapshot es `SI`/`NO`). |
| 3 | Estados los escribe `PurchaseFlowStateService`. Abrir procesar no mueve el suministro. Cerrado en T5 exige saldo 0 y `fecha_recibido` en todas las OC. Cerrar saldo cierra sin fechas y exige motivo | OK. GET de procesar no llama `sync`. `allOrdersHaveReceiptDate` deja Con orden si falta una fecha. `closeBalance` exige motivo, lo guarda en el hilo y pone `completado` / `completada`. Un cerrado no se degrada. |
| 4 | `estado_compras=rechazado` no se reescribe | OK. |
| 5 | Cero permisos nuevos. Evaluación exige `purchase.providers.edit` | OK. |
| 6 | `unit_cost` ya no se pide para cerrar; no se borra el histórico | OK. Procesar suministro no muestra ni escribe `unit_cost`. `supplyLines()` precarga `valor_unidad` desde el costo viejo. |
| 7 | Menú: Pedidos / Órdenes / Catálogos sin borrar claves de boards | OK. `PurchaseAccessService::visibleTabsFor` ya no devuelve Bandeja. `SupplyAccessService::visibleTabsFor` ya no devuelve Insumos aprobados ni catálogo. `supplies.approved.index` redirige a Por aprobar. Catálogos: Proveedores con `purchase.providers.view` (edit implica view) o Insumos con `supply.tab.catalog`. |
| 8 | Folio visible `SC-`/`SU-` sin cambiar el número guardado. OC = `id` | OK. `folioVisible()` es accessor. `numeroOc` es el `id`. |
| 9 | Mayúsculas en motivo y observaciones. El texto legal del config no fue corregido | OK en motivo, observaciones de OC, observaciones de evaluación e hilo. El config copia el bloque legal. Hueco preexistente en los textos libres del alta de la solicitud (observación 2). |
| 10 | Auth en rutas nuevas, dentro del grupo auth. Sin registro público | OK. |
| 11 | UI de evaluación y pedidos con chrome del módulo | OK. Ver observaciones 5 y 6. |

- Dashboard: el KPI de bandeja es Por comprar + Con orden (`bandeja_total`). Los cerrados no entran en ese conteo. Los enlaces llevan `vista` de Pedidos.
- Después de Cerrado, `PurchaseOrderService` rechaza otra OC.
- Excel del FO-AD-07 no se migró a `BaseExport`, como pide el brief.
- Docs de módulo y de usuario quedan para el Documentador (paso siguiente del plan).

## Verificacion

- `php artisan test --compact` de `PurchaseOrderFoAd07Test`, `PurchaseFlowStateTest`, `PurchaseSupplyPriceTest`, `PurchaseMenuFolioTest` y `PurchaseOrderEvaluationTest`: **33 passed, 329 assertions**.
- `NavigationVisibilityTest` filtrado al director: **1 failed**. Causa previa a FEAT-048 (observación 1). No es bloqueante de este corte.
- No se ejecutó `migrate:fresh` ni wipe.

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)
