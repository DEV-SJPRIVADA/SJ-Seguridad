# Control de Acceso

## Base tecnica

El proyecto usa `spatie/laravel-permission` con guard `web`.

## Roles base

- `super-admin`
- `administrador`
- `usuario`

## Permisos del sistema

Definidos en [`config/access.php`](c:/laragon/www/SJSEGURIDAD/config/access.php):

- `view.dashboard`
- `manage.users`
- `manage.requisitions`
- `manage.requisition.parameters`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`

Estos permisos se administran visualmente dentro de la fila `Administracion de usuarios` en la matriz de permisos.

## Areas actuales

- `gestion_humana`
- `operaciones`
- `programacion`
- `juridico`
- `comercial`
- `calidad`
- `remuneraciones`
- `facturacion`
- `compras`

## Acciones por area

- `view`
- `manage`

Esto produce permisos como:

- `view.area.gestion_humana`
- `manage.area.gestion_humana`
- `view.area.operaciones`
- `manage.area.operaciones`

## Tableros por area

Cada area puede tener tableros internos definidos en `config/access.php`. Los tableros base son:

- `dashboard`
- `requisiciones`

Esto produce permisos como:

- `view.board.gestion_humana.dashboard`
- `view.board.gestion_humana.requisiciones`
- `view.board.operaciones.dashboard`
- `view.board.operaciones.requisiciones`

## Asignacion base de roles

Sembrada en [`database/seeders/RoleAndPermissionSeeder.php`](c:/laragon/www/SJSEGURIDAD/database/seeders/RoleAndPermissionSeeder.php):

- `super-admin`: todos los permisos
- `administrador`: `view.dashboard`, `manage.users` y `manage.requisition.parameters`
- `usuario`: `view.dashboard`

Los roles antiguos `coordinador` y `consulta` se migran a `usuario` durante el seeder si existen. Los permisos de areas que ya no esten definidos en `config/access.php` se eliminan para evitar accesos obsoletos.

## Reglas obligatorias

- No habilitar registro publico salvo instruccion expresa
- Todo acceso sensible debe exigir autenticacion y permisos
- Los usuarios inactivos no pueden operar
- Las contrasenas temporales deben obligar cambio al primer ingreso
- Una vez el usuario actualiza correctamente su contrasena, `must_change_password` debe pasar a `false`
- En requisiciones, un permiso explicito de tablero o pestaña habilita el acceso aunque `users.area_key` sea diferente
- `users.area_key` sigue usandose como contexto operativo del usuario, no como filtro oculto para negar pestañas ya autorizadas

## Impacto de cambios

Cuando se agregue una nueva area del negocio, se deben revisar como minimo:

- `config/access.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- rutas
- navegacion
- vistas del modulo
- pruebas relacionadas con permisos
