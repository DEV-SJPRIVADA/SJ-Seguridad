# Plan de orquestacion — FEAT-031

> Generado por AgentSj tras Feature Brief final. Brief: [`FEAT-031.md`](FEAT-031.md).  
> **Pausa:** confirmacion Usuario del brief antes de lanzar Feature T1.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-031 |
| Modo | orquestado |
| Rama Git | feat/FEAT-031-desvinculaciones |
| Modulo principal | desvinculaciones (GH) |
| Run log | `docs/runs/FEAT-031-run-log.md` |
| shared-files | `config/access.php`, `config/audit.php`, `routes/areas/gestion_humana.php`, NavigationResolver, SidebarVisibilityService, User.php, FichaEmpleadosController, TerminationLetterController |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Vacios cerrados (respuestas usuario) | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan + Task Cards T1–T3 | 2 | OK |
| 4 | Feature | T1: permisos, audit, migracion, modelo, AccessService, nav, shell tabs | 3 | OK |
| 5 | Feature | T2: Masivos (lookup, process, ZIP) + hooks Ficha terminate/letter | 4 | OK |
| 6 | Feature | T3: Seguimientos UI + datatable + autosave + tests cierre | 5 | OK |
| 7 | Revisor | Review del diff completo | 6 | OK (aprobado c/ obs.) |
| 8 | Documentador | `docs/modules/desvinculaciones.md` + `docs/user/desvinculaciones.md` + INDEX/ACCESS/ficha | 7 | OK |
| 9 | AgentSj | Checklist cierre | 8 | OK |

## Paralelismo

Ninguno. Shared-files y un solo modulo: **secuencial** T1 → T2 → T3.

## Puntos de pausa usuario

- Post-Analista: OK (respondido 2026-09-14)
- **Post-Brief: confirmacion de alcance** ← **aqui**
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `config/audit.php` | T1 | Solo T1 |
| Nav / User | T1 | Solo T1 |
| `FichaEmpleadosController` / `TerminationLetterController` | T2 | Solo T2 (tras T1) |
| `routes/areas/gestion_humana.php` | T1–T3 | Extender en orden; no paralelo |

## Task Cards

- [`FEAT-031-task-1.md`](FEAT-031-task-1.md) — shell + permisos + BD
- [`FEAT-031-task-2.md`](FEAT-031-task-2.md) — Masivos + hooks Ficha
- [`FEAT-031-task-3.md`](FEAT-031-task-3.md) — Seguimientos + tests
