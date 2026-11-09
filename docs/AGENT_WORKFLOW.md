# Workflow Multi-Agente

Este documento define como trabajar features y modulos con agentes de IA en SJ Seguridad, sin depender del historial de chat.

Referencias obligatorias: [`AGENTS.md`](../AGENTS.md), [`docs/DOCUMENTATION.md`](DOCUMENTATION.md), [`docs/TASKS.md`](TASKS.md), [`docs/PROCEDURES.md`](PROCEDURES.md).

## Roles (6 + carril rapido)

| Rol | Programa | Responsabilidad |
| --- | --- | --- |
| **Analista** | No | Pregunta y cuestiona hasta cerrar vacios; produce borrador de brief o lista de preguntas |
| **Arquitecto** | No | Valida brief; define rutas, permisos, esquema BD; entrega Feature Brief final |
| **AgentSj** | Solo Lite / ajustes ≤3 archivos | Triaje A/B/C, parte trabajo, lanza Tasks necesarios, Skips, checklist; en Full no implementa el slice principal |
| **Agente Feature** | Si | Vertical slice completo de UN modulo/paso por Task Card |
| **Revisor** | No | Seguridad, duplicacion, consistencia con AGENTS.md |
| **Documentador** | No | Doc tecnica (`docs/modules/`) + doc usuario (`docs/user/`) tras revision aprobada |

No existen agentes permanentes de Backend, Frontend ni BD. Migracion, modelo, controlador, vista, JS y permisos van en el Agente Feature.

## Modos de ejecucion

### Orquestado (recomendado para features)

1. Abrir **un chat maestro** en **Agent mode**.
2. **`@agent-sj`** o **`AgentSj`** + descripcion (opcional: `Modo Lite|Standard|Full`). Alternativa: [`start-feature.md`](agents/prompts/start-feature.md).
3. AgentSj hace **triaje A/B/C** (ver abajo) y solo lanza las fases necesarias.
4. Pausa si hay preguntas de negocio/UX o duda de triaje.
5. Docs segun triaje (delta vs Documentador full).
6. Checklist de cierre e integracion.
7. Run log en `docs/runs/FEAT-XXX-run-log.md` + tabla por pantalla.

### Manual (multi-chat)

Mismo flujo de roles y mismo triaje, pero el usuario abre un chat por rol y pega outputs. Prompts en [`docs/agents/prompts/`](agents/prompts/).

### Triaje de intensidad (dentro de AgentSj)

No confundir con “orquestado vs manual”. Detalle: skill [`.cursor/skills/agent-sj/SKILL.md`](../.cursor/skills/agent-sj/SKILL.md) y [`orchestrator.md`](agents/prompts/orchestrator.md).

| Triaje | Alias | Pipeline |
| --- | --- | --- |
| **A** | Lite | 1 slice; Skip Analista si Q&A cerrado; review/docs en maestro o delta |
| **B** | Standard | ≤2 slices; Arquitecto/brief; Revisor si access/auth/migración; Documentador acotado |
| **C** | Full | Analista→Arquitecto→N slices→Revisor→Documentador |

Fases omitidas = `Skip` en run log. Ajustes UI ≤3 archivos post-entrega → chat maestro (sin nuevo Task).

## Proteccion de datos (todos los agentes)

**Prohibido** ejecutar o proponer como paso automatico, sin autorizacion explicita del usuario:

- `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`
- SQL destructivo: `DROP DATABASE`, `DROP TABLE`, `TRUNCATE`, `DELETE` masivo
- Restaurar un dump SQL sobre la BD de desarrollo o produccion

Si una migracion falla: corregir la migracion y usar `php artisan migrate`. Informar al usuario antes de cualquier operacion que pueda perder datos.

