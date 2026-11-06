# Servicio al cliente — Guía de usuario

## Objetivo

Registrar y hacer seguimiento a las peticiones, quejas, reclamos y sugerencias del formato **FO-GI-22**, ver indicadores y exportar lo filtrado a Excel.

## Alcance

Tablero **Servicio al cliente** en el área **Calidad**, con tres pestañas:

- **Dashboard** — totales, abiertos, en proceso, cerrados, vencidos y promedio de días hábiles; gráficos por tipología, categoría, estado, fuente y recurrencia del mismo cliente.
- **FO-GI-22** — listado, filtros, alta, edición, observaciones, archivos y eliminación.
- **Catálogos** — tipología, fuente, responsable y categoría. Solo quien tenga permiso de catálogos.

**En esta versión:**

- El **consecutivo** lo asigna el sistema (1, 2, 3…).
- Si el cliente es **interno**, el nombre queda **SJSEGURIDAD**. Si es **externo**, se elige de los clientes de Comercial.
- La **fecha de ejecución** es la misma de recepción.
- El **plazo de respuesta** son 10 días hábiles después de la recepción (sin contar ese día y sin festivos).
- Los **días** son los hábiles de lunes a viernes entre la recepción y la verificación, contando ambos días si son hábiles. Sin verificación, la casilla queda vacía.
- No se puede dejar el caso en **Cerrado** sin un archivo PDF, JPG o PNG.
- Las **observaciones** se agregan, corrigen o eliminan. Cada cambio queda en el log.
- En FO-GI-22, el icono de historial abre los cambios registrados del tablero.
- Eliminar un caso lo borra de verdad, pide confirmación y queda registrado en la auditoría.
- El Excel respeta los filtros activos e incluye una hoja de observaciones.
- Tipología y fuente ya vienen cargadas. Responsable y categoría se crean en Catálogos antes del primer caso.

## Definiciones

| Término | Significado |
| --- | --- |
| Caso | Un registro FO-GI-22. |
| Eficacia | Abierto, En proceso o Cerrado. |
| Vencido | El plazo ya pasó y el caso no está cerrado. |
| Recurrencia | El mismo cliente aparece más de una vez en el periodo (mes, trimestre, semestre o año). |
| Evidencia | Archivo PDF, JPG o PNG del caso. |

## Responsabilidades

| Quién | Qué hace |
| --- | --- |
| Consulta (`FO-GI-22: Ver`) | Ve el dashboard y el listado, filtra y exporta. No modifica. |
| Edición (`FO-GI-22: Editar`) | Crea, edita, comenta, adjunta y elimina casos. |
| Catálogos | Mantiene tipología, fuente, responsable y categoría. La tabla muestra el id y el nombre del selector. |
| Administrador de usuarios | Asigna el tablero y los permisos. El rol administrador los trae de base; el resto se asigna en Admin. |

## Desarrollo

1. Entre a **Calidad → Servicio al cliente**.
2. Si va a registrar casos, cree al menos una **categoría** y un **responsable** en Catálogos.
3. En **FO-GI-22**, use **Nuevo caso**. Complete recepción, tipo de cliente, ciudad, tipología, fuente, categoría, responsable y los textos.
4. Para cerrar, adjunte la evidencia en el mismo formulario o después, en la sección Adjuntos, y pase la eficacia a **Cerrado**.
5. Agregue observaciones cada vez que haya seguimiento.
6. Filtre el listado y exporte a Excel si necesita el archivo. Al entrar, el listado muestra los casos **Abiertos**. Los botones de eficacia están debajo de los demás filtros.
7. En el dashboard, elija el grano de **Recurrencia** (mes, trimestre, semestre o año) y el rango de recepción. **Abiertos**, **En proceso**, **Cerrados** y **Vencidos** abren el listado FO-GI-22 ya filtrado.

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-11-06 | Primera versión del tablero (FEAT-049). |
| 2026-11-06 | Catálogos: misma pantalla que Parámetros de requisiciones. La tabla muestra id y nombre. |
| 2026-11-06 | Nuevo caso: mismo esquema visual que Solicitar personal. |
| 2026-11-06 | El formulario no muestra ejecución ni días hábiles; esos datos quedan en el listado. |
| 2026-11-06 | Al crear un caso se vuelve al listado. Las observaciones se editan y eliminan con registro en el log. |
| 2026-11-06 | El dashboard sigue el informe de Gestión Humana. Esos cuatro indicadores abren FO-GI-22 filtrado. |
