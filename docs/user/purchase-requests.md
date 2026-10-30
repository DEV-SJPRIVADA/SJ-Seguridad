# Solicitudes de compra — Guia de usuario

## Objetivo

Permitir a colaboradores solicitar compras de bienes o servicios, que un director autorice la solicitud, y que Compras compre, emita la orden impresa y cierre el pedido. El estado del pedido cambia solo, según lo que ya ocurrió.

## Alcance

Aplica a **Solicitudes de compra** y, en el área Compras, a los tableros **Pedidos**, **Órdenes** y **Catálogos**. El tablero Dashboard se conserva.

| Perfil | Dónde entrar |
| --- | --- |
| Colaborador solicitante | Su área → Solicitudes de compra |
| Director | Compras → Solicitudes de compra → Autorizaciones compras |
| Equipo de Compras | Compras → **Pedidos** y **Órdenes**. El catálogo de proveedores está en **Catálogos** |
| Dashboard | Compras → Dashboard |

Según su perfil puede:

- Crear solicitudes con una o varias líneas de productos (con foto opcional por línea)
- Adjuntar, si lo desea, varios documentos de soporte a **toda** la solicitud (cotización, orden, evidencia)
- Consultar el estado de sus propias solicitudes, agregar **varios comentarios** al detalle y descargar esos adjuntos
- Autorizar o devolver solicitudes asignadas a usted como director
- En Pedidos: ver lo que falta por comprar, lo que ya tiene orden y el historial de lo cerrado; emitir la orden; cerrar el saldo
- Descargar el pedido (FO-AD-44) y la orden impresa (FO-AD-07)
- Registrar la fecha de recibido y calificar al proveedor, si administra el catálogo de proveedores

El director puede autorizar desde el **correo** (enlace personal firmado, sin login) o desde la **plataforma**. Los documentos de soporte **no** viajan en el correo ni en el FO-AD-44: para verlos hay que entrar al detalle en la plataforma. Compras ya no rechaza un pedido desde Pedidos. Un pedido que Compras hubiera rechazado antes no aparece en Por comprar, Con orden ni Historial: sigue en Mis solicitudes.

## Definiciones

| Termino | Significado |
| --- | --- |
| Solicitud de compra | Pedido formal de bienes o servicios con lineas de detalle (cantidad, descripcion, referencia, etc.). |
| Folio | Número visible de la solicitud. Empieza por **SC-** (por ejemplo SC-0042). El número de adentro no cambia. |
| Director aprobador | Persona con rol de director o de administrador elegida al crear la solicitud; es quien puede autorizarla. |
| Aprobación de orden | Paso opcional: Compras pide que un director o administrador apruebe una orden emitida. Si la aprueba, su nombre sale en la orden impresa; si la rechaza, la orden queda Anulada. |
| Estado | Una sola etapa para compras y suministros, con el mismo nombre y color: Por autorizar, Devuelta, Por comprar, Con orden o Cerrado. |
| Por autorizar | Esperando la decisión del director (o de Calidad, si es un insumo). |
| Devuelta | El director no autorizó. El solicitante puede corregirla y enviarla de nuevo. |
| Pedidos | Tablero de Compras con tres vistas: Por comprar, Con orden e Historial. |
| Por comprar | El director ya autorizó (o Calidad, si es un insumo) y todavía no hay cantidades en una orden. |
| Con orden | Ya hay una orden y queda cantidad por pedir, o el saldo llegó a cero pero falta anotar la fecha de recibido en alguna orden. |
| Historial / Cerrado | El pedido ya no admite otra orden. |
| Cerrar saldo | Acción de Compras para dejar el pedido en Cerrado aunque todavía quede cantidad, escribiendo el motivo. |
| Anulación de orden | Compras la pide eligiendo un director; si el director la aprueba, la orden queda Anulada y su cantidad vuelve al saldo del pedido. |
| Devolver al solicitante | Acción de Compras para regresar un pedido mal diligenciado (Por comprar, sin órdenes). Queda Devuelta y, al reenviarse, se vuelve a autorizar. |
| Solicitud Interno | Compra para uso interno. No pregunta Comodato ni Activo. |
| Solicitud Cliente | Compra asociada a un cliente. Pregunta Proyecto nuevo, Asume el cliente, Reinversión, Comodato y Activo. |
| Comodato / Activo | Preguntas de Sí o No, solo en una solicitud Cliente. Por defecto quedan en No. |
| Urgente | Marca de prioridad visible para el director y para Compras. |
| FO-AD-44 | Formato del **pedido**. No incluye los documentos de soporte. |
| FO-AD-07 | Formato de la **orden** impresa o en Excel. Una hoja. No incluye la evaluación del proveedor. |
| Orden de compra | Documento que se emite a un proveedor. Su número es el correlativo de la orden (no lleva SC-). |
| Adjuntos | Archivos opcionales de la solicitud completa. Se consultan solo en el detalle de la plataforma. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Colaborador solicitante | Crear solicitudes en su área, elegir director, adjuntar documentos si aplica y consultar Mis solicitudes. Si el director la devuelve, puede reenviarla. |
| Director | Autorizar o devolver solo las solicitudes asignadas. El motivo queda en los comentarios de la solicitud. Los adjuntos se revisan en el detalle de la plataforma. |
| Compras | Trabajar en Pedidos y Órdenes: emitir la orden, cerrar el saldo cuando haga falta y descargar el FO-AD-07. No elige el estado a mano. |
| Quien administra proveedores | Mantener el catálogo y, en cada orden emitida, anotar la fecha de recibido y la calificación. |
| Administración de usuarios | Asignar quién puede solicitar, autorizar, comprar o administrar catálogos, y el rol de director. |

