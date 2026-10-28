# Task Card — FEAT-046 / T1

> Vertical slice completo. Agente Feature: implementar según Brief; **no** doc de módulo (Documentador).

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature | FEAT-046 |
| Task | T1 |
| Modulo | gestion_humana / dashboard de área |
| Depende de | Brief `docs/briefs/FEAT-046.md` |

## Scope lock (implementar)

1. **Redirect** en `routes/web.php`: `gestion_humana` + `board=dashboard` → `route('gestion-humana.dashboard')` (espejo Comercial).
2. **Rutas** en `routes/areas/gestion_humana.php`:
   - `GET /gestion-humana/dashboard` → `gestion-humana.dashboard`
   - `GET /gestion-humana/dashboard/metrics` → `gestion-humana.dashboard.metrics`
3. **Controller** `App\Http\Controllers\GestionHumana\GestionHumanaAreaDashboardController` (página + metrics).
4. **Service** `App\Services\GestionHumana\GestionHumanaAreaDashboardService` — 10 KPIs + 7 charts + EOM + lista operativa canónica + payload JSON del Brief.
5. **Auth:** `view.board.gestion_humana.dashboard` ∨ `manage.users`. **Prohibido** tocar `config/access.php`.
6. **Vista** `resources/views/areas/gestion_humana/dashboard.blade.php` — clonar chrome Formación dashboard + densidad MT-ST-04. Título: «Dashboard de Gestión Humana – Informe Mensual». searchable-select; filtros Año/Mes/Ciudad/Cargo/Cliente; Alpine `metricsUrl` refresh.
7. **JS Vite:** `resources/js/gh-area-dashboard-charts.js` (+ defaults Apex); registrar entry en `vite.config.js`.
8. **Tests Feature** acceso 403/200, claves JSON, rotación denom=0 → `—`, cargos operativos case-insensitive, EOM básico.
9. `vendor/bin/pint --dirty --format agent`; `npm run build`; `php artisan test --compact` del alcance; smoke Chrome DevTools MCP.

## Shared-files autorizados

- `routes/web.php`
- `routes/areas/gestion_humana.php`
- `vite.config.js`
- `resources/css/app.css` solo si BEM mínimo imprescindible

**NO** `config/access.php`.

## Fuera de scope

- Export Excel/PDF
- Permisos nuevos
- Aplicar ciudad/cargo/cliente a CI / Formación / Archivo
- Snapshot físico headcount
- Cambiar dashboards de otros tableros
- Docs modules/user (Documentador)

## Criterios done

- Criterios de aceptación 1–15 del Brief.
- Reportar a AgentSj: archivos, tests, build y pasos de prueba manual para el usuario.

## Referencias

- Brief: `docs/briefs/FEAT-046.md`
- Vista ref: `formacion/dashboard.blade.php`, `mt_st_04/dashboard.blade.php`
- Reglas: `module-ui-quality.mdc`, `branding.md`, `manual-ui-verification.mdc`
- Skill Laravel: `.cursor/skills/laravel-best-practices/SKILL.md`
