# Preguntas del Analista — FEAT-025

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-025  
**Origen:** `@agent-sj` (2026-08-11)  
**Título:** Log general admin cross-módulo (sync, sin cola async)

### Solicitud del usuario (resumen)

Implementar un **log general de todas las áreas** visible en **Administración**, de modo que el super-administrador pueda investigar acciones en la plataforma sin depender solo de Indicadores. Hoy la pantalla **Auditoría del sistema** (`/admin/auditoria`) existe (FEAT-021) pero **solo recibe escrituras del módulo Indicadores** (`module=indicadores`, `area=operaciones`), por lo que se percibe como copia del log de Operaciones → Ajustes → Auditoría.

Restricciones explícitas:

- **No afectar** la auditoría de Operaciones (Ajustes → sección Auditoría, filtro `AuditLog::forModule('indicadores')`).
- **No migrar ni duplicar** historiales de dominio: requisiciones campo a campo (`personal_requisition_change_logs`, `personal_requisition_status_logs`), archivo GH (`employee_archive_consultations` + items), logs de correo/notificaciones.
- **Rechazo de cola async:** el usuario quiere **`AUDIT_QUEUE=false`** (escritura **síncrona**), sin depender de `queue:work` en producción (Hostinger compartido).

### Estado técnico hoy (repo — FEAT-021 implementado)

| Aspecto | Comportamiento actual |
| --- | --- |
| Tabla central | `audit_logs` con `module`, `area`, morph, JSON old/new/metadata |
| Servicio | `SystemAuditService` → sync directo si `AUDIT_QUEUE=false`; job `WriteAuditLogJob` si `true` |
| Default `.env` | `AUDIT_QUEUE=false` (sync); documentación FEAT-021 recomienda `true` + `queue:work` en producción |
| Único módulo que escribe | **Indicadores** vía `App\Services\Indicadores\AuditLogService` |
| UI global Admin | `GET /admin/auditoria`, permiso `system.view.audit` (solo `super-admin` por seeder) |
| UI Operaciones | Ajustes → Auditoría: `AuditLog::forModule('indicadores')` — **no tocar** |
| Catálogo config | `config/audit.php` declara `admin` y `requisitions` pero **no están instrumentados** |
| Ruido UI global | Eventos `info` de Indicadores (`dashboard_view`, `consolidado_view`) ocultos salvo checkbox **Info** |
| Áreas de negocio | 10 slugs en `config/access.php` → `areas` (gerencia, gestion_humana, operaciones, programacion, juridico, comercial, calidad, admin_financiero, compras, Tic) |
| Tableros/módulos con código | indicadores, requisiciones, gestion_clientes, suministros, solicitudes_compra, bandeja_compras, ficha_empleados, archivo, documentos calidad, admin (usuarios, notificaciones) |

---

## Entendimiento del analista (resumen)

El negocio necesita que la **auditoría global en Admin** deje de ser un espejo de Operaciones/Indicadores y pase a reflejar **acciones relevantes de los demás módulos** de la plataforma, usando la infraestructura central ya creada en FEAT-021.

La entrega no es una pantalla nueva: es **instrumentación** (wrappers + puntos de escritura en controladores/servicios) más ajustes de **configuración, filtros y documentación**, respetando historiales especializados que ya cumplen su rol (requisiciones detalladas, archivo GH).

La restricción **`AUDIT_QUEUE=false`** implica política operativa distinta a FEAT-021: escritura inline en la misma petición HTTP. En Hostinger compartido esto es coherente con no mantener workers, pero exige disciplina en **qué** se registra (resumen, no bucles masivos) para no degradar latencia.

---

## Hallazgos técnicos relevantes

