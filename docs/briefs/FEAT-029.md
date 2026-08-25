# Feature Brief — FEAT-029

> Brief final del Arquitecto (2026-08-21). Consolida `docs/briefs/FEAT-029-analyst.md` + respuestas del usuario. Evoluciona FEAT-027.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-029 |
| Modulo / area | Gestion humana — tablero **Plantillas Word** + cartas en **Ficha empleados** |
| Titulo | Tablero plantillas Word (tipo documento) + modal generar cartas (seleccion 1/N → docx/zip) |
| Solicitante | Manuel-E / AgentSj |
| Fecha | 2026-08-21 |

## Objetivo

Hoy las plantillas Word viven amarradas a la **causal** (pack fijo RENUNCIA de 3 docs siempre en ZIP) y se administran en **Catalogos → Causal desvinculacion**. El negocio necesita:

1. Un **tablero propio** en el sidebar de Gestion humana para administrar plantillas clasificadas por **tipo de documento** (catalogo editable).
2. Al desvincular, **elegir** 1 o N plantillas de tipo desvinculacion → un `.docx` o un `.zip`, persistido en el periodo para **Descargar**.

## Decisiones de negocio (cerradas)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Nombre sidebar | **Plantillas Word** |
| 2 | Permisos admin tablero | Permiso **nuevo** dedicado (`plantillas_word.*` + board) |
| 2b | Generar / Descargar | Solo `ficha_empleados.terminate` |
| 3 | Plantillas RENUNCIA existentes | **No** migrar contenido; el usuario las vuelve a subir a mano |
| 4 | Persistencia | **Siempre** (docx o zip) en el periodo; Generar **reemplaza** el anterior |
| 5 | Tipos de documento | **Catalogo editable** en el tablero |
| 6a | Agregar plantilla | Etiqueta + tipo + archivo `.docx` |
| 6b | Reemplazar | **Solo** archivo `.docx` (etiqueta/tipo se mantienen) |
| 6c | Eliminar | Con confirmacion |
| 7 | Catalogos Causal | **Retirar** admin de plantillas; solo tablero nuevo |
| 8 | Generar | **Todas** las causales (periodo cerrado), filtrando plantillas tipo desvinculacion |
| 9 | Doc usuario | Solo uso de pantallas (tablero + modal) |

## Alcance

### Incluye

1. Tablero sidebar **Plantillas Word** (patron Archivo / Ficha): lista unica de plantillas con columna tipo; CRUD tipos; alta/reemplazo/baja de plantillas.
2. Modal **Generar cartas** en ficha (vinculo cerrado): lista plantillas tipo `desvinculacion`; seleccion minima 1; 1 → descarga `.docx`; 2+ → `.zip`.
3. Boton **Descargar** sirve el ultimo archivo persistido del periodo si existe; **sin** boton Regenerar aparte (Generar abre modal y sobrescribe).
4. Persistencia en `employee_ficha_employment_periods` (`termination_letter_path` + `termination_letter_type` = `docx` \| `zip`).
5. Retiro de UI/rutas de upload plantillas en Catalogos → Causal.
6. Migraciones de esquema (no `migrate:fresh`); seed del tipo `desvinculacion`; limpieza de filas legacy pack RENUNCIA (sin conservar archivos/contenido).
7. Adaptacion de servicios FEAT-027 (seleccion por IDs; quitar gate por causal).
8. Audit de admin plantillas/tipos y generate/download.
9. Tests PHPUnit admin + generar/descargar + permisos.
10. Documentacion tecnica y de usuario (uso de pantallas).

### Fuera de alcance

- Editor Word / WYSIWYG dentro de la app.
- Conversion automatica Excel → Word.
- Envio por correo de cartas.
- Generacion masiva para muchos empleados.
- Flujo operativo de generacion por **contratacion** (u otros tipos): el catalogo **permite crear** el tipo, pero no hay modal/flujo de generacion fuera de desvinculacion en v1.
- Cambiar motor de placeholders / firmante GH (reutilizar FEAT-027).
- Migracion automatica de las 3 plantillas RENUNCIA existentes.

## Reglas de negocio

