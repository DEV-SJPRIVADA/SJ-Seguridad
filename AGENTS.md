# Bases del Proyecto

## Objetivo base
- Este repositorio es la base de una plataforma web modular para `SJ Seguridad`, desarrollada bajo un enfoque SCRUM e incremental.
- El stack base debe priorizar compatibilidad con `Laragon`, `Laravel`, `PHP 8.2`, `MySQL/Hostinger` y `JavaScript` orientado a componentes simples y reutilizables.

## Reglas para cualquier cambio futuro
- Antes de modificar una funcionalidad, revisar impacto global en autenticacion, permisos, navegacion, base de datos, pruebas y despliegue.
- No introducir dependencias innecesarias si el mismo resultado puede lograrse con Laravel core, Blade, middleware, policies o JS simple.
- Mantener los modulos por area desacoplados: rutas, controladores, vistas, validaciones y permisos deben poder crecer sin mezclar responsabilidades.
- Todo cambio nuevo debe considerar seguridad, errores probables, validacion de entradas y efectos colaterales sobre usuarios existentes.
- Cuando se agregue una nueva area del negocio, actualizar `config/access.php`, seeders, permisos, navegacion y pruebas relacionadas.
- Toda modificacion funcional debe actualizar la documentacion viva en `docs/` dentro de la misma entrega.
- La documentacion debe permitir que cualquier IA entienda el proyecto, sus modulos, sus dependencias, sus permisos y su estado actual sin depender del historial de chat.
- Si el cambio afecta un modulo existente, actualizar su archivo en `docs/modules/`. Si crea un modulo nuevo, crear su documento correspondiente.

## Seguridad obligatoria
- No habilitar registro publico salvo instruccion expresa del usuario.
- Todo acceso sensible debe quedar protegido por autenticacion y permisos.
- Los usuarios inactivos no pueden operar.
- Las contrasenas temporales deben forzar cambio al primer ingreso cuando aplique.
- Revisar periodicamente logs, pruebas y validaciones de permisos antes de cerrar cada sprint.

## Convenciones actuales
- El control de acceso usa `spatie/laravel-permission`.
- Los roles base son `super-admin`, `administrador`, `coordinador` y `consulta`.
- Los permisos del sistema y por area viven en `config/access.php`.
- La gestion inicial de usuarios esta en el modulo `admin/users`.

## Criterio de revision continua
- Verificar que no se rompan rutas protegidas ni estados de sesion.
- Verificar migraciones nuevas y compatibilidad con despliegue en hosting compartido.
- Verificar pruebas de seguridad y acceso al tocar autenticacion, usuarios o permisos.
- Si un cambio afecta varias capas, documentarlo en el cierre del trabajo.
- Verificar en cada cierre que `README.md`, `docs/INDEX.md` y el documento del modulo afectado sigan alineados con el codigo real.
