# Módulo Matrices dinámicas

## Objetivo

Permite a usuarios autorizados (`matrices.manage`) crear matrices con formularios propios (campos configurables), versionarlas, compartirlas por área o por usuario, y que otros usuarios registren, consulten y editen registros en un listado filtrable con exportación a Excel. Todo se almacena en una sola base de datos genérica.

## Tablas

- `matrices`: encabezado (código `MX-00001`, nombre, descripción, `area_key`, creador `owner_user_id`, último editor `updated_by_user_id`, estado, versión vigente, contador de registros).
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

Listado (`/`, tarjetas solo con `matrices.manage`), lista simple (`/mis-matrices`), crear, editar/publicar/archivar, compartir (store/destroy por usuario y área), registros (index, datatable, create, store, show, edit, update, archive), exportar, adjuntos (download, preview, destroy).

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

## Navegación y visibilidad de matrices

| Quién | Entrada a `/matrices/{module}` | Pestañas |
| --- | --- | --- |
| Con `matrices.manage` | Grid de **tarjetas** (crear, publicar, compartir, archivar) | «Mis matrices» + «Nueva matriz» + una pestaña por matriz propia o compartida |
| Sin `matrices.manage` (solo compartido) | **Redirige** a su primera matriz | «Mis matrices» (lista simple) + una pestaña por matriz asignada |

- `MatrixListingService::canSeeAdminCards()` decide si hay tarjetas.
- `MatrixListingService::tabbedMatrices()` devuelve **solo las matrices creadas por el usuario o compartidas** (por usuario o por área, vigentes y no archivadas). El administrador **no** ve todas las matrices del área como pestañas: el resto queda en las tarjetas.
- `GET /matrices/{module}/mis-matrices` (`matrices.mine`) es el listado de matrices propias o compartidas con buscador por nombre/código y filtros por área, creador y estado. La usan los usuarios sin `matrices.manage`; un administrador también puede abrirlo por URL.
- `BuildsMatrixTabs` arma el subnav: «Mis matrices» apunta a `matrices.index` para el administrador y a `matrices.mine` para el resto. El subnav usa scroll horizontal con flechas (`data-module-tabs-scroller`).
- Si el usuario no tiene ninguna matriz vigente (accesos vencidos o matrices archivadas), `MatrixAccessService::hasAnyAccess()` devuelve `false` y el tablero desaparece del sidebar; el índice responde 403.

## «Mis matrices» (listado con filtros)

- Ruta `matrices.mine`; controlador `MatrixController::mine()`; datos desde `MatrixListingService::mineMatrices()` (mismas reglas de visibilidad que las pestañas: propias o compartidas, vigentes y no archivadas).
- Filtros por query string, aplicados en SQL: `q` (nombre o código), `area` (`area_key`), `owner` (`owner_user_id`) y `status` (`draft` / `published`).
- Las opciones de área y creador se construyen **solo con las matrices visibles para el usuario**, no con todo el catálogo del sistema.
- Columnas: matriz (nombre + código), área, creador (con chip «Tú» si aplica), última edición (`updated_by_user_id` + `updated_at`), estado, versión y acceso.
- `updated_by_user_id` (migración `2026_11_23_100000_add_updated_by_to_matrices_table`) se actualiza al crear, editar y publicar la definición. `owner_user_id` sigue siendo el creador y no cambia.
- El listado se renderiza con Blade sobre una consulta filtrada; no requiere endpoint JSON ni DataTables.

## Auditoría

Eventos vía `MatrixAuditService` (wrapper de `SystemAuditService`) con `module = matrices`.

## Frontend

- CSS: `resources/css/modules/matrices/122-matrices.css` (bloque `Builder`).
- Alpine: `matrix-builder` (constructor visual) y `matrix-signature-pad` (captura de firma).
- Vista `modules/matrices/builder.blade.php` (crear y editar, misma plantilla):
  - Cabecera con franja de acento de marca, estado de la matriz y acciones en una sola fila (Cancelar / Publicar / Guardar borrador; el guardado usa `form="matrix-builder-form"`).
  - Paso 1 **Datos generales** y paso 2 **Campos del formulario**, con encabezados numerados y acciones de expandir/contraer con `.req-manage-filters__icon-btn`.
  - Selector de tipo como tarjetas con icono Lucide y descripción corta; dentro de cada campo, selector compacto (`field.type` se evalúa en Alpine y los botones se renderizan con Blade + `@js()`).
  - Tarjetas de campo con grupos **Contenido** y **Presentación y reglas**; movimiento/duplicado/eliminación con `.cursos-catalogo-page__icon-btn` (no se usan familias `*__icon-btn` propias).
  - Columna lateral sticky: progreso del borrador (`completionPercent`), métricas, checklist de nombres, salto directo a cada campo (`focusField`) y buenas prácticas con `<x-form-aside-guide>`.
- Cálculos de resumen en el componente Alpine: `namedFields`, `unnamedFields`, `completionPercent`. El nombre de la matriz se sincroniza con `matrixName` (`x-model`).
- Las reglas de validación del backend no cambian: `fields.*.label` es obligatorio, por eso la interfaz avisa antes de guardar.

## Documentación relacionada

- Brief: `docs/briefs/FEAT-052.md`.
- Usuario: `docs/user/matrices.md`.
