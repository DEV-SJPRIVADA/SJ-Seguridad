# Task Card — FEAT-034 / T2

| Campo | Valor |
| --- | --- |
| Feature | FEAT-034 |
| Task | T2 |
| Modulo | ficha_empleados (hooks) + uso de `EmployeeCursoPendingService` |
| shared-files | **parcial**: `FichaEmpleadosController`, `EmployeeFichaImportService` — **no** `config/access.php` |

## Objetivo

Encolar en «Nuevos sin curso» **solo** cuando una persona **entra a ficha** post-deploy (no en Ficha Pendientes de requisición).

## Brief

`docs/briefs/FEAT-034.md` — secciones Encolado + Decisiones técnicas.

## Cuándo llamar `enqueueIfEligible`

1. `FichaEmpleadosController::store`
   - Con `ficha_entry_id` (Gestionar Empleado → Crear): tras setear `moved_to_ficha_at` y guardar perfil.
   - Sin `ficha_entry_id` (alta manual): tras crear entry+perfil en ficha.
2. `EmployeeFichaImportService`: solo cuando **transiciona** a ficha (`moved_to_ficha_at` era null → now) o alta nueva en ficha. **No** en cada update de perfil ya en ficha.

## Payload típico

```php
$pendingService->enqueueIfEligible([
    'document_number' => ...,
    'full_name' => ...,
    'employee_ficha_profile_id' => ...,
    'personal_requisition_ficha_entry_id' => ...,
    'enqueued_by' => $userId,
]);
```

El servicio ya aplica: no historial cursos, no omitted/resolved, idempotente pending, solo activo.

## Scope lock (permitido)

- `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php`
- `app/Services/GestionHumana/EmployeeFichaImportService.php`
- `tests/Feature/**` Ficha store/import enqueue (archivo nuevo o ampliar existentes)
- Inyección/uso de `EmployeeCursoPendingService` (no rediseñar UI cursos)

## Prohibido

- Backfill / inventariar activos actuales
- Encolar al solo crear fila en Ficha Pendientes (sin `moved_to_ficha_at`)
- `config/access.php`, vistas cursos (salvo bug bloqueante)
- `migrate:fresh`

## Tests mínimos

1. Gestionar Empleado / store con `ficha_entry_id` → crea `employee_curso_pending` pending.
2. Alta manual store → encola.
3. Cédula con `employee_cursos` previo → no encola (reingreso).
4. Import ficha: update de ya en ficha → no encola; primera vez a ficha → encola.
5. Pint + `php artisan test --compact` de los tests tocados.

## Done

Reportar archivos, tests, blockers. Actualizar TASKS a «T2 OK — listo Revisor».
