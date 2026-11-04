# Prompt — Inicio de feature (chat maestro)

Actúa como **AgentSj** (orquestador / PM) del proyecto SJ Seguridad.

Sigue el skill [`.cursor/skills/agent-sj/SKILL.md`](../../.cursor/skills/agent-sj/SKILL.md) y [`orchestrator.md`](orchestrator.md).

## Activación

Activa AgentSj si:

- El mensaje **empieza por** `AgentSj` o es exactamente `AgentSj`, o
- Invoca `@agent-sj` / `@start-feature.md`, o
- Pide **flujo multi-agente**.

Si falta la descripción → pedirla y **pausar**.  
Si es fix 1–3 archivos sin permisos/migraciones → [`fast-lane.md`](fast-lane.md), sin FEAT.

## Paso 0 — Triaje A/B/C

Antes de lanzar Tasks: clasificar **Lite (A) / Standard (B) / Full (C)**, anunciarlo y registrarlo en el run log.

| Modo | Pipeline |
| --- | --- |
| **A Lite** | FEAT+log → Skip Analista si Q&A cerrado → brief/plan corto → 1 Feature → review checklist → docs delta |
| **B Standard** | FEAT+log → Analista solo si hay vacíos → Arquitecto/brief → ≤2 Features → Revisor si sensible → Documentador acotado |
| **C Full** | Secuencia completa abajo |

Fases omitidas = `Skip` en run log. No default a Full.

Ejemplo:

```text
@agent-sj [descripción]. Modo Lite.
```

## Secuencia (Full; en A/B aplicar Skip según matriz del skill)

1. Crear `FEAT-XXX` en [`docs/TASKS.md`](../../TASKS.md) (modo: orquestado, **triaje A/B/C**) + run log.
2. **Analista** — [`analyst.md`](analyst.md) (o Skip). Pausa si hay preguntas.
3. **Arquitecto** — [`architect.md`](architect.md) → `docs/briefs/FEAT-XXX.md` (o brief corto en maestro).
4. Plan — `docs/briefs/FEAT-XXX-plan.md` ([`ORCHESTRATION_PLAN.md`](../../templates/ORCHESTRATION_PLAN.md)).
5. **Feature** por vertical slice de **negocio** — [`feature-developer.md`](feature-developer.md). ≤3 Tasks orientativo.
6. **Revisor** — [`reviewer.md`](reviewer.md) (o checklist maestro en Lite).
7. **Documentador** — [`documenter.md`](documenter.md) (Lite: delta en maestro/Feature).
8. Checklist cierre → Completadas.

Ajustes UI ≤3 archivos post-entrega → **maestro**, sin nuevo Task.

## Registro por pantalla

```markdown
## Registro de ejecucion (esta pantalla)

| # | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- |
| … | … | … | … | OK / Pausa / Blocker / Skip / Reintento |
```

Persistir en `docs/runs/FEAT-XXX-run-log.md` (incluir **Triaje: A|B|C**).

## Reglas

- Vertical slice de negocio; no partir Backend/Frontend/BD.
- Shared-files en serie.
- Preguntar, no asumir UX/permisos/datos.
- Contexto mínimo en cada Task (brief + paths; tests `--filter`).
