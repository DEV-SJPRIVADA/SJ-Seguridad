# Feature Brief — FEAT-048

> Brief final del Arquitecto (2026-10-20). Consolida el Analista cerrado ([`FEAT-048-analyst.md`](FEAT-048-analyst.md)) y lo ya entregado en [`FEAT-047.md`](FEAT-047.md). **No es borrador.** No reabre decisiones.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-048 |
| Modulo / area | Compras / `purchase-requests` + Suministros (`supplies`) |
| Titulo | Compras: FO-AD-07 oficial, estados automáticos y menú en tres tableros |
| Solicitante | Usuario (plan aprobado 2026-10-20; pidió implementar) |
| Fecha | 2026-10-20 |
| Analista | [`docs/briefs/FEAT-048-analyst.md`](FEAT-048-analyst.md) (CERRADO) |
| Antecedente | [`docs/briefs/FEAT-047.md`](FEAT-047.md) — proveedores, VINE01, OC, saldo por línea, `purchase_order_evaluations` |
| Doc módulo | [`docs/modules/purchase-requests.md`](../modules/purchase-requests.md), [`docs/modules/suministros.md`](../modules/suministros.md) |

## Objetivo

Que Compras emita la orden en el papel oficial FO-AD-07 (una hoja carta, con las condiciones legales tal cual), que el estado del pedido lo escriba una sola máquina según lo que ya ocurrió, y que el trabajo diario viva en tres tableros ya existentes, solo con otro nombre y otra visibilidad: Pedidos, Órdenes y Catálogos. Las dos puertas de pedido, la autorización del director o de Calidad, el catálogo de proveedores, VINE01 y el reparto de una solicitud entre varios proveedores con saldo por línea se quedan como los dejó FEAT-047.

## Decisiones tecnicas del Arquitecto

Los supuestos S1–S9 del Analista quedan **aprobados**. No son preguntas. El plan ya fue aprobado por el usuario.

| # | Decisión | Efecto en el diseño |
| --- | --- | --- |
| S1 | Compra **interna**: no se muestran Proyecto nuevo, Asume el cliente, Reinversión, Comodato ni Activo, y no se guardan en verdadero. Quedan `null`, igual que hoy `asume_cliente`. Al emitir, el snapshot queda `null` y el PDF deja la celda **vacía**. «Sí» o «No» solo si el snapshot es `SI`/`NO` (solicitud Cliente). | Columnas boolean **nullable, sin default en BD**. Un default `false` rellenaría filas viejas y haría imprimir «No». |
| S2 | `estado_compras=rechazado` no se reescribe, no se renombra y **no** entra a Por comprar, Con orden ni Historial. Sigue en Mis solicitudes. Compras ya no rechaza. | `PurchaseFlowStateService` no toca esas filas. La cola las excluye. |
| S3 | El suministro gana un hilo aditivo, del mismo papel que `purchase_request_comments`. El motivo de Cerrar saldo va ahí. `observations` y `quality_observations` se siguen mostrando. El rechazo de Calidad puede seguir en su comentario actual. En compras, el motivo nuevo del director va al hilo: `comentarios_director` deja de capturarse. | Tabla nueva `supply_request_comments`. Sin backfill. |
| S4 | `fecha_recibido` es la columna ya creada en `purchase_order_evaluations`. La guarda quien tiene `purchase.providers.edit`, en la pantalla de evaluación. Se puede guardar la fecha sin las tres calificaciones. El cierre automático mira fecha + saldo en cero, no el puntaje. | Rutas de evaluación fuera del grupo `processing`. Quien solo tiene `purchase.tab.processing` no marca recibido. |
| S5 | **Cerrar saldo** lo usa `purchase.tab.processing`, en Por comprar o Con orden. Motivo y paso a Cerrado en la misma acción. Después de Cerrado no se emiten más OC. El botón cierra aunque falten fechas (también después del corte 5). | `completado` / `completada` es terminal: el servicio no degrada. |
| S6 | El tablero **Dashboard** se conserva. Desde el corte 2, «en bandeja» = Por comprar + Con orden. Los cerrados no inflan ese conteo. | `ComprasDashboardService` y `ComprasQueueService` en T2. T4 solo reetiqueta el destino de los enlaces. |
| S7 | Menú sin claves nuevas. Pedidos y Órdenes se ven con `purchase.tab.processing`. Catálogos: pestaña Proveedores con `purchase.providers.view`; pestaña Insumos con `supply.tab.catalog`. El tablero suelto Bandeja y el tablero suelto Proveedores dejan de llamarse así: su clave se reetiqueta. La pestaña Bandeja dentro de Solicitudes de compra deja de ser un segundo escritorio. Calidad solo muestra Por aprobar. | Ver § Menú. Prohibido añadir claves a `boards` o a `system_permissions`. |
| S8 | Reimprimir una OC ya emitida usa el layout nuevo. No se rellenan snapshots que no existían: esas celdas salen vacías. El `id` de la OC no cambia. | Sin backfill de `purchase_orders`. |
| S9 | Folio visible = `SC-` o `SU-` + `PurchaseRequest::folio()` / `SupplyRequest::folio()` (ya rellenan a 4). Por encima de 9999 no se recorta (`SC-10000`). El correlativo guardado no se modifica. | Accessor de presentación. Sin columna nueva. |

Otras decisiones de este brief:

