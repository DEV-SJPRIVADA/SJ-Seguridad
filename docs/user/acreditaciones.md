# Acreditaciones — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/acreditaciones.md`.

## Objetivo

Apoyar a Gestión Humana en el control del personal acreditado: vigencia de la acreditación, solicitudes en trámite, estados automáticos, el catálogo de cargos (Manager, APO, Informe y Acreditación), el **histórico diario** de los Excel que entrega la APO, las **validaciones** que cruzan Ficha activa, Acreditados y el reporte del día, la generación del archivo **APO SuperVigilancia** para envío externo (**Export Apo**) y la consulta de indicadores en el **Dashboard**.

## Alcance

Aplica al tablero **Acreditaciones** en **Gestión Humana**, con pestañas:

- **Dashboard** — lectura: KPIs por estado, filtros (fecha solicitud, cargo APO, estado en ficha, año) y gráficos. Por defecto solo empleados **activos en ficha**. Los filtros actualizan KPIs y gráficos.
- **Acreditados** — listado paginado, filtros, alta/edición/eliminación, exportar a Excel y carga masiva con plantilla.
- **Reporte Diario** — carga diaria de uno o dos Excel de la APO (En proceso y/o Acreditados APO), consulta por fecha, filtros, exportar e histórico de cargas. Es un **archivo histórico** de lo que envió la APO ese día; **no** actualiza por sí solo el listado de Acreditados ni Ficha.
- **Validaciones** — solo con permiso de edición: elige una fecha de reporte, comprueba que ese día tenga **ambos** Excel APO cargados, ejecuta el cruce y obtiene **cuatro colas** para actuar (abrir Ficha, editar o crear acreditado) y exportar. Los resultados viven **solo en pantalla** de esa corrida (no hay histórico de validaciones guardado).
- **Export Apo** — solo con permiso de edición: al entrar la pantalla está vacía; con **Validar** carga el preview SuperVigilancia (columnas del archivo + Valida/motivo), selecciona filas, puede **quitar** filas del preview, decide si incluir novedades leves y descarga el `.xls`. También puede llegar desde Acreditados o Validaciones con filas marcadas (botón **Cargar en Export Apo**).
- **Catálogo** — solo con permiso de edición: administrar las filas de cargos (Manager / APO / Informe / Acreditación) y los **parámetros de empresa** usados en Export Apo (Nit, razón social, direcciones, etc.; una sola fila editable).

**En esta versión (Acreditados):**

- La cédula debe existir previamente en **Ficha empleados**; el nombre completo se toma siempre de la ficha (no se edita a mano ni se toma del Excel).
- El **tipo** de acreditación es el **CARGO APO** del catálogo. Una misma persona puede tener varias acreditaciones si el CARGO APO es distinto.
- Si se vuelve a cargar la misma cédula con el mismo CARGO APO, el sistema **actualiza** el registro existente (no duplica).
- El **estado** (ACREDITADO, EN PROCESO, POR VENCER, DESACREDITADO) lo calcula el sistema; no se elige en el formulario ni en el Excel.
- Debe indicarse al menos una fecha: vigencia de acreditación **o** fecha de solicitud.
- El campo **CARGO** se toma del cargo en **Ficha**; no se edita a mano ni se toma del Excel de importación.
- Al eliminar un registro, se borra de forma definitiva (no queda en papelera).
- Los estados se recalculan también de forma automática cada noche según el calendario.

**En esta versión (Reporte Diario):**

- Se pueden subir **uno o los dos** archivos en la misma operación (En proceso, Acreditados APO, o ambos).
- La **fecha del reporte** es la del día del Excel APO (hoy o hacia atrás; no se permiten fechas futuras). Por defecto es hoy.
- Si esa fecha y ese origen ya tenían datos, el sistema pide **confirmar el reemplazo**. Al reemplazar solo se pisa el origen que se vuelve a subir; el otro se conserva.
- No hace falta que la cédula exista en Ficha. En **En proceso** el Estado del Excel se guarda tal cual. En **Acreditado APO**, si la fila trae fecha Vigen.Acr, el Estado (APO) queda como **ACREDITADO**.
- No hay versiones del mismo día: la última carga de ese origen **reemplaza** la anterior.

