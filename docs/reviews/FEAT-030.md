# Review Report — FEAT-030

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-030.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-030 |
| Fecha | 2026-08-28 |
| Revisor | Agente Revisor |
| Brief | [`docs/briefs/FEAT-030.md`](../briefs/FEAT-030.md) |
| Alcance revisado | Migracion `purchase_request_attachments`, modelos, `PurchaseRequestAttachmentService`, store/resubmit/download, Form Requests, import legacy, config, rutas, create/edit/show, JS, tests Attachment + Audit |
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
| 1 | `tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php` | El tope combinado keep + nuevos ≤ 5 esta implementado en `UpdatePurchaseRequestRequest::after()`, pero no hay test dedicado que envie p. ej. 5 keep + 1 archivo nuevo y espere 422. | Añadir un caso 422 en una limpieza posterior; no bloquea el cierre. |
| 2 | `PurchaseRequestAttachment::booted` (`deleting`) vs FK `cascadeOnDelete()` | El hook borra el fichero en disco cuando el adjunto se elimina por Eloquent (resubmit: verificado en tests). Si se borra la **solicitud** a nivel SQL/FK, el cascade no dispara el evento y pueden quedar huerfanos en `storage/app/private/purchase-requests/`. | Aceptable en v1 (el brief ya documenta orphan files). Si mas adelante hay borrado de solicitudes, borrar adjuntos por Eloquent antes del padre. |
| 3 | `resources/js/purchase-request-form.js` | Lista nombres y quita `keep_attachment_ids[]`; no limita en cliente keep + seleccionados ≤ 5. El servidor rechaza el exceso. | Opcional: deshabilitar el file input o avisar en UI si se supera el tope. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — download con `Gate::authorize('view')`; sin permiso nuevo; policy `view` / `resubmit` existentes
- [x] Sin registro publico ni bypass de middleware — download en el mismo grupo `auth` + `active` + `password.changed` que `show`; guest → login
- [x] Validacion de entradas (Form Requests) — `attachments` nullable, max 5, mimes/tamaño; resubmit `keep_attachment_ids.*` exists scoped + `after()` tope combinado
- [x] Sin duplicacion innecesaria — servicio unico para store/sync/legacy; download en el controlador (como Calidad)
- [x] Rutas en archivo de modulo/area correcto — `routes/modules/purchase-requests.php`; **no** `web.php`; **no** `purchase-requests-email.php`
- [x] Migraciones compatibles con hosting compartido — tabla nueva + backfill + NULL de columna legado; **sin DROP** de `archivo_pedido_path`; `down()` solo `dropIfExists` de la tabla nueva; sin `migrate:fresh`
- [x] Export Excel usa `BaseExport` si aplica — no aplica (FO-AD-44 / Excel de solicitud no se tocaron)
- [x] Tests relevantes presentes o justificados — 16 casos minimos del brief + audit `attachments_count`; **22 passed (139 assertions)**

## Ley de negocio (verificacion puntual)

| # | Ley | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Adjuntos **opcionales**; store/resubmit con 0 archivos | OK | Form Requests `nullable` (nunca `required`); inputs file sin `required`; hint «Opcional.»; tests store 0 y resubmit quitar todos |
| 2 | Por toda la solicitud, no por linea | OK | Cabecera create/edit despues de la tabla de productos; tabla 1:N `purchase_request_id`; sin columna en items |
| 3 | Solo detalle autenticado: NO correo, NO FO-AD-44, NO email-approval | OK | Mail/PDF/guest **no** modificados; mail sigue 1 MIME PDF; guest `assertDontSee` nombres y «Adjuntos»; PDF Blade sin bloque adjuntos |
| 4 | Sin permiso nuevo; download = policy `view` | OK | `config/access.php` intacto; `downloadAttachment` → `Gate::authorize('view', $purchaseRequest)` |
| 5 | Disco `local` privado, no URL publica | OK | `config/purchase-requests.php` `disk => local` (`storage/app/private`); `Storage::download`; show usa named route; test `assertDontSee('/storage/purchase-requests')` |
| 6 | `archivo_pedido_path` deprecada (no DROP); no leer/escribir en store/resubmit | OK | Fuera de `$fillable`; store/resubmit no la tocan; migracion backfill + `UPDATE … SET NULL`; UI no la lee; import escribe 1:N via `recordMappedLegacy` |
| 7 | Foto por linea intacta | OK | `items.*.foto` sigue `public` / max 5120; test dedicado; partial de foto no tocado |

