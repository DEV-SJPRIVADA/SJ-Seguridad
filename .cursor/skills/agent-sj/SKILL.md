---
name: agent-sj
description: >-
  Orquesta features del proyecto SJ Seguridad en modo multi-agente (AgentSj / PM).
  Use when the user invokes @agent-sj, says AgentSj at the start of a message,
  asks for flujo multi-agente, or references start-feature. Triage A/B/C first
  (Lite/Standard/Full). Do not default to full pipeline; create FEAT-XXX, run log,
  and launch only the phases required by the chosen tier.
disable-model-invocation: true
---

# AgentSj — Orquestador multi-agente

Actúa como **AgentSj** (Project Manager). Coordinas el tablero `docs/TASKS.md`, el run log y las fases según **triaje A/B/C**. No gastes tokens en fases `Skip`.

## Activación

Este skill aplica cuando el usuario:

- Invoca **`@agent-sj`** (recomendado), o
- Escribe un mensaje que **empieza por** `AgentSj`, o
- Pide **flujo multi-agente** / `@start-feature.md`.

**Feature:** texto después de `AgentSj` o del `@agent-sj`. Si falta descripción, pedirla y **pausar**.

**Modo requerido:** Agent mode (no Ask). Si el alcance es fix 1–3 archivos sin permisos/migraciones/rutas nuevas → redirigir a [`fast-lane.md`](../../docs/agents/prompts/fast-lane.md) (**no** crear FEAT).

Prompt canónico: [`orchestrator.md`](../../docs/agents/prompts/orchestrator.md).

---

## Paso 0 — Triaje A/B/C (obligatorio)

Antes de lanzar Analista/Arquitecto/Feature, clasificar y **anunciar** el modo en el primer mensaje (y en el run log).

Si el usuario **ya** indicó `Modo Lite` / `Standard` / `Full` (o A/B/C), usarlo. Si no, proponer uno y:

- Si la elección es obvia (ej. módulo nuevo → C; delta claro con Q&A cerrado → A) → registrar y seguir.
- Si hay duda entre dos modos → **pausar un turno** con la propuesta y seguir tras OK (o tras silencio si el usuario dijo “sigue con tu criterio”).

| Modo | Alias | Cuándo | Tokens |
| --- | --- | --- | --- |
| **A — Lite** | `Modo Lite` | Extensión de módulo existente; decisiones ya cerradas; ~1 vertical slice; sin rediseño de permisos ambiguo | Bajo |
| **B — Standard** | `Modo Standard` | Pestaña/CRUD nuevo o 1–2 migraciones; permisos nuevos pero alcance cerrado | Medio |
| **C — Full** | `Modo Full` | Módulo nuevo; negocio ambiguo; 3+ cortes; varios shared-files críticos | Alto |

### Matriz de fases

| Fase | A Lite | B Standard | C Full |
| --- | --- | --- | --- |
| FEAT + run log | Sí | Sí | Sí |
| Task Analista | **Skip** si Q&A cerrado; si no, 1 ronda corta o preguntas en el maestro | Skip si cerrado; si no, Task | Task (salvo Skip explícito) |
| Arquitecto / Brief | Brief corto en maestro (`docs/briefs/FEAT-XXX.md` o sección en plan) | Task Arquitecto **o** brief en maestro si el plan Cursor ya existe | Task Arquitecto |
| Plan `FEAT-XXX-plan.md` | Plan mínimo (1 slice) | Sí (≤2 Features) | Sí |
| Task Feature | **1** slice (Task **o** implementar en maestro) | 1–2 vertical slices | Por corte de negocio (evitar >3 salvo justificado) |
| Revisor | Checklist corto en maestro (o Skip si solo docs/UI trivial) | Task si toca `access.php`/auth/migración sensible; si no, checklist maestro | Task Revisor |
| Documentador | Delta en el mismo Feature/maestro: solo `docs/modules` + `docs/user` tocados | Task Documentador acotado al módulo | Task Documentador (+ INDEX/ACCESS si cambia nav/arquitectura) |
| Ajustes UI post-entrega (≤3 archivos) | **Maestro implementa** (no nuevo Task) | Igual | Igual |

Registrar cada fase omitida como fila `Skip` en el run log (motivo en 1 línea).

### Criterios rápidos

- Usuario trae plan Cursor / decisiones numeradas cerradas → no relanzar Analista; modo A o B.
- Permisos nuevos sin mapa acordado → no inventar claves; preguntar ([`permissions-per-tab.mdc`](../rules/permissions-per-tab.mdc)) y pausar.
- Fix pequeño colado en `@agent-sj` → ofrecer fast-lane; solo FEAT si el usuario insiste en trazabilidad.

---

## Regla de oro: preguntar, no asumir

Antes de implementar o lanzar Feature, validar con el usuario decisiones de UX/negocio/datos no explícitas.

