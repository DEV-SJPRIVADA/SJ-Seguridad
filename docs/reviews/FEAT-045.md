# Review Report — FEAT-045

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-045.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-045 |
| Fecha | 2026-10-09 |
| Alcance revisado | Diff T1–T4 (lectura código + PHPUnit): `config/access.php`, `config/mt_st_04.php`, `config/audit.php`, migración `mt_st_04_registros`, `MtSt04AccessService`, nav (`NavigationResolver`, `SidebarVisibilityService`, `User`), `MtSt04Controller` + Form Requests, `MtSt04EstadoCalculator`, `MtSt04ListService` / `DatatableService`, `MtSt04ImportService`, exports `MtSt04Export` / `MtSt04ImportTemplateExport`, `MtSt04DashboardService`, `SyncMtSt04EstadosCommand` + schedule `bootstrap/app.php`, audit wrapper, vistas `mt_st_04/*`, charts JS, tests Feature MT-ST-04 |
| Veredicto | **GO-with-nits** (Aprobado con observaciones) |

## Hallazgos

### Bloqueantes (blocker)

| # | Severidad | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- | --- |
| — | — | — | Ninguno. Auth/permisos OK; edit⇒view en AccessService; sin registro público; sin Select2/`excelHtml5`; DT `serverSide: true`; migración incremental multi-driver; calculadora +364 / VENCERA≤30 Bogotá / NO APLICA exacto; import upsert + export `BaseExport`; schedule 06:25; UI chrome Formación/Cursos. | — |

### Majors

| # | Severidad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| — | — | — | Ninguno que impida Documentador. | — |

### Minors / nits

