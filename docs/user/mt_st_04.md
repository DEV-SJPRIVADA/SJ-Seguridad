# MT-ST-04 — Guía de usuario

> Documentación operativa para usuarios finales. Ubicación: `docs/user/mt_st_04.md`.

## Objetivo

Controlar en la plataforma la vigencia de los **exámenes psicofísicos** (manejo de armas) y **psicosensométricos** (seguridad vial) del personal, en lugar de la matriz Excel **MT-ST-04**: ver indicadores, consultar y filtrar la matriz, exportar, y —con permiso de edición— crear o actualizar registros e importar desde Excel.

## Alcance

Aplica al tablero **MT-ST-04** en **Gestión Humana**, con dos pestañas:

- **Dashboard** — subpestañas **Psicofísicos** (examen de armas: totales, aptos, estados, tendencia de vencimientos por año y cantidad por cargo) y **Psicosensométricos** (examen vial: mismos indicadores sin contar «No aplica»). Por defecto solo personal **activo** en Ficha; se puede ampliar a desvinculados o a todos.
- **Matriz** — listado por páginas (una fila por cédula), filtros, exportación a Excel. Con permiso de edición: alta/edición/borrado, descarga de plantilla e importación.
- **Validaciones** — personal activo en Ficha que requiere psicofísicos y aún no está en la Matriz. Se puede filtrar por búsqueda, cola, **ciudad** y **cargo**, y **exportar a Excel** lo filtrado. Desde aquí se puede agregar a la matriz o marcar que no requiere el examen (también configurable en Ficha empleados).

**En esta versión:**

- Nombre, cargo, ciudad y puesto se toman de la **Ficha empleados** (solo lectura) cuando la cédula existe. La **ciudad** usa ciudad de trabajo; si está vacía, ciudad de residencia. Si no está en Ficha, igual se puede guardar/importar y la fila se marca como **Sin Ficha**.
- Las fechas de vencimiento y los estados (Vigente / Vencerá / Vencido / No aplica) los calcula el sistema; no se editan a mano.
- No hay acciones masivas sobre varias filas a la vez (solo importación o fila a fila).
- No se incluye la columna **RETIRADOS** del Excel antiguo.
- No hay pestaña de catálogos: Arma y Apto son opciones fijas Sí / No.

## Definiciones

| Término | Significado |
| --- | --- |
| MT-ST-04 | Tablero de control de exámenes psicofísicos y psicosensométricos. |
| Examen 1 (psicofísico) | Examen ligado al manejo de armas: fecha de examen, arma (Sí/No), apto (Sí/No), observaciones y estado de vigencia. |
| Examen 2 (psicosensométrico) | Examen de seguridad vial: fecha de examen, observaciones y estado de vigencia (o «No aplica» según el cargo). |
| Matriz | Pestaña del listado operativo (una persona = una cédula). |
| Dashboard | Pantalla de indicadores y gráficos del universo filtrado. |
| Vencimiento | Fecha de examen + 364 días (la calcula el sistema). |
| Vigente | El vencimiento aún no entra en la ventana de alerta ni está vencido. |
| Vencerá | Faltan 30 días o menos (incluido el día del vencimiento) hasta la fecha de vencimiento. |
| Vencido | La fecha de vencimiento ya pasó. |
| No aplica | En el examen 2, cuando el cargo en Ficha es exactamente **GUARDA** u **OPERADOR** (no aplica a cargos compuestos como «GUARDA SJ»). |
| Plantilla | Archivo Excel con las columnas correctas para cargar datos editables. |
| Importar | Cargar un Excel que crea o actualiza filas por cédula (si la cédula ya existe, se actualiza). |
| Ficha | Registro de empleado en el tablero Ficha empleados; fuente de nombre, cargo, ciudad y puesto. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Dashboard y Matriz; filtrar; exportar a Excel. No crea, edita, borra, descarga plantilla ni importa. |
| Operativo (permiso de edición) | Todo lo anterior + alta/edición/borrado de filas, consulta de datos de Ficha por cédula, plantilla e importación. |
| Administración de usuarios | Asignar el tablero y los permisos de MT-ST-04 a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **MT-ST-04**.
2. Por defecto entra al **Dashboard**.
3. Use las pestañas superiores para cambiar entre **Dashboard** y **Matriz**.

### Usar el Dashboard