## Desarrollo

### Crear una solicitud de compra

1. Entre a **Solicitudes de compra** en su area → pestaña **Nueva solicitud**. El formulario esta en secciones (datos generales, cliente si aplica, productos y adjuntos); use la guia lateral como checklist.
2. Complete **Area**, **Fecha**, **Solicitud para** (Interno o Cliente) y seleccione el **Director aprobador**.
3. Si tiene una cotización, escriba su **Número de cotización** (opcional). En **Comentarios** puede dejar información adicional para el director y Compras (opcional). Marque **Urgente** si aplica.
4. Si eligió **Cliente**, complete razón social y responda **Sí** o **No** en Proyecto nuevo, Asume el cliente, Reinversión, **Comodato** y **Activo**. Comodato y Activo vienen en No. Si la solicitud es **Interno**, esas preguntas no aparecen y la orden impresa muestra **No** en esas casillas. El PDF y el Excel de la solicitud y de la orden se abren en otra pestaña.
5. Agregue una o mas lineas de producto: foto, descripcion, referencia, **marca** (opcional, al lado de la referencia), cantidad, utilizacion, ubicacion.
   - Opcional **carga masiva**: **Descargar plantilla** (Excel en blanco), llenela desde la fila 3 y use **Cargar Excel** para precargar la tabla. Luego puede editar filas, agregar fotos y enviar. La foto no va en el Excel.
6. En **Adjuntos** (despues de los productos) puede, si lo desea, seleccionar **varios archivos a la vez** para toda la solicitud: cotizacion, orden de compra, evidencia u otro soporte. Es opcional: puede enviar la solicitud sin ningun archivo. Hasta 5 archivos, 10 MB cada uno. Tipos permitidos: PDF, Word, Excel, PowerPoint, JPG, PNG y WEBP.
7. Envie el formulario. Recibira confirmacion con el numero de folio.
8. El director recibira un correo con el PDF FO-AD-44 y un enlace para **autorizar por correo** o ingresar a la plataforma. Ese correo **no** incluye los archivos de Adjuntos; el director los ve al abrir el detalle en la plataforma.

### Consultar mis solicitudes

1. Abra **Mis solicitudes**.
2. Revise el listado. El folio se lee **SC-** seguido del número. La columna **Estado** muestra en qué va el pedido: Por autorizar, Devuelta, Por comprar, Con orden o Cerrado. Use **Filtros** para acotar por fechas, número de cotización (basta una parte del número), estado, urgencia o área; el listado se actualiza al elegir. La tabla muestra el número de cotización de cada solicitud. El icono de **X** limpia los filtros.
3. Pulse el folio o **Ver detalle** para consultar líneas y comentarios. Si hay documentos de soporte, aparecen en **Adjuntos**; pulse **Descargar** para guardarlos.
4. En **Comentarios de la solicitud** puede agregar varias notas mientras el pedido no esté **Cerrado**. El director y Compras también las ven. El motivo nuevo del director queda en ese hilo. Si un pedido antiguo trae un comentario suelto del director o de Compras, se sigue mostrando, pero los nuevos no se escriben aparte.
5. Los textos de la solicitud se guardan como se escriben. El motivo de cerrar saldo, las observaciones de la orden, las de la evaluación y los comentarios del hilo se guardan en mayúsculas.
6. Desde el detalle puede descargar **PDF** o **Excel** (FO-AD-44). Esos formatos no llevan los adjuntos ni el hilo de comentarios.
7. Cuando Compras emite órdenes, el detalle muestra la sección **Órdenes de compra**: número, fecha, proveedor, subtotal, IVA, total, estado (las anuladas aparecen marcadas) y fecha de recibido. Con el ícono de PDF descarga la orden FO-AD-07, y en **Ver líneas y valores unitarios** ve el precio de cada producto. La tabla de productos muestra además cuánto se ha **ordenado** y cuánto queda **pendiente**. El director asignado ve lo mismo.

### Reenviar una solicitud rechazada

1. Desde **Mis solicitudes**, abra la solicitud rechazada y entre a reenviar.
2. Revise o corrija las lineas de producto (la foto por linea sigue siendo opcional).
3. En **Adjuntos** puede **conservar** los archivos actuales, **quitar** alguno o **agregar** otros (el total no puede pasar de 5). Tambien puede dejar la solicitud sin adjuntos.
4. Envie de nuevo al director.

### Autorizar o rechazar (director)

**Opcion A — Por correo (sin login)**

1. Abra el correo de nueva solicitud de compra.
2. Pulse **Autorizar por correo** (enlace personal con vigencia limitada).
3. Revise folio, lineas y datos del solicitante; opcionalmente abra el PDF. Esta pantalla **no** muestra los documentos de Adjuntos; para verlos use **Ver en la plataforma**.
4. Escriba el motivo si devuelve la solicitud (obligatorio al devolver). Ese texto queda en los comentarios.
5. Pulse **Aprobar solicitud** o **Rechazar**.
6. Confirme en la pantalla de resultado. El solicitante recibe un correo con la decisión.

