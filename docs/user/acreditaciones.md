# Acreditaciones — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/acreditaciones.md`.

## Objetivo

Apoyar a Gestión Humana en el control del personal acreditado: vigencia de la acreditación, solicitudes en trámite, estados automáticos, el catálogo de cargos (Manager, APO, Informe y Acreditación) y el **histórico diario** de los Excel que entrega la APO (personas en proceso y acreditadas).

## Alcance

Aplica al tablero **Acreditaciones** en **Gestión Humana**, con pestañas:

- **Acreditados** — listado paginado, filtros, alta/edición/eliminación, exportar a Excel y carga masiva con plantilla.
- **Reporte Diario** — carga diaria de uno o dos Excel de la APO (En proceso y/o Acreditados APO), consulta por fecha, filtros, exportar e histórico de cargas. Es un **archivo histórico** de lo que envió la APO ese día; **no** actualiza ni cruza con el listado de Acreditados ni con Ficha.
- **Catálogo** — solo con permiso de edición: administrar las filas de cargos (Manager / APO / Informe / Acreditación).
- **Dashboard**, **Validaciones** y **Export Apo** — en esta versión aparecen como «Próximamente» (aún no tienen funciones operativas). El cruce con Ficha o con Acreditados queda para **Validaciones** (futuro).

**En esta versión (Acreditados):**

- La cédula debe existir previamente en **Ficha empleados**; el nombre completo se toma siempre de la ficha (no se edita a mano ni se toma del Excel).
- El **tipo** de acreditación es el **CARGO APO** del catálogo. Una misma persona puede tener varias acreditaciones si el CARGO APO es distinto.
- Si se vuelve a cargar la misma cédula con el mismo CARGO APO, el sistema **actualiza** el registro existente (no duplica).
- El **estado** (ACREDITADO, EN PROCESO, POR VENCER, DESACREDITADO) lo calcula el sistema; no se elige en el formulario ni en el Excel.
- Debe indicarse al menos una fecha: vigencia de acreditación **o** fecha de solicitud.
- El campo **CARGO** es texto libre (como en el Excel); no se rellena solo desde el catálogo.
- Al eliminar un registro, se borra de forma definitiva (no queda en papelera).
- Los estados se recalculan también de forma automática cada noche según el calendario.

**En esta versión (Reporte Diario):**

- Se pueden subir **uno o los dos** archivos en la misma operación (En proceso, Acreditados APO, o ambos).
- La **fecha del reporte** es la del día del Excel APO (hoy o hacia atrás; no se permiten fechas futuras). Por defecto es hoy.
- Si esa fecha y ese origen ya tenían datos, el sistema pide **confirmar el reemplazo**. Al reemplazar solo se pisa el origen que se vuelve a subir; el otro se conserva.
- No hace falta que la cédula exista en Ficha. En **En proceso** el Estado del Excel se guarda tal cual. En **Acreditado APO**, si la fila trae fecha Vigen.Acr, el Estado (APO) queda como **ACREDITADO** (no se usa la calculadora del módulo Acreditados).
- No hay versiones del mismo día: la última carga de ese origen **reemplaza** la anterior.

## Definiciones

