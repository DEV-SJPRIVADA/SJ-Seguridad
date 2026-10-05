# Task Card — FEAT-042 / T5

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Tarea # | T5 |
| Brief | `docs/briefs/FEAT-042.md` § Dashboard |
| shared-files | Vite entry si aplica (autorizado para charts JS) |

## Objetivo

Dashboard KPIs: total, por ESTADO (incl. Sin estado), promedio/distribución días respuesta, tendencia mensual; filtros año/mes; ApexCharts + metrics JSON. Sin mutaciones.

## Scope lock

- `ClienteInternoDashboardService`
- Controller dashboard + metrics
- Vista dashboard + `resources/js/cliente-interno-dashboard-charts.js` + vite.config entry
- Tests Dashboard
- `docs/TASKS.md`

## Criterios de done

1. Filtros año (default sensato) + mes opcional.
2. KPIs del brief.
3. Authorize canViewDashboard.
4. Tests + pint + `npm run build` si hay entry Vite nueva.

## No hacer

Doc final módulo (Documentador), access.php salvo inevitables.
