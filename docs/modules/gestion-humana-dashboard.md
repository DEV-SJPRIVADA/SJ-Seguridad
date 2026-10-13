# Modulo Dashboard de área Gestión Humana

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/gestion-humana-dashboard.md`.
> Feature: FEAT-046 (revisado 2026-10-13, aprobado con observaciones).

## Conceptos / áreas (gráfico Personal por concepto)

Mapa editable cargo → concepto (tablas `gh_area_dashboard_conceptos` + `gh_area_dashboard_concepto_cargos`).
Modal UI (`gh-area-conceptos-modal`): chrome alineado a Generar cartas (header con icono/badge, pasos 1–3, chips de cargos, bloque ámbar de sin asignar, footer de acciones).

- UI: botón capas en filtros del dashboard → modal CRUD (mismo permiso `view.board.gestion_humana.dashboard`).
- Seed inicial: `php artisan db:seed --class=GhAreaDashboardConceptoSeeder`.
- Cargos sin mapa → bucket **OTROS**. Un cargo solo en un concepto (`position_key` unique, UPPER/TRIM).
- Chart JSON: `charts.personal_por_concepto` (orden por volumen).

## Objetivo

Sustituir el placeholder generico del tablero **Dashboard** de Gestion Humana por un **informe mensual** con 10 KPIs y 7 graficos (ApexCharts), filtrable por ano / mes / ciudad / cargo / cliente. Mutaciones limitadas al catalogo conceptos↔cargos; sin export Excel en V1.

## Alcance actual

- Tablero sidebar **Dashboard** (`board` key `dashboard`, hogar `gestion_humana`).
- Permiso existente: `view.board.gestion_humana.dashboard`. Bypass runtime: `manage.users`.
- **Sin** claves nuevas en `config/access.php`. **Sin** AccessService nuevo.
- Migraciones: `gh_area_dashboard_conceptos` + `gh_area_dashboard_concepto_cargos` (mapa cargo→concepto editable).
- Redirect en `routes/web.php` cuando `module=gestion_humana` + `board=dashboard` → `gestion-humana.dashboard` (espejo Comercial/Compras).
- Navegacion: `NavigationResolver` apunta el tablero Dashboard a `gestion-humana.dashboard` (URL + active + area GH activa), igual que Comercial/Compras; sin esto la franja de tableros no aparece en la vista dedicada.
- Fuentes de datos (lectura): Ficha empleados, MT-ST-04, Acreditaciones, Cliente interno, Formacion, Archivo (consultas).
- Filtros parciales V1: Cliente interno, Formacion y Archivo **solo** respetan ano/mes; ciudad/cargo/cliente se ignoran en esas fuentes (`meta.filters_parciales_nota`).
- Fuera V1: export Excel/PDF, KPI At-Guarda, snapshot fisico de headcount, filtros dim ficha en CI/Formacion/Archivo, subnav de pestanas.

## Rutas

Archivo de area: `routes/areas/gestion_humana.php`  
Prefijo: `/gestion-humana` · nombres `gestion-humana.dashboard` / `.metrics`  
Middleware grupo: `auth`, `active` (via `web.php`) + `password.changed`.

| Metodo | URI | Nombre | Middleware / permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/dashboard` | `gestion-humana.dashboard` | `view.board.gestion_humana.dashboard` ∨ `manage.users` |
| GET | `/gestion-humana/dashboard/metrics` | `gestion-humana.dashboard.metrics` | idem |
| GET/POST/PATCH/DELETE | `/gestion-humana/dashboard/conceptos…` | `gestion-humana.dashboard.conceptos.*` | idem (CRUD conceptos + asignar cargos) |

**Redirect (shared):** en el closure `dashboard` de `routes/web.php`:

```php
if ($selectedModuleKey === 'gestion_humana' && $selectedBoardKey === 'dashboard') {
    return redirect()->route('gestion-humana.dashboard');
}
```

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.dashboard` | Ver pagina del informe + JSON metrics (permiso de tablero ya existente) |
| `manage.users` | Bypass admin (mismo patron que otros tableros GH) |

**Sin permiso nuevo.** Gate explicito en el controller (`authorizeView`); no hay `*AccessService` dedicado.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\GestionHumanaAreaDashboardController` | `index` (Blade + `initialPayload`) y `metrics` (JSON); normaliza query params; auth |

Sin Form Request en V1: normalizacion ano/mes (defaults TZ `America/Bogota`) en el servicio.

## Vistas

| Vista | Descripcion |
| --- | --- |
| `areas/gestion_humana/dashboard.blade.php` | Informe mensual: filtros searchable-select, 10 KPI cards, 7 charts, Alpine refresh via `metricsUrl` |

