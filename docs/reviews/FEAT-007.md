# Review Report — FEAT-007

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-007.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-007 |
| Fecha | 2026-07-27 |
| Alcance revisado | Migracion `document_expiry`, `CommercialService` helpers, `StoreCommercialServiceRequest` / Update, `service-fields.blade.php`, `CommercialServiceController` + Dashboard (helpers), `MtCo01Importer` aliases, `CommercialMatrixTest` |
| Veredicto | Aprobado con observaciones |

## Criterios de aceptacion (checklist)

| # | Criterio | Resultado |
| --- | --- | --- |
| 1 | Create/edit: toggle + fecha por documento | OK — `service-fields` en create/edit; 10 docs con checkbox y `input type=date` |
| 2 | OK + toggle ON sin fecha → error validacion | OK — `Rule::requiredIf` + test `test_service_store_requires_expires_when_ok_and_tracks_expiry` |
| 3 | OK + toggle OFF → guarda sin fecha | OK — `normalizeDocumentExpiryInputs` fuerza `null` + test `allows_ok_without_expires_when_not_tracking` |
| 4 | N/A u vacio → oculta toggle/fecha | OK — Blade + JS; server fuerza `tracks=false` / `expires=null` |
| 5 | Badges listado: contrato o documento | OK — `isExpired` / `isExpiringSoon` incluyen docs; usados en services index y client show |
| 6 | Filtros vigencia incluyen documentos | OK — `scopeFilterByVigencia` OR por columnas `*_tracks_expiry` / `*_expires_on` |
| 7 | Importador fecha+tracks si columna reconocida | OK — aliases + set `tracks=true` solo si `parseDate` no null; sin inventar |
| 8 | Tests pasan | OK — `CommercialMatrixTest` 10 passed (corridos en revision) |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- |
| 1 | `CommercialService::scopeFilterByVigencia` vs Dashboard | Con docs, un servicio puede entrar en filtro `expiring` y a la vez estar `expired` por otro documento; el Dashboard si excluye (`isExpiringSoon && !isExpired`). | Opcional: alinear filtro con exclusion mutua del Dashboard. |
| 2 | `tests/Feature/CommercialMatrixTest.php` | No hay test HTTP de filtro `vigencia` con doc vencido/por vencer ni cobertura del importer. | Opcional: assert de query/filtro e import con columna `vencimiento rut`. |
| 3 | `MtCo01Importer` | Re-import sin columna de fecha no limpia `tracks`/`expires` previos (no inventa, pero tampoco limpia). Alias de contrato documental exige sufijo `documental` (evita choque con `contract_end`). | Aceptable; documentar en doc usuario/tecnica si el Documentador refuerza. |
| 4 | `service-fields.blade.php` | Fecha no marca `required` HTML cuando OK+toggle; solo validacion server-side. | Opcional: `required` dinamico en JS. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — reutiliza `authorizeView` / `authorizeManage` existentes
- [x] Sin registro publico ni bypass de middleware
- [x] Validacion de entradas (Form Requests) — condicional por estado + toggle; Update hereda Store
- [x] Sin duplicacion innecesaria — helpers centralizados en modelo
- [x] Rutas en archivo de modulo/area correcto — sin rutas nuevas ni shared-files
- [x] Migraciones compatibles con hosting compartido — boolean default false + date nullable + `down()`
- [x] Export Excel usa `BaseExport` si aplica — columna Vigencia ya via helpers actualizados
- [x] Tests relevantes presentes o justificados — store required/optional + `isExpired` documental

## Seguridad

- Sin cambios de permisos, auth ni registro publico.
- Persistencia solo via Form Request `validated()` en store/update.
- Normalizacion server-side evita persistir fecha/tracks con estado vacio o N/A aunque el cliente envie basura.

## Consistencia con AGENTS.md y docs

- Vertical slice del modulo comercial / matriz-clientes; sin `config/access.php` ni `routes/web.php`.
- Docs tecnica/usuario ya mencionan vencimiento por documento (Feature); Documentador puede consolidar control de cambios.

## Siguiente paso

- [x] Pasar a Documentador (aprobado; sin blockers)
- [ ] Devolver a Agente Feature (si bloqueado)