| Tema | Decisión | Justificación |
| --- | --- | --- |
| Máquina | Un solo `PurchaseFlowStateService` | Aprobar, rechazar, reenviar, emitir, cerrar saldo y (en T5) registrar la fecha tienen que escribir el mismo estado. Repartir la regla en controladores la duplica. |
| Repository | No | Convención del proyecto. Eloquent + servicios que ya existen. |
| Dependencias | Ninguna nueva | DomPDF y PhpSpreadsheet ya están. |
| Permisos | Cero claves nuevas en `config/access.php` | Ley del ciclo. `PermissionCatalog` fabrica `view.board.{área}.{clave}` por cada clave de `boards`, y `sync()` borra las que sobran. Añadir `pedidos` o `catalogos`, o borrar `bandeja_compras`, crearía o destruiría permisos Spatie. |
| Menú | Reetiquetar boards que ya existen y cambiar quién los ve | Pedidos = clave `bandeja_compras`. Órdenes = clave `ordenes_compra`. Catálogos = clave `proveedores`. |
| Excel FO-AD-07 | Extender `PurchaseOrderExcelExporter` | Es un formulario, no un listado. No pasarlo a `BaseExport` ni a `excelHtml5`. `BaseExport` y `<x-export-excel>` siguen para catálogos. |
| PDF | No es una pantalla web | Se prueba en PHPUnit (texto + una página). El smoke de browser cubre el formulario y los tableros. |
| Cerrados | No se reescriben | El servicio no degrada `completado` / `completada`. La regla nueva de T5 solo gobierna la **entrada** a Cerrado. |
| Migraciones | Solo aditivas, sqlite + MySQL | Sin `enum()`, sin `change()`, sin `migrate:fresh`, sin backfill, sin TRUNCATE. |
| Auditoría | Wrapper ya usado en OC (`SystemAuditService`) | Cerrar saldo y evaluar registran evento. Sin tabla de audit nueva. |
| Mayúsculas | Hilo y motivo de Cerrar saldo vía Form Request + `UppercasesValidatedText` | Los radios y `SI`/`NO` son códigos. El texto legal del config **no** pasa por el helper: hay que copiarlo tal cual. |

### Menú (corte 4) — visibilidad, no claves nuevas

Hoy el tablero suelto `bandeja_compras` se oculta cuando la persona ya entra a Solicitudes de compra (`SidebarVisibilityService::shouldShowBandejaComprasBoard` + `bandejaAccessibleViaPurchaseBoard`), y la bandeja vive como pestaña. El corte 4 invierte eso.

| Clave que ya existe | Etiqueta nueva (`config/access.php` → `boards`) | Quién lo ve | Qué muestra |
| --- | --- | --- | --- |
| `bandeja_compras` | **Pedidos** | `purchase.tab.processing` | Tres vistas del listado que hoy es `purchase-requests.processing.index`: Por comprar, Con orden, Historial |
| `ordenes_compra` | **Órdenes** | `purchase.tab.processing` (`canViewOrdersBoard`, ya así) | Listado FO-AD-07 que ya existe |
| `proveedores` | **Catálogos** | El tablero si `purchase.providers.view` **o** `supply.tab.catalog`. Pestaña Proveedores solo con view (edit implica view). Pestaña Insumos solo con `supply.tab.catalog` | Rutas actuales de proveedores y de `supplies.products.*` |
| `solicitudes_compra` | Solicitudes de compra (igual) | Igual que hoy | Nueva, Mis solicitudes, Pendientes del director. **Sin** pestaña Bandeja |
| `suministros` | Suministros (igual) | Igual que hoy | Pedir insumos y, en Calidad, solo Por aprobar |
| `dashboard` | Dashboard (igual) | `view.board.compras.dashboard` (igual) | Conteos de bandeja alineados en T2 |

No se añaden claves a `boards`, a `system_permissions` ni a las listas de `admin_ui`. No se borran claves de `boards` (un `app:sync-permissions` las trataría como huérfanas). `view.board.compras.bandeja_compras` sigue existiendo; el rótulo del sidebar sale del valor de `boards`, no de una clave nueva.

`PurchaseAccessService::visibleTabsFor` deja de devolver `bandeja_compras`. `SupplyAccessService::visibleTabsFor` deja de devolver `insumos_aprobados` y `catalogo` (el catálogo pasa al subnav de Catálogos). `supplies.approved.index` redirige a Por aprobar para que la URL vieja no sea otro escritorio. Las rutas de export de ese listado dejan de tener entrada de menú.

El tablero Suministros sigue apuntando a la primera pestaña que quede (Mis solicitudes o Por aprobar). Quien solo administra el catálogo entra por Catálogos, no por un Suministros vacío de catálogo.

Estado activo del nav: `purchase-requests.processing.*` marca Pedidos, no Solicitudes de compra. `supplies.products.*` marca Catálogos, no Suministros.

### Máquina — `PurchaseFlowStateService`

Clase: `App\Services\PurchaseRequests\PurchaseFlowStateService`.

Métodos: `sync(PurchaseRequest|SupplyRequest $source): void` y `closeBalance(..., User $actor, string $motivo): void`.

`sync` se llama **después** de aprobar, rechazar, reenviar, emitir una OC y, en T5, guardar `fecha_recibido`. **No** se llama al abrir Procesar (GET). No renombra valores guardados.

Traducción. La etiqueta es de pantalla. La columna no se renombra.

| Etiqueta | Compra (columnas actuales) | Suministro |
| --- | --- | --- |
| Pendiente de autorización | `estado=pendiente` | `pendiente_calidad` |
| Devuelta | `estado=rechazado` | `rechazada_calidad` |
| Por comprar | `estado=aprobado`, `estado_compras=pendiente`, sin cantidades en OC | `aprobada_calidad`, sin cantidades en OC |
| Con orden | `estado_compras=en_curso` | `en_compras` |
| Cerrado | `estado_compras=completado` | `completada` |

