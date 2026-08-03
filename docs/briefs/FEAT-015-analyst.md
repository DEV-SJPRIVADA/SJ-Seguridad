# Preguntas del Analista — FEAT-015

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario (Manuel).

## Contexto recibido

**Feature ID:** FEAT-015  
**Origen:** `@agent-sj` (2026-07-29) — fase 2 de alertas documentales comercial (FEAT-014 dejó correos **fuera**; FEAT-013 entrega catálogo global de tipos + destinatarios).

**Solicitud de negocio:** el sistema debe **revisar a diario** qué clientes tienen documentación **por vencer** y enviar **correo** a las direcciones configuradas en **Administración → Configuración de notificaciones** (permiso `manage.notifications`), igual que los avisos de requisiciones.

**Pantalla de referencia:** checklist documental (`/comercial/clientes/checklist-documental`, filtro «Por vencer» / `doc_vigencia=expiring`).

**Campos por cliente (NIT):**

| Etiqueta UI | Campo | Notas |
| --- | --- | --- |
| Vencimiento | `commercial_clients.documentation_expires_on` | Fecha única documental del cliente |
| Días | `commercial_clients.alert_days_before` | Anticipación; default **30** vía catálogo si vacío |

**Ejemplo del usuario:** cliente con **30 días** de anticipación y vencimiento **29/08/2026**; espera aviso **hoy** (fecha de trabajo del equipo: **29/07/2026**). Hay que **validar** contra la regla ya implementada en código (ver abajo).

**Estado técnico relevante (repo):**

| Aspecto | Comportamiento actual |
| --- | --- |
| Regla «por vencer» | `CommercialClient::isDocumentationExpiringSoon()` y `scopeDocumentationExpiring()` — misma lógica que filtro checklist |
| «Vencida» | `isDocumentationExpired()` / `scopeDocumentationExpired()` — fecha &lt; hoy |
| Destinatarios | `NotificationConfigService::recipientEmails($module, $slug)`; fallback `config('notifications.fallback_recipient')` → `desarrollo.tic@sjsp.com.co` |
| Tipos comercial | **No existen** aún en BD; FEAT-014 documentó slug futuro `module=comercial`, `documentation_expiring` (nombre afinable) |
| Admin UI tipos | Solo slugs listados en `config/notifications.php` → `admin_configurable` (hoy solo `requisitions` / `new_requisition`) |
| Programación | `routes/console.php` **sin** `Schedule::` para este dominio; hace falta comando/job + scheduler |
| Envío correo | Patrón requisiciones: `Mail::to(...)->send(new …Mailable…)` tras resolver emails del servicio |
| Tests | `CommercialMatrixTest` (vigencia UI/servicios); `NotificationConfigTest` (admin + resolución); **sin** tests de job comercial |

---

## Entendimiento del analista (resumen)

Comercial ya ve en checklist qué clientes están **por vencer** o **vencidos** según fecha y días por NIT. FEAT-015 **automatiza** ese mismo criterio (idealmente **idéntico** al filtro «Por vencer») con una **corrida diaria** que dispara correos usando la **capa global FEAT-013**: nuevo **tipo de aviso** del módulo comercial, visible en Configuración de notificaciones, con destinatarios asignables por el administrador.

No se pide cambio de permisos de matriz (`comercial.matriz.view` / `manage`) ni nueva pantalla comercial en v1; sí **seed/migración** del tipo, **config** (`notifications.modules`, `admin_configurable`), **Mailable**, **comando o job** y **Schedule** en producción (cron `schedule:run`).

**Regla de calendario ya codificada (por vencer):**

- Requiere `documentation_expires_on` **no nula**.
- **No** está vencida: fecha de vencimiento ≥ **hoy** (inicio de día, timezone app).
- Está en ventana: vencimiento ≤ **hoy + N días**, donde **N** = `alert_days_before` del cliente o **30** por defecto (`documentationAlertDays()`).

En fórmula: vencimiento ∈ **[hoy, hoy + N]** (ambos extremos **inclusivos**), alineado a FEAT-014 regla 4 y al filtro `documentationExpiring`.

**Validación del ejemplo del usuario (29/07/2026, N=30, vence 29/08/2026):**

Con la implementación actual (`Carbon::addDays(30)` sobre «hoy»):

- Límite superior = **28/08/2026**.
- Vencimiento **29/08/2026** **no** cumple `expires <= limit` el **29/07/2026** → **no** aparece en «Por vencer» ese día.
- Entraría en ventana a partir del **30/07/2026** (límite 29/08/2026).

Si el negocio esperaba aviso el 29/07, la interpretación deseada podría ser otra (p. ej. «días restantes ≤ N» contando distinto). **Debe confirmarse** (pregunta 1).

**Clientes sin fecha:** no generan alerta UI ni deberían generar correo (salvo que negocio diga lo contrario).

**Clientes solo en portafolio inactivo:** FEAT-014 no excluyó al cliente del checklist; el job debería definir si notifica **todo NIT con fecha en ventana** o solo clientes con **servicios operativos vigentes** (pregunta 9).

---

## Preguntas abiertas (priorizadas — negocio, Manuel)

Responde para cerrar el brief. Las marcadas **(crítica)** bloquean diseño de deduplicación y contenido del correo.

### Regla y alcance de clientes

