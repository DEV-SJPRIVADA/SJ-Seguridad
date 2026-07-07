# SJ Seguridad

Base de plataforma web modular para `SJ Seguridad`, construida con `Laravel 11`, `PHP 8.2`, `MySQL` y `Blade`.

## Objetivo

Este repositorio sirve como base incremental para una plataforma empresarial con autenticacion, control de acceso por roles y permisos, y modulos desacoplados por area del negocio.

## Stack actual

- `PHP 8.2`
- `Laravel 11.51`
- `MySQL 8` para desarrollo local con `Laragon`
- `Blade` para interfaz
- `spatie/laravel-permission` para roles y permisos

## Estado funcional base

- Autenticacion web con Breeze
- Dashboard protegido por autenticacion, estado activo y cambio obligatorio de contrasena
- Gestion inicial de usuarios en `admin/users`
- Roles y permisos sembrados desde configuracion
- Modulo de requisiciones de personal con dashboard, solicitud, seguimiento, gestion y parametros
- Modulo compartido de suministros con flujo solicitante, revision de calidad, gestion de compras y catalogo

## Inicio rapido local

1. Verificar que Laragon use `PHP 8.2` y `MySQL`.
2. Crear o revisar `.env`.
3. Confirmar estos valores:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sjseguridad
DB_USERNAME=root
DB_PASSWORD=
```

4. Ejecutar:

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan config:clear
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan migrate --seed
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan app:doctor
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan serve
```

Si el entorno local se desalineo y el login vuelve a fallar:

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan app:stabilize-local
```

## Credenciales iniciales

- Usuario: `admin@sjseguridad.local`
- Clave semilla: `ChangeMe123!`

El usuario administrador inicial queda con rol `super-admin`, activo y sin cambio obligatorio de contrasena.

## Documentacion del proyecto

La documentacion viva del sistema se mantiene en [`docs/INDEX.md`](c:/laragon/www/SJSEGURIDAD/docs/INDEX.md).

Documentos principales:

- [`docs/PROJECT_CONTEXT.md`](c:/laragon/www/SJSEGURIDAD/docs/PROJECT_CONTEXT.md)
- [`docs/ARCHITECTURE.md`](c:/laragon/www/SJSEGURIDAD/docs/ARCHITECTURE.md)
- [`docs/ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md)
- [`docs/LOCAL_SETUP.md`](c:/laragon/www/SJSEGURIDAD/docs/LOCAL_SETUP.md)
- [`docs/PROCEDURES.md`](c:/laragon/www/SJSEGURIDAD/docs/PROCEDURES.md)
- [`docs/modules/admin-users.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/admin-users.md)
- [`docs/modules/branding.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/branding.md)
- [`docs/modules/requisitions.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/requisitions.md)
- [`docs/modules/suministros.md`](c:/laragon/www/SJSEGURIDAD/docs/modules/suministros.md)

## Regla operativa

Todo cambio funcional futuro debe actualizar la documentacion afectada en la misma entrega para que cualquier IA o desarrollador pueda entender:

- Que hace el modulo
- Que rutas, permisos, tablas y vistas toca
- Que riesgos o dependencias introduce
- Como se opera y valida el cambio
