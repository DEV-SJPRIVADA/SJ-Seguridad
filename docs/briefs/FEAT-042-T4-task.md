# Task Card — FEAT-042 / T4

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Tarea # | T4 |
| Brief | `docs/briefs/FEAT-042.md` (masivo opción B) |
| shared-files | **no** |

## Objetivo

Import masivo **replace-por-periodo**: usuario elige año+mes → confirma conteo a borrar → DELETE solo ese periodo → INSERT todas las filas válidas (incl. spillover fuera de periodo con anio/mes reales). Plantilla Excel. Abortar sin borrar si hay errores de validación.

## Scope lock

- `ClienteInternoImportService`, ImportTemplateExport, Import Form Request
- Rutas import / import-template / period-count
- Modal UI + botón masivo en solicitudes
- Tests import (B, no-wipe-on-error, confirmación)
- `docs/TASKS.md`

## Criterios de done

1. Replace solo `WHERE anio=? AND mes=?`.
2. Filas fuera de periodo aceptadas (B).
3. Error en obligatorias → no DELETE.
4. tipo_solicitud resuelto por name/code catálogo; estado vacío OK.
5. Audit `import_replace_period` con metadata.
6. Tests + pint.

## No hacer

Dashboard charts, access.php.
