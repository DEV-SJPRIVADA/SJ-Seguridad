# Arquitectura Actual

## Stack

- Backend: `Laravel 13`
- Lenguaje: `PHP 8.3`
- Base de datos: `MySQL`
- Frontend: `Blade`, `Vite`, `Alpine.js` (layout), `JavaScript` simple por modulo
- Permisos: `spatie/laravel-permission`

## Capa visual reutilizable

- Los estilos base del proyecto se concentran en [`resources/css/app.css`](c:/laragon/www/SJSEGURIDAD/resources/css/app.css)
- La meta actual es reducir HTML cargado de utilidades y mover la interfaz a clases semanticas reutilizables
- Ya existen clases comunes para:
  botones, formularios, tablas, tarjetas, layout, navegacion y paneles de administracion
- Las vistas autenticadas comparten ahora una estructura comun:
  barra superior, panel lateral izquierdo para modulos autorizados y menu horizontal para tableros del modulo activo
- El `dashboard` funciona como pantalla neutra: no carga un modulo por defecto y muestra el area de trabajo vacia hasta que el usuario seleccione un modulo autorizado

## Estructura principal

- `app/Http/Controllers`: controladores por dominio
- `app/Http/Requests`: validaciones por caso de uso
- `app/Http/Middleware`: reglas transversales de acceso
- `app/Models`: modelos Eloquent
- `config/access.php`: permisos del sistema, areas, acciones y definicion central de modulos/tableros de navegacion
- `routes/web.php`: rutas protegidas principales
- `database/migrations`: estructura de datos
- `database/seeders`: datos iniciales de seguridad
- `resources/views`: vistas Blade

## Flujo de acceso base

1. El usuario inicia sesion.
2. Middleware `active` valida que el usuario siga activo.
3. Middleware `password.changed` obliga cambio de contrasena si corresponde.
4. El sistema evalua permisos por rol y permisos directos.
5. Las rutas administrativas exigen `permission:manage.users`.

## Rutas principales

- `/`
  Vista publica de bienvenida.
- `/dashboard`
  Requiere `auth`, `active` y `password.changed`.
- `/profile`
  Requiere `auth` y `active`.
- `/admin/users`
  Requiere `auth`, `active`, `password.changed` y `permission:manage.users`.

## Componentes de seguridad ya implementados

- `app/Http/Middleware/EnsureUserIsActive.php`
  Cierra la sesion si el usuario esta inactivo.
- `app/Http/Middleware/EnsurePasswordIsChanged.php`
  Obliga a pasar por cambio de contrasena cuando `must_change_password` es verdadero.
- `app/Models/User.php`
  Mantiene campos de seguridad como `is_active`, `must_change_password`, `last_login_at` y `created_by`.

## Configuracion central relevante

- `config/access.php`
  Define permisos de sistema, areas del negocio y acciones por area.
- `config/permission.php`
  Configuracion del paquete Spatie.
- `.env`
  Define conexion local a `MySQL`.

## Decisiones arquitectonicas

- Los permisos no se hardcodean en vistas o controladores sin respaldo en configuracion central.
- La gestion inicial de usuarios vive aislada en `admin/users`.
- La navegacion de modulos y tableros autorizados se resuelve desde configuracion central y se comparte al layout base.
- El crecimiento por areas debe mantener rutas, permisos, vistas y validaciones desacopladas.
