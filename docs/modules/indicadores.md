# Modulo Indicadores (Operaciones)

Board **Indicadores** exclusivo del area `operaciones`. Integra captura KPI FT-OP-01…09 por usuario autenticado, dashboards, consolidado, ajustes y auditoria.

## Rutas

Prefijo: `/operaciones/indicadores` — nombre de ruta: `indicadores.*`

| Ruta | Permiso |
|---|---|
| Dashboard global | `operations.view` o `operations.manage` |
| Captura (`GET/POST .../captura/{indicator}`) | `canAccessCaptureScreen`: `operations.capture`, `operations.manage` o `operations.capture.delegate` |
| Ajustes (periodos, metas, auditoria) | `operations.manage` |
| Ajustes → Capturadores: toggle Captura | `operations.manage` |
| Ajustes → Capturadores: toggle Suplencia | `operations.manage` (`PATCH .../capturadores/{user}/suplencia`) |
| Consolidado | `operations.manage` |
| Export PDF/Excel | `operations.export` |

Tabs de navegacion (`config/access.php` → `indicador_tabs`): dashboard, captura, consolidado, ajustes. El orden en config define el orden de subtabs via `App\Support\IndicadorNavigation`. Sin pestañas de jefes ni documentos internos.

La pestaña **Ajustes** (`indicadores.admin.ajustes`) agrupa tres secciones internas via query `?section=`:

| Seccion | Contenido |
|---|---|
| `periodos` (default) | Crear/cerrar/reabrir periodos de captura |
| `metas` | Operador (`>=`, `<=`, `==`), meta (%) y critico (%) por indicador; alimenta listado, captura y cumplimiento |
| `auditoria` | Log de cambios con filtros (tabla central `audit_logs`, `module=indicadores`) |
| `capturadores` | Usuarios activos del area Operaciones; columnas independientes **Captura** (`operations.capture`) y **Suplencia** (`operations.capture.delegate`) |

Las rutas legacy `/admin/periodos`, `/admin/pesos` (redirige a metas), `/admin/metas`, `/admin/capturadores` y `/admin/auditoria` redirigen al tablero Ajustes con la seccion correspondiente. Los POST/PATCH de administracion se mantienen en las mismas rutas (`PATCH /admin/metas` guarda metas; `PATCH /admin/capturadores/{user}` activa captura; `PATCH /admin/capturadores/{user}/suplencia` activa suplencia — ruta `indicadores.admin.capturadores.delegate.update`; `PATCH /admin/pesos` sigue aceptado por compatibilidad).

## Permisos Spatie

| Permiso | Uso |
| --- | --- |
| `operations.view` | Ver dashboards |
| `operations.capture` | Captura **propia** del usuario autenticado (titular = actor) |
| `operations.capture.delegate` | Captura **a nombre de** un titular de `capturableUsers()`; **no** otorga `operations.capture` |
| `operations.manage` | Administracion (ajustes, consolidado); no implica suplencia salvo asignacion explicita |
| `operations.export` | Exportaciones |

Registrado en `config/access.php` → `area_indicador_permissions.operaciones` y subgroup `indicadores` del board Operaciones. Label: *Indicadores: Capturar por suplencia*.

**Visibilidad tab Captura:** `User::canAccessIndicadorTab('capture')` e `IndicadorNavigation` delegan en `IndicatorCaptureAccessService::canAccessCaptureScreen()` (`canCaptureIndicators` **OR** `canDelegateCapture`).

## Captura delegada (titular / digitador) — FEAT-023

Cuando un jefe capturador esta ausente (vacaciones, etc.), un suplente con `operations.capture.delegate` registra indicadores **a nombre del titular**. Dashboard, ranking, consolidado y exportaciones atribuyen la captura al titular (`user_id`), no al suplente.

### Semantica en BD (sin migracion)

| Columna | Rol | Descripcion |
| --- | --- | --- |
| `indicator_captures.user_id` | **Titular** | Jefe capturador cuyos KPIs se alimentan |
| `indicator_captures.created_by_user_id` | **Digitador** (alta) | Usuario que opero el formulario en create |
| `indicator_captures.updated_by_user_id` | **Digitador** (update) | Usuario que guardo la ultima edicion |
| `indicator_improvements.user_id` | **Titular** | Plan de mejora ligado al titular |
| `indicator_improvements.created_by_user_id` | **Digitador** (solo create) | Suplente o titular que registro la mejora |

Unicidad: una fila por `(indicator_id, user_id titular, period_id)`.

### Parametros HTTP

