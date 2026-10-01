# Run log — FEAT-041

> Registro persistente del flujo multi-agente. Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-041 |
| Titulo | Tablero Formación GH (Dashboard + Formaciones: import replace-all) |
| Modo | orquestado |
| Modulo | gestion_humana — `formacion` |
| Chat AgentSj | 2026-10-01 formacion GH |
| Brief | `docs/briefs/FEAT-041.md` |
| Plan | `docs/briefs/FEAT-041-plan.md` |
| Inicio | 2026-10-01 |
| Cierre | 2026-10-01 |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-10-01 | `implementa` (post propuesta Ask) | AgentSj | Creo FEAT-041 en TASKS.md y run log | `docs/TASKS.md`, `docs/runs/FEAT-041-run-log.md` | OK |
| 2 | 2026-10-01 | Decisiones cerradas en chat Ask + «implementa» | Analista | Skip — ver/editar tipo Cursos; 2 pestañas; replace-all; plantilla; filtros; parse fecha→mes/año | — | Skip |
| 3 | 2026-10-01 | Task Arquitecto | Arquitecto | Brief final T1–T4 + shared-files | `docs/briefs/FEAT-041.md` | OK |
| 4 | 2026-10-01 | AgentSj post-brief | AgentSj | Plan orquestacion; usuario implementa — sin pausa | `docs/briefs/FEAT-041-plan.md` | OK |
| 5 | 2026-10-01 | Task Feature T1 | Feature | Shell+permisos+migración+modelo+nav+tests acceso | access/audit/formacion config, Formacion*, rutas GH, nav, vistas placeholder, FormacionBoardAccessTest | OK |
| 6 | 2026-10-01 | Task Feature T2 | Feature | Listado Formaciones: DT serverSide, filtros, export, options, tests | FormacionDatatableService, FormacionExport, controller/rutas/vista, FormacionFormacionesTest | OK |
| 7 | 2026-10-01 | Task Feature T3 | Feature | Import replace-all + plantilla + parse fechas ES/Excel/Y-m-d + tests | FormacionImportService, TemplateExport, ImportFormacionRequest, modal UI, FormacionImportTest | OK |
| 8 | 2026-10-01 | Task Feature T4 | Feature | Dashboard KPIs + metrics JSON + filtro año + ApexCharts + tests | FormacionDashboardService, dashboard view/JS, route metrics, FormacionDashboardTest | OK |
| 9 | 2026-10-01 | Task Revisor | Revisor | Review T1–T4; 37 tests OK; Aprobado con observaciones (sin blockers) | `docs/reviews/FEAT-041.md` | OK |
| 10 | 2026-10-01 | Task Documentador | Documentador | Doc tecnica + usuario + ACCESS/INDEX/ARCHITECTURE/PROJECT_CONTEXT | `docs/modules/formacion.md`, `docs/user/formacion.md` | OK |
| 11 | 2026-10-01 | Checklist cierre | AgentSj | Checklist OK; 37 tests revalidación; movido a Completadas | `docs/TASKS.md`, run-log | OK |

## Notas

### Decisiones cerradas (usuario)

- Tablero **Formación** en Gestión humana.
- Pestañas: **Dashboard** | **Formaciones**.
- Permisos estilo Cursos: `view.board.gestion_humana.formacion` + `formacion.view` + `formacion.edit` (`edit` implica `view` en AccessService).
- Dashboard / shell: sin permiso aparte de dashboard; acceso con board + `formacion.view`.
- Sin catálogos / `parameters.edit`.
- Sin migración de permisos legacy.
- Formaciones: carga masiva que **reemplaza todo** el dataset; descarga de plantilla; filtros.
- Fecha Excel tipo `jueves, 4 de junio de 2026, 00:00` → columnas `fecha_inicio`, `mes`, `anio`.
- Archivo referencia: `FORMACION.xlsx` (~81k filas) → DataTables **server-side**.

### Columnas Excel

| Excel | Campo BD propuesto |
| --- | --- |
| Número de ID | `numero_id` |
| Nombre completo | `nombre_completo` |
| Fecha de inicio del curso | `fecha_inicio` (+ `mes`, `anio`) |
| Nombre completo del curso | `nombre_curso` |
| Calificación | `calificacion` (nullable) |
| Nombre de la categoría | `categoria` |

### Supuestos (usuario dijo implementa sin matizar)

- Dashboard v1: KPIs básicos (total, por mes, por categoría) sobre datos cargados.
- Filtros: año, mes, categoría, curso, número ID, nombre.
- Export Excel con `formacion.view`.
- Confirmación UI antes del replace-all (destructivo).
