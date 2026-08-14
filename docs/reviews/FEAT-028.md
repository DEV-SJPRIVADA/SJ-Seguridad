# Review Report — FEAT-028

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-028 |
| Fecha | 2026-08-13 |
| Revisor | Agente Revisor |
| Brief | [`docs/briefs/FEAT-028.md`](../briefs/FEAT-028.md) |
| Alcance revisado | Config/catalogos, sync, validacion, UI formulario, prefill, export/import mappers, tests FE028 |
| Veredicto | **Aprobado** — apto para cierre y documentacion |

## Verificacion de criterios (brief)

| # | Criterio | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Mismo formulario completo en create, edit y gestionar pendiente | OK | `ficha-form-fields.blade.php` compartido en `create-ficha` y `edit-ficha` |
| 2 | Un selector por par codigo/nombre; sync automatico de homologo | OK | `EmployeeFichaProfileCatalogSync` + `ficha-catalog-select.blade.php` |
| 3 | Campos obligatorios al guardar | OK | `EmployeeFichaProfileFieldRules` + tests sync/form |
| 4 | Export masivos = solo perfil + `payroll_extra` guardados | OK | `PlantillaMasivosMapper` sin fallbacks requisicion; tests `EmployeeFichaMasivosExportFe028Test` |
| 5 | Col Z NITCENTROTB siempre vacia | OK | Mapper fuerza `null` en indice 25; test dedicado |
| 6 | CLASEDOC soporta CE y exporta solo codigo | OK | `document_type_defaults` + test CE round-trip |
| 7 | Prefill honesto (sin contaminar exportables) | OK | `EmployeeFichaProfilePrefill` + bloque referencia readonly |
| 8 | Tests FE028 en verde | OK | 41 tests / 196 assertions en suite FE028 |

## Seguridad y permisos

- Sin permisos nuevos; reutiliza `ficha_empleados.manage` / `ficha_empleados.view`.
- Validacion server-side con `PayrollCatalogCode` evita codigos inventados en campos de catalogo.
- `config/access.php` no modificado.

## Observaciones (no bloqueantes)

| # | Descripcion | Sugerencia |
| --- | --- | --- |
| 1 | Test preexistente `test_admin_bypass_can_view_and_manage_ficha_empleados` espera solo tab `empleados` pero hoy hay `catalogos` tambien. | Deuda tecnica ajena a FEAT-028; corregir expectativa del test. |
| 2 | Import masivo (`EmployeeFichaImportService`) aun no persiste todos los campos de `payroll_extra` desde Excel SJ (solo columnas del template import). | Aceptable v1; completar cuando el template import incluya columnas masivos avanzadas. |

## Siguiente paso

- [x] Revisor — aprobado.
- [x] Documentador — `docs/modules/ficha-empleados.md`, `docs/user/ficha-empleados.md`.
