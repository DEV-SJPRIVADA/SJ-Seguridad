# Review Report — FEAT-029

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-029.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Fecha | 2026-08-21 |
| Revisor | Agente Revisor |
| Brief | [`docs/briefs/FEAT-029.md`](../briefs/FEAT-029.md) |
| Alcance revisado | T1–T5: `config/access.php`, Navigation/PermissionCatalog/User/seeders, migraciones `word_document_types` + alter templates, `PlantillasWord*`, `TerminationLetter*` (controller/service/request/UI modal), retiro catalog Causal, tests Feature GestionHumana |
| Veredicto | **Aprobado con observaciones** |
| **Blockers** | **0** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `resources/views/.../termination-letter-templates-admin.blade.php`, `UploadTerminationLetterTemplateRequest.php` | Codigo muerto FEAT-027: partial y Form Request ya no referenciados; el partial apunta a rutas/columnas eliminadas. | Eliminar en limpieza post-feature (o Documentador anotar deuda). |
| 2 | `UpdateWordDocumentTypeRequest` / UI tipos | El `code` del tipo `desvinculacion` es editable; si se renombra, Generar deja de encontrar plantillas (filtro por config + code). | Preferir bloquear edicion de `code` en tipos seed/usados, o documentar riesgo operativo. |
| 3 | `PlantillasWordController::index` | Solo exige `canView` (view\|manage\|bypass), no `view.board…`. Alineado a Archivo; CA1 habla del sidebar. | Aceptable; opcional endurecer index con board permission. |
| 4 | Migracion alter templates | `down()` no restaura filas/archivos RENUNCIA (documentado). `->change()` NOT NULL asume soporte nativo Laravel 13 (tests OK). | Sin accion; no usar fresh. |
| 5 | Docs modulo/usuario | Brief exige `docs/modules/plantillas-word.md`, update ficha, INDEX, ACCESS_CONTROL. | Responsabilidad del **Documentador** (siguiente paso). |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — rutas bajo `auth`+`active`; tablero `plantillas_word.*` + board; cartas solo `ficha_empleados.terminate`
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests) — tipos, docx `mimes:docx` max 5MB, `template_ids` min 1 + exists; generador revalida tipo `desvinculacion` y archivo en disco
- [x] Sin duplicacion innecesaria — reutiliza servicios FEAT-027; packs/causas soportadas retirados de config
- [x] Rutas en archivo de area correcto (`routes/areas/gestion_humana.php`); rutas catalog plantillas eliminadas (404)
- [x] Migraciones incrementales (create + alter + cleanup + seed tipo); sin `migrate:fresh` / wipe
- [x] Export Excel: no aplica
- [x] Tests relevantes presentes y verdes: **29 passed (171 assertions)** — `PlantillasWordBoardAccessTest`, `PlantillasWordCrudTest`, `WordDocumentTypeSchemaTest`, `TerminationLetterPackTest`

## Criterios de aceptacion (brief)

| # | Criterio | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Sidebar Plantillas Word con board (o bypass) | OK | `NavigationResolver` + `SidebarVisibilityService` + `PlantillasWordAccessService` |
| 2 | manage: CRUD tipos/plantillas, replace solo docx, delete con confirm, download master | OK | Controller + Form Requests + vista index (`confirm`) + tests CRUD |
| 3 | Sin manage no muta; sin board no ve sidebar | OK | Tests viewer 403 en mutaciones; board gate en nav |
| 4 | Tras migrate/seed: tipo `desvinculacion`; sin plantillas legacy RENUNCIA | OK | Migracion create+insert + alter cleanup; `WordDocumentTypeSchemaTest` count 0 |
| 5 | Periodo cerrado cualquier causal: Generar + modal solo desvinculacion con archivo | OK | `canGenerateLetters` sin gate causal; endpoint templates filtra tipo+file |
| 6 | 1→docx / N→zip; Generar reemplaza | OK | `TerminationLetterPackGeneratorService` + tests |
| 7 | Descargar ultimo archivo; sin Regenerar aparte | OK | Actions + test UI sin “Regenerar” |
| 8 | Catalog Causal sin admin plantillas; rutas viejas 404 | OK | Catalog controller/vista limpios; tests 404 + assertSee no admin |
| 9 | Sin terminate → 403 generate/download | OK | Test dedicado |
| 10 | Audit generate/download + mutaciones plantillas/tipos | OK | `EmployeeFichaAuditLogService` + asserts en tests |
| 11 | Suite PlantillasWord / TerminationLetter / schema en verde | OK | 29 tests / 171 assertions |

## Seguridad

- **Auth:** grupo GH bajo `Route::middleware(['auth', 'active'])` en `web.php`; grupos con `password.changed`.
- **Permisos tablero:** `PlantillasWordAccessService` (bypass `manage.users`); mutaciones via Form Request `canManage`; download `canView`.
- **Cartas:** solo `FichaEmpleadosAccessService::canTerminate`; generate revalida periodo `cerrado`, IDs existentes, tipo `desvinculacion` (config), archivo presente.
- **Uploads:** `mimes:docx`, max 5120 KB; almacenamiento bajo `ficha-empleados/letter-templates/{typeId}/` con UUID (sin path traversal de usuario).
- **Descargas:** disco `local` privado; 404 si path vacio o archivo ausente.
- **database-safety:** solo migrate incremental; cleanup RENUNCIA borra filas/archivos legacy (acordado en brief); tests usan `RefreshDatabase` (PHPUnit), no fresh en BD de desarrollo.

## Consistencia con AGENTS.md y shared-files

- Modulo por area GH: controlador/vistas/rutas desacoplados.
- `config/access.php`: system_permissions, boards, board_canonical_areas, admin_ui.
- `PermissionCatalog`: reject `plantillas_word` fuera de `gestion_humana`.
- `RoleAndPermissionSeeder`: administrador recibe board + view + manage.
- `config/employee_ficha.php`: packs/supported_causes retirados; `word_document_type_codes.desvinculacion` estable.
- Flag `shared-files` presente en `docs/TASKS.md`.
- Docs tecnica/usuario pendientes → Documentador.

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones no bloqueantes)
- [ ] Devolver a Agente Feature (si bloqueado)
