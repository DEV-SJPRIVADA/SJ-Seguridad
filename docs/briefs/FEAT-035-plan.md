# Plan de orquestacion — FEAT-035

> Generado por el AgentSj tras Brief Arquitecto. Guardar como `docs/briefs/FEAT-035-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-035 |
| Modo | orquestado |
| Rama Git | feat/FEAT-035-seleccion-gh (opcional) |
| Modulo principal | seleccion (Gestion humana) |
| Brief | `docs/briefs/FEAT-035.md` |
| Run log | `docs/runs/FEAT-035-run-log.md` |
| shared-files | `config/access.php`, `routes/areas/gestion_humana.php`, nav/Sidebar, `config/employee_ficha.php`, `config/audit.php` |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** Accesos + nav + shell pestañas (permisos, `SeleccionAccessService`, rutas index/dashboard vacias, subnav) | 2 | Pendiente |
| 4 | Feature | **T2** Catalogos + seed (`blood_type`, `marital_status`, `seleccion_solicitud_status` + UI Catalogos whitelist 7 tipos) | 3 | Pendiente |
| 5 | Feature | **T3** Ingreso vertical slice (migracion/modelo, CRUD, datatable, filtros, export, duplicado confirm) | 4 | Pendiente |
| 6 | Feature | **T4** Examen ocupacional vertical slice (idem T3) | 5 | Pendiente |
| 7 | Feature | **T5** Dashboard KPIs + graficos ApexCharts + filtros | 6 | Pendiente |
| 8 | Revisor | Review del diff completo | 7 | Pendiente |
| 9 | Documentador | `docs/modules/seleccion.md` + `docs/user/seleccion.md` + INDEX | 8 | Pendiente |
| 10 | AgentSj | Checklist cierre | 9 | Pendiente |

## Paralelismo

No. Shared-files (`access.php`, rutas GH) y dependencia T2→T3/T4 (catalogos). Ejecutar **secuencial**.

## Puntos de pausa usuario

- Post-Analista: OK (respondido)
- **Post-Brief: confirmacion de alcance** ← **ahora**
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Un solo Feature; no paralelo con FEAT-034 si toca access |
| `routes/areas/gestion_humana.php` | T1–T5 | Serializar en T1 base + ampliaciones |
| `config/employee_ficha.php` | T2 | Solo T2 |

## Notas AgentSj

- Tras OK del usuario al Brief/plan → lanzar Feature **T1**.
- Prohibido `migrate:fresh` / wipe.
- Un Feature = vertical slice; Task Cards en `docs/briefs/` o seccion del plan al lanzar.
