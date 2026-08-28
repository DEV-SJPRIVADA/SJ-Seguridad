# Preguntas del Analista — FEAT-030

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** vacíos de producto cerrados con decisiones del usuario + supuestos temporales razonables. **Sin pausa.** Listo para Arquitecto.

---

## Contexto recibido

**Feature ID:** FEAT-030  
**Origen:** `@agent-sj` (2026-08-28)  
**Módulo:** Solicitudes de compra (`purchase-requests`)  
**Título tentativo:** Adjuntos múltiples en solicitud de compra (nivel solicitud; detalle plataforma)

### Solicitud original (resumen)

En `purchase-requests/compras/nueva`, en la tabla de productos se pidió un campo para adjuntar documentos, **varios a la vez** en el mismo registro.

### Decisiones YA CONFIRMADAS (no repreguntar)

1. Los adjuntos son **por toda la solicitud** (cabecera). **No** por línea de producto.
2. Se ven **solo en el detalle de la plataforma**. **No** van en el correo de autorización al director.

### Estado técnico hoy (repo)

| Aspecto | Comportamiento actual |
| --- | --- |
| Form crear | `create.blade.php` — `enctype="multipart/form-data"`; tabla de líneas con **foto opcional por ítem**; **no** hay input de archivo de pedido |
| Form reenviar | `edit.blade.php` — mismo patrón; reenvío de solicitud **rechazada** |
| Validación | `archivo_pedido` nullable, 1 archivo, max 10240 KB (`StorePurchaseRequestRequest` / `UpdatePurchaseRequestRequest`) |
| Persistencia | Columna `purchase_requests.archivo_pedido_path`; Store/Resubmit guardan en disco `public` (`purchase-requests`) |
| Foto por línea | `items.*.foto` → `purchase_request_items.foto_path` disco `public` (max 5 MB, imagen) |
| Reenvío | `PurchaseRequestResubmitService` recrea ítems; conserva `archivo_pedido_path` si no llega archivo nuevo |
| Detalle | `show.blade.php` **no** muestra `archivo_pedido` |
| Correo director | `PurchaseRequestCreatedMail` solo adjunta PDF FO-AD-44 |
| Autorización guest | `email-approval.blade.php` — sin adjuntos de pedido |
| Policy `view` | super-admin / `manage.users`, compras processing, solicitante, director asignado |
| Permisos | `purchase.tab.create`, `my_requests`, `approval`, `processing` — **sin permiso nuevo esperado** |
| Guía usuario | Aún dice “opcionalmente adjunte un archivo de pedido” aunque el campo no está en la UI de crear |
| Import legacy | Copia `archivo_pedido_path` desde BD gestión-compras |

---

## Entendimiento del analista (resumen)

El negocio necesita que el **solicitante** pueda adjuntar **varios documentos de soporte** (cotizaciones, órdenes, evidencias) a la **solicitud completa**, no a cada producto. La foto por línea **sigue igual** y no se mezcla con estos adjuntos.

Quién usa el resultado: el **solicitante** (al crear o al reenviar una rechazada), el **director** (cuando entra al detalle en la plataforma) y **Compras** (al ver el detalle desde la bandeja). Quien autoriza **solo por correo** no verá estos archivos: el usuario lo confirmó.

El campo **no va en la tabla de productos** (pese a la redacción original): va en la **cabecera** del formulario, junto a los datos generales / debajo de productos, **antes de Enviar**.

Hoy existe un rastro de “un solo archivo de pedido” (columna + validación + Store) **sin UI**. Esta feature lo **reemplaza en producto** por **varios archivos** visibles en el detalle.

---

## Hallazgos técnicos relevantes

| Hallazgo | Detalle | Por qué importa |
| --- | --- | --- |
| Input ausente en UI | Validación y columna existen; crear/editar no muestran el campo | La guía de usuario describe un flujo que el formulario no ofrece |
| Un archivo vs varios | Modelo actual es 1 path en cabecera | Hace falta modelo 1:N (tabla o equivalente); Arquitecto diseña |
| Disco `public` | Fotos y `archivo_pedido` se sirven por URL pública | Documentos de pedido no deberían ser adivinables sin login; Arquitecto debe decidir disco + ruta de descarga autenticada |
| Reenvío conserva path | Si no hay archivo nuevo, se reusa `archivo_pedido_path` | Hay que definir qué pasa con **varios** archivos al reenviar (ver supuesto 6) |
| Correo / FO-AD-44 / guest | Mail solo PDF; PDF no incluye archivo de pedido; guest approval no lista adjuntos | Alineado con decisión 2; no ampliar esos canales |
| Policy `view` ya cubre actores | Solicitante, director asignado, Compras, super-admin | No hace falta permiso nuevo para ver/descargar |
| Shared-files | No se espera tocar `config/access.php` ni `routes/web.php` | Flag `shared-files` vacío salvo que Arquitecto añada ruta en `routes/modules/purchase-requests.php` (módulo propio, no compartido) |
| Audit | Eventos `create` / `resubmit` ya existen; **no** loguear paths ni contenido de archivos (FEAT-026) | Metadata: cantidad de adjuntos, no rutas |
| Hostinger / PHP | 5 archivos × 10 MB puede superar `post_max_size` | Arquitecto valida tope real; el supuesto de negocio queda en 5 × 10 MB con recorte técnico si el hosting lo exige |

