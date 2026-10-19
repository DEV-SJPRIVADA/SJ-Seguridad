# Task Card — FEAT-047 / T2

> Emitida por el AgentSj al Agente Feature.

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-047 |
| Tarea # | T2 — Catálogo Proveedores (CRUD + permisos + pestaña + DT) |
| Modulo / area | purchase-requests / Compras |
| Brief | `docs/briefs/FEAT-047.md` (Fase B sin import VINE01) |
| Plan | `docs/briefs/FEAT-047-plan.md` |

## Objetivo de esta tarea

Entregar el catálogo **Proveedores** operable: migración `purchase_providers`, permisos `purchase.providers.view` / `.edit` (edit ⇒ view), `PurchaseAccessService`, pestaña en subnav Compras, listado DataTables **server-side**, CRUD alta/edición con `dias_pago` y campos opcionales FO-AD-07, export Excel del catálogo. **Sin** import VINE01 (eso es T3).

## Archivos permitidos (scope lock) — shared-files autorizados

- `database/migrations/*purchase_providers*`
- `app/Models/PurchaseProvider.php` (o nombre del brief)
- `app/Services/Access/PurchaseAccessService.php` (+ tests acceso)
- `config/access.php` (**autorizado**)
- Seeder / sync permisos si el proyecto lo usa para nuevas keys
- `routes/modules/purchase-requests.php` (**autorizado**)
- Controllers / Form Requests / DatatableService bajo PurchaseRequests/Providers
- Vistas `resources/views/modules/purchase-requests/providers/` + partial subnav Compras
- CSS/JS Vite solo si hace falta para DT (preferir patrones existentes)
- Export catálogo (`BaseExport` + `<x-export-excel>`)
- Tests Feature view vs edit, uppercase nombre
- `docs/TASKS.md`, `docs/runs/FEAT-047-run-log.md`, nota en `docs/modules/purchase-requests.md` o stub `purchase-providers.md`

## Archivos prohibidos

- Import VINE01 / comando Artisan import (T3)
- `purchase_orders` / FO-AD-07 / evaluación (T4–T5)
- `routes/web.php` salvo require ya existente
- Otros módulos

## UI (obligatorio calidad)

- Clonar shell de listado GH referencia (Seguimientos / Cursos registros): `page-section`, `req-manage-*`, searchable-select, icon-btn canónicos, thead corporativo
- Subnav: `.module-tab` pestaña Proveedores
- Comentarios de bloques funcionales + ortografía UI
- Tras UI: tests + smoke Chrome DevTools MCP si app local disponible; si no, documentar blocker de smoke

## Entregables

- [ ] Migración multi-driver + modelo
- [ ] Permisos + AccessService + pestaña visible solo con view/edit
- [ ] Index DT server-side + filtros (default proveedor activo)
- [ ] Create/Edit con dias_pago + opcionales
- [ ] Export Excel
- [ ] Tests Feature acceso + uppercase
- [ ] Pint + actualizar TASKS/run log

## Criterios de done

1. Solo `providers.view` puede listar/exportar; sin `edit` → 403 en store/update.
2. `edit` implica poder ver pestaña (AccessService).
3. Listado server-side; default activos + es_proveedor.
4. Textos libres persistidos en MAYÚSCULAS (excepto email).
5. UI no es prototipo genérico (chrome módulo).

## Al cerrar

Reportar: archivos, tests, smoke UI, pendientes T3, blockers.