**En esta versión (Validaciones):**

- Solo quien tiene permiso de **edición** ve y usa esta pestaña (igual que Catálogo y Export Apo).
- Antes de ejecutar, el día elegido debe tener cargados **En proceso** y **Acreditado APO** en Reporte Diario. Si falta uno, el sistema lo indica y no corre el cruce.
- Hay que pulsar **Ejecutar validaciones**; no se corre solo al abrir la pantalla ni al cambiar la fecha.
- Las cuatro colas muestran pendientes de cruce y de vigencia; desde ellas se puede abrir Ficha (si tiene permiso de Ficha), editar un acreditado o crear uno con la cédula ya cargada.
- Se puede exportar cada cola o un Excel consolidado (una hoja por cola). Si sale y vuelve, o pasa mucho tiempo, debe volver a ejecutar.

**En esta versión (Export Apo):**

- Solo con permiso de **edición**. Quien solo consulta **no** ve esta pestaña.
- Candidatos: acreditados en **EN PROCESO**, **POR VENCER** o **DESACREDITADO** con Ficha **activa**. No incluye acreditados «frescos» (ACREDITADO fuera de ventana por vencer).
- Una misma cédula con dos cargos candidatos aparece como **dos filas**; usted elige cuáles exportar.
- Antes de descargar: **vista previa** con Valida / motivo. El archivo descargado **no** lleva esas columnas.
- Puede elegir política de cursos: solo vigentes, o vigentes más los que están por actualizar.
- Si hay novedades leves (curso faltante, código vacío, escuela no catalogada, etc.), un aviso pregunta si las **incluye** o no. Las filas con ficha incompleta **nunca** salen en el archivo, aunque diga que sí.
- El nombre del archivo sigue el patrón `APO` + Nit de parámetros + fecha + número del día (001, 002…).
- En el archivo, el género se envía como **1** (masculino) o **2** (femenino), según el sexo de la Ficha.
- Los códigos de curso APO deben estar cargados en el catálogo de **Cursos** (campo CURSOS / CódigoCurso) y alineados al cargo.

**En esta versión (Dashboard):**

- Visible con permiso de **consulta**. Muestra KPIs por estado, filtros y gráficos (estado, cargo APO, tendencia). No permite generar ni editar desde aquí.

## Definiciones

