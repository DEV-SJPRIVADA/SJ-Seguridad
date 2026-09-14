# Task Card — FEAT-031 / T1

| Campo | Valor |
| --- | --- |
| Feature | FEAT-031 |
| Tarea | T1 — Permisos, BD, Access, nav, shell tabs |
| Modulo | desvinculaciones |
| shared-files | **Si** — `config/access.php`, `config/audit.php`, NavigationResolver, SidebarVisibilityService, User.php, `routes/areas/gestion_humana.php` |
| Depende de | Brief + plan aprobados por usuario |
| Brief | [`FEAT-031.md`](FEAT-031.md) |

## Objetivo

Dejar el tablero **Desvinculaciones** visible en GH con pestañas Masivos/Seguimientos (vistas placeholder), permisos nuevos, migracion `employee_termination_followups`, modelo, AccessService y audit wrapper.

## Incluye

- Keys en `config/access.php` (board, tabs, system_permissions, admin groups) + sync catalogo.
- `config/audit.php` modulo `desvinculaciones`.
- Migracion + modelo `EmployeeTerminationFollowup` + relaciones.
- `DesvinculacionesAccessService`, `DesvinculacionesAuditLogService`.
- Nav sidebar + `User::defaultDesvinculacionesBoardUrl()`.
- Rutas index/masivos/seguimientos (GET) + controladores stub + vistas shell con subnav.
- Tests basicos de permiso board / 403 sin view.

## No incluye

- Process lote, ZIP, lookup real, autosave, hooks Ficha (T2/T3).

## Criterios de aceptacion (T1)

1. Sidebar muestra Desvinculaciones con permiso board.
2. Tabs Masivos/Seguimientos navegan.
3. Migracion aplica con `php artisan migrate` (sin fresh).
4. Tests permiso basicos verdes.

## Validacion

- `php artisan migrate`
- `php artisan test --compact --filter=Desvinculaciones`
- `vendor/bin/pint --dirty --format agent`
