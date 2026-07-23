# Prompts de agentes

Prompts fijos para el workflow multi-agente. Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md).

**Inicio rapido (Agent mode):** `@agent-sj [descripcion]` — skill en [`.cursor/skills/agent-sj/SKILL.md`](../../.cursor/skills/agent-sj/SKILL.md). Alternativa: `AgentSj [descripcion]`.

| Archivo | Rol |
| --- | --- |
| [`.cursor/skills/agent-sj/SKILL.md`](../../.cursor/skills/agent-sj/SKILL.md) | **Skill AgentSj** — invocacion explicita recomendada |
| [`start-feature.md`](prompts/start-feature.md) | Entrada chat maestro — inicia AgentSj (equivale a `@agent-sj`) |
| [`orchestrator.md`](prompts/orchestrator.md) | AgentSj / PM |
| [`analyst.md`](prompts/analyst.md) | Analista |
| [`architect.md`](prompts/architect.md) | Arquitecto |
| [`feature-developer.md`](prompts/feature-developer.md) | Agente Feature |
| [`reviewer.md`](prompts/reviewer.md) | Revisor |
| [`documenter.md`](prompts/documenter.md) | Documentador |
| [`fast-lane.md`](prompts/fast-lane.md) | Carril rapido |