| Hallazgo | Detalle | Por qué importa |
| --- | --- | --- |
| Infraestructura lista | Tabla, servicio, job opcional, UI global, tests base (`SystemAuditTest`) | El esfuerzo es **instrumentar módulos**, no rediseñar audit |
| Solo Indicadores escribe | Grep confirma único wrapper activo: `AuditLogService` en Operaciones | Admin vacío de otros módulos = síntoma esperado, no bug de UI |
| Operaciones aislado por diseño | `IndicadorController` sección `auditoria` filtra `forModule('indicadores')` | Cualquier cambio en query global Admin **no debe** alterar esta vista |
| Requisiciones fuera de FEAT-021 | Brief FEAT-021 excluyó dual-write y change logs | Instrumentar requisiciones requiere decisión: **eventos resumen** vs duplicar historial |
| Archivo GH propio | `employee_archive_consultations` + items con tests dedicados | No centralizar consultas documentales; posible evento resumen opcional |
| `config/audit.php` incompleto | Faltan slugs para comercial, supplies, purchases, quality_documents, ficha_empleados | Arquitecto debe extender catálogo al instrumentar |
| Catálogo severidad | `AuditEventCatalog` solo define Indicadores; resto cae en `SEVERITY_AUDIT` | Definir qué es `info` (ruido) por módulo nuevo |
| Sync en prod | `SystemAuditService::persist()` hace `create()` inline si queue=false | Importaciones masivas o muchos campos JSON pueden ralentizar request |
| Permiso actual | `system.view.audit` solo en rol `super-admin` | Confirmar si `administrador` debe ver log global |
| Filtros Admin | Fechas **opcionales** hoy; sin rango puede paginar tabla completa | Riesgo rendimiento si crece volumen cross-módulo |

---

## Propuesta de alcance por fases

Instrumentar **todos** los módulos de un golpe es grande (≈8 tableros × múltiples controladores). Propuesta para negociar con el usuario:

### Fase 1 — v1 (valor inmediato para Admin global)

| Módulo (`module`) | Área | Eventos propuestos (resumen) | Notas |
| --- | --- | --- | --- |
| `admin` | `null` | Alta/edición/inactivación usuario; cambio roles/permisos; reset contraseña temporal | Alta sensibilidad seguridad |
| `requisitions` | `gestion_humana` | Crear solicitud; cambio de estado; aprobación gerencia; export sensibles | **Sin** campo a campo (sigue en change_logs) |
| `admin` (notificaciones) | `null` | Cambio destinatarios/tipos (FEAT-013) | Módulo admin transversal |

**Entregables transversales v1:** confirmar `AUDIT_QUEUE=false` en todos los entornos; extender `config/audit.php`; wrappers delgados; tests por módulo; doc técnica/usuario.

### Fase 2 — operación comercial y compras

| Módulo | Área | Eventos propuestos |
| --- | --- | --- |
| `commercial` | `comercial` | CRUD clientes/servicios; checklist documental; parámetros |
| `supplies` | `compras` / `calidad` | Solicitud; aprobación calidad; catálogo |
| `purchase_requests` | `compras` | Crear; aprobar director; procesamiento bandeja |

### Fase 3 — GH restante y calidad

| Módulo | Área | Eventos propuestos |
| --- | --- | --- |
| `quality_documents` | `calidad` | Publicar/retirar documento; cambios metadatos |
| `ficha_empleados` | `gestion_humana` | Alta lista espera; cambios estado (sin duplicar requisición) |
| `employee_archive` | `gestion_humana` | Solo evento resumen opcional (consulta/entrega), **sin** migrar items |

### Fuera de fases hasta existir módulo

Áreas sin tablero/código hoy: **gerencia**, **programación**, **jurídico**, **admin_financiero**, **Tic** (salvo acciones vía Admin). No instrumentar slugs vacíos.

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (priorizadas — negocio):

### 1. Objetivo y usuarios

¿Quién usará el log general y para qué casos concretos?

- ¿Solo **super-admin** investigando incidentes y cambios de permisos?
- ¿También **administrador** o soporte TI con permiso dedicado?
- ¿Esperan usarlo para **auditoría legal/compliance** o solo **soporte operativo**?

### 2. Alcance — módulos en v1 vs fases posteriores

¿Confirman la **Fase 1** propuesta (`admin` usuarios/permisos + `requisitions` resumen + notificaciones)?

Si no, indiquen **qué módulos son obligatorios en la primera entrega** y cuáles pueden esperar:

- [ ] Administración (usuarios, roles, permisos)
- [ ] Requisiciones (eventos resumen)
- [ ] Configuración notificaciones
- [ ] Comercial (clientes/servicios)
- [ ] Suministros
- [ ] Solicitudes de compra
- [ ] Documentos de calidad
- [ ] Ficha empleados
- [ ] Archivo empleados (solo resumen)
- [ ] Otro: ___

### 3. Permisos

- ¿Mantiene **solo `super-admin`** con `system.view.audit`, o amplían acceso?
- ¿Algún rol de área (ej. jefe GH) debe ver **solo su área** en Admin, o eso queda fuera?

