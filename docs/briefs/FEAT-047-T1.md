# Task Card — FEAT-047 / T1

> Emitida por el AgentSj al Agente Feature. Una tarjeta = un alcance acotado.

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-047 |
| Tarea # | T1 — Fase A FO-AD-44 |
| Modulo / area | purchase-requests / supplies (export FO-AD-44) |
| Brief | `docs/briefs/FEAT-047.md` |
| Plan | `docs/briefs/FEAT-047-plan.md` |

## Objetivo de esta tarea

Alinear FO-AD-44 (PDF y Excel) de **solicitudes de compra** y **suministros** al formato oficial **Marzo 2026 Versión 01**: encabezado (código FO-AD-44, versión, página), columnas Cantidad | Foto | Descripción | Referencia | Utilización | Ubicación, y **embeber foto** del ítem si existe en disco; si no, celda vacía o “S/A” (no dejar `N/A` fijo sin intentar embeber).

## Archivos permitidos (scope lock)

- `app/Services/PurchaseRequests/PurchaseRequestExcelExporter.php`
- `app/Services/PurchaseRequests/PurchaseRequestPdfService.php` (+ Blade PDF relacionado bajo `resources/views/modules/purchase-requests/`)
- Exporters suministro FO-AD-44 equivalentes (`SupplyPurchaseReportExporter`, `SupplyPurchasePdfExporter`, vistas Blade PDF)
- `config/purchase-requests.php` / `config/supplies.php` (metadatos form_version, título, mes/año si aplica)
- Tests Feature de export FO-AD-44 existentes o nuevos bajo `tests/Feature/`
- `docs/modules/purchase-requests.md` (nota breve de alineación; Documentador hará doc completa al final)
- `docs/TASKS.md` / `docs/runs/FEAT-047-run-log.md` (fase)

## Archivos prohibidos

- `config/access.php`
- `routes/web.php` / nuevas rutas de Proveedores u OC
- Migraciones de `purchase_providers` / `purchase_orders`
- UI Proveedores, import VINE01, FO-AD-07, evaluación

## Entregables

- [ ] Excel + PDF compra alineados Marzo 2026 v01
- [ ] Excel + PDF suministro alineados (misma familia)
- [ ] Foto embebida cuando el archivo exista
- [ ] Tests Feature relevantes verdes
- [ ] Pint dirty si tocó PHP
- [ ] Actualizar fase TASKS + fila run log

## Criterios de done

1. Export FO-AD-44 muestra metadatos oficiales (código / versión Marzo 2026 v01 / página).
2. Columnas correctas con ortografía UI (Descripción, Utilización, Ubicación).
3. Si hay imagen de ítem en storage/public, se embebe; si no, vacío o S/A.
4. Tests de export pasan; sin regresiones evidentes en rutas de descarga existentes.

## Al cerrar

Reportar al AgentSj: archivos modificados, tests, pendientes T2, blockers.
