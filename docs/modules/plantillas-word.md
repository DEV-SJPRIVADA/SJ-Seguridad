# Modulo Plantillas Word

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/plantillas-word.md`.
> Area: Gestion humana. Feature: FEAT-029 (evoluciona FEAT-027).

## Objetivo

Administrar **tipos de documento** y **plantillas Word (.docx)** en un tablero propio del sidebar de Gestion Humana, independientes de la causal de desvinculacion. Las plantillas de tipo `desvinculacion` alimentan el modal **Generar cartas** en Ficha empleados (seleccion 1/N â†’ `.docx` o `.zip`).

## Alcance actual

- Tablero sidebar **Plantillas Word** (`plantillas_word`): catalogo editable de tipos + lista unica de plantillas con columna tipo.
- CRUD tipos: crear, editar (nombre/activo/orden/code), eliminar (bloqueado si hay plantillas asociadas; preferir desactivar).
- Plantillas: agregar (etiqueta + tipo activo + `.docx`), reemplazar (solo archivo), eliminar (confirmacion), descargar master.
- Generacion/descarga de cartas: vive en **Ficha empleados** (permiso `ficha_empleados.terminate`); ver [`ficha-empleados.md`](ficha-empleados.md).
- Seed del tipo `desvinculacion`; **no** migran las plantillas legacy pack RENUNCIA (hay que re-subir).
- **Fuera de alcance v1:** editor Word en app, envio por correo, generacion masiva, flujos de generacion para tipos distintos de desvinculacion (el catalogo permite crear el tipo, pero no hay modal fuera de ficha/desvinculacion).

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo `auth`/`active` global + `password.changed`).

| Metodo | URI | Nombre | Middleware / permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/plantillas-word` | `gestion-humana.plantillas-word.index` | `plantillas_word.view` o `manage` (o bypass `manage.users`) |
| POST | `/gestion-humana/plantillas-word/tipos` | `gestion-humana.plantillas-word.types.store` | `plantillas_word.manage` |
| PATCH | `/gestion-humana/plantillas-word/tipos/{type}` | `gestion-humana.plantillas-word.types.update` | `plantillas_word.manage` |
| DELETE | `/gestion-humana/plantillas-word/tipos/{type}` | `gestion-humana.plantillas-word.types.destroy` | `plantillas_word.manage` |
| POST | `/gestion-humana/plantillas-word/plantillas` | `gestion-humana.plantillas-word.templates.store` | `plantillas_word.manage` |
| POST | `/gestion-humana/plantillas-word/plantillas/{template}/reemplazar` | `gestion-humana.plantillas-word.templates.replace` | `plantillas_word.manage` |
| DELETE | `/gestion-humana/plantillas-word/plantillas/{template}` | `gestion-humana.plantillas-word.templates.destroy` | `plantillas_word.manage` |
| GET | `/gestion-humana/plantillas-word/plantillas/{template}/descargar` | `gestion-humana.plantillas-word.templates.download` | `plantillas_word.view` o `manage` |

Sidebar: visible solo con `view.board.gestion_humana.plantillas_word` (o bypass). El `index` exige `canView` (view\|manage\|bypass), no el permiso de board (patron Archivo).

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.plantillas_word` | Ver tablero **Plantillas Word** en sidebar GH |
| `plantillas_word.view` | Ver listado de tipos y plantillas; descargar master |
| `plantillas_word.manage` | CRUD tipos y plantillas (implica view en `PlantillasWordAccessService`) |

- Independientes de `ficha_empleados.manage` / `ficha_empleados.terminate`.
- Bypass: `manage.users`.
- Seed: `super-admin` todos; rol `administrador` recibe board + view + manage.
- Admin UI: subgroup `plantillas_word` bajo `gestion_humana`; board en subgroup `boards`.
- `PermissionCatalog`: rechaza board `plantillas_word` fuera de `gestion_humana`.

Servicio: `App\Services\GestionHumana\PlantillasWordAccessService` â€” `canViewPlantillasWordBoard()`, `canView()`, `canManage()`, `isAdminBypass()`.

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\PlantillasWordController` | Index tablero; store/update/destroy tipos; store/replace/destroy/download plantillas; audit |
| `StoreWordDocumentTypeRequest` | Alta tipo (`code`, `name`, `is_active`, `sort_order`) |
| `UpdateWordDocumentTypeRequest` | Edicion tipo |
| `StoreWordDocumentTemplateRequest` | Alta plantilla (`label`, `word_document_type_id`, archivo `.docx` max 5 MB) |
| `ReplaceWordDocumentTemplateRequest` | Solo archivo `.docx` |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `resources/views/areas/gestion_humana/plantillas-word/index.blade.php` | Tablero con pestanas `?tab=tipos` \| `?tab=plantillas` (module-tabs); redirects CRUD conservan la pestana |