| Término | Significado |
| --- | --- |
| Acreditado | Registro de una persona acreditada (o en trámite) para un tipo de cargo APO concreto. |
| Cédula | Número de documento. En Acreditados debe existir en Ficha empleados. |
| Nombre completo | En Acreditados: nombre tomado de la ficha. En Reporte Diario: nombre armado desde el Excel APO. |
| CARGO | Cargo del empleado en Ficha. Se completa al buscar la cédula; en import se ignora la columna Excel. |
| CARGO APO | Tipo de acreditación (por ejemplo VIGILANTE, ESCOLTA). Define la unicidad junto con la cédula en Acreditados. En Validaciones es el valor que se compara con el **Cargo** del Excel APO. En Export Apo define el match con el curso F/R del mismo cargo. |
| VIGEN.ACR | Fecha de **vencimiento** de la acreditación (Acreditados o columna del Excel Acreditados APO). |
| Fecha de solicitud | Fecha en que se inició un trámite de acreditación o renovación. |
| Renovaciones | Campo manual del trámite de renovación: **Solicitado** o **Renovado** (opcional). |
| ACREDITADO | Estado calculado en Acreditados: vigencia vigente y sin solicitud en curso (más de 21 días hasta el vencimiento). |
| EN PROCESO | Estado calculado en Acreditados: hay fecha de solicitud (tiene prioridad aunque la vigencia esté vencida). |
| POR VENCER | Estado calculado en Acreditados: la vigencia vence en 21 días o menos (y no hay solicitud). |
| DESACREDITADO | Estado calculado en Acreditados: la vigencia ya venció (y no hay solicitud). |
| Catálogo de cargos | Lista de filas Manager / APO / Informe / Acreditación que validan el CARGO APO usable. |
| Parámetros Export Apo | Datos de empresa (Nit, razón social, teléfono, direcciones, etc.) que se repiten en todas las filas del archivo SuperVigilancia. Se editan en Catálogo. |
| Plantilla de importación | Archivo Excel vacío con columnas listas para carga masiva de Acreditados. |
| Reporte Diario | Histórico de snapshots diarios de los Excel que entrega la APO (Enproceso y Acreditados APO). |
| Fecha de reporte | Día al que pertenece el Excel APO (no necesariamente el día en que se subió el archivo). También es la fecha que se valida en Validaciones. |
| Origen | Tipo de archivo del snapshot: **Enproceso** o **Acreditado APO**. |
| Reemplazo | Volver a cargar un origen en una fecha que ya tenía datos; borra la versión anterior de ese origen. |
| Validaciones | Cruce operativo entre Ficha activa, Acreditados y el Reporte Diario de un día. |
| Gate / condición de carga | Requisito de que ese día tenga **ambos** orígenes APO cargados antes de poder ejecutar. |
| Cola | Listado de resultados de una validación (hay cuatro tipos). |
| Ficha activa sin acreditación | Empleado activo en Ficha que no tiene ninguna acreditación registrada. |
| Ausente del reporte | Acreditado cuyo par cédula + CARGO APO no aparece en el reporte del día (ni en Enproceso ni en Acreditado APO). |
| EN PROCESO ya acreditado en APO | Acreditado en estado EN PROCESO en el sistema cuyo par sí figura como Acreditado APO en el reporte del día. |
| Vencidas / por vencer | Acreditados en DESACREDITADO o POR VENCER (según el estado del sistema; no depende del reporte). |
| Export Apo | Generación del archivo SuperVigilancia (`.xls`) a partir de candidatos seleccionados. **No** es el botón de exportar el listado de Acreditados, el de Reporte Diario ni el de Validaciones. |
| Vista previa (preview) | Revisión en pantalla de Valida / motivo **antes** de descargar el `.xls`. |
| Novedad blanda | Problema corregible o aceptable (sin curso, código vacío, etc.). El aviso puede permitir incluirla en el archivo. |
| Novedad dura / bloqueo | Ficha incompleta (faltan datos de identidad obligatorios). Esa fila **nunca** se exporta. |
| Política de vigencia (cursos) | Criterio para aceptar el curso: solo **VIGENTE**, o **VIGENTE + ACTUALIZAR**. |
| CódigoCurso | Código APO del tipo de curso (campo CURSOS en Catálogo Cursos). |
| Dashboard | Pantalla de lectura con KPIs, filtros y gráficos de acreditados. |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Consulta (ver tablero + permiso de consulta) | Ver **Dashboard**, Acreditados y Reporte Diario (filtrar, exportar, ver listado de cargas). No crea, edita ni elimina; no ve Catálogo, **Validaciones** ni **Export Apo**; no importa ni carga reportes. |
| Operativo (permiso de edición) | Todo lo anterior + crear/editar/eliminar acreditados, descargar plantilla, importar Excel de Acreditados, administrar Catálogo (cargos y parámetros Export Apo), **cargar o reemplazar** los Excel del Reporte Diario, **usar Validaciones** y **generar Export Apo** (Validar, modal de novedades, descarga). |
| Con permiso de gestión en Ficha | Además, desde Validaciones puede usar **Abrir Ficha** cuando la cédula está vinculada a una ficha. Sin ese permiso la cola se ve igual, pero la acción no aparece. |
| Administración de usuarios | Asignar el tablero y los permisos de Acreditaciones (y Ficha, si aplica) a quienes correspondan (no vienen por defecto al rol usuario/administrador). |

## Desarrollo

### Entrar al tablero

1. En el menú de **Gestión Humana**, abra **Acreditaciones**.
2. Por defecto entra a la pestaña **Acreditados**.
3. Use las pestañas superiores: **Dashboard** (consulta), Acreditados, **Reporte Diario**, y —si tiene edición— **Validaciones**, **Export Apo** y **Catálogo**.

### Consultar el Dashboard