1. **Admin tablero:** ver board + listar/CRUD tipos y plantillas solo con permisos `plantillas_word.*` (y bypass `manage.users`).
2. **Tipos:** catalogo con `code` unico (slug estable), `name` visible, `is_active`, `sort_order`. No eliminar un tipo que tenga plantillas asociadas (bloquear o exigir reasignacion); preferir desactivar.
3. **Plantillas:** registro independiente de causal; pertenece a un tipo; tiene `label`, `sort_order`, `template_path` (docx privado).
4. **Agregar:** obligatorio etiqueta + tipo activo + archivo `.docx`.
5. **Reemplazar:** solo `.docx`; no cambia etiqueta ni tipo.
6. **Eliminar plantilla:** confirmacion UI; borra archivo en disco + fila.
7. **Generar:** periodo `cerrado`; permiso `ficha_empleados.terminate`; plantillas seleccionadas deben existir, tener archivo y ser de tipo `desvinculacion` (code fijo del seed); minimo 1 ID.
8. **Salida:** 1 plantilla → persistir y descargar `.docx`; N → ZIP con los docx renderizados; siempre reemplaza path/tipo previos del periodo.
9. **Descargar:** 404 si no hay path o archivo ausente; no regenera.
10. **Visibilidad Generar:** cualquier causal de desvinculacion en periodo cerrado (quitar dependencia de `termination_letter_supported_causes` / packs RENUNCIA). Si no hay plantillas de tipo desvinculacion con archivo, el modal lo indica / Generar valida.
11. **Placeholders / firmante:** sin cambio de negocio (`termination_letter_placeholders`, `termination_letter_signatory`).

## Permisos (`config/access.php`)

### Naming (justificacion)

Alineado a **Archivo** / **Ficha** (tablero de area GH independiente):

| Prefijo | Patron existente | Aplicacion |
| --- | --- | --- |
| `view.board.gestion_humana.{board}` | `archivo`, `ficha_empleados` | Visibilidad sidebar |
| `{modulo}.view` / `{modulo}.manage` | `archivo.view` / `archivo.manage` | Accion funcional |

**No** reutilizar `ficha_empleados.manage` para el tablero: el usuario pidio permiso nuevo y la administracion deja de vivir en Catalogos de Ficha.

### Keys concretas

| Permiso | Rol(es) por defecto | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.plantillas_word` | `super-admin` (todos); asignar a `administrador` en seeder o Admin UI | Ver tablero **Plantillas Word** en sidebar GH |
| `plantillas_word.view` | Idem | Ver listado de plantillas y tipos (solo lectura) |
| `plantillas_word.manage` | Idem | Agregar / reemplazar / eliminar plantillas; CRUD tipos (implica view en AccessService) |
| `ficha_empleados.terminate` | Sin cambio de asignacion | **Generar cartas** y **Descargar** desde ficha (ya existente) |

### Cambios en `config/access.php`

1. `system_permissions`: agregar `plantillas_word.view`, `plantillas_word.manage`.
2. `boards`: `'plantillas_word' => 'Plantillas Word'`.
3. `board_canonical_areas`: `plantillas_word` → `home => gestion_humana`, `base_area_tab => false` (igual Archivo/Ficha).
4. `admin_ui.other_areas.gestion_humana.subgroups.boards.permissions`: agregar `view.board.gestion_humana.plantillas_word`.
5. Nuevo subgroup `plantillas_word` con `plantillas_word.view`, `plantillas_word.manage`.

### Seeders / sync

- `PermissionCatalog::sync()` crea permisos al incluir board + system_permissions.
- Actualizar `PermissionCatalog::configuredNames()` para **rechazar** `plantillas_word` fuera de `gestion_humana` (mismo if que `archivo` / `ficha_empleados`).
- `RoleAndPermissionSeeder`: `super-admin` recibe todos. Recomendado: otorgar a rol `administrador` los tres permisos nuevos (`view.board…`, `view`, `manage`) para que el tablero quede usable tras seed; **no** tocar asignaciones de `ficha_empleados.terminate` (sigue siendo asignacion operativa GH).
- Bypass: `manage.users` en `PlantillasWordAccessService` (patron Archivo).

### Generar / Descargar

Sin permiso nuevo: `FichaEmpleadosAccessService` / authorize terminate existente.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (area GH; no modulo compartido).

### Tablero Plantillas Word (nuevo grupo)

| Metodo | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/plantillas-word` | `gestion-humana.plantillas-word.index` | board + `plantillas_word.view` (o manage) |
| POST | `/gestion-humana/plantillas-word/tipos` | `gestion-humana.plantillas-word.types.store` | `plantillas_word.manage` |
| PATCH | `/gestion-humana/plantillas-word/tipos/{type}` | `gestion-humana.plantillas-word.types.update` | `plantillas_word.manage` |
| DELETE | `/gestion-humana/plantillas-word/tipos/{type}` | `gestion-humana.plantillas-word.types.destroy` | `plantillas_word.manage` |
| POST | `/gestion-humana/plantillas-word/plantillas` | `gestion-humana.plantillas-word.templates.store` | `plantillas_word.manage` |
| POST | `/gestion-humana/plantillas-word/plantillas/{template}/reemplazar` | `gestion-humana.plantillas-word.templates.replace` | `plantillas_word.manage` |
| DELETE | `/gestion-humana/plantillas-word/plantillas/{template}` | `gestion-humana.plantillas-word.templates.destroy` | `plantillas_word.manage` |
| GET | `/gestion-humana/plantillas-word/plantillas/{template}/descargar` | `gestion-humana.plantillas-word.templates.download` | `plantillas_word.view` o manage |

