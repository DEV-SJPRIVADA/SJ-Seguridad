# Acreditaciones — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/acreditaciones.md`.

## Objetivo

Apoyar a Gestión Humana en el control del personal acreditado: vigencia de la acreditación, solicitudes en trámite, estados automáticos y el catálogo de cargos (Manager, APO, Informe y Acreditación) usado para tipificar cada registro.

## Alcance

Aplica al tablero **Acreditaciones** en **Gestión Humana**, con pestañas:

- **Acreditados** — listado paginado, filtros, alta/edición/eliminación, exportar a Excel y carga masiva con plantilla.
- **Catálogo** — solo con permiso de edición: administrar las filas de cargos (Manager / APO / Informe / Acreditación).
- **Dashboard**, **Reporte Diario**, **Validaciones** y **Export Apo** — en esta versión aparecen como «Próximamente» (aún no tienen funciones operativas).

**En esta versión:**

- La cédula debe existir previamente en **Ficha empleados**; el nombre completo se toma siempre de la ficha (no se edita a mano ni se toma del Excel).
- El **tipo** de acreditación es el **CARGO APO** del catálogo. Una misma persona puede tener varias acreditaciones si el CARGO APO es distinto.
- Si se vuelve a cargar la misma cédula con el mismo CARGO APO, el sistema **actualiza** el registro existente (no duplica).
- El **estado** (ACREDITADO, EN PROCESO, POR VENCER, DESACREDITADO) lo calcula el sistema; no se elige en el formulario ni en el Excel.
- Debe indicarse al menos una fecha: vigencia de acreditación **o** fecha de solicitud.
- El campo **CARGO** es texto libre (como en el Excel); no se rellena solo desde el catálogo.
- Al eliminar un registro, se borra de forma definitiva (no queda en papelera).
- Los estados se recalculan también de forma automática cada noche según el calendario.

## Definiciones

| Término | Significado |
| --- | --- |
| Acreditado | Registro de una persona acreditada (o en trámite) para un tipo de cargo APO concreto. |
| Cédula | Número de documento. Debe existir en Ficha empleados. |
| Nombre completo | Nombre tomado de la ficha del empleado; no se escribe a mano en Acreditaciones. |
| CARGO | Texto descriptivo del cargo (formulario o Excel); independiente del catálogo en esta versión. |
| CARGO APO | Tipo de acreditación (por ejemplo VIGILANTE, ESCOLTA). Define la unicidad junto con la cédula. |
| VIGEN.ACR | Fecha de **vencimiento** de la acreditación. |
| Fecha de solicitud | Fecha en que se inició un trámite de acreditación o renovación. |
| ACREDITADO | Estado: vigencia vigente y sin solicitud en curso (más de 21 días hasta el vencimiento). |
| EN PROCESO | Estado: hay fecha de solicitud (tiene prioridad aunque la vigencia esté vencida). |
| POR VENCER | Estado: la vigencia vence en 21 días o menos (y no hay solicitud). |
| DESACREDITADO | Estado: la vigencia ya venció (y no hay solicitud). |
| Catálogo de cargos | Lista de filas Manager / APO / Informe / Acreditación que validan el CARGO APO usable. |
| Plantilla de importación | Archivo Excel vacío con columnas listas para carga masiva. |
| Export Apo | Pestaña futura; **no** es el botón de exportar el listado de Acreditados. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver Acreditados y las pestañas «Próximamente»; filtrar; exportar a Excel. No crea, edita ni elimina; no ve Catálogo ni importa. |
| Operativo (permiso de edición) | Todo lo anterior + crear/editar/eliminar acreditados, descargar plantilla, importar Excel y administrar Catálogo. |
| Administración de usuarios | Asignar el tablero y los permisos de Acreditaciones a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Acreditaciones**.
2. Por defecto entra a la pestaña **Acreditados**.
3. Use las pestañas superiores para cambiar entre Acreditados, Catálogo (si tiene edición) y las pantallas «Próximamente».

### Registrar o editar un acreditado

