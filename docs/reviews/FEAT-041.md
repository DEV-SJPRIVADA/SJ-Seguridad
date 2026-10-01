# Review Report — FEAT-041

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-041.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-041 |
| Fecha | 2026-10-01 |
| Alcance revisado | T1–T4 (permisos, migración, Formaciones DT/export/import, Dashboard KPIs) — archivos `Formacion*`, `config/access.php`, `config/audit.php`, `config/formacion.php`, rutas GH, nav, vistas, tests Feature |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `FormacionController.php` ~L118 / `FormacionExport.php` | Export hace `filteredQuery()->get()` y materializa toda la colección en memoria. Con ~81k filas (o filtro amplio) puede agotar memoria/timeout en Hostinger. | Chunked/`fromQuery` o tope + aviso; documentar límite en doc de módulo. |
| 2 | `FormacionImportService.php` ~L59–108 | Import valida todo en memoria y luego `delete` + `insert` por chunks (correcto vs TRUNCATE). Riesgo de memoria/tiempo en 81k ya anticipado en el brief. | Medir en local con archivo real; documentar `set_time_limit(300)` y límite práctico. |
| 3 | `FormacionImportService.php` ~L79–108 | Archivo solo con headers (0 filas válidas, sin errores) ejecuta replace-all y deja la tabla vacía. Confirmación UI mitiga, pero es wipe fácil. | Rechazar si `count($rows) === 0` tras parse (mantener dataset). |
| 4 | `FormacionBoardAccessTest.php` | No hay tests HTTP de sidebar con/sin `view.board.gestion_humana.formacion` (sí hay cableado en `NavigationResolver` / `SidebarVisibilityService`). | Añadir smoke como en Acreditaciones. |
| 5 | `FormacionDatatableService.php` ~L94–99 | `recordsTotal` cuenta sobre query ya filtrada por GET; el protocolo DT suele separar total global vs filtrado. Contador UI usa `recordsFiltered` (OK). | Alinear con patrón Cursos si se unifica semántica. |
| 6 | Docs | `docs/modules/formacion.md`, `docs/user/formacion.md`, ACCESS/INDEX pendientes (alcance Documentador). | Documentador tras este review. |
| 7 | Tests import | No hay assert explícito de que falte `confirm_replace` → 422; la regla `accepted` sí está en `ImportFormacionRequest`. | Test de cobertura opcional. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — `FormacionAccessService`: board / view / edit; `edit` ⇒ `view`; bypass `manage.users`
- [x] Sin registro publico ni bypass de middleware — rutas en `gestion_humana.php` bajo `auth`+`active` (vía `web.php`) + `password.changed`
- [x] Validacion de entradas (Form Requests) — `ImportFormacionRequest` (mimes, max, `confirm_replace`)
- [x] Sin duplicacion innecesaria — patrón espejo Cursos / Acreditaciones
- [x] Rutas en archivo de modulo/area correcto — no tocar `web.php`
- [x] Migraciones compatibles con hosting compartido — create multi-driver amigable (tipos estándar; índices OK en sqlite tests)
- [x] Export Excel usa `BaseExport` + `<x-export-excel>` — sin `excelHtml5`
- [x] Tests relevantes presentes — 37 passed (164 assertions)
- [x] DataTables `serverSide: true` — clase `js-formacion-formaciones-datatable`; `lengthMenu` sin `-1`; tope 100 en servidor
- [x] `<x-searchable-select>` en filtros listado y dashboard — sin Select2
- [x] Import replace-all: validate → `delete()` + chunk `insert()` — **no** TRUNCATE / `migrate:fresh`
- [x] Auditoría `import_replace` / `export` vía `FormacionAuditLogService`
- [x] Chrome `.module-tab` + icon buttons estándar

## Seguridad

- Autorización en controller (`abort_unless` / FormRequest `authorize`) alineada al brief.
- Mutación única (import) protegida por `formacion.edit` + checkbox `confirm_replace` + CSRF.
- Validación de headers/filas **antes** del delete (tests de no-wipe OK).
- Audit metadata con conteos, sin volcar PII masiva.
- Sin hallazgos de bypass auth ni exposición pública.

## Consistencia con AGENTS.md y docs

- Permisos estilo Cursos en `config/access.php` + Admin subgroup Formación.
- Shared-files tocados de forma coherente con T1 (access, audit, nav, User, rutas GH).
- Convenciones: BaseExport, searchable-select, DT server-side, audit central, protección de datos (no wipe destructivo de tooling).
- Documentación de módulo/usuario pendiente del Documentador (esperado en este punto del flujo).

## Resultado de tests (Revisor)

```text
php artisan test --compact
  tests/Feature/GestionHumana/FormacionBoardAccessTest.php
  tests/Feature/GestionHumana/FormacionFormacionesTest.php
  tests/Feature/GestionHumana/FormacionImportTest.php
  tests/Feature/GestionHumana/FormacionDashboardTest.php

Tests:    37 passed (164 assertions)
Duration: ~246s
Exit:     0
```

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)

**Acción AgentSj:** lanzar Documentador (`docs/modules/formacion.md`, `docs/user/formacion.md`, ACCESS/INDEX). Observaciones 1–3 pueden ir a backlog post-cierre o hotfix menor; no bloquean V1.