1. Abra la pestaña **Dashboard**.
2. Use los filtros (fecha de solicitud, cargo APO, estado en ficha, año de tendencia) si quiere acotar el universo. Por defecto solo se cuentan empleados **activos en ficha** (alineado con Acreditados/export); elija «Desvinculados» o «Todos (ficha)» para ampliar.
3. Revise los KPIs por estado y los gráficos (incluye tendencia de solicitudes y de vencimientos VIGEN.ACR); al cambiar un filtro se actualizan juntos.
4. Esta pantalla es solo lectura; para generar el archivo use **Export Apo**.

### Registrar o editar un acreditado

1. En **Acreditados**, pulse el botón para agregar (o el lápiz de una fila para editar).
2. Indique la **cédula**: el sistema busca el nombre en Ficha. Si la cédula no está en Ficha, no podrá guardar. En edición la identidad queda bloqueada; use el icono de cambiar persona si debe corregir cédula.
3. El **CARGO** se completa solo desde Ficha (solo lectura). Elija el **CARGO APO** del listado del catálogo (buscador) y, si aplica, observaciones.
4. Indique al menos una fecha: **VIGEN.ACR** y/o **fecha de solicitud**.
5. Revise el **estado** que muestra el sistema (solo lectura; se recalcula al guardar) y pulse **Guardar cambios**.
6. Si ya existía la misma cédula con el mismo CARGO APO, estará editando ese registro (no se crea otro).

### Filtrar, exportar y eliminar (Acreditados)

1. Use los filtros (estado de acreditación, estado en ficha, cédula, **varias cédulas** con el icono de lista, cargo, CARGO APO, rango de fechas de vigencia). Por defecto solo aparecen empleados **activos en ficha**; elija «Desvinculados» o «Todos (ficha)» para ver inactivos. El filtro de varias cédulas es exacto (coma, salto de línea o `;`, máximo 500) y **no** guarda historial. Los botones de filtrar, limpiar y exportar son iconos (pase el cursor para ver la ayuda).
2. Con permiso de edición puede marcar filas (o «seleccionar todos» del filtro, todas las páginas) y usar **Actualizar seleccionados** para poner la misma observación y/o fecha de solicitud a varios a la vez. Los campos vacíos del modal no se cambian; si pone fecha de solicitud, el estado pasa a EN PROCESO.
3. Con filas marcadas, el botón icono **Cargar en Export Apo** abre Export Apo y valida solo esos registros (aunque no estén en el universo habitual: aparecerán bloqueados con motivo).
4. Use **Nuevo** o **Editar** para un registro puntual. El estado se calcula solo; no se elige a mano.
5. Para Excel del listado, use el icono de Excel: se descarga según los filtros actuales. Incluye el estado calculado.
6. Para eliminar, confirme en el aviso: el registro desaparece de forma permanente.

### Carga masiva de Acreditados (importación)

1. Con permiso de edición, descargue la **plantilla** de importación.
2. Complete las filas a partir de la fila de datos (no borre los encabezados). Incluya cédula y CARGO APO. En **VIGEN.ACR** puede ir una fecha, quedar vacío o decir «en proceso» (queda sin vigencia y el estado pasa a **EN PROCESO**). El nombre y el **CARGO** en Excel se ignoran (el sistema usa Ficha). No agregue columna de estado.
3. Suba el archivo desde el modal de carga masiva.
4. Revise el resumen: filas nuevas, actualizadas y fallidas. Si hay fallos, descargue el reporte mientras esté disponible.
5. Causas frecuentes de fallo: cédula ausente en Ficha, ficha sin cargo, CARGO APO no activo en catálogo, o texto inválido en VIGEN.ACR (que no sea fecha ni «enproceso»).

### Cargar el Reporte Diario (Excel APO)

