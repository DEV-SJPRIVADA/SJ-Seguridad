# Gestion de suministros — Guia de usuario

## Objetivo

Permitir solicitar insumos (aseo, cafeteria, papeleria, etc.), someterlos a aprobacion de Calidad y obtener reportes de insumos aprobados para compras.

## Alcance

Aplica al tablero **Suministros** en las areas donde este habilitado.

Segun permisos, el usuario puede:

- Crear y consultar **Mis solicitudes**
- Revisar y aprobar en **Aprobacion insumos** (Calidad)
- Consultar **Insumos aprobados** y descargar reporte FO-AD-44 (Calidad / Compras)
- Administrar el **Catalogo** de productos

El proceso de compra con costeo unitario esta planificado para una fase posterior; hoy el flujo termina en aprobacion de Calidad.

## Definiciones

| Termino | Significado |
| --- | --- |
| Solicitud de suministro | Pedido de uno o varios productos del catalogo o fuera de catalogo. |
| Pendiente calidad | Solicitud enviada, esperando revision de Calidad. |
| Aprobada calidad | Calidad autorizo cantidades; disponible en insumos aprobados. |
| Rechazada calidad | Solicitud denegada con observaciones de Calidad. |
| Producto fuera de catalogo | Item que el solicitante agrega manualmente porque no existe en el catalogo. |
| Sede | Ubicacion fisica del solicitante; debe estar asignada en su usuario para poder pedir. |
| FO-AD-44 | Formato Excel de reporte por solicitud aprobada. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Solicitante | Armar pedido desde catalogo o items custom; enviar a Calidad. |
| Jefe / area Calidad | Revisar cantidades, aprobar o rechazar; consultar insumos aprobados. |
| Compras | Consultar insumos aprobados y descargar FO-AD-44 (fase actual). |
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

### Descargar FO-AD-44

1. Abra **Insumos aprobados**.
2. Filtre por sede, fechas o estado de exportacion.
3. Use **Descargar FO-AD-44** en la fila deseada.
4. La primera descarga marca la solicitud como exportada; puede volver a descargar.

### Administrar catalogo

1. Abra la pestaña **Catalogo**.
2. Cree o edite productos (nombre, descripcion, categoria).
3. Inactive productos que ya no deben pedirse.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
