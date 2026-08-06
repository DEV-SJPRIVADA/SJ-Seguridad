# Indicadores de Operaciones — Guia de usuario

## Objetivo

Registrar, consultar y consolidar indicadores de desempeno (KPI) del area de Operaciones segun el formato FT-OP, con dashboards ejecutivos y reportes de exportacion. Cuando un jefe capturador esta ausente, otra persona autorizada puede registrar los indicadores **a nombre del titular**, de modo que rankings y reportes sigan reflejando al jefe responsable.

## Alcance

Modulo exclusivo del area **Operaciones**. Segun permisos:

- **Dashboard:** ver indicadores globales, KPIs y ranking del periodo.
- **Captura:** registrar valores mensuales de indicadores; la captura puede ser propia (jefe capturador) o **por suplencia** (a nombre de otro capturador autorizado). Graficos interactivos unificados (ApexCharts) en la ficha del indicador.
- **Ajustes:** administrar periodos, metas, capturadores y suplentes del area, y consultar auditoria (gestores).
- **Consolidado:** vista consolidada de capturas del equipo (gestores).
- **Exportaciones:** PDF, Excel y PowerPoint (informe FO-GI-39, con vista previa editable) segun permiso de exportacion.

## Definiciones

| Termino | Significado |
| --- | --- |
| Indicador FT-OP | Uno de los nueve indicadores operativos configurados en el sistema. |
| Periodo | Mes/anio de captura; puede estar abierto o cerrado. |
| Captura | Registro mensual de valores de un indicador por usuario titular. |
| Titular | Jefe capturador al que se atribuyen los indicadores en dashboard, ranking y consolidado. |
| Digitador | Persona que lleno o guardo el formulario (puede ser el titular o un suplente). |
| Suplencia | Permiso para capturar indicadores a nombre de un titular cuando este esta ausente. |
| Dashboard | Tablero ejecutivo con KPIs y score ponderado. |
| Consolidado | Vista agregada de capturas de usuarios con permiso de captura o gestion. |
| Plan de mejora | Accion registrada cuando un indicador esta en rojo. |
| Peso | Porcentaje de contribucion de cada indicador al score global. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Capturador operaciones | Registrar capturas mensuales en periodos abiertos (propias). |
| Suplente operaciones | Registrar capturas **a nombre del titular** durante ausencias (vacaciones, etc.); no captura a su propio nombre salvo que tambien sea capturador. |
| Jefe / gestor operaciones | Cerrar/reabrir periodos, ajustar metas, habilitar capturadores y suplentes, revisar consolidado y auditoria. |
| Direccion / consulta | Ver dashboard y exportar reportes (segun permiso export). |
| Administrador | Asignar permisos operations.view, capture, capture.delegate, manage, export en Admin usuarios o via toggles en Ajustes. |

## Desarrollo

### Capturar indicadores del mes (captura propia)

1. Entre al area Operaciones → tablero **Indicadores** → **Captura**.
2. Seleccione el indicador de la lista.
3. Elija anio y mes (periodo debe estar abierto).
4. Revise los graficos del indicador (barras y lineas) segun el periodo; reflejan valores y cumplimiento.
5. Complete los campos del formulario y guarde.
6. Si el indicador esta en rojo, registre plan de mejora si se solicita.

Si usted es capturador y **no** tiene permiso de suplencia, no vera selector de capturador: los datos quedan a su nombre.

### Capturar por suplencia (vacaciones u otra ausencia del titular)

Use este procedimiento cuando un jefe capturador esta ausente y usted fue habilitado como **suplente** en Ajustes.

**Antes de la ausencia (gestor de indicadores):**

1. Abra **Ajustes** → seccion **Capturadores**.
2. Localice al colaborador que cubrira la ausencia (usuario activo del area Operaciones).
3. Active el interruptor **Suplencia** en su fila (independiente del interruptor **Captura**).
4. Confirme que el titular (jefe capturador) mantiene **Captura** activa; el suplente no necesita permiso de Captura para suplir.

**Durante la ausencia (suplente):**

1. Entre al area Operaciones → tablero **Indicadores** → **Captura**.
2. Seleccione el indicador a registrar.
3. En el selector **Capturador**, elija al **titular** (jefe ausente) cuyos indicadores va a registrar. La pantalla muestra el nombre del titular seleccionado.
4. Elija anio y mes (periodo abierto).
5. Complete el formulario con los datos del titular y guarde.
6. Repita para cada indicador FT-OP del mes.

**Importante:**

