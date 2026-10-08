# Feature Brief — FEAT-044

> Brief final del Arquitecto (2026-10-08). Consolida decisiones cerradas del usuario + chat AgentSj (run log FEAT-044). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-044 |
| Modulo / area | Gestion humana — tablero nuevo **Cartas Notificación** (`cartas_notificacion`) |
| Titulo | Tablero GH **Cartas Notificación**: lote → Word (`.docx` / `.zip`) sin BD |
| Solicitante | Usuario / AgentSj (chat 2026-10-08 cartas notificacion GH) |
| Fecha | 2026-10-08 |

## Objetivo

Permitir a Gestión Humana generar **cartas de notificación** en lote desde un **tablero propio** del sidebar GH: el operador arma una **grilla en memoria** (cédula, nombre, fecha de terminación, firma), opcionalmente carga un Excel o pega cédulas, y descarga **un `.docx` (1 fila) o un `.zip` (N)**. La plantilla vive en **Plantillas Word** bajo el tipo `cartas_notificacion` (exactamente una activa con archivo). **No** se persisten lotes, filas ni archivos generados.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Permisos | Board `view.board.gestion_humana.cartas_notificacion` + **solo** `cartas_notificacion.edit` (**SIN** `.view`). `edit` implica acceso a la pantalla. |
| 2 | Shell | **Sin** Dashboard / **sin** pestañas: grilla directa al entrar al tablero. |
| 3 | Spatie | **Sin** migración automática de permisos; asignación **manual** en Admin. |
| 4 | Plantilla Word | Tipo fijo `cartas_notificacion`; **exactamente 1** plantilla activa con archivo (0 o >1 bloquean, como Vacaciones). |
| 5 | Grilla | Columnas editables: `CEDULA`, `NOMBRE_COMPLETO`, `FECHA_TERMINACION`, `FIRMA` (searchable-select catálogo firmas → `${FIRMA}` name + `${CARGO_FIRMA}` code). Resto de `letter_placeholders` desde Ficha por cédula al generar. |
| 6 | UI | Patrón Masivos / Cartas Vacaciones; chrome `module-ui-quality`; searchable-select; icon-btn canónicos. **Calidad visual desde el primer slice** (no prototipo feo). |
| 7 | Excel | Plantilla Excel + carga masiva **solo a grilla en memoria** (**NO** BD). Generar 1→docx / N→zip. Tope **500** filas. |
| 8 | Ficha | Sin ficha / inactivo: **genera** + **aviso** en fila; nombre editable. |
| 9 | Filtros | Filtros **en memoria** sobre la grilla; **Limpiar** = **vaciar grilla** (no solo reset de filtro). |
| 10 | Audit | Solo al **generate** / descarga. |
| 11 | Fecha | `FECHA_TERMINACION` en formato largo español (`LetterVariableBuilder::formatLongDate`). |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Hogar | Tablero **nuevo** `cartas_notificacion` en GH (no pestaña de Cliente interno ni Desvinculaciones) | Decisión usuario; board propio en sidebar. |
| Access | Servicio nuevo `App\Services\Access\CartasNotificacionAccessService`: `canViewBoard`, `canEdit` (= acceso pantalla + mutaciones). **Sin** `.view`. | Decisión 1; paridad Plantillas Word / Archivo (board + funcional). |
| Sidebar | Visible si board **o** `cartas_notificacion.edit` **o** bypass `manage.users`. Index y POST exigen `canEdit`. Solo board sin edit → 403 con mensaje claro. | Evita tablero huérfano; Admin asigna ambos manualmente. |
| Redirect | `User::defaultCartasNotificacionBoardUrl()` → index del módulo. Cablear en `NavigationResolver` + `SidebarVisibilityService` (hogar GH). | Mismo patrón Archivo / Plantillas Word. |
| Tipo Word | Seed idempotente `word_document_types.code = cartas_notificacion` + `config/employee_ficha.php` → `word_document_type_codes.cartas_notificacion` | Convención FEAT-043. |
| Regla plantilla | Contar plantillas del tipo con archivo presente y tipo activo = **exactamente 1**; si no, 422 / bloqueo UI | Decisión 4. |
| Generador | `CartasNotificacionGeneratorService` (área GH). Reutiliza `TerminationLetterDocxRenderer` + `TerminationLetterTemplateManager` + `ZipArchive` / `WordTempDirectory`. Sin persistencia de salida. | Clonar flujo `ClienteInternoCartasVacacionesGeneratorService`. |
| Variables | Extender `LetterVariableBuilder` con `buildForCartasNotificacion(row)`: catálogo vacío → fill ficha por cédula → **override** fila (`CEDULA`, `NOMBRE_COMPLETO`, `FECHA_TERMINACION` largo ES, `FIRMA`/`CARGO_FIRMA`, `FECHA`=hoy). | Builder único; placeholders existentes. |
| `FECHA_TERMINACION` | En este flujo la clave legacy del catálogo se **rellena desde la grilla** (no desde vínculo/perfil). Documentar en plantillas-word / placeholders. | Decisión 5 + 11; evita ambigüedad con `FECHA_TERMINACION_PERFIL` / `_VINCULO`. |
| Placeholders UI | No hace falta claves nuevas salvo categoría/nota **Cartas notificación** si ayuda al operador; `CEDULA`/`NOMBRE_COMPLETO`/`FIRMA`/`CARGO_FIRMA`/`FECHA_TERMINACION` ya existen | Checklist plantillas-word. |
| Lookup ficha | Por `EmployeeFichaProfile.document_number` (normalizar como Masivos/Vacaciones). Activo → autollenar nombre; inactivo / no encontrado → aviso + nombre editable; generación OK. | Decisión 8. |
| FIRMA UI | Por fila: `<x-searchable-select>` catálogo `payroll_catalog_items` `catalog_type=firmas` (activo). Payload: `signatory_id`. | Paridad Vacaciones / Desvinculaciones. |
| Persistencia | **Ninguna** tabla de lotes ni paths de salida; Excel no escribe BD | Decisiones 7–8. |
| Excel | `BaseExport` plantilla (columnas CEDULA, NOMBRE_COMPLETO, FECHA_TERMINACION, FIRMA). POST parse → JSON filas + lookup; Alpine fusiona en grilla. FIRMA Excel: match por `name` o `code` del catálogo (case-insensitive); sin match → vacío + aviso. | Estándar proyecto; sin `excelHtml5`. |
| Límite | Máx. **500** filas (`config/cartas_notificacion.php` → `max_rows`) | Decisión 7. |
| Controllers | `CartasNotificacionController` dedicado (index, lookup, generate, import-template, import-preview). Form Requests authorize vía AccessService. | Sin Repository; ownership claro. |
| UI | Vista `cartas-notificacion/index.blade.php` (+ Alpine). **Sin** subnav de pestañas. Chrome: `page-section` + `panel` + toolbar icon-only + grilla estilo Masivos/Vacaciones. Filtro texto en memoria; Limpiar = `clearGrid()`. | Decisiones 2, 6, 9 + `module-ui-quality`. |
| Vista referencia | Clonar look de `cliente_interno/cartas-vacaciones.blade.php` + `desvinculaciones/masivos.blade.php` (toolbar, modal multi-cédula, tabla, searchable-select firma). Añadir acciones Excel (descargar plantilla / importar) con icon-btn canónicos. | Regla UI calidad desde T1. |
| Spatie migrate | **No** migración de assign desde otros permisos | Decisión 3. |
| Audit | Wrapper `CartasNotificacionAuditLogService` → `SystemAuditService`; evento solo `cartas_notificacion_generate` | Decisión 10. |
| Repository | **No.** | Convención proyecto. |
| Migrate | Solo `php artisan migrate` si hace falta sync Spatie create permission en migración ligera **o** solo `app:sync-permissions`. Preferir sync + seed; migración de **datos assign** prohibida. | Protección de datos + decisión 3. |

