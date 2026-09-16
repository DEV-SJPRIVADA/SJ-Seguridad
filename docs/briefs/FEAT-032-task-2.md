# Task Card — FEAT-032 / T2

| Campo | Valor |
| --- | --- |
| Feature | FEAT-032 |
| Tarea | T2 — Listado registros, vigencia, lookup, CRUD, documento, export |
| Modulo | cursos |
| shared-files | Parcial — posible `resources/css/app.css`; rutas GH |
| Depende de | T1 |
| Brief | [`FEAT-032.md`](FEAT-032.md) |

## Objetivo

Pestaña **Cursos** operativa: listado filtrable con vigencia, lookup cédula→nombre, CRUD, **cargar/reemplazar/descargar/quitar documento** (1 archivo por registro), export Excel.

## Incluye

- Datatable/listado + filtros (cédula, nombre, tipo, vigencia, estado; “solo ACTUALIZAR”).
- Vigencia calculada (hoy−335; `<` → ACTUALIZAR).
- Lookup `EmployeeFichaProfile`.
- Store/update/destroy + FormRequests; unique `(document_number, numero_curso)`; destroy borra archivo.
- Upload/download/destroy documento (PDF/JPG/PNG/WEBP, máx. 10 MB); disco local `employee-cursos`.
- searchable-select TIPO CURSO / ESTADO.
- Export `BaseExport` + `<x-export-excel>` con VIGENCIA.
- CSS badges vigencia si faltan.
- Tests: vigencia, unicidad, lookup, CRUD, documento, export auth.

## No incluye

- Plantilla/import (T3); bridge Ficha (T4).

## Criterios de aceptacion (T2)

1. Listado con columnas pedidas + indicador documento + vigencia RO.
2. Upload/replace/download/remove documento según permisos edit/view.
3. Destroy registro elimina archivo en disco.
4. Export 200 xlsx con vigencia.
5. `cursos.view` solo: export + download OK; mutaciones/upload 403.

## Validacion

- `php artisan test --compact --filter=Cursos`
- `vendor/bin/pint --dirty --format agent`
- `npm run build` si tocó CSS/JS Vite
