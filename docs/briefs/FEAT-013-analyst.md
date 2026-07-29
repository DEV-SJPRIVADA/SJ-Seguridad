# Preguntas del Analista — FEAT-013

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-013  
**Origen:** `@agent-sj` (2026-07-29)

1. **Bug reportado:** en **Requisiciones → Gestión humana → Parámetros**, la sección **Tipos de notificación** no muestra contenido útil (vacía o sin tipos).
2. **Producto deseado:**
   - Dejar de gestionar tipos de notificación (y el enfoque actual) en **Parámetros de GH**.
   - Nuevo tablero en **Super Admin** (nomenclatura del negocio): **Configuración de notificaciones** (o similar).
   - **Ver** las notificaciones que el sistema va **creando/registrando**.
   - **Asignar correos** destinatarios por cada notificación/tipo.
   - Integrar lo que hoy es **Correos de notificación** en Parámetros de requisiciones dentro de esta configuración **global**.

**Estado técnico hoy (repo):**

| Aspecto | Comportamiento actual |
| --- | --- |
| Catálogo de correos | `requisition_notification_emails`; CRUD en Parámetros requisiciones vía `PARAMETER_TYPES['emails']` (**todas** las áreas con tab Parámetros, no solo GH) |
| Tipos de aviso | `requisition_notification_types` + pivot `req_notif_type_email`; seed en migración `2026_07_28_162227_*` (`new_requisition`, `management_approval_cargo_nuevo`) |
| UI tipos | Solo si `{module} === gestion_humana`: tarjeta + partial `notification-types.blade.php`; PATCH `requisitions.notification-types.sync` |
| Envío | `RequisitionNotificationRecipientService::emailsForType($slug)`; fallback `desarrollo.tic@sjsp.com.co` |
| Otros correos | Cambio de estado → solo email del solicitante; suministros → flujo propio (`SupplyRequestNotification`); **no** usa este catálogo |
| Navegación admin | `config/access.php` → `navigation.administracion` (permiso `manage.users`): Usuarios / Nuevo usuario; **no** existe área sidebar `super-admin` (sí rol Spatie `super-admin`) |
| Tests | Cobertura de **envío** por tipo (`RequisitionModuleTest`); **no** hay test de pantalla Parámetros → Tipos de notificación |

---

## Entendimiento del analista (resumen)

El negocio quiere **centralizar** la operación que hoy está repartida entre el catálogo **Correos de notificación** (Parámetros de requisiciones) y la asignación **Tipos de notificación** (solo GH): un único lugar bajo **Administración**, accesible por quien administra la plataforma, para mantener **destinatarios** y **qué evento del sistema** dispara cada aviso.

El bug en GH es coherente con un despliegue donde **aún no corrió** la migración de tipos, o donde la tabla quedó **sin filas seed**: la UI renderiza la sección pero el `@foreach` de tipos no pinta paneles (solo título y texto introductorio; la tarjeta del grid puede decir **«0 tipos»**). No hay evidencia de fallo de JavaScript al abrir la sección (`section-notification-types` está cableada igual que encargados de selección).

La frase **«notificaciones que el sistema va creando/registrando»** admite dos lecturas: (A) **catálogo de tipos de evento** (definidos por desarrollo/migraciones, visibles y configurables en admin) o (B) **historial/log de correos enviados**. v1 debe cerrar cuál aplica.

Mover la funcionalidad implica **retirar** de Parámetros GH la tarjeta Tipos de notificación y, muy probablemente, **retirar o reubicar** el catálogo Correos de notificación; impacto en permisos (`manage.requisition.parameters` vs `manage.users`), rutas admin (`routes/web.php` grupo `admin.*`) y documentación FEAT-012 en `docs/modules/requisitions.md`.

---

## Causa probable del bug (sección vacía)