Saldo por línea: el que ya calcula `PurchaseOrderService::sourceLines()` (`cantidad_solicitada − cantidad_ordenada`, tope en la pendiente). No se puede ordenar más que el pendiente. Esa regla no se reescribe.

Predicado de `sync` (solo si la compra está `aprobado` y `estado_compras` no es `rechazado`; si es `rechazado`, return):

1. Si ya está `completado` / `completada`, no se toca (terminal, S5 y «no reescribir cerrados»).
2. Sin cantidades en ninguna OC → Por comprar (`pendiente` / `aprobada_calidad`).
3. Hay OC y queda saldo → Con orden.
4. **Hasta T4 (lo que implementa T2):** saldo de todas las líneas en cero y hay al menos una OC → Cerrado.
5. **Desde T5**, el paso 4 cambia dentro del mismo método: Cerrado solo si el saldo es cero **y** cada OC de ese origen tiene `fecha_recibido`. Si el saldo ya es cero y falta una fecha, queda Con orden. No se adelanta esta regla en T2.

`closeBalance` (excepción, T2 en adelante, también después de T5): exige motivo no vacío, lo guarda en el hilo (mayúsculas) y pone Cerrado aunque quede saldo o falte una fecha. Luego `PurchaseOrderService` rechaza emitir otra OC sobre un origen Cerrado.

Abrir Procesar: quitar el bloque de `PurchaseProcessingController` que, al abrir un suministro `aprobada_calidad`, lo pasa a `en_compras`.

Quitar el selector `estado_compras` y el campo `comentarios_compras` del formulario de procesar compra. El PATCH deja de persistir estado. Compras no gana una acción de rechazar. `comentarios_director` y `comentarios_compras` se siguen **mostrando** si el pedido viejo los tiene, y dejan de escribirse. El rechazo nuevo del director (pantalla y enlace firmado) agrega una fila al hilo de la compra.

Pendiente de autorización y Devuelta no entran al tablero Pedidos. Siguen en Mis solicitudes y en la cola de quien autoriza.

## Alcance

### Incluye

Cinco cortes verticales, en este orden, un agente a la vez. No se paralelizan. Cada corte lleva front y back juntos.

**T1 — Formulario Comodato/Activo + snapshot + FO-AD-07**

- Radios Comodato y Activo (Sí/No, default No) en alta, reenvío y detalle, solo si `solicitud_para=Cliente`, en la fila: Proyecto nuevo, Asume el cliente, Reinversión, Comodato, Activo.
- Columnas nullable `comodato` y `activo` en `purchase_requests`. Interna: no se muestran y se persisten `null`.
- Al emitir, snapshot `string` nullable `SI`/`NO` de `proyecto_nuevo`, `reinversion`, `comodato` y `activo`, junto al `asume_cliente` que ya existe. Suministro: los cinco quedan `null`.
- PDF y Excel al orden visual y al texto legal de este brief. Una hoja carta con 1 línea y con unas 12.
- Sin backfill de OC ya emitidas.

**T2 — Estados**

- `PurchaseFlowStateService` con la máquina de arriba, predicado de cierre «saldo cero».
- Sin selector manual. Abrir Procesar no mueve el estado.
- Reenvío del suministro `rechazada_calidad` por el solicitante, igual que la compra devuelta: vuelve a `pendiente_calidad`.
- Botón Cerrar saldo con motivo obligatorio en el hilo.
- Hilo de la compra: deja de capturarse `comentarios_director` y `comentarios_compras`.
- Tabla `supply_request_comments` para notas nuevas y para el motivo.
- Conteos de bandeja del dashboard = Por comprar + Con orden (S6).

**T3 — Un solo precio**

- El precio se captura solo al emitir la OC (`valor_unidad` e IVA importe).
- Completar o procesar un suministro ya no pide ni guarda `unit_cost`.
- Si el ítem ya tenía `unit_cost`, `PurchaseOrderService::supplyLines()` sigue precargando `valor_unidad`. No se borran costos históricos.

**T4 — Menú y folios**

- Tres tableros con las etiquetas de la tabla de menú. Vistas Por comprar, Con orden e Historial en Pedidos.
- Folio visible `SC-` / `SU-`. Número de OC = `id`.
- Ocultar la pestaña Bandeja dentro de Solicitudes de compra y la pestaña Insumos aprobados.
- Dashboard se conserva. Sus enlaces de bandeja apuntan a las vistas de Pedidos, no a un segundo escritorio.

**T5 — Recepción y evaluación**

- UI de la evaluación ya definida en FEAT-047 (pesos 25/25/50, Calif. 1–5, umbrales del config, una por OC, editable).
- Fecha de recibido en esa pantalla. Se puede guardar sola.
- A partir de este corte, `sync` solo entra a Cerrado si todas las OC tienen fecha y el saldo es 0. Cerrar saldo sigue siendo la excepción.
- La evaluación no se imprime.

### Fuera de alcance

- Requisiciones de personal.
- CRM de clientes.
- Imprimir la evaluación en el FO-AD-07.
- Permisos nuevos, claves nuevas de tablero, migración automática de grants Spatie.
- Reescribir pedidos cerrados, renombrar valores de estado, backfill de snapshots o de `comodato`/`activo`.
- `migrate:fresh`, `migrate:refresh`, `db:wipe`, TRUNCATE, DROP de tablas existentes.
- Rehacer proveedores, VINE01, saldo por línea o la tabla `purchase_order_evaluations`.
- Sustituir el FO-AD-44.
- Patrón Repository y dependencias nuevas.

## Reglas de negocio

