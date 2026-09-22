# Run log — FEAT-035

> Registro persistente del flujo multi-agente.  
> Plantilla: [`RUN_LOG.md`](../templates/RUN_LOG.md) — Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-035 |
| Titulo | Tablero Selección GH (Dashboard, Ingreso, Examen ocupacional, Catálogos) |
| Modo | orquestado |
| Modulo | seleccion (Gestion humana) |
| Chat AgentSj | 2026-09-22 seleccion GH |
| Brief | `docs/briefs/FEAT-035.md` |
| Plan | `docs/briefs/FEAT-035-plan.md` |
| Inicio | 2026-09-22 |
| Cierre | 2026-09-22 |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-09-22 | `@agent-sj` Tablero Selección GH + respuestas 1–11 y dudas 1–6 | AgentSj | Creo FEAT-035 en TASKS.md y run log; lanzo Analista | `docs/TASKS.md`, `docs/runs/FEAT-035-run-log.md` | OK |
| 2 | 2026-09-22 | Task automatico | Analista | Documento 5 preguntas abiertas; pausa hasta respuesta usuario | `docs/briefs/FEAT-035-analyst.md` | Pausa |
| 3 | 2026-09-22 | Usuario responde 1–5 | AgentSj | Cierra preguntas en analyst.md; lanzo Arquitecto | `docs/briefs/FEAT-035-analyst.md` | OK |
| 4 | 2026-09-22 | Task automatico | Arquitecto | Feature Brief FINAL | `docs/briefs/FEAT-035.md` | OK |
| 5 | 2026-09-22 | AgentSj | Plan orquestacion T1–T5; pausa confirmacion Brief | `docs/briefs/FEAT-035-plan.md` | Pausa |
| 6 | 2026-09-22 | Usuario «confirmo» | AgentSj | Task Card T1; lanzo Feature T1 | `docs/briefs/FEAT-035-task-1.md` | OK |
| 7 | 2026-09-22 | Task automatico | Feature T1 | Permisos, Access/Audit, nav, shell rutas/vistas; 15 tests OK | `SeleccionAccessService`, rutas shell, `SeleccionBoardAccessTest` | OK |
| 8 | 2026-09-22 | Task automatico | Feature T2 | Catalogos whitelist 7 tipos + seed RH/civil/SOLICITUD + exclusión Ficha; tests Seleccion OK | `SeleccionCatalogService`, seed migration, CRUD catalogos, `SeleccionCatalogTest` | OK |
| 9 | 2026-09-22 | AgentSj | Task Card T3; lanzo Feature T3 Ingreso | `docs/briefs/FEAT-035-task-3.md` | OK |
| 10 | 2026-09-22 | Task automatico | Feature T3 | Ingreso: migracion/modelo, CRUD, DT server-side, export, duplicado B, audit; 31 tests OK | `seleccion_ingresos`, `SeleccionIngreso*`, `SeleccionIngresoTest` | OK |
| 11 | 2026-09-22 | AgentSj | Task Card T4; lanzo Feature T4 Examen | `docs/briefs/FEAT-035-task-4.md` | OK |
| 12 | 2026-09-22 | Task automatico | Feature T4 | Examen: migracion/modelo, CRUD, DT, export, duplicado B, audit, refs catalog; 39 tests Seleccion OK | `seleccion_examenes_ocupacionales`, `SeleccionExamen*`, `SeleccionExamenTest` | OK |
| 13 | 2026-09-22 | AgentSj | Task Card T5; lanzo Feature T5 Dashboard | `docs/briefs/FEAT-035-task-5.md` | OK |
| 14 | 2026-09-22 | Task automatico | Feature T5 | Dashboard KPIs + ApexCharts + metrics JSON + filtros AJAX; 41 tests Seleccion OK | `SeleccionDashboardService`, `seleccion-dashboard-charts.js`, `SeleccionDashboardTest` | OK |
| 15 | 2026-09-22 | Task automatico | Revisor | Aprobado con observaciones; sin blockers | `docs/reviews/FEAT-035.md` | OK |
| 16 | 2026-09-22 | Task automatico | Documentador | Doc tecnica + usuario + INDEX/ACCESS/ARCHITECTURE | `docs/modules/seleccion.md`, `docs/user/seleccion.md` | OK |
| 17 | 2026-09-22 | Checklist cierre | AgentSj | Movio FEAT-035 a Completadas | `docs/TASKS.md` | OK |

### Estados validos

| Estado | Significado |
| --- | --- |
| OK | Paso completado |
| Pausa | Esperando respuesta del usuario |
| Blocker | Revisor o dependencia detiene el flujo |
| Skip | No aplica |
| Reintento | Correccion tras review |

## Decisiones de negocio (cerradas con usuario)

### Alcance UI
- Tablero GH **Selección** (`seleccion`): pestanas Dashboard, Ingreso, Examen ocupacional, Catálogos (si `seleccion.edit`).
- CRUD completo + filtros + DataTables server-side + export Excel.
- Dashboard: KPIs + graficos.
- Permisos: `view.board.gestion_humana.seleccion`, `seleccion.view`, `seleccion.edit` (edit incluye Catálogos).

### Datos
- Dos tablas separadas (Ingreso / Examen), independientes de Ficha/requisicion.
- Varios registros por cedula permitidos; al crear/editar **notificar** si la cedula ya esta registrada.
- Borrado duro.
- FECHA DE ARL = date. SOLICITUD = estado de catalogo.

### Catalogos / selects
- Ciudad, cargo, EPS, AFP: `payroll_catalog_items`.
- Cliente: `commercial_clients`.
- Tipo dotacion: `requisition_uniforms`.
- Responsable: reclutadores activos (`requisitions.selection_officer`).
- RH, estado civil, estados SOLICITUD: nuevos `catalog_type` en `payroll_catalog_items`; editables desde Selección Catálogos.
- Catálogos Selección tambien permiten editar city/position/eps/afp (y los nuevos).
- Servicio/sector y Jefe OPE: texto libre v1.
- Estados SOLICITUD (un solo DXEMO): CONTRATADO, DXENT OPERACIONES, DXENT SELECCIÓN, DESISTE DEL PROCESO, DXPSICOFÍSICO, DxANT, DxEMO Y PSICOFÍSICO, EN PROCESO, DXPOLIGRAFÍA, DXEMO, EN RESERVA.

### Campos Ingreso
CEDULA, APELLIDOS Y NOMBRE, CORREO, TELEFONO, CIUDAD, CARGO, CLIENTE, TALLA CAMISA, TALLA PANTALÓN, TALLA ZAPATOS, TIPO DOTACION, FECHA DE INGRESO, RH, REEMPLAZA A, RESPONSABLE, JEFE OPE ASIGNADO.

### Campos Examen ocupacional
CEDULA, APELLIDOS Y NOMBRES, CARGO, SERVICIO/SECTOR, CLIENTE, EPS, PENSION (AFP), FECHA NACIMIENTO, CIUDAD, DIRECCION, CORREO, CELULAR, ESTADO CIVIL, FECHA DE ARL, SOLICITUD, RESPONSABLE.

## Notas

- shared-files previstos: `config/access.php`, rutas GH, nav, `config/employee_ficha.php` (catalog_types), posible CSS.
- Patron de referencia: tablero Cursos (FEAT-032).
