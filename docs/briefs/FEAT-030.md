# Feature Brief — FEAT-030

> Brief final del Arquitecto (2026-08-28). Consolida `docs/briefs/FEAT-030-analyst.md` (señal `LISTO_ARQUITECTO`) + decisiones de negocio del usuario. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-030 |
| Modulo / area | Solicitudes de compra (`purchase-requests`) |
| Titulo | Adjuntos multiples a nivel solicitud (detalle autenticado; disco privado) |
| Solicitante | Usuario (chat AgentSj 2026-08-28) |
| Fecha | 2026-08-28 |

## Objetivo

Permitir al **solicitante** adjuntar **varios documentos de soporte** (cotizaciones, ordenes, evidencias) a una **solicitud de compra completa**, de forma opcional, para que el **director asignado** y **Compras** los consulten **solo en el detalle autenticado** de la plataforma.

No reemplaza la foto por linea. No viaja en el correo ni en FO-AD-44.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Nivel | Adjuntos **por toda la solicitud** (cabecera). **No** por linea de producto. |
| 2 | Visibilidad | **Solo** detalle autenticado (`show`). **No** correo. **No** FO-AD-44. **No** vista guest `email-approval`. |
| 3 | Permisos | **Sin permiso nuevo.** Ver/descargar = policy `view` existente. |
| 4 | Foto por linea | **No se toca** (`items.*.foto` / disco `public`). |
| 5 | Obligatorio | **Opcional.** Confirmado por el usuario 2026-08-28 al aprobar el brief. Se puede enviar la solicitud **sin** adjuntos. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Modelo de datos | Tabla nueva `purchase_request_attachments` (1:N). Una sola fuente de verdad en UI y codigo. | El campo scalar `archivo_pedido_path` no admite varios archivos. |
| Columna legado | **Conservar** `purchase_requests.archivo_pedido_path` nullable. **No DROP** en esta feature. Migrar datos a 1:N y **dejar de leer/escribir** la columna (quitar de `$fillable` y de Store/Resubmit). Tras copiar, poner la columna en `NULL`. | DROP COLUMN en Hostinger no aporta producto y complica rollback. Import legacy y backups antiguos siguen reconociendo el esquema. Deprecada: nunca mostrarla ni usarla como fallback en UI. |
| Disco | `local` (`storage/app/private`). **No** disco `public`. **No** `Storage::url`. | Mismo patron que Documentos de Calidad (`QualityDocumentController::streamFileDownload`). Las fotos de item siguen en `public`; no copiar ese patron. |
| Path de storage | `{purchase_request_id}/{uuid}.{ext}` bajo directorio `purchase-requests/` en disco `local`. Ejemplo: `purchase-requests/42/9f3a….pdf`. | Aisla archivos por solicitud; nombre opaco (no usar el original en disco). |
| Descarga | `GET` autenticado; `Gate::authorize('view', $purchaseRequest)`; `Storage::disk('local')->download($storedPath, $originalName)`. Scoped binding: el adjunto debe pertenecer a la solicitud (404 si no). | Evita URL adivinable. Quien ya ve el detalle puede descargar. **No** auditar cada descarga. |
| Inputs | Crear: `attachments[]`. Reenviar: `attachments[]` (nuevos) + `keep_attachment_ids[]` (conservar). **Retirar** `archivo_pedido`. | Un control multiple en cabecera; quitar individuales al reenviar igual que se quita una foto de linea (hidden IDs). |
| Servicio | `App\Services\PurchaseRequests\PurchaseRequestAttachmentService` (guardar, sincronizar en resubmit, borrar archivo de disco). Store/Resubmit lo usan; el download permanece en `PurchaseRequestController`. | Evita duplicar disco/path/borrado en controlador, resubmit e import. **No** Repository. |
| Config | Ampliar `config/purchase-requests.php` (modulo propio) con `attachments.max_files`, `max_kilobytes`, `mimes`, `disk`, `directory`. | Form Request, UI hint y servicio leen los mismos topes. **No** es shared-file. |
| Shared-files | **Ninguno.** | Rutas solo en `routes/modules/purchase-requests.php`. No tocar `config/access.php`, `routes/web.php`, layouts ni seeders globales. |
| Slice | **1 Task Card Feature** (vertical). | Cabe en un solo agente: migracion + backend + UI + tests. T2 no se justifica. |
| Migrate | Solo `php artisan migrate` incremental. **Prohibido** `migrate:fresh` / wipe / DROP TABLE de `purchase_requests`. | Regla de proteccion de datos. |

