# Task Card — FEAT-035 / T4

| Campo | Valor |
| --- | --- |
| Feature | FEAT-035 |
| Tarea | T4 — Examen ocupacional vertical slice |
| Modulo | seleccion |
| shared-files | Parcial — rutas GH |
| Depende de | T3 OK |
| Brief | [`FEAT-035.md`](FEAT-035.md) |
| Plan | [`FEAT-035-plan.md`](FEAT-035-plan.md) |

## Objetivo

Operativa completa de **Examen ocupacional**: migración/modelo, DataTables, CRUD, filtros, export, duplicado B (solo esta tabla), audit. Activar bloqueo DELETE catálogo para refs marital/eps/afp/solicitud.

## Incluye

- Tabla `seleccion_examenes_ocupacionales` + modelo/factory (columnas Brief).
- CRUD + Form Requests (todos required).
- DataTables server-side + filtros + export BaseExport.
- lookup-cedula + confirm_duplicate misma tabla.
- Audit seleccion_examen.
- Extender SeleccionCatalogService refs para eps/afp/marital/solicitud.
- Tests + pint + migrate.

## No incluye

- Dashboard charts (T5).
- Docs finales.

## Validacion

```bash
php artisan migrate
php artisan test --compact --filter=Seleccion
vendor/bin/pint --dirty --format agent
```
