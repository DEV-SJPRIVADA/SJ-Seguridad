# Feature Brief — FEAT-015

> Versión final (Arquitecto). Decisiones de negocio cerradas 2026-07-29 (ver [`FEAT-015-analyst.md`](FEAT-015-analyst.md)).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-015 |
| Modulo / area | **Comercial** (`comercial.*` matriz clientes); integracion **Administracion** (FEAT-013 tipos/destinatarios) |
| Titulo | Notificacion por correo: documentacion comercial por vencer o vencida (digest diario) |
| Solicitante | Manuel-E (via `@agent-sj`) |
| Fecha | 2026-07-29 |

## Objetivo

Automatizar el aviso por **correo** cuando la documentacion de un cliente comercial entra en la **ventana de anticipacion** (misma regla que el checklist «Por vencer») o queda **vencida**, sin depender de que Comercial revise la pantalla. Los destinatarios se configuran en **Administracion → Configuracion de notificaciones** (`manage.notifications`), usando la capa global `NotificationConfigService` (FEAT-013). El envio es un **digest diario** (un correo con la lista de clientes que **disparan aviso ese dia**), con **un aviso por cliente y ciclo** (no recordatorios diarios mientras sigue en ventana).

**Para quien:** administradores que asignan correos al tipo comercial; destinatarios operativos (Comercial / backoffice) que actuan sobre el checklist; Comercial sigue usando la UI de FEAT-014 para fechas y estados.

**Dependencias:** FEAT-013 desplegado (tablas `notification_*`, admin, `recipientEmails`); FEAT-014 desplegado (`documentation_expires_on`, `alert_days_before`, checklist y scopes en `CommercialClient`).

## Alcance

### Incluye

1. **Tipo de aviso en BD:** fila en `notification_types` con `module = comercial`, slug `documentation_expiring`, etiqueta y descripcion legibles (cubre **por vencer y vencida** — un solo tipo en admin).
2. **Config:** `config/notifications.php` — label de modulo `comercial` y slug en `admin_configurable` para que el tablero admin permita asignar correos.
3. **Tabla de deduplicacion / historial minimo** por cliente, fecha de vencimiento y «tipo de transicion» (`expiring` | `expired`) para garantizar **una vez por ciclo** (ver Reglas de negocio).
4. **Servicio de dominio** (nombre orientativo: `CommercialDocumentationNotificationService`) que: resuelve candidatos del dia, arma payload del digest, persiste logs tras envio exitoso, delega destinatarios a `NotificationConfigService::recipientEmails('comercial', 'documentation_expiring')`.
5. **Mailable + vista Markdown** del digest (NIT, razon social, fecha vencimiento, estado «por vencer» / «vencida», dias restantes si aplica, enlace al checklist).
6. **Comando Artisan** invocable manualmente y desde scheduler (p. ej. `comercial:send-documentation-notification-digest`).
7. **Programacion diaria** 06:00 `America/Bogota` via Laravel Scheduler (`Schedule::command(...)`); registro en `routes/console.php` y, si el proyecto aun no lo tiene, callback `->withSchedule()` en `bootstrap/app.php` (Hostinger: cron unico `* * * * * php artisan schedule:run`).
8. **Constantes** en `NotificationType`: `MODULE_COMERCIAL`, `SLUG_DOCUMENTATION_EXPIRING` (alineado a FEAT-014).
9. **Tests** feature: comando (Mail fake), dedupe, resolucion de destinatarios, regresion scopes existentes no alterados.
10. **Documentacion** tecnica en `docs/modules/matriz-clientes.md` y `docs/modules/notifications-config.md` (o archivo dedicado si ya existe patron); usuario en `docs/user/matriz-clientes.md` (procedimiento al recibir correo); mencion en doc admin notificaciones si aplica.

### Fuera de alcance

- Cambiar la formula de ventana «por vencer» (`isDocumentationExpiringSoon` / `scopeDocumentationExpiring`) o KPIs/checklist.
- Segundo tipo de aviso en admin para «vencida» (un solo slug).
- Correo **por cliente** (N correos/dia); recordatorios **diarios** mientras permanece en ventana.
- Historial de envios en admin, reintentos configurables, plantillas editables desde UI.
- Notificaciones in-app, SMS, aviso al asesor comercial por usuario del sistema.
- Filtrar clientes por servicios operativos activos (salvo cambio futuro de negocio).
- Cola obligatoria / worker dedicado (v1 envio sincrono en el comando; ver Riesgos).
- Permisos Spatie nuevos o rutas HTTP nuevas.
- Export Excel del barrido.

