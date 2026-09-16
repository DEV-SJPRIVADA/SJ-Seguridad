# Task Card — FEAT-032 / T4

| Campo | Valor |
| --- | --- |
| Feature | FEAT-032 |
| Tarea | T4 — Consulta/descarga cursos desde Ficha empleados |
| Modulo | cursos + ficha_empleados (bridge) |
| shared-files | **Si** — `FichaEmpleadosController`, vistas ficha (edit/show), rutas GH ficha |
| Depende de | T2 (registros + documento) |
| Brief | [`FEAT-032.md`](FEAT-032.md) |

## Objetivo

En la ficha del empleado, un botón para **consultar** sus cursos (por cédula) y **descargar** el documento si existe. Sin alta/edición/subida desde Ficha en V1.

## Incluye

- Botón en UI ficha (edit/detalle del empleado) visible con `ficha_empleados.view`.
- Endpoint listado JSON/HTML de `employee_cursos` filtrados por `document_number` del perfil/entry.
- Endpoint descarga documento con check anti-IDOR (cédula del curso = cédula del empleado abierto).
- Modal/panel: tipo, fecha, Nº, vigencia, estado, Descargar si hay archivo.
- Tests: lista OK; download OK; curso de otra cédula 403/404; sin ficha.view → 403.
- Nota mínima en docs ficha (Documentador ampliará).

## No incluye

- Subir/editar cursos desde Ficha.
- Cambiar permisos de ficha.

## Criterios de aceptacion (T4)

1. Botón visible en ficha con permiso view ficha.
2. Modal lista solo cursos de esa cédula.
3. Descargar funciona si hay documento; si no, sin botón o deshabilitado.
4. No se puede descargar curso de otra cédula manipulando ID.

## Validacion

- `php artisan test --compact --filter=Cursos`
- `php artisan test --compact --filter=Ficha` (o test dedicado bridge)
- `vendor/bin/pint --dirty --format agent`