1. **(crítica) Ventana «por vencer»**  
   ¿Confirmamos que el correo debe usar **exactamente** la misma regla que el filtro checklist «Por vencer» (vencimiento entre **hoy** y **hoy + días de anticipación**, inclusive)?  
   En el ejemplo **29/07/2026**, **30 días**, vence **29/08/2026**: con la regla actual del sistema el aviso empezaría el **30/07**, no el 29. ¿Es correcto o quieren que «30 días» signifique «faltan **≤ 30 días** calendario hasta el vencimiento» (otra fórmula)?
   esta bien que empiece el 30

2. **(crítica) Documentación ya vencida**  
   La solicitud habla solo de **por vencer**. ¿También enviamos correo cuando la documentación está **vencida** (`documentation_expires_on` &lt; hoy)?  
   - ¿Mismo tipo de aviso o **tipo separado** en Configuración de notificaciones (p. ej. «documentación comercial vencida»)?  
   - ¿Con la misma frecuencia/deduplicación que «por vencer»?
   tambien notificar
3. **Fecha de vencimiento nula**  
   ¿Nunca se envía correo si no hay fecha cargada (coherente con checklist)?
   si no hay fecha cargada, no se realiza notificacion.
### Frecuencia, deduplicación y contenido

4. **(crítica) Mientras el cliente sigue en la ventana**  
   Si un cliente permanece «por vencer» durante varios días (p. ej. 30 días seguidos), ¿qué esperan?  
   - **A)** Un correo **cada día** mientras siga en ventana  
   - **B)** **Un solo** aviso la primera vez que entra en ventana (por «ciclo» de esa fecha de vencimiento)  
   - **C)** Recordatorio cada **X** días (indicar X)  
   - **D)** Otro (describir)
 opcion b
5. **(crítica) Un correo vs muchos**  
   ¿Prefieren **un correo por cliente** en riesgo (varios correos el mismo día si hay muchos clientes) o **un digest diario** (un correo con la **lista** de todos los clientes por vencer ese día)?
   un correo con la **lista** de todos los clientes por vencer ese día
6. **Destinatarios y contenido**  
   Los correos configurados en admin: ¿**todos** reciben el **mismo** mensaje (misma lista de clientes en digest, o misma alerta por cliente)? ¿Debe incluirse en el cuerpo: **NIT**, **razón social**, **fecha de vencimiento**, **días restantes**, enlace directo al **checklist** (filtrado)? ¿Algo más (ciudad, estado resumen del checklist)?
   Debe incluirse en el cuerpo: **NIT**, **razón social**, **fecha de vencimiento**, **días restantes**, enlace directo al **checklist** (filtrado)
7. **Hora y zona horaria de la revisión diaria**  
   ¿A qué **hora** debe correr la revisión y en qué zona? Propuesta técnica: **06:00 America/Bogota** (Colombia). ¿Les sirve?
   si
### Configuración FEAT-013 y operación

8. **(crítica) Sin destinatarios asignados al tipo**  
   Si el tipo comercial no tiene correos activos en admin, ¿mantenemos el **fallback** actual a `desarrollo.tic@sjsp.com.co` (como requisiciones) o **no enviar** y solo registrar en log?
   No enviar y solo registrar en log
9. **Qué clientes entran al barrido**  
   ¿Todos los registros en `commercial_clients` con fecha en ventana, o solo clientes con al menos un **servicio operativo** (portafolio ≠ inactivos y contrato no vencido), alineado a KPIs/dashboard?
   si notificar solo clientes que tengan al menos un servicio activo.
10. **Nombre del aviso en admin**  
    Etiqueta legible propuesta: **«Documentación comercial por vencer»** (módulo **Comercial**). ¿Texto de descripción corta para el administrador (qué dispara el aviso)?
    si
### Fuera de alcance (confirmar)

11. ¿Confirmamos que **no** se pide en v1: historial de envíos en admin, notificaciones in-app, SMS, editar plantillas desde UI, ni avisar al asesor comercial del servicio por usuario del sistema?
ahun no
12. **Documentación usuario:** ¿Existe procedimiento operativo (quién actúa al recibir el correo, plazo de actualización documental) que debamos reflejar en `docs/user/`?
no hay plazo actualmente
---



## Fuera de alcance (propuesta analista — confirmar)

- Historial de envíos y reintentos desde admin (FEAT-013 ya lo dejó fuera).
- Alta manual de tipos desde UI (solo migración/seed).
- Cambiar umbrales o fechas por correo (siguen editándose en checklist).
- Notificar al **solicitante** o usuarios Spatie individuales (solo lista global admin).
- Export Excel del barrido (no solicitado).

---

## Estado

- [x] Preguntas criticas respondidas (Manuel 2026-07-29) — listo para Arquitecto
- [x] Brief final Arquitecto — [`FEAT-015.md`](FEAT-015.md)
- [ ] Brief final aprobado (Usuario)

## Respuestas del usuario

| # | Respuesta |
| --- | --- |
| 1 | **Igual que checklist actual** (sin cambiar formula; ejemplo 29/07 + vence 29/08 + 30 dias → aviso desde **30/07**) |
| 2 | **Si vencida** — **mismo tipo de aviso** en Configuracion de notificaciones (un solo slug/tipo) |
| 4 | **Un solo aviso** la primera vez que el cliente entra en ventana «por vencer» (por ciclo de esa fecha de vencimiento); no recordatorio diario mientras sigue en ventana |
| 5 | **Digest** — un correo con lista de clientes (no un correo por cliente) |
| 7 | *(pendiente confirmar hora)* Propuesta **06:00 America/Bogota** salvo objecion |
| 8 | *(pendiente)* Default FEAT-013: fallback `desarrollo.tic@sjsp.com.co` |

