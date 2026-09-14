# Task Card — FEAT-031 / T2

| Campo | Valor |
| --- | --- |
| Feature | FEAT-031 |
| Tarea | T2 — Masivos + hooks Ficha |
| Modulo | desvinculaciones |
| shared-files | **Parcial** — `FichaEmpleadosController`, `TerminationLetterController` (+ rutas GH si faltan) |
| Depende de | T1 |
| Brief | [`FEAT-031.md`](FEAT-031.md) |

## Objetivo

Implementar grilla Masivos (lookup, process, ZIP, reporte fallos, limpiar), servicio lote, creacion followup, y hooks: terminate individual + generate letter actualizan/crean followup.

## Incluye

- Endpoints lookup / templates / signatories / process (+ descarga ZIP).
- `BulkTerminationService` (o equivalente): close + sync + followup + letter; TX por empleado; continuar en fallos.
- FECHA DESVINCULACION → `last_work_day` + `termination_date`; causal/rehire/notas opcionales.
- UI grilla + boton Desvincular + reporte.
- Hook `FichaEmpleadosController::terminate` → create followup.
- Hook `TerminationLetterController::generate` → `letter_generated=true`.
- Audit lote.
- Tests: lookup, lote parcial, carta fail, ZIP, terminate → followup, letter → flag.

## No incluye

- UI completa Seguimientos / autosave (T3). Export Excel. Multi-plantilla.

## Criterios de aceptacion (T2)

Ver brief CA 3–11, 14–16 aplicables a Masivos/hooks.

## Validacion

- `php artisan test --compact` (filtros desvinculaciones / termination followup / bulk)
- `vendor/bin/pint --dirty --format agent`
- Prohibido `migrate:fresh`
