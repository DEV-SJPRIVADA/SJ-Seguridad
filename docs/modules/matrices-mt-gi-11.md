# Módulo MT-GI-11 — Matrices (Calidad)

## Objetivo

Registrar y dar seguimiento a las **solicitudes de estandarización de documentos** del sistema de gestión usando el formato **MT-GI-11**. Cualquier usuario autorizado solicita; **Calidad** ve todas las solicitudes del tablero.

Tablero propio del área Calidad, independiente del módulo genérico *Matrices dinámicas* (`matrices`, FEAT-052).

## Pestañas

| Pestaña | Ruta | Permiso |
| --- | --- | --- |
| Dashboard | `/calidad/mt-gi-11/dashboard` | `mt_gi_11.matriz.view` |
| Matriz | `/calidad/mt-gi-11/matriz` | `mt_gi_11.matriz.view` |
| Catálogos | `/calidad/mt-gi-11/catalogos` | `mt_gi_11.parameters.edit` |

`/calidad/mt-gi-11` redirige a la primera pestaña visible.

El **alta** (`/calidad/mt-gi-11/matriz/crear`) requiere `mt_gi_11.matriz.view`; la **edición** de un registro requiere, además del permiso de edición, ser dueño de la solicitud o tener `mt_gi_11.view_all`.

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.calidad.mt_gi_11` | Mostrar el tablero en el sidebar de Calidad |
| `mt_gi_11.matriz.view` | Ver dashboard y matriz y **crear solicitudes** (solo propias) |
| `mt_gi_11.matriz.edit` | Editar, eliminar, adjuntar, importar y completar Estandarización. Implica `view` |
| `mt_gi_11.view_all` | Ver todas las solicitudes (Calidad / administración) |
| `mt_gi_11.parameters.edit` | Administrar los cinco catálogos |

- El **dashboard no tiene permiso propio**: se abre con `mt_gi_11.matriz.view`.
- `manage.users` (super admin) omite todas las comprobaciones.
- El rol `administrador` recibe el paquete completo en `RoleAndPermissionSeeder`.
- **Crear** solo exige `mt_gi_11.matriz.view` (`MtGi11AccessService::canCreate()`); editar, eliminar e importar siguen exigiendo `matriz.edit`.
- La sección **Estandarización** del formulario (que **incluye el estado**) solo se renderiza con `matriz.edit` o `view_all` (`canManageEstandarizacion()`); si alguien sin permiso envía esos campos, el Form Request los descarta y fuerza `PENDIENTE`.

## Estado GESTIONADO

El estado de cierre se identifica por **código o nombre** `GESTIONADO` (`MtGi11Estado::gestionadoId()` / `isGestionadoId()`), porque el código técnico del catálogo puede diferir del label (en la BD actual: `code = APROBADA`, `name = GESTIONADO`).

Con ese estado la solicitud queda cerrada y **todos los campos de la solicitud son obligatorios**:

| Campo | Sin GESTIONADO | Con GESTIONADO |
| --- | --- | --- |
| `consecutivo` | Opcional (se autogenera) | Obligatorio |
| `codigo_actual` | Opcional | Obligatorio |
| `version_actual` | Opcional | Obligatorio |
| `codigo_estandarizacion` | Opcional | Obligatorio |
| `fecha_estandarizacion` | Opcional | Obligatorio (≥ fecha de solicitud) |
| `version_actualizada` | Opcional | Obligatorio |

Los adjuntos **no** se exigen. La regla vive en `StoreMtGi11SolicitudRequest::rules()` con `Rule::requiredIf()` y se replica en el import masivo (`MtGi11ImportService::assertGestionadoCompleto()`), que rechaza la fila con el detalle de los campos faltantes. En el formulario, el contenedor expone `data-gestionado-id` / `data-gestionado-inicial` y Alpine marca los asteriscos y pone `required` en cuanto se elige el estado.

## Alcance de registros

| Situación | Ve | Crea | Edita |
| --- | --- | --- | --- |
| `matriz.view` sin `view_all` | Solo `created_by = auth()->id()` | Sí (propias) | Nada |
| `matriz.view` + `matriz.edit` | Solo propias | Sí | Solo propias |
| `matriz.view` + `matriz.edit` + `view_all` | Todas | Sí | Todas |
| `matriz.view` + `view_all` (sin edit) | Todas | Sí | Todas (comportamiento previo de Calidad) |

El alcance aplica a listado, dashboard, export, adjuntos y validación del formulario de edición. `MtGi11Solicitud::scopeVisibleTo()` y `MtGi11AccessService::canManageSolicitud()` son el único punto de verdad.

## Modelo de datos

| Tabla | Contenido |
| --- | --- |
| `calidad_mt_gi_11_solicitudes` | Solicitud (soft delete, `created_by`/`updated_by`) |
| `calidad_mt_gi_11_adjuntos` | Archivos (disco privado `local`) |
| `calidad_mt_gi_11_procesos` | Catálogo de procesos |
| `calidad_mt_gi_11_tipos_documento` | Catálogo de tipos de documento |
| `calidad_mt_gi_11_tipos_solicitud` | Catálogo de tipos de solicitud |
| `calidad_mt_gi_11_cargos_responsable` | Catálogo de cargos |
| `calidad_mt_gi_11_estados` | Estados + `color` (`success`/`warning`/`danger`/`info`/`muted`) |

### Campos de la solicitud

| Campo | Tipo | Obligatorio |
| --- | --- | --- |
| `fecha_solicitud` | date | **Automática**: la asigna el sistema con el día del registro |
| `created_by` | fk users | Sí (autor) |
| `proceso_id` | fk catálogo | Sí |
| `consecutivo` | string(60) | No — **no único**; si va vacío se genera `MT-GI-11-{id}` (sí con GESTIONADO) |
| `tipo_documento_id` | fk catálogo | Sí |
| `nombre_documento` | string(255) | Sí |
| `tipo_solicitud_id` | fk catálogo | Sí |
| `codigo_actual` | string(60) | No (sí con GESTIONADO) |
| `version_actual` | string(30) | No (sí con GESTIONADO) |
| `justificacion` | text (máx. 2000 validado) | Sí |
| `responsable_proceso` | string(255) | Sí |
| `cargo_responsable_id` | fk catálogo | Sí |
| `codigo_estandarizacion` | string(60) | No (sí con estado GESTIONADO) |
| `fecha_estandarizacion` | date (≥ fecha solicitud) | No (sí con estado GESTIONADO) |
| `version_actualizada` | unsigned int | No (sí con estado GESTIONADO) |
| `estado_id` | fk catálogo (se elige dentro de Estandarización) | Sí (default PENDIENTE) |

### Consecutivo e identificador

El `consecutivo` **no es único** por decisión de negocio: el mismo documento puede tener varias solicitudes/versiones. Toda operación (consultar, editar, eliminar, adjuntos) usa el **`id`**. El filtro por consecutivo es coincidencia parcial (`LIKE`). El export incluye una columna `ID` como primera columna.

En el **alta** el consecutivo es **opcional**: si llega vacío, `MtGi11Service::create()` guarda `MT-GI-11-{id}` como valor provisional (se calcula después del insert porque depende del `id`). En **edición** un consecutivo vacío se ignora y se conserva el anterior. El import masivo sigue exigiendo la columna con valor.

## Fecha de solicitud

La fecha **no se captura** en el formulario: la asigna el sistema.

| Momento | Valor |
| --- | --- |
| Alta | `now()->toDateString()` al registrar |
| Edición | Se **conserva** la fecha original del registro (no se mueve) |
| Nueva versión | Fecha del día en que se registra la versión |
| Import | La fecha del archivo si viene; si está vacía, la del día |

Se inyecta en `prepareForValidation()` del Form Request, de modo que un valor enviado por el cliente **no** puede sobrescribirla. La regla `fecha_estandarizacion >= fecha_solicitud` sigue comparando contra esa fecha. En el formulario se muestra como dato informativo en la barra de metadatos («Se asigna automáticamente»).

## «Nueva versión»

`GET /calidad/mt-gi-11/matriz/{solicitud}/nueva-version` abre el formulario de alta precargado:

- Copia: proceso, consecutivo, tipo de documento, nombre del documento, tipo de solicitud, código actual, versión actual, responsable, cargo, código de estandarización y estado (PENDIENTE).
- `version_actualizada` = valor del origen + 1 (si no hay valor numérico, deriva el primer entero de `version_actual` y suma 1).
- **No** copia: `fecha_solicitud` (usa hoy), `justificacion`, `fecha_estandarizacion` ni adjuntos.
- El registro origen no se modifica; la nueva versión se guarda como solicitud nueva.

## Filtros y listado

- Fecha de solicitud (desde/hasta), proceso, consecutivo (parcial), tipo de documento y **botón de estado** (pills con el color del catálogo).
- DataTables **server-side**: buscador global sobre todos los campos y catálogos, selector 10/25/50/100 (sin «Todos»), paginación y orden por columnas.
- Orden por defecto: `fecha_solicitud DESC, id DESC`.
- Tope duro de 100 filas por página en el servidor.

## Masivos

| Acción | Ruta |
| --- | --- |
| Plantilla XLSX | `GET /calidad/mt-gi-11/matriz/plantilla` |
| Importar | `POST /calidad/mt-gi-11/matriz/importar` |
| Exportar filtrado | `GET /calidad/mt-gi-11/matriz/exportar` |

- La plantilla lleva **claves internas en la fila 1** (oculta) y **etiquetas en la fila 2**. El importador detecta si la fila 2 es de etiquetas y salta a la 3.
- Los catálogos se capturan **por nombre** (sin distinguir mayúsculas).
- **El consecutivo puede repetirse** dentro del archivo y contra la base. En la importación **sí es obligatorio** (a diferencia del alta manual).
- Errores por fila (catálogo inexistente, fecha inválida, justificación vacía, fecha de estandarización anterior, GESTIONADO con campos vacíos) se reportan y **no** se guardan; el resto sí se importa.
- Límite: `mt_gi_11.import.max_rows` (5000).

## Adjuntos

- Disco `local`, carpeta `calidad/mt-gi-11/{solicitud_id}/{uuid}.{ext}`.
- Límites: 10 archivos por envío, 10 MB cada uno, `pdf/jpg/jpeg/png`.
- Vista previa inline solo para PDF e imágenes (`isPreviewable()`); el resto solo descarga.
- Al eliminar la solicitud se borran los archivos del disco y el registro queda en soft delete.

## Persistencia en mayúsculas

`UppercasesValidatedText` sobre el Form Request. Se guardan en MAYÚSCULAS: `consecutivo`, `nombre_documento`, `codigo_actual`, `version_actual`, `justificacion`, `responsable_proceso`, `codigo_estandarizacion`. `color` está excluido por ser clave técnica de estilo.

## Auditoría

`MtGi11AuditLogService` sobre `SystemAuditService` con `module = mt_gi_11` y `area = calidad`. Registra alta, actualización, eliminación, adjuntos, import y export. Configurado en `config/audit.php`.

## Estructura de código

```
routes/areas/calidad.php                                   grupo calidad.mt-gi-11.
app/Http/Controllers/Calidad/MtGi11Controller.php
app/Http/Requests/Calidad/MtGi11/{Store,Update}MtGi11SolicitudRequest.php
app/Http/Requests/Calidad/MtGi11/{Store,Update}MtGi11CatalogItemRequest.php
app/Http/Requests/Calidad/MtGi11/StoreMtGi11AdjuntosRequest.php
app/Http/Requests/Calidad/MtGi11/ImportMtGi11Request.php
app/Models/Calidad/{MtGi11Solicitud,MtGi11Adjunto,MtGi11Catalog,MtGi11Estado,...}.php
app/Services/Access/MtGi11AccessService.php
app/Services/Calidad/MtGi11{Datatable,Service,Adjunto,Catalog,Dashboard,Import,AuditLog}Service.php
app/Exports/MtGi11Export.php
app/Exports/MtGi11ImportTemplateExport.php
app/Traits/HasMtGi11Tabs.php
config/mt_gi_11.php
resources/views/areas/calidad/mt_gi_11/{dashboard,matriz,form,catalogos}.blade.php + partials/subnav
resources/css/areas/calidad/123-calidad-mt-gi-11.css
resources/js/mt-gi-11-dashboard-charts.js
```

## Pruebas

```bash
php artisan test --compact --filter=MtGi11
```

| Archivo | Cubre |
| --- | --- |
| `MtGi11BoardAccessTest` | Permisos, catálogo sembrado, bypass admin, redirección de pestaña |
| `MtGi11SolicitudTest` | CRUD, mayúsculas, consecutivo repetido y opcional, validaciones, nueva versión, adjuntos, crear con `matriz.view` y Estandarización oculta |
| `MtGi11VisibilityTest` | Alcance propio vs `view_all` en listado, detalle, dashboard y export |
| `MtGi11DatatableTest` | Buscador global, filtros, tope de filas, orden, series del dashboard |
| `MtGi11ImportTest` | Import con catálogos por nombre, consecutivos repetidos y errores por fila |
| `MtGi11CatalogTest` | CRUD de catálogos, código técnico, color, borrado protegido |

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-11-20 | Creación del tablero MT-GI-11 con Dashboard, Matriz, Catálogos, adjuntos, nueva versión, import/export y auditoría (FEAT-053). |
| 2026-11-20 | Corrección: agregaciones del dashboard sobre query limpia para cumplir `only_full_group_by` de MySQL; test de regresión del SQL. |
| 2026-11-20 | Rediseño del formulario de alta/edición con el shell canónico `req-form-layout`: 5 secciones numeradas, metadatos, guía lateral (`x-form-aside-guide`), zona de adjuntos y barra de acciones. |
| 2026-11-20 | Ajuste del listado: chrome del DataTable con flex (el buscador ya no se solapa con el borde), traducción embebida sin CDN y liberación inmediata del estado de carga para que la tabla aparezca sin demora. |
| 2026-11-20 | Corrección del modal Masivos: el input de archivo quedaba oculto sin control visible. Se agregó la etiqueta «Elegir archivo», el nombre del archivo seleccionado y el botón de importar habilitado solo con archivo. |
| 2026-11-20 | La fecha de solicitud deja de capturarse: el sistema la asigna con el día del registro en el alta y la conserva en la edición. El import la toma del archivo o usa la fecha del día si viene vacía. |
| 2026-11-20 | Zona de adjuntos del formulario: el input nativo de archivo queda oculto y el único control visible es la etiqueta estilizada, con el nombre de los archivos elegidos. |
| 2026-11-23 | `mt_gi_11.matriz.view` habilita **crear** solicitudes (`canCreate`); editar, eliminar, importar y nueva versión siguen con `matriz.edit`. El consecutivo del alta pasa a ser **opcional** (se autogenera `MT-GI-11-{id}` si va vacío) y la sección **Estandarización** solo se muestra con `matriz.edit` o `view_all`. |
| 2026-11-23 | El campo **Estado** se mueve dentro de la sección **Estandarización**. Con el estado **GESTIONADO** (detectado por código o nombre) todos los campos de la solicitud pasan a obligatorios en alta, edición e import; los adjuntos siguen opcionales. |
