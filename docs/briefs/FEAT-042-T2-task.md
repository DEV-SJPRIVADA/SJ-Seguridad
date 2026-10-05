# Task Card — FEAT-042 / T2

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Tarea # | T2 |
| Modulo / area | gestion_humana / `cliente_interno` |
| Brief | `docs/briefs/FEAT-042.md` |
| shared-files | **no** (no tocar `config/access.php`) |

## Objetivo

Pestaña **Catálogos**: CRUD de **ESTADO** y **SOLICITUD** (`?catalog=estados|tipos-solicitud`), Form Requests, bloqueo DELETE si hay referencias, empty-state cuando SOLICITUD vacío, tests seed + CRUD. Icon-only buttons estándar.

## Scope lock (permitidos)

- `app/Http/Controllers/GestionHumana/ClienteInternoController.php` (métodos catálogos) **o** `ClienteInternoCatalogController.php`
- `app/Http/Requests/GestionHumana/ClienteInterno/*` (catalog store/update)
- `app/Services/GestionHumana/ClienteInternoCatalogService.php` (si justificado)
- Rutas catálogos en `routes/areas/gestion_humana.php` (solo rutas de esta feature)
- Vistas `resources/views/areas/gestion_humana/cliente_interno/catalogos.blade.php` + partials
- Modelos existentes si hace falta relaciones `hasMany` / conteo refs
- Audit log calls para create/update/delete catálogo
- `tests/Feature/GestionHumana/ClienteInternoCatalogosTest.php`
- `docs/TASKS.md` fase

## Prohibidos

- `config/access.php`, nav global
- DT solicitudes, import, dashboard metrics, export
- `migrate:fresh`

## Criterios de done

1. Solo `parameters.edit` (+ board) accede a Catálogos; 403 sin permiso.
2. CRUD ESTADO y tipos SOLICITUD (code, name, is_active, sort_order).
3. DELETE rechazado si hay filas en `cliente_interno_solicitudes` referenciando el ítem; desactivar OK.
4. Empty-state claro si no hay tipos de solicitud.
5. Seed ESTADO visible (4 valores) tras migrate previo T1.
6. Tests verdes + pint.

## Al cerrar

Reportar archivos, tests, pendientes T3, blockers.
