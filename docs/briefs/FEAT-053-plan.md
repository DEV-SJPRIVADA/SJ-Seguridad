# Plan — FEAT-053 (MT-GI-11)

Triaje **C — Full**. Tres cortes verticales de negocio.

## Slices

| # | Slice | Entregables | Shared-files |
| --- | --- | --- | --- |
| T1 | Base: permisos, tablero, migraciones, modelos, acceso | `config/access.php`, `RoleAndPermissionSeeder`, `PermissionCatalog`, `NavigationResolver`, `SidebarVisibilityService`, `User::defaultMtGi11BoardUrl`, 6 migraciones, 6 modelos, `MtGi11AccessService`, rutas base | Si |
| T2 | Matriz: listado + CRUD + adjuntos + nueva version | controlador, form requests, `MtGi11DatatableService`, `MtGi11Service`, `MtGi11AdjuntoService`, vistas `matriz.blade.php`, modales, `MtGi11AuditService` | Si (`routes/areas/calidad.php`) |
| T3 | Dashboard + Catalgos + Masivos + Export | `MtGi11DashboardService`, `MtGi11CatalogService`, `MtGi11ImportService`, `MtGi11Export`, plantillas, vistas, chart JS, CSS | Si |

## Orden de ejecucion

1. T1 → migraciones + modelos + permisos + navegacion (base para todo lo demas).
2. T2 → listado operativo con CRUD, adjuntos y «Nueva version».
3. T3 → dashboard con KPIs + 2 charts, catalogos administrables, plantilla/import/export.

## Verificacion por slice

- T1: `php artisan migrate` + `php artisan test --compact --filter=MtGi11BoardAccess` + `--filter=MtGi11Crud`.
- T2: `--filter=MtGi11Crud` + `--filter=MtGi11Visibility` + `--filter=MtGi11Adjuntos`.
- T3: `--filter=MtGi11Datatable` + `--filter=MtGi11Import` + `--filter=MtGi11Dashboard`.
- Cierre: `vendor/bin/pint --dirty --format agent` + `npm run build`.

## Riesgos

| Riesgo | Mitigacion |
| --- | --- |
| Dos tableros «Matrices» en Calidad | Keys distintas (`matrices` vs `mt_gi_11`) y labels distintos («Matrices» vs «MT-GI-11»). |
| `PermissionCatalog` excluye `view.board.*.matrices` | La clave nueva es `mt_gi_11`, no `matrices`; no colisiona con la exclusion. |
| Consecutivo repetido rompe busquedas | Toda operacion por `id`; filtro por consecutivo con LIKE. |
| Import con duplicados | Sin regla de unicidad; solo errores reales por fila. |
| Datos existentes | Solo migraciones aditivas (`create`), sin tocar tablas ni datos previos. |

## Nota de entorno

Las migraciones nuevas se ejecutan con `php artisan migrate`; **nunca** `migrate:fresh` / `db:wipe` (regla de proteccion de datos).
