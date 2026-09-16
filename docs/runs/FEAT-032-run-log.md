# Run log — FEAT-032

> Registro persistente del flujo multi-agente.  
> Plantilla: [`RUN_LOG.md`](../templates/RUN_LOG.md) — Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-032 |
| Titulo | Tablero Cursos (Gestion Humana) — listado, vigencia, plantilla e import masivo |
| Modo | orquestado |
| Modulo | cursos (GH) |
| Chat AgentSj | 2026-09-15 cursos GH |
| Brief | `docs/briefs/FEAT-032.md` |
| Plan | `docs/briefs/FEAT-032-plan.md` |
| Inicio | 2026-09-15 |
| Cierre | |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-09-15 | `@agent-sj` + tablero Cursos GH (campos + vigencia + plantilla/carga) | AgentSj | Creo FEAT-032 en TASKS.md y run log; lanzo Analista; pausa con preguntas al usuario | `docs/TASKS.md`, `docs/runs/FEAT-032-run-log.md` | Pausa |
| 2 | 2026-09-15 | Task automatico | Analista | Documento vacios y preguntas criticas; pide pausa | `docs/briefs/FEAT-032-analyst.md` | Pausa |
| 3 | 2026-09-15 | Post-Analista | AgentSj | Registro entrega Analista; flujo sigue en pausa esperando respuestas usuario | `docs/runs/FEAT-032-run-log.md` | Pausa |
| 4 | 2026-09-15 | Respuestas usuario 1–7 | AgentSj | Registro respuestas en analyst; lanzo Arquitecto | `docs/briefs/FEAT-032-analyst.md`, `docs/TASKS.md` | OK |
| 5 | 2026-09-15 | Task automatico | Arquitecto | Feature Brief final | `docs/briefs/FEAT-032.md` | OK |
| 6 | 2026-09-15 | Post-brief | AgentSj | Plan + Task Cards T1–T3; pausa aprobacion usuario | `docs/briefs/FEAT-032-plan.md`, `FEAT-032-task-{1,2,3}.md` | Pausa |
| 7 | 2026-09-15 | Usuario: documento curso + bridge Ficha | AgentSj | Amplio brief/plan; agrego T4 Ficha; re-pausa «apruebo» | `FEAT-032.md`, `FEAT-032-plan.md`, `FEAT-032-task-{1,2,3,4}.md` | Pausa |
| 8 | 2026-09-15 | Usuario: «aprobado» | AgentSj | Aprobacion plan; lanzo Feature T1 | — | OK |
| 9 | 2026-09-15 | Task T1 (parcial / demora) | Feature | Shell permisos BD catálogo implementado en rama | controllers, models, migrations, tests, nav | OK |
| 10 | 2026-09-15 | Usuario: continuar T1 | AgentSj | Verifico T1: migrate OK, sync-permissions, 14 tests Cursos OK, pint OK | `tests/.../CursosBoardAccessTest.php` | OK |
| 11 | 2026-09-15 | Usuario: `@agent-sj` Continua T2 | AgentSj | Lanzo Feature T2 (registros + documento + export) | Task Card T2 | En curso |
| 12 | 2026-09-15 | Usuario: demora T2 | AgentSj | Diagnostico: subagente colgado en exploracion; 0% codigo T2 | transcript T2 | Blocker |
| 13 | 2026-09-15 | Usuario: si (retomar directo) | AgentSj | Interrumpo subagente; implemento T2 en chat maestro | controller, services, vista, tests | OK |
| 14 | 2026-09-15 | Validacion T2 | AgentSj | 20 tests Cursos OK; pint OK | `CursosBoardAccessTest` + `CursosRegistrosTest` | OK |
| 15 | 2026-09-15 | Usuario: si (T3) | AgentSj | Implemento T3 directo (plantilla + import upsert) | export, import service, UI, tests | OK |
| 16 | 2026-09-15 | Validacion T3 | AgentSj | Tests Cursos OK (incl. import); pint OK | `CursosImportTest` | OK |
| 17 | 2026-09-15 | UX registros | AgentSj | Modal + / masivos upload como Ficha; sin form inline | `registros.blade.php`, partials nuevo/masivos | OK |
| 18 | 2026-09-15 | Usuario: si (T4) | AgentSj | Bridge Ficha: botón cursos + modal + download anti-IDOR | Ficha controller, modal, routes, tests | OK |
| 19 | 2026-09-15 | Validacion T4 | AgentSj | 26 tests Cursos OK (incl. bridge); pint OK | `CursosFichaBridgeTest` | OK |
| 20 | 2026-09-16 | Usuario: renovacion import | AgentSj | Columna `numero_curso_anterior` + docs modulo/usuario | import service, plantilla, tests, `docs/modules/cursos.md`, `docs/user/cursos.md` | OK |

## Notas

- Shared-files: `config/access.php`, rutas GH, nav, audit, User, Ficha (T4), posible `app.css`.
- Regla vigencia: ACTUALIZAR si `FECHA_EXPEDICION < (HOY + 30) - 365` (= hoy−335); si no → VIGENTE.
- Permisos: `view.board.gestion_humana.cursos`, `cursos.view`, `cursos.edit`.
- Plan aprobado 2026-09-15 («aprobado»).
- T1–T4 cerrados 2026-09-15. Siguiente: Revisor → Documentador → cierre.
- Nota: en la rama hay cambios ajenos a Cursos en **Desvinculaciones** (seguimientos); no forman parte de FEAT-032 — no tocarlos.
- CSS: si la UI no refleja estilos, correr `npm run build` / `npm run dev`.
- Bridge Ficha: list/download con `ficha_empleados.view`; no requiere `cursos.view`.
