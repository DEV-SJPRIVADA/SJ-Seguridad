# Feature Brief — FEAT-046

> Brief final del Arquitecto (2026-10-13). Consolida decisiones de negocio del usuario (chat 2026-10-13 / run log) + exploración de dashboards existentes (Formación, Comercial, MT-ST-04, Cliente Interno, Acreditaciones). Analista **Skip**. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-046 |
| Modulo / area | Gestión humana — tablero **Dashboard de área** (`dashboard`) |
| Titulo | Dashboard de área Gestión Humana (informe mensual KPI + gráficos) |
| Solicitante | Usuario / AgentSj (chat 2026-10-13 dashboard área GH) |
| Fecha | 2026-10-13 |
| Run log | [`docs/runs/FEAT-046-run-log.md`](../runs/FEAT-046-run-log.md) |
| Reemplaza | Placeholder `dashboard?module=gestion_humana&board=dashboard` |

## Objetivo

Sustituir el placeholder genérico del tablero **Dashboard** de Gestión Humana por un **informe mensual** con 10 KPIs y 7 gráficos, filtrable por año / mes / ciudad / cargo / cliente, para que GH vea de un vistazo personal, rotación, acreditaciones, psicofísicos, cliente interno, formación y archivo.

Operadores con `view.board.gestion_humana.dashboard` (o bypass `manage.users`) acceden a la página y al JSON de métricas. **Sin** permisos nuevos.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | KPI Cliente Interno | Count de solicitudes del mes (`cliente_interno_solicitudes` / reutilizar lógica de conteo de `ClienteInternoDashboardService`). **Reemplaza** At-Guarda. |
| 2 | Personal Operativo | Cargos **exactos** (match `trim` + case-insensitive) — lista canónica abajo. |
| 3 | Rotación Mensual % | `desvinculaciones_mes / ((activos_mes_actual + activos_mes_anterior) / 2) × 100`. Si denominador = 0 → `0` o `—` (UI). |
| 4 | Filtro cliente | `COALESCE(NULLIF(TRIM(work_center_name), ''), cost_center_name)` (work_center primero). |
| 5 | Card Psicofísicos | Count `estado_1 = VIGENTE` (MT-ST-04). |
| 6 | Card Acreditaciones | Count estado `ACREDITADO`. |
| 7 | Pie Archivo | Motivos `consultation_types` del historial de consultas (`employee_archive_consultations`). |
| 8 | Permiso | **Solo** `view.board.gestion_humana.dashboard`. **NO** crear claves nuevas en `config/access.php`. |
| 9 | Título UI | «Dashboard de Gestión Humana – Informe Mensual». |
| 10 | Filtros globales | Año, Mes, Ciudad (`displayCitySql` Ficha), Cargo (`position_name`), Cliente (regla 4). |

### Lista canónica — Personal Operativo

Match exacto tras `trim` + comparación case-insensitive (`mb_strtoupper` / equivalente):

