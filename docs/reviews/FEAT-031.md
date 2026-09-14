# Review Report — FEAT-031

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-031.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-031 |
| Fecha | 2026-09-14 |
| Revisor | Agente Revisor |
| Brief | [`docs/briefs/FEAT-031.md`](../briefs/FEAT-031.md) |
| Plan | [`docs/briefs/FEAT-031-plan.md`](../briefs/FEAT-031-plan.md) |
| Alcance revisado | T1–T3: `config/access.php`, `config/audit.php`, `PermissionCatalog`, Access/Nav/User, migracion + modelo followups, Masivos (servicios, Form Requests, controller, vista, ZIP token), hooks Ficha/Letter, `closeActivePeriod` nullable, Seguimientos (datatable, PATCH, vista, OK TODO), tests Board/Masivos/Seguimientos |
| Veredicto | **Aprobado con observaciones** |
| **Blockers** | **0** |
| **Señal AgentSj** | `APROBADO_OBSERVACIONES` |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `EmployeeTerminationFollowupService::ensureForClosedPeriod` | Brief sugiere audit `termination_followup` action `create`. Hoy solo se audita `update` (PATCH) y `bulk_termination` (lote). Altas desde Ficha individual no dejan evento `create` en modulo `desvinculaciones`. | Documentar en doc usuario/tecnica que el alta se cubre por audit de Ficha + lote; o añadir `logEvent`/`logModelChange` create en el servicio (post-V1 o cleanup). |
| 2 | `DesvinculacionesController::downloadZip` + tests | ZIP: token UUID, TTL 900s, `Cache::pull` (un solo uso), check `user_id` + `authorizeMasivos`. No hay test IDOR (usuario B con token de A → 403). | Añadir un caso en limpieza; el codigo ya lo protege. |
| 3 | `ProcessBulkTerminationRequest` | `template_id` solo `exists:termination_letter_document_templates,id`. No restringe tipo `desvinculacion` ni archivo en disco (la UI si filtra). | Opcional: `Rule::exists` scoped a tipo + path; o validar en servicio. |
| 4 | `masivos.blade.php` partial `alpine-searchable-select` | No usa el Blade `<x-searchable-select>`; replica markup/CSS/Alpine del componente para filas `x-for`. **Select2 no reintroducido.** | Aceptable para grilla dinamica; Documentador debe dejar claro el patron. Preferible alinear con componente si se unifica Alpine.data. |
| 5 | `masivos.blade.php` `processBatch` | Tras exito limpia grilla y hace `window.location.href = downloadUrl`. El token se consume en el primer GET; el boton «Descargar ZIP» con la misma URL falla en un segundo intento. | Preferir `<a download>` / fetch blob sin navegar, o no hacer `pull` hasta confirmar descarga; documentar one-shot. |
| 6 | Tests bypass | Brief pide «`manage.users` accede y procesa». Hay test de acceso al tablero; no hay test explicito de `process` solo con `manage.users`. | Cubierto por `canMasivos` bypass en AccessService; test opcional. |
| 7 | Docs modulo/usuario | Brief marca docs `desvinculaciones` + notas ficha/ACCESS/INDEX para el Documentador. Correcto que no esten en T1–T3. | Documentador tras este review. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — `DesvinculacionesAccessService`; Masivos **no** exige `ficha_empleados.terminate` (probado con `masivosUser()`); Ficha individual sigue con terminate; bypass `manage.users`
- [x] Sin registro publico ni bypass de middleware — rutas bajo `web.php` grupo `auth` + `active` + `password.changed` del area GH
- [x] Validacion de entradas (Form Requests) — lookup, process (duplicados cedula, opcionales causal/rehire/notas), PATCH parcial followup
- [x] Sin duplicacion innecesaria — orquestador `BulkTerminationService` reutiliza period + letter pack + followup service; sin Repository
- [x] Rutas en archivo de modulo/area correcto — `routes/areas/gestion_humana.php`; sin tocar `web.php` salvo require ya existente
- [x] Migraciones compatibles con hosting compartido — create incremental, FK con nombres cortos, unique periodo; **sin** `migrate:fresh` / wipe
- [x] Export Excel usa `BaseExport` si aplica — N/A (fuera V1)
- [x] Tests relevantes presentes o justificados — **28 passed (112 assertions)** en Board + Masivos + Seguimientos (re-ejecutados en revision)

## Criterios de aceptacion (brief)

| # | Criterio | Estado |
| --- | --- | --- |
| 1 | Sidebar Desvinculaciones + board permission / bypass | OK — NavigationResolver + SidebarVisibility + AccessService |
| 2 | Tabs Masivos / Seguimientos labels exactos | OK — `desvinculaciones_tabs` + subnav |
| 3 | Lookup activo / inactivo | OK — servicio + tests |
| 4 | Lote parcial no aborta | OK — try/catch por fila + test |
| 5 | Exitoso aparece en Seguimientos | OK — followup creado + snapshots |
| 6 | FECHA unica → `last_work_day` + `termination_date` | OK — assert en test parcial |
| 7 | Causal/rehire/notas opcionales Masivos; RO en Seguimientos | OK — Form Request + UI |
| 8 | Carta fail → desvinculado + `letter_generated=false` + reporte | OK — test mock generate |
| 9 | ZIP + grilla limpia | OK — token/ZIP + JS limpia filas |
| 10 | Terminate Ficha crea followup | OK — hook + test |
| 11 | Generate letter Ficha marca flag | OK — hook + test |
| 12 | OK TODO calculado 8 checks | OK — accessor + test |
| 13 | Autosave + 403 sin edit | OK — debounce 400 ms + tests |
| 14 | Masivos sin `ficha_empleados.terminate` | OK — `masivosUser` sin ese permiso |
| 15 | Sin colores / Excel / correo | OK |
| 16 | Auditoria lote + updates seguimiento | OK parcial — falta `create` followup (obs. 1) |
| 17 | Tests en verde | OK — 28 passed |

## Seguridad

- **Auth:** grupo autenticado GH (`auth`, `active`, `password.changed`). Sin registro publico.
- **Permisos:** gates por Form Request / `authorizeView` / `authorizeMasivos`. Paquete separado de Ficha; Masivos independiente de `ficha_empleados.terminate`.
- **ZIP:** token opaco en cache atado a `user_id`; one-time pull; descarga solo con `canMasivos`; archivo temporal borrado tras send.
- **Validacion:** duplicados de cedula rechazan el lote (422) antes de procesar; TX por empleado.
- **database-safety:** solo migracion create; tests con `RefreshDatabase` en PHPUnit.
- **Select2:** no presente en vistas del modulo.

## Consistencia con AGENTS.md y docs

- Area GH desacoplada (controllers/views/routes de area).
- Nav chrome: `module-tab` / `module-subnav` alineado a branding.
- Audit wrapper `DesvinculacionesAuditLogService` + `config/audit.php`.
- `PermissionCatalog` evita board fuera de `gestion_humana`.
- Documentacion tecnica/usuario del modulo: pendiente Documentador (esperado).

## Siguiente paso

- [x] Pasar a Documentador (si aprobado) — **Aprobado con observaciones**; AgentSj puede lanzar Documentador
- [ ] Devolver a Agente Feature (si bloqueado) — no aplica