## Alcance

### Incluye

- Control **multiple** de archivos en **Nueva solicitud** y **Reenviar rechazada**, en cabecera (despues de la tabla de productos, antes de Enviar). Etiqueta **Adjuntos**.
- Persistencia 1:N, validacion de tipo/tamaño/cantidad, almacenamiento en disco `local`.
- Lista + descarga en `purchase-requests.show` para quien pasa `PurchaseRequestPolicy::view`.
- Al reenviar: conservar (IDs), agregar nuevos y quitar individuales, tope 5 en total.
- Migracion de valores existentes de `archivo_pedido_path` hacia la tabla 1:N (copia de archivo `public` → `local` si el fichero existe).
- Actualizar `ImportLegacyPurchaseRequestsCommand` para escribir filas 1:N (no la columna deprecada).
- Audit `create` / `resubmit`: metadata `attachments_count` (entero). **Sin** paths ni nombres de archivo.
- Tests (ver seccion Tests minimos).
- Documentacion `docs/modules/purchase-requests.md` y `docs/user/purchase-requests.md` (**Documentador**, no Feature).

### Fuera de alcance

- Adjuntos por linea; cambios a foto de producto.
- Adjuntar archivos al correo del director (MIME o enlaces).
- Incluir adjuntos en FO-AD-44 (PDF/Excel) o en `email-approval.blade.php`.
- Permiso nuevo; indicadores en listados (Mis solicitudes, Pendientes, Bandeja).
- Alta/edicion de adjuntos en solicitudes pendientes, aprobadas o en bandeja (salvo reenvio de **rechazada**).
- Suministros / bandeja de insumos.
- Titulo, descripcion o categoria documental por archivo.
- Visor Office embebido.
- Import masivo de **binarios** desde el servidor legado (solo mapear path/nombre).
- Log de cada descarga.
- DROP de `archivo_pedido_path`.
- Cambiar `post_max_size` / `upload_max_filesize` de PHP (riesgo documentado; no bajar el tope de producto).

## Reglas de negocio

1. Adjuntos **opcionales**: 0 a **5** archivos por solicitud. Se puede enviar sin ninguno.
2. **10 MB** maximo por archivo (`max:10240` KB).
3. Tipos: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `jpg`, `jpeg`, `png`, `webp`.
4. No alteran estados (`pendiente` / `aprobado` / `rechazado` / `estado_compras`) ni el flujo de autorizacion.
5. Quien no puede `view` la solicitud recibe **403** al descargar (o 404 si el adjunto no pertenece a esa solicitud).
6. `PurchaseRequestCreatedMail` **sigue** adjuntando **unicamente** el PDF FO-AD-44.
7. Vista guest de autorizacion por correo **no** lista ni enlaza adjuntos.
8. Foto por linea: sin cambio (max 5 MB, imagen, disco `public`).
9. Al **reenviar rechazada**: se conservan los adjuntos actuales salvo que el usuario quite IDs; se pueden agregar hasta el tope combinado; quitar todos es valido (queda 0).
10. En **detalle**: bloque **Adjuntos** (nombre original + tamaño legible + enlace descargar). Si no hay filas, **no** renderizar el bloque.
11. Sin metadatos de negocio por archivo (solo archivo + nombre original + mime + bytes + orden).

## Permisos (`config/access.php`)

**Sin filas nuevas. No editar este archivo.**

| Permiso / policy | Rol(es) / actores | Uso en esta feature |
| --- | --- | --- |
| `purchase.tab.create` | Solicitante (y bypass super-admin / `manage.users`) | Subir `attachments[]` al **crear** (`store`) |
| Policy `resubmit` (`purchase.tab.my_requests` o `purchase.tab.create` + dueño + `estado=rechazado`) | Dueño | Conservar / agregar / quitar al **reenviar** (`update`) |
| Policy `view` | Dueño, director asignado con `purchase.tab.approval`, `purchase.tab.processing`, super-admin / `manage.users` | Ver bloque en `show` y **descargar** |

