# Planeación estratégica

Tablero de Calidad para construir indicadores de gestión (campos + fórmula), publicarlos, compartirlos a usuarios sueltos y consultar la ficha anual tipo FT-OP-01. Tablas propias: **no** reutiliza `indicators` / `indicator_captures` de Operaciones.

**Feature:** FEAT-050 (fase 1). Fuera de V1: DOFA, objetivos, estrategias, asignación por área, suplencia, PPTX y funciones de fórmula avanzadas (`SI()`, `MIN()`, etc.).

## Objetivo

Que Calidad cree indicadores desde cero, los publique y los asigne a capturadores; que cada asignado diligencie su fila mensual; y que Calidad o un consultor del indicador vean el historial anual con consolidado **suma de campos + recálculo de fórmula**.

## Alcance actual

- Un tablero en el área `calidad`: **Planeación estratégica**. El dashboard es **pestaña**, no un segundo ítem del sidebar. No reutiliza `view.board.calidad.dashboard`.
- Pestañas: Dashboard, Indicadores, Captura. La consulta de ficha se abre desde el listado (no hay pestaña extra).
- Periodo V1: año + mes de calendario. Frecuencia: mensual. Sin Ajustes de periodos tipo Operaciones.
- Proceso: catálogo `quality-documents.processes`. Sin pestaña de catálogos propia.
- Compartir: solo usuarios activos sueltos. Al asignar, el sistema otorga el acceso mínimo (no hace falta Admin en cada alta).
- Textos libres en MAYÚSCULAS UTF-8 (`UppercasesValidatedText` / `UppercaseFormText`). Excepciones: `code`, `formula`, `process_key`, operadores, unidad, frecuencia, claves de campo y números.
- En Identidad, Calidad escribe **insumos para la medición** y **medición**; la ficha los muestra en esas celdas (ya no hay textos fijos).

## Rutas

Prefijo `calidad/planeacion`, nombre `calidad.planeacion.*`, middleware `auth`, `active`, `password.changed`. Archivo: `routes/areas/calidad.php`.

