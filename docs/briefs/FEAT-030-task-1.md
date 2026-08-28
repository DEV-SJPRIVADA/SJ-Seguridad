# Task Card — FEAT-030 / Tarea 1

> Emitida por el AgentSj al Agente Feature. Una tarjeta = vertical slice completo.

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-030 |
| Tarea # | 1 |
| Modulo / area | purchase-requests |
| Rama Git | feat/FEAT-030-purchase-request-attachments |
| Brief | `docs/briefs/FEAT-030.md` |

## Objetivo de esta tarea

Implementar adjuntos multiples a **nivel de solicitud**: persistencia 1:N en disco `local`, carga en crear/reenviar, lista y descarga en detalle autenticado. Sin permiso nuevo. Sin correo, FO-AD-44 ni guest.

## Archivos permitidos (scope lock)

- `database/migrations/` (nueva migracion `purchase_request_attachments` + backfill)
- `app/Models/PurchaseRequest.php`, `app/Models/PurchaseRequestAttachment.php` (nuevo) + factory
- `app/Services/PurchaseRequests/PurchaseRequestAttachmentService.php` (nuevo)
- `app/Services/PurchaseRequests/PurchaseRequestResubmitService.php`
- `app/Http/Controllers/PurchaseRequests/PurchaseRequestController.php`
- `app/Http/Requests/PurchaseRequests/StorePurchaseRequestRequest.php`
- `app/Http/Requests/PurchaseRequests/UpdatePurchaseRequestRequest.php`
- `app/Console/Commands/ImportLegacyPurchaseRequestsCommand.php`
- `config/purchase-requests.php`
- `routes/modules/purchase-requests.php`
- `resources/views/modules/purchase-requests/create.blade.php`
- `resources/views/modules/purchase-requests/edit.blade.php`
- `resources/views/modules/purchase-requests/show.blade.php`
- `resources/js/purchase-request-form.js`
- `tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php` (nuevo)
- `tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php` (solo `attachments_count` si aplica)
- `tests/Feature/PurchaseRequestModuleTest.php` (solo si un test existente se rompe por quitar `archivo_pedido`)
- `docs/TASKS.md` (fase Feature T1, si AgentSj lo pide)

## Archivos prohibidos (salvo autorizacion AgentSj)

- `config/access.php`
- `routes/web.php`
- Layouts globales, `resources/css/app.css`
- Seeders globales
- `docs/modules/` y `docs/user/` (Documentador)
- Vistas mail, PDF FO-AD-44, `email-approval.blade.php` (salvo assertDontSee en tests)
- `purchase_request_items` / partial de foto

## Entregables

- [ ] Codigo segun brief FEAT-030
- [ ] Tests minimos del brief (archivo AttachmentTest + audit count)
- [ ] `php artisan migrate` incremental (**no** fresh)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] Actualizar fase en `docs/TASKS.md` a Feature T1 implementado

## Criterios de done

1. Crear con 0, 1 y N adjuntos (≤5); 6 o tipo/tamaño invalido → 422.
2. Detalle lista y descarga (policy `view`); 403 sin view; 404 IDOR.
3. Disco `local`; no URL `/storage/...`.
4. Resubmit: keep / quitar / agregar con tope combinado 5.
5. Mail solo PDF FO-AD-44; guest approval no lista Adjuntos.
6. `archivo_pedido_path` no se escribe; backfill en migracion; UI no la lee.
7. Foto por linea sin regresion.
8. Audit `attachments_count` sin paths.

## Al cerrar

Reportar al AgentSj:

- Archivos modificados
- Pendientes para Revisor
- Blockers (si `php artisan migrate` o tests fallan)