1. Dos puertas: Pedir insumos (catálogo + Calidad) y Pedir una compra (director). Autorización en plataforma y por correo firmado. El ajuste de cantidades de Calidad se conserva.
2. FO-AD-44 sigue siendo el pedido. FO-AD-07 es la orden.
3. Bandeja del tablero Pedidos = Por comprar + Con orden. Historial = Cerrado (`completado` / `completada`). `estado_compras=rechazado` no está en ninguna de las tres vistas.
4. Hasta el corte 5, la OC que deja el saldo en cero cierra. Desde el corte 5 hace falta además la fecha de recibido en **todas** las OC; si falta una, sigue Con orden. Cerrar saldo cierra igual, con motivo en el hilo.
5. Después de Cerrado no hay otra OC.
6. El precio vive en la OC. `valor_total` de línea = cantidad × valor unidad + IVA (importe), como FEAT-047.
7. Un hilo para notas nuevas. Textos viejos (`comentarios_director`, `comentarios_compras`, `observations`, `quality_observations`) solo se leen.
8. Folio de pantalla `SC-` / `SU-` sin cambiar el número guardado. OC No. = `purchase_orders.id`.

### Orden visual FO-AD-07

Papel carta, una página. Logo `public/images/logoSj.png`.

1. Logo | título **ORDEN DE COMPRA O SERVICIO** | recuadro **FO-AD-07** / **Enero de 2025** / **Versión 04** / **Página 1 de 1**.
2. Fecha `dd/mm/aaaa` (`purchase_orders.fecha`) y **OC No.** (`id`).
3. Ciudad = `purchase_orders.ciudad` (ciudad de entrega), no la ciudad del proveedor.
4. Asociado de negocio, desde el snapshot: Razón social `provider_nombre`, NIT `provider_identificacion`, Contacto, Teléfono, Correo, Dirección, Ciudad del proveedor, Forma de pago. Si la forma de pago está vacía y hay `dias_pago`, imprimir `A {n} DÍAS`. Asume el cliente: `SI`/`NO` si hay snapshot; si no, celda vacía.
5. Debajo, **siempre**, Proyecto nuevo, Reinversión, Comodato, Activo. Imprimir Sí o No solo cuando el snapshot es `SI`/`NO`. Suministro y OC emitidas antes del cambio: celdas vacías.
6. Tabla **Sírvase suministrar los servicios y/o artículos descritos a continuación:** Cantidad, Artículo, Descripción, Valor unidad, IVA (importe, no %), Valor total. Fila **TOTAL** dentro de la tabla. Artículo = el campo `articulo` que ya se llena con la referencia del origen.
7. **OBSERVACIONES** siempre (`purchase_orders.observaciones`; vacío si no hay texto).
8. Firmas, con espacio para firma manuscrita:
   - Elaborado por: **Analista de Compras** = nombre de `created_by`.
   - Revisado y aprobado por: **Director(a) área solicitante** = `aprobador` de la compra; en suministro, `quality_reviewer_id`. Si no hay persona, la línea queda en blanco con el cargo.
   - Aprobado 2 por: **Director Admon. y Financiero** en blanco; **Gerente (cuando aplique)** en blanco.
9. Debajo, **siempre**, el texto de condiciones, letra ~6px. Si hay muchas líneas, reducir la letra de la tabla antes de pasar a página 2. Meta de prueba: 1 línea y ~12 líneas, ambas en una página.

El periodo visible es **Enero de 2025** (con «de»). El título visible es **ORDEN DE COMPRA O SERVICIO**. Hoy el config dice `Enero 2025` y `ORDEN DE COMPRA`: T1 los alinea. No corregir la ortografía del texto legal.

### Texto legal exacto

Guardarlo tal cual en `config/purchase-orders.php` (clave `condiciones`). También en el Excel. No pasar este string por `UppercaseFormText`.

```
CONDICIONES GENERALES: AL ACEPTAR ESTA ORDEN DE COMPRA EL ASOCIADO DE NEGOCIO SE COMPROMETE A LAS SIGUIENTES CONDICIONES:

LA FACTURA O CUENTA DE COBRO QUE GENERE ESTA ORDEN  SERA CANCELADA PREVIA PRESENTACIÓN DE LA MISMA, A PARTIR DE SU FECHA DE RADICACION, EN EL PLAZO ACORDADO POR LAS PARTES, SIN QUE SE PRODUZCA INTERES ALGUNO.

EL ASOCIADO DE NEGOCIO DEBE RADICAR FACTURA O CUENTA DE COBRO EN ORIGINAL Y COPIA, RELACIONANDO EL NUMERO DE LA ORDEN  Y ANEXAR COPIA DE LA REMISION O ACTA DE ENTREGA CON SELLO DE RECIBIDO DEL ALMACEN;  ESTA FACTURA O CUENTA DE COBRO SOLO SERÁ ACEPTADA CUANDO EL SERVICIO CORRESPONDA A LA ORDEN EMITIDA.

PARA EFECTOS CONTABLES  SOLO SE RADICARAN FACTURAS HASTA EL DIA VEINTICINCO(25) DEL MES EN CURSO, POR LO TANTO LAS FACTURAS O CUENTAS DE COBRO QUE SE GENEREN A PARTIR DE ESA FECHA DEBERAN RADICARSE CON FECHA DEL MES SIGUIENTE.

EL ASOCIADO DE NEGOCIO SE COMPROMETE A REALIZAR LA REPARACION O PRESTACION DEL SERVICIO EN LOS TERMINOS QUE ESTIPULA ESTA ORDEN, HACIENDOSE RESPONSABLE POR LOS DAÑOS O PERDIDAS QUE SE CAUSAREN AL BIEN O BIENES PROPIEDAD DE ESTA EMPRESA EN EL DESARROLLO DE LA LABOR SOLICITADA.

LA PRESENTE ORDEN NO CONSTITUYE DEPENDENCIA LABORAL ALGUNA.

ESTA ORDEN DE COMPRA ES CONCORDANTE CON LO ESTIPULADO EN LA COTIZACION POR PARTE DEL ASOCIADO DE NEGOCIO.

VER AL RESPALDO LAS CONDICIONES LEGALES DE LA RELACIÓN COMERCIAL
```

