# Modulo Firmas manuscritas de usuario

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/user-signatures.md`.

## Objetivo

Cada usuario sube en **Mi perfil** un PNG de su firma manuscrita. El archivo queda en disco **privado**. Las vistas reutilizan `<x-user-signature>`. El PDF FO-AD-07 copia un **snapshot** al autorizar la OC. El FO-GH-22 impreso pinta la firma vigente del usuario en cada casilla cuando ya corresponde.

## Alcance actual

- Subida / reemplazo / borrado solo por el dueño de la cuenta (`POST|DELETE /profile/signature`)
- PNG, máximo 2 MB, disco `local` (`storage/app/private/user-signatures/{userId}/`)
- Componente Blade `<x-user-signature :user="$user" />` o `:src` (data URI)
- Snapshot al **autorizar** una OC: cuatro casillas del FO-AD-07 (elaborado, revisor, aprobado, gerente)
- FO-GH-22 (`requisitions.print`): Líder = `requested_by`; Dirección GH Recibió = `managed_by` (o quien pasó a `en_gestion`); Aprobación Gerencia = usuario `gerencia@sjsp.com.co` **solo si** gerencia ya autorizó (cargo nuevo)
- Casilla Gerente / Gerencia = `config('user-signatures.gerente_email')` (`gerencia@sjsp.com.co`)
- Sin imagen: casilla en blanco
- **Fuera de alcance:** Excel de OC, FO-AD-44, cartas Word `${FIRMA_IMAGEN}`, carga por Admin

No es firma criptográfica (certificado / PAdES). Distinto del catálogo GH `payroll_catalog_items` `catalog_type=firmas` (nombre + cargo en texto para cartas).

## Rutas

| Metodo | URI | Nombre | Middleware / permiso |
| --- | --- | --- | --- |
| GET | `/profile` | `profile.edit` | `auth`, `active` |
| POST | `/profile/signature` | `profile.signature.update` | `auth`, `active` |
| DELETE | `/profile/signature` | `profile.signature.destroy` | `auth`, `active` |

Sin claves Spatie nuevas: cualquiera autenticado gestiona **su** firma.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `ProfileController` | Vista de perfil; `updateSignature` / `destroySignature` |
| `UpdateUserSignatureRequest` | `signature` required, `File::image()->types(['png'])->max(2048)` |

## Servicios

| Clase | Responsabilidad |
| --- | --- |
| `App\Services\Users\UserSignatureService` | Guardar, borrar, `dataUri`, copiar a destino de snapshot, `gerenteUser()` |
| `App\Services\PurchaseRequests\PurchaseOrderSignatureSnapshotService` | `capture()` al autorizar; `dataUris()` para el PDF |
| `App\Services\Requisitions\RequisitionPrintSignatureService` | Data URI de las 3 casillas del FO-GH-22 |

## Vistas y componente

| Pieza | Uso |
| --- | --- |
| `resources/views/profile/partials/update-signature-form.blade.php` | Formulario en Mi perfil |
| `resources/views/components/user-signature.blade.php` | `<x-user-signature :user="$user" />` o `:src` |
| `resources/views/pdf/purchase-order.blade.php` | Cuatro casillas con snapshot (`:src`, PNG 52px / casilla 72px) |
| `resources/views/modules/requisitions/print.blade.php` | Tres casillas FO-GH-22 (`:src`, PNG 50px / casilla 74px) |

## Modelos y tablas

| Columna | Tabla | Notas |
| --- | --- | --- |
| `signature_path` | `users` | Ruta relativa en disco `local`; nullable |
| `signature_snapshots` | `purchase_orders` | JSON `{elaborado, revisor, aprobado, gerente}` cada uno `{user_id, path}` |

Migraciones: `2026_11_13_083005_add_signature_path_to_users_table`, `2026_11_13_083006_add_signature_snapshots_to_purchase_orders_table`.

## Configuracion

- `config/user-signatures.php`: `disk`, `directory`, `snapshot_directory`, `max_kilobytes`, `gerente_email`
- `config/purchase-orders.php` → `gerente_email` (mismo default / `PURCHASE_ORDER_GERENTE_EMAIL`)

## Reglas de negocio

1. Solo el usuario carga o quita su firma en Mi perfil.
2. El PDF de la OC muestra imagen **solo si la OC está autorizada** y hay snapshot de ese slot.
3. El snapshot se toma en `PurchaseOrderApprovalService::resolve()` cuando `$approve === true`.
4. Si el usuario cambia o borra su PNG después, las OC ya autorizadas conservan la copia.
5. OC históricas (`aprobacion_requerida = false` o autorizadas antes de esta feature) no tienen snapshot: casillas sin imagen.
6. Gerente: lookup por email configurado (incluye inactivos). Si no hay usuario o no hay PNG, casilla sin imagen; en la OC el nombre se imprime si el usuario existe.
7. FO-GH-22: plantilla vacía sin firmas. Gerencia solo si hay log de autorización cargo nuevo (`pendiente_autorizacion_gerencia` → `solicitada`). GH solo si hay `managed_by` o log a `en_gestion`.

## Auditoria

Modulo `admin`: `user_signature` / `uploaded` | `deleted`. Ver [`audit-log.md`](audit-log.md).

## Pruebas

- `tests/Feature/ProfileTest.php` — PNG, rechazo JPG, borrar
- `tests/Feature/PurchaseRequests/PurchaseOrderApprovalTest.php` — snapshot en PDF y congelado tras borrar la firma viva
- `tests/Feature/RequisitionModuleTest.php` — FO-GH-22 líder/GH/gerencia y casillas en blanco

## Referencias

- Guia de usuario: [`docs/user/user-signatures.md`](../user/user-signatures.md)
- OC / FO-AD-07: [`docs/modules/purchase-requests.md`](purchase-requests.md)