Backups automaticos Laragon (local): `C:\laragon\backup\mysql\`. Regla Cursor: [`.cursor/rules/database-safety.mdc`](../.cursor/rules/database-safety.mdc).

## Carril rapido (por defecto)

**Por defecto** el agente implementa o responde **sin** AgentSj, sin `FEAT-XXX` y sin subagentes multi-rol.

**No** pasa por Analista, AgentSj ni `TASKS.md` cuando:

- Es **consulta** → Ask mode (recomendado: `consulta rapida`; plantillas en [`fast-lane.md`](agents/prompts/fast-lane.md)).
- Es **fix** o cambio acotado → Agent mode con `fix pequeno` o `fuera de AgentSj` / `NO AgentSj`.
- Ahorro de tokens: [`.cursor/rules/contexto-minimo.mdc`](../.cursor/rules/contexto-minimo.mdc). Reglas UI pesadas van por **globs** (no always-on).

Palabras clave del carril rapido: `"consulta rapida"`, `"fix pequeno"`, `"fuera de AgentSj"`, `"NO AgentSj"`.

Palabra clave del flujo orquestado: **`@agent-sj`** o mensaje que **empieza por** `AgentSj` (+ descripcion).

**Solo** flujo completo cuando:

- El usuario invoca **`@agent-sj`** / **`AgentSj`** / `@start-feature.md` / "flujo multi-agente"
- Feature o modulo nuevo, o el usuario pide orquestacion explicita
- Toca archivos compartidos criticos (`config/access.php`, auth) y el usuario confirmo flujo completo

## Secuencia orquestada (chat maestro)

```text
0. Usuario → @agent-sj [descripcion] [Modo Lite|Standard|Full opcional]
0b. AgentSj triaje A/B/C → anuncia + run log (Pausa solo si duda de modo)
1. AgentSj crea FEAT-00X en TASKS.md (orquestado + triaje) + docs/runs/FEAT-00X-run-log.md
2. Analista (o Skip) → preguntas / borrador → PAUSA si hay vacios
3. Arquitecto o brief corto (segun triaje) → docs/briefs/FEAT-00X.md
4. AgentSj → docs/briefs/FEAT-00X-plan.md (ORCHESTRATION_PLAN, slices de negocio)
5. Feature(s) vertical slice → tests --filter → fila en run log
6. Revisor Task o checklist maestro (segun triaje)
7. Si blockers → Feature corrige
8. Documentador Task o docs delta (segun triaje)
9. AgentSj checklist → TASKS.md Completadas → fila cierre
```

En Lite/Standard, registrar Skips en lugar de forzar pasos 2–8 completos.

## Registro de ejecucion (run log)

Cada feature orquestada tiene trazabilidad **por paso** sin depender del chat.

| Donde | Que muestra |
| --- | --- |
| **Chat** (cada respuesta AgentSj) | Tabla corta `## Registro de ejecucion (esta pantalla)` |
| **`docs/runs/FEAT-XXX-run-log.md`** | Historial completo con prompt, fecha, agente, artefactos, estado |
| **`docs/TASKS.md`** | Columna **Run log** con enlace al archivo |

Plantilla: [`docs/templates/RUN_LOG.md`](templates/RUN_LOG.md). Ejemplo: [`docs/runs/FEAT-PILOT-001-run-log.md`](runs/FEAT-PILOT-001-run-log.md).

### Tabla en el chat (formato)

```markdown
## Registro de ejecucion (esta pantalla)

| # | Agente | Que hizo | Artefactos | Estado |
| --- | --- | --- | --- | --- |
| 1 | AgentSj | Creo FEAT-003 y run log | docs/TASKS.md, docs/runs/FEAT-003-run-log.md | OK |
| 2 | Analista | 2 preguntas abiertas sobre alcance | docs/briefs/FEAT-003-analyst.md | Pausa |
```

Estados: `OK`, `Pausa`, `Blocker`, `Skip`, `Reintento`.

### Activacion

| Forma | Ejemplo |
| --- | --- |
| **Skill** (recomendada) | `@agent-sj Export PDF consolidado. Modo Standard` |
| Palabra clave | `AgentSj Export PDF consolidado` |
| Arroba prompt | `@docs/agents/prompts/start-feature.md` + descripcion |
| Explicito | "flujo multi-agente" / "AgentSj" |

Skill del proyecto: [`.cursor/skills/agent-sj/SKILL.md`](../.cursor/skills/agent-sj/SKILL.md)

Cualquiera de las anteriores **obliga** modo AgentSj: no implementar directo; crear TASKS + run log; lanzar `Task` en secuencia.

La regla de carril (rapido por defecto; AgentSj solo con palabra clave) esta en [`.cursor/rules/agent-workflow.mdc`](../.cursor/rules/agent-workflow.mdc). Detalle orquestador: [`.cursor/rules/orchestrator-mode.mdc`](../.cursor/rules/orchestrator-mode.mdc).

### Reglas de oro

- Ningun Agente Feature implementa sin Feature Brief final + Task Card + ID en `TASKS.md`.
- Ninguna feature se cierra sin doc tecnica actualizada y doc de usuario con las 6 secciones obligatorias.

## Agente Documentador

Corre **despues del Revisor** y **antes del cierre** de AgentSj.

**Entradas:** Feature Brief, codigo implementado, Review Report.

**Salida tecnica** — [`docs/modules/{modulo}.md`](modules/):

- Objetivo, alcance, rutas, permisos, controladores, requests, vistas, tablas, riesgos y pendientes.
- Plantilla: [`docs/templates/TECHNICAL_MODULE_DOC.md`](templates/TECHNICAL_MODULE_DOC.md).

**Salida usuario** — [`docs/user/{modulo}.md`](user/):

1. Objetivo
2. Alcance
3. Definiciones
4. Responsabilidades
5. Desarrollo (procedimiento operativo, lenguaje no tecnico)
6. Control de cambios (tabla al final)

Plantilla: [`docs/templates/USER_MODULE_DOC.md`](templates/USER_MODULE_DOC.md).

## Ownership por modulo

