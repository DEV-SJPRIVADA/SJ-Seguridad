# Plan de orquestacion — FEAT-038

> Generado por el AgentSj tras el Feature Brief final. Guardar como `docs/briefs/FEAT-038-plan.md`.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-038 |
| Modo | orquestado |
| Rama Git | (local / sin forzar) |
| Modulo principal | acreditaciones (Gestion Humana) — pestaña Validaciones |
| Brief | [`docs/briefs/FEAT-038.md`](FEAT-038.md) |
| Analyst | [`docs/briefs/FEAT-038-analyst.md`](FEAT-038-analyst.md) |
| Run log | [`docs/runs/FEAT-038-run-log.md`](../runs/FEAT-038-run-log.md) |
| shared-files | `routes/areas/gestion_humana.php` (sí); `config/access.php` (**no**); `app.css` solo si hace falta |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacíos / 8 preguntas | — | OK |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan + pausa confirmación alcance | 2 | Pausa |
| 4 | Feature | **T1** Shell + permisos + gate (vista, tab solo edit, fecha, mensajes, link Reporte Diario) | 3 | OK |
| 5 | Feature | **T2** Motor + ejecución + listados (normalizer, runner 4 colas, cache, POST ejecutar, DT server-side) | 4 | OK |
| 6 | Feature | **T3** Acciones + export (Ficha / editar / alta precargada; export por cola + consolidado 4 hojas) | 5 | OK |
| 7 | Feature | **T4** Tests + polish (criterios aceptación, pint) | 6 | Pendiente |
| 8 | Revisor | Review diff completo → `docs/reviews/FEAT-038.md` | 7 | Pendiente |
| 9 | Documentador | `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` (+ ACCESS_CONTROL si aplica) | 8 | Pendiente |
| 10 | AgentSj | Checklist cierre → Completadas | 9 | Pendiente |

### Detalle Task Cards (vertical slices)

| ID | Scope | Entregables clave | shared-files |
| --- | --- | --- | --- |
| FEAT-038-T1 | Shell + gate | `validaciones.blade.php`; subnav solo `edit`; GET; mensajes gate ambos orígenes; link Reporte Diario; rutas en `gestion_humana.php` | Sí (rutas GH) |
| FEAT-038-T2 | Motor + DT | Normalizer; Runner (4 colas); ResultStore cache; POST ejecutar; DT server-side por cola; UI resultados | Rutas adicionales |
| FEAT-038-T3 | Acciones + export | Modales reuso; Abrir Ficha; Editar; Alta precargada; `BaseExport` por cola + consolidado | Mínimo |
| FEAT-038-T4 | Tests | Feature tests gate/match/colas/permisos/export; pint | No |

## Paralelismo

No. Un solo módulo + shared `gestion_humana.php` → **secuencia estricta** T1→T2→T3→T4.

## Puntos de pausa usuario

- Post-Analista: preguntas 1–8 — **cerrado**
- **Post-Brief: confirmación de alcance** — **ahora**
- Post-Revisor: blockers críticos

## Conflictos detectados

| Archivo | Riesgo | Resolucion |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | Otras FEAT GH en progreso | Un Feature a la vez; flag shared-files |
| `config/access.php` | — | **No tocar** |
| `AcreditacionesController` + partials modales | Reuso CRUD | No duplicar endpoints; incluir modales en vista Validaciones |

## Confirmacion de alcance (usuario)

Antes de T1, confirmar:

1. Brief [`FEAT-038.md`](FEAT-038.md) OK.
2. Gate: **ambos** orígenes APO ese día.
3. Match: `cargo_apo` ↔ Cargo Excel, igualdad normalizada (sin “contiene”).
4. 4 colas + acciones + export por cola y consolidado.
5. Sin migración / sin histórico de corridas.
6. Solo `acreditaciones.edit` (tab oculta sin edit).

Responder **«confirmo»** (o ajustes) para lanzar Feature T1.
