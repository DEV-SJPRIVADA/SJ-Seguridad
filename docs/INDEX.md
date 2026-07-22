# Documentacion Viva

Este directorio concentra el contexto necesario para que cualquier desarrollador o IA entienda el proyecto sin depender del historial del chat.

**Guia de las tres capas (IA, desarrollador, usuario):** [`DOCUMENTATION.md`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md)

## Orden recomendado de lectura

1. [`DOCUMENTATION.md`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md) — como esta organizada la documentacion
2. [`PROJECT_CONTEXT.md`](c:/laragon/www/SJSEGURIDAD/docs/PROJECT_CONTEXT.md)
3. [`AGENT_WORKFLOW.md`](c:/laragon/www/SJSEGURIDAD/docs/AGENT_WORKFLOW.md) — workflow multi-agente e IAs
4. [`ARCHITECTURE.md`](c:/laragon/www/SJSEGURIDAD/docs/ARCHITECTURE.md)
5. [`ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md)
6. [`LOCAL_SETUP.md`](c:/laragon/www/SJSEGURIDAD/docs/LOCAL_SETUP.md)
7. [`PROCEDURES.md`](c:/laragon/www/SJSEGURIDAD/docs/PROCEDURES.md)
8. Modulos tecnicos en [`docs/modules`](c:/laragon/www/SJSEGURIDAD/docs/modules)
9. Guias de usuario en [`docs/user`](c:/laragon/www/SJSEGURIDAD/docs/user)

## Workflow multi-agente

- Flujo y roles: [`AGENT_WORKFLOW.md`](c:/laragon/www/SJSEGURIDAD/docs/AGENT_WORKFLOW.md)
- Tablero de tareas: [`TASKS.md`](c:/laragon/www/SJSEGURIDAD/docs/TASKS.md)
- Prompts: [`docs/agents/prompts/`](c:/laragon/www/SJSEGURIDAD/docs/agents/prompts/)
- Plantillas: [`docs/templates/`](c:/laragon/www/SJSEGURIDAD/docs/templates/)
- Feature briefs: [`docs/briefs/`](c:/laragon/www/SJSEGURIDAD/docs/briefs/)
- Review reports: [`docs/reviews/`](c:/laragon/www/SJSEGURIDAD/docs/reviews/)

## Documentacion de usuario

Guias operativas en [`docs/user/`](c:/laragon/www/SJSEGURIDAD/docs/user/). Matriz completa en [`DOCUMENTATION.md`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md).

| Modulo | Doc tecnica | Doc usuario |
| --- | --- | --- |
| Admin usuarios | [`modules/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/admin-users.md) | [`user/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/user/admin-users.md) |
| Requisiciones | [`modules/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/requisitions.md) | [`user/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/user/requisitions.md) |
| Suministros | [`modules/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/suministros.md) | [`user/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/user/suministros.md) |
| Documentos calidad | [`modules/quality-documents.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/quality-documents.md) | [`user/quality-documents.md`](c:/laragon/www/SJSEGURIDAD/docs/user/quality-documents.md) |
| Indicadores | [`modules/indicadores.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/indicadores.md) | [`user/indicadores.md`](c:/laragon/www/SJSEGURIDAD/docs/user/indicadores.md) |
| Matriz comercial | [`modules/matriz-clientes.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/matriz-clientes.md) | [`user/matriz-clientes.md`](c:/laragon/www/SJSEGURIDAD/docs/user/matriz-clientes.md) |
| Branding | [`modules/branding.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/branding.md) | — |

## Modulos documentados

- [`modules/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/admin-users.md)
- [`modules/branding.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/branding.md)
- [`modules/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/requisitions.md)
- [`modules/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/suministros.md)
- [`modules/quality-documents.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/quality-documents.md)
- [`modules/indicadores.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/indicadores.md)
- [`modules/matriz-clientes.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/matriz-clientes.md)

## Objetivo de esta documentacion

- Explicar la intencion del sistema
- Describir la arquitectura actual
- Registrar reglas de seguridad y acceso
- Estandarizar la forma de hacer cambios
- Permitir que una IA retome el proyecto con contexto suficiente

## Stack documentado

- `Laravel 13` + `PHP 8.3` (minimo requerido)
- Desarrollo local: `Laragon 8.6+`, `MySQL 8`, `Node.js` + Vite, `Alpine.js` (layout) + JS vanilla por modulo
- Captura de Indicadores: Controllers + Blade + `public/js/indicadores-capture.js` (sin Livewire)
- Correo local: Laragon Mailpit (`smtp` `127.0.0.1:1025`); ver [`LOCAL_SETUP.md`](c:/laragon/www/SJSEGURIDAD/docs/LOCAL_SETUP.md)

## Regla de mantenimiento

Cada vez que se modifique el proyecto, se debe actualizar al menos uno de estos elementos si aplica:

- Contexto del proyecto (`PROJECT_CONTEXT.md`)
- Arquitectura u ownership (`ARCHITECTURE.md`)
- Procedimientos (`PROCEDURES.md`)
- Documentacion del modulo impactado (`docs/modules/` y `docs/user/` si hay cambio visible)
- Indice y guia maestra (`INDEX.md`, `DOCUMENTATION.md`)

Si un cambio afecta autenticacion, permisos, usuarios, base de datos, despliegue o navegacion, la actualizacion documental es obligatoria.

En features gestionadas por multi-agente, el **Documentador** cierra ambas capas de modulo; ver [`DOCUMENTATION.md`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md).