| Término | Significado |
| --- | --- |
| Acreditado | Registro de una persona acreditada (o en trámite) para un tipo de cargo APO concreto. |
| Cédula | Número de documento. En Acreditados debe existir en Ficha empleados. |
| Nombre completo | En Acreditados: nombre tomado de la ficha. En Reporte Diario: nombre armado desde el Excel APO. |
| CARGO | Texto descriptivo del cargo (formulario o Excel); independiente del catálogo en esta versión. |
| CARGO APO | Tipo de acreditación (por ejemplo VIGILANTE, ESCOLTA). Define la unicidad junto con la cédula en Acreditados. |
| VIGEN.ACR | Fecha de **vencimiento** de la acreditación (Acreditados o columna del Excel Acreditados APO). |
| Fecha de solicitud | Fecha en que se inició un trámite de acreditación o renovación. |
| Renovaciones | Campo manual del trámite de renovación: **Solicitado** o **Renovado** (opcional). |
| ACREDITADO | Estado calculado en Acreditados: vigencia vigente y sin solicitud en curso (más de 21 días hasta el vencimiento). |
| EN PROCESO | Estado calculado en Acreditados: hay fecha de solicitud (tiene prioridad aunque la vigencia esté vencida). |
| POR VENCER | Estado calculado en Acreditados: la vigencia vence en 21 días o menos (y no hay solicitud). |
| DESACREDITADO | Estado calculado en Acreditados: la vigencia ya venció (y no hay solicitud). |
| Catálogo de cargos | Lista de filas Manager / APO / Informe / Acreditación que validan el CARGO APO usable. |
| Plantilla de importación | Archivo Excel vacío con columnas listas para carga masiva de Acreditados. |
| Reporte Diario | Histórico de snapshots diarios de los Excel que entrega la APO (En proceso y Acreditados APO). |
| Fecha de reporte | Día al que pertenece el Excel APO (no necesariamente el día en que se subió el archivo). |
| Origen | Tipo de archivo del snapshot: **En proceso** o **Acreditado APO**. |
| Reemplazo | Volver a cargar un origen en una fecha que ya tenía datos; borra la versión anterior de ese origen. |
| Export Apo | Pestaña futura; **no** es el botón de exportar el listado de Acreditados ni el de Reporte Diario. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Acreditados, Reporte Diario (filtrar, exportar, ver listado de cargas) y las pestañas «Próximamente». No crea, edita ni elimina; no ve Catálogo; no importa ni carga reportes. |
| Operativo (permiso de edición) | Todo lo anterior + crear/editar/eliminar acreditados, descargar plantilla, importar Excel de Acreditados, administrar Catálogo y **cargar o reemplazar** los Excel del Reporte Diario. |
| Administración de usuarios | Asignar el tablero y los permisos de Acreditaciones a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Acreditaciones**.
2. Por defecto entra a la pestaña **Acreditados**.
3. Use las pestañas superiores para cambiar entre Acreditados, **Reporte Diario**, Catálogo (si tiene edición) y las pantallas «Próximamente» (Dashboard, Validaciones, Export Apo).

### Registrar o editar un acreditado

1. En **Acreditados**, pulse el botón para agregar (o el lápiz de una fila para editar).
2. Indique la **cédula**: el sistema busca el nombre en Ficha. Si la cédula no está en Ficha, no podrá guardar. En edición la identidad queda bloqueada; use el icono de cambiar persona si debe corregir cédula.
3. Complete el **CARGO** (texto), elija el **CARGO APO** del listado del catálogo (buscador) y, si aplica, observaciones.
4. Indique al menos una fecha: **VIGEN.ACR** y/o **fecha de solicitud**.
5. Revise el **estado** que muestra el sistema (solo lectura; se recalcula al guardar) y pulse **Guardar cambios**.
6. Si ya existía la misma cédula con el mismo CARGO APO, estará editando ese registro (no se crea otro).

### Filtrar, exportar y eliminar (Acreditados)

1. Use los filtros (estado de acreditación, estado en ficha, cédula, cargo, CARGO APO, rango de fechas de vigencia). Por defecto solo aparecen empleados **activos en ficha**; elija «Desvinculados» o «Todos (ficha)» para ver inactivos. Los botones de filtrar, limpiar y exportar son iconos (pase el cursor para ver la ayuda).
2. Con permiso de edición puede marcar filas (o «seleccionar todos» del filtro) y usar **Actualizar seleccionados** para poner la misma observación y/o fecha de solicitud a varios a la vez. Los campos vacíos del modal no se cambian; si pone fecha de solicitud, el estado pasa a EN PROCESO.
3. Para Excel del listado, use el icono de Excel: se descarga según los filtros actuales. Incluye el estado calculado.
4. Para eliminar, confirme en el aviso: el registro desaparece de forma permanente.

### Carga masiva de Acreditados (importación)

1. Con permiso de edición, descargue la **plantilla** de importación.
2. Complete las filas a partir de la fila de datos (no borre los encabezados). Incluya cédula, cargo y CARGO APO. En **VIGEN.ACR** puede ir una fecha, quedar vacío o decir «en proceso» (queda sin vigencia y el estado pasa a **EN PROCESO**). El nombre en Excel se ignora (el sistema usa Ficha). No agregue columna de estado.
3. Suba el archivo desde el modal de carga masiva.
4. Revise el resumen: filas nuevas, actualizadas y fallidas. Si hay fallos, descargue el reporte mientras esté disponible.
5. Causas frecuentes de fallo: cédula ausente en Ficha, CARGO APO no activo en catálogo, o texto inválido en VIGEN.ACR (que no sea fecha ni «en proceso»).

### Cargar el Reporte Diario (Excel APO)

