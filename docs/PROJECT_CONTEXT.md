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
- Dashboard protegido con navegacion por areas y tableros autorizados
- Perfil de usuario
- Gestion administrativa de usuarios (roles, permisos directos, sede para suministros)
- Area base persistida por usuario (`users.area_key`)
- Roles y permisos basados en configuracion (`config/access.php`, Spatie)
- Restriccion por usuario activo y cambio obligatorio de contrasena temporal
- **Requisiciones de personal:** dashboard, solicitar, seguimiento, gestion, parametros; notificaciones por correo al crear y al cambiar estado
- **Suministros:** mis solicitudes, aprobacion Calidad, insumos aprobados (FO-AD-44), catalogo; sedes; notificacion a Calidad al crear
- **Documentos de Calidad:** biblioteca por area, mis documentos, administracion centralizada
- **Indicadores (Operaciones):** captura FT-OP, dashboard, ajustes (periodos, pesos, auditoria), consolidado MADRE, export PDF/Excel
- **Comercial:** dashboard KPI, matriz clientes (NIT), servicios por portafolio; importacion MT-CO-01
- Modulos base configurados en navegacion: gestion humana, operaciones, programacion, juridico, comercial, calidad, admin y financiero, compras
- Correo local: Laragon Mailpit (`MAIL_MAILER=smtp`, puerto `1025`; ver `docs/LOCAL_SETUP.md`)

## Documentacion por capas

Ver [`docs/DOCUMENTATION.md`](DOCUMENTATION.md):

- **Proyecto / IA:** `PROJECT_CONTEXT`, `ARCHITECTURE`, `ACCESS_CONTROL`, `INDEX`, `AGENTS.md`
- **Modulo tecnico:** `docs/modules/{modulo}.md`
- **Modulo usuario:** `docs/user/{modulo}.md`

El workflow multi-agente actualiza estas capas al cerrar features ([`docs/AGENT_WORKFLOW.md`](AGENT_WORKFLOW.md)).

## Principios del proyecto

- Seguridad primero
- Cambios incrementales y auditables
- Configuracion centralizada de permisos y areas
- Documentacion viva dentro del repositorio
- Identidad visual centralizada mediante tokens corporativos reutilizables

## Entidades base ya presentes

- `users`, `roles`, `permissions`, tablas pivote Spatie
- **Requisiciones:** `personal_requisitions`, `personal_requisition_status_logs`, `personal_requisition_change_logs`, catalogos en `requisition_*`, `requisition_notification_emails`
- **Suministros:** `supply_products`, `supply_requests`, `supply_request_items`, `supply_sites`
- **Calidad documentos:** `quality_documents`, `quality_document_areas`, `quality_document_users`
- **Indicadores:** `indicators`, `indicator_periods`, `indicator_captures`, `dashboard_weights`, `dashboard_summaries`, `improvements`
- **Comercial:** `commercial_clients`, `commercial_services`, catalogos `commercial_*`
- `requisition_clients` (puente interno con matriz comercial)
- tablas de `cache` y `jobs`

## Riesgos principales actuales

- Cualquier cambio en autenticacion o permisos puede bloquear acceso a rutas protegidas
- Cualquier nueva area del negocio requiere sincronizar configuracion, seeders, navegacion y pruebas
- La documentacion puede desalinearse si no se actualiza en la misma tarea
