# Plan de orquestacion — FEAT-020

> Generado por AgentSj tras aprobacion del usuario (2026-07-30). Brief: [`FEAT-020.md`](FEAT-020.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-020 |
| Modo | orquestado |
| Modulo principal | requisitions + ficha_empleados (GH) |
| Run log | `docs/runs/FEAT-020-run-log.md` |
| shared-files | `config/access.php`, `NavigationResolver`, `User.php`, `PersonalRequisition`, `UpdatePersonalRequisitionRequest`, `RequisitionController`, `edit.blade.php`, `PersonalRequisitionChangeLogger`, `routes/web.php`, docs |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas y decisiones usuario | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Usuario | Aprobacion brief | 2 | OK |
| 4 | Feature | **T1** Esquema, modelos, permisos base | 3 | En curso |
| 5 | Feature | **T2** Requisicion: campos, validacion, duplicado, sync | 4 | Pendiente |
| 6 | Feature | **T3** Tablero Ficha empleados + navegacion + promote | 5 | Pendiente |
| 7 | Feature | **T4** Export Excel + tests regresion | 6 | Pendiente |
| 8 | Revisor | Review diff completo | 7 | Pendiente |
| 9 | Documentador | docs/modules + docs/user | 8 | Pendiente |
| 10 | AgentSj | Checklist cierre | 9 | Pendiente |

## Paralelismo

Ninguno — vertical slice secuencial T1→T4 por dependencias en shared-files.

## Puntos de pausa usuario

- Post-Analista: OK
- Post-Brief: OK (confirmo 2026-07-30)

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1, T3 | T1 primero; T3 solo navegacion |
| `RequisitionController` | T2 | Un solo agente T2 |
