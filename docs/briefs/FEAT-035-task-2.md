# Task Card — FEAT-035 / T2

| Campo | Valor |
| --- | --- |
| Feature | FEAT-035 |
| Tarea | T2 — Catálogos payroll nuevos + seed + CRUD UI Selección |
| Modulo | seleccion |
| shared-files | **Si** — `config/employee_ficha.php` (catalog_types / labels / ficha_admin_excluded); opcional ampliar rutas GH |
| Depende de | T1 OK |
| Brief | [`FEAT-035.md`](FEAT-035.md) |
| Plan | [`FEAT-035-plan.md`](FEAT-035-plan.md) |

## Objetivo

Habilitar pestaña **Catálogos** de Selección con CRUD sobre whitelist de 7 tipos en `payroll_catalog_items`, seed no destructivo de RH / estado civil / estados SOLICITUD, y exclusión de los 3 tipos nuevos en UI Catálogos de Ficha.

## Incluye

- `config/employee_ficha.php`: agregar `blood_type`, `marital_status`, `seleccion_solicitud_status` a `catalog_types` + labels; `ficha_admin_excluded_catalog_types` (o equivalente) para que Ficha **no** los muestre en admin.
- Actualizar `config/seleccion.php` `managed_catalog_types` = city, position, eps, afp, blood_type, marital_status, seleccion_solicitud_status.
- Migración seed upsert (insertOrIgnore / updateOrInsert) filas seed del Brief (RH, marital, solicitud status).
- Filtrar `EmployeeFichaCatalogService` / admin UI Ficha para respetar exclusión.
- Controlador Catálogos Selección: index (`?catalog=`), store, update, destroy.
- Form Requests; validación whitelist; unique `(catalog_type, code)`.
- Vistas tablero tarjetas + form create/edit (patrón Ficha/Cursos catálogo).
- Bloquear DELETE si hay referencias en `seleccion_ingresos` / `seleccion_examenes` **si esas tablas ya existen**; si no existen aún (T3/T4), bloquear delete solo cuando haya refs futuras o documentar TODO y permitir destroy con is_active preferido — Brief: **bloquear DELETE si hay referencias; permitir desactivar**. Sin tablas de negocio aún: destroy OK + tests de whitelist.
- Audit create/update/delete catálogo vía `SeleccionAuditLogService`.
- Tests: whitelist rechazo tipo inválido; store seed types; Ficha admin no lista blood_type; sync permisos intacto.

## No incluye

- CRUD Ingreso / Examen (T3/T4).
- Dashboard charts (T5).
- Doc usuario final.

## Criterios de aceptacion (T2)

1. Con `seleccion.edit` se abre Catálogos y se CRUD-ean los 7 tipos.
2. Seed deja RH / estado civil / SOLICITUD con codes del Brief.
3. UI Ficha Catálogos **no** muestra los 3 tipos nuevos.
4. Tipo fuera de whitelist → 404/403.
5. Tests verdes + pint.

## Validacion

```bash
php artisan migrate
php artisan test --compact --filter=Seleccion
vendor/bin/pint --dirty --format agent
```

## Al cerrar

Reportar archivos, tests, pendientes T3, blockers.
