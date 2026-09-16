# Task Card — FEAT-032 / T3

| Campo | Valor |
| --- | --- |
| Feature | FEAT-032 |
| Tarea | T3 — Plantilla vacía + import upsert |
| Modulo | cursos |
| shared-files | Parcial — rutas GH |
| Depende de | T2 |
| Brief | [`FEAT-032.md`](FEAT-032.md) |

## Objetivo

Descargar plantilla vacía e importar Excel (upsert por cédula + Nº curso) con reporte por fila. **Sin** binarios de documento en el Excel.

## Incluye

- `config/cursos.php` columnas import si no quedó en T1.
- `CursosImportTemplateExport` (fila 1 keys, fila 2 labels).
- `EmployeeCursoImportService` — upsert, match tipo_curso CI, continuar ante fallos, no borrar ausentes, **no** tocar `document_*`.
- Rutas plantilla + import (+ reporte token si patrón ficha).
- UI botones en pestaña Cursos (edit).
- Audit evento import.
- Tests: insert + update + fail tipo; plantilla auth edit.

## No incluye

- Bridge Ficha (T4); docs módulo (Documentador).

## Criterios de aceptacion (T3)

1. Plantilla solo con `cursos.edit`.
2. Import upsert + errores parciales.
3. Tipo inexistente → fail fila.
4. Import no borra ni exige documento.

## Validacion

- `php artisan test --compact --filter=Cursos`
- `vendor/bin/pint --dirty --format agent`