### Diagrama del tablero (post-feature)

```text
[Sidebar GH: Cartas Notificación] --board--> grilla directa
        │
        └── (sin Dashboard / sin pestañas)
              edit: lookup + Excel memoria + generar/descargar
```

### Flujo de generación

```text
1. UI valida filas (cédula, nombre, FECHA_TERMINACION, signatory_id; ≤500; cédulas únicas).
2. Backend: authorize edit; resolver tipo cartas_notificacion.
3. Contar plantillas activas del tipo CON archivo:
     ≠ 1 → ValidationException mensaje claro (0 / >1).
4. Por cada fila: buildForCartasNotificacion → DocxRenderer → temp.
5. 1 fila → stream .docx; N → ZIP → stream .zip; cleanup (finally).
6. Audit: generate {row_count, output_type, template_id} sin volcar PII masiva.
```

### Flujo Excel (solo memoria)

```text
1. GET import-template → .xlsx (BaseExport).
2. POST import-preview (multipart) → parse + normalizar + lookup ficha + match FIRMA → JSON.
3. Alpine agrega/fusiona filas en grilla (skip duplicados de cédula + resumen).
4. Usuario ajusta FIRMA/nombre/fecha en UI; genera como flujo normal.
5. Nada se guarda en BD.
```

### Placeholders — mapeo (generación)

