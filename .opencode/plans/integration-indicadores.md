# Integracion: Modulo Indicadores Operativos

## Objetivo

Integrar el sistema de captura y monitoreo de KPIs operativos (originalmente en `IndicadoresSJ`, Laravel 10) como un board exclusivo del area `operaciones` en SJSEGURIDAD (Laravel 13, Spatie permissions).

## Arquitectura

- **Tipo:** Area-specific (no compartido)
- **Rutas:** `routes/areas/operaciones.php`
- **Controladores:** `App\Http\Controllers\Operaciones\` (1 controlador)
- **Vistas:** `resources/views/areas/operaciones/`
- **Livewire:** `App\Livewire\Indicadores\` (9 formularios interactivos)
- **Servicios:** `App\Services\Indicadores\`

## Permisos (config/access.php)

```php
'system_permissions' => [
    'operations.view'        => 'Operaciones: Ver indicadores y dashboards',
    'operations.manage'      => 'Operaciones: Administrar indicadores (pesos, periodos, documentos, MADRE)',
    'operations.capture'     => 'Operaciones: Capturar datos de indicadores',
    'operations.export'      => 'Operaciones: Exportar a Excel/PDF',
],

'indicador_tabs' => [
    'dashboard'  => 'Dashboard',
    'captura'    => 'Captura',
    'grupos'     => 'Grupos',
    'reportes'   => 'Reportes',
    'periodos'   => 'Periodos',
    'pesos'      => 'Pesos',
    'documentos' => 'Documentos',
    'madre'      => 'MADRE',
    'auditoria'  => 'Auditoria',
],
```

## Modelos (App\Models\)

| Modelo | Tabla | Notas |
|---|---|---|
| `Indicator` | `indicators` | Codigo, nombre, unidad, meta, operador, frecuencia, formula |
| `IndicatorCapture` | `indicator_captures` | FK indicator_id, period_id, captured_by (users.id), grupo_operaciones (tinyint 1-5) |
| `Improvement` | `improvements` | FK indicator_capture_id (unique), analisis, acciones |
| `Period` | `periods` | Anio, mes, estado (open/closed) |
| `DashboardWeight` | `dashboard_weights` | FK indicator_id, weight (suma=100%) |
| `Document` | `documents` | Slug, titulo, alcance, current_version_id |
| `DocumentVersion` | `document_versions` | FK document_id, version, contenido, autor |
| `AuditLog` | `audit_logs` | user_id, evento, accion, auditable morph |
| `DashboardSummary` | `dashboard_summaries` | Anio, mes, resumen (unique pair) |

## Migraciones

Todas con timestamp posterior a `2026_07_11_*`:

1. `2026_07_11_100000_create_indicators_table.php`
2. `2026_07_11_101000_create_periods_table.php`
3. `2026_07_11_102000_create_indicator_captures_table.php`
4. `2026_07_11_103000_create_improvements_table.php`
5. `2026_07_11_104000_create_dashboard_weights_table.php`
6. `2026_07_11_105000_create_documents_table.php`
7. `2026_07_11_106000_create_document_versions_table.php`
8. `2026_07_11_107000_create_audit_logs_table.php`
9. `2026_07_11_108000_create_dashboard_summaries_table.php`

## Dependencias (composer.json)

```json
"livewire/livewire": "^4.2",
"barryvdh/laravel-dompdf": "^2.0",
"maatwebsite/excel": "^3.1"
```

## Controlador Unico

### `App\Http\Controllers\Operaciones\IndicadorController.php`

Sigue el mismo patron de `RequisitionController` (un controlador, muchos metodos):

| Metodo | Ruta | Vista | Permiso |
|---|---|---|---|
| `dashboard()` | GET /operaciones/indicadores/dashboard | `areas.operaciones.admin.index` | view/manage |
| `index()` | GET /operaciones/indicadores/captura | `areas.operaciones.indicadores.index` | capture |
| `show()` | GET /captura/{indicator:code} | `areas.operaciones.indicadores.show` | capture |
| `groupDashboard()` | GET /grupos/{grupo} | `areas.operaciones.grupos.show` | view |
| `exportExcel()` | GET /exportar/excel/{indicator}/{grupo} | StreamedResponse | export |
| `exportPdf()` | GET /exportar/pdf/{indicator}/{grupo} | PDF | export |
| `periods()` | GET /periodos | `areas.operaciones.periodos.index` | manage |
| `closePeriod()` | POST /periodos/{period}/cerrar | Redirect | manage |
| `reopenPeriod()` | POST /periodos/{period}/reabrir | Redirect | manage |
| `weights()` | GET /pesos | `areas.operaciones.configuracion.pesos` | manage |
| `updateWeights()` | PATCH /pesos | Redirect | manage |
| `documents()` | GET /documentos | `areas.operaciones.documentos.index` | manage |
| `createDocument()` | GET /documentos/crear | `areas.operaciones.documentos.create` | manage |
| `storeDocument()` | POST /documentos | Redirect | manage |
| `editDocument()` | GET /documentos/{doc}/editar | `areas.operaciones.documentos.edit` | manage |
| `updateDocument()` | PATCH /documentos/{doc} | Redirect | manage |
| `storeVersion()` | POST /documentos/{doc}/versiones | Redirect | manage |
| `mother()` | GET /madre | `areas.operaciones.madre.index` | manage |
| `motherShow()` | GET /madre/{indicator:code} | `areas.operaciones.madre.show` | manage |
| `auditLog()` | GET /auditoria | `areas.operaciones.auditoria.index` | manage |

## Livewire Components

`App\Livewire\Indicadores\` (9 formularios + base abstracta):

```
BaseIndicatorForm.php (abstracta)
FtOp01Form.php  - Capacitacion
FtOp02Form.php  - Servicios No Conformes
FtOp03Form.php  - Siniestralidad (medicion dual)
FtOp04Form.php  - Eficacia Supervision
FtOp05Form.php  - Visita a Clientes
FtOp06Form.php  - Estrategias para evitar materializacion
FtOp07Form.php  - Analisis de Riesgos
FtOp08Form.php  - Inventario puestos seguridad fisica
FtOp09Form.php  - Inventario de armas
```

Vistas Livewire: `resources/views/livewire/indicadores/`

## Routing

### `routes/areas/operaciones.php`

```php
<?php

