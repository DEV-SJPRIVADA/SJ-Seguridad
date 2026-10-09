# Plan de orquestacion — FEAT-045

> Generado por el AgentSj tras aprobar el Feature Brief. Guardar como `docs/briefs/FEAT-045-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-045 |
| Modo | orquestado |
| Rama Git | `manuel` (trabajo local) |
| Modulo principal | gestion_humana / mt_st_04 |
| Run log | `docs/runs/FEAT-045-run-log.md` |
| shared-files | `config/access.php`, rutas GH, nav/User, `config/audit.php`, schedule T4 |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | T1: shell + permisos + migración + nav + AccessService | 2 | OK |
| 4 | Feature | T2: Matriz CRUD + DT server-side + lookup + calculadora | 3 | OK |
| 5 | Feature | T3: plantilla + import upsert + export | 4 | OK |
| 6 | Feature | T4: Dashboard KPIs/charts + comando/schedule estados | 5 | OK |
| 7 | Revisor | Review del diff completo | 6 | OK |
| 8 | Documentador | docs/modules + docs/user + INDEX/ACCESS/PROCEDURES | 7 | OK |
| 9 | AgentSj | Checklist cierre | 8 | OK |

## Paralelismo

No — `shared-files` y un solo módulo; T1→T4 en serie.

## Puntos de pausa usuario

- Post-Brief: confirmado 2026-10-09 («implementa»)
- Post-Revisor: blockers críticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `routes/areas/gestion_humana.php` | T1–T4 | Secuencial |
| Schedule / `bootstrap/app.php` | T4 | Solo T4 |
