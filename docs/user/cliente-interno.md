# Cliente interno — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/cliente-interno.md`.

## Objetivo

Apoyar a Gestión Humana en el registro y seguimiento de **solicitudes de cliente interno** (consultas o trámites de personas hacia GH): ver indicadores del periodo, cargar y actualizar solicitudes una a una o por Excel, y mantener las listas de **estados** y **tipos de solicitud**.

## Alcance

Aplica al tablero **Cliente interno** en **Gestión Humana**, con pestañas:

- **Dashboard** — total de solicitudes, conteo por estado, promedio y distribución de días de respuesta, y tendencia mensual; filtros por año y (opcional) mes.
- **Solicitudes** — listado paginado, filtros, alta/edición/eliminación, exportar a Excel e importar masivo por periodo.
- **Catálogos** — solo con permiso de catálogos: administrar **Estado** y **Solicitud** (tipos).

**En esta versión:**

- Debe existir al menos un **tipo de solicitud** en Catálogos antes de **alta manual**. En el **import masivo**, si el Excel trae tipos o estados nuevos, el sistema los crea automáticamente en Catálogos.
- Los **estados** iniciales son: Pendiente, En proceso, Respondida y Cerrada (se pueden desactivar o editar; eliminar solo si nadie los usa).
- Varios registros con la **misma cédula** están permitidos.
- Los **días de respuesta** se calculan en días hábiles (lunes a viernes), sin descontar festivos. Puede corregir el número a mano; esa corrección se conserva al guardar otros campos.
- El import masivo **reemplaza solo el año y mes que usted elige**: borra las solicitudes de ese periodo e inserta las del archivo. Filas del archivo con fecha de otro mes/año también se cargan (quedan en su mes real).
- Al eliminar un registro, se borra de forma definitiva.
- No hay enlace automático con la Ficha de empleados ni avisos por correo.

**Buenas prácticas (uso diario):**

- Antes de un import grande, puede revisar Catálogos; si faltan tipos/estados del Excel, se crearán al importar (código generado a partir del nombre).
- En exportaciones muy amplias (sin filtrar año/mes), el archivo puede tardar o fallar por tamaño; filtre el periodo cuando pueda.
- En el masivo, use siempre la plantilla oficial y confirme el conteo de filas que se van a reemplazar del periodo.

## Definiciones

