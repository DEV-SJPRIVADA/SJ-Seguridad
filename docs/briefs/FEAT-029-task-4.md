# Task Card — FEAT-029 / Tarea 4

## Identificacion

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Tarea # | 4 |
| Brief | `docs/briefs/FEAT-029.md` |

## Objetivo

1. Adaptar generador: `template_ids` (min 1); tipo desvinculacion; sin gate causal; 1→docx persistido, N→zip; siempre reemplaza path/type en periodo.
2. GET JSON plantillas para modal; UI Generar abre modal (checkboxes); quitar Regenerar; Descargar sirve ultimo.
3. Retirar UI/rutas/metodos de plantillas en Catalogos → Causal.
4. Limpiar shims FEAT-027 rotos (`termination_cause_code` en catalog).

## Archivos permitidos

- `app/Services/GestionHumana/TerminationLetter/**`
- `app/Http/Controllers/GestionHumana/*TerminationLetter*` / Ficha controllers relacionados
- Form requests generate
- `routes/areas/gestion_humana.php`
- Partials ficha: `termination-letter-actions`, modal blade/JS
- Catalog views/controllers: quitar includes y metodos upload templates
- `config/employee_ficha.php` si falta algo
- Tests generate/download (actualizar TerminationLetterPackTest)
- `docs/TASKS.md`

## Criterios de done

CA 5–9 del brief (modal, 1/N, todas causales, sin catalog templates, 403 sin terminate).

## Al cerrar

Archivos, tests, pendientes T5, blockers.