**Opcion B — Por plataforma**

1. Ingrese a **Compras → Solicitudes de compra → Autorizaciones compras**, subpestaña **Solicitudes de Compra**.
2. Pulse **Autorizar** en la fila deseada (o abra el detalle desde el correo con **Ver en la plataforma**).
3. Revise el detalle, lineas, datos del solicitante y, si existen, los **Adjuntos** (puede descargarlos).
4. Opcionalmente escriba comentarios (obligatorios si rechaza).
5. Pulse **Aprobar solicitud** o **Rechazar**.
6. La solicitud sale del listado de pendientes; el solicitante recibe correo con el resultado.

### Trabajar en Pedidos

El estado **no se elige**. Cambia solo:

- **Por comprar:** autorizado y todavía sin cantidades en una orden.
- **Con orden:** ya hay una orden y queda saldo, o el saldo llegó a cero pero falta la fecha de recibido en alguna orden.
- **Cerrado** (vista **Historial**): ya no se puede emitir otra orden. Un pedido cerrado no vuelve atrás.

1. Entre a **Compras → Pedidos**.
2. Elija la vista **Por comprar**, **Con orden** o **Historial**. Por comprar y Con orden son el trabajo del día. Historial muestra lo cerrado.
3. Use **Filtros**: rango de fechas, área solicitante y tipo (solicitud de compra o suministro). Al elegir área o tipo, el listado se actualiza solo.
4. El listado sale **más reciente primero**. Sin filtro de fechas muestra hasta 200 registros recientes; con fechas ve el periodo completo. Si no ve un suministro, revise que el tipo no esté en «Solicitud compra».
5. El folio de una compra se lee **SC-** y el de un insumo **SU-**.
6. Pulse el icono de ojo (**Ver detalle**) para consultar sin cambiar el estado. Abrir **Procesar** tampoco cambia el estado.
7. En Procesar no hay lista para marcar Pendiente, En curso o Completado. El pedido avanza al emitir la orden, al anotar todas las fechas de recibido o al usar **Cerrar saldo**.
8. En un suministro, Procesar **no pide el costo unitario**. El precio se escribe al emitir la orden. Si el insumo ya tenía un costo antiguo, la orden lo trae cargado y ese costo viejo no se borra.

### Cerrar saldo

Úselo en Por comprar o Con orden cuando el pedido debe quedar cerrado aunque todavía falte cantidad o falte anotar una fecha de recibido.

1. Abra **Procesar** de esa solicitud o de ese suministro.
2. En la sección **Cerrar saldo** (está en el mismo formulario, no en una ventana aparte) escriba el **motivo**. Es obligatorio.
3. Pulse **Cerrar saldo**. El motivo queda en los comentarios, en mayúsculas, y el pedido pasa a **Cerrado**.
4. Después de eso no se puede emitir otra orden de ese pedido. Si el problema es que el pedido está mal diligenciado, use **Devolver al solicitante** en lugar de cerrar el saldo.

### Devolver al solicitante (Compras)

Úselo cuando el pedido llegó mal diligenciado. Solo aparece en **Por comprar** y mientras no se haya emitido ninguna orden.

1. Abra **Procesar** de esa solicitud o de ese suministro.
2. En la sección **Devolver al solicitante** escriba qué debe corregir. Es obligatorio.
3. Pulse **Devolver al solicitante**. El pedido sale de Pedidos y queda **Devuelta**. El motivo queda en los comentarios, en mayúsculas, y el solicitante recibe un correo.
4. El solicitante lo corrige desde **Mis solicitudes** y lo reenvía. Una solicitud de compra vuelve a **Por autorizar** y el **director debe autorizarla de nuevo**. Un suministro vuelve a **Calidad** para aprobarlo de nuevo.

### IVA de la orden de compra

1. Al **emitir una orden**, en cada línea elija la tasa de **IVA %** (viene en 19%). El sistema calcula el **Valor IVA** sobre cantidad × valor unidad y el **Valor total** = cantidad × valor unidad + IVA. Al pie se ven el subtotal, el IVA y el total.
2. La orden, su PDF y su Excel muestran el valor del IVA y, al lado, la tasa; por ejemplo **38.00 (19%)**.
3. Las tasas se administran en **Compras → Catálogos → IVA**. Quien edita proveedores puede crear una tasa, cambiar su nombre o inactivarla; las inactivas dejan de aparecer al emitir. Cambiar una tasa no modifica las órdenes ya emitidas.

### Exportar las órdenes a Excel (Compras)

1. En **Compras → Órdenes**, aplique los filtros que necesite (estado, origen, proveedor, fechas) y, si quiere, escriba en el buscador de la tabla.
2. Pulse el icono de Excel (**Exportar**) a la derecha del conteo de órdenes.
3. La hoja **Productos** trae **una fila por cada producto** de cada orden, con los datos de la orden: número, fecha, estado, origen y folio, **fecha de la solicitud**, **fecha de aprobación** (del director; en suministros, de Calidad), área, número de cotización, proveedor y su contacto, forma de pago, ciudad de entrega, quién la elaboró, fecha de recibido, observaciones y total; y de cada producto: cantidad, artículo, marca, descripción, valor unidad, IVA y valor total.
4. La hoja **Evaluación** trae una fila por cada orden con su evaluación: fecha de recibido, las tres calificaciones, sus resultados, el puntaje, el resultado (satisfactoria, requiere seguimiento o insatisfactoria), las observaciones, quién evaluó y cuándo. Las órdenes sin evaluar aparecen con **Evaluada: No**.
5. Si el filtro de estado incluye anuladas, salen con estado **Anulada**.