1. Abra la pestaña **Reporte Diario**. Por defecto verá las filas del **día de hoy** (si aún no hay carga, la tabla estará vacía).
2. Con permiso de edición, pulse la acción para **cargar reporte**.
3. Elija la **fecha del reporte** (hoy o un día anterior; no se admite futuro).
4. Adjunte el archivo de **En proceso**, el de **Acreditados APO**, o ambos. Debe subir al menos uno.
5. Confirme el envío. Al terminar verá un resumen (filas correctas y con error por cada origen). Si hay fallos, puede descargar el reporte de errores mientras esté disponible.
6. Si la fecha ya tenía datos, el modal muestra el aviso **antes** de enviar: marque **Confirmar reemplazo**, elija el/los Excel y pulse Cargar. Si envía sin confirmar, el navegador bloquea el envío (sin perder los archivos); marque el check y vuelva a pulsar Cargar. Solo si la página se recarga por un error del servidor deberá volver a elegir los archivos.
7. Las filas sin número de documento (IdNum) u otros errores de fila no detienen toda la carga: se omiten y aparecen en el reporte de fallos. Si los encabezados del Excel no coinciden con lo esperado, **no se guarda** ningún cambio de esa carga (debe corregir el archivo).
8. No es necesario que la persona exista en Ficha. Este listado **no** alimenta la pestaña Acreditados.

### Consultar histórico, filtrar y exportar (Reporte Diario)

1. Cambie la **fecha** del filtro para ver otro día ya cargado.
2. Filtre por **origen** (Todos, En proceso o Acreditado APO) y use la búsqueda por cédula, nombre o cargo.
3. Use **Ver cargas** para ver el historial de fechas cargadas (quién cargó cada origen, cuándo y cuántas filas). Al elegir una fecha se muestra su listado.
4. Exporte a Excel con el icono correspondiente: se descarga según los filtros activos (fecha, origen y búsqueda).

### Administrar Catálogo (solo edición)

1. Abra la pestaña **Catálogo**.
2. Cree o edite filas: CARGO MANAGER, CARGO APO, CARGO INFORME, CARGO ACREDITACIÓN, activo y orden.
3. Si un CARGO APO está en uso en acreditados, el sistema **no permite** eliminarlo cuando es la última fila activa de ese APO, ni renombrarlo. Puede desactivar la fila o ajustar Manager / Informe / Acreditación.
4. Los valores de CARGO APO activos son los que aparecen al registrar o importar acreditados.

### Cómo se calcula el estado (referencia — solo Acreditados)

Sin intervención manual:

1. Si hay **fecha de solicitud** → **EN PROCESO**.
2. Si no hay vigencia (**VIGEN.ACR** vacío) → **EN PROCESO**.
3. Si no hay solicitud y la **vigencia ya venció** → **DESACREDITADO**.
4. Si no hay solicitud y la vigencia vence en **21 días o menos** → **POR VENCER**.
5. En cualquier otro caso con vigencia vigente → **ACREDITADO**.

El sistema también recalcula estos estados de madrugada según el calendario.  
En **Reporte Diario** el estado o la vigencia del Excel se muestran tal como vienen de la APO; no pasan por este cálculo.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.8 | 2026-09-24 | Documentador | FEAT-037: Reporte Diario APO (carga 1–2 Excel, replace parcial, histórico, filtros y export). Placeholders restantes: Dashboard, Validaciones, Export Apo. |
| 1.7 | 2026-09-24 | Feature | Import masivo: VIGEN.ACR vacío o «en proceso» carga con vigencia vacía y estado EN PROCESO. |
| 1.6 | 2026-09-24 | Feature | Acreditados: columna/filtro RENOVACIONES (Solicitado / Renovado); orden FECHA SOLICITUD → OBSERVACIONES; también en Actualizar seleccionados. |
| 1.5 | 2026-09-24 | UI | Modal «Actualizar seleccionados»: chrome unificado, preview de filas, Aplicar cambios. |
| 1.4 | 2026-09-24 | UI | Modal editar acreditado: chrome unificado, identidad bloqueable, CARGO APO searchable-select. |
| 1.3 | 2026-09-24 | Feature | Acreditados: selección masiva + modal observaciones/fecha solicitud. |
| 1.2 | 2026-09-24 | Feature | Acreditados: filtro «Estado en ficha» (por defecto solo activos; opción desvinculados/todos). |
| 1.1 | 2026-09-23 | UI | Acciones de Acreditados (filtros, export, modales) como iconos Lucide. |
| 1.0 | 2026-09-23 | Documentador | Version inicial FEAT-036: tablero Acreditaciones (Acreditados, Catálogo, import/export; placeholders Dashboard/Reporte/Validaciones/Export Apo). |
