# Dashboard de área Gestión Humana — Guía de usuario

> Documentación operativa para usuarios finales. Ubicación: `docs/user/gestion-humana-dashboard.md`.

## Objetivo

Ver de un vistazo el **informe mensual** de Gestión Humana: personal activo, ingresos, desvinculaciones, rotación, personal operativo, acreditaciones, psicofísicos, cliente interno, formación y archivo, junto con gráficos del año seleccionado.

## Alcance

Aplica al tablero **Dashboard** del menú **Gestión Humana**.

**En esta versión puede:**

- Filtrar por año, mes, ciudad, cargo y cliente.
- Consultar 10 indicadores (tarjetas) y 7 gráficos.
- Actualizar los números al cambiar filtros (sin salir de la pantalla).

**Límites:**

- Solo consulta: no se editan datos desde este informe.
- No hay exportación a Excel ni PDF desde esta pantalla.
- Ciudad, cargo y cliente **sí** afectan personal (ficha), acreditaciones y psicofísicos.
- Ciudad, cargo y cliente **no** afectan las tarjetas/gráficos de Cliente interno, Formación ni Archivo: esas tres fuentes solo miran el **año y el mes** (el sistema lo indica en pantalla).

## Definiciones

| Término | Significado |
| --- | --- |
| Informe mensual | Vista del Dashboard de área con indicadores y gráficos del mes/año elegidos. |
| Personal activo | Personas que, según fechas de ingreso y retiro de la ficha, estaban activas al **cierre** del mes (último día del mes). |
| Ingresos del mes | Altas cuya fecha de ingreso cae en el año y mes seleccionados. |
| Desvinculaciones | Bajas cuya fecha de retiro cae en el año y mes seleccionados. |
| Rotación mensual | Porcentaje que compara las desvinculaciones del mes con el promedio de personal activo al cierre de ese mes y del mes anterior. Si no hay base para calcular, se muestra **—**. |
| Personal operativo | Activos al cierre del mes cuyo cargo coincide exactamente con una lista fija de cargos operativos (escolta, guarda, operador, supervisor, etc.). También se muestra el porcentaje respecto al total de activos. |
| Ciudad (filtro) | Ciudad de trabajo; si no hay, ciudad de residencia (misma lógica que en Ficha). |
| Cliente (filtro) | Centro de trabajo; si está vacío, centro de costo. |
| Psicofísicos (tarjeta) | Registros del módulo MT-ST-04 con estado **Vigente** del primer examen. |
| Acreditaciones (tarjeta) | Personas en estado **Acreditado**. |
| Cliente interno / Formación / Archivo | Conteos del mes (solicitudes, registros de formación, consultas de archivo). Solo filtran por año y mes. |

## Responsabilidades

| Perfil | Responsabilidad en este módulo |
| --- | --- |
| Usuario con acceso al tablero Dashboard de Gestión Humana | Abrir el informe, filtrar y interpretar KPIs/gráficos para seguimiento mensual. |
| Administración de usuarios | Asignar el tablero **Dashboard** de Gestión Humana a quien deba ver el informe (no se crean permisos adicionales para esta pantalla). |
| Dueños de datos (Ficha, MT-ST-04, Acreditaciones, etc.) | Mantener al día las fuentes; el dashboard solo refleja lo ya cargado en cada módulo. |

## Desarrollo

### Abrir el informe

1. En el menú de **Gestión Humana**, abra **Dashboard**.
2. Debe ver el título **Dashboard de Gestión Humana – Informe Mensual**.
3. Por defecto se muestran el año y el mes actuales (calendario de Colombia).

### Filtrar el informe

1. Use los selectores de **Año**, **Mes**, **Ciudad**, **Cargo** y **Cliente**.
2. Al cambiar un filtro, la pantalla se actualiza sola (puede ver un aviso breve de actualización).
3. Para quitar ciudad, cargo y cliente (y volver a un mes/año limpio según el botón de limpiar), use el icono de limpiar filtros.
4. Recuerde: Cliente interno, Formación y Archivo **no** cambian al filtrar por ciudad, cargo o cliente; solo por año y mes.

### Leer las tarjetas (indicadores)

1. Revise las diez tarjetas: personal activo, ingresos, desvinculaciones, rotación, personal operativo (con %), acreditaciones, psicofísicos, cliente interno, formación (personas del ciclo, igual que Formación → Por persona) y archivo (cédulas/documentos del historial del mes, no cabeceras de consulta).
2. Si la rotación muestra **—**, significa que no hay personal base suficiente en el mes actual y el anterior para calcular el porcentaje.
3. Personal operativo solo cuenta cargos de la lista operativa acordada; un cargo escrito distinto en la ficha no entra en ese conteo.

### Leer los gráficos

1. **Evolución de personal:** activos al cierre de cada mes (del año en curso solo hasta el mes de hoy; años pasados Ene–Dic; respeta ciudad/cargo/cliente). Cada punto muestra el número.
2. **Ingresos vs desvinculaciones:** altas y bajas por mes (mismo tope de meses que la evolución de personal). Las barras muestran el valor encima.
3. **Evolución formación:** personas del ciclo por mes (mismo tope; criterio Formación → Por persona; no ciudad/cargo/cliente). Cada punto muestra el número.
4. **Personal por concepto / área:** activos del mes agrupados por conceptos (Administración, Comercial, Guardas, etc.). Lo que no esté asignado aparece en **OTROS**. Cada barra muestra el número.
5. **Estado psicofísicos:** distribución Vigente / Vencerá / Vencido.
6. **Estado acreditación:** Acreditado / En proceso / Por vencer.
7. **Consultas archivo:** motivos del mes sobre las mismas cédulas del KPI Archivo (si una consulta tiene varios motivos, cada cédula suma en cada motivo).

### Administrar conceptos / áreas

1. En la barra de filtros, pulse el icono de **capas** (Conceptos / áreas).
2. Puede **crear** conceptos, **renombrarlos**, **eliminarlos** y **asignar o quitar cargos** de la Ficha.
3. Al cerrar el panel, el gráfico se actualiza. Un cargo solo puede pertenecer a un concepto.

### Si no puede entrar

1. Confirme con Administración que tiene asignado el tablero **Dashboard** de Gestión Humana.
2. Sin ese acceso, el sistema no muestra el informe.

## Control de cambios

| Versión | Fecha | Autor | Descripción del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-10-13 | Documentador | Versión inicial FEAT-046 (informe mensual KPI + gráficos). |
| 1.1 | 2026-10-13 | Feature | Personal por concepto/área + modal CRUD (mismo permiso del tablero). |
| 1.2 | 2026-10-13 | Feature | KPI Archivo = ítems del historial (año + mes), no cabeceras de consulta. |
| 1.3 | 2026-10-13 | Feature | Pie Consultas Archivo alineado a ítems (misma base que el KPI). |
| 1.4 | 2026-10-13 | Feature | KPI y Evolución Formación = personas del ciclo (como Formación dashboard). |
| 1.5 | 2026-10-13 | Feature | Gráficos de tendencia: año vigente hasta mes actual; años pasados Ene–Dic. |
| 1.6 | 2026-10-13 | Feature | Etiquetas numéricas en gráficos de barra y de tendencia. |
| 1.7 | 2026-10-13 | Feature | Etiquetas con fondo blanco; verticales en Personal, Ingresos vs Desvinculaciones y Formación. |