| Contexto | Parametro | Tipo | Notas |
| --- | --- | --- | --- |
| GET `indicadores.show` | `capturador_id` | `int` opcional (query) | Al cambiar selector ano/mes/capturador; recarga GET |
| POST `indicadores.capture.store` | `capturador_user_id` | `int` | Hidden o select; validado en `StoreIndicatorCaptureRequest` |

No usar alias `capturador`, `user_id` ni otros nombres.

**Errores:** titular no pertenece a `capturableUsers()` → `404` (GET show) o `422` (POST store). POST sin `capturador_user_id` cuando es obligatorio → `422`.

### Resolucion de titular — `IndicatorCaptureAccessService::resolveTitularUser(User $actor, ?int $capturadorId)`

| Perfil del actor | Regla |
| --- | --- |
| Solo `operations.capture` (sin delegate) | Titular = `$actor`; ignora IDs ajenos |
| Solo `operations.capture.delegate` | `capturador_id` debe ser ID valido en `capturableUsers()`; si ausente → default = **primer** usuario de la lista (orden por nombre); lista vacia → `403` |
| `operations.capture` + `operations.capture.delegate` | Si `capturador_id` valido → ese titular; si ausente y actor ∈ `capturableUsers()` → self; si ausente y actor ∉ lista → `422` / selector obligatorio |
| `operations.manage` sin delegate | Sin suplencia; captura propia solo si tiene `operations.capture` |

Lista blanca del selector: `capturableUsers()` — usuarios activos Operaciones con `operations.capture` o `operations.manage`.

### Servicio de acceso — metodos clave

| Metodo | Comportamiento |
| --- | --- |
| `canDelegateCapture(User)` | Activo, `area_key=operaciones`, permiso `operations.capture.delegate` |
| `canAccessCaptureScreen(User)` | `canCaptureIndicators` OR `canDelegateCapture` |
| `delegatePermissionsToGrant()` | `operations.capture.delegate`, `operations.view`, `view.board.operaciones.indicadores` (+ `view.area.operaciones` si aplica); **sin** `operations.capture` |
| `setDelegateCaptureEnabled(User, bool)` | Toggle Suplencia en Ajustes; grant/revoke delegate + permisos minimos |
| `resolveTitularUser(User, ?int)` | Ver tabla anterior |

### Flujo captura

1. **show:** middleware `indicador.tab:capture` → `resolveTitularUser` → `buildShowContext(..., titular, actor)` carga datos del titular. Vista: `showCapturadorSelector` = `canDelegateCapture(actor)`; `isDelegatedCapture` = titular ≠ actor.
2. **store:** `StoreIndicatorCaptureRequest::authorize()` exige `canAccessCaptureScreen`; resuelve titular; `IndicatorCaptureService::save(..., titular, actor)` persiste columnas titular/digitador.
3. **Auditoria:** eventos `indicator_capture` create/update incluyen `metadata: { delegated: true, titular_user_id, actor_user_id }` solo cuando titular ≠ digitador. Captura propia: sin metadata extra.
4. **Periodo cerrado:** misma validacion que captura propia; bloquea guardado delegado.

### Ajustes → Capturadores

Columna **Suplencia** independiente de **Captura**. `IndicadorController::updateCapturadorDelegate` → `setDelegateCaptureEnabled`. Activar suplencia no concede `operations.capture`. Usuarios manage-only no pueden desactivar su propia captura (regla existente en Captura); Suplencia aplica a cualquier usuario activo Operaciones.

## Modelos clave

- `User` — titular de cada captura (`indicator_captures.user_id`); digitador en `created_by_user_id` / `updated_by_user_id`
- `Indicator`, `Period` (`indicator_periods`), `IndicatorCapture`
- `DashboardWeight`
- `Improvement` — plan de mejora ligado a una captura en rojo
- `AuditLog` — auditoria central (`audit_logs`, `module=indicadores`, `area=operaciones`); escritura via `AuditLogService` wrapper

## Auditoria

Los eventos se persisten en `audit_logs` (no en `indicator_audit_logs`). Escritura: `App\Services\Indicadores\AuditLogService` → `SystemAuditService`. Lectura Ajustes: `AuditLog::forModule('indicadores')`. Migracion historica: `php artisan audit:migrate-indicator-logs`.

## Seeders

- `IndicadorSeeder` — 9 indicadores FT-OP con `target_value` (meta) y `critical_value` (critico)
- `DashboardWeightSeeder` — pesos internos del score global del dashboard (sin UI de ajuste)

## Configuracion

