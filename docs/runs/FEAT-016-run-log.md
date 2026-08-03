# Run log — FEAT-016

## Resumen de la feature

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-016 |
| Titulo | Listado servicios comercial: orden columnas y vigencia por contrato |
| Modo | orquestado |
| Modulo | matriz-clientes / comercial servicios |
| Chat AgentSj | 2026-07-29 servicios vigencia columnas |
| Brief | `docs/briefs/FEAT-016.md` |
| Plan | `docs/briefs/FEAT-016-plan.md` (pendiente) |
| Inicio | 2026-07-29 |
| Cierre | |

## Decisiones usuario (AgentSj)

| Tema | Decision |
| --- | --- |
| Orden columnas | NIT, Cliente, Contrato, Tipo servicio, Portafolio, Asesor, Inicio, Fin, Vigencia, Acciones |
| Vigencia — Activo | Fin de contrato **mas de 30 dias calendario** despues de hoy (o sin fin → Activo) |
| Vigencia — Por vencer | Fin >= hoy y fin <= hoy + 30 dias |
| Vigencia — Vencido | Fin < hoy |
| Vigencia — Inactivo | `is_inactive === true` (boton Inactivar; **no** cambia portafolio) |
| Reactivar | `POST activar` → `is_inactive = false`; boton **Activar** en servicios y ficha cliente |
| Alcance v1 | Solo tablero `/comercial/servicios` + export + filtro vigencia; dashboard y ficha cliente fuera |
| contract_end null | Activo (salvo inactivo) |

## Registro por paso

| # | Fecha | Prompt / trigger | Agente | Que hizo (1 linea) | Artefactos | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-07-29 | `@agent-sj` servicios tabla + vigencia | AgentSj | FEAT-016 en TASKS + run log; preguntas usuario | `docs/TASKS.md`, este archivo | OK |
| 2 | 2026-07-29 | Task Analista | Analista | Analisis codigo, reglas vigencia, Opcion A is_active | `docs/briefs/FEAT-016-analyst.md` | OK |
| 3 | 2026-07-29 | Task Arquitecto | Arquitecto | Feature Brief final v1 servicios + export | `docs/briefs/FEAT-016.md` | OK |
| 5 | 2026-07-29 | Usuario ajuste is_inactive + Activar | AgentSj / Feature | Implementacion T1 | codigo, migracion, tests | OK |
| 6 | | Task Revisor | Revisor | | | |
| 6 | | Task Revisor | Revisor | | | |
| 7 | | Task Documentador | Documentador | | | |
