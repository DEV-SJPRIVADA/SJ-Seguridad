# Feature Brief — FEAT-043

> Brief final del Arquitecto (2026-10-07). Consolida decisiones cerradas del usuario + chat AgentSj (run log FEAT-043). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-043 |
| Modulo / area | Gestion humana — tablero **Cliente interno** (`cliente_interno`) |
| Titulo | Pestaña **Cartas Vacaciones**: lote por cédulas → Word (`.docx` / `.zip`) |
| Solicitante | Usuario / AgentSj (chat 2026-10-07 cartas vacaciones CI) |
| Fecha | 2026-10-07 |

## Objetivo

Permitir a Gestión Humana generar **cartas de vacaciones** en lote desde Cliente interno: el operador arma una **grilla por cédula**, completa fechas/periodos/días/firma **por fila**, y descarga **un `.docx` (1 registro) o un `.zip` (N)**. La plantilla vive en **Plantillas Word** bajo el tipo `cartas_vacaciones` (exactamente una activa). No se persisten lotes ni cartas; solo generar y descargar en la sesión.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Campos por fila | `FECHA_INICIO`, `FECHA_FIN`, `FECHA_REINTEGRO`, `PERIODOS`, `DIAS_DISFRUTADOS` y `FIRMA` son **por fila** (no globales del lote). |
| 2 | Nombre / ficha | Sin ficha: usuario puede **escribir nombre a mano**. Con ficha: **autollenar** nombre. Inactivo o no encontrado: **permite generar** + **aviso en fila**. |
| 3 | UI | Grilla editable + modal **pegar varias cédulas** (estilo Desvinculaciones → Masivos). |
| 4 | Permisos | `cliente_interno.cartas_vacaciones.view` = ver pestaña **sin** generar; `.edit` = generar/descargar (`edit` ⇒ `view` en AccessService). **Migrar AMBOS** a quien tenga `cliente_interno.solicitudes.edit`. Shell: `view.board.gestion_humana.cliente_interno`. **Sin** permiso KPI nuevo. |
| 5 | Plantilla Word | Tipo `cartas_vacaciones`, **una** plantilla activa con archivo. **0 o >1** activas con archivo → **bloquear** generación con mensaje claro. |
| 6 | Validaciones | Todos obligatorios: cédula, nombre, fechas, periodos, días, firma. `fin ≥ inicio`; `reintegro ≥ fin`. |
| 7 | Solo view cartas | Usuario solo con `cartas_vacaciones.view` (+ board): **sin Dashboard**; solo pestaña Cartas Vacaciones (+ shell). |
| 8 | Salida | 1 registro → `.docx`; N → `.zip`. **Sin persistencia** de lotes ni archivos generados. |
| 9 | Placeholders | `${CEDULA}` `${NOMBRE_COMPLETO}` `${FECHA_INICIO}` `${FECHA_FIN}` `${FECHA_REINTEGRO}` `${PERIODOS}` `${DIAS_DISFRUTADOS}` `${FIRMA}` `${CARGO_FIRMA}`. |
| 10 | Estándares | Audit central; multi-driver si hay migración; **prohibido** `migrate:fresh` / wipe / TRUNCATE operativo. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Hogar | Extiende board `cliente_interno` (no tablero nuevo) | Decisión usuario; ownership GH ya en ARCHITECTURE.md. |
| Pestaña | Key `cartas_vacaciones` → label **Cartas Vacaciones** en `cliente_interno_tabs` | Modelo view/edit por pestaña (Comercial / FEAT-042). |
| Access | Ampliar `ClienteInternoAccessService`: `canViewCartasVacaciones`, `canEditCartasVacaciones`; `edit` ⇒ `view`; **`canViewDashboard` no incluye** cartas (solo solicitudes view∨edit ∨ parameters.edit ∨ bypass) | Decisión 7. |
| Redirect shell | `index` redirige a la **primera pestaña visible**; si solo cartas → Cartas Vacaciones | Evita 403 en Dashboard. |
| Tipo Word | Seed idempotente `word_document_types.code = cartas_vacaciones` + `config/employee_ficha.php` → `word_document_type_codes.cartas_vacaciones` | Misma convención que `desvinculacion` / `contratacion`. |
| Regla plantilla | Contar plantillas del tipo con archivo presente y tipo activo: debe ser **exactamente 1**; si no, 422/bloqueo UI | Decisión 5. |
| Generador | Servicio nuevo `ClienteInternoCartasVacacionesGeneratorService` (area Cliente interno). Reutiliza `TerminationLetterDocxRenderer` + `TerminationLetterTemplateManager` + `ZipArchive` / `WordTempDirectory`. **No** persiste en disco de app (solo temp + stream descarga). | Evita acoplar a packs de Ficha; sin `termination_letter_path`. |
| Variables | Extender `LetterVariableBuilder` con método dedicado (ej. `buildForCartasVacaciones(row)`) que **no** exige periodo/entrada ficha; fechas en formato largo español existente; `FIRMA`/`CARGO_FIRMA` vía catálogo `firmas` (mismo `resolveSignatory`) | Builder único documentado en plantillas-word.md. |
| Placeholders UI | Registrar claves nuevas en `letter_placeholders` (categoría **Cartas vacaciones**); `CEDULA`/`NOMBRE_COMPLETO`/`FIRMA`/`CARGO_FIRMA` ya existen | Checklist plantillas-word. |
| Lookup ficha | Por `EmployeeFichaProfile.document_number` (normalizar cédula igual que Masivos). Activo → autollenar nombre; inactivo / no encontrado → aviso fila + nombre editable | Decisión 2. |
| FIRMA UI | Por fila: `<x-searchable-select>` opciones catálogo `payroll_catalog_items` `catalog_type = firmas` (activo). Payload: `signatory_id`. `CARGO_FIRMA` = `code` del ítem | Paridad Desvinculaciones masivos / cartas Ficha. |
| Persistencia | **Ninguna** tabla de lotes ni paths de salida | Decisión 8. |
| Límite lote | Máx. **500** filas por generación (mismo tope Masivos); documentar en UI | Protege memoria/timeout. |
| Controllers | Métodos en `ClienteInternoController` **o** `ClienteInternoCartasVacacionesController` fino si el slice lo pide; Form Request generate + lookup | Sin Repository. |
| UI | Vista `cartas-vacaciones.blade.php` + Alpine (patrón `desvinculaciones/masivos`); chrome `.module-tab`; icon buttons estándar; searchable-select firma | AGENTS.md. |
| Spatie migrate | Migración de **datos** (no fresh): usuarios y roles con `cliente_interno.solicitudes.edit` reciben `cartas_vacaciones.view` **y** `.edit` | Decisión 4. |
| Repository | **No.** | Convención proyecto. |
| Migrate | Solo `php artisan migrate` incremental (+ sync permisos). | Protección de datos. |

