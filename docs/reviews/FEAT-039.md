# Review Report — FEAT-039

> Generado por el Revisor. Guardar en `docs/reviews/FEAT-XXX.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-039 (T1–T4) |
| Fecha | 2026-09-28 |
| Alcance revisado | Working tree vs `docs/briefs/FEAT-039.md` + plan + `AGENTS.md` (estado pre-Documentador) |
| Veredicto | **Aprobado con observaciones** |
| Revisor | Revisor FEAT-039 (subagente) |
| Blockers | No |
| Tests ejecutados | Params + Candidates + Generate + Dashboard + BoardAccess — **34 passed** (188 assertions), ~83 s |

## Hallazgos

### Bloqueantes

Ninguno.

### Observaciones (no bloqueantes)

| # | Prioridad | Archivo | Descripcion | Sugerencia |
| --- | --- | --- | --- | --- |
| 1 | Media | `AcreditacionExportApoRowResolver::mapGenero` | Brief S3 formaliza `Genero` = `sex` tal cual (`M`/`F`); la implementación mapea a `1`/`2` (comentario SuperVigilancia). Tests afirman `'1'`. Riesgo residual S3 del brief ya contemplaba ajuste post-prueba. | Documentar en docs módulo/usuario que el Excel emite `1`/`2`. Contrastar con archivo APO real en validación local; si SuperVigilancia exige `M`/`F`, corregir en follow-up. |
| 2 | Media | `config/acreditaciones.php` + `AcreditacionDashboardService` | Brief: conteo novedad potencial con política default **`VIGENTE`**. Código usa `dashboard_novedad_policy` = **`VIGENTE_ACTUALIZAR`**. | Alinear config a `VIGENTE` o documentar la desviación como decisión UX (KPI más “permisivo”). |
| 3 | Media | `DatabaseSeeder` | `AcreditacionExportApoSettingSeeder` **no** está registrado en `DatabaseSeeder`. Runtime mitiga con `AcreditacionExportApoSetting::singleton()` (crea defaults al primer uso). | Llamar el seeder desde `DatabaseSeeder` (o checklist deploy `db:seed --class=…`) para que Catálogo/params existan sin esperar primer hit. |
| 4 | Baja | `AcreditacionExportApoRowResolver::escuelaIsValid` | Validación por NIT hace `CursoEscuela::active()->get()` completo por fila; el Dashboard puede resolver hasta `dashboard_novedad_max_scan` candidatos. | Cachear mapa NIT→escuela por request/chunk si el volumen crece. |
| 5 | Baja | `config/acreditaciones.php` `export_apo.filename_prefix` | Clave fija `APO9005767186` no usada; el nombre real se arma con `settings.nit` (correcto según brief). | Eliminar o documentar como legacy; evitar confusión. |
| 6 | Info | Docs | `docs/modules/acreditaciones.md`, `docs/user/acreditaciones.md`, `docs/ACCESS_CONTROL.md`, nota `cursos.md` (CodigoCurso) aún pendientes. | **Documentador** (previsto post-Revisor): Export Apo = edit; Dashboard = view; gate cambió vs placeholder FEAT-036. |
| 7 | Info | `config/access.php` | No se tocó (correcto). | Documentar uso de permisos existentes en ACCESS_CONTROL. |

## Checklist de revision

- [x] Auth y permisos correctos (`AGENTS.md`) — Dashboard `canView`; Export Apo* + PATCH params `canEdit` / FormRequest `authorize`; tab `export_apo` oculta sin edit
- [x] Sin registro publico ni bypass de middleware — rutas en `gestion_humana.php` bajo grupo auth/active
- [x] Validacion de entradas (Form Requests) — preview/generate/update params
- [x] Sin duplicacion innecesaria — services Candidate / RowResolver / Preview / Generate / Dashboard
- [x] Rutas en archivo de area correcto — `routes/areas/gestion_humana.php`; sin tocar `web.php` ni `config/access.php`
- [x] Migraciones aditivas multi-driver (settings + runs; sin `enum()`/`change()`); unique `(export_date, seq)`; sin `migrate:fresh`
- [x] Export `.xls` — clase dedicada `AcreditacionExportApoXlsExport` + `Writer\Xls`; **no** BaseExport / excelHtml5; headers A–X exactos; sin Valida/motivo en archivo
- [x] Selectores — `<x-searchable-select>`; sin Select2
- [x] DataTables `serverSide: true` en candidatos; tope length 100; sin `-1` efectivo
- [x] Universo candidatos `EN_PROCESO`/`POR_VENCER`/`DESACREDITADO` + Ficha activa; multicargo 2 filas; match F\|R; bloqueo duro ficha; modal `include_novedades`; filename `APO{nit}{Ymd}{seq3}.xls`
- [x] Dashboard KPIs + metrics JSON + últimas corridas; sin «Próximamente»
- [x] Audit generate + settings update
- [x] Tests Feature presentes (34 verdes)
- [ ] Docs modules/user alineadas — **pendiente Documentador**

## Seguridad

- Viewer: Dashboard OK; sin tab Export Apo; GET/POST export-apo* y PATCH params → 403 (tests BoardAccess / Candidates / Params / Generate).
- Editor: Export Apo + Catálogo params + Dashboard.
- Bypass `manage.users` vía `AcreditacionesAccessService` (patrón existente).
- Generate: filas con `hard_block` nunca se exportan aunque `include_novedades=true`.
- Candidatos por IDs revalidados contra universo (no exporta IDs fuera de query).
- XSS en celdas DT: `e()` / payload JSON escapado.
- Sin cambios en `config/access.php`; sin registro público.

## Consistencia con AGENTS.md y Brief

| Decisión | Estado |
| --- | --- |
| Sin permiso nuevo / sin `access.php` | OK |
| Export Apo = `acreditaciones.edit`; Dashboard = `view` | OK |
| Tab `export_apo` filtrada en `visibleTabsFor` si `!canEdit` | OK |
| Headers A–X exactos; 1 hoja; `.xls` Writer dedicado | OK |
| Valida/motivo solo preview UI | OK |
| Match F\|R + max `fecha_expedicion`; no mezclar cargos | OK |
| Política vigencia `VIGENTE` \| `VIGENTE_ACTUALIZAR` | OK |
| Soft vs hard; modal Sí no levanta duro | OK |
| Seq diario Bogotá + unique + reintento | OK |
| Parámetros 1 fila + seed valores cerrados | OK (lazy singleton si no se seed-ea) |
| DT server-side; searchable-select; no Select2/excelHtml5 | OK |
| Shared-files: solo `gestion_humana.php` | OK |

## Tests

```
php artisan test --compact \
  tests/Feature/GestionHumana/AcreditacionesExportApoParamsTest.php \
  tests/Feature/GestionHumana/AcreditacionesExportApoCandidatesTest.php \
  tests/Feature/GestionHumana/AcreditacionesExportApoGenerateTest.php \
  tests/Feature/GestionHumana/AcreditacionesDashboardTest.php \
  tests/Feature/GestionHumana/AcreditacionesBoardAccessTest.php
→ Tests: 34 passed (188 assertions)
```

Cobertura CA relevante: permisos view/edit, universo (excluye ACREDITADO fresco + multicargo), match ESCOLTA F\|R, ficha dura, headers/seq/filename, include_novedades Sí/No, smoke Dashboard.

## Siguiente paso

- [x] Pasar a Documentador (aprobado con observaciones)
- [ ] Devolver a Agente Feature (si bloqueado) — N/A

AgentSj: registrar review en run log; lanzar Documentador; observaciones 1–3 pueden ir como follow-up opcional o nota en docs (no bloquean cierre).