## Reglas de negocio

### Criterios de elegibilidad (quien puede entrar al digest)

1. **`documentation_expires_on` no nula.** Si es null → no correo (coherente con checklist).
2. **Alcance:** todos los registros en `commercial_clients` que cumplan reglas; **sin** filtrar por portafolio o contrato de servicios.
3. **Por vencer:** mismo criterio que checklist / `CommercialClient::isDocumentationExpiringSoon()` (fecha ≥ hoy y ≤ hoy + `documentationAlertDays()` inclusive; «hoy» = inicio de dia timezone aplicacion).
4. **Vencida:** `CommercialClient::isDocumentationExpired()` (`documentation_expires_on` &lt; hoy).
5. **Un solo tipo en admin:** ambos casos usan `module=comercial`, slug `documentation_expiring`.

### Frecuencia y digest

6. **Digest diario:** como maximo **un correo por ejecucion** del comando (si hay ≥1 cliente candidato ese dia). Todos los destinatarios resueltos reciben el **mismo** mensaje (misma lista).
7. **Sin candidatos:** no enviar correo; comando termina OK con mensaje informativo (evitar spam vacio).
8. **Una vez por ciclo por cliente y transicion:**
   - **`expiring`:** la **primera** vez que el cliente cumple «por vencer» para un par `(commercial_client_id, documentation_expires_on)` concreto. Mientras siga en ventana en dias siguientes **no** se vuelve a incluir.
   - **`expired`:** la **primera** vez que el cliente cumple «vencida» para el mismo par `(cliente, documentation_expires_on)`. Un cliente puede generar **hasta dos** inclusiones en ciclos distintos sobre la misma fecha: una al entrar en ventana y otra al cruzar a vencida (si no corrigieron la fecha).
9. **Nuevo ciclo:** si Comercial cambia `documentation_expires_on`, el par `(client_id, nueva_fecha)` es un ciclo nuevo; pueden dispararse de nuevo `expiring` y/o `expired` segun reglas (filas de log antiguas con otra fecha no bloquean).
10. **Arranque en produccion:** clientes ya en ventana o ya vencidos sin fila de log para la transicion correspondiente entran en el **primer** digest tras despliegue (backfill implicito).

### Destinatarios y operacion

11. **Destinatarios:** `NotificationConfigService::recipientEmails('comercial', 'documentation_expiring')`; solo correos activos asignados al tipo.
12. **Sin destinatarios asignados:** fallback `config('notifications.fallback_recipient')` → `desarrollo.tic@sjsp.com.co` (politica FEAT-013).
13. **Hora de barrido:** 06:00 `America/Bogota` (scheduler); el comando debe aceptar fecha de referencia opcional (`--date=YYYY-MM-DD`) para pruebas y reproceso controlado.
14. **Contenido minimo del correo por fila:** NIT, nombre, `documentation_expires_on` formateada, etiqueta «Por vencer» o «Vencida», dias restantes (entero, solo si por vencer), enlace named route al checklist (`comercial.matriz.clients.checklist.index`) con query sugerida `doc_vigencia=expiring|expired` cuando aplique para facilitar filtro.
15. **Permisos:** ninguno nuevo; configuracion solo con `manage.notifications`; Comercial no necesita permiso extra para **recibir** correo.

### Etiqueta admin propuesta

- **Label:** «Documentacion comercial (por vencer o vencida)»
- **Description:** «Correo digest cuando un cliente entra en la ventana de anticipacion definida en el checklist o cuando la documentacion vence. Misma regla que la pantalla Checklist documental. Un aviso por cliente y ciclo; no recordatorios diarios en ventana.»

## Permisos (`config/access.php`)

| Permiso | Rol(es) tipicos | Descripcion |
| --- | --- | --- |
| `manage.notifications` | Asignado explicitamente (p. ej. `super-admin`) | Asignar correos al tipo comercial `documentation_expiring` (sin cambios en FEAT-015). |
| `comercial.matriz.view` / `manage` | Comercial | Sin cambio; no requeridos para el job. |

**Registro:** **no** anadir permisos ni entradas sidebar en esta feature.

## Rutas

| Metodo | URI | Nombre | Archivo | Notas |
| --- | --- | --- | --- | --- |
| — | — | — | — | **Sin rutas HTTP nuevas.** |

**Comando Artisan (CLI):**

| Signature | Clase | Proposito |
| --- | --- | --- |
| `comercial:send-documentation-notification-digest` | `App\Console\Commands\SendCommercialDocumentationNotificationDigestCommand` | Barrido diario, envio digest, persistencia logs. Opciones: `--date=` (opcional), `--dry-run` (opcional, lista candidatos sin mail ni logs). |