### Evaluación (corte 5, no se imprime)

Pesos y umbrales ya están en `config/purchase-orders.php` (`evaluation.weights`, `evaluation.thresholds`). No hardcodear los números en Blade.

- Una fila por OC (`purchase_order_id` único, ya creado). `updateOrCreate`.
- Calif. entera 1–5. Si se envía una calificación, las tres son obligatorias. La fecha puede guardarse sola, con las tres calificaciones en null.
- Resultado del criterio = Calif × (peso/100). Puntaje % = (suma de resultados / 5) × 100.
- Umbral: ≥ 80 `satisfactoria`, ≥ 60 `seguimiento`, si no `insatisfactoria`.
- Comprobación: 5/5/5 → 100 satisfactoria; 4/4/4 → 80 satisfactoria; 3/3/3 → 60 seguimiento; 1/1/1 → 20 insatisfactoria.
- El cierre automático no mira el puntaje.

## Permisos (`config/access.php`)

Sin claves nuevas. Sin filas nuevas en `system_permissions` ni en `admin_ui`.

| Permiso (ya existe) | Uso en FEAT-048 |
| --- | --- |
| `purchase.tab.create` / `purchase.tab.my_requests` | Pedir una compra, ver las propias, reenviar la devuelta |
| `purchase.tab.approval` | Director autoriza o devuelve (plataforma y correo) |
| `purchase.tab.processing` | Pedidos, emitir OC, Órdenes, Cerrar saldo |
| `supply.tab.my_requests` | Pedir insumos y reenviar el rechazado por Calidad |
| `supply.tab.quality` | Solo Por aprobar. Ya no abre Insumos aprobados |
| `supply.tab.catalog` | Pestaña Insumos dentro de Catálogos |
| `purchase.providers.view` | Pestaña Proveedores (edit implica view en `PurchaseAccessService`) |
| `purchase.providers.edit` | Evaluación y fecha de recibido |
| `view.board.compras.dashboard` | El dashboard se conserva |
| `view.board.compras.bandeja_compras` | Sigue la clave. El sidebar dice Pedidos por el cambio de etiqueta, no por una clave nueva |

T4, y solo T4, puede cambiar **etiquetas** de claves ya presentes en `boards`:

- `bandeja_compras` → `Pedidos`
- `ordenes_compra` → `Órdenes`
- `proveedores` → `Catálogos`

La visibilidad se cambia en `SidebarVisibilityService`, `NavigationResolver`, `PurchaseAccessService` y `SupplyAccessService`, no con permisos nuevos.

## Rutas

Archivos actuales, dentro del grupo `auth` + `active` + `password.changed` que ya los envuelve. No tocar `routes/web.php`. No publicar rutas fuera de auth.

| Método | URI | Nombre | Archivo | Auth | Corte |
| --- | --- | --- | --- | --- | --- |
| GET/POST | las de alta y reenvío de compra, ya existentes | `purchase-requests.create` / `.store` / `.edit` / `.update` | `routes/modules/purchase-requests.php` | `purchase.tab:create` o `my_requests` | T1 (campos) |
| GET | PDF y Excel de OC, ya existentes | `purchase-requests.orders.pdf` / `.excel` | el mismo | `purchase.tab:processing` | T1 (layout) |
| POST | `/bandeja-compras/solicitud/{purchase_request}/cerrar-saldo` | `purchase-requests.processing.purchase.close` | el mismo | `purchase.tab:processing` | T2 |
| POST | `/bandeja-compras/suministro/{supply_request}/cerrar-saldo` | `purchase-requests.processing.supply.close` | el mismo | `purchase.tab:processing` | T2 |
| GET | `/mis-solicitudes/{supply_request}/editar` | `supplies.edit` | `routes/modules/supplies.php` | `supply.tab:my_requests` | T2 |
| PATCH | `/mis-solicitudes/{supply_request}` | `supplies.update` | el mismo | `supply.tab:my_requests` | T2 |
| POST | `/solicitud/{supply_request}/comentarios` | `supplies.comments.store` | el mismo | policy `comment` (grupo auth, como el hilo de compra) | T2 |
| GET | `purchase-requests.processing.index` (ya existe) | igual | purchase-requests | `processing` | T4 lo convierte en Pedidos (query `vista=por_comprar\|con_orden\|historial`) |
| GET | `supplies.approved.index` (ya existe) | igual | supplies | `supply.tab:quality` | T4 redirige a `supplies.approval.index` |
| GET | `/ordenes-compra/{purchase_order}/evaluacion` | `purchase-requests.orders.evaluation.edit` | purchase-requests | `purchase.tab:providers.edit` | T5 |
| PUT | `/ordenes-compra/{purchase_order}/evaluacion` | `purchase-requests.orders.evaluation.update` | purchase-requests | `purchase.tab:providers.edit` | T5 |

El prefijo sigue siendo `purchase-requests/{module}` y `supplies/{module}`. La evaluación **no** va dentro del grupo `purchase.tab:processing`.