1. Abra la pestaña **Reporte Diario**. Por defecto verá las filas del **día de hoy** (si aún no hay carga, la tabla estará vacía).
2. Con permiso de edición, pulse la acción para **cargar reporte**.
3. Elija la **fecha del reporte** (hoy o un día anterior; no se admite futuro).
4. Adjunte el archivo de **Enproceso**, el de **Acreditados APO**, o ambos. Debe subir al menos uno.
5. Confirme el envío. Al terminar verá un resumen (filas correctas y con error por cada origen). Si hay fallos, puede descargar el reporte de errores mientras esté disponible.
6. Si la fecha ya tenía datos, el modal muestra el aviso **antes** de enviar: marque **Confirmar reemplazo**, elija el/los Excel y pulse Cargar. Si envía sin confirmar, el navegador bloquea el envío (sin perder los archivos); marque el check y vuelva a pulsar Cargar. Solo si la página se recarga por un error del servidor deberá volver a elegir los archivos.
7. Las filas sin número de documento (IdNum) u otros errores de fila no detienen toda la carga: se omiten y aparecen en el reporte de fallos. Si los encabezados del Excel no coinciden con lo esperado, **no se guarda** ningún cambio de esa carga (debe corregir el archivo).
8. No es necesario que la persona exista en Ficha. Este listado **no** alimenta la pestaña Acreditados por sí solo; el cruce se hace en **Validaciones**.

### Consultar histórico, filtrar y exportar (Reporte Diario)

1. Cambie la **fecha** del filtro para ver otro día ya cargado.
2. Filtre por **origen** (Todos, Enproceso o Acreditado APO) y use la búsqueda por cédula, nombre o cargo.
3. Use **Ver cargas** para ver el historial de fechas cargadas (quién cargó cada origen, cuándo y cuántas filas). Al elegir una fecha se muestra su listado.
4. Exporte a Excel con el icono correspondiente: se descarga según los filtros activos (fecha, origen y búsqueda).

### Ejecutar Validaciones

1. Con permiso de edición, abra la pestaña **Validaciones**.
2. Elija la **fecha de reporte** (por defecto hoy; no se admite futuro).
3. El sistema indica si ese día tiene **ambos** orígenes APO cargados:
   - Si no hay carga o falta Enproceso / Acreditado APO, verá el mensaje y un enlace a **Reporte Diario**. Complete la carga allí y vuelva.
   - Si ambos están cargados, podrá usar **Ejecutar validaciones**.
4. Pulse **Ejecutar validaciones**. No se ejecuta solo al entrar ni al cambiar la fecha; al cambiar la fecha se limpian los resultados hasta una nueva ejecución.
5. Tras ejecutar verá el resumen de conteos y las **cuatro colas**. Cada cola tiene **filtros** propios (cédula, **varias cédulas**, nombre y campos de la cola); en **Ficha activa sin acreditación** el **Cargo Ficha** es un selector con los cargos activos del catálogo de Ficha. Use la lupa para aplicar y la X para limpiar. Los resultados son de esa corrida en pantalla; si sale y vuelve, o la sesión de resultados expiró, debe ejecutar de nuevo.
6. En las colas con acreditado existente puede marcar filas (o «seleccionar todos» del filtro actual) y usar el botón **Cargar en Export Apo** para validar solo esos IDs en Export Apo. En **Ficha activa sin acreditación** no hay ID de acreditado: primero cree el registro con **Nuevo**.

### Interpretar las cuatro colas

| Cola | Qué significa | Qué puede hacer |
| --- | --- | --- |
| Ficha activa sin acreditación | Persona **activa** en Ficha que no tiene ninguna acreditación en el sistema. Muestra **Cargo Ficha** y **Tipo** (Operativo si el área de la requisición es Operaciones; Administrativo si tiene otra área; vacío si no hay requisición vinculada). | Abrir Ficha (si tiene permiso) o **Nuevo acreditado** con la cédula ya precargada. |
| Acreditado ausente del reporte del día | Hay acreditación en el sistema, pero ese par cédula + **CARGO APO** no aparece en el Excel APO del día (ni Enproceso ni Acreditado APO). Muestra **Cargo Ficha**. | Abrir Ficha o **Editar** el acreditado. Revise si el texto del Cargo en el Excel coincide con el CARGO APO (mismo texto normalizado; no basta con que «contenga» palabras parecidas). |
| EN PROCESO en sistema / ACREDITADO en APO | En el sistema sigue EN PROCESO, pero en el reporte del día (origen Acreditado APO) ya figura ese par. Muestra **VIGEN.ACR APO** (vigencia del Excel APO para ese par cédula + cargo). | Abrir Ficha o **Editar** (por ejemplo actualizar vigencia / solicitud según el trámite real). |
| Vencidas / por vencer | Acreditados en DESACREDITADO o POR VENCER. No depende del reporte del día. | Abrir Ficha o **Editar**. |