Patron UI: chrome Formacion / Cursos (`formacion-dashboard-page` / `cursos-dashboard-page` + `gh-area-dashboard-page`). Titulo visible: **«Dashboard de Gestión Humana – Informe Mensual»**. Icon-btn canonico para limpiar filtros. Sin Select2 / `excelHtml5` / familia `*__icon-btn` propia.

## Modelos y tablas

Sin tablas propias. Lectura agregada:

| Modelo | Tabla | Uso en el dashboard |
| --- | --- | --- |
| `EmployeeFichaProfile` | `employee_ficha_profiles` | Activos EOM, ingresos, desvinculaciones, operativo, evolucion, cargos, dims ciudad/cargo/cliente |
| `MtSt04Registro` | `mt_st_04_registros` | Card VIGENTE + chart estados examen 1 (join ficha para dims) |
| `AcreditacionAcreditado` | `acreditacion_acreditados` | Card ACREDITADO + chart 3 estados (join ficha) |
| `ClienteInternoSolicitud` | `cliente_interno_solicitudes` | Count `anio`+`mes` |
| `FormacionRegistro` / `FormacionDashboardService` | `formacion_registros` | KPI + serie = personas del ciclo (`buildCicloMetrics`) |
| `EmployeeArchiveConsultation` | `employee_archive_consultations` | Count `created_at` ano/mes + pie motivos JSON |

## Servicios / jobs / mail (si aplica)

| Clase | Responsabilidad |
| --- | --- |
| `App\Services\GestionHumana\GestionHumanaAreaDashboardService` | `metrics()`, `filterSelectOptions()`, EOM, rotacion, lista operativa, charts |

Constante publica: `OPERATIVE_POSITIONS` (lista canonica). Helper SQL cliente: `displayClientSql()` = `COALESCE(NULLIF(TRIM(work_center_name), ''), cost_center_name)`.

**Sin** audit obligatorio (solo lectura). **Sin** jobs / mail.

## Reglas de negocio

### Filtros

| Filtro | Semantica |
| --- | --- |
| Ano / mes | Obligatorios en practica; default calendario actual (`America/Bogota`) si invalidos |
| Ciudad | Igualdad sobre `EmployeeFichaProfile::displayCitySql()` |
| Cargo | Igualdad sobre `position_name` |
| Cliente | Igualdad sobre `displayClientSql()` (work_center → cost_center) |
| Vacio | «Todos» |

CI / Formacion / Archivo: **ignoran** ciudad/cargo/cliente; siguen ano/mes. Nota en UI/payload: `meta.filters_parciales_nota`.

### Reconstruccion EOM (activos al cierre del mes)

TZ `America/Bogota`. Persona activa al EOM de `Y-M`:

```text
hire_date IS NOT NULL
AND hire_date <= eom(Y, M)
AND (termination_date IS NULL OR termination_date > eom(Y, M))
```

- KPI **Personal Activo** = stock EOM mes seleccionado (+ filtros ficha).
- **Ingresos** = `hire_date` en ano/mes (+ filtros).
- **Desvinculaciones** = `termination_date` en ano/mes (+ filtros).
- Stock historico ≠ `employment_status` live si hay datos inconsistentes.

### Rotacion mensual %

```text
desvinculaciones_mes / ((activos_mes_actual + activos_mes_anterior) / 2) × 100
```

Si denominador ≤ 0: `rotacion_mensual_pct = 0` y `rotacion_display = "—"`.

### Personal operativo

Match `UPPER(TRIM(position_name))` contra lista canonica (trim + case-insensitive). Typos en ficha quedan fuera (esperado). KPI muestra count y `%` del total activos EOM.

Lista: `ESCOLTA`, `ESCOLTA MENSUAL`, `ESCOLTA SJ`, `GUARDA`, `GUARDA SJ`, `GUARDAS MOTORIZADOS`, `OPERADOR`, `OPERADOR SJ`, `Supervisor de puesto`, `SUPERVISOR SJ`, `SUPERVISORES`, `SUPERVISORES MENSUAL`.

### Cards cruzadas

| KPI | Definicion |
| --- | --- |
| Acreditaciones | Estado `ACREDITADO` (+ join ficha dims) |
| Psicofisicos | `estado_1 = VIGENTE` MT-ST-04 (+ join ficha) |
| Cliente interno | Solicitudes `anio`+`mes` |
| Formacion | Personas ciclo (`buildCicloMetrics` → `personas`) año+mes |
| Archivo | Ítems del historial (`employee_archive_consultation_items`): `whereYear(created_at)` + `month_number` (cédulas/documentos, no cabeceras) |

### Graficos (7)

