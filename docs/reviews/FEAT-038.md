# Review Report — FEAT-038

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-038 (T1–T4) |
| Fecha | 2026-09-25 |
| Alcance revisado | Working tree vs `docs/briefs/FEAT-038.md` + `AGENTS.md` + `docs/modules/acreditaciones.md` (estado pre-Documentador) |
| Veredicto | **Aprobado con observaciones** |
| Revisor | Revisor FEAT-038 (subagente) |
| Blockers | No |
| Tests ejecutados | `AcreditacionesValidacionesTest` + `AcreditacionesBoardAccessTest` — **43 passed** (216 assertions), ~45 s |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `AcreditacionesValidacionesTest` | ResultStore aísla por `user_id` en key + validación de payload, pero **no hay test** que demuestre que el token de un editor no sirve a otro. | Añadir 1 Feature test: editor A ejecuta → editor B con mismo `run_token`/`fecha` en DT/export → vacío/422. Follow-up opcional. |
| 2 | Baja | `AcreditacionValidacionesRunnerService::fichaEntryIdByDocumentMap` | El mapa Ficha usa `document_number` crudo; la exclusión de cédulas en `sin_acreditacion` sí normaliza. Si hubiera discrepancia de case entre Acreditados y Ficha, «Abrir Ficha» podría no resolverse. | Indexar el mapa con `normalizeDocument` (cédulas suelen ser dígitos; riesgo bajo). |
| 3 | Info | `docs/modules/acreditaciones.md` / user / ACCESS | Docs aún describen Validaciones como placeholder y permisos de GET `validaciones` con `view`. | **Documentador** (previsto post-Revisor). |
| 4 | Info | `config/access.php` | No se tocó (correcto). Texto de `acreditaciones.edit` sigue sin mencionar Validaciones. | Documentador / nota en ACCESS_CONTROL. |
| 5 | Info | Rutas | `validaciones.gate` JSON no existe; gate se resuelve en GET shell. | Alineado al Brief («opcional»). Sin acción. |
| 6 | Info | `AcreditacionValidacionesConsolidatedExport` | Consolidado multi-hoja es clase dedicada (PhpSpreadsheet), no extiende `BaseExport`; export por cola sí usa `BaseExport` + `<x-export-excel>`. | Aceptable (AGENTS.md: formato complejo → clase dedicada). |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — todos los `validaciones*` con `canEdit`; tab Validaciones oculta sin edit (como Catálogo)
- [x] Sin registro publico ni bypass de middleware — rutas en `gestion_humana.php` bajo `password.changed` + grupo `auth`/`active` de `web.php`
- [x] Validacion de entradas (Form Requests) — fecha ≤ hoy; `run_token` uuid; `cola` in COLAS; `authorize` = `canEdit`
- [x] Sin duplicacion innecesaria — modales Acreditados reutilizados; vertical slice en controller
- [x] Rutas en archivo de area correcto — `routes/areas/gestion_humana.php`; sin tocar `web.php` ni `config/access.php`
- [x] Migraciones — **sin migración**; sin tablas históricas de corridas; no `migrate:fresh`
- [x] Export Excel — cola: `BaseExport`; consolidado: 4 hojas; UI: `<x-export-excel>`; sin `excelHtml5`
- [x] Selectores — fecha `input type="date"`; sin Select2
- [x] DataTables `serverSide: true`; `lengthMenu` sin `-1`; tope 100 en servicio
- [x] Match/gate alineados al Brief — ambos orígenes; `cargo_apo`↔`cargo` normalizado; sin “contiene” en match; vencidas DESACREDITADO+POR_VENCER
- [x] Cache keyed `user_id` + fecha + token; get valida `user_id` del payload
- [x] Tests Feature presentes (43 verdes)
- [ ] Docs modules/user alineadas — **pendiente Documentador**

## Seguridad

- Shell, run, datatable, export cola/consolidado: `abort_unless(canEdit)` + Form Requests con `authorize` = `canEdit`.
- Viewer: sin tab Validaciones; GET/POST/datatable/export → 403 (tests).
- Guest: rutas detrás de auth (grupo web).
- Cache: key `acreditaciones:validaciones:{userId}:{fecha}:{token}`; `get` rechaza payload de otro `user_id`.
- Abrir Ficha: solo si `ficha_empleados.manage` (ruta destino `editFicha` exige manage) y hay `ficha_entry_id`.
- XSS en celdas DT: `e()` en `formatRow`.
- Sin auditoría de la corrida de solo lectura (Brief); mutaciones vía endpoints Acreditados existentes.

## Consistencia con AGENTS.md y Brief

| Decisión | Estado |
| --- | --- |
| Permiso solo `acreditaciones.edit`; sin permiso nuevo / sin `access.php` | OK |
| Gate ambos orígenes (`origenHasPriorData` PROCESO + ACREDITADO) | OK |
| Match `cargo_apo` vs `cargo` reporte; normalización NFD/uppercase/espacios; sin “contiene” | OK |
| 4 colas (sin_acreditacion, ausente_reporte, en_proceso_ya_acreditado, vencidas) | OK |
| Vencidas: DESACREDITADO + POR_VENCER; independiente del reporte | OK |
| Persistencia solo caché (TTL 5400 s); sin migración | OK |
| POST ejecutar; no auto-ejecutar al entrar | OK |
| DT server-side por cola; length tope 100 | OK |
| Acciones Ficha / editar / nuevo (precarga cédula) | OK |
| Export por cola + consolidado 4 hojas | OK |
| Rutas en `gestion_humana.php` | OK |

## Tests

```
php artisan test --compact tests/Feature/GestionHumana/AcreditacionesValidacionesTest.php tests/Feature/GestionHumana/AcreditacionesBoardAccessTest.php
→ Tests: 43 passed (216 assertions)
→ Duration: ~45 s
→ exit_code: 0
```

Cobertura relevante: permisos view/edit, gate (sin carga / un origen / ambos), futuro rechazado, ejecución + token, 4 colas, normalización (sufijo distinto + diacríticos), DT length -1 capped, export cola/consolidado/4 hojas, acciones, Abrir Ficha gated, sin Select2/excelHtml5, sin auto-ejecución.

## Siguiente paso

- [x] **Pasar a Documentador** (veredicto no bloqueado)
- [ ] Devolver a Agente Feature — no aplica

### Veredicto para AgentSj

**Aprobado con observaciones.** Lanzar Documentador para alinear `docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md` y notas de ACCESS (Validaciones = edit; deja de ser placeholder). Observaciones 1–2 son follow-up opcionales de calidad/test, no bloquean cierre.