| # | Hipótesis | Evidencia | Cómo validar (operación / Arquitecto) |
| --- | --- | --- | --- |
| 1 | **Migración `2026_07_28_162227` no ejecutada** en el entorno donde se reporta | Tipos solo existen por seed en esa migración; sin tabla/filas → lista vacía | `php artisan migrate:status`; contar filas en `requisition_notification_types` |
| 2 | **Tabla existe pero sin seed** (migración parcial, rollback, BD restaurada antigua) | Mismo síntoma: `typesWithAssignedEmailIds()` devuelve `[]` | Misma verificación de conteo |
| 3 | **Contenido «vacío» percibido** con tipos presentes pero **sin correos** en catálogo | Partial muestra solo mensaje «Agregue correos en Correos de notificación primero» por tipo | Revisar `requisition_notification_emails` y categoría en Parámetros |
| 4 | Menos probable: **error 500** al cargar Parámetros | No hay test de regresión UI; servicio `syncTypeEmails` referencia modelo sin `use` importado (afectaría **guardar**, no listar) | Revisar `storage/logs` al abrir Parámetros |

**Nota:** En entornos con migraciones al día (p. ej. CI con `RefreshDatabase`), los tests de mail **sí** encuentran tipos por slug; el fallo reportado apunta a **datos/ despliegue** más que a condición `showNotificationTypes` (correcta para `gestion_humana`).

---

## Preguntas abiertas (priorizadas — negocio)

Responde para cerrar el brief (3–6 críticas):

1. **Qué significa «ver las notificaciones que el sistema registra» en v1**  
   ¿Basta con listar los **tipos de aviso** definidos por el sistema (hoy 2 de requisiciones, más los que agreguen por desarrollo), con nombre/descripción y destinatarios? ¿O también necesitan un **historial de envíos** (fecha, asunto, destinatarios, estado)?

2. **Quién entra al tablero nuevo**  
   ¿Solo rol **`super-admin`**, o cualquier usuario con **`manage.users`** (hoy también **administrador** tiene ese permiso según seeders)? ¿GH debe **dejar de poder** editar correos/tipos aunque conserve `manage.requisition.parameters`?

3. **Alcance de módulos en v1**  
   ¿La configuración global cubre **solo avisos de requisiciones de personal** (los dos tipos actuales + catálogo de correos ligado), o desde v1 debe **prepararse** para otros módulos (p. ej. suministros) aunque aún no estén conectados?

4. **Correos de notificación fuera de Parámetros**  
   Al integrar en Administración, ¿se **elimina por completo** la tarjeta **Correos de notificación** del tablero Parámetros de **todas** las áreas de requisiciones (Operaciones, Comercial, etc.), o solo se quita la gestión en GH dejando el catálogo en otras áreas un tiempo?

5. **Alta de nuevos tipos de aviso**  
   Cuando el sistema incorpore un nuevo correo automático, ¿el negocio espera que aparezca **solo tras despliegue** (migración/código), sin pantalla «crear tipo» en admin? ¿Necesitan en v1 **activar/desactivar** un tipo entero (no solo destinatarios)?

6. **Correo al solicitante por cambio de estado**  
   Hoy **no** usa el catálogo ni tipos (`requested_by`). ¿Debe **seguir fuera** de Configuración de notificaciones en v1, o integrarse como otro tipo configurable?

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | v1 = **reubicar UI** + mismas tablas (`requisition_notification_*`), sin renombrar a modelo global transversal | Refactor de BD y servicios si el negocio pide catálogo único para todo el ERP |
| 2 | Acceso al tablero: permiso **`manage.users`** + entrada en `navigation.administracion` | Si solo `super-admin`, hay que restringir rol y no solo permiso |
| 3 | «Registrar» = **listar tipos seed** + CRUD de **correos** + asignación por tipo; **sin** bandeja de envíos | Expectativa de auditoría de correos no cumplida |
| 4 | Se **retiran** Tipos de notificación y **Correos de notificación** de Parámetros requisiciones en todas las áreas | Usuarios GH/área pierden atajo si aún dependían de Parámetros locales |
| 5 | Nuevos tipos solo vía **despliegue** (migración/constantes), no formulario admin | Producto pedirá alta manual de eventos |
| 6 | Bug vacío se corrige en prod con **`migrate`** pendiente; la feature **no depende** de mantener la sección en GH | Doble mantenimiento si insisten en arreglar GH además del tablero admin |
| 7 | Fallback `desarrollo.tic@sjsp.com.co` se mantiene cuando un tipo no tiene destinatarios activos | Cambio de política de TI si quieren bloquear envío en lugar de fallback |