| Modulo / area | Rutas | Controladores | Vistas | Doc tecnica | Doc usuario |
| --- | --- | --- | --- | --- | --- |
| requisitions | `routes/modules/requisitions.php` | `RequisitionController`, catalogos | `resources/views/modules/requisitions/` | `docs/modules/requisitions.md` | `docs/user/requisitions.md` |
| supplies | `routes/modules/supplies.php` | `Supply*Controller` | `resources/views/modules/suministros/` | `docs/modules/suministros.md` | `docs/user/suministros.md` |
| quality-documents | `routes/modules/quality-documents.php` | `QualityDocument*` | `resources/views/modules/quality-documents/` | `docs/modules/quality-documents.md` | `docs/user/quality-documents.md` |
| operaciones / indicadores | `routes/areas/operaciones.php` | `Operaciones\IndicadorController` | `resources/views/areas/operaciones/` | `docs/modules/indicadores.md` | `docs/user/indicadores.md` |
| comercial | `routes/areas/comercial.php` | `Comercial\*Controller` | `resources/views/areas/comercial/` | `docs/modules/matriz-clientes.md` | `docs/user/matriz-clientes.md` |
| admin-users | `routes/web.php` (admin) | `Admin\UserController` | `resources/views/admin/` | `docs/modules/admin-users.md` | `docs/user/admin-users.md` |
| branding | — | — | layouts/components | `docs/modules/branding.md` | — |

## Zonas de conflicto (shared-files)

Solo **un** agente por tarea puede tocar estos archivos (marcar en `TASKS.md` columna `shared-files`):

- `config/access.php`
- `routes/web.php` (solo linea `require` de rutas nuevas)
- `resources/views/layouts/`
- `resources/css/app.css` (solo la lista de `@import`; la capa de un tablero en `resources/css/areas/` no es compartida)
- `database/seeders/` (globales)
- `docs/INDEX.md`, `README.md`

## Paralelismo

**Permitido:** dos Agentes Feature en modulos distintos, ramas Git distintas, sin shared-files.

**Prohibido:**

- Dos agentes en el mismo modulo simultaneamente.
- Dos agentes editando `config/access.php` o `web.php` al mismo tiempo.
- Implementar sin brief aprobado.

## Pruebas y verificacion UI (desde 2026-10-28)

- **Automaticas (agente, obligatorio):** `php artisan test --compact` del alcance, `vendor/bin/pint --dirty --format agent` si se toco PHP y `npm run build` si se toco CSS/JS/Blade con Vite.
- **Manuales (usuario):** la verificacion visual en navegador es responsabilidad del usuario. El agente **no** ejecuta smoke con navegador ni MCP `chrome-devtools`; al cerrar entrega los pasos concretos (ruta, accion, resultado esperado). Regla: [`.cursor/rules/manual-ui-verification.mdc`](../.cursor/rules/manual-ui-verification.mdc).

## Checklist AgentSj (cierre)

- [ ] Feature Brief cumplido
- [ ] `config/access.php` actualizado si aplica
- [ ] Ruta en archivo de modulo/area (no logica nueva en `web.php`)
- [ ] `docs/modules/{modulo}.md` actualizado
- [ ] `docs/user/{modulo}.md` con: Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios
- [ ] `docs/INDEX.md` si modulo nuevo o cambio de navegacion
- [ ] `README.md` si cambio stack o modulos base
- [ ] Revisor sin hallazgos bloqueantes
- [ ] Run log `docs/runs/FEAT-XXX-run-log.md` completo con fila de cierre
- [ ] `php artisan test` relevante pasa
- [ ] Si el cambio tiene UI: `npm run build` actualizado y pasos de prueba manual entregados al usuario (la verificacion visual es manual del usuario) — ver [`.cursor/rules/manual-ui-verification.mdc`](../.cursor/rules/manual-ui-verification.mdc)
- [ ] Sin solapamiento con otra tarea En progreso en mismos archivos

## Uso rapido

**Feature nueva:**

```text
Agent mode → AgentSj [descripcion breve de la feature]
```

Ejemplo: `AgentSj Modulo reportes operaciones con export Excel`

Alternativa: `@docs/agents/prompts/start-feature.md` + descripcion.

Al final de cada respuesta de AgentSj veras la tabla **Registro de ejecucion (esta pantalla)**. Historial completo en `docs/runs/FEAT-XXX-run-log.md`.

**Consulta / fix pequeno:**

```text
Ask/Agent mode → fast-lane.md o "fix pequeno en [archivo]"
```

## Piloto del workflow

Para validar el flujo sin codigo nuevo, usar una feature de documentacion:

1. AgentSj crea `FEAT-PILOT-001` en `TASKS.md`.
2. Documentador genera `docs/user/admin-users.md` desde codigo y doc tecnica existente.
3. AgentSj valida checklist y cierra tarea piloto.

Ver entrada correspondiente en [`docs/TASKS.md`](TASKS.md).
