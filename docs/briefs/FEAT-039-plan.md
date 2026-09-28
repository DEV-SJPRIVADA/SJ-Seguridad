# Plan de orquestacion — FEAT-039

> Generado por el AgentSj tras aprobar el Feature Brief. Guardar como `docs/briefs/FEAT-039-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-039 |
| Modo | orquestado |
| Rama Git | — (working tree local) |
| Modulo principal | acreditaciones (GH) |
| Run log | `docs/runs/FEAT-039-run-log.md` |
| shared-files | `routes/areas/gestion_humana.php` (parcial; **no** `access.php`) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Brief borrador; 0 preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** Settings + seed + runs + Catálogo params | 2 | OK |
| 4 | Feature | **T2** Resolve / preview / candidatos | 3 | OK |
| 5 | Feature | **T3** Export `.xls` + seq/runs + modal novedades | 4 | OK |
| 6 | Feature | **T4** Dashboard KPIs + últimas corridas + tests polish | 5 | OK |
| 7 | Revisor | Review del diff completo | 6 | OK |
| 8 | Documentador | docs/modules + docs/user acreditaciones | 7 | OK |
| 9 | AgentSj | Checklist cierre | 8 | OK |

## Paralelismo

No — un solo módulo y rutas area compartidas; T1→T4 en serie.

## Puntos de pausa usuario

- Post-Analista: OK (sin preguntas)
- Post-Brief: decisiones ya confirmadas (params seed + yyyymmdd + Valida solo UI)
- Post-Revisor: blockers críticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | T1–T4 | Un Feature a la vez; serializar |
| `AcreditacionesController` / vistas catalogo | T1 + T2 | Serializar slices |
| `config/access.php` | — | **No tocar** |

## Task Cards

Ver `docs/briefs/FEAT-039.md` sección Task Cards (T1–T4).
