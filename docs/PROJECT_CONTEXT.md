# Contexto del Proyecto

## Resumen ejecutivo

`SJ Seguridad` es una plataforma web modular orientada a crecer por areas del negocio bajo un enfoque incremental. La base actual prioriza seguridad, control de acceso y compatibilidad con entornos locales en `Laragon` y despliegues compatibles con `Hostinger`.

## Objetivos tecnicos vigentes

- Mantener una base estable sobre `Laravel 13`
- Usar `PHP 8.3` como version objetivo (minimo requerido por Laravel 13)
- Trabajar con `MySQL` como motor principal
- Evitar dependencias innecesarias
- Construir modulos desacoplados por area

## Alcance actual implementado

- Inicio de sesion y recuperacion de contrasena
- Dashboard protegido
- Perfil de usuario
- Gestion administrativa de usuarios
- Area base persistida por usuario (`users.area_key`)
- Roles y permisos basados en configuracion
- Restriccion por usuario activo
- Cambio obligatorio de contrasena temporal
- Modulos base configurados: gestion humana, operaciones, programacion, juridico, comercial, calidad, admin y financiero, y compras
- Modulo inicial de requisiciones de personal con subtableros de dashboard, solicitud, seguimiento, gestion y parametros
- Notificaciones por correo al crear requisiciones (destinatarios: catalogo `requisition_notification_emails` en Parametros)
- Notificaciones por correo al solicitante cuando cambia el estado de una requisicion
- Modulo compartido de suministros con subtableros de mis solicitudes, revision de calidad, gestion de compras y catalogo
- Notificaciones por correo al crear solicitudes de suministros (destinatarios: usuarios con permiso de Calidad)
- Correo local de desarrollo orientado a Laragon Mailpit (`MAIL_MAILER=smtp`, puerto `1025`; ver `docs/LOCAL_SETUP.md`)
- Modulo compartido de documentos de Calidad con biblioteca por area y administracion centralizada
- Matriz de clientes MT-CO-01 en Comercial (`commercial_clients` + `commercial_services` por portafolio)

## Principios del proyecto

- Seguridad primero
- Cambios incrementales y auditables
- Configuracion centralizada de permisos y areas
- Documentacion viva dentro del repositorio
- Identidad visual centralizada mediante tokens corporativos reutilizables

## Entidades base ya presentes

- `users`
- `personal_requisitions`
- `personal_requisition_status_logs`
- `supply_products`
- `supply_requests`
- `supply_request_items`
- `quality_documents`
- `quality_document_areas`
- `roles`
- `permissions`
- tablas pivote de permisos del paquete `spatie/laravel-permission`
- tablas de `cache` y `jobs`

## Riesgos principales actuales

- Cualquier cambio en autenticacion o permisos puede bloquear acceso a rutas protegidas
- Cualquier nueva area del negocio requiere sincronizar configuracion, seeders, navegacion y pruebas
- La documentacion puede desalinearse si no se actualiza en la misma tarea
