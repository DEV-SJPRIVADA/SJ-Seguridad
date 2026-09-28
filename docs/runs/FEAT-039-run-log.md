# Run log — FEAT-039

> Registro persistente del flujo multi-agente. Crear al iniciar la feature como `docs/runs/FEAT-039-run-log.md`.  
> Plantilla: [`RUN_LOG.md`](../templates/RUN_LOG.md) — Ver [`docs/AGENT_WORKFLOW.md`](../AGENT_WORKFLOW.md#registro-de-ejecucion-run-log).

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-039 |
| Titulo | Export Apo SuperVigilancia (.xls) + Dashboard Acreditaciones |
| Modo | orquestado |
| Modulo | acreditaciones (GH) |
| Chat AgentSj | 2026-09-28 export apo + dashboard |
| Brief | `docs/briefs/FEAT-039.md` |
| Plan | `docs/briefs/FEAT-039-plan.md` |
| Inicio | 2026-09-28 |
| Cierre | 2026-09-28 |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-09-28 | Confirmaciones + seed parámetros Export Apo | AgentSj | Creó FEAT-039 en TASKS.md y run log; decisiones de negocio ya cerradas por el usuario | `docs/TASKS.md`, `docs/runs/FEAT-039-run-log.md` | OK |
| 2 | 2026-09-28 | Task automatico | Analista | Resumen cerrado + 0 preguntas; borrador brief FEAT-039 (Export Apo + Dashboard) | `docs/briefs/FEAT-039.md` | OK |
| 3 | 2026-09-28 | Task automatico | Arquitecto | Brief definitivo T1–T4; shared-files rutas GH; sin access.php; blocker NO | `docs/briefs/FEAT-039.md` | OK |
| 4 | 2026-09-28 | AgentSj | AgentSj | Plan orquestación FEAT-039-plan.md; lanza Feature T1 | `docs/briefs/FEAT-039-plan.md` | OK |
| 5 | 2026-09-28 | Task automatico | Feature T1 | Settings+runs migraciones; seeder; PATCH params Catálogo; config export_apo; tests 4 OK | migraciones, modelos, seeder, config, ruta, UI, test | OK |
| 6 | 2026-09-28 | Task automatico | Feature T2 | Tab export_apo solo edit; Candidate/RowResolver/Preview; DT+preview UI; tests 6 OK | AccessService, services, rutas, export-apo.blade, FormRequest, tests | OK |
| 7 | 2026-09-28 | Task automatico | Feature T3 | GenerateService + Writer Xls; seq diario; modal novedades; audit generate; tests 6+6 OK | XlsExport, GenerateService, FormRequest, ruta, UI modal, tests | OK |
| 8 | 2026-09-28 | Task automatico | Feature T4 | Dashboard KPIs + métricas JSON + últimas corridas; tests smoke; BoardAccess alineado T2; 34 OK | DashboardService, dashboard.blade, ruta metrics, tests, TASKS | OK |
| 9 | 2026-09-28 | Task automatico | Revisor | Aprobado con observaciones (Genero 1/2, KPI novedad, seeder); 34 tests OK | `docs/reviews/FEAT-039.md` | OK |
| 10 | 2026-09-28 | Task automatico | Documentador | Docs modules/user/ACCESS; quitó placeholders Dashboard/Export Apo | `docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md`, cursos, ACCESS | OK |
| 11 | 2026-09-28 | Checklist cierre | AgentSj | FEAT-039 → Completadas (Aprobado con observaciones) | `docs/TASKS.md` | OK |

## Cierre

| Campo | Valor |
| --- | --- |
| Cierre | 2026-09-28 |
| Validado | Si — Revisor: Aprobado con observaciones |
| Checklist | Brief OK; sin access.php; rutas area GH; docs modules+user; INDEX N/A; tests 34 passed; seeder params vía `AcreditacionExportApoSettingSeeder` / singleton |

## Notas

### Decisiones de negocio cerradas (usuario, chat 2026-09-28)

**Origen / filas**

- Base: Acreditados (cédula + CARGO APO) + Ficha activa; usuario selecciona filas a exportar.
- Multicasgos: 1 fila por par; si 2 cargos por vencer/vencidos → 2 filas.
- Universo: solo candidatos a **nueva acreditación / renovación**.
- Definición propuesta candidatos: estados `EN_PROCESO`, `POR_VENCER`, `DESACREDITADO` (excluir `ACREDITADO` fuera de ventana por vencer) — Analista/Arquitecto formalizan.

**Columnas Excel SuperVigilancia (headers exactos, 1 hoja, `.xls`)**

`Nit`, `RazonSocial`, `TipoDocumento`, `NoDocumento`, `Nombre1`, `Nombre2`, `Apellido1`, `Apellido2`, `FechaNacimiento`, `Genero`, `Cargo`, `Fechavinculacion`, `CodigoCurso`, `NitEscuela`, `Nro`, `TipoEstablecimiento`, `TelefonoR`, `DireccionR`, `DireccionP`, `Departamento`, `Ciudad`, `EducacionBM`, `EducacionS`, `Discapacidad`.

- `Cargo` = `cargo_acreditacion` (1,2,4,5,6).
- `TipoDocumento` = siempre `1` (fijo del catálogo parámetros).
- Identidad / fechas desde Ficha; Ficha incompleta → bloquea fila + notifica en preview.
- `CodigoCurso` desde catálogo tipos de curso (ej. R.ESCOLTA → 2201).
- Match curso: por cargo a exportar; sirve F **o** R de ese cargo; el de fecha expedición más reciente.
- `NitEscuela` = snapshot curso + validación catálogo escuelas; `Nro` = `numero_curso`.
- Fechas en Excel: `dd/mm/yyyy` (ej. real).
- **No** columna Valida/novedad en el `.xls` (solo preview UI).

**Vigencia / novedades**

- Política de vigencia (solo VIGENTE vs VIGENTE+ACTUALIZAR): **elige el usuario** al exportar.
- Filas con novedad pueden incluirse si el usuario confirma en modal Sí/No.
- Preview con Valida + motivo; export solo `acreditaciones.edit`.

**Archivo**

- Nombre: `APO9005767186{yyyymmdd}{seq}` — seq **3 dígitos** (`001`). Prefijo fijo alineado a Nit.
- Consecutivo solo en nombre de archivo; persistir runs diarios.

**Parámetros (seed inicial — Catálogo, una fila editable)**

| Clave | Valor |
| --- | --- |
| Nit | 9005767186 |
| RazonSocial | SJ SEGURIDAD PRIVADA LTDA |
| TipoDocumento | 1 |
| TipoEstablecimiento | Principal |
| TelefonoR | 3043413064 |
| DireccionR | MANZANA 8 CASA 27 |
| DireccionP | AV 4N26N 39 |
| Departamento | ValledelCauca |
| Ciudad | CALI |
| EducacionBM | 11 |
| EducacionS | Ninguna |
| Discapacidad | Ninguna |

**Dashboard**

- Incluir en el mismo FEAT (KPIs Acreditados + candidatos export / novedad + últimas corridas Export Apo). Visible con `acreditaciones.view`.

**Permisos**

- Preview/export/forzar novedades/editar parámetros: solo `acreditaciones.edit`.
- Dashboard lectura: `acreditaciones.view`.
- Sin permiso nuevo salvo que Arquitecto justifique (preferir no tocar `access.php`).

**Referencia archivo real:** `APO90057671862026092804.xls` (usuario Downloads) — Cargo 1/2/4, CodigoCurso 1201/2201/3201, misma cédula 2 filas (escolta+supervisor).

### Ejemplo match curso (usuario)

Supervisor con cursos supervisión + escolta: al exportar **ESCOLTA** validar F.ESCOLTA **o** R.ESCOLTA vigente (no mezclar con supervisión).