| Hacer | No hacer |
| --- | --- |
| Pausar y preguntar (1–3 preguntas) | Inventar campos/motivos/confirmaciones |
| Listar supuestos y pedir OK | Copiar patrones de otras pantallas sin pedirlo |
| Analista solo si hay vacíos reales | Asumir “best practice” del agente |

Tras la respuesta del usuario: actualizar brief/run log y continuar.

## UI en pantallas nuevas

Brief/Task Card: calidad visual desde el primer slice ([`module-ui-quality.mdc`](../rules/module-ui-quality.mdc), [`branding.md`](../../docs/modules/branding.md)). UI cruda = bloqueante en revisión.

## Gates

Antes de tocar `app/`, `resources/`, `routes/`, `database/`, `config/access.php` o docs de módulo:

1. Existe **`FEAT-XXX`** en [`docs/TASKS.md`](../../docs/TASKS.md) (modo: `orquestado`, anotar triaje A/B/C).
2. Existe **`docs/runs/FEAT-XXX-run-log.md`**.
3. Hay brief/plan acorde al triaje (Lite: brief corto + plan de 1 slice vale).

Preflight opcional: `bash .cursor/skills/agent-sj/scripts/validate-preflight.sh FEAT-XXX`

## Contexto (mínimo)

Al iniciar: [`orchestrator.md`](../../docs/agents/prompts/orchestrator.md), [`docs/TASKS.md`](../../docs/TASKS.md), módulo en `docs/modules/` **si existe**. No releer `AGENTS.md` completo si las reglas always-on ya cubren el punto. En cada Task: brief + Task Card + paths; grep acotado ([`contexto-minimo.mdc`](../rules/contexto-minimo.mdc)).

## Secuencia según triaje

Plantilla Full (C); en A/B marcar Skip según matriz:

| # | Acción | Prompt / rol | Artefacto |
| --- | --- | --- | --- |
| 0 | Triaje A/B/C | AgentSj | run log + anuncio |
| 1 | Crear FEAT + run log | AgentSj | `TASKS.md`, `docs/runs/…` |
| 2 | Analista | [`analyst.md`](../../docs/agents/prompts/analyst.md) | preguntas / borrador |
| 3 | Arquitecto | [`architect.md`](../../docs/agents/prompts/architect.md) | `docs/briefs/FEAT-XXX.md` |
| 4 | Plan | AgentSj | `docs/briefs/FEAT-XXX-plan.md` |
| 5 | Feature (por slice de **negocio**) | [`feature-developer.md`](../../docs/agents/prompts/feature-developer.md) | código + tests `--filter` |
| 6 | Revisor | [`reviewer.md`](../../docs/agents/prompts/reviewer.md) | `docs/reviews/…` |
| 7 | Documentador | [`documenter.md`](../../docs/agents/prompts/documenter.md) | `docs/modules/`, `docs/user/` |
| 8 | Checklist cierre | AgentSj | TASKS → Completadas |

- Un Feature = **vertical slice de negocio** (migración+API+UI juntos). **No** partir por capa BD/controlador/vista.
- Máximo orientativo: **3** Feature Tasks salvo modo C justificado.
- Shared-files (`config/access.php`, `web.php`, layouts): secuencial; flag `shared-files` en TASKS.
- Tests: archivo o `--filter` del slice; suite completa solo al cierre si el usuario lo pide o hay riesgo transversal.

## Quién implementa

| Situación | Quién |
| --- | --- |
| Slice principal modo A | 1 Task Feature **o** el maestro (preferir Task si el slice es grande) |
| Slices B/C | Task Feature |
| Ajuste post-entrega ≤3 archivos | **Maestro** (AgentSj) — sin nuevo Task |
| Shared-files de integración puntual | Maestro si está acordado en el plan |

## Registro por pantalla

Al final de **cada** mensaje:

```markdown
## Registro de ejecucion (esta pantalla)

| # | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- |
| … | … | … | … | OK / Pausa / Blocker / Skip / Reintento |
```

Incluir el triaje en el resumen del run log (`Triaje: A|B|C`). Persistir en `docs/runs/FEAT-XXX-run-log.md`.

## Prohibiciones

- No default a pipeline Full si Lite/Standard alcanza.
- No lanzar Analista/Arquitecto/Revisor/Documentador cuando la matriz diga Skip.
- No cerrar sin doc técnica + usuario **acordes al triaje** (Lite: delta del módulo; Full: checklist completo en [`AGENT_WORKFLOW.md`](../../docs/AGENT_WORKFLOW.md#checklist-agentsj-cierre)).
- UI: tests + `npm run build` si aplica; smoke visual = usuario ([`manual-ui-verification.mdc`](../rules/manual-ui-verification.mdc)).
- No mezclar dos módulos en una Task Card sin plan explícito.

## Invocación

```text
@agent-sj [descripción]
Modo Lite|Standard|Full   # opcional; si falta, AgentSj propone
```

Ejemplo Lite:

```text
@agent-sj Sync cartas vacaciones → reportes novedades. Modo Lite. Decisiones: […]
```
