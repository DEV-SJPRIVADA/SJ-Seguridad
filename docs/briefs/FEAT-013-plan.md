# Plan de orquestacion — FEAT-013

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-013 |
| Modo | orquestado |
| Modulo principal | admin / notifications-config |
| Run log | `docs/runs/FEAT-013-run-log.md` |
| shared-files | `config/access.php`, `routes/web.php`, `NavigationResolver` (si aplica), `RoleAndPermissionSeeder`, layouts sidebar |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas + respuestas usuario | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** Migracion rename + modelos + `NotificationConfigService` + envios requisiciones | 2 | Pendiente |
| 4 | Feature | **T2** Admin UI + rutas + permiso + navegacion (`shared-files`) | 3 | Pendiente |
| 5 | Feature | **T3** Retiro Parametros requisiciones + tests + limpieza rutas | 4 | Pendiente |
| 6 | Revisor | Review diff completo | 5 | Pendiente |
| 7 | Documentador | `docs/modules/notifications-config.md` + user + requisitions | 6 | Pendiente |
| 8 | AgentSj | Checklist cierre | 7 | Pendiente |

## Paralelismo

Ninguno: T2 toca shared-files; T3 depende de servicio y admin operativo.

## Puntos de pausa usuario

- Post-Analista: **cerrado** 2026-07-29
- Post-Brief: confirmacion implicita al responder preguntas

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T2 | un solo agente Feature T2 |
| `routes/web.php` | T2 | un solo agente Feature T2 |
| `RequisitionController` | T1 + T3 | T1 envios; T3 limpieza parameters |