`ESCOLTA`, `ESCOLTA MENSUAL`, `ESCOLTA SJ`, `GUARDA`, `GUARDA SJ`, `GUARDAS MOTORIZADOS`, `OPERADOR`, `OPERADOR SJ`, `Supervisor de puesto`, `SUPERVISOR SJ`, `SUPERVISORES`, `SUPERVISORES MENSUAL`.

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Ownership | Area GH; rutas en `routes/areas/gestion_humana.php`; redirect en `routes/web.php` (como Comercial/Compras) | Reemplaza placeholder del board `dashboard` |
| Controller | `App\Http\Controllers\GestionHumana\GestionHumanaAreaDashboardController` | Nombre distinto de dashboards de subtablero (Formación, MT-ST-04, etc.) |
| Service | `App\Services\GestionHumana\GestionHumanaAreaDashboardService` | Agrega KPIs/series; reutiliza queries/helpers de servicios existentes **sin** acoplar UI de otros tableros |
| Auth | `abort_unless($user->can('view.board.gestion_humana.dashboard') \|\| $user->can('manage.users'), 403)` (o helper thin en el controller). **Sin** AccessService nuevo ni keys en `access.php` | Decisión 8 |
| Persistencia | **Sin** migraciones / tablas nuevas | Solo lectura agregada sobre fuentes existentes |
| Repository | **No** | Convención proyecto |
| UI patrón | Clonar chrome **Formación** (Alpine `metricsUrl` + refresh) + layout KPI/cards de **MT-ST-04 / Comercial**; searchable-select; icon-btn canónicos | `module-ui-quality` + branding |
| Charts | ApexCharts vía Vite; entry nuevo `resources/js/gh-area-dashboard-charts.js` + defaults `apex-defaults.js` | FEAT-010 / dashboards GH |
| Ficha dims | Ciudad = `EmployeeFichaProfile::displayCitySql()`; cargo = `position_name`; cliente = expresión COALESCE work→cost | Decisiones 4 y 10 |
| Activos históricos | **Reconstrucción EOM** por fechas (`hire_date` / `termination_date`), no solo `employment_status` actual | Necesario para rotación e «Evolución de Personal» en meses pasados |
| Cliente Interno KPI | Count `where anio + mes` (mismo criterio que dashboard CI); filtros ciudad/cargo/cliente **no aplican** a esta fuente en V1 | Tabla sin dims de ficha |
| Formación / Archivo | Count por `anio`/`mes` (Formación) y `created_at` año/mes (Archivo); dims ciudad/cargo/cliente **no aplican** en V1 salvo join futuro | `formacion_registros` y consultas archivo sin esas columnas |
| Psicofísicos / Acreditaciones | Contar sobre registros MT-ST-04 / Acreditados con join a ficha para aplicar filtros globales cuando existan | Alineado a `MtSt04DashboardService` / `AcreditacionDashboardService` |
| Export Excel | **No** en V1 | Fuera de alcance |
| Audit | **No** obligatorio (solo lectura agregada) | No mutaciones |
| Migrate | N/A. **Prohibido** `migrate:fresh` / wipe | Protección de datos |

### Reconstrucción EOM de activos (formal)

Persona **activa al cierre** del mes `Y-M` (último día del mes, TZ `America/Bogota`):

```text
hire_date IS NOT NULL
AND hire_date <= eom(Y, M)
AND (termination_date IS NULL OR termination_date > eom(Y, M))
```

- `activos_mes_actual` = count con filtros globales sobre ese universo EOM del mes seleccionado.
- `activos_mes_anterior` = mismo para el mes calendario anterior (si enero → diciembre año −1).
- KPI **Personal Activo** = `activos_mes_actual`.
- **Ingresos del Mes** = `hire_date` en año/mes seleccionado (+ filtros).
- **Desvinculaciones** = `termination_date` en año/mes seleccionado (+ filtros).
- **Rotación** = fórmula decisión 3; denominador = promedio de los dos stocks EOM.

### Diagrama de flujo

```text
[Sidebar GH: Dashboard] --board--> web.php redirect
        │
        ▼
 GET /gestion-humana/dashboard
        │  authorize view.board.gestion_humana.dashboard
        ▼
 Blade + Alpine (filtros searchable-select)
        │  change → fetch metrics JSON
        ▼
 GET /gestion-humana/dashboard/metrics
        │
        ▼
 GestionHumanaAreaDashboardService::metrics(filters)
        ├── Ficha (activos EOM, ingresos, bajas, operativo, cargos, evolución)
        ├── MT-ST-04 (VIGENTE + chart estados examen1)
        ├── Acreditaciones (ACREDITADO + chart 3 estados)
        ├── Cliente interno (solicitudes mes; solo anio/mes)
        ├── Formación (registros anio/mes; solo anio/mes)
        └── Archivo (consultas mes + pie motivos; solo anio/mes)
```

## Alcance

### Incluye

