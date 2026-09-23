# Run log — FEAT-036

> Registro persistente del flujo multi-agente. Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-036 |
| Titulo | Tablero Acreditaciones (GH): shell + Acreditados (fase 1) |
| Modo | orquestado |
| Modulo | acreditaciones (gestion_humana) |
| Chat AgentSj | 2026-09-23 acreditaciones GH |
| Brief | `docs/briefs/FEAT-036.md` |
| Plan | `docs/briefs/FEAT-036-plan.md` |
| Inicio | 2026-09-23 |
| Cierre | 2026-09-23 |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-09-23 | `@agent-sj` Tablero Acreditaciones GH fase 1 | AgentSj | Creo FEAT-036 en TASKS.md y run log | `docs/TASKS.md`, `docs/runs/FEAT-036-run-log.md` | OK |
| 2 | 2026-09-23 | Task automatico | Analista | Brief analista; 5 preguntas abiertas → pausa usuario | `docs/briefs/FEAT-036-analyst.md` | Pausa |
| 3 | 2026-09-23 | Respuestas usuario 1–5 | AgentSj | Cierra preguntas; actualiza analyst.md; lanza Arquitecto | `docs/briefs/FEAT-036-analyst.md` | OK |
| 4 | 2026-09-23 | Task automatico | Arquitecto | Feature Brief final FEAT-036 | `docs/briefs/FEAT-036.md` | OK |
| 5 | 2026-09-23 | Post-brief | AgentSj | Plan orquestacion T1–T4; lanza Feature T1 | `docs/briefs/FEAT-036-plan.md` | OK |
| 6 | 2026-09-23 | Task Card T1 | Feature | Accesos / nav / shell Acreditaciones; 15 tests OK | access, audit, AccessService, trait, rutas, vistas, nav, tests | OK |
| 7 | 2026-09-23 | Task Card T2 | Feature | Catalogo cargos + seed 17 + CRUD; tests OK | migracion, modelo, seeder, Form Requests, UI, audit, tests | OK |
| 8 | 2026-09-23 | Task Card T3 | Feature | Acreditados core: migracion, calculator, DT, CRUD, export, audit; 37 tests OK | modelo, services, requests, UI, tests | OK |
| 9 | 2026-09-23 | Task Card T4 | Feature | Import upsert + sync diario; tests Acreditacion* | ImportService, plantilla, comando, schedule, UI, tests | OK |
| 10 | 2026-09-23 | Task Revisor | Revisor | Aprobado con observaciones (45 tests); docs pendientes | `docs/reviews/FEAT-036.md` | OK |
| 11 | 2026-09-23 | Task Documentador | Documentador | Docs tecnica + usuario + INDEX/ACCESS/ARCHITECTURE/TASKS | `docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md`, indices | OK |
| 12 | 2026-09-23 | Checklist cierre | AgentSj | Checklist AGENT_WORKFLOW OK; mueve a Completadas | `docs/TASKS.md`, run-log | OK |

## Notas de negocio confirmadas (usuario)

- **VIGEN.ACR** = fecha de vencimiento de la acreditacion.
- **CARGO** en Acreditados viene del Excel de carga (no se deriva automaticamente del catalogo en fase 1).
- Prioridad de estados (mayor → menor):
  1. Si hay **FECHA SOLICITUD** → **EN PROCESO**
  2. Si **VIGEN.ACR ≤ hoy** → **DESACREDITADO**
  3. Si **VIGEN.ACR ≤ hoy+21 dias** → **POR VENCER**
  4. Else → **ACREDITADO**
- Pestañas del tablero: Dashboard, Acreditados, Reporte Diario, Validaciones, Export Apo, Catalogo.
- Fase 1: shell del tablero (pestañas) + **Acreditados** (+ catalogo de cargos de la tabla adjunta).
- Relacion con Ficha empleados por **cedula**; un empleado puede tener varias acreditaciones de distinto tipo.
- Campos Acreditados: CEDULA, NOMBRE COMPLETO, CARGO, VIGEN.ACR, ESTADO, OBSERVACIONES, FECHA SOLICITUD.
- Catalogo (imagen): CARGO MANAGER | CARGO APO | CARGO INFORME | CARGO ACREDITACION (codigos 1,2,4,5,6).
- **Tipo** = **CARGO APO** del catalogo; unicidad **cedula + tipo**; upsert al re-importar.
- Cedula **obligatoria** en Ficha; **NOMBRE COMPLETO** siempre desde Ficha.
- Acreditados fase 1: CRUD manual + plantilla/re-import + export + filtros; ESTADO solo calculado.
- Catalogo fase 1: **CRUD** (opcion B).
- Permisos + audit confirmados (patron Cursos/Seleccion; sin paquete default a roles).

## Tabla para el chat (copiar al final de cada respuesta de AgentSj)

| # | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- |
| 1 | AgentSj | Creo FEAT-036 + run log | TASKS, run-log | OK |
| 2 | Analista | Brief + 5 preguntas → pausa | FEAT-036-analyst.md | Pausa |
| 3 | AgentSj | Respuestas 1–5 cerradas | analyst.md, TASKS | OK |
| 4 | Arquitecto | Feature Brief final | FEAT-036.md | OK |
| 5 | AgentSj | Plan T1–T4 | FEAT-036-plan.md | OK |
| 6–9 | Feature | T1 shell → T4 import/sync | app, tests | OK |
| 10 | Revisor | Aprobado con observaciones | FEAT-036.md review | OK |
| 11 | Documentador | Docs técnica + usuario | modules/user acreditaciones | OK |
| 12 | AgentSj | Checklist cierre | Completadas | OK |
