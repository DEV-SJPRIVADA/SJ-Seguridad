# Plan de orquestacion — FEAT-036

> Generado por el AgentSj tras el Feature Brief final. Guardar como `docs/briefs/FEAT-036-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-036 |
| Modo | orquestado |
| Rama Git | (rama actual / feat cuando aplique) |
| Modulo principal | acreditaciones (gestion_humana) |
| Brief | [`docs/briefs/FEAT-036.md`](FEAT-036.md) |
| Run log | [`docs/runs/FEAT-036-run-log.md`](../runs/FEAT-036-run-log.md) |
| shared-files | Sí — `config/access.php`, `routes/areas/gestion_humana.php`, nav (NavigationResolver / SidebarVisibilityService / User), `config/audit.php`, schedule (`routes/console.php`), seeders/sync permisos |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacíos / preguntas 1–5 | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | **T1** Accesos / nav / shell (shared-files) | 2 | OK |
| 4 | Feature | **T2** Catálogo cargos + seed 17 + CRUD | 3 | OK |
| 5 | Feature | **T3** Acreditados core (CRUD, estados, DT, export) | 4 | OK |
| 6 | Feature | **T4** Import upsert + sync diario | 5 | OK |
| 7 | Revisor | Review del diff completo | 6 | OK |
| 8 | Documentador | `docs/modules` + `docs/user` + INDEX/ACCESS/ARCH | 7 | OK |
| 9 | AgentSj | Checklist cierre | 8 | OK |

## Task Cards (detalle)

### T1 — Accesos / nav / shell

**Scope lock:**
- `config/access.php` (board, tabs, permisos, admin groups)
- `config/audit.php` (módulo acreditaciones)
- `AcreditacionesAccessService` + trait `HasAcreditacionesTabs`
- Rutas shell en `routes/areas/gestion_humana.php` (index, placeholders, stubs Acreditados/Catálogo sin lógica de negocio)
- Controlador shell mínimo + vistas Blade (tabs chrome + «Próximamente»)
- Nav: NavigationResolver / SidebarVisibilityService / User si aplica
- Tests Feature permisos / 403 / sidebar board
- **shared-files autorizado**

**Fuera:** migraciones de negocio, CRUD, import, calculator, seeder cargos.

### T2 — Catálogo + seed

**Scope lock:** migración/modelo `acreditacion_cargos`; seeder 17; UI CRUD; Form Requests; reglas delete/rename APO; tests. Depende de T1 (rutas catálogo ya existen).

### T3 — Acreditados core

**Scope lock:** migración/modelo `acreditacion_acreditados`; `AcreditacionEstadoCalculator`; datatable; CRUD; filtros; export `BaseExport`; audit mutaciones; tests estados/Ficha/unicidad. Depende de T2 (validar `cargo_apo`).

### T4 — Import + sync

**Scope lock:** plantilla; `AcreditacionImportService` upsert; reporte fallos; comando `acreditaciones:sync-estados` + schedule; tests import/sync. Depende de T3.

## Paralelismo

Ninguno. Secuencial T1→T4 por shared-files y dependencia catálogo→acreditados→import.

## Puntos de pausa usuario

- Post-Analista: cerrado
- Post-Brief: AgentSj avanza (confirmación usuario opcional)
- Post-Revisor: blockers críticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/access.php` | T1 (y no paralelizar con otros FEAT) | Solo T1 toca access; serializar |
| `routes/areas/gestion_humana.php` | T1–T4 | Un Feature a la vez; T1 crea grupo; T2–T4 amplían |
| Schedule / `console.php` | T4 | Solo T4 |