### Anular una orden de compra mal emitida (Compras y director)

Una orden emitida no se edita ni se borra. Si quedó mal (proveedor, precio, cantidad o pedido equivocado), pida su anulación. Solo se puede pedir mientras la orden no tenga fecha de recibido.

1. **Compras:** abra la orden en **Órdenes** y, en **Solicitar anulación**, elija el **director que aprueba** y escriba el motivo. Pulse **Solicitar anulación**.
2. La orden queda **Anulación pendiente**. Mientras tanto no se puede evaluar y su cantidad sigue apartada, para no emitir otra orden por lo mismo antes de la respuesta.
3. Si se equivocó al pedirla, pulse **Retirar solicitud de anulación** y la orden vuelve a **Emitida**.
4. **Director:** recibe un correo con el botón **Revisar anulación** (no pide iniciar sesión) y además la ve en **Solicitudes de compra → Autorizaciones compras**, subpestaña **Anulaciones de órdenes** (muestra entre paréntesis cuántas tiene pendientes). Solo el director elegido puede decidir.
5. Al **aprobar**, la orden queda **Anulada**: no se borra, conserva su número y su PDF y Excel muestran **ANULADA**. Su cantidad vuelve al saldo del pedido, que pasa a **Con orden** o **Por comprar** según las otras órdenes. Desde ahí Compras puede emitir la orden correcta o, si no quedan órdenes, devolver el pedido al solicitante.
6. Al **rechazar**, el director debe escribir el motivo y la orden sigue **Emitida**.
7. Quien pidió la anulación recibe un correo con la decisión. Cada paso queda en los comentarios del pedido.

El sistema no envía las órdenes al proveedor; si ya se la había entregado, avísele por fuera.

### Pedir la aprobación de una orden de compra (Compras, director o administrador)

Es **opcional**. Sin pedirla, la orden sigue **Emitida** y se puede descargar como siempre.

1. **Compras:** abra la orden en **Órdenes** y, en **Solicitar aprobación**, elija quién aprueba (en la lista salen directores y administradores). Puede escribir una nota. Pulse **Solicitar aprobación**.
2. La orden muestra **Aprobación pendiente**. Mientras espera se puede descargar, pero no se puede pedir su anulación.
3. Si se equivocó de aprobador, pulse **Retirar solicitud de aprobación** y pídala de nuevo.
4. **Aprobador:** recibe un correo con el botón para revisar la orden (no pide iniciar sesión) y además la ve en **Solicitudes de compra → Autorizaciones compras**, subpestaña **Órdenes de Compra** (muestra entre paréntesis cuántas tiene pendientes). Solo la persona elegida puede decidir.
5. Al **aprobar**, la orden sigue Emitida y en el PDF y el Excel aparece su nombre en **Aprobado 2 por**.
6. Al **rechazar**, debe escribir el motivo y la orden queda **Anulada**: su cantidad vuelve al saldo del pedido y Compras puede emitir una orden corregida.
7. Quien pidió la aprobación recibe un correo con la decisión. Cada paso queda en los comentarios del pedido.
8. Lo ya decidido sale de la lista de pendientes pero no se pierde: en **Órdenes de Compra** (y también en **Anulaciones de órdenes**) cambie **Vista** a **Historial completo**, **Aprobadas** o **Rechazadas**, y use **Ver detalle** para consultar la decisión y el comentario.

### Emitir la orden impresa (FO-AD-07)

