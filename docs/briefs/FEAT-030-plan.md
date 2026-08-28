# Plan de orquestacion — FEAT-030

> Generado por AgentSj tras Feature Brief final. Brief: [`FEAT-030.md`](FEAT-030.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-030 |
| Modo | orquestado |
| Rama Git | feat/FEAT-030-purchase-request-attachments |
| Modulo principal | purchase-requests (Solicitudes de compra) |
| Run log | `docs/runs/FEAT-030-run-log.md` |
| shared-files | — (ninguno) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Vacios cerrados (decisiones usuario + supuestos) | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan + Task Card T1 | 2 | OK |
| 4 | Feature | T1 vertical slice: migracion + servicio + store/resubmit/show/download + UI + tests | 3 | OK |
| 5 | Revisor | Review del diff completo | 4 | OK |
| 6 | Documentador | `docs/modules/purchase-requests.md` + `docs/user/purchase-requests.md` | 5 | OK |
| 7 | AgentSj | Checklist cierre | 6 | OK |

## Paralelismo

Ninguno. Un solo modulo, una sola Task Card.

## Puntos de pausa usuario

- Post-Analista: no aplica (LISTO_ARQUITECTO)
- **Post-Brief: confirmar supuestos** (tope 5×10 MB, tipos, etiqueta Adjuntos, reenviar keep/quitar) antes de T1
- Post-Revisor: blockers criticos

## Conflictos detectados

Ninguno en shared-files.

| Archivo de modulo | Quien edita |
| --- | --- |
| `routes/modules/purchase-requests.php` | Solo T1 |
| `config/purchase-requests.php` | Solo T1 (modulo propio, no shared) |

## Task Card

[`FEAT-030-task-1.md`](FEAT-030-task-1.md)
