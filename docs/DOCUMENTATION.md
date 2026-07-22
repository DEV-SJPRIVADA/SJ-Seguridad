# Guia de documentacion del proyecto

Este documento define **como se organiza, mantiene y actualiza** la documentacion de SJ Seguridad para tres audiencias. El Agente **Documentador** del workflow multi-agente debe seguir esta guia en cada cierre de feature.

Referencias: [`AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md), [`PROCEDURES.md`](PROCEDURES.md), plantillas en [`templates/`](templates/).

---

## Tres capas de documentacion

| Capa | Ubicacion | Audiencia | Proposito |
| --- | --- | --- | --- |
| **Proyecto / IA** | `docs/PROJECT_CONTEXT.md`, `docs/ARCHITECTURE.md`, `docs/ACCESS_CONTROL.md`, `docs/INDEX.md`, [`AGENTS.md`](../AGENTS.md) | IAs y desarrolladores nuevos | Contexto global, stack, seguridad, indice |
| **Modulo tecnico** | `docs/modules/{modulo}.md` | IAs y desarrolladores | Rutas, permisos, modelos, reglas de negocio, riesgos |
| **Modulo usuario** | `docs/user/{modulo}.md` | Usuarios finales, capacitacion, soporte | Procedimientos operativos en lenguaje claro |

### Orden de lectura recomendado (IA / desarrollador)

1. [`INDEX.md`](INDEX.md)
2. [`AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md) — si trabajas con agentes
3. [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md)
4. [`ARCHITECTURE.md`](ARCHITECTURE.md)
5. [`ACCESS_CONTROL.md`](ACCESS_CONTROL.md)
6. Modulo especifico en `docs/modules/`

---

## Convenciones por capa

### Capa proyecto (actualizar cuando cambie alcance global)

| Archivo | Cuando actualizar |
| --- | --- |
| `PROJECT_CONTEXT.md` | Nuevo modulo, entidades BD, riesgos globales |
| `ARCHITECTURE.md` | Estructura, ownership, decisiones arquitectonicas |
| `ACCESS_CONTROL.md` | Roles, permisos transversales, middleware |
| `INDEX.md` | Nuevo modulo, links, matriz tecnico/usuario |
| `README.md` | Stack, inicio rapido, modulos visibles |
| `AGENTS.md` | Reglas obligatorias para IAs |

### Capa modulo tecnico (`docs/modules/{modulo}.md`)

Plantilla: [`templates/TECHNICAL_MODULE_DOC.md`](templates/TECHNICAL_MODULE_DOC.md)

Contenido minimo: objetivo, alcance, rutas, permisos, controladores/requests, vistas, tablas, reglas de negocio, riesgos, pendientes.

**Slug del archivo:** usar kebab-case alineado al dominio (`requisitions`, `suministros`, `quality-documents`, `indicadores`, `matriz-clientes`, `admin-users`, `branding`).

### Capa modulo usuario (`docs/user/{modulo}.md`)

Plantilla: [`templates/USER_MODULE_DOC.md`](templates/USER_MODULE_DOC.md)

**Orden obligatorio de secciones:**

1. Objetivo
2. Alcance
3. Definiciones
4. Responsabilidades
5. Desarrollo (pasos operativos)
6. Control de cambios (tabla al final)

Lenguaje no tecnico en la seccion Desarrollo. Sin nombres de rutas, permisos Spatie ni clases PHP salvo que el usuario de negocio los conozca (ej. codigos de documento).

---

## Matriz modulo: tecnico + usuario

| Modulo | Doc tecnica | Doc usuario | Area / tipo |
| --- | --- | --- | --- |
| Admin usuarios | [`modules/admin-users.md`](modules/admin-users.md) | [`user/admin-users.md`](user/admin-users.md) | Admin |
| Requisiciones | [`modules/requisitions.md`](modules/requisitions.md) | [`user/requisitions.md`](user/requisitions.md) | Compartido |
| Suministros | [`modules/suministros.md`](modules/suministros.md) | [`user/suministros.md`](user/suministros.md) | Compartido |
| Documentos calidad | [`modules/quality-documents.md`](modules/quality-documents.md) | [`user/quality-documents.md`](user/quality-documents.md) | Compartido |
| Indicadores (Operaciones) | [`modules/indicadores.md`](modules/indicadores.md) | [`user/indicadores.md`](user/indicadores.md) | Area operaciones |
| Matriz comercial | [`modules/matriz-clientes.md`](modules/matriz-clientes.md) | [`user/matriz-clientes.md`](user/matriz-clientes.md) | Area comercial |
| Branding / UI | [`modules/branding.md`](modules/branding.md) | — (solo tecnica) | Transversal |

---

## Como actualiza el multi-agente

Al cerrar una feature (paso **Documentador**):

1. Leer Feature Brief + codigo + Review Report.
2. Actualizar **doc tecnica** del modulo afectado.
3. Actualizar **doc usuario** si cambia comportamiento visible.
4. Si es modulo nuevo: crear ambos archivos y actualizar `INDEX.md`, `ARCHITECTURE.md` (ownership), `PROJECT_CONTEXT.md` (alcance).
5. Incrementar fila en **Control de cambios** del doc usuario.
6. Orquestador valida checklist en [`AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md).
7. Orquestador cierra fila final en `docs/runs/FEAT-XXX-run-log.md`.

### Carril rapido

Fixes que **no** cambian comportamiento visible para el usuario **no** requieren actualizar doc usuario; si tocan reglas tecnicas, actualizar solo doc tecnica.

---

## Responsabilidad humana vs agente

| Accion | Responsable |
| --- | --- |
| Definir reglas de negocio nuevas | Usuario / Analista |
| Redactar doc usuario (primera version) | Documentador (revision humana recomendada) |
| Mantener doc tecnica alineada al codigo | Agente Feature + Documentador |
| Validar checklist de cierre | Orquestador + usuario |

---

## Control de calidad documental

Antes de dar por cerrada una feature, verificar:

- [ ] Doc tecnica refleja rutas y permisos reales (grep en codigo si hace falta).
- [ ] Doc usuario tiene las 6 secciones en orden.
- [ ] `INDEX.md` lista el par tecnico/usuario.
- [ ] No hay contradicciones entre `PROJECT_CONTEXT.md` y modulos.
- [ ] Fecha y version en Control de cambios del doc usuario.