---

## Fuera de alcance (propuesta analista — confirmar con usuario)

- **Historial / reintentos / plantillas** de correo editables desde admin (solo destinatarios y visibilidad de tipos, salvo que respondan lo contrario en pregunta 1).
- **Notificaciones in-app**, SMS o integraciones externas.
- Unificar en v1 el envío de **suministros** u otros módulos con el mismo tablero (salvo decisión explícita en pregunta 3).
- Crear **área sidebar nueva** llamada «Super Admin» distinta de **Administración** actual (salvo que el negocio pida rebranding de la sección existente).
- Registro público o autogestión de correos por usuarios finales de área.

---

## Borrador preliminar (NO enviar al Arquitecto hasta cerrar preguntas)

> `BORRADOR — pendiente respuestas usuario y Arquitecto`

| Campo | Valor provisional |
| --- | --- |
| ID | FEAT-013 |
| Módulo / área | **Administración** (`admin.*`); origen funcional **requisiciones** |
| Título | Configuración global de notificaciones (correos + tipos de aviso) |
| Objetivo | Centralizar destinatarios y asignación por tipo de aviso; sacar configuración de Parámetros GH/requisiciones |
| Incluye (tentativo) | Pantalla admin; CRUD correos (migrado desde Parámetros); matriz tipo ↔ correos; retirar secciones obsoletas en GH; documentación usuario admin |
| Permiso tentativo | `manage.users` (o permiso dedicado `manage.notifications` si se desacopla de usuarios) |
| Rutas tentativas | `GET/PATCH` bajo `Route::prefix('admin')` en `routes/web.php` |
| BD tentativa | Reutilizar tablas actuales; opcional migración de datos ya existente sin cambio de esquema |
| Bug GH | Tratado como síntoma de despliegue/datos; cierre operativo con migrate; UI GH eliminada al entregar admin |
| Shared-files | `config/access.php`, `routes/web.php`, `docs/modules/requisitions.md`, nuevo `docs/modules/admin-notifications.md` (nombre a confirmar) |

### Criterios de aceptación (borrador)

1. Usuario autorizado abre **Administración → Configuración de notificaciones** y ve **todos los tipos** registrados en sistema (mínimo los 2 de requisiciones) con descripción legible.
2. Puede **agregar/editar/desactivar** correos destinatarios y **asignarlos** por tipo; cambios afectan envíos reales (`new_requisition`, `management_approval_cargo_nuevo`).
3. En **GH → Parámetros** ya **no** aparecen Tipos de notificación ni (según respuesta 4) Correos de notificación.
4. Permisos: usuario sin acceso admin **no** puede mutar configuración vía rutas legacy de requisiciones (404 o redirect).
5. Tests feature cubren pantalla admin y regresión de envío por tipo.

---

## Estado

- [x] Preguntas 1–5 respondidas (2026-07-29)
- [ ] Pregunta 6 (correo cambio de estado) — supuesto: fuera de v1
- [ ] Listo para Arquitecto (tras brief)

## Respuestas del usuario

| # | Respuesta |
| --- | --- |
| 1 v1 alcance pantalla | Solo **catálogo de tipos** + **correos asignados** (sin historial de envíos) |
| 2 acceso | **Permiso nuevo dedicado** (ej. `manage.notifications`), asignable aparte |
| 3 módulos | **Diseño multi-módulo** desde ya; v1 conecta **requisiciones** |
| 4 Parámetros requisiciones | **Quitar** tipos y **Correos de notificación** en **todas** las áreas |
| 5 altas de tipos | Solo **despliegue** (migración/código); admin **asigna correos** |
| 6 cambio estado solicitante | *Sin respuesta* — supuesto analista: **sigue fuera** del tablero en v1 |
