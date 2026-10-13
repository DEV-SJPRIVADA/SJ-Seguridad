# Review Report — FEAT-046

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-046.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-046 |
| Fecha | 2026-10-13 |
| Alcance revisado | Diff T1 (lectura código + PHPUnit): controller/service dashboard área GH, Blade, charts Vite, rutas `web.php` + `gestion_humana.php`, `vite.config.js`, tests Feature + ajuste `AdminUserManagementTest`. **Sin** cambios en `config/access.php`. |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno. Auth gate correcto; sin keys nuevas; EOM/rotación/operativo/cliente/ciudad alineados al Brief; payload JSON contrato OK; UI chrome Formación + searchable-select + Apex Vite; tests 403/200, denom=0 → `—`, operativo case-insensitive, EOM. | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | Run log / cierre UI | Smoke Chrome DevTools MCP quedó pendiente por login local. Tests Feature en verde (8 passed / 110 assertions en re-ejecución Revisor). | AgentSj o Documentador: smoke breve tras credenciales locales (página + metrics 200 + consola limpia). No bloquea Documentador. |
| 2 | `GestionHumanaAreaDashboardService` | Evolución de personal ejecuta 12 conteos EOM (más ingresos/bajas por mes). Aceptable V1; riesgo de latencia con ficha grande (ya en Brief). | Documentar en doc técnica; T2 solo si se mide lento en prod. |
| 3 | `dashboard.blade.php` | KPI cards con `style="border-left-color:#…"` (incluye acento púrpura en Cliente Interno). Mismo patrón que Formación/Cursos. | Opcional unificar a tokens `--brand-*` si se limpia branding de dashboards GH. |
| 4 | `filterSelectOptions` / `positionOptions` | Brief menciona opciones de cargo «distinct trim»; opciones usan `position_name` crudo. | Trim en distinct/order si aparecen espacios en ficha. |
| 5 | Tests | Rotación con denominador > 0 no se aserta numéricamente (sí denom=0 → `—`). | Opcional: fixture con activos actual/anterior + bajas y assert del %. |
| 6 | Docs | `docs/modules` / `docs/user` / INDEX / ACCESS pendientes (fuera de scope T1). | Documentador. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — `view.board.gestion_humana.dashboard` ∨ `manage.users`; sin keys nuevas; `config/access.php` intacto
- [x] Sin registro publico ni bypass de middleware — rutas bajo `auth`+`active` (`web.php`) + `password.changed`
- [x] Validacion de entradas — normalización año/mes en servicio (aceptable V1; Form Request opcional)
- [x] Sin duplicacion innecesaria — servicio único area dashboard; reutiliza modelos/helpers existentes
- [x] Rutas en archivo de modulo/area correcto — `routes/areas/gestion_humana.php` + redirect en `web.php`
- [x] Migraciones compatibles con hosting compartido — N/A (sin migración)
- [x] Export Excel usa `BaseExport` si aplica — N/A (fuera de alcance V1)
- [x] Tests relevantes presentes o justificados — Feature acceso, payload, EOM, operativo, rotación denom=0; AdminUserManagement adaptado al redirect
- [x] UI nueva: `module-ui-quality` — shell Formación/Cursos (`formacion-dashboard-page` / `cursos-dashboard-page`), searchable-select, icon-btn canónico limpiar, sin Select2 / excelHtml5 / familia `*__icon-btn` propia
- [x] UI: DataTables server-side si el listado puede crecer — N/A (dashboard KPI/charts); sin Select2

## Seguridad

- Gate explícito en controller; sin Spatie keys nuevas.
- Bypass `manage.users` alineado al Brief (admin).
- Metrics JSON GET autenticado; sin mutaciones ni audit obligatorio.
- Filtros dim ficha parametrizados (`whereRaw` con bindings); sin SQL injection obvia.
- Sin registro público.

## Consistencia con AGENTS.md y docs

- Ownership área GH; shared-files autorizados tocados; `access.php` no tocado.
- Searchable-select + Apex vía Vite (`gh-area-dashboard-charts.js` + `apex-defaults.js`).
- Título UI y nota `meta.filters_parciales_nota` para CI/Formación/Archivo (solo año/mes).
- Contrato JSON del Brief (kpis/charts/meta) implementado.
- Docs de módulo/usuario: pendientes Documentador (esperado tras T1).

## Verificacion checklist especial (AgentSj)

| # | Criterio | Resultado |
| --- | --- | --- |
| 1 | Auth board dashboard ∨ manage.users; sin keys nuevas | OK |
| 2 | EOM / rotación / lista operativa / cliente COALESCE / displayCitySql | OK |
| 3 | CI / Formación / Archivo solo año/mes | OK (tests con filtro ciudad lo confirman) |
| 4 | UI module-ui-quality, searchable-select, Apex Vite | OK |
| 5 | Payload JSON contrato Brief | OK |
| 6 | Tests 403/200, denom=0, operativo CI, EOM | OK (re-ejecutados verdes) |
| 7 | Smoke DevTools pendiente login | Observación #1 (no bloqueante) |

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)