## Modelos y tablas

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `App\Models\WordDocumentType` | `word_document_types` | `code` unique, `name`, `is_active`, `sort_order`; scopes `active`, `ordered`, `forCode`; `templates()` HasMany |
| `App\Models\TerminationLetterDocumentTemplate` | `termination_letter_document_templates` | Nombre/tabla legado FEAT-027; ahora FK `word_document_type_id` (NOT NULL). Sin `termination_cause_code` / `document_key` / `is_required`. Scopes `forTypeCode`, `withFile`, `ordered`; relacion `type()` |

### `word_document_types`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `code` | string(50) unique | Slug estable (`desvinculacion`); editable en UI â€” **riesgo operativo** si se renombra el code seed (Generar filtra por config) |
| `name` | string(255) | Etiqueta visible |
| `is_active` | boolean | |
| `sort_order` | unsignedInt | |
| `timestamps` | | |

### Migraciones

- `2026_08_21_121003_create_word_document_types_table.php` â€” create + insert seed `desvinculacion`.
- `2026_08_21_121005_alter_termination_letter_document_templates_for_word_document_types.php` â€” FK tipo, cleanup filas/archivos legacy RENUNCIA, drop columnas causa/pack. `down()` no restaura RENUNCIA.

Seeder: `database/seeders/WordDocumentTypeSeeder.php` (idempotente); referenciado desde `DatabaseSeeder`.

Config estable: `config/employee_ficha.php` â†’ `word_document_type_codes.desvinculacion` = `'desvinculacion'`. Packs/causas soportadas de FEAT-027 **retirados**.

## Servicios / jobs / mail (si aplica)

- `App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager` â€” paths bajo `ficha-empleados/letter-templates/{typeId}/`, CRUD archivo en disco `local`.
- Generacion de cartas (Ficha): `TerminationLetterPackGeneratorService` â€” por IDs, 1â†’docx / Nâ†’zip, sin gate por causal; ver doc Ficha.
- Audit: `EmployeeFichaAuditLogService` â€” `word_document_type` (store/update/destroy), `termination_letter_template` (store/replace/delete).
- `App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer` — procesamiento XML directo del `.docx` via `ZipArchive` + `DOMDocument`. **No usa TemplateProcessor de PhpWord.** Para cada parrafo `<w:p>` del XML (document, headers, footers, footnotes), concatena el texto de todos los `<w:r><w:t>`, busca/emplaza placeholders en el texto concatenado, y escribe el resultado en un unico `<w:t>` del primer run. Esto maneja correctamente los **placeholders fragmentados** por Word (split-runs).
- `App\Services\GestionHumana\Letter\LetterVariableBuilder` — builder generico (~90 variables) que extrae datos de `EmployeeFichaProfile`, `EmployeeFichaEmploymentPeriod`, `PersonalRequisitionFichaEntry` y `PersonalRequisition`.

### DocxRenderer: manejo de placeholders fragmentados (split-runs)

Microsoft Word puede dividir un placeholder como `[NOMBRE_COMPLETO]` en multiples nodos `<w:r><w:t>` al guardar el `.docx`:

```xml
<w:r><w:t>[NOM</w:t></w:r>
<w:r><w:t>BR</w:t></w:r>
<w:r><w:t>E_COMPLETO]</w:t></w:r>
```

`TemplateProcessor` de PhpWord busca el texto completo dentro de cada `<w:t>` individualmente, por lo que no encuentra el placeholder fragmentado. El `TerminationLetterDocxRenderer` resuelve esto:

1. Abre el `.docx` directamente como `ZipArchive`.
2. Para cada archivo XML (`word/document.xml`, headers, footers, footnotes), carga el `DOMDocument`.
3. Para cada `<w:p>`, concatena el texto plano de **todos** sus `<w:r><w:t>`.
4. Si el texto concatenado contiene placeholders, los reemplaza.
5. Escribe el resultado en el `<w:t>` del primer run (preservando formato) y vacia los demas.
6. Guarda el XML modificado de vuelta en el zip.

**Implicaciones:**
- Si un placeholder tiene formato variado (ej. parte en **negrita** y parte normal), el resultado hereda el formato del primer run.
- Los headers/footers tambien se procesan (hasta 9 headers y 9 footers).
- No depende del filesystem real del template; funciona con `Storage::fake()` en tests.

