# Plan de orquestacion — FEAT-014

> Generado por AgentSj tras Feature Brief final.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-014 |
| Modo | orquestado |
| Rama Git | feat/FEAT-014-checklist-cliente (sugerida) |
| Modulo principal | matriz-clientes (Comercial) |
| Run log | `docs/runs/FEAT-014-run-log.md` |
| shared-files | `routes/areas/comercial.php`, vistas `clients/index`, dashboard, importador, requests/vistas servicio (T3–T4) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas + respuestas clave usuario | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** BD, modelos, migracion datos, catalogo documental | 2 | Pendiente |
| 4 | Feature | **T2** Ruta checklist, controlador, vista tabla, boton index | 3 | Pendiente |
| 5 | Feature | **T3** Retiro checklist servicios + import + vigencia servicios | 4 | Pendiente |
| 6 | Feature | **T4** Dashboard, export Excel, tests cierre | 5 | Pendiente |
| 7 | Revisor | Review diff completo | 6 | Pendiente |
| 8 | Documentador | `docs/modules/matriz-clientes.md` + `docs/user/matriz-clientes.md` | 7 | Pendiente |
| 9 | AgentSj | Checklist cierre → Completadas | 8 | Pendiente |

## Paralelismo

Ninguno: un vertical slice secuencial; T3 toca shared-files con servicios e import.

## Puntos de pausa usuario

- Post-Analista: **OK** (2026-07-29)
- Post-Brief: **confirmar** riesgo «un vencimiento por cliente» vs Excel por documento antes de cierre produccion
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `CommercialService.php` / migraciones | T1 drop columns vs T3 UI | T1 migracion + T3 limpieza en mismo feature, orden T1→T3 |
| `MtCo01Importer.php` | T1/T3 | Solo T3 tras modelos cliente |
| `CommercialDashboardController` | T4 | Tras T1 helpers en cliente |

## Referencia Task Cards

Ver seccion «Plan de implementacion» en [`FEAT-014.md`](FEAT-014.md).
