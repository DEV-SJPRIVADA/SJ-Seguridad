# Plan de orquestacion — FEAT-040

> Generado por AgentSj tras brief Arquitecto. Decisiones de negocio ya confirmadas por el usuario (`implementa`).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-040 |
| Modo | orquestado |
| Rama Git | — (trabajo en rama actual) |
| Modulo principal | `reportes_novedades` (GH) |
| Run log | `docs/runs/FEAT-040-run-log.md` |
| Brief | `docs/briefs/FEAT-040.md` |
| shared-files | `config/access.php`, `config/audit.php`, `routes/areas/gestion_humana.php`, NavigationResolver, SidebarVisibilityService, User, RoleAndPermissionSeeder (sync), EmployeeTerminationFollowupService (T4) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Skip — decisiones cerradas en Ask | — | Skip |
| 2 | Arquitecto | Brief final FEAT-040 | 1 | OK |
| 3 | Feature T1 | Access/audit/catalogos + 4 migraciones/modelos + AccessService + AuditLogService + nav + shell tabs | 2 | OK |
| 4 | Feature T2 | Vacaciones + Permisos (DT, CRUD GH, review, export, historial) | 3 | OK |
| 5 | Feature T3 | Incapacidades (mismo patron) | 4 | OK |
| 6 | Feature T4 | Retiros + hooks desvinculacion + tests cierre | 5 | OK |
| 7 | Revisor | Review diff completo | 6 | OK (Aprobado con observaciones) |
| 8 | Documentador | docs/modules + docs/user + ACCESS/INDEX | 7 | Pendiente |
| 9 | AgentSj | Checklist cierre | 8 | Pendiente |

## Paralelismo

No — shared-files y un solo modulo; secuencial T1→T4.

## Puntos de pausa usuario

- Post-Brief: **omitido** — usuario pidio `implementa` con mapa ya confirmado.
- Post-Revisor: solo si blockers criticos.
