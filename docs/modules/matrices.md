# Módulo Matrices dinámicas

## Objetivo

Permite a usuarios autorizados (`matrices.manage`) crear matrices con formularios propios (campos configurables), versionarlas, compartirlas por área o por usuario, y que otros usuarios registren, consulten y editen registros en un listado filtrable con exportación a Excel. Todo se almacena en una sola base de datos genérica.

## Tablas

- `matrices`: encabezado (código `MX-00001`, nombre, descripción, `area_key`, creador, estado, versión vigente, contador de registros).
- `matrix_versions`: versiones de la definición (borrador / publicada), con `published_at` y `published_by_user_id`.
- `matrix_fields`: campos de una versión (`field_key` estable, tipo, sección, opciones, reglas JSON).
- `matrix_user_access`: otorgamientos por usuario (`can_register`, `can_view_others`, `can_edit_others`, `expires_at`).
- `matrix_area_access`: otorgamientos por área (mismas columnas).
- `matrix_submissions`: registros (folio, estado, creador, versión usada). La columna `assigned_to_user_id` existe en la tabla pero ya **no se usa** en la aplicación (la asignación manual se retiró; queda solo por compatibilidad de datos).
- `matrix_submission_values`: valores por campo (texto, número, fecha, JSON).
- `matrix_submission_attachments`: adjuntos y firmas (disco privado).

## Permisos

- Un único permiso Spatie: `matrices.manage` (crear, definir, versionar, publicar, compartir, archivar).
- Las capacidades `ver`, `registrar`, `editar` son columnas en las tablas de acceso, no permisos Spatie.
- El creador de la matriz tiene todo. `matrices.manage` global también.
- `can_edit_others ⇒ can_view_others`. **"Solo los míos" = únicamente los creados por mí.**

## Estados

Matriz: `draft`, `published`, `archived`. Registro: `draft`, `submitted`, `archived`. Se usan constantes string (compatible sqlite/MySQL).

## Tipos de campo (V1)

`text`, `textarea`, `number`, `currency`, `date`, `select`, `radio`, `checkbox`, `file`, `signature`. `table` queda para V1.1.

## Adjuntos y firmas

- Disco privado `local`, ruta `matrices/{matrix_id}/{submission_id}/{uuid}.{ext}`.
- Límites: 10 MB por archivo, 10 archivos por registro, 200 MB por matriz.
- Firmas se guardan como PNG en `matrix_submission_attachments` con `field_id`, no como valor de texto. Origen: dibujo (canvas) o firma del perfil (`UserSignatureService`).
- Vista previa inline solo para PDF e imágenes (jpeg/png/webp).

## Rutas (prefijo `matrices/{module}`)

Listado, crear, editar/publicar/archivar, compartir (store/destroy por usuario y área), registros (index, datatable, create, store, show, edit, update, archive), exportar, adjuntos (download, preview, destroy).

## Controladores

`MatrixController`, `MatrixBuilderController`, `MatrixShareController`, `MatrixSubmissionController`, `MatrixExportController`, `MatrixAttachmentController` en `app/Http/Controllers/Matrices/`.

## Servicios

`MatrixAccessResolver`, `MatrixDefinitionService`, `MatrixShareService`, `MatrixListingService`, `MatrixSubmissionService`, `MatrixSubmissionValidator`, `MatrixAttachmentService`, `MatrixSubmissionPresenter`, `MatrixSubmissionDatatableService`, `MatrixSubmissionFilterService`, `MatrixAuditService`.

## Alcance de registros

La visibilidad se define **solo** en la pestaña **Compartir** de la matriz:

- Con **Ver todo** (`can_view_others`) o **Editar todo** (`can_edit_others`): ve todos los registros de la matriz.
- Sin esas casillas: ve únicamente los registros que él mismo creó.

No existe asignación manual de un registro a un usuario: `MatrixSubmission::scopeVisibleTo()` y `MatrixAccess::canViewSubmission()`/`canEditSubmission()` reciben solo `isCreator`.

## DataTable y export

Listado server-side (`serverSide: true`) con endpoint JSON. El export reutiliza los mismos filtros y alcance vía `MatrixSubmissionFilterService` y `BaseExport::downloadFromQuery`.

## Auditoría

Eventos vía `MatrixAuditService` (wrapper de `SystemAuditService`) con `module = matrices`.

## Frontend

- CSS: `resources/css/modules/matrices/122-matrices.css`.
- Alpine: `matrix-builder` (constructor visual) y `matrix-signature-pad` (captura de firma).

## Documentación relacionada

- Brief: `docs/briefs/FEAT-052.md`.
- Usuario: `docs/user/matrices.md`.
