# Plan de orquestacion — FEAT-044

> Generado por AgentSj tras Feature Brief. Plantilla: [`ORCHESTRATION_PLAN.md`](../templates/ORCHESTRATION_PLAN.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-044 |
| Modo | orquestado |
| Modulo principal | Gestion humana — Cartas Notificación |
| Brief | [`docs/briefs/FEAT-044.md`](FEAT-044.md) |
| Run log | [`docs/runs/FEAT-044-run-log.md`](../runs/FEAT-044-run-log.md) |
| shared-files | `config/access.php`, `employee_ficha.php`, rutas GH, AccessService, nav/User, WordDocumentTypeSeeder, LetterVariableBuilder |

## Secuencia

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacíos (respuestas 1–4) | — | OK |
| 2 | Arquitecto | Brief final | 1 | OK |
| 3 | AgentSj | Plan; pausa `OK implementa` | 2 | OK |
| 4 | Feature | **T1** Permisos + board + seed tipo Word + shell UI calidad | 3 | OK |
| 5 | Feature | **T2** Grilla + Excel memoria + generate docx/zip + audit + tests | 4 | OK |
| 6 | Revisor | Review completo | 5 | OK (Aprobado con observaciones) |
| 7 | Documentador | docs/modules + docs/user + ACCESS + plantillas-word | 6 | OK |
| 8 | AgentSj | Checklist cierre → Completadas | 7 | OK |

## Task Cards (resumen)

### T1 — Access / seed / shell
- shared-files: sí
- Done: board + `cartas_notificacion.edit`; seed `cartas_notificacion`; nav; shell UI calidad (referencia Vacaciones/Masivos); tests acceso

### T2 — UI + generate
- shared-files: no (salvo LetterVariableBuilder / hotfix)
- Done: grilla, Excel plantilla/carga memoria, lookup, generate 1→docx N→zip, plantilla exactamente 1, filtros, limpiar, audit, tests, Pint, smoke DevTools

## Paralelismo

Ninguno: T1 → T2.

## Puntos de pausa

- **Post-Brief:** `OK implementa`
- Post-Revisor: blockers
