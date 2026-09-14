# Task Card — FEAT-031 / T3

| Campo | Valor |
| --- | --- |
| Feature | FEAT-031 |
| Tarea | T3 — Seguimientos UI + autosave + tests cierre |
| Modulo | desvinculaciones |
| shared-files | No (salvo docs compartidos menores) |
| Depende de | T2 |
| Brief | [`FEAT-031.md`](FEAT-031.md) |

## Objetivo

Pestaña Seguimientos operativa: listado, filtros simples, 8 checks + fecha nomina con autosave, OK TODO calculado, carta generada solo lectura, campos consulta (causal/rehire/notas/cargo).

## Incluye

- Vista + datatable/listado + PATCH autosave.
- Filtros: q + incompletos / OK TODO / sin carta.
- Tests OK TODO + autosave + permiso edit 403.
- Ajustes menores INDEX/ACCESS si Feature los toca (Documentador completa docs modulo).

## No incluye

- Export Excel, colores, generacion cartas embebida.

## Criterios de aceptacion (T3)

Ver brief CA 12–13, 15–17.

## Validacion

- `php artisan test --compact` suite FEAT-031
- `vendor/bin/pint --dirty --format agent`
