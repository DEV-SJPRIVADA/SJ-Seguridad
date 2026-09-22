# Review Report — FEAT-035

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-035 |
| Fecha | 2026-09-22 |
| Alcance revisado | Working tree T1–T5 (permisos, catálogos, Ingreso, Examen, Dashboard) vs `docs/briefs/FEAT-035.md` |
| Veredicto | Aprobado con observaciones |
| Revisor | Revisor FEAT-035 (subagente) |
| Blockers | No |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `SeleccionDashboardService` | Metrics carga en memoria **todas** las filas filtradas de Ingreso + Examen (`->get()`) para armar KPIs/charts | Si el volumen crece, agregar agregaciones SQL (`groupBy` / `count`) en lugar de colecciones PHP |
| 2 | Media | `SeleccionController::ingresosExport` / `examenesExport` | Export hace `filteredQuery()->get()` **sin** `with([...])`; el export accede a `commercialClient`, `uniform`/`responsable` → N+1 | Eager-load relaciones antes del `get()` |
| 3 | Baja | `SeleccionBoardAccessTest` | Solo afirma que el rol `usuario` no recibe permisos Selección; el brief también excluye `administrador` | Añadir aserción espejo para `administrador` |
| 4 | Baja | `SeleccionExamensExport` | Nombre de clase con typo (`Examens` vs `Examenes`) | Renombrar en follow-up si no hay referencias externas |
| 5 | Info | `docs/modules/seleccion.md` / `docs/user/seleccion.md` | Docs de módulo/usuario aún no existen (previsto Documentador) | Documentador: crear docs + INDEX / ACCESS_CONTROL / ARCHITECTURE |
| 6 | Info | `SeleccionCatalogService::hasBusinessReferences` | Solo bloquea refs en tablas Selección; city/position/eps/afp dual-edit con Ficha pueden borrarse aquí si Ficha los usa | Aceptable por brief (scope Selección); documentar riesgo dual-edit en doc módulo |
| 7 | Info | Audit metadata Ingreso/Examen | `document_number` se registra en metadata de create/delete | Aceptable operativamente; si política PII se endurece, limitar a id |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — board/view/edit + bypass `manage.users`; Catálogos solo `seleccion.edit`
- [x] Sin registro publico ni bypass de middleware — rutas bajo `auth`/`active` (require en `web.php`) + `password.changed`
- [x] Validacion de entradas (Form Requests) — store/update ingreso/examen/catalog; codes payroll activos; responsable vía selection officers
- [x] Sin duplicacion innecesaria — patrón Cursos; services dedicados
- [x] Rutas en archivo de modulo/area correcto — `routes/areas/gestion_humana.php`
- [x] Migraciones compatibles con hosting compartido — aditivas `create` + seed `updateOrCreate`; **sin** unique en cédula; sin fresh
- [x] Export Excel usa `BaseExport` + `<x-export-excel>`; sin `excelHtml5`
- [x] searchable-select en formularios/filtros/dashboard; sin Select2
- [x] DataTables server-side (`serverSide: true` + DatatableService); tope `take(100)`; lengthMenu sin `-1`
- [x] Duplicado B: `confirm_duplicate` + lookup misma tabla; test cruzado Ingreso↔Examen
- [x] Catalog whitelist 7 tipos + exclusión Ficha (`ficha_admin_excluded_catalog_types`)
- [x] Audit vía `SeleccionAuditLogService` → `SystemAuditService` (`module=seleccion`, `area=gestion_humana`)
- [x] Tests Feature presentes (`SeleccionBoardAccess`, `Catalog`, `Ingreso`, `Examen`, `Dashboard`; ~41 según run log)
- [ ] Docs modules/user alineadas — **pendiente Documentador** (no bloquea código)

## Seguridad

- `SeleccionAccessService`: `canViewSeleccionBoard` / `canView` / `canEdit` con bypass `manage.users`.
- Controlador: GETs de consulta/`export`/`datatable`/`metrics` con `canView`; mutaciones + Catálogos + lookup cédula con `canEdit` (también en Form Requests).
- Whitelist de tipos de catálogo con `abort_unless(isManagedType)` → 404 fuera de lista.
- DELETE de catálogo bloqueado si hay referencias en tablas Selección.
- Sidebar/board gated por `canViewSeleccionBoard`; pestaña Catálogos filtrada en `visibleTabsFor`.
- Patrón de middleware alineado al resto de GH (grupo `auth`+`active` en `web.php`).

## Consistencia con AGENTS.md y docs

- Vertical slice área GH; shared-files documentados en TASKS.
- Selectores `<x-searchable-select>`; export `BaseExport`; DT server-side; audit central; sin Repository; migraciones aditivas.
- Nav chrome: subnav con `.module-tab` (patrón pills).
- Docs técnicas/usuario: pending Documentador (criterio brief § Documentacion).

## Criterios de aceptacion (muestreo)

| AC | Estado |
| --- | --- |
| view sin Catálogos / edit con Catálogos | OK (Access + tests) |
| 403 sin permisos; bypass manage.users | OK |
| usuario sin paquete por defecto | OK (test); administrador no cubierto (obs. #3) |
| CRUD + required fields | OK (Form Requests + tests) |
| Duplicado B misma tabla / no cruzado | OK (tests Ingreso + Examen) |
| DT server-side + export auth | OK |
| Dashboard metrics + filtros | OK (service + test) |
| Whitelist 7 + seed + exclusión Ficha | OK |
| Borrado duro + audit mutaciones | OK |
| Sin Select2 / excelHtml5 / unique cédula / fresh | OK |

## Siguiente paso

- [x] Pasar a Documentador (veredicto aprobado con observaciones; **sin blockers**)
- [ ] Devolver a Agente Feature — **no aplica**
- [ ] Follow-up opcional: obs. #1–#3 (dashboard SQL, eager export, test administrador)
