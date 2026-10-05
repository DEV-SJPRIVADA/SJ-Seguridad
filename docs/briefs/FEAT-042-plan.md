# Plan de orquestacion — FEAT-042

> Generado por AgentSj tras brief Arquitecto. **Pausa post-Brief** hasta confirmación del usuario (alcance + seed SOLICITUD).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-042 |
| Modo | orquestado |
| Rama Git | (rama actual) |
| Modulo principal | gestion_humana / `cliente_interno` |
| Run log | `docs/runs/FEAT-042-run-log.md` |
| Brief | `docs/briefs/FEAT-042.md` |
| shared-files | `config/access.php`, rutas GH, nav, `config/audit.php` (si aplica), Vite entry charts |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas + respuestas usuario | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan orquestacion | 2 | OK (esta pantalla) |
| 4 | Feature | **T1:** shell + permisos + migraciones (3 tablas) + seed ESTADO + AccessService + nav + tests acceso | 3 + OK usuario | pendiente |
| 5 | Feature | **T2:** Catálogos ESTADO + SOLICITUD (CRUD, bloqueo DELETE con refs) | 4 | pendiente |
| 6 | Feature | **T3:** Solicitudes DT/CRUD/filtros/export/días hábiles + override | 5 | pendiente |
| 7 | Feature | **T4:** Masivo replace-por-periodo (opción B) + plantilla + confirmación | 6 | pendiente |
| 8 | Feature | **T5:** Dashboard KPIs + ApexCharts | 7 | pendiente |
| 9 | Revisor | Review diff completo | 8 | pendiente |
| 10 | Documentador | docs/modules + docs/user + ACCESS/INDEX | 9 | pendiente |
| 11 | AgentSj | Checklist cierre | 10 | pendiente |

## Paralelismo

No — shared-files en T1; T2–T5 mismo módulo; secuencial.

## Puntos de pausa usuario

- Post-Analista: OK (respondido 2026-10-02)
- **Post-Brief: PAUSA** — confirmar alcance + seed SOLICITUD vacío
- Post-Revisor: si hay blockers

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `routes/areas/gestion_humana.php` | T1–T5 | Secuencial |
| nav / audit / Vite | T1 / T5 | Secuencial |
