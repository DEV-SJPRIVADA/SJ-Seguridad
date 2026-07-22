# Modulo Indicadores (Operaciones)

Board **Indicadores** exclusivo del area `operaciones`. Integra captura KPI FT-OP-01…09 por usuario autenticado, dashboards, consolidado (MADRE), ajustes y auditoria.

## Rutas

Prefijo: `/operaciones/indicadores` — nombre de ruta: `indicadores.*`

| Ruta | Permiso |
|---|---|
| Dashboard global | `operations.view` o `operations.manage` |
| Captura | `operations.capture` o `operations.manage` |
| Guardar captura (`POST .../captura/{code}`) | `operations.capture` o `operations.manage` |
| Ajustes (periodos, pesos, auditoria) | `operations.manage` |
| Consolidado (MADRE) | `operations.manage` |
| Export PDF/Excel | `operations.export` |

Tabs de navegacion (`config/access.php` → `indicador_tabs`): dashboard, captura, ajustes, madre (Consolidado). Sin pestañas de jefes ni documentos internos.

La pestaña **Ajustes** (`indicadores.admin.ajustes`) agrupa tres secciones internas via query `?section=`:

| Seccion | Contenido |
|---|---|
| `periodos` (default) | Crear/cerrar/reabrir periodos de captura |
| `pesos` | Pesos del score global del dashboard |
| `auditoria` | Log de cambios con filtros |

Las rutas legacy `/admin/periodos`, `/admin/pesos` y `/admin/auditoria` redirigen al tablero Ajustes con la seccion correspondiente. Los POST/PATCH de administracion se mantienen en las mismas rutas.

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

- `IndicadorSeeder` — 9 indicadores FT-OP
- `DashboardWeightSeeder` — pesos del score global

## Configuracion

- `config/indicators.php` — anio base, meses y codigos de captura FT-OP
- `config/access.php` — board `indicadores`, tabs y permisos `operations.*` (bloque `area_indicador_permissions.operaciones`; asignacion en UI bajo Alcance por Area → Operaciones)

## UI

Vistas en `resources/views/areas/operaciones/` con layout `<x-app-layout>`, paneles corporativos y subtabs via `App\Support\IndicadorNavigation`.

Captura mensual: `IndicadorController` + `IndicatorCaptureService` + Blade + JS vanilla (`public/js/indicadores-capture.js`), estilos en `public/css/indicadores.css`. Persistencia via `POST indicadores.capture.store`. El usuario de captura es el autenticado (readonly en filtros).

Los tableros usan la clase contenedora `indicadores-board` para tablas compactas, filtros acotados y botones al ancho de su contenido.

El dashboard global muestra KPIs del mes en tabla (`supply-table`) para evitar solapamiento de texto en tarjetas pequenas.

MADRE consolida capturas de usuarios con permiso `operations.capture` o `operations.manage`.

## Exportaciones

Servicio `App\Services\Indicadores\IndicatorReportExporter` (PhpSpreadsheet, sin maatwebsite/excel).

| Ruta | Descripcion |
|---|---|
| `indicadores.export.dashboard.pdf` | PDF dashboard ejecutivo |
| `indicadores.export.leader.excel` | Excel captura por usuario (`user_id`, `year`, `month`; default auth) |
| `indicadores.export.leader.pdf` | PDF captura por usuario |
| `indicadores.export.mother.excel` | Excel consolidado MADRE |
| `indicadores.export.mother.pdf` | PDF consolidado MADRE |

Requiere permiso `operations.export`.

## Despliegue

```bash
php artisan migrate
php artisan db:seed --class=IndicadorSeeder
php artisan db:seed --class=DashboardWeightSeeder
```

(Tambien incluidos en `DatabaseSeeder`.)

## Referencias

- Guia de usuario: [`docs/user/indicadores.md`](../user/indicadores.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
