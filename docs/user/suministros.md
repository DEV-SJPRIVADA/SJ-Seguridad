# Gestion de suministros — Guia de usuario

## Objetivo

Permitir solicitar insumos (aseo, cafetería, papelería, etc.), someterlos a la revisión de Calidad y que Compras los compre con una orden. Si Calidad devuelve el pedido, el solicitante puede corregirlo y enviarlo de nuevo.

## Alcance

Aplica al tablero **Suministros** y, para Compras, a **Pedidos** y a la pestaña **Insumos** de **Catálogos**.

| Rol | Dónde entrar |
| --- | --- |
| Solicitante | Su área → Suministros → Mis solicitudes |
| Calidad | **Calidad** → Suministros → Por aprobar |
| Compras (comprar el pedido) | **Compras** → **Pedidos** |
| Quien administra el catálogo | **Compras** → **Catálogos** → Insumos |

Según su perfil puede:

- Crear y consultar **Mis solicitudes**, y reenviar una que Calidad haya devuelto
- Revisar y aprobar o devolver en **Por aprobar** (Calidad). Ya no hay una pestaña de insumos aprobados
- Dejar notas en el pedido mientras no esté cerrado
- Administrar el catálogo de productos dentro de **Catálogos**
- Ver el detalle y descargar el FO-AD-44 cuando Calidad ya aprobó
- Comprar el insumo emitiendo la orden. El cierre lo hace la orden, no un costo unitario

## Definiciones

| Término | Significado |
| --- | --- |
| Solicitud de suministro | Pedido de uno o varios productos del catálogo, o de un producto que no está en el catálogo. |
| Folio | Número visible del pedido. Empieza por **SU-** (por ejemplo SU-0042). El número de adentro no cambia. |
| Estado | El mismo que en las solicitudes de compra: Por autorizar, Devuelta, Por comprar, Con orden o Cerrado. |
| Por autorizar | Solicitud enviada, esperando la revisión de Calidad. |
| Por comprar | Calidad ya autorizó las cantidades y todavía no hay una orden. |
| Devuelta | Calidad no autorizó. El solicitante puede corregirla y enviarla de nuevo. El motivo de Calidad se conserva. |
| Con orden | Ya hay una orden y queda cantidad, o falta anotar la fecha de recibido. |
| Cerrado | El pedido ya no admite otra orden. Lo cierra la orden (cuando no queda cantidad y todas tienen fecha de recibido) o la acción Cerrar saldo. No lo cierra un costo unitario. |
| Producto fuera de catálogo | Ítem que el solicitante agrega a mano porque no existe en el catálogo. |
| Sede | Ubicación física del solicitante. Debe estar asignada en su usuario para poder pedir. |
| FO-AD-44 | Formato del pedido (PDF y Excel) cuando Calidad ya lo aprobó. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante | Armar el pedido desde el catálogo o con ítems que no estén en él; enviarlo a Calidad; consultar el estado. Si Calidad lo devuelve, corregirlo y reenviarlo. |
| Calidad | Revisar cantidades, aprobar o devolver, y dejar notas mientras el pedido no esté cerrado. Trabaja solo en Por aprobar. |
| Compras | Ver el pedido en Pedidos, emitir la orden (ahí va el precio) y, si hace falta, cerrar el saldo con un motivo. |
| Quien administra el catálogo | Crear, editar y activar o inactivar productos en Catálogos → Insumos. |
| Admin usuarios | Asignar sede al usuario; gestionar catalogo de sedes. |

## Desarrollo

### Crear una solicitud

1. Verifique que su usuario tenga **sede** asignada (Admin usuarios); sin sede no puede solicitar.
2. Entre a **Mis solicitudes** → **Solicitar**.
3. Agregue productos del catalogo al carrito o use **Agregar fuera de catalogo** si aplica.
4. Indique inventario actual si se solicita.
5. Envíe la solicitud. Queda **Por autorizar** hasta que Calidad la revise. El folio se lee **SU-**.

### Si Calidad o Compras devuelven el pedido

Compras también puede devolverlo si llegó mal diligenciado (solo antes de emitir una orden). En ambos casos recibe un correo y el motivo queda en el detalle.

1. En **Mis solicitudes**, abra el pedido devuelto.
2. Corrija las líneas o las notas y envíelo de nuevo.
3. Vuelve a quedar pendiente de Calidad. El motivo que escribió Calidad se conserva.
4. Mientras el pedido no esté cerrado, usted, Calidad y Compras pueden agregar notas en el detalle. Esas notas se guardan en mayúsculas.

