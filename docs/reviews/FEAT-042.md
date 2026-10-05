# Review Report — FEAT-042

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-042.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Fecha | 2026-10-05 |
| Alcance revisado | T1–T5 (shell/permisos/migración, Catálogos, Solicitudes CRUD/DT/export/días hábiles, masivo B, Dashboard KPIs) — `ClienteInterno*`, `config/access.php`, `config/audit.php`, `config/cliente_interno.php`, rutas GH, nav, vistas, Vite charts, tests Feature |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `ClienteInternoImportService.php` `mapValidatedRow` | El import no valida formato de correo cuando viene informado (regla 14 del brief: si presente → email). CRUD sí usa `email` en Form Request. | Validar con `filter_var(..., FILTER_VALIDATE_EMAIL)` y rechazar fila (import completo abortado, sin wipe). |
| 2 | `ClienteInternoImportService.php` ~L102–103 | Lookup de catálogos carga **todos** los ítems (incl. `is_active=false`). Alta manual exige tipo/estado activos. | Filtrar activos en import, o documentar que match por code/name incluye inactivos. |
| 3 | `ClienteInternoController.php` `solicitudesExport` / `ClienteInternoExport` | Export hace `filteredQuery()->get()` y materializa toda la colección. Filtro amplio puede tensionar memoria/timeout en Hostinger. | Chunked/`fromQuery` o tope + aviso; documentar límite en doc de módulo. |
| 4 | `ClienteInternoController.php` | Un solo controller concentra shell + solicitudes + catálogos + import + dashboard (~580 líneas). Brief permitía CatalogController opcional. | Split opcional post-V1 si crece; no bloquea entrega. |
| 5 | `ClienteInternoDatatableService.php` ~L99 | Si `length === -1`, no aplica `take()` (mismo patrón que Formación/Cursos). UI `lengthMenu` sin «Todos»; tope 100 cuando hay length positivo. | Endurecer: tratar `-1` como 100 (como Validaciones) si se unifica política. |
| 6 | Docs | `docs/modules/cliente-interno.md`, `docs/user/cliente-interno.md`, ACCESS/INDEX/ARCHITECTURE pendientes (alcance Documentador). | Documentador tras este review. |
| 7 | Tests import | No hay assert HTTP explícito de que falte `confirm_replace` → 422; la regla `accepted` sí está en `ImportClienteInternoSolicitudesRequest`. | Cobertura opcional. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — `ClienteInternoAccessService`: board; `solicitudes.view`/`edit` (`edit` ⇒ `view`); `parameters.edit`; Dashboard = view∨parameters; bypass `manage.users`
- [x] Sin registro publico ni bypass de middleware — rutas en `gestion_humana.php` bajo `auth`+`active` (vía `web.php`) + `password.changed`
- [x] Validacion de entradas (Form Requests) — store/update solicitud y catálogo; import (`extensions`, max, `confirm_replace`)
- [x] Sin duplicacion innecesaria — patrón espejo Formación / Selección / Comercial
- [x] Rutas en archivo de modulo/area correcto — no tocar `web.php`
- [x] Migraciones compatibles con hosting compartido — create aditivo multi-driver (tipos estándar; FKs; sin `enum`/`change`); seed ESTADO upsert; SOLICITUD vacío
- [x] Export Excel usa `BaseExport` + `<x-export-excel>` — sin `excelHtml5`; plantilla import es headers-only (aceptable)
- [x] Tests relevantes presentes — 52 passed (256 assertions) en suite `ClienteInterno*`
- [x] DataTables `serverSide: true` — tope 100; `lengthMenu` sin `-1`; iconos fila estándar
- [x] `<x-searchable-select>` en filtros, formularios, import y dashboard — sin Select2
- [x] Masivo opción B: validar → errores → **sin DELETE**; OK → `DELETE WHERE anio+mes` + INSERT todas (spillover) en transacción — **no** TRUNCATE / `migrate:fresh`
- [x] Días hábiles lun–vie + `dias_respuesta_manual` (create/update + UI touched/recalcular + import vacío=auto / número=manual)
- [x] Catálogos DELETE bloqueado con refs; desactivar OK
- [x] Auditoría create/update/delete/import/export vía `ClienteInternoAuditLogService`
- [x] Chrome `.module-tab` + icon buttons `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn`
- [x] Shared-files coherentes con brief (T1: access/audit/nav/User/rutas; T5: Vite entry charts)

## Seguridad

- Autorización en controller (`abort_unless`) y FormRequest `authorize` alineada al brief Propuesta A.
- Mutaciones (CRUD, import, catálogos) gated por `edit` / `parameters.edit`; consulta/export por `view` (o implicación edit).
- Import: confirmación UI + `confirm_replace` + conteo periodo; validación completa **antes** del DELETE; tests no-wipe OK.
- Audit metadata con conteos (import_replace_period, export); sin volcar PII masiva.
- Sin hallazgos de bypass auth ni exposición pública.

## Consistencia con AGENTS.md y docs

- Permisos view/edit por pestaña + `parameters.edit`; Dashboard sin permiso KPI aparte (view OR parameters).
- Labels Admin legibles; `administrador`/`usuario` sin paquete por defecto (solo sync → super-admin).
- Migraciones solo aditivas; tests con `RefreshDatabase` (sqlite); sin fresh/wipe operativo.
- Convenciones: BaseExport, searchable-select, DT server-side, ApexCharts entry, audit central.
- Documentación de módulo/usuario pendiente del Documentador (esperado en este punto del flujo).

## Resultado de tests (Revisor)

```text
php artisan test --compact
  tests/Feature/GestionHumana/ClienteInternoBoardAccessTest.php
  tests/Feature/GestionHumana/ClienteInternoCatalogosTest.php
  tests/Feature/GestionHumana/ClienteInternoSolicitudesTest.php
  tests/Feature/GestionHumana/ClienteInternoImportTest.php
  tests/Feature/GestionHumana/ClienteInternoDashboardTest.php

Tests:    52 passed (256 assertions)
Duration: ~73s
Exit:     0
```

Nota: el glob `tests/Feature/GestionHumana/ClienteInterno` no resolvió como directorio; se ejecutaron los 5 archivos `ClienteInterno*.php` explícitamente.

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones)
- [ ] Devolver a Agente Feature (si bloqueado)

Observaciones 1–2 (email + catálogos inactivos en import) son mejoras recomendadas para un hotfix corto si AgentSj lo prioriza; **no** bloquean Documentador.