1. En **Pedidos**, cada fila tiene un icono (**Emitir orden de compra**). También está en **Procesar** y, en el detalle de la solicitud, en **Productos solicitados**. No aparece si el pedido ya está Cerrado. Si una pantalla vieja o un enlace directo intenta emitir sobre una solicitud **cerrada**, el sistema vuelve a la bandeja con el aviso **«No se puede emitir la orden de compra: la solicitud ya está cerrada.»** (antes mostraba un error 403).
2. Se abre el formulario con los datos del origen. Las líneas ya vienen con la cantidad y la descripción. En un suministro la cantidad es la autorizada. El **precio se escribe aquí**: valor unidad e IVA. Si el insumo ya tenía un costo, el valor unidad viene precargado.
3. Elija el **proveedor** (solo activos; use el buscador por nombre o identificación). Al elegirlo, si el proveedor tiene ciudad, esta se completa en **Ciudad de entrega**, y el campo **Contacto** se llena con el contacto guardado de ese proveedor. El contacto se puede **editar**: al emitir, se imprime en la orden (PDF/Excel) y además queda actualizado en el catálogo del proveedor.
4. Complete por línea el **valor unidad** y el **IVA** (el IVA es un valor, no un porcentaje). El **valor total** de la línea y el **TOTAL** de la orden se calculan solos: `cantidad × valor unidad + IVA`. La columna **Pendiente** indica cuánto falta por pedir de esa línea (si ya se emitieron otras OC de la misma solicitud).
5. Complete la **fecha**, la **ciudad de entrega** y las **observaciones**: las observaciones son **obligatorias** (el formulario no deja emitir sin ellas). Los textos se guardan en **MAYÚSCULAS**.
6. Pulse el icono azul de **check** para emitir. La orden queda registrada como **Emitida** y se abre su detalle.
7. El **número de la orden** es automático y aparece en el encabezado del PDF y del Excel. No es el folio SC- ni SU-.
8. La orden impresa es una sola hoja: título **ORDEN DE COMPRA O SERVICIO**, código **FO-AD-07**, periodo **Enero de 2025**, versión 04, logo de la empresa, datos del proveedor, las casillas Proyecto nuevo, Reinversión, Comodato y Activo, el detalle con el total, las observaciones, las firmas y las condiciones legales al pie. Esas condiciones salen tal cual el formato oficial.
9. En una solicitud **Cliente**, Comodato y Activo se imprimen Sí o No. En una solicitud interna, en un suministro y en una orden emitida antes de este cambio, esas casillas salen **en blanco** (no dicen No).
10. Las firmas traen el nombre de quien elaboró la orden (Analista de Compras) y el del director del área, o de quien revisó en Calidad si el origen es un insumo. Director administrativo y financiero, y Gerente, quedan en blanco para firma manuscrita.
11. La **evaluación del proveedor no sale** en el PDF ni en el Excel.
12. Si más tarde edita el catálogo de proveedores, la orden ya emitida **no cambia**. Al volver a imprimirla se usa el formato nuevo; lo que no se había guardado en su momento sigue en blanco y el número de la orden es el mismo.

### Ver las órdenes de compra emitidas

1. Entre a **Compras → Órdenes**. Ahí están las órdenes emitidas, con su número, fecha, proveedor, origen (folio SC- o SU-), ciudad de entrega, número de líneas, total y estado.
2. Use el **buscador** de la tabla para filtrar por proveedor, identificación, observaciones o número de OC.
3. El panel **Filtros** permite acotar por **Estado** (Todas / Emitidas / Borrador), **Origen** (Solicitud de compra / Solicitud de suministro), **Proveedor** y rango de **fechas**. El botón de **Aplicar** y el de **Limpiar** quedan uno al lado del otro.
4. En la fila, el icono de **ojo** abre el detalle de la OC y el de **documento** descarga el PDF FO-AD-07.

### Repartir una solicitud entre varios proveedores

1. Desde Pedidos, abra la orden de la solicitud o del suministro.
2. Cada línea muestra **solicitadas**, **ordenadas** y **pendiente** (la columna **Pendiente** está justo antes de **Cantidad**). La cantidad se precarga con el **pendiente** y no se puede escribir más de ese valor.
3. Si quiere repartir, emita una OC con la cantidad de ese proveedor (por ejemplo 50) y luego abra de nuevo el formulario: el pendiente ya descuenta lo ordenado (50) y podrá emitir otra OC con el resto (por ejemplo 20) para otro proveedor.
4. El sistema **no permite duplicar**: cuando ya no queda saldo, la línea aparece en gris con pendiente 0 y la emisión se rechaza con el aviso "La cantidad supera el saldo pendiente".
5. En el detalle de cada OC, el panel **Otras órdenes de esta solicitud** muestra las OC hermanas del mismo reparto.

### Catálogo de proveedores

1. El catálogo está en **Compras → Catálogos**, pestaña **Proveedores** (no es una pestaña de Solicitudes de compra). Quien puede editar proveedores también lo ve. En el mismo tablero, quien administra insumos ve la pestaña **Insumos**.
2. El listado muestra **solo proveedores activos** por defecto. Use el **buscador** de la tabla (identificación, nombre, correo, contacto, dirección o ciudad) y los botones **Activos / Inactivos / Todos** de la franja superior para cambiar de estado. La tabla incluye la columna **DIRECCIÓN**: si el texto es largo se recorta y puede verlo completo pasando el mouse. Al lado aparece el número de proveedores encontrados. El correo de cada proveedor es un enlace que abre su programa de correo. El catálogo solo contiene proveedores.
3. A la derecha de esa misma franja está la barra de acciones (iconos con su descripción al pasar el mouse): **Exportar**, **Importar VINE01** (con permiso de edición) y **+ (Nuevo proveedor)**. El **+** permite registrar identificación, nombre, teléfono, correo, contacto, dirección, ciudad, forma de pago, **días de pago** y estado.
4. El **teléfono** admite solo números (7 a 15 dígitos), el **correo** debe ser un correo válido y la **ciudad** se elige del catálogo de ciudades de Requisiciones.
5. Los textos se guardan en **MAYÚSCULAS**; el correo, la identificación, el teléfono y la ciudad se conservan tal cual.
6. Use el icono de Excel (**Exportar**) de la barra de acciones para exportar el catálogo con los filtros actuales.
7. Al editar y guardar, el listado conserva los filtros que tenía.

#### Carga masiva VINE01

