# Modulo Cartas Notificación

> Documentacion tecnica para IAs y desarrolladores. Ubicacion: `docs/modules/cartas-notificacion.md`.
> Area: Gestion humana. Feature: FEAT-044. Review: `docs/reviews/FEAT-044.md` (Aprobado con observaciones).

## Objetivo

Tablero propio de **Gestión Humana** para generar **cartas de notificación** en lote: grilla en memoria (cédula, nombre, **duración contrato 6|12**, fecha de terminación, firma), opcional Excel / pegado de cédulas, descarga **1→`.docx` / N→`.zip`**. Plantilla Word tipo `cartas_notificacion` (exactamente una activa con archivo). **Sin** persistencia de lotes, filas ni archivos de salida.

## Alcance actual

- Tablero sidebar **Cartas Notificación** (`cartas_notificacion`): grilla directa al entrar (**sin** Dashboard / **sin** pestañas).
- Permisos: board `view.board.gestion_humana.cartas_notificacion` + **solo** `cartas_notificacion.edit` (**no** existe `.view`). `edit` = acceso a pantalla + lookup + Excel + generate. Asignación Spatie **manual** (sin migración automática).
- Lookup ficha por cédula (activo → precarga nombre; inactivo / no encontrado → aviso; generación OK si validaciones pasan).
- Excel: plantilla `BaseExport` + import-preview → JSON a grilla (máx. 500; **no** escribe BD).
- Generación: `CartasNotificacionGeneratorService` + `LetterVariableBuilder::buildForCartasNotificacion` + `TerminationLetterDocxRenderer` / `TerminationLetterTemplateManager` / `WordTempDirectory` / `ZipArchive`.
- Audit: solo evento `cartas_notificacion_generate` (`row_count`, `output_type`, `template_id`; sin lista de cédulas).
- **Fuera de alcance:** permiso `.view`; Dashboard; subnav; historial / re-descarga; correo; varias plantillas seleccionables por lote; DataTables server-side; Select2; `excelHtml5`; Repository; `migrate:fresh`.

## Rutas

Archivo: `routes/areas/gestion_humana.php` (grupo `auth`/`active` global + `password.changed`). Prefijo `/gestion-humana/cartas-notificacion` · nombre `gestion-humana.cartas-notificacion.*`.

