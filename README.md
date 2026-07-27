# SJ Seguridad

Base de plataforma web modular para `SJ Seguridad`, construida con `Laravel 13`, `PHP 8.3`, `MySQL` y `Blade`.

## Objetivo

Este repositorio sirve como base incremental para una plataforma empresarial con autenticacion, control de acceso por roles y permisos, y modulos desacoplados por area del negocio.

## Stack actual

- `PHP 8.3`
- `Laravel 13`
- `MySQL 8` para desarrollo local con `Laragon 8.6+`
- `Vite 6` + `Node.js` para assets
- `Blade` para interfaz
- `spatie/laravel-permission` para roles y permisos

## Estado funcional base

- Autenticacion web con Breeze
- Dashboard protegido por autenticacion, estado activo y cambio obligatorio de contrasena
- Gestion inicial de usuarios en `admin/users`
- Roles y permisos sembrados desde configuracion
- Modulo de requisiciones de personal con dashboard, solicitud, seguimiento, gestion y parametros
- Modulo compartido de suministros con flujo solicitante, aprobacion Calidad, insumos aprobados y catalogo
- Modulo de documentos de Calidad con publicacion de archivos/enlaces y biblioteca por area
- Indicadores de Operaciones (captura, dashboard, ajustes, consolidado, exportaciones)
- Matriz comercial (dashboard, clientes, servicios)

## Inicio rapido local

1. Verificar que Laragon use `PHP 8.3` y `MySQL` (Laragon 8.6+).
2. Crear o revisar `.env` (ver [`docs/LOCAL_SETUP.md`](docs/LOCAL_SETUP.md)).
3. Confirmar estos valores:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sjseguridad
DB_USERNAME=root
DB_PASSWORD=
CACHE_PREFIX=sj-seguridad-cache-
SESSION_COOKIE=sj_seguridad_session
```

4. Ejecutar:

```powershell
cd C:\laragon\www\SJSEGURIDAD
composer install
npm install && npm run build
php artisan migrate --seed
php artisan app:doctor
php artisan test
```

Desarrollo con hot reload: `.\dev.bat`

Si el entorno local se desalineo y el login vuelve a fallar:

```powershell
php artisan app:stabilize-local
```

## Credenciales iniciales

- Usuario: `admin@sjseguridad.local`
- Clave semilla: `ChangeMe123!`

El usuario administrador inicial queda con rol `super-admin`, activo y sin cambio obligatorio de contrasena.

## Documentacion del proyecto

La documentacion viva del sistema se mantiene en [`docs/INDEX.md`](docs/INDEX.md).

**Guia de documentacion (IA, desarrollador, usuario):** [`docs/DOCUMENTATION.md`](docs/DOCUMENTATION.md).

**Features y modulos nuevos** usan el workflow multi-agente (chat maestro + **AgentSj**). Inicio: `AgentSj [descripcion]`. Ver [`docs/AGENT_WORKFLOW.md`](docs/AGENT_WORKFLOW.md) y [`docs/agents/prompts/start-feature.md`](docs/agents/prompts/start-feature.md). Consultas y fixes pequenos van por carril rapido sin ese flujo.

Documentos principales:

- [`docs/DOCUMENTATION.md`](docs/DOCUMENTATION.md)
- [`docs/PROJECT_CONTEXT.md`](c:/laragon/www/SJSEGURIDAD/docs/PROJECT_CONTEXT.md)
- [`docs/ARCHITECTURE.md`](c:/laragon/www/SJSEGURIDAD/docs/ARCHITECTURE.md)
- [`docs/ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md)
- [`docs/LOCAL_SETUP.md`](c:/laragon/www/SJSEGURIDAD/docs/LOCAL_SETUP.md)
- [`docs/PROCEDURES.md`](c:/laragon/www/SJSEGURIDAD/docs/PROCEDURES.md)
- [`docs/AGENT_WORKFLOW.md`](docs/AGENT_WORKFLOW.md)
- [`docs/user/`](docs/user/) — guias de usuario por modulo
- [`docs/modules/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/admin-users.md)
- [`docs/modules/branding.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/branding.md)
- [`docs/modules/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/requisitions.md)
- [`docs/modules/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/suministros.md)
- [`docs/modules/quality-documents.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/quality-documents.md)

## Regla operativa

Todo cambio funcional futuro debe actualizar la documentacion afectada en la misma entrega para que cualquier IA o desarrollador pueda entender:

- Que hace el modulo
- Que rutas, permisos, tablas y vistas toca
- Que riesgos o dependencias introduce
- Como se opera y valida el cambio