| Placeholder | Origen |
| --- | --- |
| `${CEDULA}` | Fila (input) |
| `${NOMBRE_COMPLETO}` | Fila (autollenado o manual) |
| `${FECHA_TERMINACION}` | Fila; formato largo ES (`j de Mes del Y`) |
| `${FIRMA}` / `${CARGO_FIRMA}` | Catálogo firmas → `name` / `code` |
| `${FECHA}` | Fecha emisión (hoy), formato largo ES |
| Resto de `letter_placeholders` | Ficha por cédula si existe; vacío si no |
| Aliases | `NOMBRE`←`NOMBRE_COMPLETO`; `DOCUMENTO`←`CEDULA`; `CIUDAD`←`CIUDAD_RESIDENCIA` (mismo criterio Vacaciones) |

## Alcance

### Incluye

- Tablero sidebar **Cartas Notificación** (board + grilla directa, sin pestañas).
- Permiso `cartas_notificacion.edit` + board `view.board.gestion_humana.cartas_notificacion` + labels Admin + sync (sin assign automático).
- `CartasNotificacionAccessService` + nav (`NavigationResolver`, `SidebarVisibilityService`, `User` default URL).
- Seed tipo Word `cartas_notificacion` + config `word_document_type_codes`.
- Lookup cédula → nombre / aviso (activo / inactivo / no encontrado).
- UI grilla + modal pegar cédulas + filtros memoria + Limpiar=vaciar.
- Plantilla Excel + import-preview a grilla (sin BD).
- Generación 1→docx / N→zip; bloqueo 0/>1 plantillas; tope 500.
- Form Requests, tests Feature (acceso, plantilla 0/1/N, generate, excel preview, validaciones).
- Docs: módulo nuevo + plantillas-word + ACCESS_CONTROL + ARCHITECTURE ownership (+ Documentador al cierre).

### Fuera de alcance

- Permiso `.view` separado; Dashboard; subnav de pestañas.
- Migración Spatie automática desde otros permisos.
- Persistencia / historial / re-descarga; envío por correo.
- Varias plantillas seleccionables por generación; editor Word en app.
- DataTables server-side (grilla es lote en memoria ≤500).
- Select2; `excelHtml5`; Repository; `migrate:fresh`.
- Cambiar motor PhpWord / DocxRenderer (solo reutilizar).

## Reglas de negocio

### Acceso

1. Sidebar: `view.board.gestion_humana.cartas_notificacion` **o** `cartas_notificacion.edit` **o** bypass `manage.users`.
2. Pantalla (GET index) y todas las acciones (lookup, generate, import-template, import-preview): solo `cartas_notificacion.edit` **o** bypass.
3. Usuario con solo board (sin edit): ve el tablero en nav → **403** al entrar (mensaje: necesita permiso de generación).
4. **No** existe `cartas_notificacion.view`.
5. Asignación Spatie: **manual** en Admin tras `app:sync-permissions` (super-admin recibe todos vía sync).

### Fila y ficha

6. Obligatorios por fila al generar: cédula, nombre completo, fecha terminación, firma (`signatory_id`).
7. Lookup: activo → precargar nombre (editable). Inactivo / sin ficha → aviso visible; nombre manual; generación OK si validaciones pasan.
8. Duplicados de cédula: al pegar / import Excel → omitir + resumen (como Masivos). Al generar → 422 si hay cédulas repetidas en el payload.
9. Filtros: búsqueda/filtro client-side sobre filas en memoria; no consulta servidor.
10. Botón Limpiar: vacía **toda** la grilla (y resetea filtro).

### Plantilla, Excel y salida

11. Tipo fijo `cartas_notificacion` (config + seed). Operador sube plantilla en Plantillas Word.
12. Generación exige **exactamente una** plantilla del tipo con archivo y tipo activo. Mensajes:
    - 0: «No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.»
    - >1: «Hay más de una plantilla activa de Cartas Notificación. Deje solo una activa.»