La ruta de descarga **no** va detras de `purchase.tab:create` ni `purchase.tab:my_requests`: el director y Compras deben poder descargar igual que ya entran a `show` (grupo `auth` + `active` + `password.changed` + Gate `view`).

## Rutas

Archivo: `routes/modules/purchase-requests.php` (**no** `routes/web.php`).

Prefijo existente: `/purchase-requests/{module}` · nombre `purchase-requests.*`.

| Metodo | URI | Nombre | Notas |
| --- | --- | --- | --- |
| POST (existente) | `/nueva` | `purchase-requests.store` | Aceptar `attachments[]`. Dejar de aceptar `archivo_pedido`. Middleware `purchase.tab:create`. |
| PATCH (existente) | `/mis-solicitudes/{purchase_request}` | `purchase-requests.update` | Aceptar `attachments[]` + `keep_attachment_ids[]`. Middleware `purchase.tab:my_requests` + policy `resubmit`. |
| GET (**nuevo**) | `/solicitud/{purchase_request}/adjuntos/{attachment}` | `purchase-requests.attachments.download` | Mismo grupo que `show` / `export.pdf` (fuera de tabs). `Gate::authorize('view')`. `scopeBindings()` o abort 404 si `attachment.purchase_request_id` ≠ solicitud. |

Declarar la ruta de adjuntos **encima** de `GET /solicitud/{purchase_request}` (junto a pdf/excel).

**No** añadir rutas en `routes/modules/purchase-requests-email.php`.

Parametro de ruta del adjunto: `{attachment}` → modelo `PurchaseRequestAttachment`.

## Base de datos

Migracion **nueva** (no editar `2026_07_31_140100_create_purchase_requests_tables.php`): p. ej. `php artisan make:migration create_purchase_request_attachments_table --no-interaction`.

Solo `php artisan migrate`. Prohibido `migrate:fresh`.

### Tabla `purchase_request_attachments` (crear)

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `purchase_request_id` | FK → `purchase_requests.id` | `constrained()->cascadeOnDelete()`; index |
| `original_name` | `string(255)` | Nombre que subio el usuario (sanitizar caracteres de path `\/`). Usar en `Content-Disposition`. |
| `stored_path` | `string(500)` | Relativo al disco `local`. Nunca URL. |
| `mime_type` | `string(127)` nullable | `UploadedFile::getMimeType()` |
| `size_bytes` | `unsignedInteger` | `getSize()` |
| `sort_order` | `unsignedTinyInteger` | 1-based; orden de carga / conservacion |
| `created_at` / `updated_at` | timestamps | |

Index: `['purchase_request_id', 'sort_order']`.

**No** columna `disk` (siempre `local` via config). **No** titulo/descripcion.

### Columna `purchase_requests.archivo_pedido_path`

| Accion | Detalle |
| --- | --- |
| Esquema | **No alterar / no DROP.** Sigue nullable. |
| Datos en `up()` | Por cada fila con `archivo_pedido_path` no vacio: (1) si el fichero existe en disco `public`, copiar a `local` en `purchase-requests/{id}/{uuid}.{ext}`; (2) insertar fila en `purchase_request_attachments` (`original_name` = `basename`, `sort_order` = 1, mime/size si se pueden leer); (3) si el path tiene valor pero el fichero **no** existe en `public`, insertar igual la metadata con `stored_path` destino (download dara 404 — aceptable para legado/import). (4) `UPDATE purchase_requests SET archivo_pedido_path = NULL`. |
| Codigo | Dejar de escribir y de leer. Quitar de `$fillable`. UI nunca usa esta columna. |
| `down()` | `dropIfExists('purchase_request_attachments')`. **No** restaurar archivos ni rellenar `archivo_pedido_path`. |

### Relacion Eloquent

- `PurchaseRequest::attachments(): HasMany` ordenado por `sort_order`, `id`.
- `PurchaseRequestAttachment::purchaseRequest(): BelongsTo`.
- Al borrar un registro de adjunto (resubmit o cascade): borrar el fichero en disco `local` si existe (hook `deleting` en el modelo o el servicio antes del delete).

