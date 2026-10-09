# Run log — FEAT-045

> Registro persistente del flujo multi-agente. Crear al iniciar la feature como `docs/runs/FEAT-045-run-log.md`.  
> Plantilla: [`RUN_LOG.md`](../templates/RUN_LOG.md) — Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-045 |
| Titulo | Tablero GH MT-ST-04 — control exámenes psicofísicos y psicosensométricos |
| Modo | orquestado |
| Modulo | gestion_humana / mt_st_04 |
| Chat AgentSj | 2026-10-09 MT-ST-04 |
| Brief | `docs/briefs/FEAT-045.md` |
| Plan | `docs/briefs/FEAT-045-plan.md` |
| Inicio | 2026-10-09 |
| Cierre | 2026-10-09 |

## Decisiones de negocio confirmadas (usuario)

| Tema | Decision |
| --- | --- |
| Permisos | `view.board.gestion_humana.mt_st_04` + `mt_st_04.view` + `mt_st_04.edit` (`edit` implica `view`); dashboard con `.view`; asignación Spatie **manual** |
| Pestañas | Dashboard (KPIs + gráficos) + Matriz (DataTables server-side). **Sin** Catálogos |
| ARMA / APTO | Selects fijos SI/NO (sin catálogo BD) |
| Lookup ficha | NOMBRE, CARGO, CIUDAD, PUESTO (`cost_center_name`) solo lectura por cédula |
| Fechas examen | Digitación manual (examen 1 y examen 2) |
| Fechas vencimiento | Calculadas = examen + **364** días (como Excel MT-ST-04) |
| ESTADO / ESTADO2 | Persistidos en BD + recalculo (cron/job + al guardar) |
| ESTADO2 NO APLICA | Si CARGO (mayúsculas) es exactamente `GUARDA` u `OPERADOR` |
| VENCERA | ≤ 30 días calendario hasta vencimiento; TZ `America/Bogota` |
| Unicidad | 1 fila por cédula; import Excel upsert por cédula |
| Bulk acciones | No (solo import + CRUD fila a fila) |
| Filtro personal | Default: solo **activos** ficha; opción UI para ver **desvinculados** |
| RETIRADOS Excel | Fuera de alcance V1 |
| Dashboard KPIs | Total/Vigente/Vencera/Vencido examen 1; mismos examen 2 (excl. NO APLICA); Aptos vs no aptos + gráficos |
| Referencia Excel | `MT-ST-04 CONTROL EXAMENES PSICOFISICOS Y PSICOSENSOMETRICO - 2026` hoja MATRIZ |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-10-09 | Confirmación alcance MT-ST-04 (respuestas finales) | AgentSj | Creó FEAT-045 + run log; decisiones cerradas; lanza Analista | `docs/TASKS.md`, `docs/runs/FEAT-045-run-log.md` | OK |
| 2 | 2026-10-09 | Task automatico | Analista | Brief analista cerrado (sin preguntas); alcance/reglas/riesgos/checklist Arquitecto | `docs/briefs/FEAT-045-analyst.md` | OK |
| 3 | 2026-10-09 | Task automatico | Arquitecto | Feature Brief final: permisos, modelo, reglas VENCERA/NO APLICA, T1–T4, shared-files | `docs/briefs/FEAT-045.md` | OK |
| 4 | 2026-10-09 | Usuario: implementa | AgentSj | Plan orquestación; lanza Feature T1 | `docs/briefs/FEAT-045-plan.md` | OK |
| 5 | 2026-10-09 | Task automatico | Feature T1 | Shell + permisos + migración `mt_st_04_registros` + AccessService + nav + vistas placeholder Dashboard/Matriz; tests acceso OK | `config/access.php`, `MtSt04AccessService`, `MtSt04Controller`, `HasMtSt04Tabs`, model/factory/migración, `config/mt_st_04.php`, `config/audit.php`, nav/User, `MtSt04BoardAccessTest` | OK |
| 6 | 2026-10-09 | Task automatico | Feature T2 | Matriz CRUD + DT server-side + lookup + calculadora estados; tests unicidad/NO APLICA/VENCERA/activos/permisos | `MtSt04EstadoCalculator`, `MtSt04ListService`, `MtSt04DatatableService`, Form Requests store/update, `MtSt04Controller` (CRUD/DT/lookup), rutas GH, vistas matriz + modales, `MtSt04MatrizTest`, `MtSt04EstadoCalculatorTest` | OK |
| 7 | 2026-10-09 | Task automatico | Feature T3 | Plantilla + import upsert (última gana) + export BaseExport; audit import_upsert/export; tests plantilla/import/export/permisos | `MtSt04ImportTemplateExport`, `MtSt04Export`, `MtSt04ImportService`, `ImportMtSt04Request`, rutas export/plantilla/import, modal import, `MtSt04ImportExportTest` | OK |
| 8 | 2026-10-09 | Task automatico | Feature T4 | Dashboard KPIs/charts + metrics JSON; comando `mt_st_04:sync-estados` + schedule 06:25 Bogotá; tests metrics/sync/NO APLICA OK | `MtSt04DashboardService`, dashboard Blade + ApexCharts JS, `SyncMtSt04EstadosCommand`, `bootstrap/app.php`, `MtSt04DashboardTest`, `MtSt04EstadoSyncTest` | OK |
| 9 | 2026-10-09 | Task automatico | Revisor | Review T1–T4: GO-with-nits; 50 tests OK; sin blockers; listo Documentador | `docs/reviews/FEAT-045.md` | OK |
| 10 | 2026-10-09 | Task automatico | Documentador | Doc técnica + usuario MT-ST-04; INDEX/ACCESS/PROCEDURES/ARCHITECTURE/PROJECT_CONTEXT/user README; TASKS listo cierre AgentSj | `docs/modules/mt_st_04.md`, `docs/user/mt_st_04.md`, `docs/INDEX.md`, `docs/ACCESS_CONTROL.md`, `docs/PROCEDURES.md`, `docs/ARCHITECTURE.md`, `docs/PROJECT_CONTEXT.md`, `docs/DOCUMENTATION.md`, `docs/user/README.md` | OK |
| 11 | 2026-10-09 | Checklist cierre | AgentSj | Checklist OK: 50 tests; smoke Dashboard+Matriz DevTools; TASKS → Completadas | review GO-with-nits; smoke `sjseguridad.test/.../mt-st-04` | OK |

## Checklist cierre AgentSj

- [x] Feature Brief cumplido (T1–T4)
- [x] `config/access.php` + AccessService + nav
- [x] Rutas en `routes/areas/gestion_humana.php`
- [x] `docs/modules/mt_st_04.md` + `docs/user/mt_st_04.md`
- [x] `docs/INDEX.md` / ACCESS / PROCEDURES
- [x] Revisor GO-with-nits (sin blockers)
- [x] Run log con fila de cierre
- [x] `php artisan test --filter=MtSt04` → 50 passed
- [x] Smoke DevTools: Dashboard KPIs/charts + Matriz DT 200
- [x] TASKS → Completadas

## Notas

- Shared-files: `config/access.php`, rutas GH / nav, audit, `bootstrap/app.php` (schedule).
- UI: calidad módulo desde el primer slice (referencia Formación / Acreditaciones / Cliente interno).
- Nits Revisor (no bloquean): filtro cargo dedicado opcional; `$fillable` con derivados; hex KPI alineado a Cursos.
- Permisos Spatie: asignación **manual** (`view.board…`, `mt_st_04.view`, `mt_st_04.edit`).
