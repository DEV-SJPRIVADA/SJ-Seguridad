# Tablero de tareas — SJ Seguridad

Tablero vivo para el **AgentSj**. Convencion de IDs: `FEAT-001`, `FEAT-002`, …

Workflow: [`docs/AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md)

---

## En progreso

| ID | Feature | Modo | Fase actual | AgentSj chat | Rama | Brief | Run log | shared-files |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FEAT-013 | Configuracion global de notificaciones (Super Admin) | orquestado | Feature T1-T3 implementado | 2026-07-29 | — | [`docs/briefs/FEAT-013.md`](briefs/FEAT-013.md) | [`docs/runs/FEAT-013-run-log.md`](runs/FEAT-013-run-log.md) | `config/access.php`, rutas admin |
| FEAT-014 | Checklist documental por cliente + vista seguimiento tablero Clientes | orquestado | Feature T1–T4 (implementado) | 2026-07-29 checklist cliente | — | [`docs/briefs/FEAT-014.md`](briefs/FEAT-014.md) | [`docs/runs/FEAT-014-run-log.md`](runs/FEAT-014-run-log.md) | rutas comercial, dashboard, import, vistas servicio |
| FEAT-015 | Notificacion correo documentacion comercial por vencer | orquestado | Feature T1 implementado — Revisor | 2026-07-29 comercial doc por vencer | — | [`docs/briefs/FEAT-015.md`](briefs/FEAT-015.md) | [`docs/runs/FEAT-015-run-log.md`](runs/FEAT-015-run-log.md) | migracion tipo notificacion, `routes/console.php`, admin notificaciones |
| FEAT-016 | Listado servicios: orden columnas y vigencia por contrato | orquestado | Feature T1 implementado — Revisor | 2026-07-29 servicios vigencia | — | [`docs/briefs/FEAT-016.md`](briefs/FEAT-016.md) | [`docs/runs/FEAT-016-run-log.md`](runs/FEAT-016-run-log.md) | export servicios, migracion is_inactive, activar |
| FEAT-020 | Contratado: cedula/nombre + lista espera + tablero Ficha empleados | orquestado | Cierre (T1-T4 + review) | 2026-07-30 ficha empleados | — | [`docs/briefs/FEAT-020.md`](briefs/FEAT-020.md) | [`docs/runs/FEAT-020-run-log.md`](runs/FEAT-020-run-log.md) | `config/access.php`, rutas GH, migraciones, requisitions edit |

---

## Cola

| ID | Prioridad | Feature | Modulo | Dependencias |
| --- | --- | --- | --- | --- |
| | | | | |

---

## Completadas (ultimas 10)

| ID | Feature | Modo | Validado | Run log | Fecha cierre |
| --- | --- | --- | --- | --- | --- |
| FEAT-023 | Captura delegada indicadores (suplencia vacaciones) | orquestado | Si | [`docs/runs/FEAT-023-run-log.md`](runs/FEAT-023-run-log.md) | 2026-08-04 |
| FEAT-022 | Pendientes ficha: Gestionar Empleado con formulario precargado desde requisicion | orquestado | Si | [`docs/runs/FEAT-022-run-log.md`](runs/FEAT-022-run-log.md) | 2026-08-03 |
| FEAT-021 | Audit log central del sistema | orquestado | Si | [`docs/runs/FEAT-021-run-log.md`](runs/FEAT-021-run-log.md) | 2026-08-03 |
| FEAT-019 | Notificacion contrato servicio por vencer (30 dias) | orquestado | Si | [`docs/runs/FEAT-019-run-log.md`](runs/FEAT-019-run-log.md) | 2026-07-30 |
| FEAT-018 | Comercial: pestaña Parametros selectores servicios | orquestado | Si | [`docs/runs/FEAT-018-run-log.md`](runs/FEAT-018-run-log.md) | 2026-07-30 |
| FEAT-017 | Comercial: tablero Gestion Clientes + pestañas Clientes/Servicios | orquestado | Si | [`docs/runs/FEAT-017-run-log.md`](runs/FEAT-017-run-log.md) | 2026-07-30 |
| FEAT-012 | Autorizacion gerencia cargo nuevo (enfoque A) | orquestado | Si | [`docs/runs/FEAT-012-run-log.md`](runs/FEAT-012-run-log.md) | 2026-07-28 |
| FEAT-011 | Encargados seleccion: usuarios GH activables (Reclutador) | orquestado | Si | [`docs/runs/FEAT-011-run-log.md`](runs/FEAT-011-run-log.md) | 2026-07-28 |
| FEAT-010 | Unificar graficos ApexCharts (GH + Operaciones; quitar Chart.js/ECharts) | orquestado | Si | [`docs/runs/FEAT-010-run-log.md`](runs/FEAT-010-run-log.md) | 2026-07-27 |
| FEAT-007 | Checklist documental: fecha vencimiento por documento | orquestado | Si | [`docs/runs/FEAT-007-run-log.md`](runs/FEAT-007-run-log.md) | 2026-07-27 |
| FEAT-006 | Export Excel Gestion: todos los campos + rango fechas | orquestado | Si | [`docs/runs/FEAT-006-run-log.md`](runs/FEAT-006-run-log.md) | 2026-07-24 |
| FEAT-005 | Campo Estructura del servicio en requisiciones | orquestado | Si | [`docs/runs/FEAT-005-run-log.md`](runs/FEAT-005-run-log.md) | 2026-07-24 |
| FEAT-004 | Ranking dashboard indicadores operaciones | orquestado | Si | [`docs/runs/FEAT-004-run-log.md`](runs/FEAT-004-run-log.md) | 2026-07-23 |
| FEAT-003 | Capturadores en Ajustes indicadores | orquestado | Si | [`docs/runs/FEAT-003-run-log.md`](runs/FEAT-003-run-log.md) | 2026-07-23 |
| FEAT-002 | Export informe gestion FO-GI-39 (PPTX) | directo | Si | — | 2026-07-22 |
| FEAT-PILOT-001 | Doc usuario admin-users (piloto workflow) | orquestado | Si | [`docs/runs/FEAT-PILOT-001-run-log.md`](runs/FEAT-PILOT-001-run-log.md) | 2026-07-22 |
| DOC-ALIGN-001 | Alineacion documentacion IA/dev/usuario (7 modulos) | manual | Si | — | 2026-07-22 |

---

## Ejemplo comentado (no borrar — referencia)

<!--
### Como crear una feature nueva

1. AgentSj agrega fila en Cola o En progreso.
2. Ejemplo:

| ID | Feature | Modo | Fase actual | AgentSj chat | Rama | Brief | Run log | shared-files |
| FEAT-001 | Export Excel ajustes operaciones | orquestado | Analista | chat-2026-07-22 | feat/FEAT-001-export-ajustes | docs/briefs/FEAT-001.md | docs/runs/FEAT-001-run-log.md | config/access.php |

3. Fases tipicas: Analista → Arquitecto → Feature (N tareas) → Revisor → Documentador → Cierre
4. Al completar: mover a Completadas y limpiar En progreso.

### Piloto FEAT-PILOT-001

Validacion del Agente Documentador sin codigo nuevo:
- Entrada: doc tecnica existente docs/modules/admin-users.md
- Salida: docs/user/admin-users.md
- Ver docs/briefs/FEAT-PILOT-001.md y docs/reviews/FEAT-PILOT-001.md
-->
