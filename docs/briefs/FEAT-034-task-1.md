# Task Card — FEAT-034 / T1

| Campo | Valor |
| --- | --- |
| Feature | FEAT-034 |
| Task | T1 |
| Modulo | cursos |
| shared-files | `routes/areas/gestion_humana.php` (sí). **No** `config/access.php`. **No** hooks Ficha (T2). |

## Objetivo

Cola «Nuevos sin curso» usable en Registros: migración vacía, servicio, resolve/omit, UI icono+contador, tests. Sin encolar aún desde Ficha (T2).

## Brief

Leer completo: `docs/briefs/FEAT-034.md` + plan `docs/briefs/FEAT-034-plan.md`.

## Reglas críticas

- Tabla `employee_curso_pending` **sin backfill**.
- Contador/listado: `status=pending` + perfil ficha `activo`.
- Resolve al **insert** de curso (store + import cursos inserts).
- Omitir: motivo opcional; sin deshacer.
- Solo `cursos.edit` ve/opera la cola.
- **Nunca** actualizar `employee_ficha_profiles` al guardar curso.
- Modal: cédula/nombre **solo lectura** desde ficha (opción A).
- UI: solo icono + número; tooltip «Nuevos sin curso»; query `cola=nuevos-sin-curso`.
- Solo `php artisan migrate` (nunca fresh).
- Pint + tests relevantes.

## Scope lock (permitido)

- `database/migrations/*employee_curso_pending*`
- `app/Models/EmployeeCursoPending.php` + factory
- `app/Services/GestionHumana/EmployeeCursoPendingService.php`
- `app/Http/Controllers/GestionHumana/CursosController.php`
- `app/Http/Requests/GestionHumana/Cursos/*Pending*` / `OmitEmployeeCursoPendingRequest`
- `app/Services/GestionHumana/EmployeeCursoImportService.php` (solo resolve en insert)
- `app/Services/GestionHumana/CursosAuditLogService.php` (omit/resolve si aplica)
- `routes/areas/gestion_humana.php`
- `resources/views/areas/gestion_humana/cursos/**`
- `resources/css/app.css` (solo clases cola mínimas)
- `database/factories/EmployeeCursoPendingFactory.php`
- `tests/Feature/**` cursos / pending

## Prohibido T1

- `FichaEmpleadosController`, `EmployeeFichaImportService` (T2)
- `config/access.php`
- Backfill / seeder inventario
- Icono en Dashboard/Catálogo
- Export Excel de cola

## Done cuando

- Migrate OK; cola vacía post-migrate
- UI + omit + store resolve + import insert resolve
- Cédula/nombre readonly en modal nuevo
- Tests T1 en verde; reportar archivos + tests al AgentSj
- Actualizar `docs/TASKS.md` fase a «T1 OK — listo T2» (AgentSj puede hacerlo al recibir done)
