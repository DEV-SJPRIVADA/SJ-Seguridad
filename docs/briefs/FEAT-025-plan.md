# Plan de orquestacion — FEAT-025

> Generado por AgentSj tras Feature Brief aprobado por usuario (2026-08-11).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-025 |
| Modo | orquestado |
| Rama Git | feat/FEAT-025-audit-cross-modulo |
| Modulo principal | admin / audit-log (cross-modulo) |
| Run log | `docs/runs/FEAT-025-run-log.md` |
| shared-files | `config/audit.php`, `.env.example`, `AuditEventCatalog`, `SystemAuditController`, vista audit, docs audit-log |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas y alcance v1 | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** Infra transversal + UI defaults 30 dias | 2 | Pendiente |
| 4 | Feature | **T2** Admin usuarios audit | 3 | Pendiente |
| 5 | Feature | **T3** Requisiciones resumen audit | 3 | Pendiente |
| 6 | Feature | **T4** Notificaciones config audit | 3 | Pendiente |
| 7 | Feature | **T5** Regresion e integracion | 4, 5, 6 | Pendiente |
| 8 | Revisor | Review diff completo | 7 | Pendiente |
| 9 | Documentador | **T6** docs modules + user audit-log | 8 | Pendiente |
| 10 | AgentSj | Checklist cierre | 9 | Pendiente |

## Paralelismo

T2, T3 y T4 pueden ejecutarse en paralelo **despues de T1** (modulos distintos, sin conflicto en shared-files salvo T1 ya aplicado). Secuencia recomendada: T1 → T2 → T3 → T4 → T5 (serial, menor riesgo en Hostinger/dev).

## Puntos de pausa usuario

- Post-Analista: **cerrado** (2026-08-11)
- Post-Brief: **confirmado** via formulario AgentSj
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/audit.php` | T1 vs resto | Solo T1 modifica |
| `UserController` | T2 | Un agente |
| `RequisitionController` + `ManagementApprovalService` | T3 | Un agente |
| `NotificationConfigService` | T4 | Un agente |

## Referencia Task Cards

Ver [`docs/briefs/FEAT-025.md`](FEAT-025.md) seccion Task Cards T1–T6.