Tras crear o editar desde una cola, conviene **volver a ejecutar** para refrescar los listados. No hay marca de «revisado».

### Exportar resultados de Validaciones

1. Con una corrida activa (después de Ejecutar), use el export de **cada cola** o el **consolidado** (un archivo con una hoja por cola).
2. Si no ha ejecutado o los resultados ya no están disponibles, el sistema pedirá que ejecute de nuevo antes de descargar.

### Generar Export Apo (SuperVigilancia)

1. Con permiso de edición, abra la pestaña **Export Apo**. Al entrar **no** se muestra listado: la pantalla queda vacía a propósito. Si llegó desde Acreditados o Validaciones con el botón **Cargar en Export Apo**, el sistema valida automáticamente solo esos IDs.
2. Elija la **política de vigencia** de cursos (solo vigentes, o vigentes + por actualizar).
3. Pulse **Validar** (sin selección previa carga todo el universo; con IDs precargados solo esos). El sistema muestra columnas SuperVigilancia más **Estado curso**, **Valida** y **Motivo** (solo en pantalla; no van al Excel). Filas fuera del universo aparecen bloqueadas con motivo. Tras Validar puede acotar el preview con el icono **varias cédulas** (exacto, máximo 500, sin historial).
4. Marque las filas a exportar. Puede **editar** un acreditado (lápiz), **quitar** una fila del preview (basura) o, con varias marcadas, usar **Actualizar** para cambiar observación/fecha/renovación en bloque. Tras guardar vuelve a Export Apo y revalida el mismo conjunto.
5. Pulse **Generar .xls**. Si entre las filas marcadas hay alguna con **Valida = No**, aparece un modal para decidir si incluye las novedades leves; si todas son válidas, descarga directo. Las filas con bloqueo duro no salen en ningún caso.
6. Confirme la descarga. El archivo es `.xls`, una sola hoja, con las columnas oficiales SuperVigilancia. El género va como **1** o **2**. El nombre incluye el Nit de parámetros, la fecha y un número correlativo del día.
7. Si el código de curso o el cargo de acreditación no están bien cargados en Catálogo Cursos, corrija allí y vuelva a **Validar**.

### Administrar Catálogo (solo edición)

1. Abra la pestaña **Catálogo**.
2. Cree o edite filas: CARGO MANAGER, CARGO APO, CARGO INFORME, CARGO ACREDITACIÓN, activo y orden.
3. Si un CARGO APO está en uso en acreditados, el sistema **no permite** eliminarlo cuando es la última fila activa de ese APO, ni renombrarlo. Puede desactivar la fila o ajustar Manager / Informe / Acreditación.
4. Los valores de CARGO APO activos son los que aparecen al registrar o importar acreditados. En Validaciones el cruce usa ese CARGO APO frente al Cargo del Excel APO: conviene que coincidan en redacción.
5. En la misma pestaña, revise o edite la sección **Parámetros Export Apo** (Nit, razón social, tipo documento, establecimiento, teléfono, direcciones, departamento, ciudad, educación y discapacidad). Es una sola fila: esos valores se repiten en todas las filas del archivo SuperVigilancia. Guarde los cambios antes de generar un export.

### Cómo se calcula el estado (referencia — solo Acreditados)

Sin intervención manual:

1. Si hay **fecha de solicitud** → **EN PROCESO**.
2. Si no hay vigencia (**VIGEN.ACR** vacío) → **EN PROCESO**.
3. Si no hay solicitud y la **vigencia ya venció** → **DESACREDITADO**.
4. Si no hay solicitud y la vigencia vence en **21 días o menos** → **POR VENCER**.
5. En cualquier otro caso con vigencia vigente → **ACREDITADO**.

