# Run log — FEAT-029

> Registro persistente del flujo multi-agente. Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Titulo | Tablero plantillas Word (tipo documento) + modal generar cartas (seleccion 1/N → docx/zip) |
| Modo | orquestado |
| Modulo | gestion_humana / ficha-empleados (cartas / plantillas) |
| Chat AgentSj | 2026-08-21 @agent-sj |
| Brief | `docs/briefs/FEAT-029.md` |
| Plan | `docs/briefs/FEAT-029-plan.md` |
| Inicio | 2026-08-21 |
| Cierre | 2026-08-21 |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-08-21 | `@agent-sj Tablero plantillas Word…` | AgentSj | Creo FEAT-029 en TASKS.md y run log | `docs/TASKS.md`, `docs/runs/FEAT-029-run-log.md` | OK |
| 2 | 2026-08-21 | Task automatico | Analista | 9 preguntas abiertas; pausa para usuario | `docs/briefs/FEAT-029-analyst.md` | Pausa |
| 3 | 2026-08-21 | Respuestas usuario 1–9 | AgentSj | Registro respuestas; reanuda → Arquitecto | `docs/briefs/FEAT-029-analyst.md` | OK |
| 4 | 2026-08-21 | Task automatico | Arquitecto | Brief final permisos/rutas/BD | `docs/briefs/FEAT-029.md` | OK |
| 5 | 2026-08-21 | AgentSj | AgentSj | Plan T1–T5 + Task Card T1 | `docs/briefs/FEAT-029-plan.md`, `docs/briefs/FEAT-029-task-1.md` | OK |
| 6 | 2026-08-21 | Task automatico | Feature | T1 permisos+sidebar+stub (7 tests) | shared-files plantillas_word, PlantillasWordBoardAccessTest | OK |
| 7 | 2026-08-21 | Task automatico | Feature | T2 migraciones+modelos+seed (3 tests) | word_document_types, alter templates | OK |
| 8 | 2026-08-21 | Task automatico | Feature | T3 CRUD tablero (16 tests acumulados) | PlantillasWordController, vistas, CrudTest | OK |
| 9 | 2026-08-21 | Task automatico | Feature | T4 generador IDs + modal + retiro catalog | TerminationLetterPack*, routes, UI ficha | OK |
| 10 | 2026-08-21 | Task automatico | Feature | T5 gaps tests + pint (29 tests verdes) | TerminationLetterPackTest, PlantillasWordCrudTest, TASKS | OK |
| 11 | 2026-08-21 | Task automatico | Revisor | Aprobado con observaciones (0 blockers) | `docs/reviews/FEAT-029.md` | OK |
| 12 | 2026-08-21 | Task automatico | Documentador | Docs tecnica+usuario plantillas-word y ficha | `docs/modules/plantillas-word.md`, `docs/user/plantillas-word.md`, ficha, INDEX, ACCESS_CONTROL | OK |
| 13 | 2026-08-21 | Checklist cierre | AgentSj | Checklist OK; movio a Completadas | `docs/TASKS.md`, run log | OK |

## Checklist cierre AgentSj

- [x] Feature Brief cumplido
- [x] `config/access.php` actualizado
- [x] Rutas en `routes/areas/gestion_humana.php`
- [x] `docs/modules/plantillas-word.md` + ficha-empleados
- [x] `docs/user/plantillas-word.md` + ficha-empleados (6 secciones)
- [x] `docs/INDEX.md` + ACCESS_CONTROL + README
- [x] Revisor sin bloqueantes (observaciones: codigo muerto FEAT-027, code editable)
- [x] Run log con fila de cierre
- [x] Tests Feature relevantes 29 passed (T5)
- [x] Sin solapamiento shared-files pendiente

## Observaciones post-cierre (no bloqueantes)

1. Borrar partial legado `termination-letter-templates-admin` y `UploadTerminationLetterTemplateRequest` en fix pequeno.
2. Evitar editar el `code` del tipo `desvinculacion` (rompe Generar).
3. Operadores deben **re-subir** plantillas Word en el tablero nuevo.


## Decisiones de producto ya confirmadas por el usuario (chat previo)

1. **Sidebar propio** (tablero aparte, no pestana dentro de Ficha).
2. Lista unica **sin amarrar a causal**; columna **tipo de documento** (`desvinculacion`, luego `contratacion`, etc.). Al generar en desvinculacion → solo plantillas tipo desvinculacion.
3. Filtro por **tipo**, no por causal de desvinculacion — confirmado.
4. Admin: **agregar** nuevas + **reemplazar** `.docx` del mismo registro + **eliminar**.
5. Sin “Regenerar” aparte: **Generar** abre modal → descarga; **Descargar** ultimo paquete si existe.
6. Evoluciona FEAT-027 (quitar plantillas del catalogo Causal).

## Notas

- Shared-files previstos: `config/access.php`, rutas GH, navegacion sidebar.
- No `migrate:fresh` / wipe sin OK del usuario.
