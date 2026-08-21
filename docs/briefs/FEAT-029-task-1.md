# Task Card — FEAT-029 / Tarea 1

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Tarea # | 1 |
| Modulo / area | Gestion humana — Plantillas Word (shared-files) |
| Brief | `docs/briefs/FEAT-029.md` |
| Plan | `docs/briefs/FEAT-029-plan.md` |

## Objetivo de esta tarea

Registrar permisos y navegacion del tablero **Plantillas Word** sin UI CRUD aun (stub de ruta/controller minimo aceptable para que el sidebar enlace sin 404).

## Archivos permitidos (scope lock)

- `config/access.php`
- `config/employee_ficha.php` (deprecar packs/supported_causes; agregar code tipo desvinculacion)
- `app/Support/PermissionCatalog.php`
- `app/Services/Navigation/NavigationResolver.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- `app/Models/User.php` (helper URL board)
- `app/Services/GestionHumana/PlantillasWordAccessService.php` (nuevo)
- `app/Http/Controllers/GestionHumana/PlantillasWordController.php` (stub index opcional)
- `resources/views/areas/gestion_humana/plantillas-word/index.blade.php` (placeholder minimo)
- `routes/areas/gestion_humana.php` (solo ruta GET index del tablero)
- Tests de acceso/permiso board si aplica en esta tarea
- `docs/TASKS.md` (fase)

## Archivos prohibidos

- Migraciones de tablas nuevas (eso es T2)
- CRUD tipos/plantillas completo (T3)
- Modal generar / servicios generate (T4)
- Docs de modulo (Documentador)

## Entregables

- [ ] Permisos + board + admin_ui en access.php
- [ ] PlantillasWordAccessService + NavigationResolver + User helper
- [ ] PermissionCatalog reject fuera GH
- [ ] Seeder administrador con board+view+manage
- [ ] Ruta index + vista placeholder
- [ ] Config employee_ficha actualizada segun brief
- [ ] Tests basicos de permiso/board si el proyecto tiene patron similar (Archivo)

## Criterios de done

1. Sidebar puede mostrar “Plantillas Word” con el permiso board.
2. `plantillas_word.view` / `manage` existen en catalogo.
3. GET index responde 200 con manage/view; 403 sin permiso.
4. No se implementa CRUD ni migraciones de tipos.

## Al cerrar

Reportar archivos modificados, pendientes T2, blockers.