### Diagrama de pestañas (post-feature)

```text
[Sidebar GH: Cliente interno] --board--> shell
        │
        ├── Dashboard          (solicitudes.view∨edit OR parameters.edit)  ← NO cartas solo
        ├── Solicitudes        (solicitudes.view∨edit)
        ├── Cartas Vacaciones  (cartas_vacaciones.view∨edit)
        │                         └── edit: lookup + generar/descargar
        └── Catálogos          (parameters.edit)
```

### Flujo de generación

```text
1. UI valida filas (obligatorios + fin≥inicio + reintegro≥fin).
2. Backend: authorize edit; resolver tipo cartas_vacaciones.
3. Contar plantillas activas del tipo CON archivo:
     ≠ 1 → ValidationException mensaje claro (0 / >1).
4. Por cada fila: mapear variables (builder vacaciones) → DocxRenderer → archivo temp.
5. 1 fila → stream .docx; N → ZIP en temp → stream .zip; cleanup temp (finally).
6. Audit: generate {row_count, output_type, template_id} sin volcar PII masiva.
```

### Placeholders — mapeo

| Placeholder | Origen |
| --- | --- |
| `${CEDULA}` | Fila (input) |
| `${NOMBRE_COMPLETO}` | Fila (autollenado o manual) |
| `${FECHA_INICIO}` / `${FECHA_FIN}` / `${FECHA_REINTEGRO}` | Fila; formato largo ES (`j de Mes del Y`) |
| `${PERIODOS}` | Fila (texto libre obligatorio) |
| `${DIAS_DISFRUTADOS}` | Fila (numérico/texto obligatorio; string en Word) |
| `${FIRMA}` | Catálogo firmas → `name` |
| `${CARGO_FIRMA}` | Catálogo firmas → `code` |