## Capas a implementar

- [x] Migracion(es) — tabla nueva + backfill + null de columna legado
- [x] Modelo(s) — `PurchaseRequestAttachment` + relacion en `PurchaseRequest` + factory del adjunto
- [x] Servicio — `PurchaseRequestAttachmentService`; extender `PurchaseRequestResubmitService` (dejar de tocar `archivo_pedido_path`)
- [x] Controlador(es) — `store` / `update` / `show` (eager load `attachments`) + `downloadAttachment`
- [x] Form Request(s) — `StorePurchaseRequestRequest` y `UpdatePurchaseRequestRequest`
- [x] Vista(s) Blade — `create.blade.php`, `edit.blade.php`, `show.blade.php`. **No** mail, **No** PDF, **No** `email-approval`
- [x] JavaScript — extender `resources/js/purchase-request-form.js` (lista de nombres seleccionados; quitar keep IDs en edit). Sin Select2. Sin entry Vite nueva si el form ya carga este archivo
- [ ] Export Excel — **no aplica**
- [x] Tests
- [x] `config/purchase-requests.php` — topes/mimes/disk
- [x] `ImportLegacyPurchaseRequestsCommand` — escribir 1:N; no `archivo_pedido_path`

## Form Requests e inputs

### Crear (`StorePurchaseRequestRequest`)

- **Quitar** regla `archivo_pedido`.
- Añadir:

| Input | Reglas |
| --- | --- |
| `attachments` | `nullable`, `array`, `max:5` |
| `attachments.*` | `file`, `max:10240` (o `config('purchase-requests.attachments.max_kilobytes')`), `mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp` |

Mensajes claros en español (tipo no permitido, tamaño, maximo 5).

### Reenviar (`UpdatePurchaseRequestRequest`)

Mismas reglas de `attachments` / `attachments.*` mas:

| Input | Reglas |
| --- | --- |
| `keep_attachment_ids` | `nullable`, `array` |
| `keep_attachment_ids.*` | `integer`, `exists:purchase_request_attachments,id` **y** `purchase_request_id` = solicitud de la ruta |

`withValidator` / `after`: `count(keep_attachment_ids unicos validos) + count(attachments)` ≤ 5. Si se supera, error en `attachments` (no silencioso).

**Semantica keep:** cada adjunto existente se pinta con `<input type="hidden" name="keep_attachment_ids[]" value="{id}">`. Quitar en UI = eliminar ese input. Si no llega ningun ID y no hay archivos nuevos → 0 adjuntos (permitido). IDs ajenos a la solicitud se rechazan (exists scoped).

### Persistencia store

Dentro de la transaccion existente (despues de crear la `PurchaseRequest` para tener `id`):

1. Guardar cada archivo via servicio (`storeAs` en disco `local`, UUID, extension en minusculas).
2. Insertar filas con `sort_order` 1..N.
3. **No** setear `archivo_pedido_path`.

### Persistencia resubmit (`PurchaseRequestResubmitService`)

1. IDs a conservar = `keep_attachment_ids` validados.
2. Adjuntos actuales cuyo `id` no este en keep → borrar fila **y** fichero en `local`.
3. Recalcular `sort_order` de los conservados (1..K, estable por orden previo).
4. Guardar archivos nuevos y append `sort_order` K+1…
5. **No** recrear adjuntos al borrar/recrear items (los items se siguen recreando; los adjuntos **no** se wipean en bloque).
6. Dejar de leer/escribir `archivo_pedido_path`.

Eager load en `edit()` y `show()`: `attachments`.

## UI

### Crear / editar

- Un `<input type="file" name="attachments[]" multiple>` en cabecera, **despues** de la tabla de productos y su hint de foto, **antes** del boton Enviar / Reenviar.
- `accept` alineado a las extensiones permitidas.
- Etiqueta visible: **Adjuntos** (opcional). Hint: documentos de soporte (cotizacion, orden, evidencia); maximo 5 archivos, 10 MB c/u; tipos listados. **No** columna nueva en la tabla de items.
- JS: mostrar nombres seleccionados antes de enviar; en edit, lista de actuales con boton quitar (elimina el hidden `keep_attachment_ids[]`). No reutilizar el partial de foto de linea.
- Formulario ya es `enctype="multipart/form-data"`.

