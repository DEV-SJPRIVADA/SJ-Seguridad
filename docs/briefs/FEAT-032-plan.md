# Plan de orquestacion — FEAT-032

> Generado por AgentSj tras Feature Brief. Brief: [`FEAT-032.md`](FEAT-032.md).  
> Actualizado 2026-09-15: **documento del curso** + **consulta/descarga desde Ficha empleados**.  
> **Pausa:** confirmacion Usuario («apruebo») antes de lanzar Feature T1.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-032 |
| Modo | orquestado |
| Rama Git | feat/FEAT-032-cursos |
| Modulo principal | cursos (GH) + bridge ficha_empleados |
| Run log | `docs/runs/FEAT-032-run-log.md` |
| shared-files | `config/access.php`, `config/audit.php`, `routes/areas/gestion_humana.php`, NavigationResolver, SidebarVisibilityService, User.php, posible `app.css`, **FichaEmpleadosController + vistas ficha** |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Vacios cerrados (respuestas usuario) | — | OK |
| 2 | Arquitecto | Feature Brief final (+ doc/ficha) | 1 | OK |
| 3 | AgentSj | Plan + Task Cards T1–T4 | 2 | OK |
| 4 | Feature | T1: permisos, audit, migraciones (doc cols), modelos, Access, nav, shell + CRUD catálogo | 3 | OK |
| 5 | Feature | T2: registros + vigencia + lookup + CRUD + **documento** + export | 4 | OK |
| 6 | Feature | T3: plantilla + import upsert + tests import | 5 | OK |
| 7 | Feature | T4: botón/modal cursos en **Ficha empleados** (consulta + descarga) | 6 | OK |
| 8 | Revisor | Review del diff completo | 7 | Pendiente |
| 9 | Documentador | docs cursos + nota ficha + INDEX/ACCESS/ARCHITECTURE | 8 | Pendiente |
| 10 | AgentSj | Checklist cierre | 9 | Pendiente |

## Paralelismo

Ninguno. Shared-files: **secuencial** T1 → T2 → T3 → T4.

## Puntos de pausa usuario

- Post-Analista: OK (2026-09-15)
- Post-Brief: alcance ampliado documento + Ficha ← **aqui (re-confirmacion)**
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` / audit / nav / User | T1 | Solo T1 |
| `routes/areas/gestion_humana.php` | T1–T4 | Extender en orden |
| `app.css` | T2 | Solo T2 |
| Ficha controller / vistas | T4 | Solo T4 |

## Task Cards

- [`FEAT-032-task-1.md`](FEAT-032-task-1.md) — shell + permisos + BD + catálogo
- [`FEAT-032-task-2.md`](FEAT-032-task-2.md) — registros + documento + export
- [`FEAT-032-task-3.md`](FEAT-032-task-3.md) — plantilla + import
- [`FEAT-032-task-4.md`](FEAT-032-task-4.md) — bridge Ficha empleados