## Alcance

### Incluye

- Pestaña **Cartas Vacaciones** en Cliente interno (subnav + vista grilla + modal pegar cédulas).
- Permisos `cliente_interno.cartas_vacaciones.view` / `.edit` + labels Admin + sync.
- Migración Spatie: asignar **ambos** a quien tenga `solicitudes.edit` (users + roles).
- Actualizar `ClienteInternoAccessService` + `visibleTabsFor` + redirect shell + trait tabs.
- Seed tipo Word `cartas_vacaciones` + config `word_document_type_codes` + placeholders nuevos.
- Lookup cédula → nombre / estado aviso (activo / inactivo / no encontrado).
- Generación 1→docx / N→zip sin persistencia; bloqueo si 0 o >1 plantillas activas.
- Form Request, tests Feature (acceso, validaciones, plantilla 0/1/N, docx/zip, migración permisos).
- Docs: `cliente-interno`, `plantillas-word`, `ACCESS_CONTROL` (+ Documentador al cierre).

### Fuera de alcance

- Persistencia / historial de lotes o re-descarga posterior.
- Envío por correo de cartas.
- Editor Word en app; varias plantillas seleccionables por generación.
- Bridge con Reportes-Novedades → Vacaciones (datos de goce).
- Permiso KPI / dashboard propio; Select2; `excelHtml5`; Repository; `migrate:fresh`.
- Cambiar motor PhpWord / DocxRenderer (solo reutilizar).

## Reglas de negocio

### Acceso y pestañas

1. Shell / sidebar board: `view.board.gestion_humana.cliente_interno` (o bypass `manage.users`).
2. Ver pestaña Cartas Vacaciones: `cartas_vacaciones.view` **o** `.edit` (o bypass).
3. Generar / lookup que muta estado de generación: solo `.edit` (o bypass). Usuario solo `.view`: ve grilla en solo lectura (o vacía informativa) **sin** botón generar ni aplicar lote; **403** en POST generate/lookup si aplica mutación — preferencia Arquitecto: lookup de autollenado también exige `.edit` (consulta operativa del lote); view solo muestra empty-state “sin permiso para generar”.
4. `cartas_vacaciones.edit` ⇒ `cartas_vacaciones.view` en AccessService.
5. Dashboard **no** se abre solo con permisos de cartas (decisión 7).
6. Bypass `manage.users` en board / view / edit cartas.

### Fila y ficha

7. Obligatorios por fila: cédula, nombre completo, fecha inicio, fecha fin, fecha reintegro, periodos, días disfrutados, firma (`signatory_id`).
8. `fecha_fin >= fecha_inicio`; `fecha_reintegro >= fecha_fin`.
9. Lookup: si perfil activo → precargar nombre (editable). Si inactivo o no hay ficha → aviso en fila; nombre manual permitido; generación OK si validaciones pasan.
10. Duplicados de cédula en la misma grilla: **bloquear o advertir** — decisión Arquitecto: **evitar duplicados** al pegar (skip + conteo como Masivos); en grilla manual, validar unicidad de cédula en el lote al generar (422 si repetida).

### Plantilla y salida

