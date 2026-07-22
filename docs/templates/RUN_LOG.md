# Run log — FEAT-XXX

> Registro persistente del flujo multi-agente. Crear al iniciar la feature como `docs/runs/FEAT-XXX-run-log.md`.  
> Plantilla: [`RUN_LOG.md`](RUN_LOG.md) — Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-XXX |
| Titulo | |
| Modo | orquestado / manual |
| Modulo | |
| Chat Orquestador | |
| Brief | `docs/briefs/FEAT-XXX.md` |
| Plan | `docs/briefs/FEAT-XXX-plan.md` |
| Inicio | YYYY-MM-DD |
| Cierre | |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | | `orquestar` + descripcion (o `@start-feature.md`) | Orquestador | Creo FEAT-XXX en TASKS.md y run log | `docs/TASKS.md`, `docs/runs/FEAT-XXX-run-log.md` | OK |
| 2 | | Task automatico | Analista | | | |
| 3 | | Task automatico | Arquitecto | | | |
| 4 | | Task automatico | Feature | | | |
| 5 | | Task automatico | Revisor | | | |
| 6 | | Task automatico | Documentador | | | |
| 7 | | Checklist cierre | Orquestador | Movio a Completadas | `docs/TASKS.md` | OK |

### Estados validos

| Estado | Significado |
| --- | --- |
| OK | Paso completado |
| Pausa | Esperando respuesta del usuario |
| Blocker | Revisor o dependencia detiene el flujo |
| Skip | No aplica en este feature (ej. piloto sin Analista) |
| Reintento | Correccion tras review |

## Tabla para el chat (copiar al final de cada respuesta del Orquestador)

Al cerrar **cada turno**, el Orquestador muestra solo las filas nuevas o actualizadas desde el ultimo mensaje:

| # | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- |
| | | | | |

## Notas

- Prompt / trigger: resumen corto del mensaje del usuario o `Task automatico` si fue subagente.
- Artefactos: rutas de archivos creados o modificados (separados por coma).
- Incrementar `#` secuencialmente; no reutilizar numeros.
