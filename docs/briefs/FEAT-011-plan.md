# Plan de orquestacion — FEAT-011

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-011 |
| Modo | orquestado |
| Rama Git | Manuel-E |
| Modulo principal | requisitions (GH) |
| Run log | `docs/runs/FEAT-011-run-log.md` |
| shared-files | `config/access.php`, `routes/modules/requisitions.php`, `RequisitionController`, `parameters.blade.php` |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas + respuestas usuario | — | OK |
| 2 | Arquitecto | `docs/briefs/FEAT-011.md` | 1 | OK |
| 3 | Feature | Vertical slice completo (migracion, servicio, rutas, UI, validacion, export, tests) | 2 | En curso |
| 4 | Revisor | `docs/reviews/FEAT-011.md` | 3 | Pendiente |
| 5 | Documentador | `docs/modules/requisitions.md` + `docs/user/` | 4 | Pendiente |
| 6 | AgentSj | Cierre TASKS | 5 | Pendiente |

## Task Card — Tarea 3 (Feature)

- **Brief:** `docs/briefs/FEAT-011.md`
- **shared-files:** true
- **Criterio:** tests listados en brief pasan; Pint en PHP tocado

## Puntos de pausa usuario

- Q7 legacy: propuesta documentada; confirmacion implicita si no objeta

## Conflictos

Ninguno (un solo agente Feature en modulo requisitions).