**Scheduler:**

| Evento | Expresion | Archivo |
| --- | --- | --- |
| `Schedule::command('comercial:send-documentation-notification-digest')->dailyAt('06:00')->timezone('America/Bogota')` | Diario 06:00 Bogota | Registrar en `routes/console.php` (closure `Schedule::`) **y/o** `bootstrap/app.php` `withSchedule` segun convencion Laravel 13 del repo al implementar. |

**Enlace en correo (existente FEAT-014):**

| Metodo | URI | Nombre |
| --- | --- | --- |
| GET | `/comercial/clientes/checklist-documental` | `comercial.matriz.clients.checklist.index` |

## Base de datos

### Migracion: tipo de notificacion

Insert idempotente en `notification_types` (`insertOrIgnore` o equivalente por unique `(module, slug)`):

| module | slug | label (ejemplo) | description (ejemplo) | sort_order |
| --- | --- | --- | --- | --- |
| `comercial` | `documentation_expiring` | Documentacion comercial (por vencer o vencida) | Ver regla 15 arriba | Tras tipos requisiciones (p. ej. 10) |

### Migracion: tabla dedupe / historial

**Nombre sugerido:** `commercial_client_documentation_notification_logs`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `commercial_client_id` | FK → `commercial_clients`, cascade delete | |
| `documentation_expires_on` | date | Snapshot de la fecha de vencimiento del ciclo notificado |
| `alert_kind` | string(16) | Valores fijos: `expiring`, `expired` |
| `notified_at` | timestamp | Momento del envio exitoso del digest que incluyo al cliente |
| `timestamps` | | Opcional `created_at`/`updated_at`; `notified_at` puede bastar — implementacion elige uno, documentar |

**Indice unico:** `(commercial_client_id, documentation_expires_on, alert_kind)` — garantiza idempotencia «una vez por ciclo y transicion».

**Modelo:** `App\Models\CommercialClientDocumentationNotificationLog` con relacion `belongsTo(CommercialClient)`.

**Persistencia:** insertar filas **solo despues** de `Mail::send` exitoso del digest que contuvo al cliente (misma transaccion DB recomendada para logs + evitar marcar si falla el mail).

### Consulta de candidatos (orientativa)

Para fecha de referencia `$asOf` (inicio de dia):

- **Expiring:** clientes con `scopeDocumentationExpiring($asOf)` (o equivalente Eloquent) **AND** NOT EXISTS log con `alert_kind = expiring` y `documentation_expires_on` = valor actual del cliente.
- **Expired:** clientes con `scopeDocumentationExpired($asOf)` **AND** NOT EXISTS log con `alert_kind = expired` y misma fecha.

Unificar en coleccion para el digest; orden sugerido: vencidos primero, luego por vencer; dentro por `documentation_expires_on` asc, luego nombre.

**Nota:** usar la fecha **actual** del cliente al evaluar y al escribir log; si entre candidatura y envio cambia la fecha, preferir revalidar antes de enviar o omitir fila (edge case raro).

## Capas a implementar

- [ ] Migracion(es) — tipo notificacion + tabla logs + modelo
- [ ] Constantes `NotificationType` — modulo comercial + slug
- [ ] Config — `config/notifications.php` modules + admin_configurable
- [ ] Servicio — `App\Services\Comercial\CommercialDocumentationNotificationService` (o namespace `Notifications` si se prefiere agrupar con FEAT-013)
- [ ] Mailable — `App\Mail\CommercialDocumentationDigestMail` + vista `resources/views/emails/comercial/documentation-digest.blade.php` (Markdown)
- [ ] Comando — `SendCommercialDocumentationNotificationDigestCommand`
- [ ] Scheduler — `routes/console.php` + `bootstrap/app.php` si aplica
- [ ] Controlador(es) — **no**
- [ ] Vista(s) Blade web — **no** (solo email)
- [ ] Export Excel — **no**
- [ ] Tests — ver seccion Tests

## Mailable (outline)

**Clase:** `CommercialDocumentationDigestMail`

| Aspecto | Detalle |
| --- | --- |
| Constructor | Recibe `Carbon $asOf` y lista de DTOs/array shapes: `nit`, `name`, `documentation_expires_on`, `status` (`expiring`\|`expired`), `days_remaining` (nullable int), `checklist_url` |
| Envelope | Asunto: p. ej. `[SJ Seguridad] Documentacion comercial — {N} cliente(s) ({fecha referencia})` |
| Content | Markdown; tabla o lista de clientes; pie con enlace general al checklist |
| Queue | **No** implementar `ShouldQueue` en v1 (envio sincrono en comando; Hostinger sin worker) |
| Envio | `Mail::to($recipients)->send(...)` con array de emails del servicio de config |

