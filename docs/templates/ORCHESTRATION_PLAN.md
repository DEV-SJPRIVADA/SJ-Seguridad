# Plan de orquestacion — FEAT-XXX

> Generado por el AgentSj tras el triaje / brief. Guardar como `docs/briefs/FEAT-XXX-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-XXX |
| Triaje | A Lite / B Standard / C Full |
| Modo ejecucion | orquestado / manual |
| Rama Git | feat/FEAT-XXX-slug |
| Modulo principal | |
| Run log | `docs/runs/FEAT-XXX-run-log.md` |
| shared-files | |
| Skips previstos | (ej. Analista, Revisor Task) |

## Secuencia de tareas

> Cada **Feature** = vertical slice de **negocio** (migración + backend + UI juntos). No partir por capa.

### Ejemplo Lite (A)

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Skip — decisiones cerradas | — | Skip |
| 2 | AgentSj | Brief/plan corto | — | |
| 3 | Feature | Slice unico: … | 2 | |
| 4 | AgentSj | Review checklist + docs delta | 3 | |
| 5 | AgentSj | Checklist cierre | 4 | |

### Ejemplo Full (C)

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | |
| 2 | Arquitecto | Feature Brief final | 1 | |
| 3 | Feature | Slice 1: (ej. catalogo + permisos + listado) | 2 | |
| 4 | Feature | Slice 2: (ej. import + export) | 3 | |
| 5 | Revisor | Review del diff completo | 4 | |
| 6 | Documentador | docs/modules + docs/user | 5 | |
| 7 | AgentSj | Checklist cierre | 6 | |

## Paralelismo

¿Hay tareas en paralelo? (solo modulos distintos y sin shared-files)

-

## Puntos de pausa usuario

- Post-triaje (si duda A/B/C)
- Post-Analista: preguntas abiertas
- Post-Brief: confirmacion de alcance (Standard/Full)
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| | | serializar / asignar un solo agente |