1. Abra la ventana de carga (icono **importar** de la barra de acciones) y, dentro de ella, use el icono de Excel (**plantilla**) para obtener el archivo `VINE01` (hoja **VINE01** con encabezados y hoja **Instrucciones**).
2. Complete las columnas: **IDENTIFICA**, **NOMBRE**, **TELEFONO**, **CORREO_ELE**, **CONTACTO**, **DIRECCION**, **CIUDAD**, **FORMA_PAGO**, **PROVEEDOR**, **ESTADO** y **DIAS_PAGO** (opcional). `CONTACTO`, `DIRECCION`, `CIUDAD`, `FORMA_PAGO` y `DIAS_PAGO` pueden quedar vacíos. Se guarda lo que venga: la **CIUDAD** ya no se valida contra un catálogo (códigos de ciudad incluidos, ej. `76001-CALI`); si **DIAS_PAGO** trae texto como `30 DIAS` se toma el número y si trae `CONTADO` u otra condición, esa condición pasa a la **forma de pago** y los días quedan en blanco. Si `DIRECCION` queda vacía en un proveedor que ya existe, **se conserva** la dirección que tenía.
3. Con permiso de edición, el icono de **importar** de la barra de acciones abre la ventana de carga. Dentro de la ventana, todas las acciones son iconos con su descripción al pasar el mouse: **plantilla** (Excel), **elegir archivo** y **cerrar** (contorno) e **importar** (azul). Elija el archivo y pulse el icono azul de importar.
4. **Solo se cargan las filas con PROVEEDOR = SI.** Las filas que solo son cliente se omiten. Si el archivo no trae las columnas obligatorias (IDENTIFICA, NOMBRE, PROVEEDOR) o no tiene proveedores, se muestra el error y no se guarda nada.
5. Los registros se actualizan por **identificación**: si ya existe, se actualiza; si no, se crea. Al terminar, el resultado aparece en una **ventana de resumen** (no arriba de la página) con los conteos: nuevos, actualizados, omitidos por no ser proveedor, filas vacías y con error. Ciérrela con el icono de **Entendido** (chulo azul) o la **X**.
6. Si hubo filas con error, la ventana lista las primeras 50 (número de fila y motivo) y muestra el icono de **descarga** para bajar el **reporte `.xlsx`** completo con todas las filas fallidas.

### Dashboard Compras

1. Entre a **Compras → Dashboard** (tablero Dashboard Compras).
2. Filtre por ano, mes (opcional), area y tipo si necesita acotar el periodo.
3. Revise los indicadores: pendientes del director, en bandeja, bandeja pendiente, en curso y completadas en el periodo. **En bandeja** suma Por comprar y Con orden. Lo cerrado no entra en ese número.
4. Esos números coinciden con Pedidos cuando usa el mismo mes o rango de fechas.
5. Pulse un indicador de bandeja para abrir **Pedidos** en la vista que corresponde (Por comprar o Con orden), no un listado aparte.
6. Consulte los graficos de tendencia, estados y distribucion por area o tipo.

### Recepción y evaluación

Esto no se imprime en la orden. Lo hace quien administra el catálogo de proveedores, no quien solo trabaja en Pedidos.

1. Abra la orden en **Órdenes** y entre a la evaluación.
2. Puede guardar solo la **fecha de recibido**, sin calificar.
3. Si califica, complete las tres notas de 1 a 5: referencias y cantidades, oportunidad de entrega y calidad del producto. El resultado se calcula solo (compra satisfactoria, requiere seguimiento o compra insatisfactoria).
4. Cuando **todas** las órdenes de ese pedido tienen fecha de recibido y ya no queda cantidad por pedir, el pedido pasa solo a **Cerrado**. Si falta una fecha, sigue en **Con orden**. La calificación no cierra el pedido.
5. **Cerrar saldo** lo cierra igual aunque falten fechas.

### Suministros en Pedidos