11. Tipo fijo `cartas_vacaciones` (config + seed). Usuario sube la plantilla en tablero Plantillas Word.
12. Generación exige **exactamente una** plantilla del tipo con archivo en disco y tipo activo. Mensajes:
    - 0: «No hay plantilla activa de Cartas Vacaciones. Cargue una en Plantillas Word.»
    - >1: «Hay más de una plantilla activa de Cartas Vacaciones. Deje solo una activa.»
13. 1 fila válida → descarga `.docx`; 2+ → `.zip` con un docx por fila (nombre archivo incluye cédula/slug seguro).
14. Sin guardar path en BD ni storage permanente de salida.
15. Límite máximo 500 filas por request.

### Auditoría

16. Evento `cartas_vacaciones_generate` (módulo audit `cliente_interno`, area `gestion_humana`): metadata `row_count`, `output_type`, `template_id`; sin lista completa de cédulas.

## Permisos (`config/access.php`)

### Keys concretas

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| `view.board.gestion_humana.cliente_interno` | Sin cambio | Shell Cliente interno (ya existe) |
| `cliente_interno.cartas_vacaciones.view` | Migración desde `solicitudes.edit` + asignación manual | Cliente interno: Ver Cartas Vacaciones |
| `cliente_interno.cartas_vacaciones.edit` | Migración desde `solicitudes.edit` + asignación manual | Cliente interno: Generar Cartas Vacaciones |

**Labels Admin:** `Cliente interno: Ver Cartas Vacaciones`, `Cliente interno: Generar Cartas Vacaciones`.

**No** entra en `area_indicador_permissions` (mismo patrón FEAT-042: `system_permissions` + subgroup Admin Cliente interno).

### Implicaciones AccessService

| Otorgado | Efecto |
| --- | --- |
| `cartas_vacaciones.edit` | Puede view cartas; generar/lookup |
| `cartas_vacaciones.view` (solo) | Ve pestaña; **no** Dashboard; **no** generar |
| `solicitudes.*` / `parameters.edit` | Sin cambio funcional previo; Dashboard sigue igual |
| `manage.users` | Bypass |

### Cambios en `config/access.php`

- `system_permissions`: +2 keys.
- `cliente_interno_tabs`: + `'cartas_vacaciones' => 'Cartas Vacaciones'`.
- Admin subgroup `cliente_interno.permissions`: + view/edit cartas.
- Board existente: sin cambio de key.

### Seeders / sync / migración Spatie

1. `app:sync-permissions` crea permisos; `super-admin` recibe todos.
2. Migración de datos (idempotente): todo `model_has_permissions` / `role_has_permissions` con `cliente_interno.solicitudes.edit` → insertar también `cartas_vacaciones.view` y `.edit` si faltan.
3. `administrador` / `usuario`: **no** paquete automático nuevo salvo lo que ya tengan vía sync de super-admin; el resto queda en migración + Admin UI.
4. Tras deploy: `migrate` + `app:sync-permissions` + re-login.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (ya `require` en `web.php`; **no** tocar `web.php`).

Prefijo existente: `/gestion-humana/cliente-interno` · nombre `gestion-humana.cliente-interno.`

| Metodo | URI | Nombre | Permiso / notas |
| --- | --- | --- | --- |
| GET | `/cartas-vacaciones` | `cartas-vacaciones` | Shell grilla. `cartas_vacaciones.view`∨edit |
| POST | `/cartas-vacaciones/lookup` | `cartas-vacaciones.lookup` | JSON: 1 o N cédulas → nombre/estado. **edit** |
| POST | `/cartas-vacaciones/generar` | `cartas-vacaciones.generate` | Body `rows[]`; stream docx/zip. **edit** |

Middleware: `password.changed` (grupo GH). Authorize vía AccessService en controller/Form Request.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `word_document_types` | seed / upsert | Fila `code=cartas_vacaciones`, name `Cartas Vacaciones`, `is_active=true`, sort coherente |
| Spatie permissions | migración datos | Crear permisos si sync no corre en migrate; asignar view+edit a poseedores de `solicitudes.edit` |
| Tablas de lotes / cartas CI | **ninguna** | Sin persistencia |

Actualizar:

