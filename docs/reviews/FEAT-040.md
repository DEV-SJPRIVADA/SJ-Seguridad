# Review Report — FEAT-040

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-040 (T1–T4) |
| Fecha | 2026-09-30 |
| Alcance revisado | Working tree vs `docs/briefs/FEAT-040.md` + plan + `AGENTS.md` (estado pre-Documentador) |
| Veredicto | **Aprobado con observaciones** |
| Revisor | Revisor FEAT-040 (subagente) |
| Blockers | No |
| Tests ejecutados | `php artisan test --compact --filter=ReportesNovedades` — **28 passed** (173 assertions), ~52 s |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `ReportesNovedadesRetiroSyncService` + migración retiros | Unique simple `employee_termination_followup_id` incluye filas soft-deleted. Si GH anula (soft-delete) un retiro auto y luego `ensureForClosedPeriod` vuelve a correr con el mismo followup vivo, el `create` puede chocar con el unique (el sync solo busca no-deleted). El brief ya lo listó como riesgo residual. | En follow-up: `withTrashed()` + restore/update, o unique parcial solo no-deleted / nullificar FK al soft-delete. |
| 2 | Media | Suite Feature | Criterio mínimo del brief incluye test de **lookup** cédula→JSON; no hay caso Feature dedicado en `ReportesNovedades*` (sí hay `LookupFichaRequest` + `ReportesNovedadesFichaLookupService` cableados). | Añadir test Feature post-cierre (Documentador no bloquea; Feature follow-up). |
| 3 | Baja | `ReportesNovedadesFichaLookupService` | Fallback sin entry: `destino` usa `work_center_name` del perfil; el brief define destino = **cliente** de ficha. Path principal (entry + requisition) sí usa `clientName()`. | Documentar en módulo; si hace falta, mapear cliente también en el fallback de perfil. |
| 4 | Baja | Cobertura Incapacidades | Un solo test Feature agrega CRUD+ownership+export (correcto como smoke); menos granular que Vacaciones/Permisos. | OK para V1; ampliar si hay regresiones de columnas Nómina (`dias_entrega`). |
| 5 | Info | Docs | `docs/modules/reportes-novedades.md`, `docs/user/reportes-novedades.md`, ACCESS / INDEX / nota desvinculaciones / audit-log pendientes. | **Documentador** (siguiente paso). |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — board + view/edit/review × 4; `edit`⇒`view`; `review`⇒`view`+export; bypass `manage.users`
- [x] Sin registro publico ni bypass de middleware — rutas en `routes/areas/gestion_humana.php` + `password.changed` (grupo auth/active vía `web.php`)
- [x] Validacion de entradas (Form Requests) — Store/Update GH vs Review Nómina separados; catálogos `Rule::in`
- [x] Ownership columnas — update GH no incluye cols Nómina; review solo cols Nómina (tests Vacaciones/Incapacidades/Retiros/Permisos)
- [x] Sin Select2 / sin `excelHtml5`
- [x] Export Excel usa `BaseExport` (wrappers por hoja) + `<x-export-excel>`
- [x] DataTables `serverSide: true` en 4 hojas; `lengthMenu` sin `-1`; tope servidor 100
- [x] Historial por pestaña (modal + endpoint JSON); sin pestaña Auditoría global del tablero
- [x] Soft-delete Retiros (destroy manual + annul en revert); hooks create/revert en `EmployeeTerminationFollowupService`
- [x] Migraciones aditivas 4 tablas; SoftDeletes solo Retiros; sin `migrate:fresh`
- [x] `<x-searchable-select>` en catálogos de formularios
- [x] Audit wrapper `ReportesNovedadesAuditLogService` + módulo en `config/audit.php`
- [x] Nav: `NavigationResolver`, `SidebarVisibilityService`, `User::defaultReportesNovedadesBoardUrl()`
- [x] Tests Feature presentes (28 verdes)
- [ ] Docs modules/user alineadas — **pendiente Documentador**

## Seguridad

- Sin permisos de hoja → 403 en index/tabs (BoardAccessTest).
- Solo `.edit`: muta GH; `observacion_nomina` / cols Nómina no persisten en update GH.
- Solo `.review`: ve/exporta; store/destroy → 403; review ignora campos GH.
- Lookup cédula exige `canEditAnySheet` (no abre ficha a solo view/review).
- Retiros auto/annul corren bajo flujo Desvinculaciones/Ficha ya autorizado (no exige `retiros.edit` al actor).
- Bypass `manage.users` centralizado en `ReportesNovedadesAccessService`.
- Sin registro público; sin cambios destructivos de BD en la entrega.

## Consistencia con AGENTS.md y Brief

| Decision | Estado |
| --- | --- |
| Tablero UI **Reportes-Novedades**; tabs Vacaciones/Incapacidades/Retiros/Permisos | OK |
| Permisos view/edit/review + board; Admin subgroup | OK |
| Edición libre (sin workflow estados) | OK |
| 4 tablas tipadas; SoftDeletes solo Retiros | OK |
| Hook ensure + soft-delete en revert followup | OK (tests auto + revert) |
| Catálogos fijos `config/reportes_novedades.php` | OK |
| BaseExport + searchable-select; no Select2/excelHtml5 | OK |
| Historial por pestaña; audit mutaciones/export/retiro_auto | OK |
| DT server-side 4 hojas | OK |
| Fuera de alcance: import xlsm, KPI, BD FERIADOS, pestaña Auditoría | OK (no implementados) |

## Tests

| Suite / filtro | Resultado |
| --- | --- |
| `--filter=ReportesNovedades` | **28 passed** (173 assertions) |

Cobertura alineada al brief: board/403, edit⇒view, review⇒view/export/no-store, ownership GH/Nómina, destroy review 403, catálogo inválido, CRUD, export gates, historial, ensure idempotente, revert soft-delete, bypass `manage.users`. Hueco menor: lookup Feature (obs. #2).

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones; sin blockers)
- [ ] Devolver a Agente Feature (solo si hubiera blockers)

**Listo Documentador:** crear `docs/modules/reportes-novedades.md` + `docs/user/reportes-novedades.md`, actualizar ACCESS / INDEX / audit-log / nota en desvinculaciones; mencionar obs. #1 (unique vs soft-delete) y destino=cliente en la doc técnica.
