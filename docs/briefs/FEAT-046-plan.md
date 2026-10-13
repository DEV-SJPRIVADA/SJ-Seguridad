# Plan de orquestacion — FEAT-046

> Generado por el AgentSj tras aprobar el Feature Brief. Guardar como `docs/briefs/FEAT-046-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-046 |
| Modo | orquestado |
| Rama Git | `manuel` (trabajo local) |
| Modulo principal | gestion_humana / dashboard de área |
| Run log | `docs/runs/FEAT-046-run-log.md` |
| Brief | `docs/briefs/FEAT-046.md` |
| shared-files | `routes/web.php`, `routes/areas/gestion_humana.php`, `vite.config.js` (**no** `config/access.php`) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios | — | Skip |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | T1: vertical slice completo (redirect + rutas + service + Blade + Alpine/Apex + tests + build + smoke) | 2 | OK (smoke parcial) |
| 4 | Feature | T2: solo si perf EOM o deuda UI bloqueante tras review | 3 | Skip (sin reason) |
| 5 | Revisor | Review del diff completo | 3 (o 4) | OK (Aprobado con observaciones) |
| 6 | Documentador | docs/modules + docs/user + INDEX/ACCESS | 5 | OK |
| 7 | AgentSj | Checklist cierre | 6 | OK |

## Paralelismo

No — `shared-files` y un solo módulo; T1 → Revisor → Documentador en serie. T2 solo si hay reason claro.

## Puntos de pausa usuario

- Post-Brief: confirmado 2026-10-13 («si» + 7 decisiones)
- Post-Revisor: blockers críticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `routes/web.php` | T1 | Solo T1 (redirect GH dashboard) |
| `routes/areas/gestion_humana.php` | T1 | Solo T1 |
| `vite.config.js` | T1 | Solo T1 (entry charts) |
| `config/access.php` | — | **No tocar** |