- `database/seeders/WordDocumentTypeSeeder.php` — `firstOrCreate` tipo `cartas_vacaciones`.
- `config/employee_ficha.php` — `word_document_type_codes.cartas_vacaciones` + entradas `letter_placeholders`.

**Prohibido** `migrate:fresh`.

## Capas a implementar

- [ ] Migracion(es) — Spatie assign view+edit desde `solicitudes.edit` (idempotente); opcional insert tipo Word si se prefiere migración vs solo seeder
- [ ] Seeder — `WordDocumentTypeSeeder` + sync permisos
- [ ] Config — `access.php`; `employee_ficha.php` (codes + placeholders); opcional `cliente_interno.php` (`cartas_vacaciones.max_rows`)
- [ ] Access — `ClienteInternoAccessService` + trait `HasClienteInternoTabs` / redirect index
- [ ] Services — `ClienteInternoCartasVacacionesGeneratorService`; extensión `LetterVariableBuilder`; lookup ficha
- [ ] Controlador(es) — rutas cartas en Cliente interno
- [ ] Form Request(s) — lookup + generate (validación filas + fechas + firma exists firmas)
- [ ] Vista(s) Blade — `cartas-vacaciones.blade.php` (+ modal pegar cédulas)
- [ ] JavaScript / Alpine — grilla, bulk paste, generate download (blob)
- [ ] Audit — evento generate en wrapper `ClienteInternoAuditLogService`
- [ ] Tests Feature
- [ ] Docs módulo (Documentador)

## Componentes reutilizables

- `<x-searchable-select>` — firma por fila.
- Chrome `.module-tab` + `.req-manage-filters__icon-btn` / icon-only de fila.
- UI referencia: `resources/views/areas/gestion_humana/desvinculaciones/masivos.blade.php` (grilla + modal multi-cédula).
- `TerminationLetterDocxRenderer`, `TerminationLetterTemplateManager`, `WordTempDirectory`, `LetterVariableBuilder`.
- Catálogo firmas (`PayrollCatalogItem` `catalog_type=firmas`).
- `SystemAuditService` vía wrapper Cliente interno.
- **No** `BaseExport` / Excel en esta feature.

## Documentacion a actualizar

- [ ] `docs/modules/cliente-interno.md`
- [ ] `docs/user/cliente-interno.md`
- [ ] `docs/modules/plantillas-word.md` (tipo `cartas_vacaciones` + placeholders + flujo CI)
- [ ] `docs/user/plantillas-word.md` (si menciona tipos seed)
- [ ] `docs/ACCESS_CONTROL.md`
- [ ] `docs/INDEX.md` (si aplica)
- [ ] Documentador al cierre del flujo

## Archivos compartidos (`shared-files`)

**`shared-files: si`**

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Permisos + tab `cartas_vacaciones` + Admin UI |
| `config/employee_ficha.php` | `word_document_type_codes` + `letter_placeholders` |
| `routes/areas/gestion_humana.php` | Rutas Cartas Vacaciones |
| `app/Services/Access/ClienteInternoAccessService.php` | view/edit cartas; dashboard sin cartas-only; `visibleTabsFor` |
| `database/seeders/WordDocumentTypeSeeder.php` | Seed tipo `cartas_vacaciones` |
| Migración Spatie (permisos) | Assign view+edit desde `solicitudes.edit` |
| `app/Services/GestionHumana/Letter/LetterVariableBuilder.php` | Método variables vacaciones (shared letter stack) |
| Trait / User / nav tabs Cliente interno | Si el redirect/tabs viven fuera del controller de módulo |

**Flag en `docs/TASKS.md`:** `shared-files` = sí. **T1** única autorizada a tocar `access.php` / AccessService / seed tipo+Spatie / config employee_ficha codes. Generador+UI pueden ser T2 sin reabrir access salvo hotfix AgentSj.

## Criterios de aceptacion