## Reglas de negocio

1. Admin tablero: board + view/manage propios; no reutilizar permisos de Ficha.
2. No eliminar tipo con plantillas asociadas (error UI); desactivar en su lugar.
3. Agregar plantilla: etiqueta + tipo **activo** + `.docx` obligatorio.
4. Reemplazar: solo archivo; etiqueta y tipo no cambian.
5. Eliminar plantilla: confirmacion; borra fila + archivo en disco.
6. Tras migrate: existe tipo `desvinculacion`; **cero** plantillas legacy RENUNCIA â€” operadores deben **re-subir**.
7. El modal Generar en ficha solo lista plantillas tipo `desvinculacion` con archivo presente (detalle en [`ficha-empleados.md`](ficha-empleados.md)).

## JavaScript / assets (si aplica)

- Confirmacion nativa/`confirm` en destroy de plantillas/tipos (alineado a Ficha).
- Modal de generacion de cartas: parciales en vistas de Ficha empleados (Alpine + eventos), no en este tablero.

## Export Excel (si aplica)

No aplica.

## Navegacion

- `NavigationResolver`: board `plantillas_word` â†’ `gestion-humana.plantillas-word.index` si `PlantillasWordAccessService::canViewPlantillasWordBoard`.
- Active: `str_starts_with($routeName, 'gestion-humana.plantillas-word.')`.
- `User::defaultPlantillasWordBoardUrl()` (patron Archivo/Ficha).
- `board_canonical_areas`: `plantillas_word` â†’ hogar `gestion_humana`, `base_area_tab => false`.

## Validacion local

1. `php artisan migrate` (sin fresh); verificar tipo seed y templates sin RENUNCIA.
2. Asignar board + manage; subir â‰¥2 plantillas tipo Desvinculacion.
3. En ficha, periodo cerrado: Generar 1 y N; Descargar; Generar de nuevo y confirmar reemplazo.
4. Catalogos â†’ Causal sin UI de plantillas; rutas viejas 404.
5. `php artisan test --compact --filter=PlantillasWord` y `--filter=TerminationLetter` / `WordDocumentTypeSchema`.

## Riesgos y pendientes

- Operadores esperan plantillas RENUNCIA ya cargadas â†’ comunicar re-subida.
- Editar `code` del tipo `desvinculacion` rompe el filtro de Generar (config + code).
- Codigo muerto FEAT-027 (observacion review): partial `termination-letter-templates-admin` y `UploadTerminationLetterTemplateRequest` ya no referenciados â€” limpieza post-feature.
- Nombre modelo/tabla `termination_letter_*` es legado; rename opcional futuro.
- Periodos con ZIP viejo FEAT-027: Descargar sigue si el archivo existe; Generar nuevo reemplaza.

### Guia para disenadores de plantillas Word

Para evitar problemas con placeholders no reemplazados:

1. **Escribir placeholders en un solo paso:** No copiar/pegar parcialmente. Seleccionar el placeholder completo, copiar y pegar de una sola vez en la posicion deseada.
2. **Evitar formato mixto dentro del placeholder:** No aplicar negrita/color/subrayado a partes del placeholder. El formato se aplica al placeholder completo.
3. **No usar estilos de parrafo que Word transforme:** Algunos estilos de lista o encabezado fuerzan saltos de run. Usar estilo "Normal" o un estilo personalizado simple.
4. **Re-guardar como .docx despues de editar:** Despues de modificar la plantilla, ir a Archivo > Guardar como > y seleccionar formato `.docx` (no `.doc`).
5. **Verificar con texto plano:** Abrir el `.docx` con Bloc de notas y buscar los `[PLACEHOLDER]`. Si aparecen completos, Word no los fragmentara.
6. **Regla general:** Si al abrir el `.docx` generado con el Bloc de notas los placeholders aparecen completos (sin espacios raros), el render los reemplazara correctamente.

## Tests

- `tests/Feature/GestionHumana/PlantillasWordBoardAccessTest.php`
- `tests/Feature/GestionHumana/PlantillasWordCrudTest.php`
- `tests/Feature/GestionHumana/WordDocumentTypeSchemaTest.php`
- Generacion/cartas: `tests/Feature/GestionHumana/TerminationLetterPackTest.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-029.md`](../briefs/FEAT-029.md)
- Review: [`docs/reviews/FEAT-029.md`](../reviews/FEAT-029.md)
- Doc usuario: [`docs/user/plantillas-word.md`](../user/plantillas-word.md)
- Cartas en ficha: [`docs/modules/ficha-empleados.md`](ficha-empleados.md)
- Control de acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
