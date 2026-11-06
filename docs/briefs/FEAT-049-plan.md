# Plan — FEAT-049 Servicio al cliente

Triaje **C**. Analista: Skip (respuestas numeradas cerradas). Arquitecto: brief en el maestro.

## Cortes

| Corte | Contenido | Quién |
| --- | --- | --- |
| T1 | Permisos, nav, rutas, migraciones, modelos, días hábiles, catálogos, acceso | Maestro (shared-files) |
| T2 | FO-GI-22 CRUD, DataTable, filtros, observaciones, adjuntos, borrado con log | Maestro |
| T3 | Dashboard KPI + Apex y export Excel de dos hojas | Maestro |

Un solo implementador porque `config/access.php`, navegación y `routes/web.php` se tocan en todos los cortes.

## Shared-files

- `config/access.php`
- `config/audit.php`
- `app/Support/PermissionCatalog.php`
- `app/Services/Navigation/NavigationResolver.php`
- `app/Services/Navigation/SidebarVisibilityService.php`
- `app/Models/User.php`
- `routes/web.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- `vite.config.js`
- `resources/css/app.css` (solo el bloque del módulo)
- `docs/ACCESS_CONTROL.md`, `docs/INDEX.md`

## Verificación

- `php artisan test --compact` de los tests del módulo.
- `vendor/bin/pint --dirty --format agent`
- `npm run build` (entrada Vite del dashboard).
- Smoke visual: usuario.
