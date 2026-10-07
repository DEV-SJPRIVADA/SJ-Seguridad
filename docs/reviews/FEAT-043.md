# Review Report — FEAT-043

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-043.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-043 |
| Fecha | 2026-10-07 |
| Alcance revisado | T1+T2 (lectura código): `config/access.php`, `ClienteInternoAccessService`, `HasClienteInternoTabs`, `User::defaultClienteInternoBoardUrl`, `ClienteInternoController` (cartas), rutas GH GET/POST, `WordDocumentTypeSeeder`, migración Spatie, `employee_ficha` placeholders/codes, `ClienteInternoCartasVacacionesGeneratorService`, `LetterVariableBuilder::buildForCartasVacaciones`, Form Requests Lookup/Generate, `cartas-vacaciones.blade.php`, `config/cliente_interno.php`, tests Access/Generate/BoardAccess |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno. Sin bypass de auth, permisos faltantes ni registro público. | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `cartas-vacaciones.blade.php` | El flujo del brief (paso 1) describe validación UI de obligatorios y `fin≥inicio` / `reintegro≥fin` antes de generar. Alpine solo exige cédula no vacía (`hasProcessableRows`) y delega el resto al 422 del backend (cubierto por Form Request + tests). | Validación cliente por fila con mensaje claro; mantener reglas actuales en `GenerateCartasVacacionesRequest`. |
| 2 | `ClienteInternoCartasVacacionesGenerateTest.php` | No hay caso Feature que fuerce el tope de 500 filas (`max` Form Request / Generator). El límite está cableado en `config/cliente_interno.php`, reglas y UI. | Test 422 con 501 filas (payload mínimo) para cerrar AC del límite. |
| 3 | `cartas-vacaciones.blade.php` | Botón Generar usa `req-manage-filters__icon-btn--primary` + icono download. El mapa icon-only del proyecto marca Descargar como `--ghost`. | Alinear a `--ghost`, o CTA primario con texto si se quiere énfasis de acción. |
| 4 | `ClienteInternoCartasVacacionesGeneratorService::normalizeDocument` | Solo hace `trim`. El paste masivo en UI también quita espacios internos (`replace(/\s+/g, '')`); el lookup fila a fila / backend no normaliza igual. | Alinear normalización backend (y blur de fila) con el paste para evitar miss de ficha. |
| 5 | `ClienteInternoCartasVacacionesGenerateTest.php` | AC#9 (placeholders sustituidos, sin literales `${…}`) no se aserta abriendo el `.docx` generado; sí hay cobertura de `buildForCartasVacaciones` (FIRMA/CARGO/fechas) y download 1→docx / N→zip. | Opcional: assert sobre texto del docx descargado (1 fila) sin literales de las 9 claves. |
| 6 | Docs módulo / ACCESS | `cliente-interno`, `plantillas-word`, `ACCESS_CONTROL` (+ INDEX si aplica) pendientes — esperable para fase Documentador. | Documentador al cierre; no bloquea Feature. |
| 7 | Run log / cierre UI | Plan T2 marca smoke DevTools MCP; el run log no deja evidencia de URL/pasos/resultado. Tests PHPUnit OK (29 passed). | AgentSj/Feature: confirmar smoke UI en cierre o dejar nota en run log. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`)
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests)
- [x] Sin duplicacion innecesaria
- [x] Rutas en archivo de modulo/area correcto (`routes/areas/gestion_humana.php`, grupo `password.changed`; sin tocar `web.php`)
- [x] Migraciones compatibles con hosting compartido (migración de datos Spatie idempotente users+roles; sin schema destructivo; sin `migrate:fresh`)
- [x] Export Excel usa `BaseExport` si aplica (N/A — fuera de alcance)
- [x] Tests relevantes presentes o justificados

## Verificacion vs brief (ley)

| Criterio | Estado |
| --- | --- |
| Permisos `cartas_vacaciones.view` / `.edit` + labels Admin | OK — `config/access.php` system_permissions + subgroup Cliente interno |
| `edit` ⇒ `view` en AccessService | OK — `canViewCartasVacaciones` = view ∨ edit |
| Dashboard **no** con cartas-only | OK — `canViewDashboard` solo solicitudes ∨ parameters; tests Access |
| Redirect shell / `defaultClienteInternoBoardUrl` a primera pestaña (cartas si solo esa) | OK |
| Migración Spatie desde `solicitudes.edit` → view **y** edit (users + roles), idempotente | OK — migración + test (doble `up()`) |
| Exactamente 1 plantilla tipo activo + archivo en disco; 0 / >1 bloquean con mensajes del brief | OK — `resolveExactlyOneTemplate` |
| 1 → `.docx`; N → `.zip`; sin persistencia; cleanup temp + `deleteFileAfterSend` | OK |
| Validaciones obligatorias + fechas + firma firmas activas + unicidad cédula | OK — Form Request + `after()` |
| Lookup exige `.edit`; view-only empty-state + 403 generate/lookup | OK |
| Placeholders + `buildForCartasVacaciones` (FIRMA name / CARGO_FIRMA code; fechas largo ES) | OK |
| Audit `cartas_vacaciones_generate` con `row_count`, `output_type`, `template_id` sin PII masiva | OK |
| Límite 500 | OK en config/reglas/UI; test explícito pendiente (obs. 2) |
| Searchable-select Alpine (partial Masivos); sin Select2; sin `migrate:fresh` | OK |
| Seed tipo `cartas_vacaciones` + config codes + placeholders categoría Cartas vacaciones | OK |
| Ortografía UI (cédula, Días, Generar, plantilla, empty-state) | OK en cadenas tocadas |

## Seguridad

- Rutas bajo `auth` + `active` (vía `web.php`) y `password.changed` (grupo GH Cliente interno).
- Authorize generate/lookup vía Form Request → `canEditCartasVacaciones` (bypass `manage.users`).
- GET pestaña → `canViewCartasVacaciones`; Dashboard gated sin permisos de cartas.
- Sin registro público; sin bypass de middleware.
- Audit sin lista de cédulas.
- Salida temporal (`WordTempDirectory`) + cleanup `workDir` en `finally` + `deleteFileAfterSend(true)`.
- Firma validada contra catálogo `firmas` activo (`exists` + `is_active`).

## Consistencia con AGENTS.md y docs

- Modelo view/edit por pestaña alineado a Comercial / FEAT-042.
- Reutiliza stack Word existente (DocxRenderer, TemplateManager, WordTempDirectory); sin Repository; sin Select2; sin excelHtml5.
- Icon buttons estándar (chrome `.req-manage-filters__icon-btn` + fila `.cursos-catalogo-page__icon-btn`); firma con Alpine `searchableSelect` (mismo partial que Masivos).
- Documentación viva de módulo/ACCESS pendiente del Documentador (obs. 6).

## Resultado de tests (Revisor)

```text
php artisan test --compact
  tests/Feature/GestionHumana/ClienteInternoCartasVacacionesAccessTest.php
  tests/Feature/GestionHumana/ClienteInternoCartasVacacionesGenerateTest.php
  tests/Feature/GestionHumana/ClienteInternoBoardAccessTest.php

Tests:    29 passed (152 assertions)
Duration: ~90s
```

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones)
- [ ] Devolver a Agente Feature (si bloqueado)

### Instrucciones para AgentSj

1. **No** devolver a Feature por bloqueantes (no hay).
2. Lanzar **Documentador** (`cliente-interno`, `plantillas-word`, `ACCESS_CONTROL`, INDEX si aplica).
3. Observaciones 1–5 y 7 son mejoras opcionales / backlog o confirmación de smoke; no condicionan el Documentador.
