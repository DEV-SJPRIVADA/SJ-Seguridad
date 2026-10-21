# Plan de orquestacion — FEAT-048

> Generado por el AgentSj tras el Feature Brief final. Plantilla: [`ORCHESTRATION_PLAN.md`](../templates/ORCHESTRATION_PLAN.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-048 |
| Modo | orquestado |
| Rama Git | — (sin rama dedicada; el usuario no pidió rama) |
| Modulo principal | purchase-requests + supplies |
| Brief | [`docs/briefs/FEAT-048.md`](FEAT-048.md) |
| Run log | [`docs/runs/FEAT-048-run-log.md`](../runs/FEAT-048-run-log.md) |
| shared-files | Si. Un agente a la vez. |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Este plan | 2 | OK |
| 4 | Feature | **T1** Comodato/Activo + snapshot OC + PDF/Excel FO-AD-07 | 3 | OK |
| 5 | Feature | **T2** Máquina de estados, reenvío suministro, Cerrar saldo, hilo | 4 | OK |
| 6 | Feature | **T3** Precio único en la OC | 5 | OK |
| 7 | Feature | **T4** Menú Pedidos / Órdenes / Catálogos y folios SC/SU | 6 | OK |
| 8 | Feature | **T5** Fecha de recibido + evaluación (no impresa) | 7 | OK |
| 9 | Revisor | Review del diff de FEAT-048 | 8 | OK — Aprobado con observaciones |
| 10 | Documentador | docs/modules + docs/user | 9 | OK |
| 11 | AgentSj | Checklist cierre | 10 | OK — smoke UI pendiente (app local apagada) |

## Paralelismo

Ninguno. Mismo módulo y shared-files. T3 depende de que T2 quite el cierre manual. T4 depende de las vistas de estado de T2. T5 depende de la máquina de T2.

## Puntos de pausa usuario

- Post-Analista: cerrado (sin preguntas).
- Post-Brief: el usuario ya pidió implementar el plan aprobado. Sin pausa adicional.
- Post-Revisor: blockers críticos.

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/purchase-orders.php` | T1 | Solo T1 |
| `routes/modules/purchase-requests.php` | T2, T5 | Serializar |
| `routes/modules/supplies.php` | T2 | Solo T2 |
| `config/access.php` | T4 | Solo etiquetas de tableros ya existentes. Cero claves nuevas |
| Nav (`NavigationResolver`, `SidebarVisibilityService`, access services, `User`) | T4 | Solo T4 |
| FEAT-047 (mismo módulo, aún en TASKS) | T1–T5 | No rehacer proveedores, VINE01, saldo ni `purchase_order_evaluations`. T5 absorbe la UI de evaluación que FEAT-047 dejó pendiente |

## Task cards

### T1 — Formulario + FO-AD-07

- **Objetivo:** Comodato y Activo en Datos del cliente; snapshot en la OC; PDF y Excel con el formato oficial y las condiciones, en una hoja carta.
- **Archivos:** migraciones aditivas, modelos, Form Requests, create/edit/show, `PurchaseOrderService`, `config/purchase-orders.php`, `pdf/purchase-order.blade.php`, exportadores PDF y Excel, tests.
- **Hecho cuando:** una compra de cliente imprime Sí/No; interna, suministro y OC anterior imprimen esas celdas vacías; 1 línea y unas 12 siguen en una carta; el Excel trae el texto legal tal cual.
- **shared-files:** `config/purchase-orders.php`.

### T2 — Estados automáticos

- **Objetivo:** `PurchaseFlowStateService`. Sin selector manual. Abrir Procesar no cambia el estado. Reenvío de suministro rechazado. Cerrar saldo con motivo. Hilo de suministro.
- **Hecho cuando:** emitir una OC parcial deja Con orden; saldo cero deja Cerrado; Cerrar saldo exige motivo; el dashboard no cuenta cerrados como bandeja.
- **shared-files:** rutas de purchase-requests y supplies.

### T3 — Un solo precio

- **Objetivo:** procesar un suministro ya no pide `unit_cost`. Si había costo, la OC lo precarga. No se borran costos históricos.
- **shared-files:** no.

### T4 — Menú y folios

- **Objetivo:** etiquetas Pedidos, Órdenes y Catálogos. Folio visible `SC-` / `SU-`. Bandeja distinta del historial. Sin pestaña Insumos aprobados. Sin claves nuevas.
- **shared-files:** `config/access.php` (solo etiquetas), navegación y access services.

### T5 — Recepción y evaluación

- **Objetivo:** fecha de recibido y evaluación 25/25/50 con `purchase.providers.edit`. No se imprime. Cerrado exige todas las fechas y saldo 0. Cerrar saldo sigue cerrando sin fechas.
- **shared-files:** `routes/modules/purchase-requests.php`.
