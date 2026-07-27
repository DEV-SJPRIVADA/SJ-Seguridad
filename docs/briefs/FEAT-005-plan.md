# Plan de orquestacion — FEAT-005

## Tareas

| # | Task Card | Agente | Dependencia |
| --- | --- | --- | --- |
| 1 | Vertical slice campo `service_structure` | Feature | Brief FEAT-005 |
| 2 | Review defect-first | Revisor | Tarea 1 |
| 3 | Docs modules + user | Documentador | Tarea 2 OK |

## Task Card 1 — Feature

**Scope lock**

- Migracion: `service_structure` text nullable en `personal_requisitions`
- Modelo `PersonalRequisition` fillable
- `StorePersonalRequisitionRequest` / `UpdatePersonalRequisitionRequest`: `service_structure` required string
- `RequisitionController::store` y `update`: persistir campo
- Export: columna en `exportExcel` y `trackingExport`
- `PersonalRequisitionChangeLogger`: label **Estructura del servicio**
- Vistas: `form-fields-requester.blade.php` y `form-fields.blade.php` — textarea dentro seccion 4, despues de perfil/dotacion; label + help text
- Tests en `RequisitionModuleTest` (create required + update + see in form)
- **No** tocar print, emails, `config/access.php`, `routes/web.php`

**Done cuando:** tests filtro requisitions pasan y campo aparece en Solicitar y Gestion.
