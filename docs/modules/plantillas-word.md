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
| PATCH | `/gestion-humana/plantillas-word/plantillas/{template}` | `gestion-humana.plantillas-word.templates.update` | `plantillas_word.manage` (etiqueta / tipo / orden; modal `.plantillas-word-edit-modal`) |
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
| `resources/views/areas/gestion_humana/plantillas-word/index.blade.php` | Tablero con pestanas `?tab=plantillas` (default) \| `?tab=tipos`; en Plantillas filtros GET `q` / `type` / `file`; formularios de alta en secciones; selector `.docx` + acciones Lucide |

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
- `App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer` — `TemplateProcessor` PhpWord con macros canónicas `${CLAVE}` (+ fallback temporal `[CLAVE]`); previo merge de split-runs en XML.
- `App\Services\GestionHumana\Letter\LetterVariableBuilder` — builder único (~90 variables) para desvinculación, contratación y tipos futuros. Catálogo UI: `config/employee_ficha.php` → `letter_placeholders`. Modal **Variables disponibles** en `plantillas-word/index` con filtro Alpine (clave / descripción / categoría).

### Cómo agregar una variable nueva (checklist)

No hace falta tocar controladores de generación (`TerminationLetterController`, etc.). Pasos:

1. **Dato en sistema:** si el valor aún no existe, agregar campo (migración/modelo/formulario ficha) y asegurar que se guarda.
2. **UI (copia usuario):** registrar la clave en `config/employee_ficha.php` → `letter_placeholders` (categoría + descripción). Aparece como `${CLAVE}` en Plantillas Word.
3. **Valor al generar:** mapear la clave en `App\Services\GestionHumana\Letter\LetterVariableBuilder::build()` desde perfil, periodo, entrada ficha o requisición. Montos en letras: `App\Support\SpanishMoneyWords` (ej. `${SALARIO_EN_LETRAS}`).
4. **Probar:** plantilla con `${CLAVE}` → generar carta → el `.docx` no debe dejar `${CLAVE}` literal (salvo que el dato esté vacío).
5. **Docs:** actualizar esta sección / `docs/modules/ficha-empleados.md` si el campo es de negocio visible.

**Variables de salario:** `${SALARIO}` (número formateado) y `${SALARIO_EN_LETRAS}` (texto, mayúsculas, p. ej. `UN MILLÓN QUINIENTOS MIL PESOS`). Equivalente de vínculo: `${SALARIO_VINCULO}` / `${SALARIO_VINCULO_EN_LETRAS}`.

**No requerido:** cambios en `PlantillasWordController` (ya lee el config), ni en pack generators (ya usan `LetterVariableBuilder`).

### DocxRenderer: manejo de placeholders fragmentados (split-runs)

Microsoft Word puede dividir un placeholder como `${NOMBRE_COMPLETO}` en múltiples nodos `<w:t>`. El renderer concatena runs por párrafo **antes** de `TemplateProcessor` para que el reemplazo encuentre la macro completa.

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

1. **Usar el formato canónico del listado:** `${CLAVE}` (copiar desde la UI Plantillas Word). Ejemplo: `${NOMBRE_COMPLETO}`, `${DOCUMENTO}`.
2. **Escribir placeholders en un solo paso:** No copiar/pegar parcialmente. Seleccionar el placeholder completo, copiar y pegar de una sola vez.
3. **Evitar formato mixto dentro del placeholder:** No aplicar negrita/color/subrayado a partes del placeholder.
4. **No usar estilos de parrafo que Word transforme:** Algunos estilos de lista o encabezado fuerzan saltos de run. Usar estilo "Normal".
5. **Re-guardar como .docx despues de editar:** Archivo > Guardar como > `.docx` (no `.doc`).
6. **Mismo motor para todos los tipos:** desvinculacion, contratacion y tipos futuros usan el mismo catálogo; si el dato no existe en la ficha, la variable queda vacía.

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