---

## Preguntas abiertas

Ninguna que requiera pausa. Las 8 preguntas de plantilla quedan **cerradas** así:

| # | Tema | Cierre |
| --- | --- | --- |
| 1 | Objetivo y usuarios | Solicitante adjunta soporte; director y Compras lo consultan en el detalle autenticado. |
| 2 | Alcance | Ver **Incluye** / **Fuera de alcance** abajo. Decisión 1 y 2 son ley. |
| 3 | Permisos | Reutilizar existentes. **Sin permiso nuevo.** Descarga = quien ya puede `view`. |
| 4 | Reglas | Adjunto **opcional**; varios archivos; create + reenviar rechazada; no cambia estados ni flujo de aprobación. |
| 5 | Datos | Relación 1:N de adjuntos a la solicitud (Arquitecto: tabla vs evolución de `archivo_pedido_path`). |
| 6 | Interfaz | Campo múltiple en crear/reenviar (cabecera, **fuera** de la tabla de productos). Lista + descarga en `show`. Sin indicador en listados. Sin correo. |
| 7 | Integraciones | Ninguna nueva. No embeber en FO-AD-44 ni en `PurchaseRequestCreatedMail`. |
| 8 | Doc usuario | Corregir la frase de “un archivo de pedido”; documentar varios adjuntos opcionales y que **no** viajan en el correo. Sin procedimiento operativo adicional del negocio. |

---

## Fuera de alcance (explícito)

- Adjuntos **por línea** de producto (la foto por ítem no se toca).
- Adjuntar los archivos al **correo** del director (ni como MIME ni como enlace en el mail).
- Incluir adjuntos en **FO-AD-44** (PDF o Excel) ni en la vista **guest** de autorización por correo (`email-approval`).
- Permiso nuevo en `config/access.php`.
- Añadir/quitar adjuntos en solicitudes **pendientes o aprobadas** (solo alta al crear y al reenviar rechazada).
- Adjuntos en **suministros** / bandeja de insumos.
- Indicador de “tiene adjuntos” en Mis solicitudes, Pendientes o Bandeja.
- Título/descripción/categoría por archivo (solo el archivo y su nombre original).
- Visor interno tipo Office; basta lista + descargar / abrir.
- Import legacy masivo de archivos binarios (solo mapear el path legado si ya existe en BD, decisión de Arquitecto).

---

## Supuestos temporales (cerrados para no bloquear el brief)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Adjuntos **opcionales** (0 a N). La solicitud se envía igual sin archivos. | El negocio esperaba al menos un documento obligatorio. |
| 2 | Tope **5 archivos** por solicitud; **10 MB** por archivo (mismo tope actual de `archivo_pedido`). Tipos: **PDF**, Office (**doc, docx, xls, xlsx, ppt, pptx**) e imagen (**jpg, jpeg, png, webp**). | Pedían más archivos, otros tipos (ZIP, MSG) o tope distinto; o el hosting obliga a bajar MB. |
| 3 | UI de carga: **un** control de archivos múltiples en **cabecera** (después de la tabla de productos, antes de Enviar). Texto de ayuda con tipos y topes. **No** columna nueva en la tabla de ítems. | Esperaban el control visualmente “en la fila” pese a decidir cabecera. |
| 4 | Etiqueta visible: **Adjuntos** (opcional). Ayuda: documentos de soporte de la solicitud (cotización, orden, evidencia). | Preferían “Archivo de pedido” u otra etiqueta de FO. |
| 5 | **Crear** y **reenviar rechazada** (`edit`) son los únicos momentos de carga. No hay edición de adjuntos en pendiente/aprobado/compras. | Querían que Compras o el director subieran archivos después. |
| 6 | Al **reenviar**: se **conservan** los adjuntos actuales; se pueden **agregar** más (hasta el tope) y **quitar** individuales (mismo espíritu que quitar foto de línea). | Esperaban reemplazo total (borrar todos y subir de nuevo) o no poder quitar. |
| 7 | En **detalle** (`show`): bloque “Adjuntos” con nombre original + tamaño + acción descargar/abrir. Si no hay ninguno, **no** se muestra el bloque (o vacío discreto). Visible para **quien ya puede ver** la solicitud (`PurchaseRequestPolicy::view`). | El director guest o un rol extra debía verlos; o querían el bloque siempre visible. |
| 8 | La pantalla **autorizar por correo** (sin login) **no** lista adjuntos. El director que quiera verlos usa **Ver en la plataforma**. | El director esperaba verlos en el enlace firmado sin entrar a la app. |
| 9 | Datos legado: un `archivo_pedido_path` existente se **migra** al nuevo modelo 1:N para no perder archivos históricos; la columna vieja queda a criterio del Arquitecto (deprecar). | No había archivos reales en local/prod y la migración es ruido; o preferían convivir dos mecanismos. |
| 10 | Descarga autenticada (misma policy `view`); **no** URL pública adivinable. No se audita cada descarga; en create/resubmit solo **cantidad** de archivos (sin paths). | Querían log de cada descarga o seguir sirviendo por disco `public` como las fotos. |
| 11 | Sin `shared-files`. Rutas nuevas, si las hay, viven en `routes/modules/` del módulo. | Arquitecto necesita tocar layout o `access.php`. |
| 12 | Sin procedimiento operativo escrito extra: basta actualizar guía de usuario del módulo. | Calidad/Compras tenía un FO que exige adjunto obligatorio o tipos distintos. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