use App\Http\Controllers\Operaciones\IndicadorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'password.changed'])
    ->prefix('operaciones/indicadores')
    ->name('indicadores.')
    ->group(function () {
        Route::get('/dashboard', [IndicadorController::class, 'dashboard'])->name('dashboard');

        Route::middleware(['indicador.tab:capture'])->group(function () {
            Route::get('/captura', [IndicadorController::class, 'index'])->name('index');
            Route::get('/captura/{indicator:code}', [IndicadorController::class, 'show'])->name('show');
        });

        Route::middleware(['indicador.tab:groups'])->group(function () {
            Route::get('/grupos/{grupo}', [IndicadorController::class, 'groupDashboard'])->name('groups.show');
        });

        Route::middleware(['can:operations.export'])->group(function () {
            Route::get('/exportar/excel/{indicator:code}/{grupo}', [IndicadorController::class, 'exportExcel'])->name('export.excel');
            Route::get('/exportar/pdf/{indicator:code}/{grupo}', [IndicadorController::class, 'exportPdf'])->name('export.pdf');
        });

        Route::middleware(['can:operations.manage'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/periodos', [IndicadorController::class, 'periods'])->name('periods.index');
            Route::post('/periodos/{period}/cerrar', [IndicadorController::class, 'closePeriod'])->name('periods.close');
            Route::post('/periodos/{period}/reabrir', [IndicadorController::class, 'reopenPeriod'])->name('periods.reopen');

            Route::get('/pesos', [IndicadorController::class, 'weights'])->name('weights');
            Route::patch('/pesos', [IndicadorController::class, 'updateWeights'])->name('weights.update');

            Route::get('/documentos', [IndicadorController::class, 'documents'])->name('documents.index');
            Route::get('/documentos/crear', [IndicadorController::class, 'createDocument'])->name('documents.create');
            Route::post('/documentos', [IndicadorController::class, 'storeDocument'])->name('documents.store');
            Route::get('/documentos/{document}/editar', [IndicadorController::class, 'editDocument'])->name('documents.edit');
            Route::patch('/documentos/{document}', [IndicadorController::class, 'updateDocument'])->name('documents.update');
            Route::post('/documentos/{document}/versiones', [IndicadorController::class, 'storeVersion'])->name('documents.versions.store');

            Route::get('/madre', [IndicadorController::class, 'mother'])->name('mother.index');
            Route::get('/madre/{indicator:code}', [IndicadorController::class, 'motherShow'])->name('mother.show');

            Route::get('/auditoria', [IndicadorController::class, 'auditLog'])->name('audit.index');
        });
    });
```

### `routes/web.php` (cambios)

```php
// Dentro del grupo middleware auth, active, password.changed
require __DIR__.'/areas/operaciones.php';

