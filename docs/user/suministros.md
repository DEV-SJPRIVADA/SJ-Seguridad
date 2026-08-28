# Gestion de suministros — Guia de usuario

## Objetivo

Permitir solicitar insumos (aseo, cafeteria, papeleria, etc.), someterlos a aprobacion de Calidad, procesarlos en Compras cuando corresponda y obtener reportes FO-AD-44 en PDF o Excel.

## Alcance

Aplica al tablero **Suministros**. Segun su rol, el menu lo muestra en un solo lugar:

| Rol | Donde entrar |
| --- | --- |
| Solicitante | Su area asignada → Suministros → Mis solicitudes |
| Calidad (aprobacion) | **Calidad** → Suministros |
| Compras (catalogo / bandeja) | **Compras** → Suministros (catalogo) o **Solicitudes de compra → Bandeja compras** |

Segun permisos, el usuario puede:

- Crear y consultar **Mis solicitudes**
- Revisar y aprobar en **Aprobacion insumos** (Calidad)
- Consultar **Insumos aprobados** y descargar reporte FO-AD-44 (Calidad)
- Administrar el **Catalogo** de productos (Compras)
- Ver detalle y exportar FO-AD-44 desde la **Bandeja compras** (Compras), con la misma presentacion que una solicitud de compra

## Definiciones

| Termino | Significado |
| --- | --- |
| Solicitud de suministro | Pedido de uno o varios productos del catalogo o fuera de catalogo. |
| Folio | Numero de cuatro digitos que identifica la solicitud (ej. 0042). |
| Pendiente calidad | Solicitud enviada, esperando revision de Calidad. |
| Aprobada calidad | Calidad autorizo cantidades; pasa a bandeja de Compras. |
| Rechazada calidad | Solicitud denegada con observaciones de Calidad. |
| En compras | Compras esta gestionando la adquisicion del insumo. |
| Completada | Compras cerro el pedido con costos registrados. |
| Producto fuera de catalogo | Item que el solicitante agrega manualmente porque no existe en el catalogo. |
| Sede | Ubicacion fisica del solicitante; debe estar asignada en su usuario para poder pedir. |
| FO-AD-44 | Formato estandar de reporte (PDF y Excel) por solicitud aprobada. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante | Armar pedido desde catalogo o items custom; enviar a Calidad; consultar estado en Mis solicitudes. |
| Jefe / area Calidad | Revisar cantidades, aprobar o rechazar; consultar insumos aprobados. |
| Compras | Gestionar bandeja unificada, ver detalle, exportar FO-AD-44, ingresar costos y completar pedidos. |
| Administrador catalogo | Crear, editar y activar/inactivar productos. |
| Admin usuarios | Asignar sede al usuario; gestionar catalogo de sedes. |

## Desarrollo

### Crear una solicitud

1. Verifique que su usuario tenga **sede** asignada (Admin usuarios); sin sede no puede solicitar.
2. Entre a **Mis solicitudes** → **Solicitar**.
3. Agregue productos del catalogo al carrito o use **Agregar fuera de catalogo** si aplica.
4. Indique inventario actual si se solicita.
5. Envie la solicitud; queda en estado pendiente de Calidad.

### Aprobar o rechazar (Calidad)

1. Abra **Aprobacion insumos**.
2. Seleccione una solicitud pendiente.
3. Ajuste cantidades aprobadas si es necesario.
4. Apruebe o rechace con observaciones.

### Consultar detalle y descargar FO-AD-44 (solicitante o Calidad)

1. Abra **Mis solicitudes** o **Insumos aprobados** y entre al detalle de la solicitud.
2. Revise datos del pedido, lineas e observaciones.
3. Si la solicitud ya fue aprobada por Calidad, use **Descargar PDF** o **Exportar Excel** (formato FO-AD-44).

### Descargar FO-AD-44 desde Insumos aprobados (Calidad)

1. Abra **Insumos aprobados**.
2. Filtre por sede, fechas o estado de exportacion.
3. Use **Descargar FO-AD-44** en la fila deseada.
4. La primera descarga marca la solicitud como exportada; puede volver a descargar.

### Gestionar desde Bandeja compras (Compras)

1. Entre a **Compras → Solicitudes de compra → Bandeja compras**.
2. Filtre por fechas, area, tipo **Suministro** o estado si lo necesita. Los insumos aprobados salen mezclados con solicitudes de compra, **los mas recientes primero**; si no los ve en la primera pagina, filtre por tipo **Suministro**.
3. Pulse **Ver detalle** para ver el pedido con el mismo formato visual que una solicitud de compra.
4. Desde el detalle descargue **PDF** o **Excel** (FO-AD-44).
5. Para registrar costos y cerrar, use el flujo **Procesar** desde la bandeja.

### Administrar catalogo

1. Abra la pestaña **Catalogo** (Compras).
2. Cree o edite productos (nombre, descripcion, categoria).
3. Inactive productos que ya no deben pedirse.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-08-03 | Navegacion canonica | Entrada al menu por area base vs Calidad vs Compras |
| 1.2 | 2026-08-03 | Detalle y export | Vista detalle unificada con solicitud compra; PDF/Excel FO-AD-44 desde detalle; flujo Compras en bandeja |
| 1.3 | 2026-08-28 | Bandeja compras | Insumos aprobados visibles por fecha mas reciente; filtro tipo Suministro se aplica al elegir |
