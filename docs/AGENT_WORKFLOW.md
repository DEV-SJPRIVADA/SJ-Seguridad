# Workflow Multi-Agente

Este documento define como trabajar features y modulos con agentes de IA en SJ Seguridad, sin depender del historial de chat.

Referencias obligatorias: [`AGENTS.md`](../AGENTS.md), [`docs/DOCUMENTATION.md`](DOCUMENTATION.md), [`docs/TASKS.md`](TASKS.md), [`docs/PROCEDURES.md`](PROCEDURES.md).

## Roles (6 + carril rapido)

| Rol | Programa | Responsabilidad |
| --- | --- | --- |
| **Analista** | No | Pregunta y cuestiona hasta cerrar vacios; produce borrador de brief o lista de preguntas |
| **Arquitecto** | No | Valida brief; define rutas, permisos, esquema BD; entrega Feature Brief final |
| **Orquestador** | No | Parte trabajo, lanza subagentes Task, detecta conflictos, valida checklist; no implementa ni documenta |
| **Agente Feature** | Si | Vertical slice completo de UN modulo/paso por Task Card |
| **Revisor** | No | Seguridad, duplicacion, consistencia con AGENTS.md |
| **Documentador** | No | Doc tecnica (`docs/modules/`) + doc usuario (`docs/user/`) tras revision aprobada |

No existen agentes permanentes de Backend, Frontend ni BD. Migracion, modelo, controlador, vista, JS y permisos van en el Agente Feature.

## Modos de ejecucion

### Modo A — Orquestado (recomendado para features)

1. Abrir **un chat maestro** en **Agent mode**.
2. Pegar [`docs/agents/prompts/start-feature.md`](agents/prompts/start-feature.md) + descripcion de la feature.
3. El Orquestador lanza subagentes (`Task`) en secuencia.
4. Pausa post-Analista y post-Brief si hay preguntas abiertas al usuario.
5. Documentador actualiza docs tecnicas y de usuario.
6. Orquestador ejecuta checklist de cierre e integracion.

### Modo B — Manual (multi-chat)

Mismo flujo de roles, pero el usuario abre un chat por rol y pega outputs entre chats. Util para control fino paso a paso.

Prompts en [`docs/agents/prompts/`](agents/prompts/).

## Carril rapido (excepciones)

**No** pasa por Analista, Orquestador ni `TASKS.md` cuando:

- Es **consulta** → Ask mode directo (opcional: [`fast-lane.md`](agents/prompts/fast-lane.md)).
- Es **fix pequeno** acotado a 1–3 archivos, sin permisos, migraciones ni rutas nuevas → Agent mode con alcance explicito.

Palabras clave: `"consulta rapida"` o `"fix pequeno"`.

**Siempre** flujo completo cuando:

- Toca archivos compartidos o crea algo nuevo (permiso, ruta, tabla, modulo).
- Dos agentes podrian trabajar en paralelo sin scope definido.

## Secuencia orquestada (chat maestro)

```text
0. Usuario → start-feature.md + descripcion
1. Orquestador crea FEAT-00X en TASKS.md (modo: orquestado)
2. Task Analista → ANALYST_QUESTIONS o borrador brief
   └─ PAUSA si hay preguntas → usuario responde
3. Task Arquitecto → docs/briefs/FEAT-00X.md (final)
4. Orquestador → docs/briefs/FEAT-00X-plan.md (ORCHESTRATION_PLAN)
5. Por cada tarea (secuencial salvo modulos independientes):
   Task Feature → implementa → actualiza TASKS.md fase
6. Task Revisor → docs/reviews/FEAT-00X.md
7. Si blockers → Task Feature corrige → vuelta a 6
8. Task Documentador → docs/modules/{modulo}.md + docs/user/{modulo}.md
9. Orquestador checklist → TASKS.md Completadas → integracion
```

### Reglas de oro

- Ningun Agente Feature implementa sin Feature Brief final + Task Card + ID en `TASKS.md`.
- Ninguna feature se cierra sin doc tecnica actualizada y doc de usuario con las 6 secciones obligatorias.

## Agente Documentador

Corre **despues del Revisor** y **antes del cierre** del Orquestador.

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
- `resources/css/app.css`
- `database/seeders/` (globales)
- `docs/INDEX.md`, `README.md`

## Paralelismo

**Permitido:** dos Agentes Feature en modulos distintos, ramas Git distintas, sin shared-files.

**Prohibido:**

- Dos agentes en el mismo modulo simultaneamente.
- Dos agentes editando `config/access.php` o `web.php` al mismo tiempo.
- Implementar sin brief aprobado.

## Checklist Orquestador (cierre)

- [ ] Feature Brief cumplido
- [ ] `config/access.php` actualizado si aplica
- [ ] Ruta en archivo de modulo/area (no logica nueva en `web.php`)
- [ ] `docs/modules/{modulo}.md` actualizado
- [ ] `docs/user/{modulo}.md` con: Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios
- [ ] `docs/INDEX.md` si modulo nuevo o cambio de navegacion
- [ ] `README.md` si cambio stack o modulos base
- [ ] Revisor sin hallazgos bloqueantes
- [ ] `php artisan test` relevante pasa
- [ ] Sin solapamiento con otra tarea En progreso en mismos archivos

## Uso rapido

**Feature nueva:**

```text
Agent mode → pegar docs/agents/prompts/start-feature.md
"Construir modulo [X]: [descripcion breve]"
```

**Consulta / fix pequeno:**

```text
Ask/Agent mode → fast-lane.md o "fix pequeno en [archivo]"
```

## Piloto del workflow

Para validar el flujo sin codigo nuevo, usar una feature de documentacion:

1. Orquestador crea `FEAT-PILOT-001` en `TASKS.md`.
2. Documentador genera `docs/user/admin-users.md` desde codigo y doc tecnica existente.
3. Orquestador valida checklist y cierra tarea piloto.

Ver entrada correspondiente en [`docs/TASKS.md`](TASKS.md).
