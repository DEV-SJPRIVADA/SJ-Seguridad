# Review Report — FEAT-036

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-036 |
| Fecha | 2026-09-23 |
| Alcance revisado | Working tree T1–T4 (shell, catálogo, Acreditados, import/sync) vs `docs/briefs/FEAT-036.md` |
| Veredicto | **Aprobado con observaciones** |
| Revisor | Revisor FEAT-036 (subagente) |
| Blockers | No |
| Tests ejecutados | `tests/Feature/GestionHumana/Acreditacion*` + BoardAccess + Catalog + Import + EstadoSync — **45 passed** (181 assertions) |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `AcreditacionAcreditadoListService::all` / `exportAcreditados` | Export hace `filteredQuery()->get()` sin tope; volumen alto puede saturar memoria | Follow-up: chunk/stream o tope documentado (mismo patrón observado en Selección FEAT-035) |
| 2 | Baja | `Store/UpdateAcreditacionAcreditadoRequest` | `Rule::unique` compara `cargo_apo` exacto; import/upsert usa `forCargoApo` (LOWER/TRIM). Con collation case-sensitive podría divergir | Alinear unique a comparación case-insensitive o normalizar `cargo_apo` a mayúsculas al persistir |
| 3 | Baja | Suite Feature Acreditaciones | Se audita en controlador (create/update/delete + resumen import), pero **no** hay test que afirme filas en `audit_logs` | Añadir 1–2 aserciones espejo Cursos/Ficha |
| 4 | Baja | `AcreditacionEstadoCalculator` | Si ambas fechas son `null`, retorna `ACREDITADO` (defensivo); Form Request/import ya rechazan el caso | Preferible throw/assert o estado explícito de error si algún path llega sin fechas |
| 5 | Info | Audit metadata | Create/delete registran `document_number` en metadata | Aceptable operativamente; si política PII se endurece, limitar a id |
| 6 | Info | Docs módulo/usuario | `docs/modules/acreditaciones.md` / `docs/user/acreditaciones.md` aún no existen (previsto Documentador) | Documentador: crear docs + INDEX / ACCESS_CONTROL / ARCHITECTURE |
| 7 | Info | Working tree | Hay archivos ajenos a FEAT-036 (p. ej. Plantillas Word) junto al slice | AgentSj: no mezclar en el mismo commit de cierre |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — board / `acreditaciones.view` / `acreditaciones.edit` + bypass `manage.users`; Catálogo solo edit
- [x] Sin registro publico ni bypass de middleware — rutas bajo `auth`/`active` (`web.php`) + `password.changed`
- [x] Validacion de entradas (Form Requests) — Ficha obligatoria, al menos una fecha, `cargo_apo` activo en catálogo, unique cédula+APO
- [x] Sin duplicacion innecesaria — patrón Cursos/Selección; services dedicados
- [x] Rutas en archivo de modulo/area correcto — `routes/areas/gestion_humana.php`
- [x] Migraciones compatibles con hosting compartido — aditivas `create` + seed `updateOrCreate`; **sin** `migrate:fresh`
- [x] Export Excel usa `BaseExport` + `<x-export-excel>`; plantilla dedicada sin columna ESTADO; sin `excelHtml5`
- [x] searchable-select en filtros/forms; sin Select2
- [x] DataTables server-side (`serverSide: true` + `AcreditacionAcreditadoDatatableService`); `lengthMenu` sin `-1`; tope 100
- [x] Unicidad `(document_number, cargo_apo)` en BD + upsert import
- [x] Estados con prioridad brief; sync `acreditaciones:sync-estados` @ 06:20 Bogota (`bootstrap/app.php`)
- [x] Catálogo seed 17 + CRUD; delete/rename APO protegido con acreditados
- [x] Placeholders Dashboard / Reporte Diario / Validaciones / Export Apo («Próximamente»)
- [x] Audit vía `AcreditacionesAuditLogService` → `SystemAuditService` (`module=acreditaciones`, `area=gestion_humana`)
- [x] Tests Feature presentes (45 verdes)
- [ ] Docs modules/user alineadas — **pendiente Documentador** (no bloquea código)

## Seguridad

- `AcreditacionesAccessService`: board / view / edit con bypass `manage.users`.
- Controlador: GETs de consulta/datatable/export/placeholders con `canView`; mutaciones, lookup Ficha, plantilla/import/reporte y Catálogo con `canEdit` (también en Form Requests).
- `visibleTabsFor` oculta Catálogo sin edit; tests de sidebar board y 403 sin permisos.
- Roles `usuario` / `administrador` **no** reciben permisos Acreditaciones por defecto (test + seeder sin paquete).
- `PermissionCatalog` no genera `view.board.*.acreditaciones` fuera de `gestion_humana`.
- CSRF en forms DELETE de datatable; ESTADO sin `name` en UI (no se envía); payload servidor recalcula estado.
- Middleware alineado al resto de GH.

## Consistencia con AGENTS.md y docs

- Vertical slice área GH; shared-files autorizados en brief/plan (`access.php`, rutas GH, nav, `audit.php`, schedule).
- Selectores `<x-searchable-select>`; export `BaseExport`; DT server-side; audit central; sin Repository; migraciones aditivas.
- Nav chrome: subnav `.module-tab` (patrón pills).
- Docs técnicas/usuario: pending Documentador (criterio brief § Documentacion).

## Criterios de aceptacion (muestreo)

| AC | Estado |
| --- | --- |
| view sin Catálogo / mutación; edit con Catálogo + CRUD/import | OK (Access + tests + vistas `canEdit`) |
| 403 sin permisos; bypass `manage.users` | OK |
| `usuario` / `administrador` sin paquete por defecto | OK |
| Placeholders 200 «Próximamente» | OK |
| Ficha obligatoria + `full_name` desde Ficha | OK |
| Ambas fechas vacías → 422 / import fail | OK |
| Upsert cédula+`cargo_apo` | OK |
| Prioridad estados (solicitud > vencido > por vencer > acreditado) | OK (calculator + tests) |
| ESTADO no editable UI/request/import | OK |
| DT server-side + filtros + export auth | OK |
| Catálogo seed 17 + protección APO | OK |
| Sync diario 06:20 + comando | OK |
| Audit mutaciones (+ resumen import) | OK en código; aserción DB pendiente (obs. #3) |
| Sin Select2 / excelHtml5 / migrate:fresh | OK |

## Siguiente paso

- [x] **Pasar a Documentador** (veredicto no bloqueado)
- [ ] Devolver a Agente Feature — no aplica

**Mensaje a AgentSj:** FEAT-036 **Aprobado con observaciones**. Puede lanzar Documentador (`docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md`, INDEX / ACCESS_CONTROL / ARCHITECTURE). Observaciones #1–#4 son follow-up opcional, no bloquean cierre. Evitar incluir en el commit de Acreditaciones archivos ajenos (Plantillas Word) del working tree.