| Metodo | URI | Nombre | Middleware / permiso |
| --- | --- | --- | --- |
| GET | `/` | `index` | `cartas_notificacion.edit` (o bypass). Solo board → **403** con mensaje claro |
| POST | `/lookup` | `lookup` | Form Request → `canEdit` |
| GET | `/import-template` | `import-template` | `canEdit` (`abort_unless`) |
| POST | `/import-preview` | `import-preview` | Form Request → `canEdit` |
| POST | `/generar` | `generate` | Form Request → `canEdit`; stream descarga |

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.cartas_notificacion` | Ver tablero en sidebar GH |
| `cartas_notificacion.edit` | Entrar a la pantalla + lookup + Excel + generar/descargar |

- **No** existe `cartas_notificacion.view`.
- Labels Admin: board `Cartas Notificación`; funcional `Cartas Notificación: Generar`.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos vía `app:sync-permissions`; `administrador` / `usuario` **sin** paquete por defecto. **Sin** migración Spatie de assign.
- Admin UI: subgroup `boards` + subgroup `cartas_notificacion` bajo `gestion_humana` en `config/access.php`.

Servicio: `App\Services\Access\CartasNotificacionAccessService`

| Método | Regla |
| --- | --- |
| `canViewBoard` | board **∨** `cartas_notificacion.edit` **∨** bypass |
| `canEdit` | `cartas_notificacion.edit` **∨** bypass |
| `isAdminBypass` | `manage.users` |

| Otorgado | Efecto |
| --- | --- |
| board + edit | Sidebar OK + index + acciones |
| Solo edit (sin board) | Sidebar OK (vía `canViewBoard`) + index + acciones |
| Solo board | Sidebar OK; index/POST → **403** («Necesita el permiso Cartas Notificación: Generar…») |

## Controladores y requests

| Clase | Responsabilidad |
| --- | --- |
| `App\Http\Controllers\GestionHumana\CartasNotificacionController` | Index (grilla + options firma); lookup; generate + audit; import-template; import-preview |
| `LookupCartasNotificacionRequest` | Authorize `canEdit`; valida lista de cédulas |
| `GenerateCartasNotificacionRequest` | Authorize `canEdit`; `rows` 1…`max_rows`; cédula/nombre/fecha/signatory_id; unicidad cédula (after) |
| `ImportPreviewCartasNotificacionRequest` | Authorize `canEdit`; archivo Excel |

## Vistas

| Vista | Descripcion |
| --- | --- |
| `resources/views/areas/gestion_humana/cartas_notificacion/index.blade.php` | Shell chrome GH (panel, toolbar icon-only, thead corporativo, empty-state); Alpine grilla; modal pegar cédulas; flujo Excel; searchable-select firma por fila. **Sin** `.module-tab` / subnav |

Referencia visual: Cartas Vacaciones (Cliente interno) + Masivos (Desvinculaciones). Icon-btn canónicos (`.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn`); **sin** familia `cartas-notificacion__icon-btn`.

## Modelos y tablas

| Modelo / recurso | Tabla / origen | Notas |
| --- | --- | --- |
| — (lote) | — | **Ninguna** tabla de lotes ni paths de salida |
| `WordDocumentType` | `word_document_types` | Seed `code=cartas_notificacion`, name `Cartas Notificación`, `sort_order=4` |
| `TerminationLetterDocumentTemplate` | `termination_letter_document_templates` | Plantillas del tipo; generación exige exactamente 1 con archivo en disco |
| `EmployeeFichaProfile` | perfiles ficha | Lookup por `document_number` (preferir activo; si varios, más reciente por `id`) |
| `PayrollCatalogItem` | catálogo `firmas` | FIRMA UI / Excel (`signatory_id` → name/code) |

Config:

- `config/cartas_notificacion.php` → `max_rows` (500); `duracion_contrato_options` (`[6, 12]`).
- `config/employee_ficha.php` → `word_document_type_codes.cartas_notificacion`; categoría UI `letter_placeholders` **Cartas notificación** (incluye `DURACION_CONTRATO`).
- Seeder: `WordDocumentTypeSeeder` (`firstOrCreate` tipo).

## Servicios / jobs / mail (si aplica)

| Clase | Responsabilidad |
| --- | --- |
| `CartasNotificacionGeneratorService` | `lookup`, `generate` (1 docx / N zip + cleanup), `previewImport`, `resolveExactlyOneTemplate` |
| `LetterVariableBuilder::buildForCartasNotificacion` | Catálogo vacío → fill ficha → override fila (`CEDULA`, `NOMBRE_COMPLETO`, `DURACION_CONTRATO` 6|12, `FECHA_TERMINACION` largo ES, `FIRMA`/`CARGO_FIRMA`, `FECHA`=hoy); aliases `NOMBRE`/`DOCUMENTO`/`CIUDAD` |
| `TerminationLetterDocxRenderer` / `TerminationLetterTemplateManager` / `WordTempDirectory` | Motor Word compartido |
| `CartasNotificacionAuditLogService` | Wrapper → `SystemAuditService` (`module=cartas_notificacion`, `area=gestion_humana`) |
| `CartasNotificacionImportTemplateExport` | Extiende `BaseExport`; columnas CEDULA, NOMBRE_COMPLETO, DURACION_CONTRATO, FECHA_TERMINACION, FIRMA |

### Navegación

- `NavigationResolver`: board `cartas_notificacion` → `User::defaultCartasNotificacionBoardUrl()` si `canViewBoard`.
- Active: `str_starts_with($routeName, 'gestion-humana.cartas-notificacion.')`.
- `SidebarVisibilityService`: hogar `gestion_humana`.
- `board_canonical_areas`: hogar `gestion_humana`, `base_area_tab => false`.

### Placeholders — generación (`buildForCartasNotificacion`)

| Placeholder | Origen |
| --- | --- |
| `${CEDULA}` / `${DOCUMENTO}` | Fila; `DOCUMENTO` = misma cédula |
| `${NOMBRE_COMPLETO}` / `${NOMBRE}` | Fila (manda sobre ficha si viene escrito) |
| `${DURACION_CONTRATO}` | **Fila** / Excel: `6` o `12` (meses) |
| `${FECHA_TERMINACION}` | **Fila** (no vínculo/perfil); `formatLongDate` en **MAYÚSCULAS** (`15 DE JUNIO DEL 2026`) |
| `${FIRMA}` / `${CARGO_FIRMA}` | Catálogo firmas → `name` / `code` (`signatory_id`) |
| `${FECHA}` | Emisión (hoy), formato largo ES |
| Resto de `letter_placeholders` | Ficha por cédula si existe; vacío si no |
| `${CIUDAD}` | Alias de `CIUDAD_RESIDENCIA` |

**Nota:** en este flujo `${FECHA_TERMINACION}` manda la grilla; no confundir con `${FECHA_TERMINACION_PERFIL}` / `_VINCULO` de otros flujos.

### Flujo de generación

```text
1. UI / Form Request: cédula, nombre, DURACION_CONTRATO (6|12), FECHA_TERMINACION, signatory_id; ≤500; cédulas únicas.
2. resolveExactlyOneTemplate (tipo activo + archivo en disco).
3. Por fila: buildForCartasNotificacion → DocxRenderer → temp.
4. 1 → stream .docx; N → ZIP → stream .zip; cleanup workDir (finally) + deleteFileAfterSend.
5. Audit: cartas_notificacion_generate {row_count, output_type, template_id}.
```

Mensajes plantilla:

- 0: «No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.»
- >1: «Hay más de una plantilla activa de Cartas Notificación. Deje solo una activa.»

### Flujo Excel (solo memoria)

Columnas: `CEDULA` (obligatoria), `NOMBRE_COMPLETO`, `DURACION_CONTRATO` (6|12), `FECHA_TERMINACION`, `FIRMA` (name o code case-insensitive). Sin match FIRMA / duración inválida → vacío + aviso. Alpine fusiona en grilla (omitir duplicados de cédula + resumen). Nada se guarda en BD.

## Reglas de negocio

1. Sidebar: board **∨** edit **∨** bypass.
2. Index y todas las acciones: solo edit **∨** bypass.
3. Solo board → 403 en index/POST.
4. Obligatorios al generar: cédula, nombre completo, duración contrato (6 o 12), fecha terminación, `signatory_id` (firma activa).
5. Lookup: activo precarga nombre editable; inactivo/sin ficha → aviso; generación permitida si validaciones OK.
6. Duplicados: al pegar/import → omitir + resumen; al generate → 422.
7. Filtros solo client-side; **Limpiar** = vaciar grilla + reset filtro (`clearGrid()`).
8. Exactamente 1 plantilla del tipo con archivo; tope 500 filas.
9. 1 fila → `.docx`; 2+ → `.zip`; sin path permanente en storage.
10. Audit solo en generate; no lookup ni import-preview.

## JavaScript / assets (si aplica)

- Alpine en `index.blade.php`: grilla, filtros memoria, modal multi-cédula, merge Excel, `generateBatch` (blob download), searchable-select firma.
- Observaciones review (no bloqueantes): validación cliente parcial (cédula); estilos inline toolbar; normalización cédula UI (quita espacios internos) vs backend (`trim`).

## Export Excel (si aplica)

Solo **plantilla de carga** (`CartasNotificacionImportTemplateExport` / `BaseExport`). No export de listado persistente. Sin `excelHtml5`.

## Validacion local

1. `php artisan app:sync-permissions`; Admin: asignar board + `cartas_notificacion.edit`; re-login.
2. Plantillas Word: una plantilla tipo **Cartas Notificación** con archivo.
3. Probar grilla: pegar cédulas, Excel, avisos, filtros, Limpiar=vaciar, generar 1 y N; bloqueo 0/>1 plantillas.
4. Usuario solo board: 403 al entrar.
5. `php artisan test --compact tests/Feature/GestionHumana/CartasNotificacionAccessTest.php tests/Feature/GestionHumana/CartasNotificacionGenerateTest.php`
6. Smoke UI Chrome DevTools MCP (tablero + generate + import).

## Riesgos y pendientes

| Riesgo | Mitigacion / nota |
| --- | --- |
| 0 o >1 plantillas activas | Bloqueo + empty-state; docs Plantillas Word |
| Timeout / memoria ZIP grande | Tope 500; cleanup temp |
| Confusión `FECHA_TERMINACION` legacy vs grilla | Builder documentado; categoría UI **Cartas notificación** |
| Solo board sin edit | Mensaje 403; docs usuario: asignar ambos |
| Match FIRMA Excel ambiguo | Match exacto name/code; sin match → aviso |
| Obs. review #1 | Test Feature 501 filas pendiente (opcional) |
| Obs. review #2–5 | Validación UI, estilos inline, icon Generar, normalización cédula — mejoras post-cierre |

## Tests

- `tests/Feature/GestionHumana/CartasNotificacionAccessTest.php`
- `tests/Feature/GestionHumana/CartasNotificacionGenerateTest.php`

## Referencias

- Feature Brief: [`docs/briefs/FEAT-044.md`](../briefs/FEAT-044.md)
- Review: [`docs/reviews/FEAT-044.md`](../reviews/FEAT-044.md)
- Doc usuario: [`docs/user/cartas-notificacion.md`](../user/cartas-notificacion.md)
- Plantillas Word: [`docs/modules/plantillas-word.md`](plantillas-word.md)
- Control de acceso: [`docs/ACCESS_CONTROL.md`](../ACCESS_CONTROL.md)
- Cartas Vacaciones (patrón similar en CI): [`docs/modules/cliente-interno.md`](cliente-interno.md)
