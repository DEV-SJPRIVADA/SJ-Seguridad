# Task Card — FEAT-042 / T3

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Tarea # | T3 |
| Brief | `docs/briefs/FEAT-042.md` |
| shared-files | **no** |

## Objetivo

Pestaña **Solicitudes**: DataTables `serverSide: true`, filtros, CRUD (alta/editar/eliminar duro), export `BaseExport` + `<x-export-excel>`, días hábiles + `dias_respuesta_manual`, searchable-select, icon-only buttons. **Sin** masivo (T4).

## Scope lock

- Controller métodos solicitudes + Form Requests store/update
- `ClienteInternoDatatableService`, business-days helper/service, Export class
- Rutas solicitudes (list/datatable/export/store/update/destroy) — **no** import
- Vistas solicitudes + modales form
- CSS mínimo solo si imprescindible (preferir clases existentes)
- Tests Feature solicitudes CRUD/DT/export/días
- `docs/TASKS.md`

## Criterios de done

1. DT serverSide; filtros año/mes/estado/cedula/nombre (mínimo brief).
2. Obligatorios: fecha_solicitud, nombre, cedula, tipo_solicitud; anio/mes derivados.
3. Días hábiles lun–vie; override flag; null sin fecha_respuesta.
4. Export con view; mutaciones con edit.
5. Delete duro con confirmación.
6. Tests + pint.

## No hacer

Import masivo, dashboard charts, access.php.