### 4. Reglas de negocio — qué eventos registrar

Por módulo, confirmen nivel de detalle:

| Módulo | ¿Qué registrar en v1? | ¿Qué NO registrar? |
| --- | --- | --- |
| **Requisiciones** | ¿Solo cambios de estado y aprobaciones? ¿Creación? ¿Export Excel? | Confirmar: **no** duplicar historial campo a campo |
| **Admin usuarios** | ¿Create/update/inactivate? ¿Cada permiso individual o resumen por guardado? | ¿Login/logout de todos los usuarios? |
| **Indicadores** | ¿Sin cambios (sigue como hoy)? | — |
| **Resto (fase 2+)** | ¿Misma regla: create/update/delete + exportaciones sensibles? | ¿Excluir listados GET / vistas dashboard? |

¿Deben registrarse eventos de **seguridad** globales (login exitoso/fallido, cambio contraseña, bloqueo usuario inactivo)?

### 5. Datos

- ¿Solo eventos **hacia adelante** (desde el despliegue), o migrar historial existente (change_logs, status_logs) al central? *(FEAT-021 migró Indicadores con comando dedicado; replicar patrón es esfuerzo aparte.)*
- ¿Retención sigue en **24 meses** (`audit:purge` programado)?

### 6. Interfaz — filtros y defaults en Admin

- ¿Las fechas **Desde/Hasta** deben ser **obligatorias** al abrir o filtrar (ej. default últimos 7 o 30 días)?
- ¿Por defecto se ocultan eventos **Info** de Indicadores (comportamiento actual) — confirmar?
- ¿Desean **excluir por defecto todo el módulo Indicadores** en Admin global (porque ya existe en Operaciones), o ver **todo mezclado**?
- ¿Necesitan **export Excel** del listado global en v1?

### 7. Integraciones — cola y producción

- Confirmación explícita: **`AUDIT_QUEUE=false` en desarrollo, pruebas y producción**, sin plan de activar cola. ¿Correcto?
- Si en el futuro migran a VPS con workers, ¿deben poder reactivar cola sin reimplementar?

### 8. Documentación usuario

- ¿Existe procedimiento interno (TI/Compliance) para revisar auditoría que debamos reflejar en `docs/user/audit-log.md`?
- ¿Capacitación a super-admin sobre filtros por área/módulo?

### 9. Límites — qué NO debe cambiar (confirmación)

Confirme explícitamente:

| Componente | Compromiso |
| --- | --- |
| Operaciones → Ajustes → Auditoría | Misma query `forModule('indicadores')`, mismos filtros, misma paginación |
| GH Archivo | Historial `employee_archive_consultations` intacto; sin migración al central |
| Requisiciones edición | `personal_requisition_change_logs` y `status_logs` siguen siendo la fuente de detalle en pantalla Editar |
| Indicadores escritura | Wrapper y eventos actuales sin regresión |

¿Algo más que **no** debamos tocar?

---

## Fuera de alcance (propuesta analista — confirmar)

- Migrar historiales de dominio al central (requisiciones campo a campo, archivo GH, logs de correo).
- Sustituir pantallas de historial embebidas en módulos por el log global.
- Cola async obligatoria o dependencia de `queue:work` en Hostinger.
- Instrumentar áreas sin módulo implementado (gerencia, programación, jurídico, admin_financiero).
- Registro de cada GET/listado/dashboard salvo decisión explícita por módulo.
- Dual-write permanente (escribir en central **y** en tablas legacy).
- Permisos de auditoría **por área** para roles no super-admin (salvo respuesta contraria en pregunta 3).

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | **v1 = Fase 1:** `admin` + `requisitions` resumen + notificaciones; resto en fases 2–3 | Entrega no cumple expectativa de “todas las áreas” en primera release |
| 2 | **`AUDIT_QUEUE=false`** permanente en todos los entornos; documentación FEAT-021 se actualiza | Latencia en acciones pesadas; doc contradictoria con prod |
| 3 | Requisiciones: eventos **resumen** (estado, aprobación, create); **sin** old/new campo a campo en central | Duplicación o confusión con historial Editar si se loguea detalle |
| 4 | Operaciones Ajustes Auditoría **sin cambios** de código/query | Regresión si alguien unifica queries sin flag |
| 5 | GH Archivo: **cero** escritura central en v1 (o solo 1 evento “consulta registrada” si lo piden) | Expectativa de ver detalle documental en Admin global |
| 6 | Admin global: fechas **obligatorias** con default 30 días al cargar | Consultas lentas / timeout en Hostinger |
| 7 | Indicadores visible en Admin global; eventos `info` ocultos por defecto | Usuario esperaba ocultar todo Indicadores en Admin |
| 8 | Solo **super-admin** mantiene `system.view.audit` | Administradores piden acceso |
| 9 | Sin export Excel del log global en v1 | Necesidad inmediata de reportes compliance |
| 10 | Sin migración retroactiva de logs legacy; solo forward | Admin histórico incompleto pre-despliegue |
| 11 | Login/logout **no** se registran en v1 | Brecha en investigación de seguridad |
| 12 | Wrappers delgados por módulo (patrón `Indicadores\AuditLogService`) | Inconsistencia si algún módulo escribe directo al servicio |