13. Excel plantilla columnas: `CEDULA`, `NOMBRE_COMPLETO` (opcional), `FECHA_TERMINACION`, `FIRMA` (opcional: name o code del catálogo).
14. Import Excel **no** persiste; solo hidrata grilla (máx. 500 filas totales tras merge).
15. 1 fila válida → `.docx`; 2+ → `.zip` (nombre archivo con cédula/slug seguro).
16. Sin path en BD ni storage permanente de salida.
17. Límite máximo 500 filas por generate / por import preview.

### Auditoría

18. Evento `cartas_notificacion_generate` (módulo audit `cartas_notificacion`, area `gestion_humana`): metadata `row_count`, `output_type`, `template_id`; sin lista completa de cédulas. **No** auditar lookup ni import-preview.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.cartas_notificacion` | Asignación manual Admin (+ super-admin vía sync) | Ver tablero Cartas Notificación en sidebar |
| `cartas_notificacion.edit` | Asignación manual Admin (+ super-admin vía sync) | Cartas Notificación: Generar (acceso pantalla + lote) |

**Labels Admin:** `Cartas Notificación` (board en Ver tableros), `Cartas Notificación: Generar`.

**No** entra en `area_indicador_permissions` (mismo patrón GH: `system_permissions` + subgroup Admin).

### Implicaciones AccessService

| Otorgado | Efecto |
| --- | --- |
| `cartas_notificacion.edit` | Ve sidebar (vía canViewBoard) + entra + lookup/Excel/generar |
| Solo board | Ve sidebar; **403** en index y POST |
| `manage.users` | Bypass |

### Cambios en `config/access.php`

- `boards`: + `'cartas_notificacion' => 'Cartas Notificación'`.
- `board_canonical_areas`: + hogar `gestion_humana`, `base_area_tab => false`.
- `system_permissions`: + `cartas_notificacion.edit`.
- Admin `other_areas.gestion_humana.subgroups.boards`: + `view.board.gestion_humana.cartas_notificacion`.
- Admin subgroup nuevo `cartas_notificacion` con `cartas_notificacion.edit`.

### Seeders / sync / Spatie

1. `app:sync-permissions` crea permisos; `super-admin` recibe todos.
2. **No** migración de assign a usuarios/roles existentes.
3. Tras deploy: sync + Admin asigna board + edit a operadores; re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (ya `require` en `web.php`; **no** tocar `web.php`).

Prefijo sugerido: `/gestion-humana/cartas-notificacion` · nombre `gestion-humana.cartas-notificacion.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/` | `index` | Grilla. **edit** |
| POST | `/lookup` | `lookup` | JSON cédulas → nombre/estado. **edit** |
| GET | `/import-template` | `import-template` | Descarga `.xlsx` plantilla. **edit** |
| POST | `/import-preview` | `import-preview` | Parse Excel → JSON filas (sin BD). **edit** |
| POST | `/generar` | `generate` | Body `rows[]`; stream docx/zip. **edit** |

Middleware: `password.changed` (grupo GH). Authorize vía AccessService en controller/Form Request.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `word_document_types` | seed / upsert | `code=cartas_notificacion`, name `Cartas Notificación`, `is_active=true`, sort coherente (p. ej. 4) |
| Spatie permissions | sync | Crear keys; **sin** assign masivo |
| Tablas de lotes / cartas | **ninguna** | Sin persistencia |

Actualizar:

- `database/seeders/WordDocumentTypeSeeder.php` — `firstOrCreate` tipo `cartas_notificacion`.
- `config/employee_ficha.php` — `word_document_type_codes.cartas_notificacion`.
- `config/cartas_notificacion.php` (nuevo) — `max_rows => 500`.

**Prohibido** `migrate:fresh`.

## Capas a implementar

- [ ] Config — `access.php`; `employee_ficha.php` (code tipo); `cartas_notificacion.php` (max_rows)
- [ ] Seeder — `WordDocumentTypeSeeder` + sync permisos
- [ ] Access — `CartasNotificacionAccessService`
- [ ] Nav — `NavigationResolver`, `SidebarVisibilityService`, `User::defaultCartasNotificacionBoardUrl`
- [ ] Services — `CartasNotificacionGeneratorService`; `LetterVariableBuilder::buildForCartasNotificacion`; import preview helper
- [ ] Export — `CartasNotificacionImportTemplateExport` extends `BaseExport`
- [ ] Controlador — `CartasNotificacionController`
- [ ] Form Request(s) — lookup, generate, import-preview
- [ ] Vista(s) Blade — index grilla (chrome pulido desde T1) + modal cédulas + modal/flujo Excel
- [ ] JavaScript / Alpine — grilla, filtros memoria, bulk paste, excel merge, generate download (blob)
- [ ] Audit — wrapper + evento generate
- [ ] Tests Feature
- [ ] Docs módulo (Documentador) + ownership ARCHITECTURE

## Componentes reutilizables

- `<x-searchable-select>` — firma por fila.
- Chrome `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn` (filas); **sin** familia CSS nueva `cartas-notificacion__icon-btn`.
- UI referencia: `cartas-vacaciones.blade.php` + `desvinculaciones/masivos.blade.php`.
- `TerminationLetterDocxRenderer`, `TerminationLetterTemplateManager`, `WordTempDirectory`, `LetterVariableBuilder`.
- Catálogo firmas (`PayrollCatalogItem` `catalog_type=firmas`).
- `BaseExport` — solo plantilla Excel de carga (no export de listado persistente).
- `SystemAuditService` vía wrapper del módulo.
- Clases globales: `page-section`, `app-container`, `panel`, `data-table-wrap`, `req-manage-*`.

### Exigencia UI (obligatoria desde T1)

La **primera** entrega visual (shell T1) debe verse como un listado/tablero GH pulido: panel, toolbar icon-only, thead corporativo, empty-state con tipografía/ortografía correctas, tokens branding. **Prohibido** entregar “API + Blade mínimo feo” y dejar el look para T2. T2 completa grilla funcional + Excel + generate sobre ese chrome.

## Documentacion a actualizar

- [ ] `docs/modules/cartas-notificacion.md` (nuevo)
- [ ] `docs/user/cartas-notificacion.md` (nuevo)
- [ ] `docs/modules/plantillas-word.md` (tipo `cartas_notificacion` + flujo + regla 1 plantilla)
- [ ] `docs/user/plantillas-word.md` (si menciona tipos seed)
- [ ] `docs/ACCESS_CONTROL.md`
- [ ] `docs/ARCHITECTURE.md` (ownership fila módulo)
- [ ] `docs/INDEX.md` (si aplica)
- [ ] Documentador al cierre del flujo

## Archivos compartidos (`shared-files`)

**`shared-files: si`**

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Board + permiso edit + Admin UI |
| `config/employee_ficha.php` | `word_document_type_codes.cartas_notificacion` |
| `routes/areas/gestion_humana.php` | Rutas del tablero |
| `app/Services/Access/CartasNotificacionAccessService.php` | Nuevo (ownership módulo; listado por tocar access stack) |
| `app/Services/Navigation/NavigationResolver.php` | Registrar board en strip GH |
| `app/Services/Navigation/SidebarVisibilityService.php` | Visibilidad hogar GH |
| `app/Models/User.php` | `defaultCartasNotificacionBoardUrl` |
| `database/seeders/WordDocumentTypeSeeder.php` | Seed tipo Word |
| `app/Services/GestionHumana/Letter/LetterVariableBuilder.php` | Método `buildForCartasNotificacion` (shared letter stack) |

**No** tocar `routes/web.php` ni layouts globales salvo que AgentSj autorice hotfix.

**Flag en `docs/TASKS.md`:** `shared-files` = sí. **T1** única autorizada a tocar `access.php` / AccessService / nav / User / seed tipo / config codes. Generador+UI+Excel+LetterVariableBuilder en **T2** (LetterVariableBuilder es shared: serializar; no paralelo con otros FEAT que lo toquen).

## Criterios de aceptacion

1. Con board + `cartas_notificacion.edit`: ve tablero en sidebar GH, entra a grilla directa (sin Dashboard ni pestañas) y puede generar.
2. Con solo board (sin edit): ve enlace → **403** en index; POST lookup/generate/import → 403.
3. **No** existe permiso Spatie `cartas_notificacion.view`.
4. Tras sync, permisos aparecen en Admin; **no** se asignan solos a roles/usuarios existentes (salvo super-admin vía sync total).
5. Grilla + modal pegar cédulas + import Excel: hidratan memoria; duplicados omitidos con resumen; lookup autollena nombre si activo.
6. Fila sin ficha o inactiva: aviso visible; nombre editable; generación permitida si campos OK.
7. Limpiar vacía la grilla completa; filtros solo afectan vista en memoria.
8. Validación: faltante obligatorio → 422; >500 → 422; cédulas duplicadas en generate → 422.
9. 0 plantillas activas del tipo → bloqueo mensaje claro; >1 → bloqueo; exactamente 1 → OK.
10. 1 fila → Content-Disposition `.docx`; 2+ → `.zip` con N docx; `${FECHA_TERMINACION}` en español largo; FIRMA/CARGO_FIRMA coherentes con `signatory_id`.
11. Resto de placeholders de ficha rellenados cuando hay perfil; vacíos si no.
12. No queda archivo de salida permanente en storage tras la respuesta; Excel no escribe tablas de negocio.
13. Audit registra **solo** generate con conteo y tipo salida.
14. Selectores searchable-select; icon-btn canónicos; sin Select2; sin `excelHtml5`; sin `migrate:fresh`.
15. UI T1/T2 cumple checklist `module-ui-quality` (chrome GH, no prototipo genérico).
16. Tests Feature: acceso board-only vs edit; plantilla 0/1/2; validaciones; 1→docx / N→zip; import-preview sin BD; builder fecha larga.

## Validacion local

1. `php artisan app:sync-permissions` (+ migrate solo si el Feature añade migración no destructiva de esquema; no assign Spatie).
2. Admin: asignar board + `cartas_notificacion.edit` a un usuario de prueba; re-login.
3. Plantillas Word: confirmar tipo seed; subir **una** plantilla `cartas_notificacion`.
4. Probar grilla: pegar cédulas, Excel, avisos, filtros, Limpiar=vaciar, generar 1 y N.
5. Probar bloqueo 0 y >1 plantillas activas.
6. Usuario solo board: 403 al entrar.
7. `php artisan test --compact` (Feature Cartas Notificación).
8. Smoke UI Chrome DevTools MCP (tablero + generate + import).
9. `vendor/bin/pint --dirty --format agent` tras PHP.
10. Si se toca CSS: `npm run build`.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Operador deja 0 o varias plantillas activas | Bloqueo explícito + docs Plantillas Word + empty-state en tablero |
| Timeout / memoria ZIP grande | Límite 500; temp cleanup; tests N pequeño |
| Confusión `FECHA_TERMINACION` (legacy vínculo vs grilla) | Builder documentado: en este flujo manda la fila; docs placeholders |
| Solo board sin edit (UX frustrante) | Mensaje 403 claro; docs usuario: asignar ambos en Admin |
| Shared-files race (nav + access + LetterVariableBuilder) | T1 serializa access/nav/seed; T2 LetterVariableBuilder; AgentSj no paralelo con FEAT que toque letter stack |
| Match FIRMA en Excel ambiguo | Match exacto name o code; sin match → vacío + aviso fila |
| Lookup ficha ambiguo (varios perfiles) | Preferir activo; si varios, más reciente — documentar + test |
| Import Excel malformado | 422 con fila/columna; no partial BD (no hay BD) |

## Slice de tareas (Task Cards) — sugerido AgentSj

| ID | Titulo | Alcance vertical | shared-files |
| --- | --- | --- | --- |
| **T1** | Access + seed tipo Word + nav + shell UI pulido | `access.php`, AccessService, NavigationResolver, SidebarVisibilityService, User URL, WordDocumentTypeSeeder, config codes + `cartas_notificacion.php`, rutas GET index, Blade shell chrome calidad (panel/toolbar/empty-state), tests acceso/nav/seed | **si** |
| **T2** | UI grilla completa + Excel memoria + generate docx/zip | GeneratorService, LetterVariableBuilder método, Form Requests, Alpine grilla/filtros/pegar/Excel, BaseExport plantilla, import-preview, audit generate, tests generate/plantilla 0/1/N/excel | LetterVariableBuilder (shared; serializar) |

**Orden:** T1 → T2.

**Nota AgentSj:** T1 ya debe entregar UI de calidad (empty-state usable, no placeholder feo). T2 no “embellece después”; completa funcionalidad sobre el chrome de T1.

## Aprobacion

- [x] Analista — vacíos cerrados (decisiones usuario 1–11 + run log FEAT-044)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmación / `OK implementa`
- [ ] Feature T1+T2 + Revisor + Documentador + cierre AgentSj
