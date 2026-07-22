# Modulo Documentos de Calidad

## Objetivo

Permitir al director de Calidad publicar documentos (Word/Excel) o enlaces externos, registrar la metadata documental del negocio, asignar que areas pueden consultarlos o descargarlos, y activar o inactivar cada publicacion.

## Alcance actual

### Identificacion del documento (formulario Administrar)

| Campo negocio | Columna BD | Catalogo |
|---------------|------------|----------|
| Codigo | `code` | Texto libre |
| Nombre del documento o Registro | `title` | Texto libre |
| Proceso al que pertenece | `process_key` | `quality-documents.processes` |
| Tipo | `document_type` | `quality-documents.types` |
| Origen | `origin` | `quality-documents.origins` |
| Estado del documento | `document_status` | `quality-documents.document_statuses` |
| Estado de la actividad | `activity_status` | `quality-documents.activity_statuses` |
| Tipo de almacenamiento | `storage_type` | `quality-documents.storage_types` |
| Version actual | `current_version` | Texto libre (opcional) |
| Fecha ultima actualizacion | `last_updated_at` | Fecha (opcional) |
| Tiempo de retencion documental | `retention_period` | Texto libre (opcional) |
| Disposicion final | `final_disposition` | Texto libre (opcional) |

- `type` (tipo de recurso: `file` o `link`) es independiente de `storage_type` (Digital / Impreso / Digital e impreso).
- `is_active` controla visibilidad en biblioteca y Mis documentos; es distinto de `document_status` (Elaboracion / Revision / Aprobado).
- `description` permanece en BD pero no se muestra en el formulario principal.

### Tablero y visibilidad

- Tablero compartido `Documentos` integrado en la navegacion por area.
- Sub-pestanas:
  - `Biblioteca` (consulta por area)
  - `Mis documentos` (documentos asignados al usuario)
  - `Administrar` (solo Calidad con permiso de gestion)
- Tipos de recurso soportados:
  - Archivo: `.doc`, `.docx`, `.xls`, `.xlsx` (max. 10 MB)
  - Enlace externo (URL validada)
- Asignacion de visibilidad por area mediante checkboxes.
- Asignacion de visibilidad por usuario especifico mediante checkboxes con buscador.
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
- **Proceso ≠ Area de acceso:** el select "Proceso al que pertenece" usa el catalogo de procesos de calidad; las areas de visibilidad siguen siendo `access.areas`.
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

- `quality_documents` (incluye `code`, `process_key`, `document_type`, `origin`, `document_status`, `activity_status`, `storage_type`, `current_version`, `last_updated_at`, `retention_period`, `final_disposition`)
- `quality_document_areas`
- `quality_document_users`

## Catalogos en config

Archivo principal: [`config/quality-documents.php`](c:/laragon/www/SJSEGURIDAD/config/quality-documents.php)

- `processes` — procesos de calidad (gestion gerencial, SST, gestion documental, etc.)
- `types` — tipos de documento (procedimiento, formato, manual, plan, etc.)
- `origins` — interno / externo
- `document_statuses` — elaboracion / revision / aprobado
- `activity_statuses` — pendiente / en proceso / actualizada
- `storage_types` — digital / impreso / digital e impreso

Visibilidad por area: `access.areas`

## Archivos clave

- Controlador: [`app/Http/Controllers/QualityDocuments/QualityDocumentController.php`](c:/laragon/www/SJSEGURIDAD/app/Http/Controllers/QualityDocuments/QualityDocumentController.php)
- Modelo: [`app/Models/QualityDocument.php`](c:/laragon/www/SJSEGURIDAD/app/Models/QualityDocument.php)
- Requests: `app/Http/Requests/QualityDocuments/`
- Vistas: `resources/views/modules/quality-documents/`
- Trait de pestanas: [`app/Traits/HasQualityDocumentTabs.php`](c:/laragon/www/SJSEGURIDAD/app/Traits/HasQualityDocumentTabs.php)

## Pruebas

- [`tests/Feature/QualityDocumentModuleTest.php`](c:/laragon/www/SJSEGURIDAD/tests/Feature/QualityDocumentModuleTest.php)

## Riesgos

- En hosting compartido verificar `upload_max_filesize` y `post_max_size` >= 10M.
- Los archivos se almacenan en `storage/app/private/quality-documents`; al eliminar un documento se borra el archivo fisico.
- Documentos migrados desde `root_process` pueden requerir revision manual si el proceso no tenia equivalente en el nuevo catalogo.

## Pendientes (v2)

- Categorias o etiquetas de documentos.
- Notificaciones por correo al publicar.
- Versionado historico de revisiones.

## Referencias

- Guia de usuario: [`docs/user/quality-documents.md`](../user/quality-documents.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
