# Review Report — FEAT-010

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-010.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-010 |
| Fecha | 2026-07-27 |
| Alcance revisado | `apex-defaults.js`; entries Vite comercial/requisitions/indicadores; dashboards Blade GH + Comercial + show indicadores; stub `public/js/indicadores-capture.js`; `vite.config.js`; tests smoke; grep Chart.js/ECharts; docs Architecture + modulos |
| Veredicto | **Aprobado con observaciones** |

## Criterios de aceptacion (checklist)

| # | Criterio | Resultado |
| --- | --- | --- |
| 1 | Comercial + GH + captura Operaciones solo ApexCharts (Vite) | OK — tres entries en `vite.config.js`; Blades con `@vite`; shared `apex-defaults.js` |
| 2 | Ningun Chart.js ni ECharts en HTML/JS servido | OK — grep `resources/` limpio; `public/js` solo comentario de deprecacion en stub; `package.json` solo `apexcharts` |
| 3 | Filtros GH y captura indicadores OK | OK — auto-submit selects conservado en GH/Comercial; show indicadores mantiene filtros año/mes + Vite entry |
| 4 | Tests verdes | OK — 5 passed / 39 assertions (`RequisitionsDashboardChartsTest`, `IndicadorCapturePageTest`, `CommercialDashboardChartsTest`) |
| 5 | Docs: ApexCharts estandar | OK — `ARCHITECTURE.md` + `requisitions.md` / `indicadores.md` / `matriz-clientes.md`; INDEX con referencia antigua (obs.) |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `docs/INDEX.md` | Sigue listando captura como `public/js/indicadores-capture.js` (sin Livewire), desalineado con Vite + Apex. | Documentador: apuntar a `resources/js/indicadores-capture.js` y stub deprecado. |
| 2 | `comercial-dashboard-charts.js` / `requisitions-dashboard-charts.js` | Estructura casi duplicada (read JSON, 4 renders, auto-submit filtros). | Aceptable en este slice; opcional helper compartido en feature futura. |
| 3 | `resources/js/indicadores-capture.js` (`renderMixedBarLine`) | Cada serie de barras recibe su propio `yaxis` con `seriesName` distinto; Apex puede escalar ejes izquierdos por separado (denominador vs numerador). | Validar visualmente FT-OP-01/03; si distorsiona, compartir `seriesName`/eje unico para columnas. |
| 4 | `tests/Feature/IndicadorCapturePageTest.php` | `assertSee('indicadores-capture')` tambien matchea `data-indicadores-capture` / form id; no afirma explicitamente el entry Vite. | Opcional: `assertSee('resources/js/indicadores-capture'` o fragmento del manifest Vite en HTML. |
| 5 | `comercial-dashboard-charts.js` `renderTrend` | Prop `labels` redundante junto a `xaxis.categories` (patron pie/donut). | Quitar `labels` en area charts si se retoca. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — sin cambios de permisos/rutas; tests con permisos de area/tablero
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests) — N/A (sin cambios de negocio/persistencia de KPIs)
- [x] Sin duplicacion innecesaria — shared defaults; entries de dashboard similares pero acotadas al alcance
- [x] Rutas en archivo de modulo/area correcto — sin rutas nuevas
- [x] Migraciones compatibles con hosting compartido — N/A
- [x] Export Excel usa `BaseExport` si aplica — N/A
- [x] Tests relevantes presentes o justificados — smoke GH/Comercial + captura sin CDN ECharts/Chart.js

## Seguridad

- Sin cambios de auth, permisos ni registro publico.
- Datos de grafico via `@json` / `data-chart='@json(...)'` (patron Laravel existente); JS parsea con try/catch.
- Stub deprecado en `public/js` no carga librerias externas; solo `console.warn`.

## Consistencia con AGENTS.md y docs

- Alineado con stack modular + Vite; estandar ApexCharts documentado en Architecture y modulos afectados.
- Defaults UX del brief: `toolbar` off, `animations` off, colores brand en `apex-defaults.js`.
- Operaciones: mixed bar/line (sin cilindro ECharts) conforme a decision UX.
- Documentacion de usuario (`docs/user/`) no revisada aqui — corresponde al Documentador post-aprobacion.
- `docs/INDEX.md` pendiente de alinear (obs. 1).

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)

**Notificacion AgentSj:** veredicto **Aprobado con observaciones**. Puede lanzar Documentador. Observaciones 1 (INDEX) deben quedar en doc de cierre; 2–5 son opcionales / deuda menor.