1. Con board + solo `cartas_vacaciones.view`: ve pestaña Cartas Vacaciones; **no** ve Dashboard; **403** en generate/lookup.
2. Con `cartas_vacaciones.edit` (sin view Spatie): ve pestaña y puede generar (implicación view).
3. Usuario migrado desde `solicitudes.edit` tiene **ambos** permisos cartas tras migrate+sync.
4. `canViewDashboard` false si solo tiene permisos de cartas (+ board).
5. Grilla + modal pegar cédulas: agrega filas; duplicados omitidos con resumen; lookup autollena nombre si activo.
6. Fila sin ficha o inactiva: aviso visible; nombre editable; generación permitida si campos OK.
7. Validación: faltante obligatorio → 422; `fin < inicio` o `reintegro < fin` → 422.
8. 0 plantillas activas del tipo → bloqueo con mensaje claro; >1 → bloqueo con mensaje claro; exactamente 1 → OK.
9. 1 fila → Content-Disposition `.docx`; 2+ → `.zip` con N docx; placeholders sustituidos (no quedan `${…}` literales de las 9 claves si datos presentes).
10. No queda archivo de salida permanente en storage de la app tras la respuesta.
11. `${FIRMA}` / `${CARGO_FIRMA}` coherentes con el `signatory_id` de la fila.
12. Audit registra generate con conteo y tipo salida.
13. Selectores searchable-select; sin Select2; sin `migrate:fresh`.
14. Tests Feature cubren permisos, migración Spatie, plantilla 0/1/2, validaciones fechas, 1→docx / N→zip.

## Validacion local

1. `php artisan migrate` (incremental) + `php artisan app:sync-permissions`.
2. Verificar asignación: usuario con `solicitudes.edit` recibe view+edit cartas.
3. En Plantillas Word: crear tipo ya seedado / subir **una** plantilla `cartas_vacaciones`.
4. Probar grilla: pegar cédulas, avisos, generar 1 y N.
5. Probar bloqueo 0 y >1 plantillas activas.
6. Usuario solo view: sin Dashboard, sin generar.
7. `php artisan test --compact` (Feature Cliente interno / Cartas Vacaciones).
8. Smoke UI Chrome DevTools MCP (pestaña + generate).
9. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Operador deja 0 o varias plantillas activas | Bloqueo explícito + docs usuario Plantillas Word + empty-state en pestaña |
| Timeout / memoria en ZIP grande | Límite 500; temp cleanup; tests con N pequeño; documentar |
| Placeholders nuevos no en plantilla subida | UI lista variables; docs; generación no falla si faltan en docx (PhpWord deja sin sustituir solo lo ausente) |
| Shared-files race | Solo T1 toca access/seed/Spatie; AgentSj serializa |
| Lookup ficha ambiguo (varios perfiles) | Resolver por `document_number` único de negocio; si >1, tomar activo preferente o el más reciente — documentar en servicio + test |
| CARGO_FIRMA = code catálogo (no “cargo laboral”) | Documentar en placeholders; mismo comportamiento cartas Ficha |
| Migración Spatie incompleta (solo users, no roles) | Migración debe cubrir `role_has_permissions` y `model_has_permissions` |

## Slice de tareas (Task Cards) — sugerido AgentSj

| ID | Titulo | Alcance vertical | shared-files |
| --- | --- | --- | --- |
| **T1** | Permisos + Access + seed tipo Word + placeholders + rutas shell | `access.php`, AccessService, tabs/redirect, WordDocumentTypeSeeder, config employee_ficha, migración Spatie, ruta GET pestaña + empty, tests acceso/migración | **si** |
| **T2** | UI grilla + lookup + generate docx/zip | GeneratorService, LetterVariableBuilder método vacaciones, Form Requests, Blade/Alpine Masivos-like, audit, tests generate/validación/plantilla 0/1/N | no |

**Orden:** T1 → T2.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas usuario 1–7 + decisiones chat)
- [x] Arquitecto — brief final
- [x] Usuario — `OK implementa` (2026-10-07)
- [x] Feature T1+T2 + Revisor (Aprobado con observaciones) + Documentador + cierre AgentSj
