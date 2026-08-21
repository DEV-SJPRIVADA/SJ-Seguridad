# Task Card — FEAT-029 / Tarea 3

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Tarea # | 3 |
| Modulo / area | Gestion humana — Plantillas Word (tablero CRUD) |
| Brief | `docs/briefs/FEAT-029.md` |

## Objetivo

Tablero completo: listar plantillas con tipo; CRUD tipos (crear/editar/desactivar-eliminar con restrict si hay plantillas); agregar plantilla (label+tipo+docx); reemplazar solo docx; eliminar con confirmacion; descargar master; audit.

## Archivos permitidos

- `app/Http/Controllers/GestionHumana/PlantillasWordController.php`
- Form Requests bajo `app/Http/Requests/GestionHumana/PlantillasWord/` (o similar)
- `app/Services/GestionHumana/TerminationLetter/TerminationLetterTemplateManager.php` (adaptar paths por type id)
- Vistas `resources/views/areas/gestion_humana/plantillas-word/**`
- JS del tablero si aplica bajo resources
- `routes/areas/gestion_humana.php` (rutas CRUD tablero; no tocar generate ficha salvo necesario)
- Audit wrapper/service segun patron GH
- Tests Feature admin CRUD + permisos
- `docs/TASKS.md`

## Prohibido

- Modal generar cartas / cambiar TerminationLetterController generate (T4)
- Reintroducir UI plantillas en Catalogos Causal (T4 las retira)
- `migrate:fresh`

## Criterios de done

1. UI usable alineada a modulos GH (chrome pills si aplica).
2. Reglas brief: add label+tipo+file; replace solo file; delete confirm; no borrar tipo con plantillas.
3. Rutas del brief para tipos/plantillas.
4. Tests CRUD + 403 sin manage.
5. Pint OK.

## Al cerrar

Archivos, tests, pendientes T4, blockers.