Reenvío de suministro: solo el solicitante dueño, solo si `status=rechazada_calidad`. Pasa a `pendiente_calidad`. No borra `quality_observations`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `purchase_requests.comodato` | alter aditivo | `boolean` nullable, **sin** default. Cliente persiste `true`/`false` desde el radio. Interna persiste `null` |
| `purchase_requests.activo` | alter aditivo | Igual que `comodato` |
| `purchase_orders.proyecto_nuevo` | alter aditivo | `string(20)` nullable, igual que `asume_cliente`. Valores `SI`/`NO` o `null` |
| `purchase_orders.reinversion` | alter aditivo | Igual |
| `purchase_orders.comodato` | alter aditivo | Igual |
| `purchase_orders.activo` | alter aditivo | Igual |
| `supply_request_comments` | tabla nueva | Espejo de `purchase_request_comments`: `supply_request_id` FK cascade, `user_id` FK cascade, `body` text, timestamps, índice `(supply_request_id, created_at)` |
| `purchase_order_evaluations` | ya existe | T5 solo le pone UI. `fecha_recibido` ya está. No alterar el esquema |
| Estados | sin alter | No renombrar `pendiente`, `rechazado`, `aprobado`, `en_curso`, `completado` ni los status de suministro |

Multi-driver (sqlite de PHPUnit y MySQL). Sin `enum()`, sin `change()`, sin `after()` obligatorio. Sin backfill: las filas viejas quedan con las columnas nuevas en `null`. `down()` solo elimina lo que esta feature agregó.

En el detalle de una solicitud Cliente ya guardada, `null` en Comodato o Activo se muestra como No (default del formulario) y, si se reenvía, se persiste `false`. Eso no rellena el snapshot de una OC ya emitida.

## Capas a implementar

- [ ] Migraciones aditivas (T1 columnas; T2 tabla de hilo)
- [ ] Modelos: fillable/casts en `PurchaseRequest` y `PurchaseOrder`; `SupplyRequestComment`; relación `comments()` en `SupplyRequest`; `folioVisible()` en compra y suministro (T4)
- [ ] `PurchaseFlowStateService` (T2; el predicado de fecha se ajusta en T5, misma clase)
- [ ] `PurchaseOrderService`: snapshot de los cuatro flags; rechazar OC si el origen ya está Cerrado
- [ ] `PurchaseApprovalService` y el controller del correo: el motivo nuevo va al hilo
- [ ] `PurchaseProcessingController`: quitar el cambio de estado al abrir y el PATCH de estado
- [ ] Reenvío de suministro (controller + servicio, espejo de `PurchaseRequestResubmitService`)
- [ ] Form Requests: radios Cliente; Cerrar saldo; comentario de suministro; evaluación. Trait `UppercasesValidatedText` en textos libres
- [ ] Policy `comment` en `SupplyRequestPolicy` (mismo criterio que la compra: quien puede ver, y no si ya está `completada`)
- [ ] `PurchaseOrderPdfExporter` + `resources/views/pdf/purchase-order.blade.php`
- [ ] `PurchaseOrderExcelExporter` (mismo contenido, incluidas las condiciones)
- [ ] `config/purchase-orders.php`: título, periodo y `condiciones`
- [ ] Vistas: create, edit, show de compra; procesar compra y suministro; Pedidos; subnav Catálogos; evaluación
- [ ] Nav: `NavigationResolver`, `SidebarVisibilityService`, `PurchaseAccessService`, `SupplyAccessService`, mapa de pestañas en `User` (`defaultSupplyBoardUrl`)
- [ ] `ComprasQueueService`, `ComprasQueueFilterBag`, `ComprasDashboardService` (T2 conteos; T4 vistas y folios)
- [ ] Tests Feature por corte
- [ ] Docs de módulo y de usuario en la misma entrega del agente Feature (no en este brief)

## Componentes reutilizables

- Radios Sí/No: el mismo patrón de Proyecto nuevo / Asume el cliente en `create.blade.php`. No hace falta `<x-searchable-select>` para un Sí/No. Los selects de verdad siguen siendo searchable-select.
- Hilo de compra ya existente (`StorePurchaseRequestCommentRequest`, `PurchaseRequestComment`). El de suministro lo copia.
- `PurchaseOrderService::sourceLines()` para saldo y para precargar `valor_unidad` desde `unit_cost`.
- `PurchaseOrderPdfExporter` y `PurchaseOrderExcelExporter`. No `excelHtml5`. No migrar este formulario a `BaseExport`.
- `<x-export-excel>` y `BaseExport` se quedan donde ya están (catálogo de proveedores y listados).
- Chrome: `pur-req-form`, `req-manage-page`, `req-manage-filters`, `.module-tab`.
- Iconos: `.req-manage-filters__icon-btn` en chrome; `.cursos-catalogo-page__icon-btn` en la fila. No inventar `{modulo}__icon-btn`.
- Modal del motivo de Cerrar saldo: card opaca de los módulos GH, no un modal transparente.
- DomPDF (carta) y PhpSpreadsheet, ya en el proyecto.
- Pesos y umbrales: `config('purchase-orders.evaluation')`.

## Vista referencia UI

| Pantalla | Clonar de |
| --- | --- |
| Alta, reenvío y detalle (fila Comodato/Activo) | `resources/views/modules/purchase-requests/create.blade.php` (`pur-req-form`) |
| Procesar (sin selector de estado; botón Cerrar saldo) | `processing/process-purchase.blade.php` y `process-supply.blade.php` |
| Pedidos (tres vistas) | `processing/index.blade.php` (`req-manage-*`, pastillas, icon-btn) |
| Órdenes | Listado de OC ya existente |
| Catálogos | Listado de proveedores (pestaña Proveedores) y catálogo de insumos (pestaña Insumos), con subnav `.module-tab` |
| Evaluación | Layout `pur-req-form` sobre el detalle de la OC. No es un tablero nuevo |
| PDF | No es pantalla. No diseñar una página web que imite el papel |

Primera entrega visual de cada corte con el chrome de Solicitudes de compra. Si T1 o T4 tocan `resources/css/app.css`, `npm run build` antes de cerrar el corte.

