# Indicadores de Operaciones — Guia de usuario

## Objetivo

Registrar, consultar y consolidar indicadores de desempeno (KPI) del area de Operaciones segun el formato FT-OP, con dashboards ejecutivos y reportes de exportacion.

## Alcance

Modulo exclusivo del area **Operaciones**. Segun permisos:

- **Dashboard:** ver indicadores globales y resumen del periodo.
- **Captura:** registrar valores mensuales de indicadores asignados a su usuario.
- **Ajustes:** administrar periodos, metas, capturadores del area y consultar auditoria (gestores).
- **Consolidado:** vista consolidada de capturas del equipo (gestores).
- **Exportaciones:** PDF, Excel y PowerPoint (informe FO-GI-39) segun permiso de exportacion.

## Definiciones

| Termino | Significado |
| --- | --- |
| Indicador FT-OP | Uno de los nueve indicadores operativos configurados en el sistema. |
| Periodo | Mes/anio de captura; puede estar abierto o cerrado. |
| Captura | Registro mensual de valores de un indicador por usuario. |
| Dashboard | Tablero ejecutivo con KPIs y score ponderado. |
| Consolidado | Vista agregada de capturas de usuarios con permiso de captura o gestion. |
| Plan de mejora | Accion registrada cuando un indicador esta en rojo. |
| Peso | Porcentaje de contribucion de cada indicador al score global. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Capturador operaciones | Registrar capturas mensuales en periodos abiertos. |
| Jefe / gestor operaciones | Cerrar/reabrir periodos, ajustar metas, revisar consolidado y auditoria. |
| Direccion / consulta | Ver dashboard y exportar reportes (segun permiso export). |
| Administrador | Asignar permisos operations.view, capture, manage, export en Admin usuarios. |

## Desarrollo

### Capturar indicadores del mes

1. Entre al area Operaciones → tablero **Indicadores** → **Captura**.
2. Seleccione el indicador de la lista.
3. Elija anio y mes (periodo debe estar abierto).
4. Complete los campos del formulario y guarde.
5. Si el indicador esta en rojo, registre plan de mejora si se solicita.

### Consultar el dashboard

1. Abra **Dashboard** en Indicadores.
2. Filtre por periodo si hay selector.
3. Revise KPIs y tabla resumen (incluye resultado del mes anterior junto al del periodo seleccionado).
4. Revise **Ranking de usuarios**: posicion, nombre, indicadores gestionados en el mes, porcentaje gestionado sobre el total FT-OP activos, y mejoras registradas (solo quienes capturaron datos en el periodo).
5. Revise **Indicadores criticos**: solo aparecen usuarios cuyo resultado supera el umbral critico configurado en Metas.
6. Exporte a PDF o descargue el **Informe PPTX** (FO-GI-39) si tiene permiso de exportacion.

### Administrar periodos (Ajustes)

1. Abra **Ajustes** → seccion **Periodos**.
2. Cree un periodo nuevo o cierre/reabra existentes.
3. Solo periodos abiertos permiten nuevas capturas.

### Ajustar metas por indicador

1. En Ajustes → **Metas**, seleccione el **Operador** (`>=`, `<=`, `==`) y modifique **Meta (%)** y **Critico (%)** de cada FT-OP.
2. **FT-OP-03 (Compuesto):** no tiene operador unico; **Meta** limita la frecuencia (A) y **Critico** el impacto economico (B). El semaforo exige cumplir ambos.
2. Indique el motivo del cambio y guarde.
3. Los valores se reflejan de inmediato en la ficha de captura y en el calculo de cumplimiento.

### Administrar capturadores (Ajustes)

1. Abra **Ajustes** → seccion **Capturadores**.
2. Revise usuarios activos del area **Operaciones**.
3. Use el **interruptor (toggle)** en la columna Captura para activar o inactivar el permiso (sin motivo adicional).
4. Los administradores de indicadores permanecen siempre habilitados.

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
