# Task Card — FEAT-047 / T3

> Emitida por el AgentSj al Agente Feature. Ejecutada 2026-10-19.

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-047 |
| Tarea # | T3 — Import / plantilla VINE01 (solo PROVEEDOR=SI) |
| Modulo / area | purchase-requests / Compras |
| Brief | `docs/briefs/FEAT-047.md` (Fase B, cierre) |
| Plan | `docs/briefs/FEAT-047-plan.md` |

## Objetivo de esta tarea

Cerrar la Fase B con la carga masiva de proveedores desde la plantilla **VINE01**: descargar plantilla, importar Excel/CSV cargando **solo** filas con `PROVEEDOR = SI`, upsert por `identificacion`, `DIAS_PAGO` opcional, reporte de creados/actualizados/omitidos/errores **en modal** (con descarga del reporte `.xlsx` de fallos) y comando Artisan con `--dry-run` / `--limit`.

## Archivos tocados (scope)

- `config/purchase-requests.php` (columnas/topes de import proveedores)
- `app/Exports/PurchaseProviderImportTemplateExport.php`
- `app/Services/PurchaseRequests/Providers/PurchaseProviderImportService.php`
- `app/Http/Requests/PurchaseRequests/ImportPurchaseProvidersRequest.php`
- `app/Console/Commands/ImportPurchaseProvidersCommand.php`
- `app/Http/Controllers/PurchaseRequests/PurchaseProviderController.php` (`template`, `import`, `downloadImportReport`)
- `routes/modules/purchase-requests.php` (`providers.template`, `providers.import`, `providers.import-report`)
- `resources/views/components/import-result-modal.blade.php` (compartido; icon-only + `download-params`/`skipped_label`)
- Vistas `providers/index.blade.php` + `providers/partials/import-modal.blade.php`
- Tests `tests/Feature/PurchaseRequests/PurchaseProviderImportTest.php`
- `docs/modules/purchase-requests.md`, `docs/user/purchase-requests.md`, run log

## Reglas de negocio

1. Solo `PROVEEDOR = SI` (normaliza `SI/SÍ/1/X/TRUE`).
2. La tabla solo almacena proveedores (sin columnas de clasificación); la columna `CLIENTE` del archivo se ignora.
3. Upsert por `identificacion`; `NOMBRE` en MAYÚSCULAS; `TELEFONO` solo dígitos; `ESTADO` `ACTIVO`/`INACTIVO` (default `activo`).
4. `DIAS_PAGO` entero 0–3650 o vacío.
5. Encabezados obligatorios en fila 1: `IDENTIFICA`, `NOMBRE`, `PROVEEDOR`.
6. Los campos FO-AD-07 extra (contacto, dirección, ciudad, forma de pago) no vienen en la plantilla y se editan en pantalla.

## Verificacion

- `php artisan test --compact tests/Feature/PurchaseRequests` (import + catalog + access en verde).
- `vendor/bin/pint --dirty --format agent`.
- Smoke navegador (Chrome DevTools MCP) pendiente de sesión autenticada.