- Redirect en `routes/web.php` cuando `module=gestion_humana` + `board=dashboard` → ruta nombrada del dashboard de área (espejo Comercial).
- Rutas GET página + GET metrics JSON en `routes/areas/gestion_humana.php`.
- `GestionHumanaAreaDashboardController` + `GestionHumanaAreaDashboardService`.
- Vista Blade `resources/views/areas/gestion_humana/dashboard.blade.php` (o `…/area_dashboard/index.blade.php` si se prefiere carpeta; preferencia Arquitecto: **`dashboard.blade.php` en raíz del área**).
- Alpine: carga inicial + refresh al cambiar filtros (`metricsUrl`).
- Entry Vite ApexCharts + registro en `vite.config.js`.
- 10 KPIs + 7 gráficos según § Reglas.
- Opciones de filtros (años, meses, ciudades, cargos, clientes) derivadas de Ficha con la misma semántica de display.
- Tests Feature: acceso 403 sin permiso; 200 con permiso; payload JSON con claves esperadas; rotación denom=0; match cargos operativos case-insensitive; reconstrucción EOM básica.
- Docs: `docs/modules/` (nuevo o sección en doc de área GH / `branding` cross-link) + `docs/user/` equivalente — Documentador al cierre. Actualizar `docs/INDEX.md` si aplica.
- `npm run build` tras Vite/CSS.
- Smoke UI Chrome DevTools MCP tras tests.

### Fuera de alcance

- Claves nuevas o renombres en `config/access.php`.
- KPI At-Guarda / cualquier métrica no listada.
- Filtros ciudad/cargo/cliente aplicados a Cliente Interno, Formación o Archivo (V1: solo año/mes en esas fuentes).
- Snapshot físico de headcount mensual (tabla histórica); V1 = reconstrucción por fechas.
- Export Excel / PDF / PowerPoint del informe.
- Edición de datos desde el dashboard.
- Subnav de pestañas (el board Dashboard de área no es un subtablero con tabs).
- Select2 / `excelHtml5` / Repository / Chart.js / ECharts.
- Cambiar dashboards de Formación, MT-ST-04, Acreditaciones, Cliente Interno, Cursos, Comercial (solo reutilizar lógica de conteo donde convenga).

## Reglas de negocio

### Filtros

1. **Año** (obligatorio en práctica; default año calendario actual o con datos, patrón Formación).
2. **Mes** (1–12; default mes actual). El informe es **mensual**: mes siempre aplica a KPIs de flujo (ingresos, desvinculaciones, CI, formación, archivo) y a stocks EOM del mes.
3. **Ciudad** = igualdad sobre `EmployeeFichaProfile::displayCitySql()` (`COALESCE(NULLIF(work_city_name,''), residence_city_name)`).
4. **Cargo** = igualdad sobre `position_name` (valor exacto de opción; opciones distinct trim).
5. **Cliente** = igualdad sobre `COALESCE(NULLIF(TRIM(work_center_name),''), cost_center_name)`.
6. Filtros vacíos = «Todos».
7. Fuentes sin dims de ficha (CI, Formación, Archivo): **ignoran** ciudad/cargo/cliente; siguen respetando año/mes. Documentar en UI con nota breve o tooltip si el Feature lo estima (no bloquear).

### KPIs (10)

| # | KPI | Definición |
| --- | --- | --- |
| 1 | Personal Activo | Stock EOM mes seleccionado (+ filtros ficha) |
| 2 | Ingresos del Mes | Count `hire_date` en año/mes (+ filtros) |
| 3 | Desvinculaciones | Count `termination_date` en año/mes (+ filtros) |
| 4 | Rotación Mensual % | Fórmula decisión 3; 1 decimal recomendado |
| 5 | Personal Operativo | Count activos EOM cuyo `position_name` ∈ lista canónica; mostrar también **% del total** activos EOM |
| 6 | Acreditaciones | Count filas Acreditados con estado `ACREDITADO` (+ join ficha filtros si aplica) |
| 7 | Psicofísicos | Count MT-ST-04 con `estado_1 = VIGENTE` (+ join ficha filtros) |
| 8 | Cliente Interno | Count solicitudes `anio`+`mes` (filtros ficha N/A) |
| 9 | Formación | Count `formacion_registros` `anio`+`mes` (filtros ficha N/A) |
| 10 | Archivo | Count `employee_archive_consultations` con `created_at` en año/mes (filtros ficha N/A) |

### Gráficos (7)