1. Elija la subpestaña **Psicofísicos** o **Psicosensométricos** (como en Formación).
2. En **Psicofísicos** revise totales (vigente / vencerá / vencido), aptos y no aptos.
3. En **Psicosensométricos** revise los mismos estados: el total no incluye a quienes están en «No aplica» (ese conteo aparece aparte).
4. **Clic en un KPI** abre la **Matriz** ya filtrada con ese criterio (y con ciudad/cargo/puesto/estado ficha si los tenía aplicados).
5. En cada subpestaña revise: donut de estados, **tendencia de vencimientos por año** y **cantidad por cargo** (barras horizontales).
6. Use los filtros de **ciudad**, **cargo** y **puesto** (aplican a ambas subpestañas). Puede limpiarlos con el botón X.
7. Si necesita incluir personal desvinculado o todos, cambie el filtro de estado en Ficha.

### Consultar y filtrar la Matriz

1. Vaya a la pestaña **Matriz**.
2. Por defecto ve **activos en Ficha** y también filas **sin Ficha**. Use el filtro de estado para desvinculados, solo sin Ficha o todos.
3. Use los filtros de búsqueda (cédula o nombre), estado psicofísico, estado psicosensométrico, arma, apto y ciudad.
4. El listado se carga por páginas; no se cargan todas las filas de una vez.
5. Las columnas de nombre, cargo, ciudad y puesto reflejan lo que hay hoy en Ficha. En la tabla, el bloque **azul** es el examen psicofísico (armas) y el **verde** el psicosensométrico (vial); en Nuevo / Editar aparecen como secciones separadas.
6. Para descargar lo filtrado a Excel, use el botón de exportar.

### Crear o editar un registro (solo edición)

1. En **Matriz**, abra la opción de nuevo registro.
2. Digite la **cédula**. El sistema busca en Ficha y muestra nombre, cargo, ciudad y puesto. Si la cédula no está en Ficha, igual puede guardar: en la matriz aparecerá marcada como **Sin Ficha**.
3. Complete la sección **psicofísico (armas)** (arma, fecha, apto, observaciones) y la de **psicosensométrico (vial)** (fecha y observaciones) según corresponda.
4. Guarde. El sistema calcula solo las fechas de vencimiento y los estados.
5. Para editar, use el icono de editar en la fila. La cédula no se cambia.
6. Para eliminar, confirme el borrado (es definitivo).

### Descargar plantilla e importar (solo edición)

1. En **Matriz**, abra la opción de importación / plantilla.
2. Descargue la **plantilla** y complete las columnas (cédula, arma, fechas de examen, apto, observaciones). El nombre completo en el archivo es solo ayuda: el sistema usa Ficha.
3. No intente poner en el archivo las columnas de estado o de vencimiento: no se toman del Excel.
4. Importe el archivo. Si una cédula se repite varias veces en el mismo archivo, **gana la última fila**.
5. Filas vacías se omiten. Cédulas sin Ficha **sí se importan** y quedan marcadas como Sin Ficha en la matriz.
6. Tras un import exitoso, revise el listado y el Dashboard.

**Importante:** una misma cédula no puede tener dos filas en la matriz.

### Cómo se actualizan los estados sin editar

Cada noche el sistema recalcula vencimientos y estados con la fecha del día (horario de Colombia). También se recalculan al guardar o al importar. Si el cargo en Ficha cambia (por ejemplo de un cargo compuesto a GUARDA), el estado del examen 2 se ajusta en el próximo guardado o en el recálculo diario.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-10-09 | Documentador | Versión inicial FEAT-045: tablero MT-ST-04 (Dashboard + Matriz, CRUD, import upsert, export, sync diario de estados). |
| 1.1 | 2026-10-09 | Feature | Matriz y modales: separación visual psicofísico vs psicosensométrico. |
| 1.2 | 2026-10-09 | Feature | Pestaña Validaciones + flag «Requiere psicofísicos» en Ficha. |
| 1.3 | 2026-10-09 | Feature | Alta/import permiten cédula sin Ficha; matriz marca «Sin Ficha». |
| 1.4 | 2026-10-09 | Feature | Dashboard partido en Psicofísicos / Psicosensométricos; tendencia vencimientos por año y barras por cargo. |
| 1.5 | 2026-10-09 | Feature | Dashboard: subpestañas Psicofísicos / Psicosensométricos (chrome Formación). |
| 1.6 | 2026-10-09 | Feature | Dashboard: filtros ciudad, cargo y puesto (ambas subpestañas). |
| 1.7 | 2026-10-09 | Feature | Dashboard: KPIs clicables abren Matriz filtrada. |
| 1.8 | 2026-10-09 | Feature | Validaciones: filtros ciudad y cargo. |
| 1.9 | 2026-10-09 | Feature | Validaciones: export Excel (respeta filtros). |
