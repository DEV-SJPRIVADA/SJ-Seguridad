# Review Report — FEAT-037

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-037 / T1 |
| Fecha | 2026-09-24 |
| Alcance revisado | Working tree vs `docs/briefs/FEAT-037.md` + plan T1 (migraciones, models, Import/List/Datatable, Form Request, controller `reporteDiario*`, rutas, vista, config headers, audit, tests) |
| Veredicto | **Aprobado con observaciones** |
| Revisor | Revisor FEAT-037 (subagente) |
| Blockers | No |
| Tests ejecutados | `tests/Feature/GestionHumana/AcreditacionesReporteDiarioTest.php` — **12 passed** (77 assertions) |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `AcreditacionesReporteDiarioTest` | Decisión crítica «validar **todos** los Excel antes de mutar / abort total» está implementada en `ImportService` (parse completo → luego confirm → luego TX), pero el test de headers inválidos cubre **un solo** archivo. Falta regresión 2 archivos (uno OK + uno bad) que demuestre que el origen ya cargado **no** se toca. | Añadir 1 test: día con PROCESO ok; POST ambos (proceso válido + acreditado headers rotos) → error; filas PROCESO intactas; sin cabecera/filas nuevas del día/origen fallido. Follow-up opcional, no bloquea. |
| 2 | Baja | `AcreditacionesReporteDiarioTest` | Audit de import (`eventType=acreditacion_reporte_diario_carga`, `action=imported`) está en controlador; **no** hay aserción sobre `audit_logs`. | 1 aserción espejo Desvinculaciones/Cursos. |
| 3 | Baja | `AcreditacionReporteDiarioListService::all` / `exportReporteDiario` | Export hace `filteredQuery()->get()` sin tope; en v1 (~2000 filas/día) es aceptable. | Documentar límite operativo; chunk/stream solo si crece el filtro multi-día. |
| 4 | Baja | `reporte-diario.blade.php` (Alpine) | Tras error `confirm_replace`, `confirmReplace` se inicializa en `true` (checkbox pre-marcado). El usuario aún debe re-elegir archivos y reenviar; es confirmación por reintento, no por check explícito. | Preferible `confirmReplace: false` y obligar el checkbox; UX. |
| 5 | Baja | Tests | Caso brief «headers OK + 0 filas válidas → replace vacío del origen» no tiene test dedicado (el código sí borra/inserta vacío y actualiza metadata). | Test opcional de cobertura. |
| 6 | Info | Docs módulo/usuario | `docs/modules/acreditaciones.md` aún describe Reporte Diario como placeholder; `docs/user/acreditaciones.md` pendiente de actualizar. | **Documentador** (previsto; no bloquea código). |
| 7 | Info | Working tree | Hay cambios ajenos a FEAT-037 (p. ej. requisitions, `app.css` `.req-manage-filters__export-group` no usado por esta vista). | AgentSj: no mezclar en el commit de cierre de FEAT-037. |

### Nits

| # | Archivo | Nota |
| --- | --- | --- |
| N1 | `ImportAcreditacionReporteDiarioRequest` | Acepta `csv` además de xlsx/xls; brief habla de Excel APO. Aceptable; documentar o restringir si se quiere estricto. |
| N2 | `AcreditacionReporteDiarioListService::cargasList` | `->get()` de todas las cabeceras; OK v1; paginar si el histórico crece mucho. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — `acreditaciones.view` / `.edit` vía `AcreditacionesAccessService`; Form Request `canEdit` en import; sin permiso nuevo
- [x] Sin registro publico ni bypass de middleware — rutas en bloque `password.changed` bajo grupo `auth`/`active` de `web.php`
- [x] Validacion de entradas (Form Requests) — fecha ≤ hoy; ≥1 archivo; mimes; `confirm_replace` boolean
- [x] Sin duplicacion innecesaria — espejo Acreditados (Import/List/Datatable)
- [x] Rutas en archivo de area correcto — `routes/areas/gestion_humana.php` (shared-files autorizado)
- [x] Migraciones compatibles hosting + sqlite tests — `create` aditivas, sin `enum()`/`change()`; FKs `nullOnDelete` / `cascadeOnDelete`
- [x] Export Excel usa `BaseExport` + `<x-export-excel>`; sin `excelHtml5`
- [x] Selectores: `<x-searchable-select>` en filtro origen; sin Select2
- [x] DataTables `serverSide: true`; `lengthMenu` sin `-1`; tope 100 en servicio
- [x] Sin tocar `config/access.php`
- [x] Sin cruce Ficha / `acreditacion_acreditados` / `AcreditacionEstadoCalculator`
- [x] Replace parcial: delete solo orígenes subidos; metadata del no enviado intacta (test parcial)
- [x] Headers validados (parse) antes de mutar BD; filas malas skip+reporte
- [x] Audit mutación import/replace (no GET)
- [x] Tests Feature presentes (12 verdes)
- [ ] Docs modules/user alineadas — **pendiente Documentador**

## Seguridad

- GETs index/datatable/export/cargas: `canView` (403 sin permiso; guest redirect).
- POST import + download reporte fallos: `canEdit` (Form Request + `abort_unless`).
- Viewer: sin botón/modal de carga; POST import 403 (test).
- CSRF en form multipart; token cache ~1 h para reporte de fallos.
- Sin escritura a `acreditacion_acreditados`; sin calculadora de estados.
- XSS en celdas DT: `e()` en `formatRow`.

## Consistencia con AGENTS.md y brief (decisiones críticas)

| Decisión | Estado |
| --- | --- |
| Carga 1–2 archivos; partial conserva origen no enviado | OK (código + test parcial) |
| Fecha ≤ hoy; futuro rechazado | OK (Form Request + service + test + `max` UI) |
| `confirm_replace` si origen ya tiene datos | OK (metadata/`origenHasPriorData` + tests) |
| Validar headers de **todos** antes de mutar; mal → abort total | OK en código; cobertura test dual-file pendiente (obs. #1) |
| Filas malas skip+reporte; sin Ficha; sin EstadoCalculator | OK |
| DT server-side; BaseExport; sin Select2/excelHtml5 | OK |
| Sin cruce Ficha/Acreditados; sin versionado mismo día | OK (replace duro por origen) |
| Sin `access.php` / permiso nuevo | OK |

## Criterios de aceptacion (muestreo)

| AC | Estado |
| --- | --- |
| view: listado + filtros + export; sin cargar | OK |
| edit: 1 / 2 orígenes; partial preserve | OK |
| Fecha futura 422/redirect | OK |
| Replace sin confirm no muta; con confirm sí | OK |
| Headers inválidos no mutan | OK (1 archivo) |
| Fila sin IdNum → fail + resto ok | OK |
| Cédula no en Ficha se guarda; 0 en acreditados | OK |
| DT JSON + export spreadsheet | OK |
| Ver cargas (JSON + modal Alpine) | OK (código; smoke UI manual recomendable) |
| Audit import | OK código; aserción DB pendiente |
| Docs | Pendiente Documentador |

## Siguiente paso

- [x] **Pasar a Documentador** (veredicto no bloqueado)
- [ ] Devolver a Agente Feature — no aplica

**Mensaje a AgentSj:** FEAT-037 / T1 **Aprobado con observaciones**. Sin blockers. Lanzar Documentador (`docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md`, INDEX / ACCESS_CONTROL si describen placeholder). Observaciones #1–#5 son follow-up opcionales (test dual-header + audit assert recomendados). Separar del commit archivos ajenos (requisitions / CSS no usado por esta pestaña).