## Documentacion a actualizar

La actualiza el agente Feature en la misma entrega. Este brief no edita `docs/modules` ni `docs/user`.

- [ ] `docs/modules/purchase-requests.md` (FO-AD-07 oficial, máquina, Pedidos, evaluación)
- [ ] `docs/modules/suministros.md` (reenvío, hilo, sin Insumos aprobados en el menú, catálogo dentro de Catálogos)
- [ ] `docs/user/purchase-requests.md` (bandeja, orden, cerrar saldo, recepción)
- [ ] `docs/user/suministros.md` (reenviar un rechazo de Calidad)
- [ ] `docs/ACCESS_CONTROL.md` — una nota de visibilidad: no hay claves nuevas; Pedidos / Órdenes / Catálogos son boards ya existentes reetiquetados
- [ ] `docs/INDEX.md` solo si la ficha de Compras describe la bandeja suelta

## Archivos compartidos (`shared-files`)

Flag: **Sí**. Un agente a la vez. No paralelizar cortes.

| Archivo | Corte | Qué se permite |
| --- | --- | --- |
| `config/purchase-orders.php` | T1 | `form_period` = `Enero de 2025`, `report_title` = `ORDEN DE COMPRA O SERVICIO`, clave `condiciones` con el texto exacto |
| `config/access.php` | T4 | Solo etiquetas de `bandeja_compras`, `ordenes_compra` y `proveedores`. Cero claves nuevas. No borrar claves de `boards` ni de `system_permissions` |
| `routes/modules/purchase-requests.php` | T2 y T5 | Cerrar saldo (grupo processing) y evaluación (grupo `providers.edit`) |
| `routes/modules/supplies.php` | T2 y T4 | Reenvío, comentarios, redirect de `approved.index` |
| `app/Services/Navigation/NavigationResolver.php` | T4 | Etiqueta, URL activa y que Catálogos / Pedidos no se pisen con Suministros / Solicitudes |
| `app/Services/Navigation/SidebarVisibilityService.php` | T4 | Mostrar Pedidos con `purchase.tab.processing`; Catálogos si hay view de proveedores o catálogo de insumos |
| `app/Services/Access/PurchaseAccessService.php` | T4 | Quitar la pestaña Bandeja de `visibleTabsFor` |
| `app/Services/Access/SupplyAccessService.php` | T4 | Quitar Insumos aprobados y sacar el catálogo del subnav de Suministros |
| `app/Models/User.php` | T4 | `defaultSupplyBoardUrl`: ya no priorizar `insumos_aprobados` ni `catalogo` como entrada de Suministros |

No hace falta `routes/web.php`.

## Criterios de aceptacion

1. Una solicitud Cliente guarda Comodato y Activo en No por defecto, los muestra en alta, reenvío y detalle, y la OC nueva imprime Sí o No. Una interna los deja `null` y no los imprime como No. Un suministro y una OC anterior al cambio imprimen esas celdas vacías.
2. El PDF y el Excel traen el título **ORDEN DE COMPRA O SERVICIO**, el periodo **Enero de 2025**, el logo `logoSj.png`, las firmas de este brief y el texto legal **idéntico** (espacios dobles y tildes tal cual). Con 1 línea y con unas 12 líneas el PDF es una sola hoja carta. La evaluación no aparece.
3. Enviar, rechazar, reenviar, aprobar y emitir OC cambian la etiqueta según la tabla, sin selector. Abrir Procesar no pasa un suministro a `en_compras`.
4. Un suministro `rechazada_calidad` se reenvía y vuelve a `pendiente_calidad`. El motivo nuevo del director queda en el hilo, no en `comentarios_director`.
5. Cerrar saldo sin motivo falla. Con motivo, el texto queda en el hilo en mayúsculas y el pedido queda Cerrado aunque sobre saldo. Después no se puede emitir otra OC.
6. Un pedido con `estado_compras=rechazado` no cambia de valor y no sale en Por comprar, Con orden ni Historial.
7. Procesar un suministro no pide `unit_cost`. Si el ítem ya tenía costo, el formulario de la OC trae ese `valor_unidad`. El costo viejo sigue en la línea del suministro.
8. Con `purchase.tab.processing` el sidebar muestra Pedidos y Órdenes. Catálogos muestra Proveedores o Insumos según el permiso de siempre. No hay pestaña Bandeja dentro de Solicitudes de compra ni pestaña Insumos aprobados. El folio se ve `SC-` / `SU-` y el de la OC sigue siendo el `id`. No aparece ninguna clave nueva en `config/access.php`.
9. El KPI de bandeja del dashboard cuenta Por comprar + Con orden y no suma cerrados.
10. Con `purchase.providers.edit` se guarda la fecha sin calificaciones, y la evaluación calcula 100 / 80 / 60 / 20 como en la tabla. Sin ese permiso, 403. Con saldo cero y una OC sin fecha, el pedido sigue Con orden. Con todas las fechas y saldo cero, pasa a Cerrado. Cerrar saldo lo cierra igual sin fechas.
11. Ningún pedido cerrado se reescribe. `php artisan migrate` (sin fresh) deja las filas viejas en su sitio.

## Validacion local