| Término | Significado |
| --- | --- |
| Solicitud | Registro de un pedido o consulta de una persona (fecha, nombre, cédula, tipo, estado, etc.). |
| Tipo de solicitud (catálogo SOLICITUD) | Clasificación del trámite (se crea en Catálogos o automáticamente al importar Excel). |
| Estado | Situación del trámite (Pendiente, En proceso, Respondida, Cerrada, u otros que se agreguen). |
| Novedad | Texto libre de observación; no es una lista desplegable. |
| Días de respuesta | Cantidad de días hábiles (lun–vie) entre la fecha de solicitud y la de respuesta. Puede fijarse manualmente. |
| Año / mes | Se obtienen siempre de la fecha de solicitud; no se escriben a mano. |
| Import replace por periodo | Carga Excel que borra las solicitudes del año+mes elegido e inserta las del archivo. |
| Spillover | Filas del Excel cuya fecha cae fuera del año+mes elegido: se aceptan y quedan en su periodo real. |
| Sin estado | Solicitudes sin estado asignado; aparecen agrupadas así en el Dashboard. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + ver solicitudes) | Ver Dashboard y Solicitudes; filtrar; exportar a Excel. No crea, edita, elimina ni importa; no ve Catálogos. |
| Operativo (editar solicitudes) | Todo lo de consulta + crear/editar/eliminar solicitudes, descargar plantilla e importar masivo. |
| Solo catálogos | Ver Dashboard y administrar Catálogos (Estado y tipos de Solicitud). No ve ni modifica el listado de Solicitudes. |
| Completo | Consulta + operativo + catálogos. |
| Administración de usuarios | Asignar el tablero y los permisos de Cliente interno (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Cliente interno**.
2. Use las pestañas superiores: Dashboard, Solicitudes y (si aplica) Catálogos.
3. Si solo tiene permiso de catálogos, verá Dashboard y Catálogos; la pestaña Solicitudes no aparece.

### Preparar Catálogos (primera vez)

1. Vaya a **Catálogos**. Verá dos tarjetas: **Estados** (precargados) y **Tipos de solicitud** (vacía al inicio; muestra aviso «Sin tipos — créelos aquí»).
2. Pulse **Tipos de solicitud**. En la parte superior use código + nombre (ej. `CERTIFICADO` / `Certificado laboral`) y el botón **+**. Deje **Activo** marcado.
3. Puede cambiar entre Estados y Tipos con las pestañas del panel, o volver a las tarjetas con **Volver a catálogos**.
4. Confirme los estados (Pendiente, En proceso, Respondida, Cerrada). Para dejar de usar un ítem sin borrarlo, desactívelo. Solo elimine si el sistema indica que no hay registros que lo usen.

### Usar el Dashboard

1. Elija el **año** (y opcionalmente el **mes**).
2. Revise el total de solicitudes, el desglose por estado (incluye “Sin estado”), el promedio de días de respuesta y los gráficos.
3. La tendencia mensual muestra todo el año seleccionado aunque filtre por un mes concreto.
4. Los datos se actualizan al cambiar los filtros.

### Registrar o editar una solicitud

1. Vaya a **Solicitudes**.
2. Pulse agregar (o editar una fila).
3. Complete lo obligatorio: fecha de solicitud, nombre y apellidos, cédula y tipo de solicitud. El resto (correo, fecha de respuesta, estado, novedad, días) es opcional.
4. Si indica fecha de respuesta y no toca “días de respuesta”, el sistema calcula los días hábiles solo. Si modifica el número de días, se guarda como valor manual.
5. Guarde. El listado se actualizará.

### Filtrar, exportar y eliminar

1. Use los filtros del listado (año, mes, estado, tipo, búsqueda, etc.).
2. Para Excel, pulse exportar: saldrá lo filtrado en pantalla.
3. Para eliminar, confirme en el aviso: el registro desaparece de forma definitiva.

### Importar masivo (reemplazo por periodo)

1. En **Solicitudes**, descargue la **plantilla** de importación.
2. Llene las columnas con los encabezados exactos de la plantilla. La columna **Solicitud** puede ser un tipo ya existente (nombre o código) o uno nuevo: si no existe, se crea en Catálogos. Lo mismo aplica a **Estado** cuando viene informado.
3. Elija el **año** y el **mes** del periodo que va a reemplazar y suba el archivo.
4. Revise el **conteo** de solicitudes actuales de ese periodo que se borrarán y confirme el reemplazo.
5. Si el archivo tiene errores en filas obligatorias, **no se borra nada** del periodo: corrija y vuelva a intentar.
6. Tras un import correcto: se eliminan solo las del periodo elegido y se cargan todas las filas válidas del archivo (también las de otras fechas). El mensaje indica cuántas quedaron fuera del periodo y, si aplica, cuántos tipos/estados nuevos se crearon.

### Campos de la plantilla Excel

| Columna | Obligatoria | Notas |
| --- | --- | --- |
| Fecha de solicitud | Sí | Define el año y mes del registro |
| Nombre y apellidos | Sí | |
| Cédula | Sí | Pueden repetirse |
| Correo electrónico | No | |
| Solicitud | Sí | Si no existe en Catálogos, se crea al importar |
| Fecha de respuesta | No | |
| Estado | No | Si se llena y no existe, se crea al importar |
| Novedad | No | Texto libre |
| Días de respuesta | No | Vacío = cálculo automático; con número = valor manual |

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.2 | 2026-10-06 | Agent | Import masivo: crea automáticamente tipos de solicitud y estados del Excel si no existen en Catálogos. |
| 1.1 | 2026-10-05 | Agent | Catálogos: al abrir se muestran las tarjetas (Estados / Tipos); aviso y enlace directo desde Solicitudes si no hay tipos. |
| 1.0 | 2026-10-05 | Documentador | Version inicial FEAT-042 (Dashboard, Solicitudes, Catálogos, masivo B, días hábiles) |
