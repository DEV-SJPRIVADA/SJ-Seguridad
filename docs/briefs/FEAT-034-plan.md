# Plan de orquestacion — FEAT-034

> Generado por el AgentSj tras el Feature Brief. Guardar como `docs/briefs/FEAT-034-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-034 |
| Modo | orquestado |
| Rama Git | feat/FEAT-034-cursos-nuevos-sin-curso (opcional) |
| Modulo principal | cursos (+ hooks ficha_empleados) |
| Brief | [`docs/briefs/FEAT-034.md`](FEAT-034.md) |
| Run log | `docs/runs/FEAT-034-run-log.md` |
| shared-files | **parcial**: `routes/areas/gestion_humana.php`, `FichaEmpleadosController`, `EmployeeFichaImportService` — **no** `config/access.php` |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1:** … | 2 | OK |
| 4 | Feature | **T2:** Hooks enqueue en `FichaEmpleadosController::store` + `EmployeeFichaImportService` (solo transicion a ficha) + tests Ficha | 3 | En curso |
| 5 | Revisor | Review del diff completo FEAT-034 | 4 | Pendiente |
| 6 | Documentador | `docs/modules/cursos.md` + `docs/user/cursos.md` (+ nota ficha si aplica) | 5 | Pendiente |
| 7 | AgentSj | Checklist cierre → Completadas | 6 | Pendiente |

## Task Cards (scope lock)

### T1 — Cursos + BD + UI cola

**Objetivo:** Cola usable en Registros con resolve/omit; tabla vacía al migrate.

**Archivos permitidos (orientativo):**

- `database/migrations/*employee_curso_pending*`
- `app/Models/EmployeeCursoPending.php` (+ factory)
- `app/Services/GestionHumana/EmployeeCursoPendingService.php`
- `app/Http/Controllers/GestionHumana/CursosController.php`
- `app/Http/Requests/GestionHumana/Cursos/OmitEmployeeCursoPendingRequest.php` (o similar)
- `app/Services/GestionHumana/EmployeeCursoImportService.php`
- `app/Services/GestionHumana/CursosAuditLogService.php` (si aplica)
- `routes/areas/gestion_humana.php`
- `resources/views/areas/gestion_humana/cursos/registros.blade.php` (+ partials)
- `resources/css/app.css` (solo clases cola si hace falta)
- `tests/Feature/**` relacionados a cursos/cola

**Prohibido T1:** hooks Ficha (van en T2); `config/access.php`; backfill/seeder de inventario.

**CA T1:** migrate vacío; UI icono solo `cursos.edit`; omit + store resolve; import cursos insert resolve; tests verdes.

### T2 — Hooks Ficha (shared-files parcial)

**Objetivo:** Encolar solo al ingresar a ficha post-deploy.

**Archivos permitidos:**

- `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` (`store`)
- `app/Services/GestionHumana/EmployeeFichaImportService.php`
- Tests Feature Ficha store/import enqueue
- Puede tocar solo el servicio de pending (llamadas), no rediseñar UI cursos

**CA T2:** Gestionar Empleado / alta / import ficha → enqueue si eligible; reingreso con cursos no encola; update perfil existente no encola; tests.

## Paralelismo

Ninguno: T2 depende de servicio/modelo de T1. Shared-files parcial → **secuencial**.

## Puntos de pausa usuario

- Post-Analista: OK (respondido)
- **Post-Brief: confirmacion de alcance** ← **ahora**
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | T1 | Solo T1 |
| `FichaEmpleadosController` / `EmployeeFichaImportService` | T2 | Solo T2 tras T1 |
| `config/access.php` | — | No tocar |

## Proteccion de datos

- Solo `php artisan migrate` incremental.
- **Prohibido** `migrate:fresh` / wipe / backfill de activos actuales sin curso.
