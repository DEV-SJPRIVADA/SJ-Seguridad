# Plan de orquestacion — FEAT-XXX

> Generado por el AgentSj tras aprobar el Feature Brief. Guardar como `docs/briefs/FEAT-XXX-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-XXX |
| Modo | orquestado / manual |
| Rama Git | feat/FEAT-XXX-slug |
| Modulo principal | |
| Run log | `docs/runs/FEAT-XXX-run-log.md` |
| shared-files | |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | |
| 2 | Arquitecto | Feature Brief final | 1 | |
| 3 | Feature | Tarea 1: (ej. migracion + modelos) | 2 | |
| 4 | Feature | Tarea 2: (ej. controlador + rutas) | 3 | |
| 5 | Feature | Tarea 3: (ej. vistas + JS) | 4 | |
| 6 | Revisor | Review del diff completo | 5 | |
| 7 | Documentador | docs/modules + docs/user | 6 | |
| 8 | AgentSj | Checklist cierre | 7 | |

## Paralelismo

¿Hay tareas que pueden ejecutarse en paralelo? (solo si modulos distintos y sin shared-files)

-

## Puntos de pausa usuario

- Post-Analista: preguntas abiertas
- Post-Brief: confirmacion de alcance
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| | | serializar / asignar un solo agente |
