# Plan de orquestacion — FEAT-033

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-033 |
| Modo | orquestado |
| Rama Git | (working tree actual) |
| Modulo principal | development-requests / tic |
| Run log | `docs/runs/FEAT-033-run-log.md` |
| shared-files | `config/access.php`, `routes/web.php`, NavigationResolver, SidebarVisibility, User, audit/notifications |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Skip — plan usuario aprobado | — | Skip |
| 2 | Arquitecto | Brief FEAT-033 | 1 | OK |
| 3 | Feature | T1 foundation: schema, models, access, nav, audit | 2 | OK |
| 4 | Feature | T2 intake: create, my requests, leader approval, attachments | 3 | OK |
| 5 | Feature | T3 TIC queue + estados + bloque TIC + UAT/cierre | 4 | OK |
| 6 | Feature | T3b chat + mail + IDOR tests | 5 | OK |
| 7 | Feature | T4 dashboard KPIs + export + tests acceso | 6 | OK |
| 8 | Revisor | Review diff | 7 | OK (aprobado con obs.) |
| 9 | Documentador | docs/modules + docs/user | 8 | OK (en T4) |
| 10 | AgentSj | Checklist cierre | 9 | OK |

## Paralelismo

Ninguno — shared-files y un solo modulo.

## Puntos de pausa usuario

- Post-Brief: **omitido** — usuario pidio implementar el plan adjunto.
- Post-Revisor: solo si hay blockers criticos.
