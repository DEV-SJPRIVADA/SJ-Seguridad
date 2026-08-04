# Plan de orquestacion — FEAT-023

> Generado por AgentSj tras Feature Brief final.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-023 |
| Modo | orquestado |
| Rama Git | feat/FEAT-023-captura-delegada |
| Modulo principal | indicadores (operaciones) |
| Run log | `docs/runs/FEAT-023-run-log.md` |
| shared-files | `config/access.php`, `App\Support\IndicadorNavigation.php`, `App\Models\User.php` (canAccessIndicadorTab) |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacios / preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | Feature | T1: Vertical slice captura delegada (permiso, servicios, controller, vistas, ajustes, tests) | 2 | OK |
| 4 | Revisor | Review del diff completo | 3 | OK |
| 5 | Documentador | docs/modules + docs/user indicadores | 4 | OK |
| 6 | AgentSj | Checklist cierre | 5 | OK |

## Task Card T1 — Vertical slice

**Scope lock:** implementar FEAT-023 segun `docs/briefs/FEAT-023.md` completo.

**Incluye:**
- `config/access.php` — permiso `operations.capture.delegate`
- `IndicatorCaptureAccessService` — canDelegateCapture, canAccessCaptureScreen, delegatePermissionsToGrant, setDelegateCaptureEnabled, resolveTitularUser
- `IndicatorCaptureService` — buildShowContext/save con titular+actor; audit metadata; persistImprovement
- `IndicadorController` — show/storeCapture + updateCapturadorDelegate
- `StoreIndicatorCaptureRequest` — authorize + capturador_user_id
- Rutas PATCH suplencia en `routes/areas/operaciones.php`
- Vistas: `show.blade.php`, `capturadores.blade.php`
- `IndicadorNavigation` + `User::canAccessIndicadorTab('capture')`
- Tests listados en brief (IndicadorModuleTest o archivo dedicado)
- `vendor/bin/pint --dirty`

**Excluye:** documentacion modulo/usuario (Documentador).

## Paralelismo

Ninguno — un solo vertical slice.

## Puntos de pausa usuario

- Post-Analista: **omitido** (decisiones confirmadas)
- Post-Brief: **omitido** (usuario confirmo alcance)
- Post-Revisor: blockers criticos

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `config/access.php` | FEAT-013/020 en progreso | Serializar; solo editar permiso FEAT-023 |
