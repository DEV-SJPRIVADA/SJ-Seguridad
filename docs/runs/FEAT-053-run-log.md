# Run log — FEAT-053

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-053 |
| Titulo | Tablero MT-GI-11 Matrices (Calidad) — Dashboard + Matriz + Catalogos |
| Modo | orquestado |
| Triaje | C — Full (tablero nuevo, 5 permisos nuevos, 6 migraciones, 3 cortes de negocio) |
| Modulo | `calidad` — tablero `mt_gi_11` |
| Brief | `docs/briefs/FEAT-053.md` |
| Plan | `docs/briefs/FEAT-053-plan.md` |
| Inicio | 2026-11-20 |

## Decisiones tomadas (usuario)

Ver tabla «Decisiones de negocio (ley)» del brief. Resumen: tablero independiente `mt_gi_11`; permisos `view`/`edit` por pestana + `view_all` + `parameters.edit`; consecutivo **no unico** y llave = `id`; boton «Nueva version»; justificacion `text` max 2000; 5 catalogos administrables; 4 estados con color; adjuntos multiples con preview; import sin validar duplicados; mayusculas en todo texto libre.

## Registro por paso

| # | Fecha | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- |
| 1 | 2026-11-20 | AgentSj | Triaje C; FEAT-053 + brief + plan + run log; FEAT-052 retroactivo | `docs/TASKS.md`, `docs/briefs/FEAT-053*.md`, `docs/runs/FEAT-05{2,3}-run-log.md` | OK |
| 2 | 2026-11-20 | AgentSj | Analista | — | Skip (Q&A cerrado en chat: 10 decisiones) |
| 3 | 2026-11-20 | AgentSj | Arquitecto | — | Skip (brief + plan en el maestro) |
| 4 | 2026-11-20 | Feature | T1: permisos, tablero, migraciones, modelos, acceso | `config/access.php`, `config/mt_gi_11.php`, 2 migraciones, 7 modelos, `MtGi11AccessService`, `PermissionCatalog`, seeder, nav, `User`, `HasMtGi11Tabs`, rutas | OK |
| 5 | 2026-11-20 | Feature | T2: Matriz CRUD + adjuntos + nueva version | `MtGi11Controller`, 6 Form Requests, `MtGi11Service`, `MtGi11AdjuntoService`, `MtGi11AuditLogService`, views `matriz`/`form`, tests | OK |
| 6 | 2026-11-20 | Feature | T3: Dashboard + Catalogos + Masivos + Export | `MtGi11DashboardService`, `MtGi11CatalogService`, `MtGi11ImportService`, `MtGi11Export`, plantilla, views, JS charts, CSS 123 | OK |
| 7 | 2026-11-20 | Revisor | Checklist maestro: acceso por permiso, mayusculas, alcance propio/view_all, cascada CSS, auditoria | 47 tests MtGi11 | OK |
| 8 | 2026-11-20 | Documentador | Docs modulo + usuario + ACCESS + INDEX | `docs/modules/matrices-mt-gi-11.md`, `docs/user/matrices-mt-gi-11.md` | OK |

## Verificación

- `php artisan migrate --force`: 2 migraciones nuevas aplicadas (catalogos + solicitudes/adjuntos).
- `php artisan test --compact --filter=MtGi11`: **47 pruebas / 155 aserciones** en verde.
- Regresión `tests/Feature/Calidad/` + `tests/Feature/Matrices/`: **137 pruebas / 830 aserciones** en verde.
- `vendor/bin/pint --dirty --format agent`: OK.
- `npm run build`: OK (incluye `mt-gi-11-dashboard-charts.js` y CSS 123).

## Notas de implementación

- El tablero se llama **MT-GI-11** (`key = mt_gi_11`) para no colisionar con el módulo genérico «Matrices» (FEAT-052), que ya existía.
- `PermissionCatalog` mantiene su exclusión de `view.board.*.matrices`; la clave nueva es `mt_gi_11` y no se ve afectada.
- `color` del catálogo de estados se excluye de la normalización a mayúsculas por ser clave técnica de estilo.
- El import detecta si la fila 2 del Excel es la fila de etiquetas de la plantilla y, si no, importa desde la fila 2.
- El export usa `BaseExport::downloadFromQuery`; el mapeo por etiqueta evita depender de `data_get` sobre el modelo.

## Corrección posterior (2026-11-20)

**Error reportado en runtime (MySQL):** `SQLSTATE[42000]: 1055 ... only_full_group_by` en `/calidad/mt-gi-11/dashboard`.

- **Causa:** `MtGi11DashboardService::metrics()` reutilizaba `MtGi11DatatableService::filteredQuery()`, que trae `select *` + el subquery de `withCount('adjuntos')`. Al agregar `group by estado_id`, MySQL rechaza columnas no agregadas. SQLite no aplica esa restricción, por eso los tests no lo detectaron.
- **Corrección:** se añadió `aggregateQuery()` (query limpia: `visibleTo` + filtros, sin eager loads ni `withCount`) y `toBase()` en las agregaciones. Aplica a `byEstado` y a `countsByRelation`.
- **Blindaje:** test `test_dashboard_aggregate_sql_has_no_select_star` inspecciona el SQL generado y falla si reaparece `select *` o `adjuntos_count`.
- **Verificación en MySQL real:** dashboard con y sin datos devuelve KPIs y series correctos.