| # | Gráfico | Tipo | Serie |
| --- | --- | --- | --- |
| 1 | Evolución de Personal | Línea | Activos EOM de los **12 meses** del año filtrado (aplicar ciudad/cargo/cliente) |
| 2 | Ingreso Vs Desvinculaciones | Barras agrupadas | Por mes del año: ingresos vs desvinculaciones |
| 3 | Evolución Formación | Línea | Registros formación por mes del año |
| 4 | Personal por cargos | Barras | Top N (p. ej. 12–15) cargos entre activos EOM del mes |
| 5 | Estado Psicofísicos | Barras o donut | `VIGENTE` / `VENCERA` / `VENCIDO` (`estado_1`) |
| 6 | Estado Acreditación | Barras o donut | `ACREDITADO` / `EN PROCESO` / `POR VENCER` (sin DESACREDITADO en este chart V1) |
| 7 | Consultas Archivo | Pie | Motivos: expandir JSON `consultation_types`; labels desde `config('employee_ficha.archive_consultation_types')` |

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.dashboard` (existente) | Asignación actual / bypass `manage.users` | Ver página + JSON metrics |

**Sin permiso nuevo. Sin cambios a `config/access.php`.** El board ya existe en navegación; solo se cablea la pantalla real.

## Rutas

| Metodo | URI | Nombre | Archivo de rutas | Auth |
| --- | --- | --- | --- | --- |
| GET | `/gestion-humana/dashboard` | `gestion-humana.dashboard` | `routes/areas/gestion_humana.php` | `view.board.gestion_humana.dashboard` ∨ `manage.users` |
| GET | `/gestion-humana/dashboard/metrics` | `gestion-humana.dashboard.metrics` | `routes/areas/gestion_humana.php` | idem |

**Redirect (shared):** en `routes/web.php`, dentro del closure `dashboard`, añadir (junto a Comercial/Compras):

```php
if ($selectedModuleKey === 'gestion_humana' && $selectedBoardKey === 'dashboard') {
    return redirect()->route('gestion-humana.dashboard');
}
```

No nuevas rutas públicas fuera del grupo `auth`+`active`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| — | sin migración | Lectura: `employee_ficha_profiles`, `mt_st_04_registros`, acreditados, `cliente_interno_solicitudes`, `formacion_registros`, `employee_archive_consultations` |

## Capas a implementar

- [ ] Migracion(es) — **no**
- [ ] Modelo(s) — **no** (usar existentes)
- [x] Controlador(es) — `GestionHumanaAreaDashboardController` (`__invoke`/`index` + `metrics`)
- [x] Servicio(s) — `GestionHumanaAreaDashboardService`
- [ ] Form Request(s) — opcional thin para validar año/mes; o normalización en servicio (aceptable V1)
- [x] Vista(s) Blade — dashboard área GH
- [x] JavaScript — Alpine page + `gh-area-dashboard-charts.js` + entry Vite
- [ ] Export Excel — **no**
- [x] Tests — Feature acceso + metrics + reglas rotación/operativo/EOM

## Componentes reutilizables

- `<x-searchable-select>` para Año, Mes, Ciudad, Cargo, Cliente.
- `.req-manage-filters__icon-btn` (limpiar filtros).
- ApexCharts + `resources/js/charts/apex-defaults.js`.
- Patrón Alpine Formación: `initialPayload` + `metricsUrl` + `onSelectChange` / `clearFilters`.
- Helpers existentes: `EmployeeFichaProfile::displayCitySql()`, constantes estado MT-ST-04 / Acreditaciones, labels `archive_consultation_types`.
- Reutilizar **conteo** Cliente Interno / Formación donde sea trivial (no obligar a inyectar servicios enteros si ensucia el contrato; preferir queries claras en el AreaDashboardService).
- Tokens branding / clases `page-section`, `panel`, `app-container`, cards KPI al estilo Formación/MT-ST-04.

### Vista referencia a clonar

| Prioridad | Vista | Qué copiar |
| --- | --- | --- |
| 1 | `resources/views/areas/gestion_humana/formacion/dashboard.blade.php` | Shell filtros Alpine + metrics refresh + cards/charts grid |
| 2 | `resources/views/areas/gestion_humana/mt_st_04/dashboard.blade.php` | Densidad KPI + charts Apex |
| 3 | `resources/views/areas/comercial/dashboard.blade.php` | Redirect board + título informe (adaptar look a chrome GH moderno; **no** reintroducir estilos inline legacy si Formación ya los supera) |

Título visible: **«Dashboard de Gestión Humana – Informe Mensual»**.

## Payload metrics JSON (contrato)

Query params (todos opcionales salvo defaults de servicio): `anio`, `mes`, `ciudad`, `cargo`, `cliente`.

```json
{
  "filters": {
    "anio": "2026",
    "mes": "10",
    "ciudad": "",
    "cargo": "",
    "cliente": ""
  },
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
    "evolucion_personal": { "labels": ["Ene", "…", "Dic"], "data": [] },
    "ingresos_vs_desvinculaciones": {
      "labels": ["Ene", "…", "Dic"],
      "ingresos": [],
      "desvinculaciones": []
    },
    "evolucion_formacion": { "labels": ["Ene", "…", "Dic"], "data": [] },
    "personal_por_cargos": { "labels": [], "data": [] },
    "estado_psicofisicos": {
      "labels": ["VIGENTE", "VENCERA", "VENCIDO"],
      "data": []
    },
    "estado_acreditacion": {
      "labels": ["ACREDITADO", "EN PROCESO", "POR VENCER"],
      "data": []
    },
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

Si `denom` rotación = 0: `rotacion_mensual_pct` = `0` y `rotacion_display` = `"—"` o `"0%"` (Feature elige uno y lo fija en tests; preferencia Arquitecto: **`"—"`** cuando denom=0).

La página Blade recibe `initialPayload`, `metricsUrl`, opciones de select (`yearOptions`, `monthOptions`, `cityOptions`, `positionOptions`, `clientOptions`).

## Documentacion a actualizar

- [ ] `docs/modules/gestion-humana-dashboard.md` (nuevo recomendado) **o** sección en doc de área si Documentador unifica
- [ ] `docs/user/gestion-humana-dashboard.md` (Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios)
- [ ] `docs/INDEX.md` — enlace al módulo
- [ ] `docs/ARCHITECTURE.md` — fila ownership si se documentan dashboards de área
- [ ] `README.md` — no (salvo Documentador lo estime)
- [ ] `docs/ACCESS_CONTROL.md` — nota breve: board dashboard GH ahora tiene pantalla real; **sin** permiso nuevo

## Archivos compartidos (`shared-files`)

| Archivo | ¿Se toca? | Notas |
| --- | --- | --- |
| `config/access.php` | **NO** | Sin claves nuevas |
| `routes/web.php` | **SÍ** | Redirect `gestion_humana` + `dashboard` |
| `routes/areas/gestion_humana.php` | **SÍ** | Grupo dashboard área |
| `vite.config.js` | **SÍ** | Entry charts (shared-file build) |
| Layouts globales | **NO** (salvo necesidad mínima de `@vite`) | Preferir `@vite` en la vista |
| `resources/css/app.css` | Solo si hace falta BEM mínimo del dashboard; reutilizar clases existentes | |
| Seeders de permisos | **NO** | |

**Flag `shared-files` en TASKS.md:** `sí` — `routes/web.php`, `routes/areas/gestion_humana.php`, `vite.config.js`. **No** `access.php`.

## Criterios de aceptacion

1. Sidebar GH → Dashboard abre el informe (ya no el placeholder vacío de `view('dashboard')`).
2. Título «Dashboard de Gestión Humana – Informe Mensual».
3. Usuario sin `view.board.gestion_humana.dashboard` (y sin `manage.users`) → 403 en página y metrics.
4. Usuario con permiso → 200; filtros searchable-select; cambio de filtro refresca KPIs/gráficos vía JSON.
5. 10 KPIs visibles con definiciones de § Reglas.
6. 7 gráficos ApexCharts renderizan sin error de consola bloqueante.
7. Personal Operativo solo cuenta la lista canónica (trim + case-insensitive).
8. Rotación usa fórmula cerrada; denom 0 → display `—` (o 0, fijo en test).
9. Filtro cliente usa COALESCE work_center → cost_center.
10. Ciudad usa `displayCitySql`.
11. Card Psicofísicos = VIGENTE; Acreditaciones = ACREDITADO.
12. Pie Archivo = motivos `consultation_types`.
13. Sin cambios en `config/access.php`.
14. `php artisan test --compact` del alcance en verde; `npm run build` si hubo Vite/CSS.
15. Smoke Chrome DevTools MCP OK.

## Validacion local

1. Login con usuario que tenga `view.board.gestion_humana.dashboard`.
2. Abrir GH → Dashboard; verificar redirect y título.
3. Cambiar mes/año/ciudad/cargo/cliente; ver «Actualizando…» y KPIs coherentes.
4. Forzar URL metrics sin permiso → 403.
5. `php artisan test --compact --filter=GestionHumanaAreaDashboard` (o nombre del test Feature).
6. Smoke DevTools: consola limpia; network metrics 200.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| **Reconstrucción EOM de activos** costosa o divergente vs `employment_status` actual | Formalizar SQL EOM; índices existentes en fechas; tests con fixtures hire/termination; documentar que stock histórico ≠ status live si datos inconsistentes |
| **Cliente Interno / Formación / Archivo sin ciudad/cargo/cliente** | V1 ignora esos filtros en esas fuentes; `meta.filters_parciales_nota`; no inventar joins frágiles por cédula |
| Datos ficha sin `hire_date` | Excluir de stock EOM (regla `hire_date IS NOT NULL`); reportar en doc |
| Cargos operativos con typos en ficha | Match exacto lista; no fuzzy; typos quedan fuera (esperado) |
| Pie Archivo multi-motivo por consulta | Contar **cada** motivo en el array (una consulta con 2 motivos incrementa 2 segmentos) |
| Performance 12× EOM + joins MT-ST-04/Acred | Queries agregadas; limitar top cargos; evitar N+1; si lento en prod, T2 de optimización |
| Drift labels ortografía en `archive_consultation_types` | Usar config tal cual; no renombrar keys en esta feature |

**Dependencias:** Ficha empleados, MT-ST-04 (FEAT-045), Acreditaciones, Cliente interno (FEAT-042), Formación (FEAT-041), Archivo — módulos estables en lectura.

## Task Cards sugeridas (para AgentSj)

| Task | Scope vertical | Notas |
| --- | --- | --- |
| **T1** (preferido, único) | Redirect `web.php` + rutas GH + `GestionHumanaAreaDashboardController` + `GestionHumanaAreaDashboardService` (10 KPIs + 7 charts + EOM + lista operativa) + Blade + Alpine + Vite entry/charts + tests Feature + `npm run build` | **shared-files:** `web.php`, `gestion_humana.php`, `vite.config.js`. **No** `access.php`. Vertical slice completo. |
| **T2** (solo si hace falta) | Optimización SQL EOM / índices / caching ligero **o** pulido UI si T1 deja deuda visual explícita en review | Abrir **solo** con reason claro (perf medible o nits UI bloqueantes). Si no, Revisor → Documentador tras T1. |

Revisor tras T1 (o T1+T2). Documentador al cierre (`docs/modules` + `docs/user`).

## Aprobacion

- [x] Analista — Skip (vacíos ya cerrados por usuario 2026-10-13; sin ronda de preguntas)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-046.md`)
- [x] Usuario — confirmación 2026-10-13 (7 decisiones de negocio + permiso único)

---

## Handoff a AgentSj

1. Actualizar `docs/TASKS.md`: Brief → `docs/briefs/FEAT-046.md`; fase → plan orquestación; `shared-files` = `routes/web.php`, `routes/areas/gestion_humana.php`, `vite.config.js` (**no** `access.php`).
2. Generar `docs/briefs/FEAT-046-plan.md` (ORCHESTRATION_PLAN) con **T1** (y T2 opcional condicionado).
3. Lanzar Agente Feature T1 → Revisor → Documentador.
4. Actualizar `docs/runs/FEAT-046-run-log.md` con paso Arquitecto OK.