### Detalle (`show`)

- Bloque **Adjuntos** debajo de la tabla de productos (antes de logs de correo si existen).
- Columnas/campos: nombre original, tamaño (KB/MB), enlace a `purchase-requests.attachments.download` (mismo `$module` de la pagina).
- Sin bloque si `attachments` vacio.
- **No** usar `fotoUrl()` / URLs publicas.

## Componentes reutilizables

- Patron descarga: `QualityDocumentController` + disco `local` + `download($path, $originalName)`. **No** `PurchaseRequestItem::fotoUrl()`.
- Form Requests existentes (ampliar, no crear un tercer request solo de archivos).
- `<x-searchable-select>`: no aplica (no hay select nuevo).
- `BaseExport` / `<x-export-excel>`: no aplica.
- Audit: `PurchaseRequestAuditLogService::logEvent` existente; añadir `attachments_count` en metadata de `create` y `resubmit`. Prohibido loguear `stored_path`, `original_name` o contenido.

## Documentacion a actualizar

- [x] `docs/modules/purchase-requests.md` — Documentador (tabla 1:N, disco local, ruta download, deprecacion de `archivo_pedido_path`, que no van en mail/FO/guest)
- [x] `docs/user/purchase-requests.md` — Documentador (quitar “un archivo de pedido”; describir Adjuntos opcionales multiples y que el correo no los lleva)
- [ ] `docs/INDEX.md` — no esperado
- [ ] `README.md` — no esperado

Feature **no** edita `docs/modules/` ni `docs/user/`.

## Archivos compartidos (`shared-files`)

**Ninguno.**

No tocar: `config/access.php`, `routes/web.php`, layouts (`app.blade.php` / chrome), seeders globales, `resources/css/app.css` (salvo que un ajuste minimo de modulo viva en vista; preferir clases existentes `form-hint`, `panel`, `btn`).

`routes/modules/purchase-requests.php` y `config/purchase-requests.php` son **del modulo**, no shared-files.

## Tests minimos

Archivo recomendado: `tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php` (el modulo test ya es muy largo). Complementar `PurchaseRequestAuditTest` con `attachments_count`. `Storage::fake('local')` (y `public` solo donde ya se prueba la foto).

| # | Caso | Expectativa |
| --- | --- | --- |
| 1 | Store **0** adjuntos | 302; 0 filas; solicitud creada |
| 2 | Store **1** y **N** (≤5) tipos permitidos | Filas + ficheros en `local`; `show` lista nombres |
| 3 | Store **6** archivos | 422; no persiste extras |
| 4 | Tipo o tamaño invalido | 422; mensaje; sin fila |
| 5 | Download dueño / director asignado / `processing` | 200; content disposition con nombre original |
| 6 | Download usuario sin `view` | 403 |
| 7 | Download `{attachment}` de otra solicitud (IDOR) | 404 |
| 8 | Guest / no auth | redirect login |
| 9 | Resubmit: keep todos | IDs conservados; mismos `stored_path` |
| 10 | Resubmit: quitar uno + agregar uno (total ≤5) | El quitado no existe en disco; el nuevo si |
| 11 | Resubmit: quitar todos y no subir | 0 adjuntos |
| 12 | Mail `PurchaseRequestCreatedMail` | Sigue **un** adjunto MIME = PDF FO-AD-44; **no** los archivos de pedido |
| 13 | Guest `email-approval` | `assertDontSee` nombres de adjuntos / “Adjuntos” de pedido |
| 14 | Foto por linea | Regresion: sigue en disco `public` (test existente no debe romperse) |
| 15 | Audit create/resubmit | `metadata.attachments_count` entero; JSON del log **sin** path ni `original_name` |
| 16 | `show` sin adjuntos | 200; no bloque Adjuntos |

No hace falta test de migrate:fresh. Un test de backfill es opcional (crear path en `public` + columna legado **solo si** se invoca la logica extraible; si el backfill vive solo en la migracion, no forzar RefreshDatabase contra migracion custom).

## Plan de implementacion (Task Cards)

