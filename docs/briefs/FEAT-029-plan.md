# Plan de orquestacion — FEAT-029

> Generado por AgentSj tras Feature Brief final. Brief: [`FEAT-029.md`](FEAT-029.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-029 |
| Modo | orquestado |
| Rama Git | (trabajar en rama actual / Manuel-E) |
| Modulo principal | Gestion humana — Plantillas Word + Ficha empleados (cartas) |
| Run log | `docs/runs/FEAT-029-run-log.md` |
| shared-files | `config/access.php`, PermissionCatalog, NavigationResolver, RoleAndPermissionSeeder, `config/employee_ficha.php`, `routes/areas/gestion_humana.php`, User |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Preguntas + respuestas usuario | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | T1: Permisos + Access + Navigation + seeders (shared-files) | 2 | OK |
| 4 | Feature | T2: Migraciones BD + modelos + seed tipo desvinculacion | 3 | OK |
| 5 | Feature | T3: Tablero CRUD tipos/plantillas (controller, vistas, audit) | 4 | OK |
| 6 | Feature | T4: Generador por IDs + modal ficha + retirar catalog Causal | 5 | OK |
| 7 | Feature | T5: Tests PHPUnit + pint | 6 | OK |
| 8 | Revisor | Review del diff completo | 7 | OK |
| 9 | Documentador | docs/modules + docs/user (+ INDEX / ACCESS_CONTROL) | 8 | OK |
| 10 | AgentSj | Checklist cierre | 9 | OK |

## Paralelismo

Ninguno. Shared-files y un solo modulo GH — **secuencial**.

## Puntos de pausa usuario

- Post-Analista: cerrado (respuestas 2026-08-21)
- Post-Brief: decisiones ya confirmadas; se avanza a Feature sin nueva pausa
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 | Solo T1 |
| `routes/areas/gestion_humana.php` | T3 + T4 | T3 agrega rutas tablero; T4 adapta cartas y retira catalog — secuencial |
| `config/employee_ficha.php` | T1 o T2 | Preferir T1 (config) junto a permisos |

## Notas AgentSj

- No `migrate:fresh`. Cleanup RENUNCIA = borrar filas/archivos en migracion data step (usuario re-sube).
- Generar/Descargar: solo `ficha_empleados.terminate`.
