# Task Card — FEAT-029 / Tarea 2

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Tarea # | 2 |
| Modulo / area | Gestion humana — Plantillas Word (BD + modelos) |
| Brief | `docs/briefs/FEAT-029.md` |
| Plan | `docs/briefs/FEAT-029-plan.md` |

## Objetivo

Migraciones incrementales + modelos: catalogo `word_document_types`, alter `termination_letter_document_templates` (FK tipo; cleanup filas RENUNCIA; dropear causa/document_key/is_required), seed tipo `desvinculacion`.

## Archivos permitidos

- `database/migrations/*` (nuevas)
- `database/seeders/*` relacionados a WordDocumentType (si aplica)
- `app/Models/WordDocumentType.php`
- `app/Models/TerminationLetterDocumentTemplate.php`
- Factories si el proyecto las usa para estos modelos
- Tests de modelo/migracion/schema si aplica
- `docs/TASKS.md` (fase)

## Prohibido

- `migrate:fresh` / wipe / TRUNCATE manual fuera de data cleanup acotado en migracion
- UI CRUD completa (T3)
- Cambios generate modal (T4)
- Toccar `config/access.php` salvo bug T1

## Criterios de done

1. `php artisan migrate` OK.
2. Existe tipo seed `desvinculacion`.
3. Tabla templates sin columnas causa/document_key/is_required; con `word_document_type_id` NOT NULL.
4. Filas RENUNCIA eliminadas (y archivos si la migracion lo hace).
5. Modelo `WordDocumentType` + relacion en template; scopes utiles.

## Al cerrar

Reportar migraciones corridas, archivos, tests, blockers.