### Cartas en Ficha (adaptar / ampliar)

| Metodo | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/gestion-humana/ficha-empleados/empleados/periodos/{period}/cartas/plantillas` | `gestion-humana.ficha-empleados.employees.period.letters.templates` | `ficha_empleados.terminate` |
| POST | `.../periodos/{period}/cartas/generar` | `...period.letters.generate` (existente) | `ficha_empleados.terminate` — body: `template_ids` (array, min 1) |
| GET | `.../periodos/{period}/cartas/descargar` | `...period.letters.download` (existente) | `ficha_empleados.terminate` |

Endpoint GET plantillas: JSON (id, label, sort_order) filtrado a tipo `desvinculacion` con archivo presente; alimenta el modal.

### Retirar (Catalogos Causal)

Eliminar rutas:

- `gestion-humana.ficha-empleados.catalogs.termination-letter-template.upload`
- `...download`
- `...delete`

## Base de datos

**Prohibido** `migrate:fresh` / wipe. Solo `migrate` incremental.

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `word_document_types` | create | Catalogo editable de tipos |
| `termination_letter_document_templates` | alter | Dejar de depender de causal/packs; FK a tipo |
| Filas legacy RENUNCIA | data step en migracion | Borrar filas pack (y archivos en disco si existen); **no** reasignar a tipo nuevo |
| Seed tipo `desvinculacion` | seeder o insert en migracion | Code `desvinculacion`, name `Desvinculacion`, active, sort 1 |
| `employee_ficha_employment_periods` | sin alter estructural | Reusar `termination_letter_path` + `termination_letter_type` (`docx` \| `zip`) |

### `word_document_types`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `code` | string(50) unique | Slug estable (`desvinculacion`); no editable a la ligera tras crear (o solo admin avanzado); preferir editar `name` |
| `name` | string(255) | Etiqueta UI (“Desvinculacion”) |
| `is_active` | boolean default true | |
| `sort_order` | unsignedInt default 0 | |
| `timestamps` | | |

### Alter `termination_letter_document_templates`

Orden sugerido (una o dos migraciones):

1. Crear `word_document_types` + insert seed `desvinculacion`.
2. Agregar `word_document_type_id` nullable FK → `word_document_types.id` (`nullOnDelete` o `restrict`).
3. **Data cleanup (acorde a decision 3):** eliminar filas con `termination_cause_code = 'RENUNCIA'` (y cualquier fila sin tipificar); borrar archivos en `template_path` si existen en disco. No migrar contenido a tipo nuevo.
4. Hacer `word_document_type_id` **NOT NULL**.
5. Eliminar unique `term_letter_tpl_cause_doc_uq` e indice por causa.
6. Dropear columnas obsoletas: `termination_cause_code`, `document_key`, `is_required` (ya no hay pack required).
7. Conservar: `id`, `label`, `sort_order`, `template_path`, `timestamps` + FK tipo.

Modelo: renombrar conceptualmente sigue siendo `TerminationLetterDocumentTemplate` en v1 (menos churn) **o** renombrar a `WordDocumentTemplate` si el Feature Agent prefiere claridad; si se renombra, actualizar servicios/tests en la misma entrega. **Recomendacion Arquitecto:** mantener nombre de modelo/tabla `termination_letter_document_templates` en v1 (solo alter) para reducir riesgo; documentar que ahora son plantillas por tipo, no por causal.

### Periodo — persistencia

| Campo existente | Uso FEAT-029 |
| --- | --- |
| `termination_letter_path` | Ruta relativa disco `local` (docx o zip) |
| `termination_letter_type` | `docx` o `zip` (antes solo `zip`) |

Al generar: borrar archivo anterior si existe; escribir nuevo; actualizar ambos campos.

### Config `employee_ficha.php`

- **Deprecar / eliminar** uso operativo de `termination_letter_supported_causes` y `termination_letter_packs` (quitar lecturas en codigo; limpiar keys en config en la misma entrega).
- Conservar `termination_letter_placeholders` y `termination_letter_signatory`.
- Agregar clave estable, ej. `word_document_type_codes.desvinculacion` => `'desvinculacion'` (o `termination_letter_document_type_code` => `desvinculacion`) para que el modal/generador no hardcodee el string en multiples sitios.

## Capas a implementar

- [ ] Migracion(es): `word_document_types` + alter templates + cleanup + seed tipo
- [ ] Modelo(s): `WordDocumentType`; actualizar `TerminationLetterDocumentTemplate` (relacion `type()`, scopes `forTypeCode`, `withFile`)
- [ ] Access: `PlantillasWordAccessService`; enganchar `NavigationResolver` + `User::defaultPlantillasWordBoardUrl()`; `PermissionCatalog` reject board fuera GH
- [ ] Controlador(es): `PlantillasWordController` (index + CRUD tipos/plantillas); adaptar `TerminationLetterController` (list templates JSON, generate con IDs, download mime/extension); **quitar** metodos catalog upload/download/delete
- [ ] Form Request(s): store tipo; update tipo; store plantilla (label, type_id, file docx); replace plantilla (file); generate letters (`template_ids`)
- [ ] Services: `TerminationLetterTemplateManager` (paths por tipo id, CRUD archivo); `TerminationLetterPackGeneratorService` → generar por IDs, 1=docx / N=zip, sin gate causal, persistir type; VariableBuilder / DocxRenderer sin cambio funcional
- [ ] Vistas Blade: `resources/views/areas/gestion_humana/plantillas-word/` (index tablero); retirar include `termination-letter-templates-admin` de catalogs; actualizar `termination-letter-actions` (Generar abre modal; Descargar si path)
- [ ] JavaScript: modal seleccion plantillas (checkbox, validar ≥1, POST generate, descarga blob/redirect)
- [ ] Export Excel: no aplica
- [ ] Audit: eventos `termination_letter_template` (store/replace/delete), opcional `word_document_type` (CRUD), `termination_letter_pack` generate/download (metadata: template_ids, output type)
- [ ] Tests: admin permisos tablero; CRUD tipo/plantilla; generate 1 docx / N zip; replace path; todas causales; 403 sin terminate; catalog routes 404; seed tipo existe

## Componentes reutilizables

- Disco `local` + paths bajo `ficha-empleados/letter-templates/{typeId}/` y `ficha-empleados/termination-letters/{periodId}/`.
- `phpoffice/phpword` `TemplateProcessor` (existente).
- Estilo nav chrome pills (sidebar board).
- Confirmacion SweetAlert2 / `confirm` nativo alineado al modulo Ficha.
- No Repository; Services existentes del dominio TerminationLetter.

## Navegacion / sidebar

Patron Archivo:

1. Board key `plantillas_word` en `boards`.
2. `NavigationResolver`: si `$boardKey === 'plantillas_word'` y area `gestion_humana` y `PlantillasWordAccessService::canViewBoard`, route `gestion-humana.plantillas-word.index`.
3. Active: `str_starts_with($routeName, 'gestion-humana.plantillas-word.')`.
4. `board_canonical_areas` + `PermissionCatalog` reject fuera de GH.

## Documentacion a actualizar

- [ ] `docs/modules/ficha-empleados.md` — seccion cartas FEAT-027 → FEAT-029 (modal, tipo, persistencia, sin catalog Causal)
- [ ] `docs/user/ficha-empleados.md` — uso Generar/Descargar (solo pantallas)
- [ ] `docs/modules/plantillas-word.md` — doc tecnica del tablero (nuevo)
- [ ] `docs/user/plantillas-word.md` — uso admin tipos/plantillas (nuevo; solo pantallas)
- [ ] `docs/INDEX.md` — enlaces nuevos
- [ ] `docs/ACCESS_CONTROL.md` — permisos board + plantillas_word.*
- [ ] `README.md` — solo si el indice de modulos lo requiere

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board, permisos, admin_ui, board_canonical_areas |
| `app/Support/PermissionCatalog.php` | Reject board fuera GH |
| `app/Services/Navigation/NavigationResolver.php` | Entrada sidebar |
| `database/seeders/RoleAndPermissionSeeder.php` | Defaults administrador (recomendado) |
| `config/employee_ficha.php` | Quitar packs/causas; code tipo desvinculacion |
| `routes/areas/gestion_humana.php` | Rutas tablero + cartas; quitar catalog templates |
| `app/Models/User.php` | Helper URL default board (patron Archivo) |

Flag `shared-files` ya anotado en `docs/TASKS.md`.

## Criterios de aceptacion

1. Sidebar Gestion humana muestra **Plantillas Word** solo con `view.board.gestion_humana.plantillas_word` (o bypass admin).
2. Usuario con `plantillas_word.manage` puede: crear tipo; crear plantilla (etiqueta+tipo+docx); reemplazar solo docx; eliminar con confirmacion; descargar master.
3. Usuario sin `plantillas_word.manage` no puede mutar; sin board no ve el tablero.
4. Tras migrate + seed existe tipo `desvinculacion` / “Desvinculacion”; **no** aparecen las 3 plantillas legacy RENUNCIA (hay que subirlas de nuevo).
5. En ficha, periodo cerrado de **cualquier** causal: boton **Generar cartas** (con `terminate`); abre modal solo con plantillas tipo desvinculacion que tengan archivo.
6. Seleccion 1 → descarga `.docx` y queda path/type=`docx` en el periodo; seleccion N → `.zip` y type=`zip`; Generar otra vez **reemplaza** el archivo anterior.
7. **Descargar** sirve el ultimo archivo guardado; no existe boton **Regenerar** separado.
8. Catalogos → Causal **no** muestra ni ofrece upload/descarga/borrado de plantillas Word; rutas viejas responden 404.
9. Generar/Descargar sin `ficha_empleados.terminate` → 403.
10. Audit registra generate/download y mutaciones de plantillas (y tipos si aplica).
11. `php artisan test` — suite TerminationLetter / PlantillasWord nueva o actualizada pasa.

## Validacion local

1. `php artisan migrate` (sin fresh); verificar tipo seed y tabla templates limpia de RENUNCIA.
2. Asignar permisos board + manage a un admin GH; subir ≥2 plantillas tipo Desvinculacion.
3. Desvincular empleado (cualquier causal); Generar 1 y N; Descargar; regenerar via Generar y confirmar reemplazo.
4. Verificar Catalogos Causal sin UI de plantillas.
5. `php artisan test --compact --filter=TerminationLetter` (y filtro PlantillasWord si aplica).
6. `vendor/bin/pint --dirty` en entrega de codigo.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Operadores esperan las 3 plantillas RENUNCIA ya cargadas | Comunicar en cierre: hay que **re-subir**; seed solo crea el tipo |
| Periodos con ZIP viejo FEAT-027 | Descargar sigue funcionando si el archivo existe; Generar nuevo reemplaza |
| `PermissionCatalog` / sidebar olvidan el board | Checklist shared-files + test de visibilidad |
| Borrar tipo con plantillas | Restrict en destroy + mensaje UI |
| Confundir `plantillas_word.manage` con `ficha_empleados.manage` | Admin UI labels claros; doc ACCESS_CONTROL |
| Modal sin plantillas | Mensaje vacio + validacion server |
| Nombre modelo/tabla legado “termination_*” | Aceptable en v1; documentar; rename opcional futuro |

Dependencia: FEAT-027 en produccion (servicios, PhpWord, columnas periodo).

## Plan de implementacion sugerido (para AgentSj / Task Cards)

| Tarea | Contenido |
| --- | --- |
| T1 | Permisos + access + NavigationResolver + seeders (shared-files) |
| T2 | Migraciones BD + modelos + seeder tipo desvinculacion |
| T3 | Tablero CRUD tipos/plantillas (controller, requests, vistas, audit) |
| T4 | Generador por IDs + endpoints modal/generate/download + UI ficha; retirar catalog |
| T5 | Tests + pint; handoff Revisor / Documentador |

## Aprobacion

- [x] Analista — vacios cerrados (respuestas usuario 2026-08-21)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion (si AgentSj lo requiere antes de Feature)
- [ ] AgentSj — Task Cards + orquestacion Feature
