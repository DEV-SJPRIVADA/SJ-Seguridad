# Plan — FEAT-050 Planeación estratégica fase 1

Triaje **C**. Analista: Skip (decisiones numeradas cerradas). Arquitecto: brief en el maestro.

## Cortes

| Corte | Contenido | Quién |
| --- | --- | --- |
| T1 | Permisos, nav, rutas, migraciones, modelos, AccessService, constructor (campos + fórmula), listado, publicar/versionar | Feature Task (`shared-files`) |
| T2 | Compartir usuarios + auto-permisos, captura dinámica, reemplazo con modal, plan de mejora | Feature Task |
| T3 | Consulta ficha FT-OP-01 (historial, gráfico, análisis), consolidado suma + filtro capturador, dashboard | Feature Task |
| Revisor | Checklist access/auth/migraciones | Task Revisor |
| Documentador | `docs/modules/planeacion-estrategica.md` + user + ACCESS + INDEX | Task Documentador |

## Shared-files

Tocar en **serie** (T1 primero):

- `config/access.php`
- `config/audit.php`
- `app/Support/PermissionCatalog.php`
- `app/Services/Navigation/NavigationResolver.php`
- `app/Services/Navigation/SidebarVisibilityService.php`
- `app/Models/User.php`
- `routes/areas/calidad.php` (no lógica nueva en `web.php`; el require ya existe)
- `database/seeders/RoleAndPermissionSeeder.php`
- `vite.config.js` (T3 charts)
- `resources/css/app.css` (solo `@import` al final)

## Verificación

- `php artisan test --compact` de `tests/Feature/Calidad/Planeacion*` y `tests/Unit/Calidad/Planeacion*`
- `vendor/bin/pint --dirty --format agent`
- `npm run build` si hay CSS/JS Vite
- Smoke visual: usuario
