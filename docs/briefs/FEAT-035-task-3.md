# Task Card — FEAT-035 / T3

| Campo | Valor |
| --- | --- |
| Feature | FEAT-035 |
| Tarea | T3 — Ingreso vertical slice (BD + CRUD + DataTables + export + duplicado) |
| Modulo | seleccion |
| shared-files | Parcial — ampliar `routes/areas/gestion_humana.php` |
| Depende de | T2 OK |
| Brief | [`FEAT-035.md`](FEAT-035.md) |
| Plan | [`FEAT-035-plan.md`](FEAT-035-plan.md) |

## Objetivo

Operativa completa de pestaña **Ingreso**: migración/modelo, listado server-side, CRUD, filtros, export Excel, lookup/confirmación de cédula duplicada (misma tabla).

## Incluye

- Migración `seleccion_ingresos` (columnas del Brief) + modelo `SeleccionIngreso` + factory.
- Sync snapshots `*_name` desde payroll / relaciones al guardar.
- Rutas: datatable, lookup-cedula, export, store, update, destroy.
- Form Requests: todos required; exists payroll codes; commercial_client; uniform activo; responsable selection_officer.
- `confirm_duplicate` obligatorio si hay matches (misma tabla, excluir self en update); sin flag → 422 + lista.
- DataTables server-side service + vista listado + modal/form create-edit (searchable-select).
- Filtros acordes (q, fechas, cliente, responsable, etc. — alinear a Brief).
- Export `BaseExport` + `<x-export-excel>`.
- Audit create/update/delete ingreso.
- Activar bloqueo DELETE de catálogo cuando `blood_type_code` / city / position referenciados (si T2 dejó TODO).
- Tests Feature: store, duplicate require confirm, update, destroy, datatable auth, export auth.

## No incluye

- Examen ocupacional (T4).
- Dashboard metrics/charts (T5).
- Docs finales.

## Criterios

1. CRUD Ingreso con todos los campos obligatorios.
2. Duplicado cédula: aviso + `confirm_duplicate=1`.
3. DataTables server-side + export.
4. Tests + pint verdes.
5. `php artisan migrate` sin fresh.

## Validacion

```bash
php artisan migrate
php artisan test --compact --filter=Seleccion
vendor/bin/pint --dirty --format agent
```