- Los indicadores quedan registrados **a nombre del titular**, no del suplente. Dashboard, ranking y consolidado reflejan al jefe responsable.
- El sistema registra quien opero el formulario (digitador) en auditoria cuando titular y digitador son personas distintas.
- Si el periodo esta cerrado, no se puede guardar (igual que en captura propia).
- Elija con cuidado el capturador correcto en el selector; no hay confirmacion adicional al guardar.

**Al regresar el titular (gestor, opcional):**

1. En **Ajustes** → **Capturadores**, puede desactivar **Suplencia** del colaborador que ya no necesita cubrir ausencias.

### Consultar el dashboard

1. Abra **Dashboard** en Indicadores.
2. Filtre por periodo si hay selector.
3. Revise KPIs y tabla resumen (incluye resultado del mes anterior junto al del periodo seleccionado).
4. Revise **Ranking de usuarios**: posicion, nombre, indicadores gestionados en el mes, porcentaje gestionado sobre el total FT-OP activos, y mejoras registradas (solo quienes capturaron datos en el periodo).
5. Revise **Indicadores criticos**: solo aparecen usuarios cuyo resultado supera el umbral critico configurado en Metas.
6. Exporte a PDF o abra **Preparar informe PPTX** (FO-GI-39) si tiene permiso de exportacion.

### Preparar el informe de gestion PPTX (FO-GI-39)

1. Desde el Dashboard, haga clic en **Preparar informe PPTX** (o navegue directo a la vista previa) y seleccione ano/mes.
2. Revise la vista previa: titulo de portada, **graficos mensuales** (denominador, numerador, resultado y meta, igual que en el PowerPoint) y las narrativas de los 9 indicadores FT-OP-01…09, precargadas con el analisis registrado en captura o con un texto generado automaticamente.
3. Edite el titulo o cualquier narrativa y haga clic en **Guardar borrador**. El borrador queda asociado al ano/mes seleccionado y persiste al recargar la pagina.
4. Haga clic en **Descargar PPTX** para generar el PowerPoint con los textos guardados.
5. Si desea descartar sus cambios y volver a los textos automaticos, use **Regenerar textos**: elimina el borrador guardado para ese periodo.

### Administrar periodos (Ajustes)

1. Abra **Ajustes** → seccion **Periodos**.
2. Cree un periodo nuevo o cierre/reabra existentes.
3. Solo periodos abiertos permiten nuevas capturas.

### Ajustar metas por indicador

1. En Ajustes → **Metas**, seleccione el **Operador** (`>=`, `<=`, `==`) y modifique **Meta (%)** y **Critico (%)** de cada FT-OP.
2. **FT-OP-03 (Compuesto):** no tiene operador unico; **Meta** limita la frecuencia (A) y **Critico** el impacto economico (B). El semaforo exige cumplir ambos.
2. Indique el motivo del cambio y guarde.
3. Los valores se reflejan de inmediato en la ficha de captura y en el calculo de cumplimiento.

### Administrar capturadores y suplentes (Ajustes)

1. Abra **Ajustes** → seccion **Capturadores**.
2. Revise usuarios activos del area **Operaciones**.
3. Columna **Captura:** use el interruptor para activar o inactivar el permiso de captura propia (sin motivo adicional). Los administradores de indicadores permanecen siempre habilitados en captura.
4. Columna **Suplencia:** use el interruptor independiente para autorizar captura **a nombre de otros** capturadores. Activar suplencia **no** otorga captura propia.
5. Un usuario puede tener solo Captura, solo Suplencia, o ambos segun la operacion del area.

### Revisar auditoria

1. En Ajustes → **Auditoria**, filtre por usuario, indicador o fecha.
2. Consulte historial de cambios.

### Consolidado

1. Abra la pestaña **Consolidado**.
2. Seleccione indicador y periodo.
3. Revise capturas agregadas del equipo.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-07-23 | FEAT-003 | Seccion Capturadores en Ajustes para habilitar captura FT-OP |
| 1.2 | 2026-07-27 | FEAT-010 | Graficos de captura unificados en ApexCharts (sin ECharts) |
| 1.3 | 2026-08-04 | FEAT-023 | Captura delegada (suplencia vacaciones): permiso Suplencia, selector Capturador, procedimiento titular/digitador |
| 1.4 | 2026-08-04 | FEAT-024 | Vista previa HTML del informe PPTX FO-GI-39 con narrativas y titulo editables; borrador por ano/mes; boton Regenerar textos |
| 1.5 | 2026-08-04 | FEAT-025 | Graficos ApexCharts en vista previa del informe PPTX (serie mensual por indicador) |