| Método | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/calidad/planeacion` | `calidad.planeacion.index` | Tablero; redirige a la primera pestaña visible |
| GET | `/calidad/planeacion/dashboard` | `calidad.planeacion.dashboard` | `planeacion.dashboard.view` |
| GET | `/calidad/planeacion/indicadores` | `calidad.planeacion.indicadores` | `planeacion.indicadores.view` o `.edit` |
| GET | `/calidad/planeacion/indicadores/datatable` | `calidad.planeacion.indicadores.datatable` | Igual |
| GET | `/calidad/planeacion/indicadores/crear` | `calidad.planeacion.indicadores.create` | `planeacion.indicadores.edit` |
| POST | `/calidad/planeacion/indicadores` | `calidad.planeacion.indicadores.store` | `.edit` |
| POST | `/calidad/planeacion/indicadores/previsualizar-formula` | `calidad.planeacion.indicadores.preview` | `.edit` |
| GET | `/calidad/planeacion/indicadores/{indicator}` | `calidad.planeacion.indicadores.show` | Consulta del indicador (`canConsultIndicator`) |
| GET | `/calidad/planeacion/indicadores/{indicator}/exportar-excel` | `calidad.planeacion.indicadores.export.excel` | Igual que la ficha |
| GET | `/calidad/planeacion/indicadores/{indicator}/exportar-pdf` | `calidad.planeacion.indicadores.export.pdf` | Igual que la ficha |
| GET | `/calidad/planeacion/indicadores/{indicator}/editar` | `calidad.planeacion.indicadores.edit` | `.edit` |
| PATCH | `/calidad/planeacion/indicadores/{indicator}` | `calidad.planeacion.indicadores.update` | `.edit` |
| POST | `/calidad/planeacion/indicadores/{indicator}/publicar` | `calidad.planeacion.indicadores.publish` | `.edit` |
| POST | `/calidad/planeacion/indicadores/{indicator}/archivar` | `calidad.planeacion.indicadores.archive` | `.edit` |
| POST | `/calidad/planeacion/indicadores/{indicator}/restablecer` | `calidad.planeacion.indicadores.restore` | `.edit` |
| POST | `/calidad/planeacion/indicadores/{indicator}/compartir` | `calidad.planeacion.indicadores.share` | `.edit` (usuarios sueltos y áreas) |
| POST | `/calidad/planeacion/indicadores/{indicator}/suplencias` | `calidad.planeacion.indicadores.substitutions.store` | `.edit` |
| DELETE | `/calidad/planeacion/indicadores/{indicator}/suplencias/{substitution}` | `calidad.planeacion.indicadores.substitutions.destroy` | `.edit` |
| GET | `/calidad/planeacion/captura` | `calidad.planeacion.captura` | `planeacion.captura.view` o `.edit` |
| GET | `/calidad/planeacion/captura/datatable` | `calidad.planeacion.captura.datatable` | Igual |
| GET | `/calidad/planeacion/captura/{indicator}` | `calidad.planeacion.captura.show` | Captura de **ese** indicador (`canCaptureIndicator`) |
| POST | `/calidad/planeacion/captura/{indicator}` | `calidad.planeacion.captura.store` | `planeacion.captura.edit` + asignación |

El tablero en el menú usa `view.board.calidad.planeacion_estrategica`. `manage.users` hace bypass. La clave de tablero solo existe en el área `calidad`.

## Permisos

`edit` implica `view` en `PlaneacionEstrategicaAccessService`. El dashboard **no** se deriva de `indicadores.view`.

| Permiso | Label Admin | Uso |
| --- | --- | --- |
| `view.board.calidad.planeacion_estrategica` | Planeación estratégica | Sidebar |
| `planeacion.dashboard.view` | Planeación: Dashboard | Pestaña Dashboard (sin `edit`) |
| `planeacion.indicadores.view` | Planeación: Ver indicadores | Listar y abrir ficha; sin `.edit` = solo indicadores con `can_consult` |
| `planeacion.indicadores.edit` | Planeación: Editar indicadores | CRUD, versionar, publicar, compartir, archivar y restablecer; implica consulta de todos |
| `planeacion.captura.view` | Planeación: Ver captura | Pestaña Captura |
| `planeacion.captura.edit` | Planeación: Capturar | Guardar o reemplazar la captura **propia** |

Config: `config/access.php` (`area_indicador_permissions`, grupo Admin **Calidad → Planeación estratégica (funciones)**). Pestañas: `planeacion_tabs`.

### Auto-otorgamiento al compartir

`PlaneacionAssignmentService` sincroniza asignados directos (`quality_planning_indicator_assignees`) y por área (`quality_planning_indicator_area_assignees`). `can_capture` siempre es verdadero; `can_consult` es opcional. El área se resuelve contra `users.area_key` y se expanden los usuarios activos del área al guardar para otorgar el mínimo:

| Rol en el indicador | Permisos mínimos |
| --- | --- |
| Solo captura | tablero + `planeacion.captura.view` + `planeacion.captura.edit` |
| Captura + consulta | lo anterior + `planeacion.indicadores.view` + `planeacion.dashboard.view` |

Al quitar el último indicador asignado (directo o por área), revoca esos mínimos si no quedan otros. Si quedan asignaciones pero ninguna con consulta, revoca solo el par de consulta/dashboard.

**Nunca revoca** `planeacion.indicadores.edit`: Calidad no pierde permisos al quitarse de un indicador.

Suplencias temporales: tabla `quality_planning_indicator_substitutions` (titular, suplente, `starts_on`/`ends_on`). El suplente solo **captura/reemplaza** dentro de su rango y la captura queda a nombre del **titular** (`user_id` del titular; `created_by_user_id`/`updated_by_user_id` guardan quién la diligenció, y el audit registra `substitute_user_id` + `holder_user_id`). No hereda consulta ni dashboard. Al registrar la suplencia se otorga el mínimo de captura al suplente; al eliminarla se revoca si no quedan asignaciones ni suplencias vigentes. Servicio: `PlaneacionSubstitutionService`.

### Semilla de roles

- `super-admin`: catálogo completo vía `app:sync-permissions` / `PermissionCatalog::sync()`.
- `administrador`: tablero + `planeacion.indicadores.edit` + `planeacion.dashboard.view` + `planeacion.captura.view` / `.edit`.
- No hay permiso legado que migrar. El rol Spatie `director` de Compras **no** se usa aquí: el director de un indicador es un usuario con `can_consult` (y captura) en ese indicador.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `Calidad\PlaneacionEstrategicaController` | Shell, CRUD, publicar, archivar, restablecer, compartir, captura, preview de fórmula, ficha |
| `StorePlaneacionIndicatorRequest` / `UpdatePlaneacionIndicatorRequest` | Alta/edición; 2–12 campos; clave `^[a-z][a-z0-9_]*$`; fórmula validada |
| `PreviewPlaneacionFormulaRequest` | Valores de prueba para el botón Probar |
| `SyncPlaneacionAssigneesRequest` | Usuarios activos, `user_id` distinto, `can_consult` opcional |
| `StorePlaneacionCaptureRequest` | Año/mes, `input_data`, análisis, plan de mejora, `replace_confirmed` |
| Trait `HasPlaneacionTabs` | Subtabs visibles según el AccessService |

## Vistas

Directorio `resources/views/areas/calidad/planeacion/`.

| Vista | Descripción |
| --- | --- |
| `dashboard.blade.php` | KPI del mes + gráficos Apex (chrome GH) |
| `indicadores.blade.php` | Listado DataTables server-side |
| `form.blade.php` | Constructor: ficha, campos, fórmula, Probar |
| `show.blade.php` | Ficha anual; export Excel/PDF; compartir vía ícono (modal); restablecer si está archivado |
| `exports/ficha-pdf.blade.php` | PDF de la ficha (una hoja A4 horizontal; gráfico PNG embebido) |
| `captura.blade.php` | Listado de indicadores a capturar |
| `captura-form.blade.php` | Formulario del mes + ficha propia; Excel/PDF |
| `partials/ficha-gestion.blade.php` | Hoja tipo FT-OP-01 (ene–dic, gráfico, análisis) |
| `partials/share.blade.php` | Modal de asignar usuarios o áreas (captura / consulta) y suplencias; se abre con ícono `users` |
| `partials/confirm-archive-modal.blade.php` | Confirmar archivo antes de POST `archivar` |
| `partials/subnav.blade.php` | Pills Dashboard / Indicadores / Captura |
| `partials/capture-metrics.blade.php` | Tarjetas resultado / semáforo / cumple |

## Modelos y tablas

Migración `2026_11_10_114549_create_quality_planning_tables.php` (MySQL y sqlite).

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `QualityPlanning\QualityPlanningIndicator` | `quality_planning_indicators` | `code` unique (máx. 40, lo escribe Calidad), `created_by_user_id`, `archived_at`, `current_published_version_id` |
| `QualityPlanning\QualityPlanningIndicatorVersion` | `quality_planning_indicator_versions` | Ficha + `formula` + `fields` JSON; `insumos` y `medicion` (texto libre de identidad); estado `draft` / `published` / `superseded`; unique `(indicator_id, version_number)` |
| `QualityPlanning\QualityPlanningIndicatorAssignee` | `quality_planning_indicator_assignees` | `can_capture`, `can_consult`; unique `(indicator_id, user_id)` |
| `QualityPlanning\QualityPlanningCapture` | `quality_planning_captures` | `input_data` JSON, `result_value`, `complies`, análisis, mejora; unique `(indicator_version_id, user_id, year, month)` |

JSON de cada campo: `{ key, label, decimals (0\|2), required, sort }`.

## Servicios / jobs / mail (si aplica)

| Servicio | Responsabilidad |
| --- | --- |
| `FormulaEvaluator` | Lista blanca `+ - * / ( )`, números y claves; RPN; sin `eval`; división por cero → `DomainException` |
| `PlaneacionIndicatorService` | Crear/editar/publicar/archivar/restablecer; si la versión de trabajo está congelada, el update abre un borrador vN+1 |
| `PlaneacionPeriodService` | Qué versión rige cada mes (cierre del mes de publicación de vN>1) |
| `PlaneacionCaptureService` | Guardar/reemplazar captura propia; unicidad por persona/mes |
| `PlaneacionConsolidadoService` | Ficha anual: suma de campos del mes + recálculo; filtro Todos / un capturador |
| `PlaneacionAssignmentService` | Sync de asignados + auto-permisos |
| `PlaneacionDashboardService` | KPI y series del mes, acotados al perfil |
| `PlaneacionFichaExportService` | Excel (`PlaneacionFichaExport`) y PDF de la ficha; mismos filtros que la consulta |
| `PlaneacionIndicatorDatatableService` / `PlaneacionCaptureDatatableService` | Listados server-side (tope 100) |
| `PlaneacionAuditLogService` | Wrapper: `module=planeacion_estrategica`, `area=calidad` |

No hay jobs ni correo en V1.

## Reglas de negocio

### Fórmula

- Operadores permitidos: `+ - * / ( )` y menos unario. Números y claves de campo (`campo_1`, etc.).
- Sin `eval`, sin `SI()`, `MIN()` ni funciones.
- Preview con valores de prueba (endpoint JSON). División por cero no guarda (422).
- Ejemplo: `((campo_1 / campo_2) + campo_3) * 100`. María 22/25+0,02 → 90; Carlos 24/25+0 → 96; **Todos** suma 46/50+0,02 → **94** (no promedio de 90 y 96).

### Versionado (marzo cerrado)

- Publicar congela la versión (`published`) y marca las anteriores como `superseded`.
- v1 rige **desde su mes de publicación inclusive**.
- Al publicar vN (N>1) en el mes P, **P queda cerrado**: ni alta ni reemplazo de captura. Los meses anteriores siguen en la versión previa; el mes siguiente es el primero de vN.
- Ejemplo: publicar v2 el 15 de marzo → febrero = v1; marzo cerrado; abril = v2.
- Editar una versión publicada abre un borrador nuevo. Solo se publica un borrador.
- La ficha de consulta tiene selector **Versión** (`?version=`): `Vigente (según el mes)` usa la versión que rige cada mes; una versión concreta muestra su definición y solo las capturas de esa versión.
- El panel **Historial de versiones** lista v1…vN con estado, fecha de publicación y un diff respecto a la previa (nombre, fórmula, meta, campos). También aparece al editar.

### Captura

- Varios usuarios, mismo indicador, mismo mes: **una fila por persona**.
- Segunda captura del mismo usuario/mes: modal; sin `replace_confirmed` → 422. El reemplazo queda en auditoría (`action=replace`).
- Si no cumple la meta, el plan de mejora es obligatorio.
- Capturador sin consulta: solo pestaña Captura de **sus** indicadores; la ficha del formulario va forzada a su fila (sin selector de equipo).
- Quien tiene `planeacion.indicadores.edit` puede capturar cualquier indicador no archivado.

### Consolidado

- Como Operaciones: **suma cada campo** de las capturas del mes (versión que rige ese mes) y **recalcula la fórmula**. No promedia porcentajes.
- Filtro **Todos** / un capturador. El director solo ve capturadores de **ese** indicador.
- Análisis mensual se muestra al filtrar un capturador; en Todos queda `—`.
- Semáforo: cumple / no cumple / crítico según operador, meta y valor crítico de la versión del mes.
- Gráfico: 12 categorías fijas (`ENE`…`DIC`); meses vacíos van en `null` (no se reagrupan al final).

### Dashboard

- Indicadores publicados no archivados. Editor: todos. Consultor: solo `can_consult`.
- KPI: cantidad, % capturados (cupos asignados vs capturados), verde / rojo / pendientes.
- Filtro año + mes de calendario.

## JavaScript / assets (si aplica)

- CSS: `resources/css/areas/calidad/117-calidad-planeacion.css`. Captura: hueco vertical de 10px entre secciones.
- Charts: `resources/js/planeacion-dashboard-charts.js`, `resources/js/planeacion-ficha-charts.js` (Apex; entries Vite). El gráfico de ficha usa puntos `{x, y}` por mes (`ENE`…`DIC`), curva recta y **ancho 100%** alineado a la hoja. Etiquetas de **valor** encima de cada barra y de **porcentaje (Resultado)** dentro del grupo de barras; Meta no lleva etiqueta. Cabeceras **GRÁFICOS** y **ANÁLISIS DE RESULTADOS** con fondo `--color-primary` (mismo azul de las pestañas de tablero) y texto blanco; margen vertical 20px alrededor del canvas. En la hoja, `.t-head` en negrita y `.t-body` en peso normal.
- Selects: `<x-searchable-select>`. Chrome: `.module-tab`, icon-btn canónicos. Listados: DataTables `serverSide`. Compartir: botón `users` en toolbar (ficha) y acciones (edición); el formulario vive en `<x-modal name="planeacion-share">`. Archivar: formularios `.js-confirm-archive` + modal `confirm-archive`. Restablecer: ícono `rotate-ccw` en listado (filtro Archivado) y ficha.
- Referencia de ficha: captura Operaciones (FT-OP-01). Referencia de dashboard: GH área.

## Export Excel (si aplica)

Desde la ficha de consulta y desde la captura: ícono Excel (`<x-export-excel>`) y PDF (`lucide-file-text`). Quien consulta usa los filtros (año, mes, capturador, versión); el capturador exporta su propia fila. Clase `app/Exports/PlaneacionFichaExport.php`. El gráfico se incrusta como PNG (GD `IndicatorPdfChartRenderer`) en Excel (`MemoryDrawing`, columnas A–L) y en el PDF. El análisis de resultados ocupa el mismo ancho A–L (texto C–J, CUMPLE K, MEJORA L) y, en consolidado Todos, une los textos de cada capturador. El PDF es una sola tabla A4 horizontal.

## Validación local

1. `php artisan test --compact tests/Feature/Calidad/PlaneacionBoardAccessTest.php tests/Feature/Calidad/PlaneacionIndicatorCrudTest.php tests/Feature/Calidad/PlaneacionAssignmentTest.php tests/Feature/Calidad/PlaneacionCaptureTest.php tests/Feature/Calidad/PlaneacionConsolidadoTest.php tests/Feature/Calidad/PlaneacionDashboardTest.php tests/Unit/Calidad/Planeacion`
2. El Review Report FEAT-050 reportó 34 pruebas en verde en T3.
3. `npm run build` si se toca CSS/JS/Blade del tablero.
4. Smoke visual: lo hace el usuario (ficha 12 meses, gráfico, dashboard).

## Riesgos y pendientes

- Preview Alpine (cliente) y evaluador PHP pueden divergir; la verdad es el servidor.
- El gráfico de pantalla es Apex; Excel/PDF llevan una imagen PNG generada con GD (no es el canvas del navegador).
- Archivar pide confirmación (modal). No borra historial; deja de capturarse y de listarse para consulta sin `.edit`. Calidad restablece con el ícono de restablecer (listado filtro **Archivado** o ficha).
- `current_published_version_id` no tiene FK en la migración (puntero nullable).

## Referencias

- Feature Brief: `docs/briefs/FEAT-050.md`
- Review Report: `docs/reviews/FEAT-050.md`
- Doc usuario: `docs/user/planeacion-estrategica.md`
- Acceso: `docs/ACCESS_CONTROL.md` (área `calidad`)
- Tono / tablero hermano: `docs/modules/servicio-cliente.md`