| Key payload | Tipo | Serie |
| --- | --- | --- |
| `evolucion_personal` | Linea | Activos EOM por mes (+ dims ficha); año vigente hasta mes actual, año pasado Ene–Dic |
| `ingresos_vs_desvinculaciones` | Barras agrupadas | Ingresos vs bajas por mes (mismo tope que evolución personal) |
| `evolucion_formacion` | Linea | Personas del ciclo por mes (hasta mes actual si el año es el vigente; año pasado = Ene–Dic) |
| `personal_por_cargos` | Barras | Top 15 cargos entre activos EOM del mes |
| `estado_psicofisicos` | Barras/donut | VIGENTE / VENCERA / VENCIDO |
| `estado_acreditacion` | Barras/donut | ACREDITADO / EN PROCESO / POR VENCER (sin DESACREDITADO V1) |
| `consultas_archivo` | Pie | Misma base que KPI Archivo (ítems año+`month_number`); cada cédula suma 1 a cada motivo de su consulta (`consultation_types`). Labels desde `config('employee_ficha.archive_consultation_types')` |

### Payload metrics JSON (contrato)

Query: `anio`, `mes`, `ciudad`, `cargo`, `cliente`.

```json
{
  "filters": { "anio": "2026", "mes": "10", "ciudad": "", "cargo": "", "cliente": "" },
  "kpis": {
    "personal_activo": 0,
    "ingresos_mes": 0,
    "desvinculaciones_mes": 0,
    "rotacion_mensual_pct": 0.0,
    "rotacion_display": "0.0%",
    "personal_operativo": 0,
    "personal_operativo_pct": 0.0,
    "acreditaciones": 0,
    "psicofisicos": 0,
    "cliente_interno": 0,
    "formacion": 0,
    "archivo": 0
  },
  "charts": {
    "evolucion_personal": { "labels": [], "data": [] },
    "ingresos_vs_desvinculaciones": { "labels": [], "ingresos": [], "desvinculaciones": [] },
    "evolucion_formacion": { "labels": [], "data": [] },
    "personal_por_cargos": { "labels": [], "data": [] },
    "estado_psicofisicos": { "labels": [], "data": [] },
    "estado_acreditacion": { "labels": [], "data": [] },
    "consultas_archivo": { "labels": [], "data": [] }
  },
  "meta": {
    "activos_mes_actual": 0,
    "activos_mes_anterior": 0,
    "eom_date": "2026-10-31",
    "filters_parciales_nota": "Cliente interno, Formación y Archivo solo filtran por año/mes."
  }
}
```

## JavaScript / assets (si aplica)

| Asset | Rol |
| --- | --- |
| Alpine en la Blade | `initialPayload`, `metricsUrl`, refresh al cambiar filtros / limpiar |
| `resources/js/gh-area-dashboard-charts.js` | Render/update ApexCharts (7 instancias) |
| `resources/js/charts/apex-defaults.js` | Colores / `sharedChart` |
| `vite.config.js` | Entry `gh-area-dashboard-charts.js` |

Tras cambios Vite/CSS: `npm run build`.

## Export Excel (si aplica)

No aplica en V1.

## Validacion local

1. Login con `view.board.gestion_humana.dashboard` (o `manage.users`).
2. Sidebar GH → **Dashboard** → redirect + titulo del informe.
3. Cambiar ano/mes/ciudad/cargo/cliente; KPIs/graficos refrescan via metrics.
4. Sin permiso → 403 en pagina y metrics.
5. `php artisan test --compact --filter=GestionHumanaAreaDashboard`
6. Smoke Chrome DevTools MCP (pagina + metrics 200 + consola limpia) cuando haya sesion local.

## Riesgos y pendientes

| Riesgo / pendiente | Notas |
| --- | --- |
| Perf EOM ×12 (+ ingresos/bajas) | Aceptable V1; T2 solo si latencia medible en prod (observacion Revisor) |
| Filtros parciales CI/Formacion/Archivo | Documentado en UI/`meta`; no inventar joins fragiles |
| Ficha sin `hire_date` | Excluida del stock EOM |
| Opciones cargo con espacios | Observacion Revisor: distinct/order podria trim; no bloqueante |
| Smoke DevTools | Pendiente login local al cierre T1; no bloquea documentacion |
| Acentos KPI border-left | Patron Formacion; opcional tokens `--brand-*` |

## Referencias

- Feature Brief: [`docs/briefs/FEAT-046.md`](../briefs/FEAT-046.md)
- Review: [`docs/reviews/FEAT-046.md`](../reviews/FEAT-046.md)
- Run log: [`docs/runs/FEAT-046-run-log.md`](../runs/FEAT-046-run-log.md)
- Doc usuario: [`docs/user/gestion-humana-dashboard.md`](../user/gestion-humana-dashboard.md)
- Branding / UI: [`docs/modules/branding.md`](branding.md)
- Modulos fuente: Ficha, MT-ST-04, Acreditaciones, Cliente interno, Formacion, Archivo
