# Modulo Indicadores (Operaciones)

Board **Indicadores** exclusivo del area `operaciones`. Integra captura KPI FT-OP-01…09, dashboards, MADRE, periodos, pesos y auditoria.

## Rutas

Prefijo: `/operaciones/indicadores` — nombre de ruta: `indicadores.*`

| Ruta | Permiso |
|---|---|
| Dashboard global | `operations.view` o `operations.manage` |
| Captura | `operations.capture` o `operations.manage` |
| Jefes de operaciones | `operations.view` o `operations.manage` |
| Admin (periodos, pesos, documentos, MADRE, auditoria) | `operations.manage` |
| Export PDF dashboard | `operations.export` |

## Permisos Spatie

- `operations.view` — ver dashboards y jefes
- `operations.capture` — capturar indicadores (todos los jefes activos)
- `operations.manage` — administracion completa
- `operations.export` — exportaciones

No hay asignacion por zona/jefe en usuarios: el acceso es solo por permiso.

## Modelos clave

- `OperationsLeader` — catalogo administrable de jefes (`operations_leaders`)
- `Indicator`, `Period` (`indicator_periods`), `IndicatorCapture`
- `DashboardWeight`, `DashboardSummary`
- `IndicatorSystemDocument` / `IndicatorSystemDocumentVersion` — docs internos del modulo (separados de `QualityDocument`)

## Seeders

- `IndicadorSeeder` — 9 indicadores FT-OP
- `DashboardWeightSeeder` — pesos del score global
- **No** se siembran jefes de operaciones (catalogo inicial vacio)

## Configuracion

- `config/indicators.php` — anio base, meses, mapa Livewire por codigo
- `config/access.php` — board `indicadores`, tabs y permisos `operations.*` (bloque `area_indicador_permissions.operaciones`; asignacion en UI bajo Alcance por Area → Operaciones)

## UI

Vistas en `resources/views/areas/operaciones/` con layout `<x-app-layout>`, paneles corporativos y subtabs via `App\Support\IndicadorNavigation`.

Formularios Livewire en `app/Livewire/Indicadores/` con estilos complementarios en `public/css/indicadores.css`.

El dashboard global muestra KPIs del mes en tabla (`supply-table`) para evitar solapamiento de texto en tarjetas pequeñas.

## Exportaciones

Servicio `App\Services\Indicadores\IndicatorReportExporter` (PhpSpreadsheet, sin maatwebsite/excel).

| Ruta | Descripcion |
|---|---|
| `indicadores.export.dashboard.pdf` | PDF dashboard ejecutivo |
| `indicadores.export.leader.excel` | Excel captura por jefe (`operations_leader_id`, `year`, `month`) |
| `indicadores.export.leader.pdf` | PDF captura por jefe |
| `indicadores.export.mother.excel` | Excel consolidado MADRE |
| `indicadores.export.mother.pdf` | PDF consolidado MADRE |

Requiere permiso `operations.export`.

## Documentacion interna

CRUD en rutas `indicadores.admin.documents.*` sobre tablas `indicator_system_documents` y `indicator_system_document_versions` (separadas de `QualityDocument`). Al actualizar pesos del dashboard se versiona automaticamente el documento `pesos-dashboard`.

Ejecutar en entorno local/despliegue:

```bash
php artisan migrate
php artisan db:seed --class=IndicadorSeeder
php artisan db:seed --class=DashboardWeightSeeder
```

(Tambien incluidos en `DatabaseSeeder`.)
