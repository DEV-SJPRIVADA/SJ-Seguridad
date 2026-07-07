# Documentacion Viva

Este directorio concentra el contexto necesario para que cualquier desarrollador o IA entienda el proyecto sin depender del historial del chat.

## Orden recomendado de lectura

1. [`PROJECT_CONTEXT.md`](c:/laragon/www/SJSEGURIDAD/docs/PROJECT_CONTEXT.md)
2. [`ARCHITECTURE.md`](c:/laragon/www/SJSEGURIDAD/docs/ARCHITECTURE.md)
3. [`ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md)
4. [`LOCAL_SETUP.md`](c:/laragon/www/SJSEGURIDAD/docs/LOCAL_SETUP.md)
5. [`PROCEDURES.md`](c:/laragon/www/SJSEGURIDAD/docs/PROCEDURES.md)
6. Modulos en [`docs/modules`](c:/laragon/www/SJSEGURIDAD/docs/modules)

## Modulos documentados

- [`modules/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/admin-users.md)
- [`modules/branding.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/branding.md)
- [`modules/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/requisitions.md)
- [`modules/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/suministros.md)

## Objetivo de esta documentacion

- Explicar la intencion del sistema
- Describir la arquitectura actual
- Registrar reglas de seguridad y acceso
- Estandarizar la forma de hacer cambios
- Permitir que una IA retome el proyecto con contexto suficiente

## Regla de mantenimiento

Cada vez que se modifique el proyecto, se debe actualizar al menos uno de estos elementos si aplica:

- Contexto del proyecto
- Arquitectura
- Procedimientos
- Documentacion del modulo impactado

Si un cambio afecta autenticacion, permisos, usuarios, base de datos, despliegue o navegacion, la actualizacion documental es obligatoria.