## Servicios y componentes reutilizables

| Componente | Uso |
| --- | --- |
| `NotificationConfigService::recipientEmails` | Unica fuente de destinatarios + fallback |
| `CommercialClient::isDocumentationExpiringSoon`, `isDocumentationExpired`, scopes | **No modificar formulas**; reutilizar en servicio |
| `NotificationType::MODULE_COMERCIAL`, `SLUG_DOCUMENTATION_EXPIRING` | Call sites comando/tests |
| Patron comando existente | `ImportMtCo01MatrixCommand` — clase en `app/Console/Commands/`, registro auto-discovery Laravel |
| `get-absolute-url` / `route()` | URLs absolutas en correo |

## Documentacion a actualizar

- [ ] `docs/modules/matriz-clientes.md` — job diario, dedupe, tipo FEAT-013, enlace checklist en correo.
- [ ] `docs/modules/notifications-config.md` — **crear o ampliar** con tipo `comercial` / `documentation_expiring` y fallback.
- [ ] `docs/user/matriz-clientes.md` — que hacer al recibir digest (revisar checklist, actualizar fecha/estados).
- [ ] `docs/user/notifications-config.md` — bloque Comercial en admin (si existe doc usuario FEAT-013).
- [ ] `docs/INDEX.md` — solo si falta referencia cruzada.
- [ ] `README.md` — solo si documenta cron/scheduler (opcional: linea cron Hostinger).

## Archivos compartidos (`shared-files`)

Marcar **`shared-files: true`** en Task Card si aplica:

| Archivo | Motivo |
| --- | --- |
| `config/notifications.php` | Modulo `comercial` + slug configurable |
| `routes/console.php` | Definicion `Schedule::` |
| `bootstrap/app.php` | `withSchedule()` si aun no existe en el proyecto |
| `app/Models/NotificationType.php` | Constantes modulo comercial |

**Ownership principal (sin flag global):** migracion logs, modelo log, servicio comercial notificacion, mailable, vista email, comando, tests feature dedicados.

## Task cards sugeridas (vertical slice — 1 agente Feature)

Un solo slice end-to-end recomendado para AgentSj (Revisor + Documentador despues):

### FEAT-015-T1 — Digest documentacion comercial + scheduler

- Migracion: seed tipo `notification_types` + tabla `commercial_client_documentation_notification_logs` + modelo.
- Actualizar `NotificationType` constantes; `config/notifications.php`.
- Implementar `CommercialDocumentationNotificationService` + `CommercialDocumentationDigestMail` + vista Markdown.
- Comando `comercial:send-documentation-notification-digest` con `--date`, `--dry-run`.
- Registrar schedule 06:00 America/Bogota (`shared-files` console/bootstrap).
- Tests feature (ver abajo); `vendor/bin/pint --dirty` en PHP tocado.
- Handoff Documentador: docs modulo/usuario/notifications.

**No dividir** en tareas Backend/Frontend; no tocar `config/access.php` salvo que se anada permiso (no requerido).

## Criterios de aceptacion

1. Tras migrar, en **Configuracion de notificaciones** aparece el tipo **Comercial** «Documentacion comercial (por vencer o vencida)» con slug `documentation_expiring`; admin puede asignar correos activos.
2. Cliente con fecha en ventana «por vencer» (regla actual) se incluye en el **primer** digest del dia en que entra en ventana; **no** se repite al dia siguiente si sigue en ventana y la fecha no cambio.
3. Cliente que pasa a **vencida** se incluye **una vez** en digest (kind `expired`) aunque ya se hubiera notificado `expiring` para la misma `documentation_expires_on`.
4. Cliente con `documentation_expires_on` null **nunca** aparece en digest.
5. Si ningun cliente es candidato, el comando **no** envia correo.
6. Si hay candidatos, **un** correo digest llega a todos los destinatarios resueltos (misma lista); contenido incluye NIT, nombre, fecha, estado y enlace checklist.
7. Tipo sin correos asignados usa fallback `desarrollo.tic@sjsp.com.co`.
8. Scheduler documentado para 06:00 America/Bogota; comando manual ejecutable en local con `Mail::fake` / mailhog.
9. Formulas checklist (`isDocumentationExpiringSoon`, filtros) **sin cambios** de comportamiento (tests FEAT-014 siguen validos).
10. Tests nuevos en verde; sin permisos nuevos.

