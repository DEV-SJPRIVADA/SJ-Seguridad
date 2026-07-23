# Modulo Indicadores (Operaciones)

Board **Indicadores** exclusivo del area `operaciones`. Integra captura KPI FT-OP-01…09 por usuario autenticado, dashboards, consolidado, ajustes y auditoria.

## Rutas

Prefijo: `/operaciones/indicadores` — nombre de ruta: `indicadores.*`

| Ruta | Permiso |
|---|---|
| Dashboard global | `operations.view` o `operations.manage` |
| Captura | `operations.capture` o `operations.manage` |
| Guardar captura (`POST .../captura/{code}`) | `operations.capture` o `operations.manage` |
| Ajustes (periodos, metas, auditoria) | `operations.manage` |
| Consolidado | `operations.manage` |
| Export PDF/Excel | `operations.export` |

Tabs de navegacion (`config/access.php` → `indicador_tabs`): dashboard, captura, consolidado, ajustes. El orden en config define el orden de subtabs via `App\Support\IndicadorNavigation`. Sin pestañas de jefes ni documentos internos.

La pestaña **Ajustes** (`indicadores.admin.ajustes`) agrupa tres secciones internas via query `?section=`:

| Seccion | Contenido |
|---|---|
| `periodos` (default) | Crear/cerrar/reabrir periodos de captura |
| `metas` | Operador (`>=`, `<=`, `==`), meta (%) y critico (%) por indicador; alimenta listado, captura y cumplimiento |
| `auditoria` | Log de cambios con filtros |
| `capturadores` | Usuarios activos del area Operaciones; toggle Activar/Inactivar permiso `operations.capture` (sin motivo en UI) |

Las rutas legacy `/admin/periodos`, `/admin/pesos` (redirige a metas), `/admin/metas`, `/admin/capturadores` y `/admin/auditoria` redirigen al tablero Ajustes con la seccion correspondiente. Los POST/PATCH de administracion se mantienen en las mismas rutas (`PATCH /admin/metas` guarda metas; `PATCH /admin/capturadores/{user}` activa captura; `PATCH /admin/pesos` sigue aceptado por compatibilidad).

## Permisos Spatie

- `operations.view` — ver dashboards
- `operations.capture` — capturar indicadores (propios del usuario autenticado)
- `operations.manage` — administracion completa (ajustes, consolidado)
- `operations.export` — exportaciones

El acceso es solo por permiso Spatie; las capturas se asocian a `user_id`.

## Modelos clave

- `User` — dueño de cada captura (`indicator_captures.user_id`)
- `Indicator`, `Period` (`indicator_periods`), `IndicatorCapture`
- `DashboardWeight`, `DashboardSummary`
- `Improvement` — plan de mejora ligado a una captura en rojo

## Seeders

- `IndicadorSeeder` — 9 indicadores FT-OP con `target_value` (meta) y `critical_value` (critico)
- `DashboardWeightSeeder` — pesos internos del score global del dashboard (sin UI de ajuste)

## Configuracion

- `config/indicators.php` — anio base, meses y codigos de captura FT-OP
- `config/access.php` — board `indicadores`, tabs y permisos `operations.*` (bloque `area_indicador_permissions.operaciones`; asignacion en UI bajo Alcance por Area → Operaciones)

## UI

Vistas en `resources/views/areas/operaciones/` con layout `<x-app-layout>`, paneles corporativos y subtabs via `App\Support\IndicadorNavigation`.

Captura mensual: `IndicadorController` + `IndicatorCaptureService` + Blade + JS vanilla (`public/js/indicadores-capture.js`), estilos en `public/css/indicadores.css`. Persistencia via `POST indicadores.capture.store`. El usuario de captura es el autenticado (readonly en filtros).

Los tableros usan la clase contenedora `indicadores-board` para tablas compactas, filtros acotados y botones al ancho de su contenido.

El dashboard global muestra KPIs del mes en tabla (`supply-table`) con columnas Codigo, Indicador, resultado del mes anterior, Resultado, Meta y Estado.

La seccion **Indicadores criticos** lista solo capturas en umbral critico por usuario (columnas Usuario, Indicador, Valor critico). La regla usa `critical_value` y el operador del indicador: con `>=` cuando el resultado cae por debajo del critico; con `<=` o `==` cuando lo supera.

El consolidado agrega capturas de usuarios del area Operaciones con permiso `operations.capture` o `operations.manage` (gestion en Ajustes → Capturadores).

## Exportaciones

Servicio `App\Services\Indicadores\IndicatorReportExporter` (PhpSpreadsheet, sin maatwebsite/excel).

| Ruta | Descripcion |
|---|---|
| `indicadores.export.dashboard.pdf` | PDF dashboard ejecutivo |
| `indicadores.export.leader.excel` | Excel captura por usuario (`user_id`, `year`, `month`; default auth) |
| `indicadores.export.leader.pdf` | PDF captura por usuario |
| `indicadores.export.consolidado.excel` | Excel consolidado |
| `indicadores.export.consolidado.pdf` | PDF consolidado |
| `indicadores.export.management.pptx` | Informe de gestion FO-GI-39 (PowerPoint) |

Requiere permiso `operations.export`.

## Informe de gestion FO-GI-39 (PowerPoint)

Plantilla sanitizada: `storage/app/templates/operaciones/FO-GI-39-v7.template.pptx`

Servicios:

- `ManagementReportDataBuilder` — KPIs, narrativa y series mensuales por FT-OP.
- `ManagementReportPptxArchive` — extrae/reempaqueta la plantilla PPTX en disco temporal.
- `ManagementReportChartInjector` — inyecta graficos desde `chart-prototype/` cuando la plantilla no los trae.
- `ManagementReportChartSanitizer` — elimina referencias Excel/extensiones invalidas del XML de graficos.
- `ManagementReportChartUpdater` — actualiza caches mensuales del grafico.
- `ManagementReportPptxExporter` — orquesta placeholders, graficos y descarga del informe.

Ruta: `GET indicadores.export.management.pptx?year=&month=`. Boton **Informe PPTX** en dashboard de indicadores.

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

- Guia de usuario: [`docs/user/indicadores.md`](../user/indicadores.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
