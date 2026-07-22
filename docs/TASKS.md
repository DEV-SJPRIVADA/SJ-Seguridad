# Tablero de tareas — SJ Seguridad

Tablero vivo para el **Orquestador**. Convencion de IDs: `FEAT-001`, `FEAT-002`, …

Workflow: [`docs/AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md)

---

## En progreso

| ID | Feature | Modo | Fase actual | Orquestador chat | Rama | Brief | Run log | shared-files |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| | | | | | | | | |

---

## Cola

| ID | Prioridad | Feature | Modulo | Dependencias |
| --- | --- | --- | --- | --- |
| | | | | |

---

## Completadas (ultimas 10)

| ID | Feature | Modo | Validado | Run log | Fecha cierre |
| --- | --- | --- | --- | --- | --- |
| FEAT-002 | Export informe gestion FO-GI-39 (PPTX) | directo | Si | — | 2026-07-22 |
| FEAT-PILOT-001 | Doc usuario admin-users (piloto workflow) | orquestado | Si | [`docs/runs/FEAT-PILOT-001-run-log.md`](runs/FEAT-PILOT-001-run-log.md) | 2026-07-22 |
| DOC-ALIGN-001 | Alineacion documentacion IA/dev/usuario (7 modulos) | manual | Si | — | 2026-07-22 |

---

## Ejemplo comentado (no borrar — referencia)

<!--
### Como crear una feature nueva

1. Orquestador agrega fila en Cola o En progreso.
2. Ejemplo:

| ID | Feature | Modo | Fase actual | Orquestador chat | Rama | Brief | Run log | shared-files |
| FEAT-001 | Export Excel ajustes operaciones | orquestado | Analista | chat-2026-07-22 | feat/FEAT-001-export-ajustes | docs/briefs/FEAT-001.md | docs/runs/FEAT-001-run-log.md | config/access.php |

3. Fases tipicas: Analista → Arquitecto → Feature (N tareas) → Revisor → Documentador → Cierre
4. Al completar: mover a Completadas y limpiar En progreso.

### Piloto FEAT-PILOT-001

Validacion del Agente Documentador sin codigo nuevo:
- Entrada: doc tecnica existente docs/modules/admin-users.md
- Salida: docs/user/admin-users.md
- Ver docs/briefs/FEAT-PILOT-001.md y docs/reviews/FEAT-PILOT-001.md
-->
