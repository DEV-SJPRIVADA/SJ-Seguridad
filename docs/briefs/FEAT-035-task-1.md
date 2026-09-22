# Task Card — FEAT-035 / T1

| Campo | Valor |
| --- | --- |
| Feature | FEAT-035 |
| Tarea | T1 — Permisos, Access, audit config, nav, shell pestañas |
| Modulo | seleccion |
| shared-files | **Si** — `config/access.php`, `config/audit.php`, NavigationResolver / SidebarVisibilityService / User (default URL si patrón Cursos), `routes/areas/gestion_humana.php` |
| Depende de | Brief + plan confirmados por usuario («confirmo») |
| Brief | [`FEAT-035.md`](FEAT-035.md) |
| Plan | [`FEAT-035-plan.md`](FEAT-035-plan.md) |

## Objetivo

Dejar el tablero **Selección** visible en Gestión humana con pestañas **Dashboard** \| **Ingreso** \| **Examen ocupacional** \| **Catálogos** (Catálogos solo con `seleccion.edit`), permisos, Access/Audit stub y vistas shell (placeholders). **Sin** migraciones de negocio Ingreso/Examen ni CRUD catálogos (eso es T2+).

## Incluye

- Keys en `config/access.php` (system_permissions, boards, board_canonical_areas, `seleccion_tabs`, admin_permission_groups) + sync permisos (`app:sync-permissions` / PermissionCatalog).
- `config/audit.php` módulo `seleccion` → area `gestion_humana`.
- `config/seleccion.php` stub mínimo (tabs labels / managed_catalog_types placeholder OK).
- `SeleccionAccessService` (canViewBoard / canView / canEdit + bypass `manage.users`).
- `SeleccionAuditLogService` wrapper (puede quedar listo sin llamadas aún).
- Trait `HasSeleccionTabs` o equivalente + partial subnav.
- Nav sidebar GH + `User::defaultSeleccionBoardUrl()` si el patrón Cursos lo exige.
- Rutas shell en `routes/areas/gestion_humana.php`:
  - `index` → redirect dashboard
  - `dashboard` (vista placeholder KPI)
  - `ingresos` (vista placeholder listado)
  - `examenes` (vista placeholder)
  - `catalogos` (vista placeholder, middleware `seleccion.edit`)
- Controlador(es) mínimo(s) que solo renderizan shell + auth.
- Tests Feature: board 403 sin permiso; view OK; catalogos 403 con solo view; catalogos OK con edit; sidebar label.

## No incluye

- Migraciones `seleccion_ingresos` / `seleccion_examenes_ocupacionales` (T3/T4).
- Seed / CRUD catálogos payroll nuevos (T2).
- DataTables, export, CRUD registros, lookup duplicado (T3/T4).
- Dashboard metrics JSON / ApexCharts (T5).
- Docs modules/user finales (Documentador).

## Criterios de aceptacion (T1)

1. Sidebar muestra **Selección** con `view.board.gestion_humana.seleccion`.
2. Tabs navegan a dashboard / ingresos / examenes; **Catálogos** solo visible/accesible con `seleccion.edit`.
3. Rol `usuario` no recibe permisos Selección por defecto tras sync.
4. Tests de permiso básicos verdes.
5. `vendor/bin/pint --dirty --format agent`.

## Validacion

```bash
php artisan app:sync-permissions
php artisan test --compact --filter=Seleccion
vendor/bin/pint --dirty --format agent
```

## Al cerrar (reportar a AgentSj)

- Archivos modificados
- Tests ejecutados + resultado
- Pendientes T2
- Blockers
