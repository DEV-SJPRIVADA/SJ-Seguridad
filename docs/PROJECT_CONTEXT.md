# Contexto del Proyecto

## Resumen ejecutivo

`SJ Seguridad` es una plataforma web modular orientada a crecer por areas del negocio bajo un enfoque incremental. La base actual prioriza seguridad, control de acceso y compatibilidad con entornos locales en `Laragon` y despliegues compatibles con `Hostinger`.

## Objetivos tecnicos vigentes

- Mantener una base estable sobre `Laravel 11`
- Usar `PHP 8.2` como version objetivo
- Trabajar con `MySQL` como motor principal
- Evitar dependencias innecesarias
- Construir modulos desacoplados por area

## Alcance actual implementado

- Inicio de sesion y recuperacion de contrasena
- Dashboard protegido
- Perfil de usuario
- Gestion administrativa de usuarios
- Roles y permisos basados en configuracion
- Restriccion por usuario activo
- Cambio obligatorio de contrasena temporal
- Modulos base configurados: gestion humana, operaciones, programacion, juridico, comercial, calidad, remuneraciones, facturacion y compras

## Principios del proyecto

- Seguridad primero
- Cambios incrementales y auditables
- Configuracion centralizada de permisos y areas
- Documentacion viva dentro del repositorio
- Identidad visual centralizada mediante tokens corporativos reutilizables

## Entidades base ya presentes

- `users`
- `roles`
- `permissions`
- tablas pivote de permisos del paquete `spatie/laravel-permission`
- tablas de `cache` y `jobs`

## Riesgos principales actuales

- Cualquier cambio en autenticacion o permisos puede bloquear acceso a rutas protegidas
- Cualquier nueva area del negocio requiere sincronizar configuracion, seeders, navegacion y pruebas
- La documentacion puede desalinearse si no se actualiza en la misma tarea
