# Administracion de usuarios — Guia de usuario

## Objetivo

Permitir a los administradores del sistema crear y mantener cuentas de usuarios internos, asignarles permisos de acceso y controlar su estado (activo/inactivo y cambio de contrasena).

## Alcance

Esta guia aplica al modulo **Administracion de usuarios**, accesible desde el menu de administracion para quienes tienen permiso de gestionar usuarios.

El administrador puede:

- Ver el listado de usuarios y buscar por nombre o correo
- Crear nuevos usuarios
- Editar datos del usuario y sus permisos
- Activar o desactivar cuentas
- Indicar si el usuario debe cambiar la contrasena en el proximo ingreso

Queda fuera de alcance de este modulo la gestion de contenido de otros tableros (requisiciones, suministros, etc.); aqui solo se configura **quien puede acceder** a que funciones.

## Definiciones

| Termino | Significado |
| --- | --- |
| Usuario activo | Puede iniciar sesion en la plataforma. |
| Usuario inactivo | No puede iniciar sesion; la sesion se cierra si se desactiva mientras usa el sistema. |
| Rol | Conjunto base de permisos (por ejemplo super-admin, administrador, usuario). |
| Permiso directo | Permiso adicional asignado a un usuario concreto, mas alla de su rol. |
| Area asignada | Departamento base del usuario (gestion humana, operaciones, comercial, etc.). |
| Cambio obligatorio de contrasena | El usuario debe definir una nueva clave antes de usar el sistema. |
| Permisos en su area | Acciones que el usuario puede hacer dentro de su area asignada (por ejemplo solicitar requisiciones). |
| Funcionalidades transversales | Permisos que aplican en varias areas (gestion de requisiciones GH, suministros Calidad/Compras, administracion). |
| Visualizacion de otras areas | Permite ver tableros de otras areas del negocio segun la matriz configurada. |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Super-admin / Administrador con `manage.users` | Crear, editar y desactivar usuarios; asignar permisos y roles. |
| Usuario estandar | No accede a este modulo; solo usa las funciones que le hayan sido asignadas. |
| Soporte / capacitacion | Usar esta guia para orientar a administradores en altas y cambios de acceso. |

## Desarrollo

### Acceder al modulo

1. Inicie sesion con una cuenta que tenga permiso de administrar usuarios.
2. En el menu lateral, abra la seccion de **Administracion** o **Usuarios** (segun la navegacion de su instalacion).
3. Se mostrara el listado de usuarios.

### Consultar usuarios

1. En el listado, use el campo de busqueda para filtrar por nombre o correo.
2. Por defecto solo se muestran usuarios **activos**.
3. Marque **Mostrar usuarios inactivos** si necesita ver cuentas deshabilitadas.
4. Seleccione un usuario del listado para ver su resumen y permisos efectivos.

### Crear un usuario

1. Pulse **Crear usuario** o equivalente.
2. En la pestana **Usuario**, complete: nombre, correo, area asignada, rol y contrasena temporal si aplica.
3. Indique si el usuario debe **cambiar la contrasena** en el primer ingreso.
4. En la pestana **Que puede hacer**, asigne permisos en tres bloques:
   - **En su area asignada:** acciones propias de su departamento.
   - **Funcionalidades transversales:** gestion GH, Calidad, Compras, admin, documentos, etc.
   - **Activa visualizacion de otras areas:** tableros adicionales por area.
5. Revise los avisos de coherencia que muestre el sistema (son recomendaciones, no bloqueos).
6. Guarde el formulario.

### Editar un usuario

1. Localice al usuario en el listado y abra **Editar**.
2. Modifique datos personales, area, rol o estado activo segun necesite.
3. Ajuste permisos en la pestana **Que puede hacer**.
4. Guarde los cambios.

### Desactivar un usuario

1. Edite el usuario.
2. Desmarque o cambie el estado a **inactivo**.
3. Guarde. El usuario no podra volver a iniciar sesion.

### Buenas practicas

- Asigne solo los permisos necesarios para el puesto.
- Use contrasenas temporales y exija cambio en el primer ingreso para cuentas nuevas.
- Desactive cuentas de personal que ya no pertenezca a la organizacion.
- Ante dudas sobre un permiso transversal, consulte con el responsable del area (GH, Compras, Calidad, etc.).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial — guia de usuario Admin Users |
| 1.1 | 2026-07-22 | Alineacion documental | Sincronizada con matriz DOCUMENTATION.md |