## Validacion local

1. `php artisan migrate`
2. Asignar correos al tipo comercial en admin (usuario con `manage.notifications`).
3. Fabricar cliente: vencimiento dentro de ventana; ejecutar `php artisan comercial:send-documentation-notification-digest --dry-run` y sin dry-run con `Mail::fake`.
4. Repetir comando al dia siguiente (o `--date`) y verificar que el mismo cliente no se reenvia en ventana.
5. Avanzar fecha o usar `--date` en dia de vencimiento +1 para escenario `expired`.
6. `php artisan test --compact` (archivos FEAT-015).
7. Verificar registro schedule: `php artisan schedule:list` (Laravel 11+).
8. `vendor/bin/pint --dirty` en PHP modificado.

## Tests (minimos)

Archivo sugerido: `tests/Feature/CommercialDocumentationNotificationTest.php` (o ampliacion acotada de `CommercialMatrixTest` si se prefiere un solo archivo comercial — preferir archivo dedicado).

| Test | Intencion |
| --- | --- |
| `test_digest_includes_client_first_day_in_expiring_window` | Fecha/`alert_days_before` alineados a `isDocumentationExpiringSoon`; Mail sent; log `expiring` creado. |
| `test_digest_skips_client_still_in_window_next_day` | Segunda ejecucion `--date` +1 dia; Mail not sent o digest vacio; sin nuevo log. |
| `test_digest_notifies_once_when_documentation_becomes_expired` | `--date` en dia post-vencimiento; log `expired`; cliente en mail. |
| `test_digest_excludes_client_without_expiry_date` | `documentation_expires_on` null → no mail. |
| `test_digest_uses_notification_config_recipients` | Pivot admin + `Mail::assertSent` destinatarios. |
| `test_digest_falls_back_when_no_recipients` | Sin pivot → `hasTo(fallback)`. |
| `test_dry_run_does_not_send_mail_or_persist_logs` | `--dry-run` → no Mail, no filas log. |
| `test_new_expiry_date_starts_new_cycle` | Cambiar `documentation_expires_on` → permite nuevo `expiring`. |
| `test_notification_type_seeded_and_admin_configurable` | GET admin con permiso muestra tipo comercial (opcional, regresion FEAT-013). |

Usar `Carbon::setTestNow()` / `--date` coherente con timezone app; RefreshDatabase.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| FEAT-013 no migrado en entorno | Bloquear UAT; tipo comercial depende de tablas globales. |
| `APP_TIMEZONE` distinto de Bogota | Documentar alinear timezone app; scheduler explicito `America/Bogota`. |
| Hostinger sin `schedule:run` | README/doc despliegue: cron minuto; comando idempotente por dedupe. |
| Envio sincrono lento con muchos clientes | v1 aceptable; monitor; futuro queue opcional. |
| Mail falla tras calcular candidatos | No escribir logs si send falla; reintento dia siguiente puede reintentar mismos candidatos (aceptable). |
| Desincronia digest vs pantalla si cambian reglas en futuro | Contrato: reutilizar metodos/scopes del modelo, no duplicar SQL. |
| Un solo slug para dos estados | Copy admin y asunto dejan claro por vencer + vencida. |

## Supuestos documentados (preguntas analista)

| # | Supuesto en brief |
| --- | --- |
| 3 | Fecha nula → nunca correo. |
| 6 | Contenido digest: NIT, nombre, fecha, estado, dias restantes, enlace checklist (pregunta 6 analista). |
| 7 | 06:00 America/Bogota confirmado como default usuario pendiente. |
| 8 | Fallback TI si sin destinatarios (FEAT-013). |
| 9 | Todos los `commercial_clients` con reglas de fecha; sin filtro servicios activos. |
| 11 | Fuera v1: historial admin, in-app, SMS, plantillas UI. |
| 10 | Etiqueta/descripcion admin segun seccion Reglas. |

## Integracion FEAT-013 / FEAT-014

| Feature | Contrato |
| --- | --- |
| FEAT-013 | `recipientEmails('comercial', 'documentation_expiring')`; tipo listado via `admin_configurable`; sin CRUD de tipos en UI. |
| FEAT-014 | Campos cliente y scopes; checklist ruta named para enlaces; **no** reintroducir correo en controladores web del checklist. |

## Aprobacion

- [x] Analista — preguntas criticas respondidas (Manuel 2026-07-29)
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion explicita del brief
- [ ] AgentSj — plan de orquestacion y Task Card en `docs/TASKS.md`
