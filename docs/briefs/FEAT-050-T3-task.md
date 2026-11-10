# Task Card — FEAT-050 T3

**Slice:** Ficha FT-OP-01 (historial + gráfico + análisis) + consolidado suma + dashboard KPI  
**shared-files:** `vite.config.js` (entrada charts), `resources/css/app.css` solo si falta import (ya existe 117)  
**Depende de:** T1+T2 (26 tests).

## Objetivo

La consulta y la captura se ven **como Operaciones FT-OP-01**: ficha de gestión, filas ene–dic por cada campo definido, nivel de cumplimiento, gráfico Apex (resultado vs meta), análisis mensual. “Todos los capturadores” = suma de cada campo y recálculo de la fórmula. Dashboard de pestaña con KPI del mes.

## 1. Consolidado

`PlaneacionConsolidadoService`:

- Capturadores = assignees `can_capture` del indicador (activos).
- Mes M: capturas de `versionForCapture` para ese mes.
- **Un usuario:** `input_data` de su captura; resultado guardado o recalculado.
- **Todos:** para cada key de `fields` de la versión de ese mes, **sumar** valores numéricos de las capturas; evaluar fórmula con esas sumas; `complies` con meta/operador de esa versión. No promediar %.
- Año: 12 columnas. Si un mes está cerrado o sin versión, celdas vacías.
- Si v1 y v2 en el mismo año, cada mes usa su versión; las filas de la tabla son las keys de la versión del **mes seleccionado** (o published actual). Keys faltantes en otro mes → vacío.

Quién ve filtro capturador (`Todos` + lista):
- `indicadores.edit` (Calidad): todos los capturadores del indicador.
- `can_consult`: solo capturadores de **ese** indicador.
- Capturador sin consult: **sin** filtro; siempre sus datos.

## 2. Ficha visual (clonar `capture-form.blade.php` de Operaciones)

Partial reutilizable `planeacion/partials/ficha-gestion.blade.php`:

- Encabezado formato (logo, título FICHA DEL INDICADOR DE GESTIÓN, código, periodo, versión, página).
- Nombre, objetivo, proceso, unidad, meta, frecuencia, tendencia (de operador), insumos, crítico, fórmula con etiquetas, responsabilidades.
- Tabla RESULTADOS ene–dic: **una fila por campo** (etiqueta) + fila cumplimiento % + fila meta + fila crítico (colores verde/rojo como FT-OP).
- GRÁFICOS: Apex mixed — resultado mensual (línea/barras) vs meta. `resources/js/planeacion-ficha-charts.js` + `sharedChart` / `apex-defaults`. Data-labels visibles, títulos centrados (dashboard-ui.mdc).
- ANÁLISIS DE RESULTADOS: 12 filas año/mes/texto/cumple/mejora. En modo Todos el análisis puede ir “—”; cumple/mejora del consolidado.

Montar la ficha:
- En **consulta** `indicadores.show` (añadir GET year, month, capturador_id; searchable-select; recarga GET).
- En **captura** `captura.show` debajo del formulario (solo el usuario titular; historial de su año).

Reutilizar clases `indicadores-sheet-panel` / `indicadores-board` si aplica, o equivalentes en `117-calidad-planeacion.css`. No copiar CSS de Operaciones a otro archivo de área; o reusar las clases globales ya usadas por indicadores.

## 3. Dashboard pestaña

Reemplazar placeholder. Clonar chrome GH (`gh-area-dashboard-page` / `cursos-dashboard-page__kpi`):

- Filtros año/mes (`<x-searchable-select>`).
- KPI: cantidad indicadores en alcance, % capturados (asignados vs capturas del mes), en verde, en rojo/crítico, pendientes.
- Tabla o cards por indicador: código, nombre, resultado **consolidado**, meta, estado. Director: solo `can_consult`. Capturador-only **no** ve esta pestaña (sin `dashboard.view`).
- Charts: 1–2 Apex (cumplimiento por indicador; pendientes). JS `resources/js/planeacion-dashboard-charts.js`. Registrar en `vite.config.js`.
- `abort_unless(canViewDashboard)`.

## 4. Tests

- `PlaneacionConsolidadoTest`: María 22/25/0.02 y Carlos 24/25/0 → Todos campo_1=46, campo_2=50, campo_3=0.02, resultado 94; filtro María = 90. Director no ve capturador ajeno a otro indicador.
- `PlaneacionDashboardTest`: consultor no ve KPI de indicador no asignado; editor sí. 403 sin `dashboard.view`.
- Consulta show con capturador_id filtra.
- No romper T1+T2.

`php artisan test --compact tests/Feature/Calidad/Planeacion* tests/Unit/Calidad/Planeacion*`
Pint dirty. `npm run build`.

## Prohibido

- Export Excel/PDF (fuera de este corte si no entra fácil; no bloquear).
- DOFA. Operaciones tables. Permisos nuevos. Docs finales. migrate:fresh.
- Select2. Dashboard genérico sin chrome GH.

## Cierre

Archivos, tests, blockers. Sin commit. Sin docs/modules (Documentador después).