Los insumos que Calidad aprobó aparecen junto con las solicitudes de compra, con folio **SU-**. Desde **Ver detalle** puede consultar el pedido y descargar **PDF** o **Excel** (FO-AD-44). El precio y el cierre se hacen con la orden, no con un costo unitario en Procesar.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.36 | 2026-10-21 | Compras | Emitir orden de compra: en **Líneas y precios** la columna **Pendiente** aparece antes de **Cantidad**. |
| 1.49 | 2026-10-23 | Compras | Autorizaciones compras: en **Órdenes de Compra** y **Anulaciones de órdenes** lo ya decidido queda en el historial; use **Vista** (Historial completo, Aprobadas o Rechazadas), igual que en Solicitudes de Compra. |
| 1.48 | 2026-10-23 | Compras | **Aprobación opcional de órdenes:** Compras la pide a un director o administrador; se decide por correo o en Autorizaciones compras → **Órdenes de Compra**. Las subpestañas quedan **Solicitudes de Compra**, **Órdenes de Compra** y **Anulaciones de órdenes**. Los administradores también aparecen como aprobadores de solicitudes y anulaciones. |
| 1.47 | 2026-10-23 | Compras | Export de Órdenes: fecha de la solicitud y de su aprobación, y nueva hoja **Evaluación** con una fila por orden. |
| 1.54 | 2026-10-30 | Compras | Emitir orden: nuevo campo **Contacto** después del proveedor. Se llena con el contacto guardado del proveedor y se puede editar; al emitir, se imprime en la orden (detalle, PDF y Excel) y se actualiza el contacto del proveedor en el catálogo. |
| 1.53 | 2026-10-30 | Compras | Emitir orden: si la solicitud ya está **cerrada**, el sistema muestra el aviso **«No se puede emitir la orden de compra: la solicitud ya está cerrada.»** en lugar de un error 403. Las **observaciones** del formulario pasan a ser **obligatorias**. |
| 1.52 | 2026-10-30 | Compras | Orden impresa FO-AD-07: la franja de firmas tiene **altura fija de 60px** (igual en todas las órdenes, sin importar la cantidad de líneas). |
| 1.51 | 2026-10-30 | Compras | Carga masiva VINE01: **CIUDAD** ya no se valida contra el catálogo (se guarda tal como viene y reemplaza en actualización); **DIAS_PAGO** tolerante (`30 DIAS` → 30; texto como `CONTADO` pasa a forma de pago); el error de archivo sin filas válidas ahora indica cuántas filas fallaron y la primera razón. |
| 1.50 | 2026-10-29 | Compras | Catálogo de proveedores: nueva columna **DIRECCIÓN** en el listado (buscable y ordenable, recortada con texto completo al pasar el mouse) y columna **DIRECCION** en la plantilla/carga masiva VINE01 (opcional; vacía conserva la dirección registrada). |
| 1.46 | 2026-10-23 | Compras | Emitir orden: el IVA se elige como **tasa del catálogo** (0%, 5%, 19%…) y el sistema calcula su valor y el total de cada línea. Nueva pestaña **Catálogos → IVA**. |
| 1.45 | 2026-10-23 | Compras | Detalle de la solicitud: después de Cantidad va Referencia y luego Descripción. Emitir orden: la columna "Artículo" se llama ahora **Referencia** (Cantidad → Referencia → Descripción). |
| 1.44 | 2026-10-22 | Compras | Órdenes: botón **Exportar Excel** con una fila por producto de cada orden y los datos de la orden, según los filtros y la búsqueda actuales. |
| 1.43 | 2026-10-22 | Compras | El detalle de la solicitud muestra sus **órdenes de compra** (con valores, anuladas marcadas y PDF descargable) y el saldo ordenado/pendiente por producto. El solicitante y el director las ven sin entrar a Órdenes. |
| 1.42 | 2026-10-22 | Compras | El número de cotización, la marca y los comentarios del solicitante se ven también en Procesar compra, en la aprobación por correo, en el PDF de la solicitud, en Autorizaciones compras y en Pedidos. |
| 1.41 | 2026-10-22 | Comercial / Compras | Productos solicitados: campo **Marca** (opcional) al lado de Referencia; también en la plantilla Excel de productos y en el detalle. |
| 1.40 | 2026-10-22 | Compras | Mis solicitudes: filtro y columna **Número de cotización**. |
| 1.39 | 2026-10-22 | Compras | Nueva y Reabrir: en Datos generales, **Número de cotización** (junto a Solicitud para) y **Comentarios** (cuadro grande, encima de Marcar como urgente). Ambos opcionales y visibles en el detalle. |
| 1.38 | 2026-10-21 | Compras | La pestaña del director pasa a llamarse **Autorizaciones compras**, con subpestañas **Pendientes de autorización** y **Anulaciones de órdenes**. |
| 1.37 | 2026-10-21 | Compras | **Anular orden de compra:** Compras la pide eligiendo el director que aprueba; el director decide por correo o en Pendientes de autorización; Compras puede retirar la solicitud. |
| 1.36 | 2026-10-21 | Compras | Nueva acción **Devolver al solicitante** en Procesar (Por comprar, sin órdenes). Al reenviar, el director (o Calidad, en suministros) vuelve a autorizar. |
| 1.35 | 2026-10-21 | Compras | Nueva y Reabrir: en Datos del cliente, Proyecto nuevo, Asume el cliente, Reinversión, Comodato y Activo quedan en una sola fila en portátil y pantalla grande. |
| 1.34 | 2026-10-21 | Compras | Un solo estado con el mismo nombre en compras y suministros (Por autorizar, Devuelta, Por comprar, Con orden, Cerrado). Mis solicitudes muestra una sola columna de estado. |
| 1.33 | 2026-10-21 | Compras | Mis solicitudes: panel de filtros por fechas, estado, estado de compras, urgencia y área. |
| 1.32 | 2026-10-21 | Compras | Carga masiva VINE01: se importan también Contacto, Ciudad y Forma de pago. La ciudad se valida contra el catálogo de Requisiciones, igual que en el alta manual. |
| 1.31 | 2026-10-21 | Compras | Nueva solicitud: en Productos solicitados, Cantidad queda antes de Utilización. |
| 1.30 | 2026-10-21 | Compras | En la orden impresa, Forma de pago hasta Activo muestran la respuesta junto a la etiqueta. Las condiciones dejan más espacio entre párrafos. |
| 1.29 | 2026-10-20 | Compras | El PDF y el Excel de la solicitud y de la orden se abren en otra pestaña. En la orden, Asume el cliente, Proyecto nuevo, Reinversión, Comodato y Activo muestran Sí o No según la solicitud. |
| 1.28 | 2026-10-20 | Compras | Orden impresa FO-AD-07 en una hoja; radios Comodato y Activo; el estado cambia solo; Pedidos (Por comprar, Con orden, Historial), Órdenes y Catálogos; Cerrar saldo; recepción y evaluación que no se imprime; el precio solo en la orden. |
| 1.27 | 2026-10-20 | Compras | Proveedores: listado rediseñado; el estado se elige con botones (Activos / Inactivos / Todos) en la misma franja que el conteo y las acciones; correo enlazable. |
| 1.26 | 2026-10-19 | Compras | Nuevo tablero **Órdenes de compra** con el listado de todas las OC emitidas (buscador y filtros). Una solicitud se puede **repartir entre varios proveedores**: cada línea muestra el saldo pendiente y el sistema bloquea las órdenes duplicadas. |
| 1.25 | 2026-10-19 | Compras | Emisión de **Orden de Compra (FO-AD-07)** desde la bandeja: elegir proveedor activo, capturar valor unidad e IVA por línea, total automático, número de OC automático y descarga en PDF/Excel. |
| 1.24 | 2026-10-19 | Compras | Ajuste del catálogo: los botones de filtro quedan en fila junto al selector Estado y la descarga de la plantilla VINE01 se mueve al interior de la ventana de importar. |
| 1.23 | 2026-10-19 | Compras | Rediseño del catálogo de proveedores: filtros en un panel plegable y barra de acciones siempre visible (exportar, plantilla, importar, nuevo). |
| 1.22 | 2026-10-19 | Compras | El catálogo solo almacena proveedores: se retira la clasificación cliente/proveedor y la columna CLIENTE de la plantilla VINE01. |
| 1.21 | 2026-10-19 | Compras | Carga masiva VINE01: el resultado se muestra en una ventana de resumen (no como alerta superior) con opción de **descargar el reporte `.xlsx`** de filas fallidas. |
| 1.20 | 2026-10-19 | Compras | Proveedores: buscador DataTables, botón **Nuevo proveedor** arriba a la derecha de la tabla, sin clasificación cliente/proveedor y carga masiva/plantilla **VINE01** (solo PROVEEDOR=SI). |
| 1.19 | 2026-10-19 | Compras | Proveedores ahora es un tablero del área Compras; acciones icon-only, teléfono numérico, correo validado y ciudad del catálogo de Requisiciones. |
| 1.18 | 2026-10-19 | Compras | Pestaña Proveedores: catálogo, alta/edición, filtros, export y permisos Ver/Editar. |
| 1.16 | 2026-10-02 | Compras | Datos del cliente: campo **Reinversión** (Si/No) al lado de Asume el cliente. |
| 1.17 | 2026-10-02 | UI | Productos: foto antes de descripción; layout de campos; radios de cliente en una fila; ortografía. |
| 1.16 | 2026-10-02 | UI | Nueva/Reabrir solicitud: productos en tarjetas con más espacio para Descripción, Referencia, Utilización y Ubicación. |
| 1.15 | 2026-10-02 | UI | Procesar suministro (bandeja): diseño alineado a procesar compra; acciones con iconos. |
| 1.14 | 2026-10-02 | UI | Procesar (bandeja) y Reabrir (mis solicitudes): diseño alineado a Nueva solicitud; acciones con iconos. |
| 1.13 | 2026-10-02 | UI | Bandeja compras: Ver detalle, Procesar y Limpiar filtros pasan a iconos. |
| 1.12 | 2026-09-22 | UI | Mis solicitudes y Bandeja compras: intro, resumen y listado alineados al resto del modulo. |
| 1.11 | 2026-09-22 | UI | Nueva y detalle: secciones, guia lateral, volver contextual y CTA Procesar desde bandeja. |
| 1.0 | 2026-07-31 | Modulo inicial | Flujo solicitante → director → Compras; FO-AD-44 |
| 1.1 | 2026-07-31 | Autorizacion in-app | Director autoriza en plataforma; correo solo notifica |
| 1.2 | 2026-08-03 | Navegacion canonica | Tabla de entrada al menu por perfil; guia ampliada |
| 1.3 | 2026-08-03 | Bandeja y dashboard | Filtros bandeja, dashboard Compras, ver detalle suministro unificado, KPIs alineados |
| 1.5 | 2026-08-20 | Analista TIC | Sin descripcion general ni justificacion de cabecera; el detalle va en las lineas de producto |
| 1.4 | 2026-08-03 | Autorizacion por correo | Director puede aprobar/rechazar via enlace firmado sin login; flujo dual correo + plataforma |
| 1.6 | 2026-08-28 | Bandeja | Listado por fecha mas reciente; filtro tipo/area se aplica al elegir |
| 1.10 | 2026-09-14 | Compras | Comentarios solo si Compras no esta completada; Mis solicitudes muestra estado compras; detalle conserva pestaña de origen (`from`). |
| 1.9 | 2026-09-14 | Compras | Hilo de comentarios en el detalle de la solicitud (varios mensajes post-envio; visible para solicitante, director y Compras). |
| 1.8 | 2026-09-14 | Compras | Nueva solicitud: plantilla Excel y carga masiva de productos a la tabla (sin fotos en el archivo). |
| 1.7 | 2026-08-28 | FEAT-030 | Adjuntos opcionales multiples por solicitud (detalle de la plataforma). El correo de autorizacion y el FO-AD-44 no los incluyen. Al reenviar una rechazada se pueden conservar, agregar o quitar |
