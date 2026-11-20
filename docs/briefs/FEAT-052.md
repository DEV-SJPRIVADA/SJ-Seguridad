# FEAT-052 — Matrices dinámicas

## Objetivo

Permitir que usuarios con permiso transversal creen matrices con formularios propios (campos configurables), compartan su diligenciamiento por usuario o por área, registren datos con adjuntos y consulten la información en un listado filtrable con exportación a Excel.

## Alcance V1

- Constructor visual de matrices: secciones y campos tipados (texto, área de texto, número, moneda, fecha, selección, opción única, selección múltiple, adjunto, firma).
- Versionado: publicar una matriz congela sus campos; editar una matriz publicada crea una versión nueva.
- Compartir por usuario o por área con tres capacidades y dos alcances:
  - Capacidades: **ver**, **registrar**, **editar**.
  - Alcance: **solo registros propios** (creados o asignados) o **todos los registros**.
  - Defaults: creador ve todo; compartir con área o usuario habilita ver + registrar solo lo suyo; "ver registros de otros" y "editar registros de otros" son casillas explícitas.
- Asignación de un registro a un usuario; "solo los míos" incluye los asignados.
- Listado DataTables server-side con filtros, columnas configurables y detalle lateral.
- Adjuntos en disco privado, descarga autorizada y previsualización de PDF/imágenes.
- Exportación única por matriz que respeta filtros y alcance.
- Archivado lógico (nunca borrado físico) y auditoría central.

## Fuera de V1

- Tablas repetibles dentro del formulario (V1.1).
- Lógica condicional, campos calculados y fórmulas.
- Flujo de aprobación con estados adicionales.
- Previsualización de Word/Excel (requiere conversión externa).
- Notificaciones por correo al compartir o asignar (V1.1).

## Permisos

| Permiso | Tipo | Uso |
| --- | --- | --- |
| `matrices.manage` | Spatie (global) | Crear matrices, editar definición, publicar, compartir y archivar |
| Ver | Por matriz (BD) | Abrir la matriz, listado y detalle |
| Registrar | Por matriz (BD) | Crear y editar registros propios/asignados, adjuntar |
| Editar | Por matriz (BD) | Editar registros de otros (requiere alcance "todos") |

- No se crean permisos de pestaña (`matrices.tab.*`): la visibilidad se calcula por acceso real.
- `MatrixAccessResolver` es el único punto de verdad; `MatrixPolicy` y el filtro SQL lo consumen.
- Unión de otorgamientos: gana el más permisivo. `editar a otros` implica `ver a otros`.
- Usuarios sin área no ven el tablero.

## Modelo de datos

- `matrices`: `code`, `name`, `area_key`, `owner_user_id`, `status`, `current_version_id`, `submission_counter`, `archived_at`.
- `matrix_versions`: `matrix_id`, `version_number`, `status`, `published_at`, `published_by_user_id`.
- `matrix_fields`: `matrix_version_id`, `field_key`, `label`, `type`, `section`, `help`, `required`, `options`, `rules`, `sort`, `col_span`.
- `matrix_user_access`: `matrix_id`, `user_id`, `can_register`, `can_view_others`, `can_edit_others`, `expires_at`.
- `matrix_area_access`: `matrix_id`, `area_key`, mismas capacidades.
- `matrix_submissions`: `matrix_id`, `matrix_version_id`, `folio`, `status`, `created_by_user_id`, `assigned_to_user_id`, `submitted_at`, `archived_at`.
- `matrix_submission_values`: `submission_id`, `field_id`, `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`.
- `matrix_submission_attachments`: `submission_id`, `field_id`, `original_name`, `stored_path`, `mime_type`, `size_bytes`.

Reglas: sin `enum()`, estados como `string` indexado, decimales `(18,4)`, FK con `nullOnDelete` para usuarios, folio único por matriz, borrado lógico.

## Rutas

Módulo compartido en `/matrices/{module}` (mismo patrón que `quality-documents`), con `{module}` validado contra `config('access.areas')`.

- `GET /matrices/{module}` — mis matrices y compartidas.
- `GET|POST /matrices/{module}/crear` — constructor.
- `PUT /matrices/{module}/{matrix}` — guardar borrador.
- `POST /matrices/{module}/{matrix}/publicar` — nueva versión.
- `GET|POST/DELETE /matrices/{module}/{matrix}/compartir` — accesos.
- `GET /matrices/{module}/{matrix}/registros` + `/datatable` — listado.
- `GET|POST/PUT /matrices/{module}/{matrix}/registros/{submission}` — registro y edición.
- `GET /matrices/{module}/{matrix}/exportar` — Excel.
- `POST/GET/DELETE .../adjuntos/{attachment}` — carga, descarga y previsualización.

## Fases

1. BD, modelos, resolver de accesos y policy (**en curso**).
2. Constructor backend + vistas + publicación.
3. Compartir por usuario/área.
4. Registro de datos + adjuntos.
5. DataTable + filtros + detalle + export.
6. Auditoría, documentación y build.

## Criterios de aceptación

1. El creador ve todos los registros de su matriz.
2. Un área compartida por defecto no ve ni edita registros ajenos; "editar todo" es explícito.
3. Un usuario individual ve solo los suyos y los asignados.
4. Un usuario sin permiso no accede ni por URL directa.
5. Los adjuntos solo se descargan con autorización; PDF/imagen se previsualizan.
6. Editar una matriz publicada crea versión nueva y conserva los registros históricos.
7. El export respeta filtros y alcance.
8. `php artisan test --compact --filter=Matrix`, `pint` y `npm run build` en verde.

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-11-20 | Creación del brief con las decisiones aprobadas por el usuario. |
