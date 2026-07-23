# Run logs — ejecuciones multi-agente

Cada feature en modo **orquestado** o **manual** tiene un registro persistente:

`docs/runs/FEAT-XXX-run-log.md`

## Para que sirve

- Saber **que agente** actuo en cada paso (Analista, Arquitecto, Feature, Revisor, Documentador, AgentSj).
- Ver **que hizo** y **que archivos** toco, sin depender del historial del chat.
- Mostrar al usuario una **tabla resumida por pantalla** al final de cada respuesta de AgentSj.

## Como se crea

1. AgentSj lo crea al recibir **`AgentSj`** o `@start-feature.md` (paso 1 del flujo).
2. Actualiza una fila por cada subagente `Task` o accion propia.
3. Enlaza la ruta en la columna **Run log** de [`docs/TASKS.md`](../TASKS.md).

Plantilla: [`docs/templates/RUN_LOG.md`](../templates/RUN_LOG.md)

## Ejemplo

- [`FEAT-PILOT-001-run-log.md`](FEAT-PILOT-001-run-log.md) — piloto del Documentador
