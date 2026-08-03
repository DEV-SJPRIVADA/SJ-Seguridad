# Configuracion de notificaciones — Guia de usuario

## Objetivo

Permitir a los administradores definir **quien recibe cada correo automatico** del sistema (requisiciones, documentacion comercial, etc.).

## Alcance

Aplica a **Administracion → Configuracion de notificaciones**, para usuarios con permiso `manage.notifications`.

El administrador puede:

- Ver un resumen de avisos configurados y pendientes
- Consultar el correo de **respaldo** del sistema
- Buscar avisos por nombre o modulo
- Filtrar avisos con o sin destinatarios
- Agregar y quitar correos por tipo de aviso

Queda fuera de alcance: crear nuevos tipos de aviso (los define el sistema), editar plantillas de correo o ver historial de envios.

## Definiciones

| Termino | Significado |
| --- | --- |
| Aviso / tipo de notificacion | Evento del sistema que dispara un correo (ej. nueva requisicion). |
| Destinatario | Correo que recibe el aviso cuando ocurre el evento. |
| Correo de respaldo | Direccion usada si el aviso no tiene destinatarios configurados. |
| Modulo | Area de negocio que agrupa avisos (Requisiciones, Comercial, etc.). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Usuario con `manage.notifications` | Mantener destinatarios actualizados por aviso. |
| Super-admin / TI | Verificar correo de respaldo en configuracion del servidor si aplica. |
| Usuario estandar | No accede a esta pantalla. |

## Desarrollo

### Acceder

1. Inicie sesion con permiso de configurar notificaciones.
2. En el menu **Administracion**, abra **Configuracion de notificaciones**.

### Revisar el resumen

1. En la parte superior vea los indicadores: modulos, avisos, configurados y sin destinatarios.
2. Anote el **correo de respaldo** mostrado en la cabecera; se usara cuando un aviso quede vacio.

### Configurar destinatarios de un aviso

1. En el grid de modulos, pulse la tarjeta del area (ej. Requisiciones o Comercial).
2. En la lista lateral seleccione el aviso deseado.
3. En **Destinatarios**, revise los correos actuales (chips).
4. Para quitar uno, pulse **×** en el chip y confirme.
5. En **Agregar correo**, escriba la direccion y pulse **Agregar**.
6. Tras guardar, la pantalla permanece en el mismo aviso.

### Buscar avisos pendientes

1. Use el campo **Buscar aviso o modulo**.
2. En el filtro de estado elija **Sin destinatarios** para localizar avisos que aun usan el respaldo.

## Control de cambios

| Fecha | Cambio |
| --- | --- |
| 2026-08-03 | Guia inicial alineada al rediseño de pantalla (grid, detalle, chips). |