| # | Severidad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | minor | `matriz.blade.php` / `MtSt04ListService` | Brief sugiere filtro operativo por **cargo**; UI expone `q` (cédula/nombre) + estados/arma/apto/ficha; DT search también cubre cargo, pero no hay control dedicado. | Opcional: searchable/input cargo en filtros GET. |
| 2 | nit | `MtSt04Registro` `$fillable` | Incluye derivados (`fecha_vencimiento_*`, `estado_*`). Store/Update/Import no los toman del request (OK). | Reducir fillable a editables + auditoría; calcular derivados solo en memoria/save. |
| 3 | nit | `dashboard.blade.php` | KPI cards con `style="border-left-color:#…"` (patrón Cursos). | Preferir tokens `--brand-*` / clases KPI existentes si se unifica branding. |
| 4 | minor | `MtSt04Controller::exportMatriz` | Export carga colección completa filtrada en memoria (`listService->all`). Alineado a otros módulos GH; riesgo solo a volumen alto. | Documentar límite operativo; chunk/stream si crece. |
| 5 | minor | Run log / cierre UI | No hay evidencia explícita de smoke Chrome DevTools MCP en el run log (regla proyecto post-UI). Código UI cumple chrome. | AgentSj: smoke breve Dashboard+Matriz o nota en checklist cierre. |
| 6 | nit | Docs módulo | `docs/modules/mt_st_04.md`, `docs/user/mt_st_04.md`, ACCESS/INDEX/PROCEDURES pendientes. | Documentador (siguiente paso). |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`)
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests store/update/import; huérfanas + unicidad)
- [x] Sin duplicacion innecesaria (calculadora única; ListService compartido DT/export/dashboard)
- [x] Rutas en archivo de area correcto (`routes/areas/gestion_humana.php`, `password.changed`; sin tocar `web.php`)
- [x] Migraciones compatibles (create estándar; unique + índices; FK users `nullOnDelete`; sin `enum`/`change`; OK sqlite tests)
- [x] Export Excel usa `BaseExport` + `<x-export-excel>`; plantilla sin ESTADO/VENCIMIENTO
- [x] Tests relevantes presentes (acceso, matriz, calculadora, import/export, dashboard, sync)
- [x] UI nueva: shell `req-manage-*` / `module-tab`, searchable-select, icon-btn canónicos (`req-manage-filters__icon-btn` / `cursos-catalogo-page__icon-btn`); sin Select2 ni familia `mt-st-04__icon-btn`
- [x] UI: DataTables `serverSide: true`; clase `js-mt-st-04-datatable` (no `.js-datatable`); `lengthMenu` sin `-1`; tope server 100

## Verificacion vs brief (ley)

| Criterio | Estado |
| --- | --- |
| Board `view.board.gestion_humana.mt_st_04` + `mt_st_04.view` / `.edit`; sin `parameters.edit` / sin board dashboard aparte | OK |
| `edit` ⇒ `view` en `MtSt04AccessService`; bypass `manage.users` | OK — tests Board + Matriz |
| Tabs Dashboard \| Matriz; labels Admin legibles; subgroup MT-ST-04 | OK |
| Tabla `mt_st_04_registros`; unique `document_number`; sin FK ficha | OK |
| Lookup live NOMBRE/CARGO/CIUDAD/PUESTO; alta/import rechazan huérfanas | OK |
| Vencimiento = examen + 364; estados persistidos | OK — calculator + tests |
| VENCERA inclusivo ≤30d TZ America/Bogota | OK — bordes día 0 y día 30 |
| ESTADO2 NO APLICA solo `GUARDA`/`OPERADOR` exact (mb_strtoupper); `GUARDA SJ` no | OK |
| DT default activos ficha; filtro desvinculado/todos | OK |
| Import upsert; última duplicada gana; plantilla keys fila1 / labels fila2 | OK |
| Export BaseExport con estados/vencimientos + ficha live | OK |
| Dashboard KPIs ex1 + ex2 excl. NO APLICA + aptos + charts | OK |
| Comando `mt_st_04:sync-estados` + schedule 06:25 Bogotá `withoutOverlapping` | OK |
| Audit created/updated/deleted/import_upsert/export/sync_estados | OK |
| Sin Select2 / excelHtml5 / bulk / RETIRADOS / migrate:fresh | OK |

## Seguridad

- Rutas bajo `auth` + `active` (vía `web.php`) y `password.changed` (grupo GH).
- Vista: `abort_unless(canView)`; mutaciones Form Request `canEdit` o `abort_unless(canEdit)` (destroy/template/lookup).
- Viewer: 403 en store/lookup/destroy/import/plantilla; export permitido.
- Editor sin Spatie `view`: puede ver y mutar (implicación).
- Sin registro público; Spatie assign manual (sin migración legacy).
- Validación cédula `exists` ficha + `unique` matriz; import rechaza huérfanas por fila.
- Audit con conteos/ids; sin volcar PII masivo del Excel.
- Datatable escapa HTML (`e()`); CSRF en forms y delete de fila.

## Consistencia con AGENTS.md y docs

- Vertical slice GH; ownership rutas en `gestion_humana.php`.
- Permisos por pestaña view/edit (modelo Comercial/Formación).
- Chrome UI alineado a Formación/Cursos/Acreditaciones.
- Docs viva de módulo/usuario/ACCESS/PROCEDURES → Documentador.
- Protección de datos: solo migrate incremental; sin wipe/fresh en feature.

## Pruebas ejecutadas (Revisor)

```text
php artisan test --compact ^
  tests/Feature/GestionHumana/MtSt04BoardAccessTest.php ^
  tests/Feature/GestionHumana/MtSt04MatrizTest.php ^
  tests/Feature/GestionHumana/MtSt04EstadoCalculatorTest.php ^
  tests/Feature/GestionHumana/MtSt04ImportExportTest.php ^
  tests/Feature/GestionHumana/MtSt04DashboardTest.php ^
  tests/Feature/GestionHumana/MtSt04EstadoSyncTest.php
→ 50 passed (248 assertions)
```

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)

**Para AgentSj:** veredicto **GO-with-nits** — lanzar Documentador (`docs/modules/mt_st_04.md`, `docs/user/mt_st_04.md`, `ACCESS_CONTROL`, `INDEX`, `PROCEDURES` comando/schedule, `README` si lista tableros GH). Observaciones 1–5 opcionales post-cierre; ninguna bloquea. Recomendado: smoke DevTools Dashboard+Matriz en checklist AgentSj.
