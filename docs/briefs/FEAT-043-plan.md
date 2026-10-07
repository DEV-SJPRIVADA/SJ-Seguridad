# Plan de orquestacion — FEAT-043

> Generado por el AgentSj tras aprobar el Feature Brief. Plantilla: [`ORCHESTRATION_PLAN.md`](../templates/ORCHESTRATION_PLAN.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-043 |
| Modo | orquestado |
| Rama Git | — (local / AgentSj chat) |
| Modulo principal | Gestion humana — Cliente interno (`cartas_vacaciones`) |
| Brief | [`docs/briefs/FEAT-043.md`](FEAT-043.md) |
| Run log | [`docs/runs/FEAT-043-run-log.md`](../runs/FEAT-043-run-log.md) |
| shared-files | `config/access.php`, `config/employee_ficha.php`, `routes/areas/gestion_humana.php`, `ClienteInternoAccessService`, `WordDocumentTypeSeeder`, migración Spatie, `LetterVariableBuilder` |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacíos UX (respuestas 1–7) | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan + Task Cards; pausa `OK implementa` | 2 | OK |
| 4 | Feature | **T1** Permisos + Access + seed tipo Word + placeholders + GET pestaña + tests acceso | 3 | OK |
| 5 | Feature | **T2** UI grilla + lookup + generate docx/zip + audit + tests generate | 4 | OK |
| 6 | Revisor | Review del diff completo | 5 | OK (Aprobado con observaciones) |
| 7 | Documentador | `docs/modules` + `docs/user` (CI + plantillas-word) + ACCESS | 6 | OK |
| 8 | AgentSj | Checklist cierre → Completadas | 7 | OK |

## Task Cards (resumen)

### T1 — Permisos / Access / seed / shell

- **shared-files:** sí (`access.php`, AccessService, tabs/redirect, WordDocumentTypeSeeder, `employee_ficha.php` codes+placeholders, migración Spatie, ruta GET + empty-state).
- **Done:** permisos view/edit; migración desde `solicitudes.edit`; Dashboard sin cartas-only; tipo `cartas_vacaciones`; pestaña visible; tests acceso/migración.

### T2 — UI + generación

- **shared-files:** no (salvo hotfix AgentSj).
- **Done:** grilla Masivos-like; lookup; generate 1→docx / N→zip; bloqueo 0/>1 plantillas; validaciones; audit; tests; Pint; smoke DevTools MCP.

## Paralelismo

Ninguno: T1 → T2 secuencial (shared-files en T1; LetterVariableBuilder en T2).

## Puntos de pausa usuario

- Post-Analista: cerrado (respuestas 1–7).
- **Post-Brief:** confirmación de alcance → **`OK implementa`**.
- Post-Revisor: blockers críticos.

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `ClienteInternoAccessService` | T1 | Solo T1 |
| `LetterVariableBuilder` | T2 | Tras T1; no paralelo |
| `routes/areas/gestion_humana.php` | T1 (+ T2 añade POST) | T1 GET; T2 lookup/generate en mismo archivo — secuencial |