**N = 1.** Una sola tarea Feature, vertical slice.

| ID | Agente | Alcance | Depende |
| --- | --- | --- | --- |
| T1 | Feature | Migracion + modelo + servicio + Form Requests + store/resubmit/show/download + vistas + JS + import-legacy + config + tests + Pint | Brief |

T2 **no**. Partir Backend/Frontend/BD violaria el workflow. El slice cabe en un agente: un modulo, un archivo de rutas, sin shared-files.

## Criterios de aceptacion

1. En **Nueva solicitud**, un control **Adjuntos** (multiple, cabecera, fuera de la tabla de productos) permite 0–5 archivos; enviar sin archivos sigue funcionando.
2. Tras crear, el **detalle** autenticado lista nombre y tamaño y permite descargar a solicitante, director asignado y Compras; un tercero sin `view` obtiene 403; un ID de adjunto cruzado obtiene 404.
3. Los ficheros **no** son alcanzables por URL publica (`/storage/...`). Viven en disco `local`.
4. El **correo** al director no incluye esos archivos; FO-AD-44 PDF/Excel no los embebe; **autorizar por correo** no los lista.
5. Al **reenviar** una rechazada se conservan los que traen `keep_attachment_ids[]`, se pueden agregar hasta 5 total y quitar individuales.
6. Archivo con tipo o tamaño invalido, o mas de 5, se rechaza con error de validacion visible.
7. La foto por linea no cambia de comportamiento ni de disco.
8. `archivo_pedido_path` deja de ser fuente de verdad: no se escribe en store/resubmit; la UI no la lee; valores previos migrados a `purchase_request_attachments`.
9. Audit `create`/`resubmit` incluye `attachments_count` y no incluye paths.
10. Tests de la seccion Tests minimos en verde con `php artisan test --compact` sobre los archivos tocados. Pint en PHP sucio.

## Validacion local

1. `php artisan migrate` (incremental; **no** fresh).
2. Crear solicitud con 0 adjuntos y con varios tipos permitidos; ver detalle; descargar; 403 con otro usuario.
3. Reenviar rechazada: conservar, agregar, quitar.
4. Mailpit: correo del director solo con PDF FO-AD-44.
5. Confirmar que una URL `/storage/purchase-requests/...` **no** sirve los nuevos adjuntos.
6. `php artisan test --compact tests/Feature/PurchaseRequests/PurchaseRequestAttachmentTest.php tests/Feature/PurchaseRequests/PurchaseRequestAuditTest.php tests/Feature/PurchaseRequestModuleTest.php`

## Riesgos y dependencias

- **`post_max_size` / `upload_max_filesize`:** 5×10 MB + fotos de linea puede superar el limite PHP en Laragon/Hostinger (el POST entero se descarta antes de Laravel). No bajar el tope de producto en esta feature; si en QA real falla el POST, informar al usuario (ajuste de php.ini), no recortar tipos a escondidas.
- **Legado sin binario:** paths importados o `archivo_pedido_path` sin fichero en `public` generan fila con download 404. Aceptable; no copiar binarios desde el servidor gestion-compras.
- **Orphan files:** si falla el delete de disco tras borrar fila, puede quedar basura en `storage/app/private/purchase-requests/`. Aceptable; no job de limpieza en v1.
- **Doble fuente:** mitigado dejando de escribir `archivo_pedido_path` y anulando valores tras backfill. Feature no debe implementar fallback “si attachments vacio, leer columna vieja”.
- **Ruta download y tab middleware:** si se coloca por error bajo `purchase.tab:my_requests`, el director no podra descargar. Debe ir junto a `show`.

## Aprobacion

- [x] Analista — vacios cerrados (`LISTO_ARQUITECTO`, supuestos consolidados)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-030.md`)
- [x] Usuario — confirmacion 2026-08-28: aprueba el brief; **carga de documentos opcional**

## Señal al AgentSj

`BRIEF_FINAL_OK`

- Archivo: `docs/briefs/FEAT-030.md`
- **shared-files:** no
- **Tareas Feature recomendadas:** 1 (T1 vertical slice completo)
- Siguiente: plan `docs/briefs/FEAT-030-plan.md` + Task Card unica + lanzar Agente Feature
