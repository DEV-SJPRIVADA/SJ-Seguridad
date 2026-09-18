# Task Card — FEAT-032 / T1

| Campo | Valor |
| --- | --- |
| Feature | FEAT-032 |
| Tarea | T1 — Permisos, BD, Access, nav, shell tabs + CRUD Catálogo |
| Modulo | cursos |
| shared-files | **Si** — `config/access.php`, `config/audit.php`, NavigationResolver, SidebarVisibilityService, User.php, `routes/areas/gestion_humana.php` |
| Depende de | Brief + plan aprobados por usuario («apruebo») |
| Brief | [`FEAT-032.md`](FEAT-032.md) |

## Objetivo

Dejar el tablero **Cursos** visible en GH con pestañas **Cursos** \| **Catálogo**, permisos, migraciones `curso_tipos` + `employee_cursos`, modelos, Access/Audit, y **CRUD funcional del catálogo**.

## Incluye

- Keys en `config/access.php` (board, tabs, system_permissions, admin groups) + sync catálogo permisos.
- `config/audit.php` módulo `cursos`.
- Migraciones + modelos `CursoTipo`, `EmployeeCurso` (+ factories mínimas) **incluyendo columnas de documento** (`document_path`, `document_original_name`, `document_mime`, `document_size_bytes`).
- `CursosAccessService`, `CursosAuditLogService`, trait tabs si aplica.
- Nav sidebar + `User::defaultCursosBoardUrl()`.
- Rutas shell (index redirect, registros placeholder GET, catálogo CRUD completo).
- Vistas: shell + subnav + Catálogo (listado + create/edit/delete; bloqueo delete con hijos).
- `config/cursos.php` stub opcional (mimes documento / import keys) si conviene anticipar.
- Tests: board/view/edit 403; catálogo store; destroy bloqueado si tiene hijos.

## No incluye

- CRUD registros UI, vigencia UI, lookup, export, plantilla/import (T2/T3).

## Criterios de aceptacion (T1)

1. Sidebar muestra **Cursos** con `view.board.gestion_humana.cursos`.
2. Tabs **Cursos** / **Catálogo** navegan; Catálogo exige `cursos.edit`.
3. Migraciones con `php artisan migrate` (sin fresh).
4. CRUD catálogo (5 campos) operativo; unique `tipo_curso`.
5. Tests permiso + catálogo basicos verdes.

## Validacion

- `php artisan migrate`
- `php artisan app:sync-permissions` (si aplica)
- `php artisan test --compact --filter=Cursos`
- `vendor/bin/pint --dirty --format agent`