1. `php artisan migrate` aditivo. Prohibido `migrate:fresh`, `db:wipe` y truncar.
2. `vendor/bin/pint --dirty --format agent` tras PHP de cada corte.
3. Tests, por corte:
   - T1: `php artisan test --compact tests/Feature/PurchaseRequests/PurchaseOrderEmissionTest.php` y un test nuevo de layout (Cliente vs interna vs suministro, texto legal, una página con 1 y con 12 líneas, Excel con las condiciones).
   - T2: test nuevo de máquina (`PurchaseFlowStateTest` o equivalente): abrir Procesar no mueve estado, reenvío de suministro, Cerrar saldo, exclusión de `rechazado`, conteo de dashboard.
   - T3: el formulario de suministro no exige `unit_cost`; la OC precarga el costo viejo y no lo borra.
   - T4: test de navegación: etiquetas Pedidos / Órdenes / Catálogos, sin pestaña Bandeja ni Insumos aprobados, folio `SC-`/`SU-`, 403/redirect de la URL vieja de insumos aprobados.
   - T5: test de evaluación (fórmula, fecha sola, 403 sin `purchase.providers.edit`, PDF sin el bloque, cierre con y sin fechas).
4. `npm run build` si el corte tocó CSS o JS de Vite.
5. Smoke Chrome DevTools donde hay pantalla (no hace falta «abrir» el PDF como página):
   - T1: alta Cliente muestra la fila de cinco radios; interna no.
   - T2: procesar no ofrece selector de estado; Cerrar saldo exige motivo.
   - T3: la OC precarga el valor si había costo; procesar suministro no pide costo unitario.
   - T4: se ven los tres tableros; Calidad no ve Insumos aprobados; el folio se lee `SC-` o `SU-`.
   - T5: la pantalla de evaluación guarda la fecha y no pinta ese bloque en el documento.

## Riesgos y dependencias

| Riesgo | Mitigación |
| --- | --- |
| Añadir una clave de board crea `view.board.*` al sincronizar | T4 solo cambia etiquetas y predicados de visibilidad |
| Borrar la clave `bandeja_compras` borra el permiso en `sync()` | La clave se queda; cambia el texto |
| T5 reabre pedidos que T2 ya cerró por saldo cero | `completado` / `completada` es terminal. La fecha solo decide la entrada a Cerrado |
| Reimprimir cambia el aspecto de una OC vieja | S8: el layout nuevo, las celdas sin snapshot vacías, el `id` igual |
| El texto legal se «corrige» al copiarlo | Copiar el bloque de este brief. El test compara el string completo |
| DomPDF se va a página 2 | Bajar la letra de la tabla antes que la de las condiciones. El test exige una página |
| El dashboard sigue contando cerrados hasta T4 | S6 obliga el conteo en T2, antes del cambio de menú |
| Dos agentes tocan `access.php` o la nav a la vez | Un agente. Orden T1 → T2 → T3 → T4 → T5 |

## Propuesta de slices (para el plan AgentSj)

Orden estricto. Un agente. No paralelizar.

| Slice | Objetivo | Archivos principales | Hecho cuando | shared-files |
| --- | --- | --- | --- | --- |
| **T1** | Comodato/Activo + snapshot OC + PDF y Excel FO-AD-07 de una hoja con el texto legal exacto | Migraciones de `purchase_requests` y `purchase_orders`; `PurchaseOrderService`; `pdf/purchase-order.blade.php`; `PurchaseOrderPdfExporter`; `PurchaseOrderExcelExporter`; `config/purchase-orders.php`; create/edit/show | Cliente imprime Sí/No; interna, suministro y OC vieja imprimen vacío; PDF de 1 y de ~12 líneas es una carta; Excel trae las condiciones | `config/purchase-orders.php` |
| **T2** | Máquina única, sin selector, reenvío de suministro, Cerrar saldo, hilo | `PurchaseFlowStateService`; `PurchaseProcessingController`; `PurchaseApprovalService`; reenvío y policy de suministro; migración `supply_request_comments`; `ComprasQueueService`; `ComprasDashboardService`; rutas de cierre y de reenvío | Abrir Procesar no mueve estado; el rechazo de Calidad se reenvía; Cerrar saldo exige motivo y deja Cerrado; el dashboard no cuenta cerrados | `routes/modules/purchase-requests.php`, `routes/modules/supplies.php` |
| **T3** | Un solo precio, el de la OC | `process-supply.blade.php`; `ProcessSupplyComprasRequest`; `PurchaseProcessingController::updateSupply`; precarga ya existente en `supplyLines()` | Procesar no pide `unit_cost`; si había costo, `valor_unidad` viene cargado; el costo histórico no se borra | No |
| **T4** | Tres tableros, folios, bandeja distinta del historial | Etiquetas en `config/access.php`; `NavigationResolver`; `SidebarVisibilityService`; `PurchaseAccessService`; `SupplyAccessService`; `User::defaultSupplyBoardUrl`; listado de Pedidos | Se ven Pedidos, Órdenes y Catálogos con los permisos de siempre; no hay Bandeja suelta ni Insumos aprobados; folio `SC-`/`SU-`; el dashboard sigue | `config/access.php` (solo etiquetas), nav y access services, `User.php` |
| **T5** | Fecha de recibido + evaluación 25/25/50, sin imprimirla | Rutas `orders.evaluation.*`; Form Request; vista sobre el detalle de la OC; ajuste del predicado en `PurchaseFlowStateService` | `purchase.providers.edit` guarda fecha y Calif.; 403 sin ese permiso; Cerrado solo con todas las fechas y saldo 0; Cerrar saldo sigue cerrando sin fechas; el PDF no lleva la evaluación | `routes/modules/purchase-requests.php` |

T5 depende de T2 (la máquina) y de la tabla que ya dejó FEAT-047. T3 puede apoyarse en que T2 ya quitó el «completar» manual. T4 depende de que T2 ya sepa qué filas son Por comprar, Con orden y Cerrado.

## Aprobacion

- [x] Analista — vacíos cerrados (`docs/briefs/FEAT-048-analyst.md`)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-048.md`)
- [x] Usuario — plan aprobado el 2026-10-20 (ley de este ciclo)
