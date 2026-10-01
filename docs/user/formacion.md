# Formación — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/formacion.md`.

## Objetivo

Consultar y mantener en la plataforma el registro de formaciones que antes se manejaba en Excel: ver indicadores por año, filtrar el listado, exportar y, cuando corresponda, recargar el conjunto completo de registros desde un archivo.

## Alcance

Aplica al tablero **Formación** en **Gestión Humana**, con pestañas:

- **Dashboard** — total de registros del año, distribución por mes y por categoría; al cambiar el año se actualizan los indicadores.
- **Formaciones** — listado paginado (carga por páginas), filtros (año, mes, categoría, curso, número de ID, nombre), exportar a Excel. Con permiso de edición: descargar plantilla e importar un archivo que **reemplaza todos** los registros actuales.

**En esta versión:**

- No hay alta, edición ni borrado de filas una a una: la única forma de cambiar los datos es el import completo.
- Categoría y nombre de curso se toman tal cual del Excel (no hay catálogos internos).
- Este tablero es **independiente** del tablero **Cursos** (vigencias, Ficha empleados, etc.): son procesos distintos.
- El volumen puede ser muy alto (decenas de miles de filas); el listado se carga por páginas.

## Definiciones

| Término | Significado |
| --- | --- |
| Formación | Registro de una persona en un curso/formación (ID, nombre, fecha de inicio, curso, calificación, categoría). |
| Dashboard | Pantalla de indicadores del año seleccionado (total, por mes, por categoría). |
| Formaciones | Pestaña del listado operativo con filtros y exportación. |
| Plantilla | Archivo Excel vacío con las columnas exactas que debe tener la carga. |
| Importar (reemplazo total) | Cargar un archivo que **borra todos** los registros actuales y deja solo los del archivo nuevo. |
| Número de ID | Identificador de la persona en el Excel de formaciones. |
| Fecha de inicio del curso | Fecha a partir de la cual el sistema calcula el mes y el año del registro. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Dashboard y Formaciones; filtrar; exportar a Excel. No descarga plantilla ni importa. |
| Operativo (permiso de edición) | Todo lo anterior + descargar plantilla e importar con reemplazo total (previa confirmación). |
| Administración de usuarios | Asignar el tablero y los permisos de Formación a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Formación**.
2. Por defecto entra al **Dashboard**.
3. Use las pestañas superiores para cambiar entre **Dashboard** y **Formaciones**.

### Usar el Dashboard

1. Elija el **año** en el filtro.
2. Revise el total de registros, el gráfico o desglose por mes y el de categorías principales.
3. Los datos se recalculan al cambiar el año.

### Consultar y filtrar formaciones

1. Vaya a la pestaña **Formaciones**.
2. Use los filtros disponibles: año, mes, categoría, curso, número de ID y nombre.
3. El listado se actualiza por páginas; no se cargan todas las filas de una vez.
4. Para descargar lo filtrado a Excel, use el botón de exportar.

### Descargar plantilla e importar (solo edición)

1. En **Formaciones**, abra la opción de carga / plantilla.
2. Descargue la **plantilla** y complete las columnas con los mismos encabezados (número de ID, nombre completo, fecha de inicio del curso, nombre completo del curso, calificación, nombre de la categoría).
3. La fecha puede ir en formato de texto en español (por ejemplo *jueves, 4 de junio de 2026, 00:00*), como fecha Excel o como año-mes-día.
4. Antes de enviar, marque la casilla de **confirmación**: el sistema **eliminará todos los registros actuales** y cargará solo los del archivo.
5. Importe el archivo. Si las columnas no coinciden o hay filas con datos obligatorios inválidos, **no** se borran los datos previos y verá el mensaje de error.
6. Si el archivo es muy grande, la operación puede tardar varios minutos; evite usar el listado al mismo tiempo hasta que termine.
7. Tras un import exitoso, el listado y el Dashboard reflejan el nuevo conjunto de datos.

**Importante:** un archivo válido pero vacío (solo encabezados) puede dejar el tablero sin registros; confirme siempre antes de importar.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-10-01 | Documentador | Version inicial FEAT-041: tablero Formación (Dashboard + Formaciones, export e import con reemplazo total). |