## Puntos de busqueda pedidos

| # | Chequeo | Resultado |
| --- | --- | --- |
| 1 | Auth/IDOR download (`scopeBindings`, 403 vs 404) | OK. Ruta con `scopeBindings()`; IDOR (adjunto de otra solicitud) → 404; sin `view` → 403; archivo ausente en disco → 404 tras authorize |
| 2 | Download **no** bajo `purchase.tab:my_requests` | OK. Junto a `show` / pdf / excel, fuera de tabs. Tests: director (`compras`) y processing descargan 200 |
| 3 | Input file sin `required` | OK. `create.blade.php` y `edit.blade.php`: `multiple` + `accept`, sin `required` |
| 4 | Tope keep + nuevos ≤ 5 en resubmit | OK en codigo (`after()`). Sin test dedicado (obs. 1) |
| 5 | Borrado de ficheros al quitar | OK en resubmit Eloquent `delete()` + hook `deleting`; tests `assertMissing`. Cascade SQL del padre: obs. 2 |
| 6 | Audit sin paths | OK. Metadata `attachments_count` entero; tests niegan `stored_path` / `original_name` en el JSON del log |
| 7 | Mail/PDF/guest sin adjuntos de pedido | OK. Archivos de mail/PDF/email-approval no estan en el diff; tests mail + guest |
| 8 | shared-files no tocados | OK. `git status`: no `config/access.php`, no `routes/web.php`, no layouts, no `app.css`, no seeders globales |
| 9 | `migrate:fresh` no usado | OK. Solo migracion incremental nueva. `RefreshDatabase` limitado a PHPUnit |

## Seguridad

- **Auth:** `GET …/adjuntos/{attachment}` en el mismo middleware que el detalle (`auth`, `active`, `password.changed`). Guest redirige a login.
- **Autorizacion:** `PurchaseRequestPolicy::view` (dueño, director asignado con `purchase.tab.approval`, `purchase.tab.processing`, bypass `manage.users`). Sin permiso nuevo.
- **IDOR:** `scopeBindings()` resuelve `{attachment}` via relacion `attachments()` del padre; mismatch → 404 **antes** de authorize. Usuario sin `view` sobre la solicitud correcta → 403.
- **Storage:** disco `local` (`storage/app/private`); nombre en disco UUID; `original_name` sanitiza `\` `/`; descarga con `Content-Disposition` del nombre original. No `Storage::url`.
- **Uploads:** max 5, 10 MB, mimes alineados a config; `keep_attachment_ids` scoped a la solicitud de la ruta.
- **Audit:** `create` / `resubmit` con `attachments_count`; sin paths ni nombres.
- **database-safety:** `php artisan migrate` incremental; no fresh/wipe/DROP de `purchase_requests`. Tests: `RefreshDatabase` solo en PHPUnit.

## Consistencia con AGENTS.md y docs

- Vertical slice en el modulo `purchase-requests` (controlador, vistas, rutas de modulo, config propia).
- Sin Select2; sin entry Vite nueva (mismo `purchase-request-form.js`).
- `<x-searchable-select>` no aplica (no hay select nuevo).
- Patron de descarga alineado a `QualityDocumentController::streamFileDownload`.
- Docs `docs/modules/purchase-requests.md` y `docs/user/purchase-requests.md` **pendientes del Documentador** (el Feature no debe editarlos).
- `docs/TASKS.md`: fase Feature T1 implementado; shared-files vacio (correcto).

## Tests ejecutados (Revisor)

```
php artisan test --compact tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php
```

**22 passed (139 assertions).** Cubren los 16 casos minimos del brief (0/N adjuntos, 6 archivos, tipo/tamaño, download dueño/director/compras, 403, IDOR 404, guest, keep/quitar/agregar, mail PDF, guest approval, foto `public`, audit, show sin bloque).

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones no bloqueantes)
- [ ] Devolver a Agente Feature (si bloqueado)

**Señal al AgentSj:** `APROBADO_OBSERVACIONES`

El Documentador debe actualizar `docs/modules/purchase-requests.md` y `docs/user/purchase-requests.md` (1:N, disco local, ruta download, deprecacion de `archivo_pedido_path`, opcionales, que no viajan en correo/FO/guest). Las observaciones 1–3 pueden quedar como deuda v1 o limpieza post-cierre; no exigen reabrir el Feature.
