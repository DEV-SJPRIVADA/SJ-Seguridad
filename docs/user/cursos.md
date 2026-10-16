# Cursos — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/cursos.md`.

## Objetivo

Llevar el control de cursos de las personas (vigencia, estado de tramite y documento), con carga individual o masiva por Excel, y consulta desde la ficha del empleado.

## Alcance

Aplica al tablero **Cursos** en **Gestion Humana**, con pestanas:

- **Dashboard** — KPIs y graficos; al cambiar filtros se actualizan solos (sin boton). Incluye un grafico de cursos **por actualizar o vencidos** que aun **no estan solicitados**, desglosados por tipo. Solo considera personal **activo en Ficha**. Clic en un KPI abre **Cursos** ya filtrado (vigencia, estado o sin documento, según el indicador).
- **Cursos** — listado paginado (carga por paginas); por defecto muestra solo personal **activo en Ficha** (chip **Solo activos Ficha**, desactivable). Filtros (incluye **varias cédulas** con icono, exacto, sin historial), chips **En perfil / Sin perfil**, **Solo ACTUALIZAR**, **Sin documento**, columna **EN PERFIL**, alta/edicion/eliminacion, documento, export Excel e import masivo. Si una **cédula** aparece en más de un curso, se muestra en **rojo** en la columna Cédula. Puede registrar cursos **aunque la cédula no esté en Ficha** (manual o Excel con nombre). En alta/edicion debe seleccionar la **escuela**; el sistema guarda codigo y NIT. Con permiso de edición: selección múltiple para marcar a **SOLICITADO** (incluye «todos los del filtro actual»).
- **Validaciones** — colas de hallazgo en vivo (sin gate de reporte):
  - **Activos sin curso**: personas activas en Ficha con **Requiere cursos** activo y sin ningún registro de curso. Con edición: **Agregar curso**.
  - **Por actualizar / vencidos**: una fila por curso en ACTUALIZAR o VENCIDO de activos. Con edición: editar y marcar SOLICITADO.
  - Coexiste con la cola **Nuevos sin curso** dentro de la pestaña Cursos (pendientes al entrar a ficha; icono omitir si no aplica cursos). Al guardar un curso desde el modal debe dejar activo «Requiere cursos»; en **Ficha empleados** puede ajustarse con permiso de ficha + cursos.
- **Catalogo** — tablero de tarjetas (como en Ficha empleados): elija **Tipos de curso** o **Escuelas** (CODIGO, NIT, NOMBRE) para gestionar cada uno.

**En esta version:** el import **no** sube PDFs/imagenes; el documento se carga fila a fila. Desde **Ficha empleados** solo se consultan y descargan cursos (no se editan alla).

## Definiciones

| Termino | Significado |
| --- | --- |
| VIGENCIA | Calculada por el sistema: **VIGENTE** (curso al dia), **ACTUALIZAR** (faltan ~30 dias o menos para el año), **VENCIDO** (ya paso 1 año desde la fecha de expedicion). |
| ESTADO | Tramite: **SOLICITADO** (manual), **ACTUALIZADO** (curso vigente) o **PENDIENTE** (por actualizar o vencido, salvo que ya este solicitado). No se carga por Excel. |
| No.CURSO | Numero actual del curso (unico por cedula). En import, los digitos a la izquierda del guion identifican la escuela (ej. ECSP0015-… → codigo 15). |
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
4. Pulse un KPI para ir a la pestaña **Cursos** con el filtro correspondiente (por ejemplo Vigentes → vigencia VIGENTE; Sin documento → solo sin archivo adjunto).
5. Graficos: cursos por tipo, vigencia, estado, **por actualizar/vencidos sin solicitar** (por tipo) y tendencia mensual del año (nuevos vs actualizaciones de carga), con valores visibles en cada serie.

### Marcar varios a SOLICITADO

1. Filtre el listado (por vigencia, estado, tipo, cédula o varias cédulas con el icono de lista — exacto, máximo 500, sin historial —, etc.).
2. Marque filas con el checkbox de la izquierda, o use el checkbox del encabezado para seleccionar **todos los elegibles** del resultado filtrado (no incluye los que ya están SOLICITADO).
3. Pulse **Marcar SOLICITADO**.
4. Revise el listado del modal, lea el aviso de que **no se puede revertir** desde esta acción, marque la casilla de confirmación y ejecute.
5. Si necesita deshacer el estado, edite el registro uno a uno.

### Importar masivos (renovaciones)

1. En **Cursos**, abra el icono de carga / plantilla masivos y descargue la **plantilla vacia**.
2. Complete datos desde la fila 3. Si la **cédula está en Ficha**, el nombre se toma de ahí; si **no está en perfil**, use la columna **NOMBRE COMPLETO** del Excel (obligatoria en ese caso).
3. **Alta o correccion del mismo numero:** deje **No.CURSO ANTERIOR** vacio y llene **No.CURSO** (con formato que incluya el codigo de escuela, ej. `ECSP0015-M256412`). El sistema busca la escuela en el catalogo y guarda codigo, NIT y nombre.
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
| 1.17 | 2026-10-16 | Al eliminar un curso, documento o ítem de catálogo aparece un modal de confirmación. |
| 1.16 | 2026-10-16 | Cursos (registros): por defecto solo personal activo en Ficha (igual que Dashboard). |
| 1.15 | 2026-10-16 | Dashboard: KPIs clicables hacia Cursos con filtro; gráficos con etiquetas de valor; chips Sin documento y Solo activos Ficha. |
| 1.15 | 2026-10-16 | Registros: cédulas con más de un curso se marcan en rojo. |
| 1.14 | 2026-10-15 | Registros: columna EN PERFIL; chips En perfil/Sin perfil y Solo ACTUALIZAR; alta e import permiten cédulas sin ficha. |
| 1.13 | 2026-10-02 | Cola Nuevos sin curso: acciones de fila y volver a registros pasan a iconos. |
| 1.12 | 2026-09-29 | Nueva pestaña Validaciones: activos sin curso + por actualizar/vencidos (una fila por curso); reutiliza cursos.view/edit; coexiste con cola Nuevos sin curso. |
| 1.11 | 2026-09-29 | Registros: filtro varias cédulas (modal, exacto, max 500, sin historial); el Excel respeta ese filtro. |
| 1.10 | 2026-09-17 | Selector de escuela solo muestra nombre; import identifica escuela por No.CURSO. |
| 1.9 | 2026-09-17 | Registros: obligatoriedad de escuela; se guardan codigo y NIT. |
| 1.8 | 2026-09-17 | Catalogo: tablero de tarjetas (tipos / escuelas) como en Ficha empleados. |
| 1.7 | 2026-09-17 | Catalogo: CRUD de escuelas (codigo, NIT, nombre). |
| 1.6 | 2026-09-16 | Listado: marcar varios registros a SOLICITADO con confirmación. |
| 1.5 | 2026-09-16 | Dashboard: grafico de por actualizar/vencidos sin solicitar, por tipo. |
| 1.4 | 2026-09-16 | Estado PENDIENTE; Excel ya no pide ESTADO (se asigna segun vigencia). |
| 1.3 | 2026-09-16 | Vigencia incluye **VENCIDO** cuando ya pasó 1 año desde la fecha de expedición. |
| 1.2 | 2026-09-16 | Pestaña Dashboard con KPIs/gráficos y filtros en vivo. |
| 1.1 | 2026-09-16 | Plantilla import: No.CURSO ANTERIOR para renovar sin duplicar; cedula/nombre desde Ficha. |
| 1.0 | 2026-09-15 | FEAT-032: tablero Cursos y Catalogo. |
