# Task Card — FEAT-010 / Tareas 1–3 (vertical slice)

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-010 |
| Brief | `docs/briefs/FEAT-010.md` |
| Plan | `docs/briefs/FEAT-010-plan.md` |

## Objetivo

Implementar unificacion ApexCharts completa: shared defaults, GH dashboard, Operaciones captura Vite+Apex, cleanup Chart.js/ECharts, tests y docs tecnicas basicas.

## Scope lock (permitido)

- `resources/js/charts/apex-defaults.js` (nuevo)
- `resources/js/comercial-dashboard-charts.js`
- `resources/js/requisitions-dashboard-charts.js` (nuevo)
- `resources/js/indicadores-capture.js` (nuevo desde public)
- `vite.config.js`
- `resources/views/modules/requisitions/dashboard.blade.php`
- `resources/views/areas/operaciones/indicadores/show.blade.php` (+ capture blades solo si hace falta attrs)
- `public/js/indicadores-capture.js` — eliminar o stub deprecado que no cargue ECharts
- `tests/Feature/*` relacionados (GH charts, IndicadorCapturePageTest, CommercialDashboardChartsTest si se rompe)
- `docs/ARCHITECTURE.md`, `docs/modules/{matriz-clientes,requisitions,indicadores}.md`
- `public/build/**` via npm run build
- `docs/TASKS.md` / run log fase

## Prohibido

- access.php, migraciones, cambiar payloads PHP de negocio
- Reintroducir Chart.js o ECharts

## Done

1. Grep limpio chart.js / echarts / new Chart / ensureEcharts
2. Tests pasan + npm run build
3. Reportar archivos a AgentSj