// En el dashboard redirect:
if ($selectedBoardKey === 'indicadores') {
    return redirect()->route('indicadores.dashboard');
}
```

## Navegacion (AppServiceProvider)

En `resolveNavigation()`, dentro del mapeo de boards por area, agregar:

```php
if ($boardKey === 'indicadores') {
    if ($key !== 'operaciones') {
        return null;
    }

    if (! $user->can('operations.view') && ! $user->can('operations.manage')) {
        return null;
    }

    return [
        'label' => 'Indicadores',
        'route' => 'indicadores.dashboard',
        'url' => route('indicadores.dashboard'),
        'active' => str_starts_with((string) $routeName, 'indicadores.'),
    ];
}
```

## Middleware

### `App\Http\Middleware\EnsureIndicadorAccess.php`

```php
class EnsureIndicadorAccess
{
    public function handle(Request $request, Closure $next, string $tab): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessIndicadorTab($tab), 403);
        return $next($request);
    }
}
```

Registrar en `bootstrap/app.php` con alias `indicador.tab`.

## User Model (metodos nuevos)

```php
public function indicadorBoardTabsFor(): Collection
{
    $tabs = collect([]);

    if ($this->can('operations.view') || $this->can('operations.manage')) {
        $tabs->push('dashboard');
    }

    if ($this->can('operations.capture')) {
        $tabs->push('captura');
    }

    if ($this->can('operations.manage')) {
        $tabs->push('periodos');
        $tabs->push('pesos');
        $tabs->push('documentos');
        $tabs->push('madre');
        $tabs->push('auditoria');
    }

    return $tabs->unique()->values();
}

public function canAccessIndicadorTab(string $tab): bool
{
    return match ($tab) {
        'capture' => $this->can('operations.view') || $this->can('operations.capture'),
        'groups' => $this->can('operations.view'),
        default => $this->can('operations.manage'),
    };
}
```

## Servicios (App\Services\Indicadores\)

| Servicio | Proposito |
|---|---|
| `OperationsDashboardService.php` | KPIs globales, ranking de grupos, tendencias |
| `GrupoDashboardService.php` | Dashboard por grupo, resumen, graficos |
| `IndicatorMotherService.php` | Consolidado por indicador a traves de grupos |
| `YearRangeService.php` | Rango de anios desde config + periodos |

## Vistas

### Estructura en `resources/views/areas/operaciones/`

```
areas/operaciones/
├── admin/
│   ├── index.blade.php       (dashboard global)
│   └── pdf.blade.php         (exportacion PDF)
├── indicadores/
│   ├── index.blade.php       (lista de indicadores)
│   └── show.blade.php        (captura con @livewire)
├── grupos/
│   └── show.blade.php        (dashboard por grupo)
├── periodos/
│   └── index.blade.php       (gestionar apertura/cierre)
├── documentos/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── madre/
│   ├── index.blade.php       (consolidado por indicador)
│   └── show.blade.php
├── auditoria/
│   └── index.blade.php
├── configuracion/
│   └── pesos.blade.php
├── exportaciones/
│   ├── zona.blade.php        (Excel grupo)
│   ├── zona-pdf.blade.php    (PDF grupo)
│   ├── madre.blade.php       (Excel MADRE)
│   └── madre-pdf.blade.php   (PDF MADRE)
└── partials/
    └── subnav.blade.php
```

Todas las vistas usan `<x-app-layout>` con `header` slot para subnav, siguiendo el patron de los modulos existentes.

## Seeders

| Seeder | Datos |
|---|---|
| `IndicadorSeeder.php` | 9 indicadores: FT-OP-01 a FT-OP-09 con metas, operadores, formulas |
| `PesoSeeder.php` | Pesos ponderados iniciales (suma 100%) |
| `DocumentoSistemaSeeder.php` | Documentos base: reglas del sistema, pesos dashboard |

Agregar llamadas en `DatabaseSeeder.php`.

## Lo que se descarta del proyecto original

| Archivo | Motivo |
|---|---|
| `Admin\UserController` + vistas | SJSEGURIDAD ya tiene admin/users |
| `Admin\ZoneController` + migraciones zones/user_zones | Reemplazado por grupo_operaciones en users |
| `Middleware\EnsureRole` | Reemplazado por Spatie permissions |
| `Middleware\EnsureZoneAccess` | Reemplazado por logica de grupo_operaciones |
| `routes/auth.php` | Ya existe en SJSEGURIDAD |
| `resources/views/auth/`, `layouts/`, `components/`, `profile/` | Ya existen en SJSEGURIDAD |
| Users migration + AdminUserSeeder | Ya existen en SJSEGURIDAD |

## Script de migracion de datos

Comando Artisan: `php artisan indicadores:importar`

- Lee BD de IndicadoresSJ (conexion separada en config/database.php)
- Migra: periodos, indicadores, capturas, mejoras, documentos, pesos
- Convierte zone_id -> grupo_operaciones segun relacion user_zones original
- Convierte role admin/usuario -> permisos Spatie correspondientes

## Orden de implementacion sugerido

1. `composer require` (Livewire, DOMPDF, Excel)
2. Migraciones
3. Modelos
4. Servicios
5. Livewire Components
6. Middleware + registro en bootstrap/app.php
7. Controlador
8. Rutas + web.php
9. Vistas
10. config/access.php + AppServiceProvider (navegacion)
11. User model (metodos)
12. Seeders
13. Script migracion datos
14. Pruebas
