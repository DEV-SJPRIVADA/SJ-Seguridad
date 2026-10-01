# Plan de orquestacion — FEAT-041

> Generado por AgentSj tras brief Arquitecto. Usuario dijo `implementa` — sin pausa post-brief.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-041 |
| Modo | orquestado |
| Rama Git | (rama actual Manuel-E) |
| Modulo principal | gestion_humana / `formacion` |
| Run log | `docs/runs/FEAT-041-run-log.md` |
| shared-files | `config/access.php`, `config/audit.php`, nav (`NavigationResolver`, `SidebarVisibilityService`, `User`), `routes/areas/gestion_humana.php` |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Skip — decisiones cerradas en Ask | — | Skip |
| 2 | Arquitecto | Feature Brief final | — | OK |
| 3 | Feature | T1: shell + permisos + migración + modelo + nav + AccessService + audit + tests acceso | 2 | OK |
| 4 | Feature | T2: Formaciones listado/filtros/DT/export | 3 | OK |
| 5 | Feature | T3: plantilla + import replace-all + parse fechas | 4 | OK |
| 6 | Feature | T4: Dashboard KPIs + metrics | 5 | OK |
| 7 | Revisor | Review diff completo | 6 | pendiente |
| 8 | Documentador | docs/modules + docs/user + ACCESS/INDEX | 7 | pendiente |
| 9 | AgentSj | Checklist cierre | 8 | pendiente |

## Paralelismo

No — shared-files en T1; T2–T4 secuenciales sobre el mismo módulo.

## Puntos de pausa usuario

- Post-Analista: Skip
- Post-Brief: Skip (usuario `implementa`)
- Post-Revisor: si hay blockers

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `routes/areas/gestion_humana.php` | T1–T4 | Secuencial |
