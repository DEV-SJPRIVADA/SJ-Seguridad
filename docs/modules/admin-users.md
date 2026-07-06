# Modulo Admin Users

## Objetivo

Gestionar usuarios internos del sistema, incluyendo rol principal, permisos directos, estado activo y cambio obligatorio de contrasena.

## Alcance actual

- Panel lateral con listado paginado y busqueda de usuarios
- Panel dividido con listado lateral y resumen operativo del usuario seleccionado
- Creacion de usuarios
- Edicion de usuarios
- Asignacion de rol
- Asignacion de area base (`users.area_key`)
- Asignacion de permisos directos
- Activacion o desactivacion
- Forzar cambio de contrasena
- Creacion con contrasena temporal sin confirmacion; el usuario debe renovarla en su primer ingreso cuando `must_change_password` este activo
- Matriz visible de visualizacion y gestion por area
- Matriz de tableros por area; inicialmente cada modulo tiene `Dashboard` y `Requisiciones`
- Matriz tabular con filtros por modulo y texto para administrar crecimiento de permisos

## Rutas

Definidas en [`routes/web.php`](c:/laragon/www/SJSEGURIDAD/routes/web.php):

- `GET /admin/users`
- `GET /admin/users/create`
- `POST /admin/users`
- `GET /admin/users/{user}/edit`
- `PUT/PATCH /admin/users/{user}`

## Middleware y permisos

- `auth`
- `active`
- `password.changed`
- `permission:manage.users`

## Controladores y requests

- [`app/Http/Controllers/Admin/UserController.php`](c:/laragon/www/SJSEGURIDAD/app/Http/Controllers/Admin/UserController.php)
- [`app/Http/Requests/Admin/StoreUserRequest.php`](c:/laragon/www/SJSEGURIDAD/app/Http/Requests/Admin/StoreUserRequest.php)
- [`app/Http/Requests/Admin/UpdateUserRequest.php`](c:/laragon/www/SJSEGURIDAD/app/Http/Requests/Admin/UpdateUserRequest.php)

## Vistas relacionadas

- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/partials/form.blade.php`
- `resources/css/app.css`

## Tablas implicadas

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

## Reglas de negocio actuales

- Solo usuarios con `manage.users` pueden operar el modulo
- El correo se almacena en minusculas
- El usuario puede crearse inactivo
- El usuario puede quedar marcado para cambio obligatorio de contrasena
- En creacion se captura una sola contrasena temporal; no se solicita confirmacion porque el usuario final debe cambiarla al ingresar
- En edicion, la contrasena es opcional y tampoco exige confirmacion; si se define una nueva, se desactiva `must_change_password`
- El rol `super-admin` tiene acceso total a cualquier habilidad del sistema mediante regla global en `Gate::before`
- El rol `administrador` gestiona usuarios, roles y permisos, pero sus permisos operativos por area deben asignarse explicitamente
- La eliminacion de cuentas no se expone en perfil para usuarios normales; queda restringida al rol `super-admin`
- El panel principal muestra accesos efectivos del usuario seleccionado para facilitar auditoria visual
- La presentacion del modulo se apoya en clases CSS reutilizables para evitar dependencia excesiva de utilidades inline
- El modulo se integra al layout comun de la aplicacion: aparece como opcion lateral autorizada y expone tableros horizontales segun permisos
- Los permisos del sistema se muestran dentro de la fila `Administracion de usuarios`; los tableros de negocio se habilitan por columna en la misma matriz
- La administracion y auditoria de permisos usan una tabla jerarquica por modulo con filtros en cliente para buscar rapidamente por area, permiso o codigo
- La matriz de permisos solo se expone en creacion y edicion de usuario; la pantalla principal de `admin/users` se reserva para contexto operativo y acceso rapido a editar
- Cada fila del listado principal selecciona al usuario y muestra su resumen de areas y permisos; desde el panel derecho se entra a `Editar usuario`
- En edicion y creacion, la tabla de permisos prioriza lectura operativa: muestra nombre del permiso y asignacion, sin columna separada de codigo tecnico

## Riesgos

- Cambios en permisos o roles impactan este modulo de forma directa
- Un cambio incorrecto en requests o middleware puede exponer gestion de usuarios
- Si se agregan campos nuevos al usuario, este modulo debe documentarse y validarse de nuevo
- El campo `area_key` impacta modulos dependientes del area propia del usuario, especialmente `Requisiciones`