- `config/indicators.php` — anio base, meses y codigos de captura FT-OP
- `config/access.php` — board `indicadores`, tabs y permisos `operations.*` (bloque `area_indicador_permissions.operaciones`; asignacion en UI bajo Alcance por Area → Operaciones)

## UI

Vistas en `resources/views/areas/operaciones/` con layout `<x-app-layout>`, paneles corporativos y subtabs via `App\Support\IndicadorNavigation`.

Captura mensual: `IndicadorController` + `IndicatorCaptureService` + `IndicatorCaptureAccessService` + Blade + **ApexCharts** via Vite (`resources/js/indicadores-capture.js`; metrics/modales + charts FT-OP-01/03 mixed bar/line). Estilos en `public/css/indicadores.css`. Persistencia via `POST indicadores.capture.store`.

Vista `indicadores/show.blade.php`: selector **Capturador** (GET `capturador_id`, onchange submit) visible si `canDelegateCapture`; hidden POST `capturador_user_id`; cabecera muestra nombre del titular. Usuarios solo-captura: sin selector; titular = actor.

El archivo `public/js/indicadores-capture.js` esta deprecado (stub con `console.warn`; no carga ECharts). **FEAT-010:** Chart.js/ECharts retirados del runtime; estandar ApexCharts compartido con Comercial y GH.

Los tableros usan la clase contenedora `indicadores-board` para tablas compactas, filtros acotados y botones al ancho de su contenido.

El dashboard global muestra KPIs del mes en tabla (`supply-table`) con columnas Codigo, Indicador, resultado del mes anterior, Resultado, Meta y Estado.

La seccion **Indicadores criticos** lista solo capturas en umbral critico por usuario (columnas Usuario, Indicador, Valor critico). La regla usa `critical_value` y el operador del indicador: con `>=` cuando el resultado cae por debajo del critico; con `<=` o `==` cuando lo supera.

**Ranking de usuarios:** solo usuarios con al menos una captura en el periodo. Columnas: posicion, Usuario, cantidad de indicadores gestionados (capturas del mes), **% gestionado** (capturas del usuario sobre total de indicadores activos FT-OP, redondeado) y cantidad de mejoras ingresadas (registros `Improvement` ligados a esas capturas). Orden: mas indicadores gestionados primero; empate por mejoras y nombre.

El consolidado agrega capturas de usuarios del area Operaciones con permiso `operations.capture` o `operations.manage` (gestion en Ajustes → Capturadores).

**FT-OP-01 … FT-OP-09 (v2 consolidado):** la ruta `indicadores.admin.consolidado.show` reutiliza la misma ficha y grafico de captura (`capture-form.blade.php` o `capture-form-03.blade.php` + `indicadores-capture.js`) en modo solo lectura para los codigos en `config/indicators.php` → `consolidado_capture_view_codes`. Filtros: ano, mes y **capturador** (`user_id` opcional). Sin capturador seleccionado muestra datos consolidados (suma de todos los capturadores; FT-OP-03 agrega KPIs A/B y clasificacion consolidada). Con capturador muestra la captura individual. Export: consolidado Excel/PDF cuando es “Todos”; leader Excel/PDF cuando hay capturador.

## Exportaciones

Servicio `App\Services\Indicadores\IndicatorReportExporter` (PhpSpreadsheet, sin maatwebsite/excel).

| Ruta | Descripcion |
|---|---|
| `indicadores.export.dashboard.pdf` | PDF dashboard ejecutivo |
| `indicadores.export.leader.excel` | Excel captura por usuario (`user_id`, `year`, `month`; default auth) |
| `indicadores.export.leader.pdf` | PDF captura por usuario |
| `indicadores.export.consolidado.excel` | Excel consolidado |
| `indicadores.export.consolidado.pdf` | PDF consolidado |
| `indicadores.export.management.preview` | Vista previa HTML del informe FO-GI-39 con narrativas editables |
| `indicadores.export.management.draft.store` | Guarda borrador (titulo + narrativas) por ano/mes |
| `indicadores.export.management.draft.regenerate` | Elimina el borrador y vuelve a las narrativas auto-generadas |
| `indicadores.export.management.pptx` | Informe de gestion FO-GI-39 (PowerPoint), usa el borrador si existe |

Requiere permiso `operations.export`.

**Botones en UI:** Excel/PDF visibles en **Consolidado admin** (`/admin/consolidado/{indicator}`), no en la pantalla de **Captura** (`/captura/{indicator}`). Las rutas `leader.*` siguen disponibles por API/direct URL (p. ej. consolidado con capturador seleccionado).

**PDF alineado con la UI (DomPDF):**