---

## Borrador preliminar (NO enviar al Arquitecto hasta cerrar preguntas)

> `BORRADOR — pendiente respuestas usuario y Arquitecto`

| Campo | Valor provisional |
| --- | --- |
| ID | FEAT-025 |
| Módulo / área | **Admin** (lectura global) + instrumentación **cross-módulo** |
| Título | Log general admin cross-módulo (escritura sync) |
| Objetivo | Poblar `audit_logs` desde módulos clave para que `/admin/auditoria` refleje la operación real de la plataforma |
| Incluye (tentativo v1) | Wrappers + puntos de escritura Fase 1; extensión `config/audit.php`; política `AUDIT_QUEUE=false`; tests; actualización docs audit-log |
| Fuera (tentativo) | Migración historiales dominio; cambios UI Operaciones/GH archivo; cola async |
| Permiso | `system.view.audit` (alcance roles a confirmar) |
| Rutas | Sin rutas nuevas salvo export Excel si se confirma |
| BD | Sin cambios de esquema (tabla `audit_logs` existente) |
| Shared-files | `config/audit.php`, múltiples controladores/servicios, `.env.example`, docs |

### Criterios de aceptación (borrador)

1. Tras acciones en módulos v1, `/admin/auditoria` muestra eventos con `module`/`area` correctos.
2. Operaciones → Ajustes → Auditoría sigue mostrando **solo** Indicadores; tests sin regresión.
3. Requisiciones Editar sigue mostrando change_logs/status_logs; **no** desaparecen datos.
4. Con `AUDIT_QUEUE=false`, eventos persisten **sin** ejecutar `queue:work`.
5. GH Archivo sin migración de consultas al central.
6. Documentación técnica y usuario actualizada; checklist anti-fallas incluye sync en prod.

---

## Estado

- [x] Preguntas criticas respondidas (2026-08-11) — listo para Arquitecto
- [ ] Brief final aprobado por Arquitecto

## Respuestas del usuario

*(Completar tras la pausa del AgentSj.)*

| # | Tema | Respuesta |
| --- | --- | --- |
| 1 | Usuarios / objetivo | Solo **super-admin** con `system.view.audit`; soporte e investigacion de incidentes |
| 2 | Modulos v1 | **Fase 1 propuesta:** Admin (usuarios/permisos) + Requisiciones resumen + Notificaciones. Resto fases 2-3 |
| 3 | Permisos | Solo **super-admin** (sin ampliar a administrador ni filtro por area en v1) |
| 4 | Eventos por modulo | Requisiciones: resumen (create, estados, aprobaciones, export); Admin: CRUD usuarios/permisos; sin login/logout v1; sin campo a campo requisiciones |
| 5 | Datos / migracion / retencion | Solo eventos **hacia adelante**; retencion 24 meses (`audit:purge`) sin cambio |
| 6 | Filtros UI Admin | **Default ultimos 30 dias** al abrir; eventos Info Indicadores ocultos (comportamiento actual); **sin** excluir modulo Indicadores por defecto; **sin** export Excel v1 |
| 7 | AUDIT_QUEUE=false permanente | **Confirmado:** sync en todos los entornos; sin `queue:work` |
| 8 | Documentacion operativa | Actualizar docs existentes audit-log al cierre |
| 9 | Limites Operaciones / GH / Requisiciones | **Confirmado:** Operaciones Ajustes intacto; GH Archivo intacto; change_logs/status_logs requisiciones intactos |
