# Task Card — FEAT-050 T2

**Slice:** Compartir usuarios + captura dinámica + reemplazo confirmado + plan de mejora  
**shared-files:** No (salvo `User.php` si hace falta un helper; no tocar access.php salvo bug T1)  
**Depende de:** T1 ya en el árbol (14 tests verdes).

## Objetivo

Calidad asigna capturadores y directores (`can_consult`). El sistema otorga permisos mínimos. Cada asignado diligencia los campos de la versión vigente del mes. El mismo usuario/mes reemplaza solo con confirmación. Publicar v2 el día 15 cierra ese mes en v1.

## Implementar

### 1. Alcance de listado Indicadores
- `indicadores.edit`: todos.
- `indicadores.view` sin edit: solo indicadores donde el usuario es assignee `can_consult=true` y no archivados.
- `show` de un indicador: 403 si view-only y no es consultor.
- Actualizar `PlaneacionIndicatorDatatableService` + AccessService (`canConsultIndicator`).

### 2. Compartir (solo `indicadores.edit`)
- UI en show/edit: lista de usuarios activos (`<x-searchable-select>`) + check **Consulta (ve consolidado del equipo)**. Todo asignado captura (`can_capture=true`).
- Sync `quality_planning_indicator_assignees`.
- Auto-permisos (`PlaneacionAssignmentService`):
  - Captura: `view.board.calidad.planeacion_estrategica` + `planeacion.captura.view` + `planeacion.captura.edit`
  - Consulta: lo anterior + `planeacion.indicadores.view` + `planeacion.dashboard.view`
  - Al quitar el último assignment: revocar solo lo auto (nunca `planeacion.indicadores.edit`). Si sigue siendo consultor en otro indicador, no quitar view/dashboard.
- No usar rol Spatie `director`.

### 3. Versión capturable de un mes
`PlaneacionPeriodService::versionForCapture(indicator, year, month)`:
- v1 (primera publicada): aplica desde su mes de `published_at` inclusive.
- Al publicar vN (N>1) en mes P: mes P **cerrado** (ni alta ni replace en v{N-1} ni en vN). Meses `< P` siguen en versión anterior. Meses `> P` usan vN.
- Si el periodo está cerrado o no hay versión: 422 con mensaje claro.

### 4. Captura
- Listado pestaña Captura: indicadores publicados asignados al usuario (`can_capture`). Calidad edit ve todos los publicados.
- Formulario GET `captura/{indicator}`: selector año/mes, inputs por `fields`, tarjetas resultado/semáforo/cumple/mejora (cálculo live JS + servidor). Análisis textarea. Si `!complies`, `improvement_required` obligatorio.
- POST guarda `input_data`, `result_value`, `complies`, análisis (MAYÚSCULAS), mejora.
- Titular = auth user. `created_by`/`updated_by` = auth.
- Unicidad (version, user, year, month).

### 5. Reemplazo
- Si ya existe captura del mes y POST **sin** `replace_confirmed=1` → 422.
- Con confirmación → update + auditoría `capture replace`.
- Modal propio (no el de borrar): “Ya existe una captura de {mes} {año}. Si continúa, se reemplazarán los datos actuales.” Cancelar / Reemplazar.
- Periodo cerrado: no reemplaza.

### 6. Cumplimiento
Comparar `result_value` con `target_operator` y `target_value`. Semáforo verde/rojo. División por 0 → 422.

### 7. UI
Clonar chrome FO-GI-22 / captura Operaciones (campos + métricas). Selects searchable. Icon-btn canónicos. Ortografía. Comentarios de bloque.

## Tests
- `tests/Feature/Calidad/PlaneacionAssignmentTest.php`: share otorga captura; consultor ve solo sus indicadores; capturador no entra a Indicadores; quitar último revoca captura.edit.
- `tests/Feature/Calidad/PlaneacionCaptureTest.php`: dos usuarios mismo mes dos filas; segundo save sin confirm 422; con confirm pisa; mejora si no cumple; v2 publicado el 15/03 cierra marzo (422) y abril usa v2.
- Unit `versionForCapture` si el servicio queda testeable.

Correr también los 14 de T1 para no regresar.

`php artisan test --compact tests/Feature/Calidad/Planeacion*` `tests/Unit/Calidad/Planeacion*`
`vendor/bin/pint --dirty --format agent`
`npm run build` si CSS/JS.

## Prohibido
- Consolidado “Todos” / gráfico anual / dashboard KPI (T3)
- Tocar `indicators` de Operaciones
- Inventar permisos
- Docs finales
- migrate:fresh

## Cierre
Archivos, tests, pendientes T3, blockers. Sin commit.