| Export | Contenido |
|---|---|
| Dashboard PDF | Score global, KPIs del mes, ranking de usuarios e indicadores criticos (equivalente a `panel__body`, sin filtros) |
| Captura PDF (`leader.pdf`) | Campos del indicador, metricas (resultado/semaforo/cumple/mejora), analisis de mejora y ficha completa (`indicadores-sheet-panel`) con grafico PNG generado server-side |
| Consolidado PDF (`consolidado.pdf`, FT-OP-01…09) | Misma ficha que la vista consolidado v2; **A4 vertical**, hoja 1 = ficha + GRAFICOS, hoja 2 = solo analisis de resultados |

Servicios: `IndicatorCapturePdfPresenter` (contexto + filas de formulario), `IndicatorPdfChartRenderer` (GD PNG embebido en base64). Vistas: `resources/views/areas/operaciones/exports/capture-pdf.blade.php` y partials en `exports/partials/`. Excel mantiene el formato tabular anterior.

## Informe de gestion FO-GI-39 (PowerPoint)

Plantilla sanitizada: `storage/app/templates/operaciones/FO-GI-39-v7.template.pptx`

Servicios:

- `ManagementReportDataBuilder` — KPIs, narrativa y series mensuales por FT-OP; aplica el borrador guardado (`ManagementReportDraftService`) al final de `build()`.
- `ManagementReportDraftService` — CRUD del borrador (`ManagementReportDraft` / tabla `indicator_management_report_drafts`, unica por ano/mes): `getDraft()`, `saveDraft()`, `clearDraft()`, `applyDraftToReport()`.
- `ManagementReportPptxArchive` — extrae/reempaqueta la plantilla PPTX en disco temporal.
- `ManagementReportChartInjector` — inyecta graficos desde `chart-prototype/` cuando la plantilla no los trae.
- `ManagementReportChartSanitizer` — elimina referencias Excel/extensiones invalidas del XML de graficos.
- `ManagementReportChartUpdater` — actualiza caches mensuales del grafico.
- `ManagementReportPptxExporter` — orquesta placeholders, graficos y descarga del informe.

Flujo: `GET indicadores.export.management.preview?year=&month=` muestra una vista HTML (no un render PPTX fiel) con el titulo de portada, **graficos ApexCharts mensuales** (misma serie que el PPTX: denominador, numerador, resultado y meta) y las 9 narrativas FT-OP-01…09 precargadas (analisis de captura del mes o texto auto-generado). Datos de graficos: `chart_series` en `ManagementReportDataBuilder`; JS `resources/js/management-report-preview-charts.js`. El usuario edita y guarda con `POST indicadores.export.management.draft.store` (`report_title` opcional, `narratives[FT-OP-XX]`); el borrador se guarda con `updated_by_user_id`. **Regenerar textos** (`POST .../draft.regenerate`) elimina el borrador del periodo y vuelve a los textos auto-generados. La descarga (`GET indicadores.export.management.pptx`) reutiliza `ManagementReportDataBuilder::build()`, que ya aplica el borrador si existe. Boton **Preparar informe PPTX** en el dashboard de indicadores abre la vista previa.

Documentacion de placeholders: `storage/app/templates/operaciones/README.md`. Mapeo en `config/indicators.php` → `management_report`. Regenerar plantilla: `python tools/sanitize_pptx_template.py`. Prototipo de graficos: `python tools/extract_chart_prototype.py`.

## Despliegue

```bash
php artisan migrate
php artisan db:seed --class=IndicadorSeeder
php artisan db:seed --class=DashboardWeightSeeder
php artisan indicadores:seed-demo --force
```

Datos demo: capturas para los 9 FT-OP (meses 1–12 del anio base) con usuario `operaciones.demo@sjseguridad.test` / `password`. Reabre periodos cerrados del anio base. Si la plantilla PPTX no trae graficos, el export los inyecta desde `storage/app/templates/operaciones/chart-prototype/`.

(Tambien incluidos en `DatabaseSeeder` los seeders de catalogo; el demo es opcional via comando.)

## Referencias

- Feature Brief: [`docs/briefs/FEAT-024.md`](../briefs/FEAT-024.md) — preview HTML informe FO-GI-39 con narrativas editables
- Feature Brief: [`docs/briefs/FEAT-023.md`](../briefs/FEAT-023.md) — captura delegada (suplencia)
- Review: [`docs/reviews/FEAT-023.md`](../reviews/FEAT-023.md)
- Guia de usuario: [`docs/user/indicadores.md`](../user/indicadores.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
