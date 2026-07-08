# Modulo Documentos de Calidad

## Objetivo

Permitir al director de Calidad publicar documentos (Word/Excel) o enlaces externos, asignar que areas pueden consultarlos o descargarlos, y activar o inactivar cada publicacion.

## Alcance actual

- Campos del documento:
  - `title` (titulo)
  - `code` (codigo, ej. `SG-FR-001`)
  - `root_process` (proceso raiz: area propietaria del documento, distinta de las areas con acceso)
  - `document_type` (tipo de documento: Formato, Indicador, Instructivo, Manual, Matriz, Formulario, Documentos Generales)
  - `description` (descripcion)
  - `type` (tipo de recurso: archivo o enlace)
- Tablero compartido `Documentos` integrado en la navegacion por area.
- Sub-pestanas:
  - `Biblioteca` (consulta por area)
  - `Administrar` (solo Calidad con permiso de gestion)
- Tipos soportados:
  - Archivo: `.doc`, `.docx`, `.xls`, `.xlsx` (max. 10 MB)
  - Enlace externo (URL validada)
- Asignacion de visibilidad por area mediante checkboxes.
- Asignacion de visibilidad por usuario especifico mediante checkboxes con buscador.
- Tablero personal `Mis documentos` como pestaña del tablero `Documentos` (junto a `Biblioteca`), visible solo si el usuario tiene documentos activos asignados directamente.
- Activacion / inactivacion sin eliminar el registro.
- Descarga segura de archivos via controlador (disco `private`).

## Reglas de negocio

- Solo usuarios con el permiso funcional `manage.quality.documents` pueden administrar documentos (por ejemplo, la directora de Calidad).
- Varios usuarios pueden tener acceso al area Calidad; la gestion de documentos se asigna de forma independiente en Admin → Usuarios → pestaña Funcionalidad → categoria Calidad.
- El tablero `Documentos` aparece en **todas** las areas donde el usuario tenga acceso (`view.area.*` o `manage.area.*`).
- La biblioteca de cada area muestra unicamente los documentos activos asignados a esa area.
- Un documento puede asignarse a areas, a usuarios especificos, o ambos (modo combinado).
- Los documentos asignados solo a usuarios no aparecen en bibliotecas de area; el destinatario los ve en la pestaña `Mis documentos` del tablero `Documentos`.
- La pestaña `Mis documentos` aparece junto a `Biblioteca` cuando el usuario tiene al menos un documento activo asignado directamente.
- La pestaña `Administrar` solo aparece en el modulo `calidad` y requiere `manage.quality.documents`.
- Las rutas bajo `/administrar` responden solo con `module=calidad`; cualquier otro modulo devuelve 404.
- No se usa `view.board.{area}.documentos`; el tablero es automatico con el acceso al area.
- Documentos inactivos no aparecen en bibliotecas de area.
- La biblioteca filtra por el `{module}` de la URL (area del tablero activo).

## Rutas

Definidas en [`routes/modules/quality-documents.php`](c:/laragon/www/SJSEGURIDAD/routes/modules/quality-documents.php):

- `GET /quality-documents/{module}/mis-documentos`
- `GET /quality-documents/{module}/mis-documentos/{document}/descargar`
- `GET /quality-documents/{module}/mis-documentos/{document}/abrir`
- `GET /quality-documents/{module}/biblioteca`
- `GET /quality-documents/{module}/biblioteca/{document}/descargar`
- `GET /quality-documents/{module}/biblioteca/{document}/abrir`
- `GET /quality-documents/{module}/administrar`
- `GET /quality-documents/{module}/administrar/crear`
- `POST /quality-documents/{module}/administrar`
- `GET /quality-documents/{module}/administrar/{document}/editar`
- `PATCH /quality-documents/{module}/administrar/{document}`
- `PATCH /quality-documents/{module}/administrar/{document}/estado`
- `DELETE /quality-documents/{module}/administrar/{document}`

## Permisos

- `manage.quality.documents` — administracion completa del modulo (asignacion explicita por usuario).

## Tablas

- `quality_documents` (incluye `code`, `root_process`, `document_type`)
- `quality_document_areas`
- `quality_document_users`

## Catalogos en config

- `access.quality_document_types` — tipos de documento seleccionables.
- `access.areas` — se reutiliza para el `root_process` (proceso raiz).

## Archivos clave

- Controlador: [`app/Http/Controllers/QualityDocuments/QualityDocumentController.php`](c:/laragon/www/SJSEGURIDAD/app/Http/Controllers/QualityDocuments/QualityDocumentController.php)
- Requests: `app/Http/Requests/QualityDocuments/`
- Vistas: `resources/views/modules/quality-documents/`
- Trait de pestanas: [`app/Traits/HasQualityDocumentTabs.php`](c:/laragon/www/SJSEGURIDAD/app/Traits/HasQualityDocumentTabs.php)

## Pruebas

- [`tests/Feature/QualityDocumentModuleTest.php`](c:/laragon/www/SJSEGURIDAD/tests/Feature/QualityDocumentModuleTest.php)

## Riesgos

- En hosting compartido verificar `upload_max_filesize` y `post_max_size` >= 10M.
- Los archivos se almacenan en `storage/app/private/quality-documents`; al eliminar un documento se borra el archivo fisico.

## Pendientes (v2)

- Categorias o etiquetas de documentos.
- Notificaciones por correo al publicar.
- Estado borrador y versionado.