### Aprobar o devolver (Calidad)

1. Abra **Calidad → Suministros → Por aprobar**. Ahí están las solicitudes pendientes de **todas las áreas**. Use **Filtros → Área solicitante** para ver solo un área; el icono de **X** quita el filtro. El Excel exporta lo que está filtrado. No hay una lista aparte de insumos ya aprobados.
2. Seleccione una solicitud pendiente.
3. Ajuste las cantidades si hace falta.
4. Apruebe o devuelva, con observaciones. Al aprobar, el pedido pasa a Compras como **Por comprar**.

### Consultar el detalle y descargar el FO-AD-44

1. Abra **Mis solicitudes** y entre al detalle. Calidad lo abre desde Por aprobar antes de decidir; después, quien compra lo abre desde Pedidos.
2. Revise los datos, las líneas y las observaciones.
3. Si Calidad ya aprobó, use **Descargar PDF** o **Exportar Excel** (FO-AD-44).
4. Cuando Compras emite órdenes, el detalle muestra la sección **Órdenes de compra** (valores, estado con las anuladas marcadas, recibido y PDF FO-AD-07 descargable) y, en los productos, cuánto se ha ordenado y cuánto queda pendiente.

### Comprar el insumo (Compras)

1. Entre a **Compras → Pedidos**.
2. En **Por comprar** están los que Calidad ya aprobó y todavía no tienen orden. En **Con orden** están los que ya tienen una orden y les falta cantidad o la fecha de recibido. En **Historial** están los cerrados.
3. Filtre por tipo **Suministro** si el listado mezcla compras e insumos. Los más recientes salen primero. El folio se lee **SU-**.
4. **Ver detalle** muestra el pedido y permite descargar el FO-AD-44.
5. **Procesar** no pide el costo unitario y no cambia el estado solo por abrirlo. El precio se escribe al **emitir la orden**. Si el producto ya tenía un costo antiguo, la orden lo trae cargado y ese costo no se borra.
6. El pedido pasa a **Cerrado** cuando ya no queda cantidad por pedir y todas las órdenes tienen fecha de recibido. Si falta una fecha, sigue en Con orden.
7. Si hay que cerrarlo antes, use la sección **Cerrar saldo** dentro de Procesar, con un motivo. El motivo queda en las notas. Después no se emite otra orden.

### Administrar el catálogo

1. Abra **Compras → Catálogos → Insumos**. Ya no está dentro de Suministros.
2. Cree o edite productos (nombre, descripción, categoría).
3. Inactive los productos que ya no deben pedirse.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.10 | 2026-10-22 | Compras | El detalle del suministro muestra sus órdenes de compra con PDF descargable y el saldo ordenado/pendiente. |
| 1.9 | 2026-10-21 | Compras | Compras puede devolver un pedido Por comprar sin órdenes; al reenviarlo, Calidad lo vuelve a aprobar. |
| 1.8 | 2026-10-21 | Calidad / Compras | Por aprobar muestra los pedidos de todas las áreas, con filtro por área. Los estados se llaman igual que en compras (Por autorizar, Devuelta, Por comprar, Con orden, Cerrado). Observaciones en mayúsculas. |
| 1.7 | 2026-10-20 | Compras | Reenvío si Calidad devuelve el pedido; notas en el detalle; el catálogo pasa a Catálogos; ya no hay pestaña de insumos aprobados; el folio se lee SU-; el cierre lo hace la orden, no el costo unitario. |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-08-03 | Navegacion canonica | Entrada al menu por area base vs Calidad vs Compras |
| 1.2 | 2026-08-03 | Detalle y export | Vista detalle unificada con solicitud compra; PDF/Excel FO-AD-44 desde detalle; flujo Compras en bandeja |
| 1.6 | 2026-10-02 | UI | Aprobacion, Insumos aprobados y Catalogo: layout e iconos alineados a solicitudes de compra. |
| 1.5 | 2026-10-02 | UI | Mis solicitudes y Solicitar: layout e iconos alineados a solicitudes de compra. |
| 1.4 | 2026-10-02 | UI | Detalle de suministro y procesar en bandeja: mismo layout e iconos que solicitudes de compra. |
| 1.3 | 2026-08-28 | Bandeja compras | Insumos aprobados visibles por fecha mas reciente; filtro tipo Suministro se aplica al elegir |
