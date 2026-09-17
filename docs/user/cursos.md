# Cursos — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/cursos.md`.

## Objetivo

Llevar el control de cursos de las personas (vigencia, estado de tramite y documento), con carga individual o masiva por Excel, y consulta desde la ficha del empleado.

## Alcance

Aplica al tablero **Cursos** en **Gestion Humana**, con pestanas:

- **Dashboard** — KPIs y graficos; al cambiar filtros se actualizan solos (sin boton). Incluye un grafico de cursos **por actualizar o vencidos** que aun **no estan solicitados**, desglosados por tipo.
- **Cursos** — listado, filtros, alta/edicion/eliminacion, documento, export Excel e import masivo. Con permiso de edición: selección múltiple para marcar a **SOLICITADO**.
- **Catalogo** — tipos de curso usados en el listado y en el Excel.

**En esta version:** el import **no** sube PDFs/imagenes; el documento se carga fila a fila. Desde **Ficha empleados** solo se consultan y descargan cursos (no se editan alla).

## Definiciones

| Termino | Significado |
| --- | --- |
| VIGENCIA | Calculada por el sistema: **VIGENTE** (curso al dia), **ACTUALIZAR** (faltan ~30 dias o menos para el año), **VENCIDO** (ya paso 1 año desde la fecha de expedicion). |
| ESTADO | Tramite: **SOLICITADO** (manual), **ACTUALIZADO** (curso vigente) o **PENDIENTE** (por actualizar o vencido, salvo que ya este solicitado). No se carga por Excel. |
| No.CURSO | Numero actual del curso (unico por cedula). |
| No.CURSO ANTERIOR | Columna opcional del Excel de import para **renovar** un curso cuando cambia el numero (y/o fecha/tipo). |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (`cursos.view` + board) | Ver listado, filtrar, exportar Excel, descargar documentos. |
| Edicion (`cursos.edit`) | Todo lo anterior + CRUD, catalogo, plantilla e import masivo, subir/quitar documento. |
| Ficha empleados (lectura) | Ver cursos del empleado y descargar documento si existe. |

## Desarrollo (uso diario)

### Dashboard

1. Entre al tablero **Cursos** (abre en Dashboard).
2. Use filtros: rango de fecha de expedicion, tipo, vigencia, estado y año de tendencia.
3. Los indicadores y graficos se recalculan al cambiar un filtro.
4. Graficos: cursos por tipo, vigencia, estado, **por actualizar/vencidos sin solicitar** (por tipo) y tendencia mensual del año (nuevos vs actualizaciones de carga).

### Marcar varios a SOLICITADO

1. Filtre el listado (por vigencia, estado, tipo, etc.).
2. Marque filas con el checkbox de la izquierda, o use el checkbox del encabezado para seleccionar **todos los elegibles** del resultado filtrado (no incluye los que ya están SOLICITADO).
3. Pulse **Marcar SOLICITADO**.
4. Revise el listado del modal, lea el aviso de que **no se puede revertir** desde esta acción, marque la casilla de confirmación y ejecute.
5. Si necesita deshacer el estado, edite el registro uno a uno.

### Importar masivos (renovaciones)

1. En **Cursos**, abra el icono de carga / plantilla masivos y descargue la **plantilla vacia**.
2. Complete datos desde la fila 3. La **cedula debe existir en Ficha**; el nombre del Excel se **ignora** (se toma de Ficha).
3. **Alta o correccion del mismo numero:** deje **No.CURSO ANTERIOR** vacio y llene **No.CURSO**.
4. **Renovacion** (cambia No.CURSO, fecha y a veces tipo, p. ej. Fundamentacion → Reentrenamiento):
   - En **No.CURSO ANTERIOR** ponga el numero que hoy tiene el registro vencido.
   - En **No.CURSO** ponga el numero nuevo.
   - El sistema busca cedula + numero anterior y **actualiza esa fila** (no crea otra).
   - Si no encuentra el anterior, la fila **falla** y **no** se crea duplicado.
   - Si el numero nuevo ya lo tiene otro curso de la misma persona, la fila **falla**.
5. Importe el archivo; revise el resumen y, si hay errores, el reporte Excel.

### Exportar

El icono Excel del listado exporta segun los filtros actuales (incluye vigencia). Esa exportacion **no** incluye la columna de renovacion (solo sirve en la plantilla de import).

## Control de cambios

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.6 | 2026-09-16 | Listado: marcar varios registros a SOLICITADO con confirmación. |
| 1.5 | 2026-09-16 | Dashboard: grafico de por actualizar/vencidos sin solicitar, por tipo. |
| 1.4 | 2026-09-16 | Estado PENDIENTE; Excel ya no pide ESTADO (se asigna segun vigencia). |
| 1.3 | 2026-09-16 | Vigencia incluye **VENCIDO** cuando ya pasó 1 año desde la fecha de expedición. |
| 1.2 | 2026-09-16 | Pestaña Dashboard con KPIs/gráficos y filtros en vivo. |
| 1.1 | 2026-09-16 | Plantilla import: No.CURSO ANTERIOR para renovar sin duplicar; cedula/nombre desde Ficha. |
| 1.0 | 2026-09-15 | FEAT-032: tablero Cursos y Catalogo. |