1. En **Acreditados**, pulse el botón para agregar (o el lápiz de una fila para editar).
2. Indique la **cédula**: el sistema busca el nombre en Ficha. Si la cédula no está en Ficha, no podrá guardar. En edición la identidad queda bloqueada; use el icono de cambiar persona si debe corregir cédula.
3. Complete el **CARGO** (texto), elija el **CARGO APO** del listado del catálogo (buscador) y, si aplica, observaciones.
4. Indique al menos una fecha: **VIGEN.ACR** y/o **fecha de solicitud**.
5. Revise el **estado** que muestra el sistema (solo lectura; se recalcula al guardar) y pulse **Guardar cambios**.
6. Si ya existía la misma cédula con el mismo CARGO APO, estará editando ese registro (no se crea otro).

### Filtrar, exportar y eliminar

1. Use los filtros (estado de acreditación, estado en ficha, cédula, cargo, CARGO APO, rango de fechas de vigencia). Por defecto solo aparecen empleados **activos en ficha**; elija «Desvinculados» o «Todos (ficha)» para ver inactivos. Los botones de filtrar, limpiar y exportar son iconos (pase el cursor para ver la ayuda).
2. Con permiso de edición puede marcar filas (o «seleccionar todos» del filtro) y usar **Actualizar seleccionados** para poner la misma observación y/o fecha de solicitud a varios a la vez. Los campos vacíos del modal no se cambian; si pone fecha de solicitud, el estado pasa a EN PROCESO.
2. Para Excel del listado, use el icono de Excel: se descarga según los filtros actuales. Incluye el estado calculado.
3. Para eliminar, confirme en el aviso: el registro desaparece de forma permanente.

### Carga masiva (importación)

1. Con permiso de edición, descargue la **plantilla** de importación.
2. Complete las filas a partir de la fila de datos (no borre los encabezados). Incluya cédula, cargo, CARGO APO y al menos una fecha. El nombre en Excel se ignora (el sistema usa Ficha). No agregue columna de estado.
3. Suba el archivo desde el modal de carga masiva.
4. Revise el resumen: filas nuevas, actualizadas y fallidas. Si hay fallos, descargue el reporte mientras esté disponible.
5. Causas frecuentes de fallo: cédula ausente en Ficha, CARGO APO no activo en catálogo, o ambas fechas vacías.

### Administrar Catálogo (solo edición)

1. Abra la pestaña **Catálogo**.
2. Cree o edite filas: CARGO MANAGER, CARGO APO, CARGO INFORME, CARGO ACREDITACIÓN, activo y orden.
3. Si un CARGO APO está en uso en acreditados, el sistema **no permite** eliminarlo cuando es la última fila activa de ese APO, ni renombrarlo. Puede desactivar la fila o ajustar Manager / Informe / Acreditación.
4. Los valores de CARGO APO activos son los que aparecen al registrar o importar acreditados.

### Cómo se calcula el estado (referencia)

Sin intervención manual:

1. Si hay **fecha de solicitud** → **EN PROCESO**.
2. Si no hay solicitud y la **vigencia ya venció** → **DESACREDITADO**.
3. Si no hay solicitud y la vigencia vence en **21 días o menos** → **POR VENCER**.
4. En cualquier otro caso con vigencia vigente → **ACREDITADO**.

El sistema también recalcula estos estados de madrugada según el calendario.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.5 | 2026-09-24 | UI | Modal «Actualizar seleccionados»: chrome unificado, preview de filas, Aplicar cambios. |
| 1.4 | 2026-09-24 | UI | Modal editar acreditado: chrome unificado, identidad bloqueable, CARGO APO searchable-select. |
| 1.3 | 2026-09-24 | Feature | Acreditados: selección masiva + modal observaciones/fecha solicitud. |
| 1.2 | 2026-09-24 | Feature | Acreditados: filtro «Estado en ficha» (por defecto solo activos; opción desvinculados/todos). |
| 1.1 | 2026-09-23 | UI | Acciones de Acreditados (filtros, export, modales) como iconos Lucide. |
| 1.0 | 2026-09-23 | Documentador | Version inicial FEAT-036: tablero Acreditaciones (Acreditados, Catálogo, import/export; placeholders Dashboard/Reporte/Validaciones/Export Apo). |