**Señal al AgentSj:** `LISTO_ARQUITECTO`

## Respuestas del usuario

(2026-08-28 — chat AgentSj, **antes** de este análisis)

1. **Nivel:** adjuntos por **toda la solicitud** (cabecera), no por línea de producto.
2. **Visibilidad:** **solo** en el detalle de la plataforma; **no** en el correo de autorización al director.

El resto de reglas de esta entrega son **supuestos temporales** de la tabla anterior (el usuario pidió brief en este ciclo y no bloquear por formatos).

---

# Feature Brief — FEAT-030

> `BORRADOR — pendiente Arquitecto`

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-030 |
| Modulo / area | Solicitudes de compra (`purchase-requests`) |
| Titulo | Adjuntos múltiples en solicitud de compra (nivel solicitud; detalle plataforma) |
| Solicitante | Usuario (chat AgentSj 2026-08-28) |
| Fecha | 2026-08-28 |

## Objetivo

Permitir al solicitante adjuntar **varios documentos de soporte** a una solicitud de compra (cotizaciones, órdenes, evidencias), de forma **opcional** y **a nivel de solicitud**, para que director y Compras los consulten **solo en el detalle autenticado** de la plataforma.

## Alcance

### Incluye

- Campo de **múltiples archivos** en **Nueva solicitud** y en **Reenviar rechazada**, a nivel cabecera (fuera de la tabla de productos).
- Persistencia 1:N de adjuntos asociados a la solicitud.
- Visualización y descarga en `purchase-requests.show` para quien ya puede ver la solicitud.
- Conservar / agregar / quitar adjuntos al reenviar una rechazada (tope 5).
- Migración del archivo legado `archivo_pedido_path` al modelo múltiple, si existe valor.
- Validación de tipos y tamaños; tests de carga, visibilidad, reenvío y que el correo/FO-AD-44 **no** lleven estos archivos.
- Actualización de `docs/modules/purchase-requests.md` y `docs/user/purchase-requests.md` (Documentador).

### Fuera de alcance

- Adjuntos por línea; foto de producto sin cambios.
- Correo al director, FO-AD-44 PDF/Excel, vista guest de autorización.
- Permiso nuevo; indicadores en listados; suministro; alta de adjuntos post-envío (salvo reenvío rechazada).
- Metadatos por archivo (título, tipo documental de negocio).
- Visor Office embebido.

## Reglas de negocio

- Adjuntos **opcionales**; de 0 a **5** archivos.
- **10 MB** máximo por archivo.
- Tipos permitidos: PDF; Word/Excel/PowerPoint (`doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`); imágenes `jpg`, `jpeg`, `png`, `webp`.
- No alteran estados (`pendiente` / `aprobado` / `rechazado` / bandeja).
- Quien no puede ver la solicitud no puede descargar adjuntos.
- El correo de nueva solicitud **sigue** adjuntando únicamente el PDF FO-AD-44.
- La foto por línea permanece independiente (max 5 MB, imagen).

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `purchase.tab.create` | solicitante (y roles que ya lo tienen) | Subir adjuntos al **crear** |
| `purchase.tab.my_requests` o `purchase.tab.create` | dueño + policy `resubmit` | Subir/quitar adjuntos al **reenviar rechazada** |
| Policy `view` (sin permiso nuevo) | solicitante, director asignado, `purchase.tab.processing`, super-admin / `manage.users` | Ver y descargar en el detalle |

