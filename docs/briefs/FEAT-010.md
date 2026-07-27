# Feature Brief — FEAT-010

> Version final (Arquitecto / AgentSj). UX confirmada 2026-07-27: Apex bar/line sin cilindro ECharts.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-010 |
| Modulo / area | requisitions (GH), indicadores/operaciones, charts JS compartido |
| Titulo | Unificar graficos en ApexCharts (retirar Chart.js y ECharts) |
| Solicitante | Usuario (continuacion piloto Comercial) |
| Fecha | 2026-07-27 |

## Objetivo

Estandarizar una sola libreria de graficos (**ApexCharts** via npm/Vite) en Comercial, Gestion Humana y Operaciones/indicadores, eliminando Chart.js y ECharts del runtime.

## Alcance

### Incluye

1. Shared `resources/js/charts/apex-defaults.js` + refactor ligero de `comercial-dashboard-charts.js`.
2. GH: migrar dashboard requisiciones a ApexCharts (entry Vite + JSON Blade).
3. Operaciones: migrar `indicadores-capture.js` a Vite; reescribir charts con Apex (bar + line mixed); quitar CDN ECharts.
4. Grep cleanup: cero referencias runtime a Chart.js/ECharts.
5. Tests smoke GH + actualizar test captura indicadores; docs Architecture + modulos.

### Fuera de alcance

- Cambios de KPIs, filtros o formulas de negocio.
- Replicar pictorialBar/cilindro ECharts.
- Nuevos tipos de grafico no existentes hoy.

## Reglas de negocio / UX

1. Solo ApexCharts en el proyecto.
2. Operaciones: equivalencia de series (denominador, numerador, % cumplimiento, meta) con barras/lineas Apex estandar.
3. Defaults: toolbar off, animations off (o minimos), colores brand.

## Permisos / Rutas / BD

Sin cambios.

## Capas a implementar

- [x] Vista(s) Blade — dashboards GH + show indicadores
- [x] JavaScript — Vite entries + shared defaults
- [x] Tests
- [x] Documentacion

## Archivos compartidos (`shared-files`)

`vite.config.js`, entries JS, dashboards Blade, `public/js/indicadores-capture.js` (migrar/deprecar), docs.

## Criterios de aceptacion

1. Comercial + GH + captura Operaciones solo ApexCharts (Vite).
2. Ningun Chart.js ni ECharts en HTML/JS servido.
3. Filtros GH y captura indicadores OK.
4. Tests verdes; `npm run build` OK.
5. Docs: ApexCharts estandar.

## Aprobacion

- [x] Usuario — Apex sin cilindro; continuar plan (2026-07-27)
- [x] Plan — `docs/briefs/FEAT-010-plan.md`