El sistema también recalcula estos estados de madrugada según el calendario.  
En **Reporte Diario** el estado o la vigencia del Excel se muestran tal como vienen de la APO; no pasan por este cálculo.  
En **Validaciones**, la cola de vencidas usa el estado ya calculado del acreditado; el resto de colas cruzan datos de Ficha, Acreditados y el snapshot del día.  
En **Export Apo**, el estado del acreditado define quién entra al universo de candidatos; el estado del **curso** (vigente / actualizar / vencido) se aplica según la política elegida al Validar y generar.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.19 | 2026-09-29 | Feature | Filtro varias cédulas (modal, exacto, max 500, sin historial) en Acreditados, Validaciones, Export Apo preview y Cursos registros; export respeta el filtro. |
| 1.18 | 2026-09-29 | Feature | Dashboard: por defecto solo empleados activos en ficha; filtro Estado ficha (activos/desvinculados/todos). |
| 1.17 | 2026-09-29 | Feature | Dashboard: quita candidatos/novedad/corridas Export Apo; filtros + gráficos ApexCharts. |
| 1.16 | 2026-09-28 | Feature | Acreditados/Validaciones: Cargar en Export Apo; quitar fila en preview; fuera de universo = bloqueo. |
| 1.15 | 2026-09-28 | Feature | Export Apo: se elimina listado DT/filtros; Validar muestra solo preview con columnas APO. |
| 1.14 | 2026-09-28 | Feature | Export Apo: se restaura Preview SuperVigilancia; match F\|R acepta `cargo_acredit` como código de catálogo. |
| 1.13 | 2026-09-28 | Feature | Export Apo: pantalla vacía hasta Validar; columnas tipo/estado curso solo en vista. |
| 1.12 | 2026-09-28 | Documentador | FEAT-039: Export Apo (preview, modal novedades, `.xls`, parámetros en Catálogo) + Dashboard; Genero 1/2; sin placeholders. |
| 1.11 | 2026-09-25 | Feature | CARGO (Acreditados/masivos) derivado de Ficha `position_name`; columna Excel CARGO se ignora. |
| 1.10 | 2026-09-25 | Feature | Validaciones: filtros por cola; cola sin acreditación muestra Cargo Ficha + Tipo (admin/operativo vía área requisición). |
| 1.9 | 2026-09-25 | Documentador | FEAT-038: Validaciones operativa (gate ambos orígenes, Ejecutar, 4 colas, acciones, export cola+consolidado). Placeholders restantes: Dashboard, Export Apo. |
| 1.8 | 2026-09-24 | Documentador | FEAT-037: Reporte Diario APO (carga 1–2 Excel, replace parcial, histórico, filtros y export). Placeholders restantes: Dashboard, Validaciones, Export Apo. |
| 1.7 | 2026-09-24 | Feature | Import masivo: VIGEN.ACR vacío o «enproceso» carga con vigencia vacía y estado EN PROCESO. |
| 1.6 | 2026-09-24 | Feature | Acreditados: columna/filtro RENOVACIONES (Solicitado / Renovado); orden FECHA SOLICITUD → OBSERVACIONES; también en Actualizar seleccionados. |
| 1.5 | 2026-09-24 | UI | Modal «Actualizar seleccionados»: chrome unificado, preview de filas, Aplicar cambios. |
| 1.4 | 2026-09-24 | UI | Modal editar acreditado: chrome unificado, identidad bloqueable, CARGO APO searchable-select. |
| 1.3 | 2026-09-24 | Feature | Acreditados: selección masiva + modal observaciones/fecha solicitud. |
| 1.2 | 2026-09-24 | Feature | Acreditados: filtro «Estado en ficha» (por defecto solo activos; opción desvinculados/todos). |
| 1.1 | 2026-09-23 | UI | Acciones de Acreditados (filtros, export, modales) como iconos Lucide. |
| 1.0 | 2026-09-23 | Documentador | Version inicial FEAT-036: tablero Acreditaciones (Acreditados, Catálogo, import/export; placeholders Dashboard/Reporte/Validaciones/Export Apo). |
