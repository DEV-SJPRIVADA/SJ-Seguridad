# Task Card — FEAT-042 / T1

> Emitida por AgentSj. Usuario: `OK implementa` (2026-10-05). Seed SOLICITUD vacío.

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Tarea # | T1 |
| Modulo / area | gestion_humana / `cliente_interno` |
| Brief | `docs/briefs/FEAT-042.md` |
| shared-files | **sí** (autorizado) |

## Objetivo de esta tarea

Shell del tablero **Cliente interno** en GH: permisos, AccessService, migraciones (3 tablas) + seed ESTADO, modelos/factories, rutas shell + placeholders Dashboard/Solicitudes/Catálogos, subnav, nav sidebar, audit entry, tests de acceso.

**No** implementar CRUD catálogos, DT solicitudes, masivo ni KPIs reales (T2–T5).

## Archivos permitidos (scope lock)

- `config/access.php`
- `config/audit.php` (si el patrón Formación registra módulo)
- `config/cliente_interno.php` (si brief lo pide en T1; si no, deferir)
- `routes/areas/gestion_humana.php`
- `app/Services/Access/ClienteInternoAccessService.php`
- `app/Traits/HasClienteInternoTabs.php` (o equivalente Formación)
- `app/Http/Controllers/GestionHumana/ClienteInternoController.php`
- `app/Models/ClienteInternoEstado.php`
- `app/Models/ClienteInternoTipoSolicitud.php`
- `app/Models/ClienteInternoSolicitud.php`
- `app/Services/ClienteInterno/ClienteInternoAuditLogService.php` (o GestionHumana namespace)
- `database/migrations/*cliente_interno*`
- `database/factories/ClienteInterno*.php`
- Nav: `NavigationResolver`, `SidebarVisibilityService`, `User` **solo** lo necesario para board `cliente_interno` (espejo Formación)
- Vistas placeholder: `resources/views/areas/gestion_humana/cliente_interno/{dashboard,solicitudes,catalogos}.blade.php` + partials subnav
- `tests/Feature/GestionHumana/ClienteInternoBoardAccessTest.php`
- `docs/TASKS.md` (fase T1 OK)
- `docs/runs/FEAT-042-run-log.md` (si AgentSj lo pide; preferir reportar y dejar a AgentSj)

## Archivos prohibidos

- CRUD catálogos / Datatable / Import / Export / Dashboard metrics JS (T2–T5)
- `routes/web.php` (salvo descubrimiento de que falta require — reportar blocker)
- Docs de módulo usuario/técnica final (Documentador)
- `migrate:fresh` / wipe / TRUNCATE

## Entregables

- [ ] Permisos + board + tabs en `access.php` + Admin UI group
- [ ] `ClienteInternoAccessService` (edit⇒view; dashboard = view∨parameters; bypass manage.users)
- [ ] Migraciones multi-driver 3 tablas + seed upsert ESTADO (4 valores); SOLICITUD 0 filas
- [ ] Modelos + factories básicas
- [ ] Rutas shell: index redirect, dashboard/solicitudes/catalogos placeholders
- [ ] Sidebar muestra **Cliente interno** con board permission
- [ ] Tests Feature acceso (403 sin permiso; tabs visibles según permisos)
- [ ] `php artisan migrate` (incremental) + `php artisan test` del archivo de acceso
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] Actualizar `docs/TASKS.md` fase T1 OK

## Criterios de done

1. Con board + view, sidebar y pestañas Dashboard+Solicitudes visibles; placeholders 200.
2. Con solo parameters.edit + board: Dashboard+Catálogos; Solicitudes oculta.
3. Seed ESTADO presente tras migrate; tipos solicitud tabla vacía.
4. Tests acceso verdes.

## Al cerrar

Reportar al AgentSj: archivos modificados, tests, pendientes T2, blockers.
