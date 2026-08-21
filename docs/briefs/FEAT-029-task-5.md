# Task Card — FEAT-029 / Tarea 5

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Tarea # | 5 |
| Brief | `docs/briefs/FEAT-029.md` |

## Objetivo

Cerrar gaps de tests y calidad: correr suite relacionada, corregir fallos, asegurar CA del brief cubiertos (permisos, CRUD, generate 1/N, todas causales, catalog 404, seed tipo). Pint final. No docs de modulo (Documentador).

## Archivos permitidos

- `tests/Feature/GestionHumana/**` relacionados
- Fixes minimos en app/ si un test revela bug de T1–T4
- `docs/TASKS.md`

## Criterios de done

1. `php artisan test --compact` con filtros PlantillasWord + TerminationLetter + WordDocumentType (+ board) en verde
2. Catalog routes viejas 404 si hay test
3. Pint dirty OK
4. Lista de cobertura vs CA del brief

## Al cerrar

Resultado tests, fixes, handoff Revisor.
