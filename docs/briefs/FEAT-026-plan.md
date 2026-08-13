# Plan de orquestacion — FEAT-026

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-026 |
| Depende de | FEAT-025 |
| Run log | `docs/runs/FEAT-026-run-log.md` |
| shared-files | `config/audit.php`, `AuditEventCatalog.php`, `docs/modules/audit-log.md` |

## Secuencia

| # | Agente | Task | Estado |
| --- | --- | --- | --- |
| 1 | Arquitecto | Brief FEAT-026 | OK |
| 2 | Feature | T1 Comercial + config/audit base | Pendiente |
| 3 | Feature | T2 Suministros | Pendiente |
| 4 | Feature | T3 Compras | Pendiente |
| 5 | Feature | T4 Documentos calidad | Pendiente |
| 6 | Feature | T5 Ficha empleados | Pendiente |
| 7 | Feature | T6 Regresion SystemAuditTest | Pendiente |
| 8 | Revisor | Review | Pendiente |
| 9 | Documentador | audit-log.md | Pendiente |

T2–T5 secuenciales tras T1 (shared config). T6 tras T1–T5.

Ver Task Cards en [`docs/briefs/FEAT-026.md`](FEAT-026.md).
