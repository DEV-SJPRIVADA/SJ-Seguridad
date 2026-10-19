# Plan de orquestacion — FEAT-047

> Generado por el AgentSj tras Feature Brief final. Plantilla: [`ORCHESTRATION_PLAN.md`](../templates/ORCHESTRATION_PLAN.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-047 |
| Modo | orquestado |
| Rama Git | feat/FEAT-047-compras-proveedores-oc (opcional) |
| Modulo principal | purchase-requests / Compras |
| Brief | [`docs/briefs/FEAT-047.md`](FEAT-047.md) |
| Run log | [`docs/runs/FEAT-047-run-log.md`](../runs/FEAT-047-run-log.md) |
| shared-files | Si — `config/access.php`, `routes/modules/purchase-requests.php`, subnav Compras |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan de orquestacion | 2 | OK |
| 4 | Feature | **T1** Fase A: alinear FO-AD-44 Marzo 2026 v01 (PDF/Excel compra+suministro, foto) | 3 | OK |
| 5 | Feature | **T2** Proveedores: migracion + permisos + AccessService + pestana + CRUD + DT server-side | 4 | OK |
| 6 | Feature | **T3** Import/export VINE01 (solo PROVEEDOR=SI, dry-run/limit, dias_pago) | 5 | Pendiente |
| 7 | Feature | **T4** OC morph + emision bandeja (compra/suministro) + FO-AD-07 PDF/Excel | 6 | Pendiente |
| 8 | Feature | **T5** Evaluacion post-entrega UI+BD (no impresa; providers.edit) | 7 | Pendiente |
| 9 | Revisor | Review del diff completo | 8 | Pendiente |
| 10 | Documentador | docs/modules + docs/user (+ ACCESS_CONTROL / INDEX) | 9 | Pendiente |
| 11 | AgentSj | Checklist cierre | 10 | Pendiente |

## Paralelismo

Ninguno: shared-files (`access.php`, rutas/nav) y dependencia A→B→C. Ejecutar T1–T5 en serie.

## Puntos de pausa usuario

- Post-Analista: cerrado
- Post-Brief: usuario pidio iniciar implementacion (2026-10-19) — sin pausa adicional
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/access.php` | T2 | Solo T2 toca permisos nuevos |
| `routes/modules/purchase-requests.php` | T2, T4, T5 | Serializar |
| Subnav Compras | T2 | Solo T2 anade pestana Proveedores |
| Exporters FO-AD-44 | T1 | Solo T1 |
| Exporters FO-AD-07 / orders | T4–T5 | Tras T2–T3 |