Sin filas nuevas en `config/access.php`.

## Rutas

| Metodo | URI | Nombre | Archivo de rutas |
| --- | --- | --- | --- |
| POST (existente) | `purchase-requests/{module}` | `purchase-requests.store` | `routes/modules/` (módulo purchase-requests) — aceptar array de archivos |
| PUT/PATCH (existente) | reenvío rechazada | `purchase-requests.update` | mismo módulo |
| GET (nuevo, si Arquitecto) | descarga de un adjunto | p. ej. `purchase-requests.attachments.download` | mismo archivo de rutas; **policy `view`** |

No tocar `routes/web.php`.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| Relación 1:N de adjuntos (nombre a definir por Arquitecto) | migracion | `purchase_request_id`, nombre original, path, mime/size, orden; FK a `purchase_requests` |
| `purchase_requests.archivo_pedido_path` | alter / deprecar | Migrar valores existentes al 1:N; no dejar dos fuentes de verdad en UI |

## Capas a implementar

- [ ] Migracion(es)
- [ ] Modelo(s)
- [ ] Controlador(es) (store/update/show + descarga autenticada)
- [ ] Form Request(s) (`archivo_pedido` → múltiples archivos)
- [ ] Vista(s) Blade (`create`, `edit`, `show`; **no** mail, **no** FO-AD-44, **no** email-approval)
- [ ] JavaScript (si aplica: lista de nombres seleccionados / quitar antes de enviar)
- [ ] Export Excel (si aplica — usar `BaseExport` y `<x-export-excel>`) — **no aplica**
- [ ] Tests

## Componentes reutilizables

- Formulario ya es `multipart/form-data`.
- Patrón de “quitar” similar a foto de línea, pero **no** reutilizar el partial de foto (dominio distinto).
- Disco y descarga: preferible patrón **Documentos de Calidad** (disco no público + download con autorización), no URL `Storage::url` pública.

## Documentacion a actualizar

- [ ] `docs/modules/purchase-requests.md`
- [ ] `docs/user/purchase-requests.md` (quitar “un archivo de pedido”; describir varios adjuntos opcionales y que no van en el correo)
- [ ] `docs/INDEX.md` (si aplica) — no esperado
- [ ] `README.md` (si aplica) — no esperado

## Archivos compartidos (`shared-files`)

Ninguno esperado (`config/access.php`, `routes/web.php`, layouts, seeders globales).

Rutas del propio módulo en `routes/modules/` **no** son shared-files.

## Criterios de aceptacion

1. En **Nueva solicitud**, el usuario puede seleccionar **varios** archivos opcionales a nivel de solicitud (no por fila de producto) y enviar sin adjuntos.
2. Tras crear, el **detalle** muestra la lista de adjuntos (nombre) y permite descargarlos a solicitante, director asignado y Compras; un usuario sin `view` recibe 403.
3. El **correo** al director **no** incluye esos archivos; el PDF/Excel FO-AD-44 **no** los embebe; la vista **autorizar por correo** no los lista.
4. Al **reenviar** una rechazada se conservan los adjuntos previos, se pueden agregar hasta el tope y quitar individuales.
5. Un archivo que no cumple tipo o tamaño se rechaza con mensaje claro; más de 5 archivos se rechaza.
6. La foto por línea sigue funcionando igual.
7. Tests cubren store con 0, 1 y N archivos, descarga autorizada/no autorizada, reenvío y no-regresión de mail/PDF.

## Validacion local

1. Crear solicitud con 0 adjuntos y con varios tipos permitidos; verificar detalle y 403 de un tercero.
2. Reenviar rechazada: conservar, agregar y quitar.
3. Confirmar correo (Mailpit) y FO-AD-44 sin los adjuntos de pedido.
4. `php artisan test --compact` filtros del módulo purchase-requests.

## Riesgos y dependencias

- Límite PHP/`post_max_size` en Laragon y Hostinger vs 5 × 10 MB (Arquitecto puede bajar MB sin cambiar la regla de “varios opcionales”).
- Archivos hoy en disco `public` (fotos): no copiar ese patrón para documentos de pedido.
- Datos legado en `archivo_pedido_path` (import) deben migrarse o se “pierden” en la UI nueva.
- Volumen de upload en el mismo POST que fotos de líneas: una solicitud con muchas fotos + 5 adjuntos puede fallar por tamaño total.

## Aprobacion

- [x] Analista — vacíos cerrados (decisiones usuario + supuestos temporales)
- [ ] Arquitecto — brief final
- [ ] Usuario — confirmación (opcional; AgentSj puede seguir: el usuario pidió brief en este ciclo)
