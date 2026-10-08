# Review Report — FEAT-044

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-044.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-044 |
| Fecha | 2026-10-08 |
| Alcance revisado | T1+T2 (lectura código + PHPUnit): `config/access.php`, `cartas_notificacion.php`, `employee_ficha.php`, `CartasNotificacionAccessService`, nav (`NavigationResolver`, `SidebarVisibilityService`), `User::defaultCartasNotificacionBoardUrl`, `CartasNotificacionController`, Form Requests Lookup/Generate/ImportPreview, `CartasNotificacionGeneratorService`, `LetterVariableBuilder::buildForCartasNotificacion`, `CartasNotificacionImportTemplateExport`/`BaseExport`, `CartasNotificacionAuditLogService`, `WordDocumentTypeSeeder`, vista `cartas_notificacion/index.blade.php`, rutas GH, tests Access + Generate |
| Veredicto | **Aprobado con observaciones** |

## Hallazgos

### Bloqueantes

| # | Archivo | Descripcion | Accion requerida |
| --- | --- | --- | --- |
| — | — | Ninguno. Sin bypass de auth, sin `.view`, sin Dashboard/pestañas, sin Select2, sin `migrate:fresh`, sin migración Spatie de assign. UI clona chrome Cartas Vacaciones / Masivos (no prototipo crudo). | — |

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `CartasNotificacionGenerateTest.php` | No hay caso Feature que fuerce el tope de 500 filas (`max` Form Request / Generator). El límite está cableado en `config/cartas_notificacion.php`, reglas, UI y Alpine. | Test 422 con 501 filas (payload mínimo) para cerrar AC del límite. |
| 2 | `cartas_notificacion/index.blade.php` | Alpine `generateBatch` / `hasProcessableRows` solo exige cédula no vacía; obligatorios (nombre, fecha, firma) y unicidad se delegan al 422 del backend (cubierto por Form Request + tests). | Validación cliente por fila con mensaje claro antes del POST; mantener reglas en `GenerateCartasNotificacionRequest`. |
| 3 | `cartas_notificacion/index.blade.php` | Toolbar de acciones usa `style="display: flex…"` e input filtro con `min-width`/`max-width` inline. | Preferir clase utilitaria / chrome existente (p. ej. flex del toolbar Vacaciones) sin estilos inline. |
| 4 | `cartas_notificacion/index.blade.php` | Botón Generar usa `req-manage-filters__icon-btn--primary` + icono download (mismo patrón Vacaciones). El mapa icon-only marca Descargar como `--ghost`. | Alinear a `--ghost`, o CTA primario con texto si se quiere énfasis. |
| 5 | `CartasNotificacionGeneratorService::normalizeDocument` | Solo `trim`. El paste masivo en UI quita espacios internos (`replace(/\s+/g, '')`); lookup fila a fila / backend no normaliza igual. | Alinear normalización backend (y blur de fila) con el paste. |
| 6 | Docs módulo / ACCESS / plantillas-word | `cartas-notificacion`, `plantillas-word`, `ACCESS_CONTROL`, `ARCHITECTURE` (+ INDEX si aplica) pendientes — esperable para fase Documentador. | Documentador al cierre; no bloquea Feature. |
| 7 | Run log | T2 marca smoke DevTools; evidencia breve en run log. PHPUnit OK (19 passed). | Confirmar nota de smoke (URL/paso/resultado) en cierre AgentSj si hace falta trazabilidad. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`)
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests)
- [x] Sin duplicacion innecesaria
- [x] Rutas en archivo de modulo/area correcto (`routes/areas/gestion_humana.php`, grupo `password.changed`; sin tocar `web.php`)
- [x] Migraciones compatibles con hosting compartido (sin migración de assign Spatie; seed tipo Word + sync permisos; sin `migrate:fresh`)
- [x] Export Excel usa `BaseExport` si aplica (plantilla import → grilla memoria)
- [x] Tests relevantes presentes o justificados
- [x] UI nueva: `module-ui-quality` — shell Masivos/Vacaciones (`page-section`, `panel`, toolbar icon-only canónico, `data-table`, searchable-select Alpine, empty-state), sin Select2 ni familia `cartas-notificacion__icon-btn`
- [x] UI: grilla en memoria ≤500 (DataTables server-side N/A por brief)

## Verificacion vs brief (ley)

| Criterio | Estado |
| --- | --- |
| Solo `cartas_notificacion.edit` (**sin** `.view`) + board `view.board.gestion_humana.cartas_notificacion` | OK — `config/access.php` system_permissions + Admin subgroup; test niega `.view` |
| Labels Admin legibles (`Cartas Notificación: Generar`, board en Ver tableros) | OK |
| AccessService: `canViewBoard` = board ∨ edit ∨ bypass; `canEdit` = edit ∨ bypass | OK |
| Solo board → sidebar OK, index/POST **403** mensaje claro | OK — controller + tests Access/Generate |
| Sin migración Spatie assign automática; `administrador` no recibe permisos por defecto | OK — sin migración; test `administrador_does_not_receive…` |
| Sin Dashboard / sin pestañas (`module-tab`); grilla directa | OK |
| Tipo Word `cartas_notificacion` seed + config codes; exactamente 1 plantilla con archivo; 0/>1 bloquean mensajes del brief | OK — seeder + `resolveExactlyOneTemplate` + tests |
| Grilla memoria; Excel plantilla + import-preview **sin BD** | OK — BaseExport + `previewImport`; test cuenta perfiles estable |
| Lookup ficha (activo / inactivo / no encontrado) + nombre editable | OK |
| FIRMA searchable-select (catálogo `firmas`); payload `signatory_id` | OK — Alpine `searchableSelect` + partial Masivos |
| `FECHA_TERMINACION` largo ES desde grilla (`formatLongDate`) | OK — test `15 de Junio del 2026` + FIRMA/CARGO_FIRMA |
| 1 → `.docx`; N → `.zip`; cleanup temp + `deleteFileAfterSend` | OK — generator + tests |
| Filtros en memoria; Limpiar = `clearGrid()` (vacía filas + filtro) | OK |
| Tope 500; unicidad cédula en generate (422) | OK en config/reglas/UI/Alpine; test explícito 501 pendiente (obs. 1) |
| Audit solo `cartas_notificacion_generate` con `row_count` / `output_type` / `template_id` sin PII masiva | OK |
| Sin Select2; sin `excelHtml5`; sin `migrate:fresh` | OK |
| UI calidad desde T1 (no Blade mínimo feo) | OK — paridad visual Vacaciones/Masivos |

## Seguridad

- Rutas bajo `auth` + `active` (vía `web.php`) y `password.changed` (grupo GH).
- Index exige `canEdit` (403 si solo board); lookup/generate/import-preview via Form Request authorize; import-template con `abort_unless(canEdit)`.
- Bypass solo `manage.users`.
- Sin registro público; sin permiso `.view` inventado.
- Firma validada contra catálogo `firmas` activo (`exists` + `is_active`).
- Audit sin lista de cédulas.
- Salida temporal (`WordTempDirectory`) + cleanup `workDir` en `finally` + `deleteFileAfterSend(true)`.
- Excel no escribe tablas de negocio.

## Consistencia con AGENTS.md y docs

- Modelo board + solo `.edit` (sin `.view`) alineado al brief y a patrón Archivo/Plantillas Word (funcional único).
- Reutiliza stack Word (`TerminationLetterDocxRenderer`, `TerminationLetterTemplateManager`, `WordTempDirectory`, `LetterVariableBuilder`); sin Repository.
- Icon buttons canónicos; searchable-select; chrome GH de referencia.
- Documentación viva de módulo/ACCESS/plantillas-word pendiente del Documentador (obs. 6).

## Pruebas ejecutadas (Revisor)

```text
php artisan test --compact tests/Feature/GestionHumana/CartasNotificacionAccessTest.php tests/Feature/GestionHumana/CartasNotificacionGenerateTest.php
→ 19 passed (94 assertions)
```

## Siguiente paso

- [x] Pasar a Documentador (si aprobado)
- [ ] Devolver a Agente Feature (si bloqueado)

**Para AgentSj:** veredicto **Aprobado con observaciones** — lanzar Documentador (`docs/modules/cartas-notificacion.md`, `docs/user/cartas-notificacion.md`, plantillas-word, ACCESS_CONTROL, ARCHITECTURE). Observaciones 1–5 son mejoras opcionales post-cierre; ninguna bloquea.
